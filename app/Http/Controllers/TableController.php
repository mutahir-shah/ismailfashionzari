<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Table;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Schema;
use DB;

class TableController extends Controller
{
    use \App\Traits\CacheForget;

    private function restaurantModuleAvailable(): bool
    {
        return is_dir(base_path('Modules/Restaurant')) && Schema::hasTable('floors');
    }

    public function index()
    {
        $lims_table_all = Table::where('is_active', true)->get();

        if($this->restaurantModuleAvailable()){
            $floors = DB::table('floors')->get();

            return view('backend.table.index', compact('lims_table_all','floors'));
        }

        return view('backend.table.index', compact('lims_table_all'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['is_active'] = true;
        $new = Table::create($data);

        if($this->restaurantModuleAvailable()){
            $floor = DB::table('floors')->where('id',$request->floor_id)->first();
            $newTable = [
                'id' => $new->id, // Unique ID for the new table
                'x' => 0,    // Default x coordinate
                'y' => 0,    // Default y coordinate
                'width' => 100, // Default width
                'height' => 100, // Default height
                'name' => $new->name .'('.$new->number_of_person.')' // Name of the new table
            ];

            $floorplan = json_decode($floor->floorplan, true);

            // Add the new table to the floorplan
            $floorplan[] = $newTable;

            // Save the updated floorplan back to the database
            DB::table('floors')
                ->where('id', $floor->id)
                ->update(['floorplan' => json_encode($floorplan)]);
        }

        $this->cacheForget('table_list');
        return redirect()->back()->with('message', __('db.Table created successfully'));
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        $table = Table::find($request->table_id);
        $floor_prev_id = $table->floor_id;
        $table->update($request->all());

        if($this->restaurantModuleAvailable()){

            if($floor_prev_id != $request->floor_id){
                $floor_prev = DB::table('floors')->where('id',$floor_prev_id)->first();
                $floorplan_prev = json_decode($floor_prev->floorplan, true);

                $table_id = $request->table_id;

                // Remove the table from the floorplan
                $updatedFloorplan = array_filter($floorplan_prev, function ($item) use ($table_id) {
                    return $item['id'] != $table_id;
                });

                // Save the updated floorplan back to the database
                DB::table('floors')
                    ->where('id', $floor_prev_id)
                    ->update(['floorplan' => json_encode(array_values($updatedFloorplan))]);

                $newTable = [
                    'id' => $request->table_id, // Unique ID for the new table
                    'x' => 0,    // Default x coordinate
                    'y' => 0,    // Default y coordinate
                    'width' => 100, // Default width
                    'height' => 100, // Default height
                    'name' => $request->name.'('.$request->number_of_person.')' // Name of the new table
                ];

                $floor = DB::table('floors')->where('id',$request->floor_id)->first();
                $floorplan = json_decode($floor->floorplan, true);

                $floorplan[] = $newTable;
                
                // Save the updated floorplan back to the database
                DB::table('floors')
                    ->where('id', $floor->id)
                    ->update(['floorplan' => json_encode($floorplan)]);

            }else{
                $floor = DB::table('floors')->where('id',$request->floor_id)->first();
                $floorplan = json_decode($floor->floorplan, true);

                if(isset($floorplan)){
                    foreach ($floorplan as &$item) {
                        if ($item['id'] == $request->table_id) {
                            $item['name'] = $request->name.'('.$request->number_of_person.')'; // Update the name only
                            break;
                        }
                    }
                }else{
                    $newTable = [
                        'id' => $request->table_id, // Unique ID for the new table
                        'x' => 0,    // Default x coordinate
                        'y' => 0,    // Default y coordinate
                        'width' => 100, // Default width
                        'height' => 100, // Default height
                        'name' => $request->name.'('.$request->number_of_person.')' // Name of the new table
                    ];

                    $floorplan[] = $newTable;
                }
                
                // Save the updated floorplan back to the database
                DB::table('floors')
                    ->where('id', $floor->id)
                    ->update(['floorplan' => json_encode($floorplan)]);

            }
        }

        $this->cacheForget('table_list');
        return redirect()->back()->with('message', __('db.Table updated successfully'));
    }

    public function destroy($id)
    {
        $table = Table::find($id);
        $table->update(['is_active'=>false]);

        if($this->restaurantModuleAvailable()){
            $floor = DB::table('floors')->where('id',$table->floor_id)->first();
            $floorplan = json_decode($floor->floorplan, true);

            $table_id = $table->id;

            // Remove the table from the floorplan
            $updatedFloorplan = array_filter($floorplan, function ($item) use ($table_id) {
                return $item['id'] != $table_id;
            });



            // Save the updated floorplan back to the database
            DB::table('floors')
                ->where('id', $table->floor_id)
                ->update(['floorplan' => json_encode(array_values($updatedFloorplan))]);
        }

        $this->cacheForget('table_list');
        return redirect()->back()->with('message', __('db.Table deleted successfully'));
    }

    public function floorplanStatus(Request $request)
    {
        $warehouse_id = $request->input('warehouse_id') ? (int)$request->input('warehouse_id') : null;
        
        $floors = [];
        $tableStatus = [];

        if ($this->restaurantModuleAvailable()) {
            $floors = DB::table('floors')
                ->when($warehouse_id, function($query, $warehouse_id) {
                    return $query->where('warehouse_id', $warehouse_id);
                })
                ->get()
                ->map(function($floor) {
                    $floor->floorplan = json_decode($floor->floorplan, true);
                    return $floor;
                });

            if (class_exists(\Modules\Restaurant\Services\TableOccupancyService::class)) {
                $tableStatus = \Modules\Restaurant\Services\TableOccupancyService::getTableStatusMap($warehouse_id);
            }
        }
        
        if (empty($tableStatus)) {
            $tables = \App\Models\Table::where('is_active', true)->get();
            foreach ($tables as $table) {
                $tableStatus[$table->id] = [
                    'id'               => $table->id,
                    'name'             => $table->name,
                    'status'           => 'available',
                    'readiness'        => null,
                    'open_order_count' => 0,
                    'sale_id'          => null,
                    'item_count'       => null,
                    'total'            => null,
                    'waiter_id'        => null,
                    'waiter_name'      => null,
                    'opened_at'        => null,
                ];
            }
        }
        
        return response()->json([
            'floors' => $floors,
            'tables' => $tableStatus
        ]);
    }
}
