<?php

namespace Modules\Project\Http\Controllers;

use Modules\Project\Entities\Project;
use Modules\Project\Entities\Task;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */

	public function index(Request $request)
	{
		$projects = Project::select('id', 'title')->get();

		if ($request->ajax()) {
			$columns = [
				0 => 'id',
				1 => 'task_name',
				2 => 'start_date',
				3 => 'end_date',
				4 => 'task_status',
				5 => 'assigned_employee',
				6 => 'created_by',
			];

			$query = Task::with(['project:id,title', 'addedBy:id,name', 'assignedEmployees:id,name']);

			if (!empty($request->input('search.value'))) {
				$search = $request->input('search.value');
				$query->where(function ($q) use ($search) {
					$q->where('task_name', 'LIKE', "%{$search}%")
						->orWhere('task_status', 'LIKE', "%{$search}%");
				});
			}

			$totalData = Task::count();
			$totalFiltered = $query->count();

			$limit = $request->input('length');
			$start = $request->input('start');
			$orderColumnIndex = $request->input('order.0.column');
			$orderColumn = $columns[$orderColumnIndex] ?? 'id';
			$orderDir = in_array($request->input('order.0.dir'), ['asc', 'desc']) ? $request->input('order.0.dir') : 'asc';

			if ($orderColumn == 'assigned_employee') {
				$orderColumn = 'id'; // Placeholder for complex relation sorting
			}

			if ($limit != -1) {
				$query->offset($start)->limit($limit);
			}

			$tasks = $query->orderBy($orderColumn, $orderDir)->get();

			$data = [];

            $user = auth()->user();
            $permissions = \Illuminate\Support\Facades\Cache::remember('role_has_permissions_list' . $user->role_id, 60 * 60 * 24 * 365, function () use ($user) {
                return \Illuminate\Support\Facades\DB::table('permissions')
                    ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                    ->where('role_id', $user->role_id)
                    ->select('permissions.name')
                    ->get();
            })->pluck('name')->toArray();

			foreach ($tasks as $task) {
				$assigned = $task->assignedEmployees->isEmpty() ? '-' : $task->assignedEmployees->pluck('name')->implode(', ');

				$taskName = $task->task_name . '<br><h6><a href="' . route('projects.show', $task->project_id) . '">' . ($task->project->title ?? '') . '</a></h6>';

				$data[] = [
					'id' => $task->id,
					'task_name' => $taskName,
					'start_date' => $task->start_date,
					'end_date' => $task->end_date,
					'task_status' => $task->task_status,
					'assigned_employee' => $assigned,
					'created_by' => $task->addedBy->name ?? '',
					'action' => (in_array('project_task_show', $permissions) ? '<a id="' . $task->id . '" class="show btn btn-success btn-sm" href="' . route('tasks.show', $task) . '"><i class="ti ti-eye"></i></a>' : '') .
						(in_array('project_task_edit', $permissions) ? '&nbsp;&nbsp;<button type="button" name="edit" id="' . $task->id . '" class="edit btn btn-primary btn-sm"><i class="ti ti-pencil"></i></button>' : '') .
						(in_array('project_task_delete', $permissions) ? '&nbsp;&nbsp;<button type="button" name="delete" id="' . $task->id . '" class="delete btn btn-danger btn-sm"><i class="ti ti-trash"></i></button>' : '')
				];
			}

			return response()->json([
				'draw' => intval($request->input('draw')),
				'recordsTotal' => $totalData,
				'recordsFiltered' => $totalFiltered,
				'data' => $data,
			]);
		}

		$employees = Employee::where('is_active', true)->get();
		return view('project::backend.task.index', compact('projects', 'employees'));
	}

	private function progressBar($progress)
	{
		$color = $progress > 70 ? 'green' : ($progress > 50 ? 'yellow' : 'red');
		return "{$progress}% complete
			<div class='progress'>
				<div class='progress-bar {$color}' role='progressbar' style='width: {$progress}%' aria-valuenow='{$progress}' aria-valuemin='0' aria-valuemax='100'></div>
			</div>";
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		//
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param Request $request
	 * @return Response
	 */
	public function store(Request $request)
	{
		$validator = Validator::make(
			$request->only(
				'task_name',
				'project_id',
				'employee_id',
				'description',
				'start_date',
				'end_date',
				'task_hour'
			),
			[
				'task_name' => 'required',
				'project_id' => 'required',
				'employee_id' => 'required',
				'task_hour' => 'required|numeric',
				'start_date' => 'required',
				'end_date' => 'required|after_or_equal:start_date',
			]
		);

		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()->all()]);
		}

		$data = [];

		$data['task_hour'] = $request->task_hour;
		$data['task_name'] = $request->task_name;
		$data['project_id'] = $request->project_id;
		$data['start_date'] = $request->start_date;
		$data['end_date'] = $request->end_date;

		$data['description'] = $request->description;
		$data['added_by'] = auth()->user()->id;

		$task = Task::create($data);
		$employees = $request->input('employee_id');
		if ($employees) {
			$task->assignedEmployees()->sync($employees);
		}

		$success_msg = __('Data Added successfully.');
		
		if ($request->is_notify && $employees) {
			$employeeModels = Employee::whereIn('id', $employees)->whereNotNull('phone_number')->get();
			$phoneNumbers = $employeeModels->pluck('phone_number')->toArray();

			if (count($phoneNumbers) > 0) {
				$settings = \App\Models\WhatsappSetting::first();
				if ($settings && $settings->phone_number_id && $settings->permanent_access_token) {
					$msg = "New task assigned to you: " . $task->task_name;
					$result = $settings->sendMessage($phoneNumbers, 'text', $msg);
					if ($result['success'] ?? false) {
						$success_msg .= ' WhatsApp notification sent!';
					} else {
						$success_msg .= ' Failed to send WhatsApp notification: ' . ($result['message'] ?? 'Unknown error');
					}
				} else {
					$success_msg .= ' WhatsApp notification skipped (WhatsApp settings not configured).';
				}
			}
		}

		return response()->json(['success' => $success_msg]);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param Project $task
	 * @return Response
	 */
	public function show(Task $task)
	{
		$name = $task->assignedEmployees->pluck('id')->toArray();

		$employees = Employee::where('is_active', 1)->get();

		return view('project::backend.task.details', compact('task', 'employees', 'name'));
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param Project $task
	 * @return Response
	 */
	public function edit($id)
	{
		if (request()->ajax()) {
			$data = Task::with('assignedEmployees')->findOrFail($id);
			$employee_ids = $data->assignedEmployees->pluck('id');

			return response()->json(['data' => $data, 'employee_ids' => $employee_ids]);
		}
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param Request $request
	 * @param Project $task
	 * @return Response
	 */
	public function update(Request $request)
	{
		$id = $request->hidden_id;

		$validator = Validator::make(
			$request->only(
				'edit_task_name',
				'edit_project_id',
				'edit_employee_id',
				'edit_task_hour',
				'edit_description',
				'edit_start_date',
				'edit_end_date',
				'edit_task_status'
			),
			[
				'edit_task_name' => 'required',
				'edit_project_id' => 'required',
				'edit_employee_id' => 'required|array',
				'edit_task_hour' => 'required',
				'edit_start_date' => 'required',
				'edit_end_date' => 'required|after_or_equal:edit_start_date',
			]
		);


		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()->all()]);
		}


		$data = [];

		$data['task_name'] = $request->edit_task_name;
		$data['project_id'] = $request->edit_project_id;
		$data['start_date'] = $request->edit_start_date;
		$data['end_date'] = $request->edit_end_date;

		if ($request->edit_description) {
			$data['description'] = $request->edit_description;
		}

		$data['task_hour'] = $request->edit_task_hour;
		$data['task_status'] = $request->edit_task_status;


		$task = Task::find($id);
		$task->update($data);

		$employees = $request->input('edit_employee_id');
		if ($employees) {
			$task->assignedEmployees()->sync($employees);
		}

		$success_msg = __('Data is successfully updated');
// dd($request->all());
		if ($request->edit_is_notify && $employees) {
			$employeeModels = Employee::whereIn('id', $employees)->whereNotNull('phone_number')->get();
			$phoneNumbers = $employeeModels->pluck('phone_number')->toArray();

			if (count($phoneNumbers) > 0) {
				$settings = \App\Models\WhatsappSetting::first();
				if ($settings && $settings->phone_number_id && $settings->permanent_access_token) {
					$msg = "Task updated: " . $task->task_name;
					$result = $settings->sendMessage($phoneNumbers, 'text', $msg);
					if ($result['success'] ?? false) {
						$success_msg .= ' WhatsApp notification sent!';
					} else {
						$success_msg .= ' Failed to send WhatsApp notification: ' . ($result['message'] ?? 'Unknown error');
					}
				} else {
					$success_msg .= ' WhatsApp notification skipped (WhatsApp settings not configured).';
				}
			}
		}

		return response()->json(['success' => $success_msg]);
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param Project $task
	 * @return Response
	 */
	public function destroy($id)
	{
		if (!config('app.user_verified')) {
			return response()->json(['error' => 'This feature is disabled for demo!']);
		}

		Task::whereId($id)->delete();

		return response()->json(['success' => __('Data is successfully deleted')]);
	}


	public function progressStore(Request $request, Task $task)
	{
		$validator = Validator::make(
			$request->only('task_hour'),
			[
				'task_hour' => 'required|numeric',
			]
		);


		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()->all()]);
		}

		$data = [];


		$data['task_hour'] = $request->task_hour;

		if ($request->task_status) {
			$data['task_status'] = $request->task_status;
		}


		$task->update($data);

		return response()->json(['success' => __('Data is successfully updated')]);
	}


	public function notesStore(Request $request, Task $task)
	{
		$validator = Validator::make(
			$request->only('task_note'),
			[
				'task_note' => 'required',
			]
		);


		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()->all()]);
		}


		$data = [];

		$data['task_note'] = $request->task_note;

		$task->update($data);

		return response()->json(['success' => __('Data is successfully updated')]);
	}
}
