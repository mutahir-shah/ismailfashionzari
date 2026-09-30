<?php

namespace Modules\Project\Http\Controllers;

use Modules\Project\Entities\Project;
use Modules\Project\Entities\ProjectBug;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjectBugController extends Controller {

	public function index(Request $request, Project $project)
	{

		if ($request->ajax()) {
			$columns = ['user_id','title','id'];

			$columnIndex = $request->input('order.0.column', 1); // default to title
			$columnName = $columns[$columnIndex] ?? 'title';

			$columnSortOrder = strtolower($request->input('order.0.dir', 'desc'));
			if (!in_array($columnSortOrder, ['asc', 'desc'])) {
				$columnSortOrder = 'desc';
			}

			$length = $request->input('length');

			$searchValue = $request->input('search.value'); // Search value

			$query = ProjectBug::with('User:id,name')
				->where('project_id', $project->id);

			$total = ProjectBug::where('project_id', $project->id)->count();
			$totalFiltered = $total;

			if (!empty($searchValue)) {
				$query->where(function ($q) use ($searchValue) {
					$q->where('title', 'LIKE', "%{$searchValue}%")
					->orWhereHas('User', function ($u) use ($searchValue) {
						$u->where('name', 'LIKE', "%{$searchValue}%");
					});
				});

				$totalFiltered = $query->count();
			}

			if ($length != -1) {
				$query->offset($request->input('start'))->limit($length);
			}

			$bugs = $query->orderBy($columnName, $columnSortOrder)->get();

			$data = [];

			foreach ($bugs as $bug) {
				$username = $bug->User->name ?? 'Unknown';
				
				// Fetching department via the main Employee model
				$department = __('db.Admin Only');
				try {
					$dept_name = \App\Models\Employee::join('departments', 'employees.department_id', '=', 'departments.id')
										->where('employees.user_id', $bug->user_id)
										->value('departments.name');
					if ($dept_name) {
						$department = $dept_name;
					}
				} catch (\Exception $e) {
					// Fallback if query fails
				}

				$userColumn = $username . ' (' . $department . ')';

				$titleColumn = $bug->title;
				if ($bug->bug_attachment) {
					$titleColumn .= '<br><h6><a href="' . route('projects.downloadBug', $bug->id) . '">' . __('db.Download') . '</a></h6>';
				}

				$actionColumn = '<button type="button" name="edit" id="' . $bug->id . '" class="edit-bug btn btn-primary btn-sm"><i class="ti ti-pencil"></i></button>';
				if (auth()->user()->can('delete-project')) {
					$actionColumn .= '&nbsp;&nbsp;<button type="button" name="delete" id="' . $bug->id . '" class="delete-bug btn btn-danger btn-sm"><i class="ti ti-trash"></i></button>';
				}

				$data[] = [
					'id' => $bug->id,
					'user' => $userColumn,
					'title' => $titleColumn,
					'action' => $actionColumn,
					'status' => $bug->status,
					'created_at' => (string)$bug->created_at,
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

		$validator = Validator::make($request->only('bugs_title', 'bug_attachment'),
			[
				'bugs_title' => 'required',
				'bug_attachment' => 'nullable|file|max:10240|mimes:jpeg,png,jpg,gif,ppt,pptx,doc,docx,pdf',
			]
		);


		if ($validator->fails())
		{
			return response()->json(['errors' => $validator->errors()->all()]);
		}

		$data = [];

		$data['title'] = $request->get('bugs_title');
		$data['user_id'] = $logged_user->id;
		$data ['project_id'] = $project->id;

		$file = $request->bug_attachment;

		$file_name = null;

		if (isset($file))
		{
			if ($file->isValid())
			{
				$file_name = 'bug' . time() . '.' . $file->getClientOriginalExtension();
				$file->storeAs('public/project_bug_attachments', $file_name);
				$data['bug_attachment'] = $file_name;
			}
		}

		ProjectBug::create($data);

		return response()->json(['success' => __('Data Added successfully.')]);
	}


	public function editStatus($id)
	{

		$bug = ProjectBug::findOrFail($id);

		return response()->json(['id' => $bug->id, 'status' => $bug->status]);
	}

	public function updateStatus(Request $request)
	{
		$id = $request->bug_status_id;


		$data = [];

		$data['status'] = $request->bug_status;

		ProjectBug::whereId($id)->update($data);

		return response()->json(['success' => __('Data is successfully updated')]);

	}

	public function destroy($id)
	{
		$logged_user = auth()->user();

		if ($logged_user->can('delete-project'))
		{
			$bug = ProjectBug::findOrFail($id);
			$file_path = $bug->discussion_attachment;

			if ($file_path)
			{
				$file_path = storage_path('app/public/project_bug_attachments/' . $file_path);
				if (file_exists($file_path))
				{
					unlink($file_path);
				}
			}

			$bug->delete();

			return response()->json(['success' => __('Data is successfully deleted')]);
		}

		return abort('403', __('You are not authorized'));
	}

	public function download($id)
	{

		$file = ProjectBug::findOrFail($id);

		$file_path = $file->bug_attachment;

		$download_path = storage_path("app/public/project_bug_attachments/" . $file_path);

		if (file_exists($download_path))
		{
			$response = response()->download($download_path);

			return $response;
		} else
		{
			return abort('404', __('File not Found'));
		}
	}

}
