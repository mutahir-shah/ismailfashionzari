<?php

namespace Modules\Project\Http\Controllers;

use Modules\Project\Entities\Project;
use Modules\Project\Entities\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjectTaskController extends Controller {

	public function index(Request $request, Project $project)
	{
		if ($request->ajax()) {
			$columns = ['task_name', 'end_date', 'task_status', 'added_by', 'id'];

			$columnIndex = $request->input('order.0.column', 0);
			$columnName = $columns[$columnIndex] ?? 'task_name';

			$columnSortOrder = strtolower($request->input('order.0.dir', 'desc'));
			if (!in_array($columnSortOrder, ['asc', 'desc'])) {
				$columnSortOrder = 'desc';
			}

			$length = $request->input('length');
			$searchValue = $request->input('search.value'); // Search value

			$query = Task::with('addedBy:id,name')->where('project_id', $project->id);
			$total = $query->count();
			$totalFiltered = $total;

			if (!empty($searchValue)) {
				$query->where(function ($q) use ($searchValue) {
					$q->where('task_name', 'LIKE', "%{$searchValue}%")
					->orWhereHas('addedBy', function ($u) use ($searchValue) {
						$u->where('name', 'LIKE', "%{$searchValue}%");
					});
				});

				$totalFiltered = $query->count();
			}

			if ($length != -1) {
				$query->offset($request->input('start'))->limit($length);
			}

			$tasks = $query->orderBy($columnName, $columnSortOrder)->get();

			$data = [];

			foreach ($tasks as $task) {
				$username = $task->addedBy->name ?? 'Unknown';

				$action = '<a id="' . $task->id . '" class="show-task btn btn-success btn-sm" href="' . route('tasks.show', $task) . '"><i class="ti ti-eye"></i></a>';
				if (auth()->user()->can('delete-project')) {
					$action .= '&nbsp;&nbsp;<button type="button" name="delete" id="' . $task->id . '" class="delete-task btn btn-danger btn-sm"><i class="ti ti-trash"></i></button>';
				}

				$data[] = [
					'id' => $task->id,
					'task_name' => $task->task_name,
					'end_date' => $task->end_date,
					'task_status' => $task->task_status,
					'created_by' => $username,
					'action' => $action,
				];
			}

			return response()->json([
				'draw' => intval($request->input('draw')),
				'recordsTotal' => $total,
				'recordsFiltered' => $totalFiltered,
				'data' => $data,
			]);
		}
	}

	public function store(Request $request, Project $project)
	{
		$logged_user = auth()->user();


		// if ($logged_user->can('store-project'))
		// {
			$validator = Validator::make($request->only('task_title', 'estimated_hour', 'description', 'start_date'
				, 'end_date'),
				[
					'task_title' => 'required',
					'estimated_hour' => 'required|numeric',
					'start_date' => 'required',
					'end_date' => 'required|after_or_equal:start_date',
				]
			);


			if ($validator->fails())
			{
				return response()->json(['errors' => $validator->errors()->all()]);
			}


			$data = [];

			$data['task_name'] = $request->task_title;
			$data['added_by'] = $logged_user->id;
			$data['project_id'] = $project->id;
			$data ['start_date'] = $request->start_date;
			$data ['end_date'] = $request->end_date;

			$data ['description'] = $request->task_description;
			$data ['task_hour'] = $request->estimated_hour;


			Task::create($data);

			return response()->json(['success' => __('Data Added successfully.')]);
		// }

		// return response()->json(['success' => __('You are not authorized')]);
	}

	public function destroy($id)
	{
		$logged_user = auth()->user();

		if ($logged_user->can('delete-project'))
		{
			$task = Task::findOrFail($id);

			$task->delete();

			return response()->json(['success' => __('Data is successfully deleted')]);
		}
		return response()->json(['success' => __('You are not authorized')]);
	}
}
