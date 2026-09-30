<?php

namespace Modules\Project\Http\Controllers;

use App\Models\Employee;
use Modules\Project\Entities\Task;
use Modules\Project\Entities\TaskDiscussion;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskDiscussionController extends Controller {

	public function index(Request $request, Task $task)
	{
		if ($request->ajax()) {
			$columns = ['id','user_id','task_discussion'];

			$columnIndex = $request->input('order.0.column', 2); // default to last column
			$columnName = $columns[$columnIndex] ?? 'created_at';

			$columnSortOrder = strtolower($request->input('order.0.dir', 'desc'));
			if (!in_array($columnSortOrder, ['asc', 'desc'])) {
				$columnSortOrder = 'desc';
			}

			$length = $request->input('length');

			$searchValue = $request->input('search.value'); // Search value

			$query = TaskDiscussion::with('User:id,name')->where('task_id', $task->id);

			if (!empty($searchValue)) {
				$query->where(function ($q) use ($searchValue) {
					$q->where('task_discussion', 'like', "%$searchValue%");
				});
			}

			$total = $query->count();

			if ($length != -1) {
				$query->offset($request->input('start'))->limit($length);
			}

			$discussions = $query->orderBy($columnName, $columnSortOrder)->get();

			$data = [];

			foreach ($discussions as $row) {
				$username = $row->User->name ?? 'Unknown';

				try {
					$department = Employee::where('user_id', $row->user_id ?? 0)
						->join('departments', 'employees.department_id', '=', 'departments.id')
						->value('departments.name');
				} catch (Exception $e) {
					$department = __('db.admin');
				}

				$department = $department ?? '';

				$userColumn = $username . ' (' . $department . ')';

				$actionColumn = '<button type="button" name="delete" id="' . $row->id . '" class="delete-discussion btn btn-danger btn-sm"><i class="ti ti-trash"></i></button>';

				$dateFormat = \App\Models\GeneralSetting::first()->date_format ?? 'd-m-Y';

				$data[] = [
					'id' => $row->id,
					'user' => $userColumn,
					'message' => $row->task_discussion,
					'action' => $actionColumn,
					'created_at' => \Carbon\Carbon::parse(str_replace('--', '-', $row->created_at))->format($dateFormat . ' h:i A'),
				];
			}

			return response()->json([
				'draw' => intval($request->input('draw')),
				'recordsTotal' => $total,
				'recordsFiltered' => $total,
				'data' => $data,
			]);
		}
	}

	public function store(Request $request, Task $task)
	{

		$validator = Validator::make($request->only('task_discussions'),
			[
				'task_discussions' => 'required',
			]
		);


		if ($validator->fails())
		{
			return response()->json(['errors' => $validator->errors()->all()]);
		}

		$data = [];

		$data['task_discussion'] = $request->get('task_discussions');
		$data['user_id'] = auth()->user()->id;
		$data ['task_id'] = $task->id;

		TaskDiscussion::create($data);

		return response()->json(['success' => __('Data Added successfully.')]);
	}


	public function destroy($id)
	{
		$task = TaskDiscussion::findOrFail($id);

		$task->delete();

		return response()->json(['success' => __('Data is successfully deleted')]);
	}
}
