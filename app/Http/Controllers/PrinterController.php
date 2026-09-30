<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Illuminate\Validation\Rule;
use App\Models\Warehouse;
use App\Models\Printer;
use App\Services\PrinterService;

class PrinterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $lims_warehouse_all = Warehouse::where('is_active', true)->get();
        $lims_printer_all = Printer::all();
        $connection_types = Printer::connection_types();
        $capability_profiles = Printer::capability_profiles();
        return view('backend.printer.create', compact('lims_warehouse_all', 'lims_printer_all', 'connection_types', 'capability_profiles'));
    }


    public function store(Request $request)
    {
        $this->validate(
            $request,
            [
                'warehouse_id' => [
                    'required',
                    Rule::unique('printers', 'warehouse_id'),
                ],
                'name' => 'required|max:255',
            ],
            [
                'warehouse_id.unique' => __('db.This warehouse already has a printer assigned'),
            ]
        );


        $input = $request->only(['name', 'warehouse_id', 'connection_type', 'capability_profile', 'char_per_line', 'ip_address', 'port', 'path']);

        $input['created_by'] = Auth::user()->id;

        if ($input['connection_type'] == 'network') {
            $input['path'] = '';
        } elseif (in_array($input['connection_type'], ['windows', 'linux'])) {
            $input['ip_address'] = '';
            $input['port'] = '';
        }

        // Check connector before saving
        try {
            $receipt_printer = new Printer($input);
            app(PrinterService::class)->getConnector($receipt_printer);
            $receipt_printer->save();
            return redirect('printers')->with('message', __('db.Data inserted successfully'));
        } catch (\Throwable $e) {
            return redirect('printers')->with('not_permitted', $e->getMessage());
        }
    }

    public function edit($id)
    {
        $lims_printer_data = Printer::findOrFail($id);
        return $lims_printer_data;
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $this->validate(
            $request,
            [
                'warehouse_id' => [
                    'required',
                    Rule::unique('printers', 'warehouse_id')->ignore($request->printer_id),
                ],
                'name' => 'required|max:255',
            ],
            [
                'warehouse_id.unique' => __('db.This warehouse already has a printer assigned'),
            ]
        );

        $input = $request->only(['name', 'warehouse_id', 'connection_type', 'capability_profile', 'char_per_line', 'ip_address', 'port', 'path']);

        $printer = Printer::findOrFail($request->printer_id);

        if ($input['connection_type'] == 'network') {
            $input['path'] = '';
        } elseif (in_array($input['connection_type'], ['windows', 'linux'])) {
            $input['ip_address'] = '';
            $input['port'] = '';
        }

        // Check connector before saving
        try {
            $printer->fill($input);
            app(PrinterService::class)->getConnector($printer);
            $printer->save();
            return redirect('printers')->with('message', __('db.Data updated successfully'));
        } catch (\Throwable $e) {
            return redirect('printers')->with('not_permitted', $e->getMessage());
        }
    }

    public function destroy(Request $request, $id = null)
    {
        if ($request->has('printerIdArray')) {
            $ids = $request->input('printerIdArray');
            Printer::whereIn('id', $ids)->delete();
            return __('db.Data deleted successfully');
        } elseif ($id !== null) {
            $lims_printer_data = Printer::findOrFail($id);
            $lims_printer_data->delete();
            return redirect('printers')->with('not_permitted', __('db.Data deleted successfully'));
        }
    }
}
