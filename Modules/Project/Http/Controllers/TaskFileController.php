<?php

namespace Modules\Project\Http\Controllers;

use Modules\Project\Entities\Task;
use Modules\Project\Entities\TaskFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TaskFileController extends Controller
{

	public function index(Request $request, Task $task)
	{

		if ($request->ajax()) {
			$columns = ['id', 'file_description', 'created_at'];

			$columnIndex = $request->input('order.0.column', 2); // default to last column
			$columnName = $columns[$columnIndex] ?? 'created_at';

			$columnSortOrder = strtolower($request->input('order.0.dir', 'desc'));
			if (!in_array($columnSortOrder, ['asc', 'desc'])) {
				$columnSortOrder = 'desc';
			}

			$length = $request->input('length');

			$searchValue = $request->input('search.value'); // Search value

			$query = TaskFile::with('User:id,name')->where('task_id', $task->id);

			if (!empty($searchValue)) {
				$query->where(function ($q) use ($searchValue) {
					$q->where('file_title', 'LIKE', "%{$searchValue}%")
						->orWhereHas('User', function ($u) use ($searchValue) {
							$u->where('name', 'LIKE', "%{$searchValue}%");
						});
				});

				$totalFiltered = $query->count();
			}

			$total = $query->count();

			if ($length != -1) {
				$query->offset($request->input('start'))->limit($length);
			}

			$files = $query->orderBy($columnName, $columnSortOrder)->get();

			$data = [];

			foreach ($files as $file) {
				$desc = $file->file_description ?? '';
				$desc .= '<br><h6><a href="' . route('tasks.downloadFile', $file->id) . '">' . __('db.Download') . '</a></h6>';

				$action = '<button type="button" name="delete" id="' . $file->id . '" class="delete-file btn btn-danger btn-sm"><i class="ti ti-trash"></i></button>';

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
				'recordsFiltered' => $total,
				'data' => $data,
			]);
		}
	}

	public function store(Request $request, Task $task)
	{
		$validator = Validator::make(
			$request->only('file_title', 'file_description', 'file_attachment'),
			[
				'file_title' => 'required|string',
				'file_description' => 'nullable|string',
				'file_attachment' => 'required|file|max:10240|mimes:jpeg,png,jpg,gif,ppt,pptx,doc,docx,pdf',
			]
		);

		if ($validator->fails()) {
			return response()->json([
				'errors' => $validator->errors()->all()
			], 422);
		}

		$file = $request->file('file_attachment');

		$file_name = null;

		if ($file && $file->isValid()) {
			$file_name = 'task_file_' . time() . '.' . $file->getClientOriginalExtension();

			Storage::disk('public')->putFileAs(
				'task_file_attachments',
				$file,
				$file_name
			);
		}

		$data = [
			'file_description' => $request->file_description,
			'file_title' => $request->file_title,
			'task_id' => $task->id,
			'user_id' => auth()->id(),
			'file_attachment' => $file_name,
		];

		TaskFile::create($data);

		return response()->json([
			'success' => __('Data Added successfully.')
		]);
	}

	public function destroy($id)
	{
		$file = TaskFile::findOrFail($id);

		$file_path = $file->file_attachment;

		$path = "task_file_attachments/{$file_path}";

		if ($file_path && Storage::disk('public')->exists($path)) {
			Storage::disk('public')->delete($path);
		}

		$file->delete();

		return response()->json([
			'success' => __('Data is successfully deleted')
		]);
	}

	public function download($id)
	{
		$file = TaskFile::findOrFail($id);

		$file_path = $file->file_attachment;

		$path = "task_file_attachments/{$file_path}";

		if (Storage::disk('public')->exists($path)) {
			return Storage::disk('public')->download($path);
		}

		abort(404, __('File not Found'));
	}
}
