<?php

namespace App\Http\Controllers;

use Auth;
use App\Models\InvoiceSchema;
use App\Models\InvoiceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Spatie\Permission\Models\Role;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class InvoiceSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if (!$role->hasPermissionTo('invoice_setting'))
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));

        $data['invoiceSettings'] = InvoiceSetting::all();
        return view('backend.setting.invoice_setting.index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if (!$role->hasPermissionTo('invoice_create_edit_delete'))
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));

        return view('backend.setting.invoice_setting.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if(!config('app.user_verified')) {
            return redirect()->back()->with('not_permitted', __('db.This feature is disable for demo!'));
        }

        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if (!$role->hasPermissionTo('invoice_create_edit_delete'))
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));


        $request->validate(['template_name' => 'required|string|max:255']);
        try{
            DB::beginTransaction();
            $data = $this->getRequestData($request);

            $data['status'] = isset($request->status) ? 1 : 0;
            $data['is_default'] = isset($request->is_default) ? 1 : 0;

            InvoiceSetting::query()->create($data);

            DB::commit();
            return redirect()->route('settings.invoice.index')->with('customMessage', 'Invoice setting stored successfully.');
        }catch(\Throwable $e){
            DB::rollBack();
            return redirect()->route('settings.invoice.index')->with('customMessage','Failed to store invoice setting');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if (!$role->hasPermissionTo('invoice_create_edit_delete'))
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));

        $invoice = InvoiceSetting::findOrFail($id);
        return view('backend.setting.invoice_setting.edit',compact('invoice'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if($request->ajax()){
            $data = $this->changeStatus($request,$id);
            return true;
        }

        if(!config('app.user_verified')) {
            return redirect()->back()->with('not_permitted', __('db.This feature is disable for demo!'));
        }

        $request->validate([
            'template_name' => 'required|string|max:255',
            'prefix' => 'string|max:10',
        ]);

        try{
            DB::beginTransaction();

            $data = $this->getRequestData($request);

            InvoiceSetting::query()->findOrFail($id)->update($data);
            DB::commit();
            return redirect()->back()->with('customMessage', 'Invoice setting stored successfully');
        }catch(\Throwable $e){
            DB::rollBack();
            return redirect()->back()->with('not_permitted', 'Failed to store invoice setting');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if(!config('app.user_verified')) {
            return response()->json(['not_permitted' => __('db.This feature is disable for demo!')]);
        }

        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if (!$role->hasPermissionTo('invoice_create_edit_delete'))
            return response()->json(['not_permitted' => __('db.Sorry! You are not allowed to access this module')]);

        $invoice = InvoiceSetting::findOrFail($id);

        if ($invoice->is_default != 1) {
            $invoice->delete();
            return response()->json(['message' => 'Invoice deleted successfully', 'success' => true]);
        } else {
            return response()->json(['message' => 'Default invoice cannot be deleted', 'success' => false]);
        }
    }


    public function getRequestData($request){
            $data = $request->all();
            if($request->hasFile('company_logo')){
                $data['company_logo'] = $this->uploadInvoiceTemplate($request->company_logo);
            }
            if($request->hasFile('preview_invoice')){
                $data['preview_invoice'] = $this->uploadInvoiceTemplate($request->preview_invoice);
            }


            if($request->status == 1){
                InvoiceSetting::query()->where('status', 1)->update(['status' => 0]);
            }

            if($request->is_default == 1){
                InvoiceSetting::query()->where('is_default', 1)->update(['is_default' => 0]);
            }

            //checkbox data
            // Default 0 for all checkboxes unless checked
            $checkboxFields = [
                'show_barcode',
                'show_qr_code',
                'show_description',
                'show_in_words',
                'active_primary_color',
                'show_warehouse_info',
                'show_bill_to_info',
                'show_footer_text',
                'show_biller_info',
                'show_paid_info',
                'show_payment_note',
                'show_ref_number',
                'active_date_format',
                'active_generat_settings',
                'active_logo_height_width',
                'hide_total_due',
                'show_vat_registration_number',
                'show_sale_note',
                'show_customer_name',
            ];

            $showColumn = [];

            foreach ($checkboxFields as $field) {
                $showColumn[$field] = isset($request->show_column[$field]) ? 1 : 0;
            }
            // Store JSON data
            $data['show_column'] = json_encode($showColumn);
            return $data;
    }


    private function uploadInvoiceTemplate($request_image)
    {
        if(isset($request_image)) {
            $logo = $request_image;
            if ($logo) {
                $ext = pathinfo($logo->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName = date("Ymdhis") . '.' . $ext;

                // Save original file first
                $logo->move(public_path('invoices/'), $imageName);

                $manager = new ImageManager(new GdDriver());
                $image = $manager->read(public_path('invoices/') . $imageName);
                // Get original dimensions
                $originalWidth = $image->width();
                $originalHeight = $image->height();

                // Only resize if wider than 300px
                if ($originalWidth > 300) {
                    $newWidth = 300;
                    $newHeight = intval(($originalHeight / $originalWidth) * $newWidth);

                    // Resize explicitly
                    $image->resize($newWidth, $newHeight);
                }

                // Save resized image
                if (!file_exists(public_path("invoices/small")) && !is_dir(public_path("invoices/small"))) {
                    mkdir(public_path("invoices/small"), 0755, true);
                }
                $image->save(public_path('invoices/small/' . $imageName), quality: 100);

                return $imageName;
            }
        }

        return null;
    }


    public function changeStatus($request,$id){
        if($request->column == 'status'){
            InvoiceSetting::query()->where('status', 1)->update(['status' => 0]);
            InvoiceSetting::query()->findOrFail($id)->update(['status' => 1]);
            return true;
        }

        if($request->column == 'is_default'){
            InvoiceSetting::query()->where('is_default', 1)->update(['is_default' => 0]);
            InvoiceSetting::query()->findOrFail($id)->update(['is_default' => 1]);
            return true;
        }
    }
}
