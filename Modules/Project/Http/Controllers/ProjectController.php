<?php

namespace Modules\Project\Http\Controllers;

use App\Models\User;
use App\Models\Customer;
use App\Models\Employee;
// use App\Notifications\ProjectCreatedNotifiaction;
// use App\Notifications\ProjectUpdatedNotification;
use Modules\Project\Entities\Project;
use Modules\Project\Entities\ProjectCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */

	public function index(Request $request)
	{
		$customers = Customer::select('id', 'name')->get(); //Correction
		$categories = ProjectCategory::where('is_active', true)->select('id', 'name')->get();

		if ($request->ajax()) {
			$query = Project::with(['customer:id,name', 'assignedEmployees:id,name']);

			// Searching
			if ($request->has('search.value') && $request->input('search.value') !== '') {
				$search = $request->input('search.value');
				$query->where(function($q) use ($search) {
					$q->where('title', 'like', "%{$search}%")
					  ->orWhereHas('customer', fn($sub) => $sub->where('name', 'like', "%{$search}%"));
				});
			}

			// Ordering
			$orderColumnIndex = $request->input('order.0.column');
			$orderDirection = $request->input('order.0.dir');
			$columns = [
				0 => 'id', 1 => 'title', 2 => 'project_priority', 3 => 'assigned_employee', 4 => 'customer_id', 5 => 'start_date', 6 => 'end_date', 7 => 'project_progress'
			];

			if (!empty($columns[$orderColumnIndex])) {
				$sortColumn = $columns[$orderColumnIndex];
				if ($sortColumn == 'assigned_employee') {
					// Custom sorting for many-to-many is complex, keep it simple for now or use title
				} else {
					$query->orderBy($sortColumn, $orderDirection);
				}
			}

			// Total records before filtering
			$recordsTotal = Project::count();
			$recordsFiltered = $query->count();

			// Pagination
			$start = $request->input('start');
			$length = $request->input('length');
			if ($length != -1) {
				$query->offset($start)->limit($length);
			}
			$projects = $query->get();

			$data = [];
			foreach ($projects as $project) {
				$assigned = $project->assignedEmployees->isEmpty() ? '-' : $project->assignedEmployees->pluck('name')->implode(', ');

				$data[] = [
					'id' => '<div class="checkbox"><input type="checkbox" class="dt-checkboxes" value="'.$project->id.'"><label></label></div>',
					'summary' => '<br><h6><a href="' . route('projects.show', $project->id) . '">' . e($project->title) . '</a></h6>',
					'project_priority' => $project->project_priority ?? '-',
					'assigned_employee' => $assigned,
					'customer' => optional($project->customer)->name,
					'start_date' => $project->start_date,
					'end_date' => $project->end_date,
					'project_status' => ucwords(str_replace('_', ' ', $project->project_status ?? 'not_started')),
					'action' => $this->actionButtons($project)
				];
			}

			return response()->json([
				'draw' => intval($request->input('draw')),
				'recordsTotal' => $recordsTotal,
				'recordsFiltered' => $recordsFiltered,
				'data' => $data,
			]);
		}

		$employees = Employee::where('is_active', true)->get();
		// dd($employees);
		return view('project::backend.project.index', compact('customers', 'employees', 'categories'));
	}

	private function progressBar($progress) 
	{
		$color = $progress > 70 ? 'green' : ($progress > 50 ? 'yellow' : 'red');
		return "{$progress}% complete
			<div class='progress'>
				<div class='progress-bar {$color}' role='progressbar' style='width: {$progress}%' aria-valuenow='{$progress}' aria-valuemin='0' aria-valuemax='100'></div>
			</div>";
	}

	private function actionButtons($data)
	{
		$buttons = '';

        static $permissions = null;
        if ($permissions === null) {
            $user = auth()->user();
            $permissions = \Illuminate\Support\Facades\Cache::remember('role_has_permissions_list' . $user->role_id, 60 * 60 * 24 * 365, function () use ($user) {
                return \Illuminate\Support\Facades\DB::table('permissions')
                    ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                    ->where('role_id', $user->role_id)
                    ->select('permissions.name')
                    ->get();
            })->pluck('name')->toArray();
        }
		if (in_array('project_project_show', $permissions)) {
			$buttons .= '<a id="' . $data->id . '" class="show btn btn-success btn-sm" href="' . route('projects.show', $data) . '"><i class="ti ti-eye"></i></a>';
		}

		if (in_array('project_project_edit', $permissions)) {
			$buttons .= '&nbsp;<button type="button" name="edit" id="' . $data->id . '" class="edit btn btn-primary btn-sm"><i class="ti ti-pencil"></i></button>';
		}

		if (in_array('project_project_delete', $permissions)) {
			$buttons .= '&nbsp;<button type="button" name="delete" id="' . $data->id . '" class="delete btn btn-danger btn-sm"><i class="ti ti-trash"></i></button>';
		}

		return $buttons;
	}


	/**
	 * Store a newly created resource in storage.
	 *
	 * @param Request $request
	 * @return Response
	 */
	public function store(Request $request)
	{
		// dd($request->all());
		$validator = Validator::make($request->only('title', 'customer_id', 'employee_id', 'project_category_id', 'project_priority','project_status', 'description', 'start_date'
			, 'end_date', 'summary'),
			[
				'title' => 'required',
				'customer_id' => 'required',
				'employee_id' => 'required',
				'project_priority' => 'required',
				'project_status' => 'required',
				'start_date' => 'required',
				'end_date' => 'required|after_or_equal:start_date',
			]
		);


		if ($validator->fails())
		{
			return response()->json(['errors' => $validator->errors()->all()]);
		}


		$data = [];

		$data['summary'] = $request->summary;
		$data['title'] = $request->title;
		$data['customer_id'] = $request->customer_id;
		$data['project_category_id'] = $request->project_category_id;
		$data ['start_date'] = $request->start_date;
		$data ['end_date'] = $request->end_date;

		$data ['description'] = $request->description;
		$data ['project_priority'] = $request->project_priority;
		$data ['project_status'] = $request->project_status;


		$project = Project::create($data);
		
		if ($request->employee_id) {
			$project->assignedEmployees()->sync($request->employee_id);
		}

		$success_msg = __('Data Added successfully.');
		$settings = \App\Models\WhatsappSetting::first();
		$isSettingsConfigured = $settings && $settings->phone_number_id && $settings->permanent_access_token;
		$notifyAttempted = false;

		if ($request->is_notify_employee && $request->employee_id) {
			$notifyAttempted = true;
			$employeeModels = Employee::whereIn('id', $request->employee_id)->whereNotNull('phone_number')->get();
			$phoneNumbers = $employeeModels->pluck('phone_number')->toArray();

			if (count($phoneNumbers) > 0 && $isSettingsConfigured) {
				$msg = "New project assigned to you: " . $project->title;
				$settings->sendMessage($phoneNumbers, 'text', $msg);
			}
		}

		if ($request->is_notify_customer && $request->customer_id) {
			$notifyAttempted = true;
			$customer = Customer::find($request->customer_id);
			if ($customer && $customer->wa_number && $isSettingsConfigured) {
				$msg = "A new project has been created for you: " . $project->title;
				$settings->sendMessage([$customer->wa_number], 'text', $msg);
			}
		}

		if ($notifyAttempted) {
			if ($isSettingsConfigured) {
				$success_msg .= ' WhatsApp notifications sent!';
			} else {
				$success_msg .= ' WhatsApp notifications skipped (WhatsApp settings not configured).';
			}
		}

		return response()->json(['success' => $success_msg]);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param Project $project
	 * @return Response
	 */
	public function show(Project $project)
	{
		$name = $project->assignedEmployees->pluck('id')->toArray();

		$employees = Employee::where('is_active', 1)->get();

		return view('project::backend.project.details', compact('project', 'employees', 'name'));
	
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param Project $project
	 * @return Response
	 */
	public function edit($id)
	{
		if (request()->ajax())
		{
			$data = Project::with('assignedEmployees')->findOrFail($id);
			$employee_ids = $data->assignedEmployees->pluck('id');

			return response()->json(['data' => $data, 'employee_ids' => $employee_ids]);
		}

	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param Request $request
	 * @param Project $project
	 * @return Response
	 */
	public function update(Request $request)
	{
		$id = $request->hidden_id;

		$validator = Validator::make($request->only('edit_title', 'edit_customer_id', 'edit_project_category_id', 'edit_project_priority', 'edit_project_status', 'edit_description', 'edit_start_date'
			, 'edit_end_date', 'edit_summary', 'edit_project_progress', 'edit_employee_id'),
			[
				'edit_title' => 'required',
				'edit_customer_id' => 'required',
				'edit_employee_id' => 'required|array',
				'edit_project_priority' => 'required',
				'edit_project_status' => 'required',
				'edit_start_date' => 'required',
				'edit_end_date' => 'required',
			]
		);


		if ($validator->fails())
		{
			return response()->json(['errors' => $validator->errors()->all()]);
		}


		$data = [];

		$data['summary'] = $request->edit_summary;
		$data['title'] = $request->edit_title;
		$data['customer_id'] = $request->edit_customer_id;
		$data['project_category_id'] = $request->edit_project_category_id;
		$data ['start_date'] = $request->edit_start_date;
		$data ['end_date'] = $request->edit_end_date;

		if ($request->edit_description)
		{
			$data ['description'] = $request->edit_description;
		}

		$data ['project_priority'] = $request->edit_project_priority;
		$data ['project_status'] = $request->edit_project_status;
		if ($request->edit_project_progress)
		{
			$data ['project_progress'] = $request->edit_project_progress;
		}


		$project = Project::find($id);
		$project->update($data);

		if ($request->edit_employee_id) {
			$project->assignedEmployees()->sync($request->edit_employee_id);
		}

		$success_msg = __('Data is successfully updated');
		$settings = \App\Models\WhatsappSetting::first();
		$isSettingsConfigured = $settings && $settings->phone_number_id && $settings->permanent_access_token;
		$notifyAttempted = false;

		$employees = $project->assignedEmployees->pluck('id')->toArray();

		if ($request->edit_is_notify_employee && count($employees) > 0) {
			$notifyAttempted = true;
			$employeeModels = Employee::whereIn('id', $employees)->whereNotNull('phone_number')->get();
			$phoneNumbers = $employeeModels->pluck('phone_number')->toArray();

			if (count($phoneNumbers) > 0 && $isSettingsConfigured) {
				$msg = "Project updated: " . $project->title;
				$settings->sendMessage($phoneNumbers, 'text', $msg);
			}
		}

		if ($request->edit_is_notify_customer && $request->edit_customer_id) {
			$notifyAttempted = true;
			$customer = Customer::find($request->edit_customer_id);
			if ($customer && $customer->wa_number && $isSettingsConfigured) {
				$msg = "Your project has been updated: " . $project->title;
				$settings->sendMessage([$customer->wa_number], 'text', $msg);
			}
		}

		if ($notifyAttempted) {
			if ($isSettingsConfigured) {
				$success_msg .= ' WhatsApp notifications sent!';
			} else {
				$success_msg .= ' WhatsApp notifications skipped (WhatsApp settings not configured).';
			}
		}

		return response()->json(['success' => $success_msg]);
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param Project $project
	 * @return Response
	 */
	public function destroy($id)
	{
		if (!config('app.user_verified'))
		{
			return response()->json(['error' => 'This feature is disabled for demo!']);
		}

		Project::whereId($id)->delete();

		return response()->json(['success' => __('Data is successfully deleted')]);

	}


	public function progressStore(Request $request, Project $project)
	{

		$data = [];
		if ($request->project_progress)
		{
			$data['project_progress'] = $request->project_progress;
		}
		if ($request->project_priority)
		{
			$data['project_priority'] = $request->project_priority;
		}
		if ($request->project_status)
		{
			$data['project_status'] = $request->project_status;
		}


		$project->update($data);

		//$assigned = $project->assignedEmployees()->pluck('id');


		// if (sizeof($assigned) == 0)
		// {
		// 	$notificable = User::where('role_users_id', 1)
		// 		->orWhere('id', $project->Customer_id)
		// 		->get();
		// 	Notification::send($notificable, new ProjectUpdatedNotification($project));
		// } else
		// {
		// 	$notificable = User::where('role_users_id', 1)
		// 		->orwhereIntegerInRaw('id', $assigned)
		// 		->orWhere('id', $project->Customer_id)
		// 		->get();
		// 	Notification::send($notificable, new ProjectUpdatedNotification($project));
		// }

		return response()->json(['success' => __('Data is successfully updated')]);
	}


	public function notesStore(Request $request, Project $project)
	{

		$validator = Validator::make($request->only('project_note'),
			[
				'project_note' => 'required',
			]
		);

		if ($validator->fails())
		{
			return response()->json(['errors' => $validator->errors()->all()]);
		}

		$data = [];

		$data['project_note'] = $request->project_note;

		$project->update($data);

		return response()->json(['success' => __('Data is successfully updated')]);
	}

	public function fetchEmployee(Request $request)
	{
		$value = $request->get('value');
		$data = Employee::where('is_active', 1)->get();

		$output = '';
		foreach ($data as $row)
		{
			$output .= '<option value=' . $row->id . '>' . $row->name . '</option>';
		}

		return $output;
	}


}
