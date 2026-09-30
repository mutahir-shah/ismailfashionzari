<?php

namespace App\Http\Controllers;

use Auth;
use App\Models\Product;
use App\Models\Warehouse;
use App\Traits\CacheForget;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Product_Warehouse;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    use CacheForget;
    public function index()
    {
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        $numberOfWarehouse = Warehouse::where('is_active', true)->count();
        return view('backend.warehouse.create', compact('lims_warehouse_list', 'numberOfWarehouse'));
    }

    public function warehouseData(Request $request)
    {
        $columns = [
            1 => 'warehouses.name',
            2 => 'warehouses.phone',
            3 => 'warehouses.email',
            4 => 'warehouses.address',
            5 => 'number_of_product',
            6 => 'stock_qty',
        ];
    
        $totalData = Warehouse::where('is_active', true)->count();
        $totalFiltered = $totalData;
    
        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')] ?? 'warehouses.id';
        $dir = $request->input('order.0.dir', 'asc');
    
        $query = Warehouse::select('warehouses.*')
                // Subquery 1: Count of active products in this warehouse
                ->selectRaw('(
                    SELECT COUNT(DISTINCT product_id) 
                    FROM product_warehouse 
                    JOIN products ON product_warehouse.product_id = products.id 
                    WHERE product_warehouse.warehouse_id = warehouses.id 
                    AND products.is_active = 1
                ) as number_of_product')
                // Subquery 2: Sum of stock quantity in this warehouse
                ->selectRaw('(
                    SELECT COALESCE(SUM(qty), 0) 
                    FROM product_warehouse 
                    WHERE product_warehouse.warehouse_id = warehouses.id
                ) as stock_qty')
                ->where('warehouses.is_active', true);
    
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('warehouses.name', 'LIKE', "%{$search}%")
                  ->orWhere('warehouses.phone', 'LIKE', "%{$search}%")
                  ->orWhere('warehouses.email', 'LIKE', "%{$search}%")
                  ->orWhere('warehouses.address', 'LIKE', "%{$search}%");
            });
    
            $totalFiltered = (clone $query)->count();
        }
    
        $warehouses = $query
            ->orderBy($order, $dir)
            ->offset($start)
            ->limit($limit)
            ->get();
    
        $data = [];
    
        foreach ($warehouses as $warehouse) {
    
            $action = view('backend.warehouse.partials.action-buttons', compact('warehouse'))->render();

            if ($warehouse->pos_type === 'restaurant') {
                $pos_type = '<span class="badge badge-success">'.__('db.Restaurant POS').'</span>';
            } elseif ($warehouse->pos_type === 'both') {
                $pos_type = '<span class="badge badge-info">'.__('db.Regular + Restaurant').'</span>';
            } else {
                $pos_type = '<span class="badge badge-secondary">'.__('db.Regular POS').'</span>';
            }
    
            $data[] = [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'phone' => $warehouse->phone,
                'email' => $warehouse->email,
                'address' => $warehouse->address,
                'number_of_product' => $warehouse->number_of_product,
                'stock_qty' => $warehouse->stock_qty,
                'pos_type' => $pos_type,
                'action' => $action,
            ];
        }
    
        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $data,
        ]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => [
                'max:255',
                    Rule::unique('warehouses')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'pos_type' => 'nullable|in:regular,restaurant,both',
        ]);
        $input = $request->all();
        $input['pos_type'] = in_array($request->input('pos_type'), ['regular', 'restaurant', 'both'], true) ? $request->input('pos_type') : 'regular';
        $input['is_active'] = true;

        $lims_warehouse_data = Warehouse::create($input);

        $lims_product_data = Product::pluck('id');
        foreach ($lims_product_data as $product) {
            Product_Warehouse::create([
                'product_id' => $product,
                'warehouse_id' => $lims_warehouse_data->id,
                'qty' => 0
            ]);
        }
        $this->cacheForget('warehouse_list');

        // if ajax - from create purchase page
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'id'      => $lims_warehouse_data->id,
                'name'    => $lims_warehouse_data->name, // Make sure 'name' exists on your model
            ]);
        }
        
        return redirect('warehouse')->with('message', __('db.Data inserted successfully'));
    }

    public function edit($id)
    {
        $lims_warehouse_data = Warehouse::findOrFail($id);
        return $lims_warehouse_data;
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => [
                'max:255',
                    Rule::unique('warehouses')->ignore($request->warehouse_id)->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'pos_type' => 'nullable|in:regular,restaurant,both',
        ]);
        $input = $request->all();
        if (isset($input['pos_type'])) {
            $input['pos_type'] = in_array($input['pos_type'], ['regular', 'restaurant', 'both'], true) ? $input['pos_type'] : 'regular';
        }
        $lims_warehouse_data = Warehouse::find($input['warehouse_id']);
        $lims_warehouse_data->update($input);
        $this->cacheForget('warehouse_list');
        return redirect('warehouse')->with('message', __('db.Data updated successfully'));
    }

    public function importWarehouse(Request $request)
    {
        //get file
        $upload=$request->file('file');
        $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
        if($ext != 'csv')
            return redirect()->back()->with('not_permitted', __('db.Please upload a CSV file'));
        $filename =  $upload->getClientOriginalName();
        $upload=$request->file('file');
        $filePath=$upload->getRealPath();
        //open and read
        $file=fopen($filePath, 'r');
        $header= fgetcsv($file);
        $escapedHeader=[];
        //validate
        foreach ($header as $key => $value) {
            $lheader=strtolower($value);
            $escapedItem=preg_replace('/[^a-z]/', '', $lheader);
            array_push($escapedHeader, $escapedItem);
        }
        //looping through othe columns
        while($columns=fgetcsv($file))
        {
            if($columns[0]=="")
                continue;
            foreach ($columns as $key => $value) {
                $value=preg_replace('/\D/','',$value);
            }
           $data= array_combine($escapedHeader, $columns);

           $warehouse = Warehouse::firstOrNew([ 'name'=>$data['name'], 'is_active'=>true ]);
           $warehouse->name = $data['name'];
           $warehouse->phone = $data['phone'];
           $warehouse->email = $data['email'];
           $warehouse->address = $data['address'];
           $warehouse->is_active = true;
           $warehouse->save();
        }
        $this->cacheForget('warehouse_list');
        return redirect('warehouse')->with('message', __('db.Warehouse imported successfully'));
    }

    public function deleteBySelection(Request $request)
    {
        $warehouse_id = $request['warehouseIdArray'];
        foreach ($warehouse_id as $id) {
            $this->assertNoActiveOperationalUsers($id);
            $lims_warehouse_data = Warehouse::find($id);
            $lims_warehouse_data->deactivate();
        }
        $this->cacheForget('warehouse_list');
        return __('db.Data deleted successfully');
    }

    public function destroy($id)
    {
        $this->assertNoActiveOperationalUsers($id);
        $lims_warehouse_data = Warehouse::find($id);
        $lims_warehouse_data->deactivate();
        $this->cacheForget('warehouse_list');
        return redirect('warehouse')->with('not_permitted', __('db.Data deleted successfully'));
    }

    private function assertNoActiveOperationalUsers(int $warehouseId): void
    {
        $assigned = \App\Models\User::where('warehouse_id', $warehouseId)
            ->where('is_active', true)->where('is_deleted', false)
            ->where('role_id', '>', 2)->where('role_id', '!=', 5)->count();

        if ($assigned) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'warehouse_id' => "Reassign {$assigned} active operational user(s) before deactivating this warehouse.",
            ]);
        }
    }

    public function warehouseAll()
    {
        if(Auth::user()->role_id > 2)
            $lims_warehouse_list = DB::table('warehouses')->where([
            ['is_active', true],
            ['id', Auth::user()->warehouse_id]
        ])->get();
        else
            $lims_warehouse_list = DB::table('warehouses')->where('is_active', true)->get();

        $html = '';
        foreach($lims_warehouse_list as $warehouse){
            $html .='<option value="'.$warehouse->id.'">'.$warehouse->name.'</option>';
        }

        return response()->json($html);
    }
}
