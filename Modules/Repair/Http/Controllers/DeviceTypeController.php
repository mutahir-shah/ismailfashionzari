<?php

namespace Modules\Repair\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Repair\Entities\DeviceType;
use Illuminate\Validation\Rule;
use App\Traits\CacheForget;

class DeviceTypeController extends Controller
{
    use CacheForget;

    public function index()
    {
        $lims_device_type_all = DeviceType::where('is_active', true)->get();
         return view('repair::device_type.index', compact('lims_device_type_all'));
    }

    public function store(Request $request)
    {
        $request->merge(['name' => preg_replace('/\s+/', ' ', $request->name)]);

        $this->validate($request, [
            'name' => [
                'required',
                'max:255',
                Rule::unique('device_types')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'category'    => 'required|in:device,vehicle',
            'description' => 'nullable|max:500',
        ]);

        $input = $request->only('name', 'category', 'description');
        $input['is_active'] = true;

        $deviceType = DeviceType::create($input);
        $this->cacheForget('device_type_list');

        if (isset($request->ajax))
            return $deviceType;
        else
            return redirect()->back()->with('message', __('db.device_type_created_successfully'));
    }

    public function edit($id)
    {
        $lims_device_type_data = DeviceType::findOrFail($id);
        return $lims_device_type_data;
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => [
                'required',
                'max:255',
                Rule::unique('device_types')->ignore($request->device_type_id)->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'category'    => 'required|in:device,vehicle',
            'description' => 'nullable|max:500',
        ]);

        $lims_device_type_data = DeviceType::findOrFail($request->device_type_id);
        $lims_device_type_data->name        = $request->name;
        $lims_device_type_data->category    = $request->category;
        $lims_device_type_data->description = $request->description;
        $lims_device_type_data->save();

        $this->cacheForget('device_type_list');
        return redirect()->back()->with('message', __('db.device_type_updated_successfully'));
    }

    public function importDeviceType(Request $request)
    {
        $upload = $request->file('file');
        $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);

        if ($ext != 'csv')
            return redirect()->back()->with('not_permitted', __('db.Please upload a CSV file'));

        $filePath = $upload->getRealPath();
        $file = fopen($filePath, 'r');
        $header = fgetcsv($file);

        $escapedHeader = [];
        foreach ($header as $value) {
            $lheader     = strtolower($value);
            $escapedItem = preg_replace('/[^a-z]/', '', $lheader);
            array_push($escapedHeader, $escapedItem);
        }

        while ($columns = fgetcsv($file)) {
            if ($columns[0] == "")
                continue;

            $data = array_combine($escapedHeader, $columns);

            $deviceType = DeviceType::firstOrNew([
                'name'      => $data['name'],
                'is_active' => true,
            ]);
            $deviceType->name        = $data['name'];
            $deviceType->category    = $data['category'] ?? 'device';
            $deviceType->description = $data['description'] ?? null;
            $deviceType->is_active   = true;
            $deviceType->save();
        }

        $this->cacheForget('device_type_list');
        return redirect('device-type')->with('message', __('db.device_type_imported_successfully'));
    }

    public function deleteBySelection(Request $request)
    {
        $device_type_id = $request['deviceTypeIdArray'];

        foreach ($device_type_id as $id) {
            $lims_device_type_data = DeviceType::findOrFail($id);
            $lims_device_type_data->is_active = false;
            $lims_device_type_data->save();
        }

        $this->cacheForget('device_type_list');
        return 'Device Type deleted successfully!';
    }

    public function destroy($id)
    {
        $lims_device_type_data = DeviceType::findOrFail($id);
        $lims_device_type_data->is_active = false;
        $lims_device_type_data->save();

        $this->cacheForget('device_type_list');
        return back()->with('not_permitted', __('db.device_type_deleted_successfully'));

    }

    public function exportDeviceType(Request $request)
    {
        $lims_device_type_data = $request['deviceTypeArray'];
        $csvData = array('Name, Category, Description');

        foreach ($lims_device_type_data as $deviceTypeId) {
            if ($deviceTypeId > 0) {
                $data      = DeviceType::where('id', $deviceTypeId)->first();
                $csvData[] = $data->name . ',' . $data->category . ',' . $data->description;
            }
        }

        $filename  = date('Y-m-d') . ".csv";
        $file_path = public_path() . '/downloads/' . $filename;
        $file_url  = url('/') . '/downloads/' . $filename;
        $file      = fopen($file_path, "w+");

        foreach ($csvData as $exp_data) {
            fputcsv($file, explode(',', $exp_data));
        }

        fclose($file);
        return $file_url;
    }
}
