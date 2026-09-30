<?php

namespace App\Http\Controllers;

use App\Models\Barcode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class BarcodeController extends Controller
{
    private function hasPermission()
    {
        $role = Role::find(Auth::user()->role_id);
        return $role->hasPermissionTo('barcode_setting');
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!$this->hasPermission()) {
            return redirect('/dashboard')
                ->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        return view('backend.barcode.index');
    }

    public function barcodeData(Request $request)
    {
        if (!$this->hasPermission()) {
            return redirect('/dashboard')
                ->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        $columns = array(
            0 => 'id',
            1 => 'name',
            2 => 'name',
            3 => 'description',
        );

        $totalData = DB::table('barcodes')->where('is_custom',true)->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')] ?? 'id';
        $dir = $request->input('order.0.dir');
        if(empty($request->input('search.value')))
            $barcodes = Barcode::offset($start)
                        ->where('is_custom',true)
                        ->limit($limit)
                        ->orderBy($order,$dir)
                        ->get();
        else
        {
            $search = $request->input('search.value');
            $barcodes =  Barcode::where([
                            ['name', 'LIKE', "%{$search}%"],
                            ['is_custom', true]
                        ])->offset($start)
                        ->limit($limit)
                        ->orderBy($order,$dir)->get();

            $totalFiltered = Barcode::where([
                                ['name', 'LIKE', "%{$search}%"],
                                ['is_custom', true]
                        ])->count();
        }
        $data = array();
        if(!empty($barcodes))
        {
            foreach ($barcodes as $key=>$barcode)
            {
                $nestedData['id'] = $barcode->id;
                $nestedData['key'] = $key;
                $nestedData['name'] = $barcode->name;
                $nestedData['description'] = $barcode->description;

                $nestedData['options'] = '<div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'. __('db.action') .'
                            <span class="caret"></span>
                            <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <a  href="'.route('barcodes.edit', $barcode->id).'" class="btn btn-link"><i class="ti ti-edit"></i> '. __('db.edit') .'</a>
                                </li>
                                <li class="divider"></li>
                                <form action="' . route("barcodes.destroy", $barcode->id) . '" method="POST">'.csrf_field().'' . method_field("DELETE") . '
                                <li>
                                <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="ti ti-trash"></i> '. __('db.delete') .'</button>
                                </li></form>
                            </ul>
                        </div>';
                $data[] = $nestedData;
            }
        }
        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
            );

        echo json_encode($json_data);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!$this->hasPermission()) {
            return redirect('/dashboard')
                ->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        return view('backend.barcode.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!$this->hasPermission()) {
            return redirect('/dashboard')
                ->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        try {
            $input = $request->only(['name', 'description', 'width', 'height', 'top_margin',
                'left_margin', 'row_distance', 'col_distance',
                'stickers_in_one_row', 'paper_width','is_custom', 'layout_type' ]);

            if (! empty($request->input('is_default'))) {
                //get_default
                $default = Barcode::where('is_default', 1)
                                ->update(['is_default' => 0]);
                $input['is_default'] = 1;
            }
            if ($request->layout_type === 'continuous') {
                $input['is_continuous'] = 1;
                $input['stickers_in_one_sheet'] = 28;
                $input['paper_height'] = 0;
            } else {
                $input['is_continuous'] = 0;
                $input['stickers_in_one_sheet'] = $request->input('stickers_in_one_sheet');

                // Dumbbell uses its own label height
                if ($request->layout_type === 'dumbbell') {
                    $input['paper_height'] = 0;
                } else {
                    $input['paper_height'] = $request->input('paper_height');
                }
            }
            $barcode = Barcode::create($input);
            $output = ['success' => 1,
                'msg' => __('barcode.added_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect('barcodes')->with('status', $output);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        if (!$this->hasPermission()) {
            return redirect('/dashboard')
                ->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        $barcode = Barcode::where('is_custom',true)->find($id);

        return view('backend.barcode.edit',compact('barcode'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (!$this->hasPermission()) {
            return redirect('/dashboard')
                ->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        try {
            $input = $request->only([
                'name',
                'description',
                'width',
                'height',
                'top_margin',
                'left_margin',
                'row_distance',
                'col_distance',
                'stickers_in_one_row',
                'paper_width',
                'is_custom',
                'layout_type',
            ]);

            if ($request->layout_type === 'continuous') {
                $input['is_continuous'] = 1;
                $input['stickers_in_one_sheet'] = 28;
                $input['paper_height'] = 0;
            } else {
                $input['is_continuous'] = 0;
                $input['stickers_in_one_sheet'] = $request->stickers_in_one_sheet;

                // Dumbbell uses its own label height
                if ($request->layout_type === 'dumbbell') {
                    $input['paper_height'] = 0;
                } else {
                    $input['paper_height'] = $request->paper_height;
                }
            }

            if ($request->filled('is_default')) {
                Barcode::where('is_default', 1)->update(['is_default' => 0]);
                $input['is_default'] = 1;
            }

            Barcode::where('id', $id)->update($input);

            $output = [
                'success' => 1,
                'msg' => __('barcode.updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency(
                'File:' . $e->getFile() .
                ' Line:' . $e->getLine() .
                ' Message:' . $e->getMessage()
            );

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect('barcodes')->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $barcode = Barcode::find($id);

        if (!$barcode) {
            return redirect('barcodes')->with('status', [
                'success' => 0,
                'msg' => __('db.something_went_wrong'),
            ]);
        }

        $barcode->delete();

        return redirect('barcodes')->with('status',  [
            'success' => 1,
            'msg' => __('db.deleted_success'),
        ]);
    }
}
