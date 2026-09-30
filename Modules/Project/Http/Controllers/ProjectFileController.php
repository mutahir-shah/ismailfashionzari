<?php

namespace Modules\Project\Http\Controllers;


use Modules\Project\Entities\Project;
use Modules\Project\Entities\ProjectFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjectFileController extends Controller {

	public function index(Request $request, Project $project)
	{
		if ($request->ajax()) {
			$columns = ['file_title','file_description','created_at','id'];

			$columnIndex = $request->input('order.0.column', 2); // default to created_at
			$columnName = $columns[$columnIndex] ?? 'created_at';

			$columnSortOrder = strtolower($request->input('order.0.dir', 'desc'));
			if (!in_array($columnSortOrder, ['asc', 'desc'])) {
				$columnSortOrder = 'desc';
			}

			$length = $request->input('length');
			$searchValue = $request->input('search.value'); // Search value

			$query = ProjectFile::where('project_id', $project->id);
			$total = $query->count();
			$totalFiltered = $total;

			if (!empty($searchValue)) {
				$query->where(function ($q) use ($searchValue) {
					$q->where('file_title', 'LIKE', "%{$searchValue}%")
					->orWhere('file_description', 'LIKE', "%{$searchValue}%");
				});

				$totalFiltered = $query->count();
			}

			if ($length != -1) {
				$query->offset($request->input('start'))->limit($length);
			}

			$files = $query->orderBy($columnName, $columnSortOrder)->get();

			$data = [];

			foreach ($files as $file) {
				$desc = $file->file_description ?? '';
				$desc .= '<br><h6><a href="' . route('projects.downloadFile', $file->id) . '">' . __('db.Download') . '</a></h6>';

				$action = '';
				if (auth()->user()->can('delete-project')) {
					$action = '<button type="button" name="delete" id="' . $file->id . '" class="delete-file btn btn-danger btn-sm"><i class="ti ti-trash"></i></button>';
				}

				$data[] = [
					'id' => $file->id,
					'file_title' => $file->file_title,
					'file_description' => $desc,
					'created_at' => (string)$file->created_at,
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

		$validator = Validator::make($request->only('file_title', 'file_description', 'file_attachment'),
			[
				'file_title' => 'required',
				'file_attachment' => 'required|file|max:10240|mimes:jpeg,png,jpg,gif,ppt,pptx,doc,docx,pdf',
			]
		);


		if ($validator->fails())
		{
			return response()->json(['errors' => $validator->errors()->all()]);
		}

		$data = [];

		$data['file_description'] = $request->get('file_description');
		$data['file_title'] = $request->file_title;
		$data ['project_id'] = $project->id;

		$file = $request->file_attachment;

		$file_name = null;

		if (isset($file))
		{
			if ($file->isValid())
			{
				$file_name = 'project_file_' . time() . '.' . $file->getClientOriginalExtension();
				$file->storeAs('public/project_file_attachments', $file_name);
				$data['file_attachment'] = $file_name;
			}
		}

		ProjectFile::create($data);

		return response()->json(['success' => __('Data Added successfully.')]);
	}


	public function destroy($id)
	{
		$logged_user = auth()->user();

		if ($logged_user->can('delete-project'))
		{
			$file = ProjectFile::findOrFail($id);
			$file_path = $file->file_attachment;

			if ($file_path)
			{
				$file_path = storage_path('app/public/project_file_attachments/' . $file_path);
				if (file_exists($file_path))
				{
					unlink($file_path);
				}
			}

			$file->delete();

			return response()->json(['success' => __('Data is successfully deleted')]);
		}

		return response()->json(['success' => __('You are not authorized')]);
	}

	public function download($id)
	{

		$file = ProjectFile::findOrFail($id);

		$file_path = $file->file_attachment;

		$download_path = storage_path("app/public/project_file_attachments/" . $file_path);

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
