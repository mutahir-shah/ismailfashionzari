<?php

namespace Modules\Project\Http\Controllers;

use App\Models\Employee;
use Modules\Project\Entities\Project;
use Modules\Project\Entities\ProjectDiscussion;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjectDiscussionController extends Controller {

	public function index(Request $request, Project $project)
	{
		if ($request->ajax()) {
			$columns = ['id', 'project_discussion', 'created_at']; // actual DB fields

			$columnIndex = $request->input('order.0.column', 2); // default to last column
			$columnName = $columns[$columnIndex] ?? 'created_at';

			$columnSortOrder = strtolower($request->input('order.0.dir', 'desc'));
			if (!in_array($columnSortOrder, ['asc', 'desc'])) {
				$columnSortOrder = 'desc';
			}

			$length = $request->input('length');

			$searchValue = $request->input('search.value'); // Search value

			$query = ProjectDiscussion::with('User:id,name')
						->where('project_id', $project->id);

			if (!empty($searchValue)) {
				$query->where(function ($q) use ($searchValue) {
					$q->where('project_discussion', 'like', "%$searchValue%");
				});
			}

			$total = $query->count();

			if ($length != -1) {
				$query->offset($request->input('start'))->limit($length);
			}

			$discussions = $query->orderBy($columnName, $columnSortOrder)->get();

			$data = [];

			foreach ($discussions as $row) {
				$username = $row->User->name ?? '';

				$userColumn = $username;

				$messageColumn = $row->project_discussion;
				if ($row->discussion_attachment) {
					$messageColumn .= '<br><h6><a href="' . route('projects.downloadAttachment', $row->id) . '">' . __('db.Download') . '</a></h6>';
				}

				$actionColumn = '<button type="button" name="delete" id="' . $row->id . '" class="delete-discussion btn btn-danger btn-sm"><i class="ti ti-trash"></i></button>';

				$dateFormat = \App\Models\GeneralSetting::first()->date_format ?? 'd-m-Y';

				$data[] = [
					'user' => $userColumn,
					'message' => $messageColumn,
					'created_at' => Carbon::parse(str_replace('--', '-', $row->created_at))->format($dateFormat . ' h:i A'),
					'action' => $actionColumn,
				];
			}

			return response()->json([
				"draw" => intval($request->input('draw')),
				"recordsTotal" => $total,
				"recordsFiltered" => $total,
				"data" => $data,
			]);
		}

		return view('project::backend.discussions.index', compact('project'));
	}

	public function store(Request $request, Project $project)
	{
		$logged_user = auth()->user();


		$validator = Validator::make($request->only('project_discussions', 'discussion_attachments'),
			[
				'project_discussions' => 'required',
				'discussion_attachments' => 'nullable|file|max:10240|mimes:jpeg,png,jpg,gif,ppt,pptx,doc,docx,pdf',
			]
		);


		if ($validator->fails())
		{
			return response()->json(['errors' => $validator->errors()->all()]);
		}

		$data = [];

		$data['project_discussion'] = $request->get('project_discussions');
		$data['user_id'] = $logged_user->id;
		$data ['project_id'] = $project->id;

		$file = $request->discussion_attachments;

		$file_name = null;

		if (isset($file))
		{
			if ($file->isValid())
			{
				$file_name = 'discussion_' . time() . '.' . $file->getClientOriginalExtension();
				$file->storeAs('public/project_discussion_attachments', $file_name);
				$data['discussion_attachment'] = $file_name;
			}
		}

		ProjectDiscussion::create($data);

		return response()->json(['success' => __('Data Added successfully.')]);
	}


	public function destroy($id)
	{

		$project = ProjectDiscussion::findOrFail($id);
		$file_path = $project->discussion_attachment;

		if ($file_path)
		{
			$file_path = storage_path('app/public/project_discussion_attachments/' . $file_path);
			if (file_exists($file_path))
			{
				unlink($file_path);
			}
		}

		$project->delete();

		return response()->json(['success' => __('Data is successfully deleted')]);
	}


	public function download($id)
	{

		$file = ProjectDiscussion::findOrFail($id);

		$file_path = $file->discussion_attachment;

		$download_path = storage_path("app/public/project_discussion_attachments/" . $file_path);

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
