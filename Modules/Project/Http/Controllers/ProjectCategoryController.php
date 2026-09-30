<?php

namespace Modules\Project\Http\Controllers;

use Modules\Project\Entities\ProjectCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjectCategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ProjectCategory::query();

            if ($request->has('search.value') && $request->input('search.value') !== '') {
                $search = $request->input('search.value');
                $query->where('name', 'like', "%{$search}%");
            }

            $recordsTotal = ProjectCategory::count();

            $orderColumnIndex = $request->input('order.0.column');
            $orderDirection = $request->input('order.0.dir');
            $columns = ['id', 'name', 'is_active'];
            if (isset($columns[$orderColumnIndex])) {
                $query->orderBy($columns[$orderColumnIndex], $orderDirection);
            }

            $recordsFiltered = $query->count();

            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            if ($length != -1) {
                $query->offset($start)->limit($length);
            }
            $categories = $query->get();

            $data = [];
            foreach ($categories as $category) {
                $data[] = [
                    'id' => $category->id,
                    'DT_RowId' => $category->id,
                    'name' => e($category->name),
                    'is_active' => $category->is_active ? __('db.Active') : __('db.Inactive'),
                    'action' => $this->actionButtons($category)
                ];
            }

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data
            ]);
        }

        return view('project::backend.project_category.index');
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

        if (in_array('project_category_edit', $permissions)) {
            $buttons .= '<button type="button" name="edit" id="' . $data->id . '" class="edit btn btn-primary btn-sm"><i class="ti ti-pencil"></i></button>';
        }

        if (in_array('project_category_delete', $permissions)) {
            $buttons .= '&nbsp;<button type="button" name="delete" id="' . $data->id . '" class="delete btn btn-danger btn-sm"><i class="ti ti-trash"></i></button>';
        }

        return $buttons;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:project_categories,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()]);
        }

        ProjectCategory::create([
            'name' => $request->name,
            'is_active' => $request->is_active,
        ]);

        return response()->json(['success' => __('Data Added successfully.')]);
    }

    public function edit($id)
    {
        if (request()->ajax()) {
            $data = ProjectCategory::findOrFail($id);
            return response()->json(['data' => $data]);
        }
    }

    public function update(Request $request)
    {
        $id = $request->hidden_id;

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:project_categories,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()]);
        }

        ProjectCategory::whereId($id)->update([
            'name' => $request->name,
            'is_active' => $request->is_active,
        ]);

        return response()->json(['success' => __('Data is successfully updated')]);
    }

    public function destroy($id)
    {
        if (!config('app.user_verified')) {
            return response()->json(['error' => 'This feature is disabled for demo!']);
        }

        $category = ProjectCategory::findOrFail($id);
        $category->delete();

        return response()->json(['success' => __('Data is successfully deleted')]);
    }
}
