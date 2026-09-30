<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Product_Sale;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductBatch;
use App\Models\Delivery;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use App\Mail\DeliveryDetails;
use App\Mail\DeliveryChallan;
use Illuminate\Support\Facades\Mail;
use App\Models\MailSetting;
use Illuminate\Support\Facades\Cache;
use App\Models\Courier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryController extends Controller
{
    use \App\Traits\MailInfo;

    public function index()
    {
        $role = Role::find(Auth::user()->role_id);

        if ($role->hasPermissionTo('delivery')) {
            $lims_courier_list = Courier::where('is_active', true)->get();
            return view('backend.delivery.index', compact('lims_courier_list'));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }


    public function deliveryListData(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);

        $limit  = (int) $request->input('length', 10);
        $start  = (int) $request->input('start', 0);

        if ($limit < 0) {
            $limit = 10;
        }

        $search = $request->input('search.value');

        $query = Delivery::with([
            'sale:id,reference_no,customer_id,grand_total',
            'sale.customer:id,name,phone_number,city',
            'courier:id,name'
        ])
            ->leftJoin('sales', 'deliveries.sale_id', '=', 'sales.id')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->select('deliveries.*')
            ->whereNull('sales.deleted_at');

        if ($role->hasPermissionTo('delivery')) {
            if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $query->where('deliveries.user_id', Auth::id());
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('deliveries.reference_no', 'LIKE', "%{$search}%")
                    ->orWhere('sales.reference_no', 'LIKE', "%{$search}%")
                    ->orWhere('customers.name', 'LIKE', "%{$search}%")
                    ->orWhere('customers.phone_number', 'LIKE', "%{$search}%")
                    ->orWhere('deliveries.packing_slip_ids', 'LIKE', "%{$search}%");
            });
        }

        $totalData = Delivery::count();
        $totalFiltered = $query->count();

        $deliveries = $query
            ->orderBy('deliveries.id', 'desc')
            ->skip($start)
            ->take($limit)
            ->get();

        $productNames = DB::table('product_sales')
            ->join('products', 'products.id', '=', 'product_sales.product_id')
            ->whereIn('product_sales.sale_id', $deliveries->pluck('sale_id'))
            ->select('product_sales.sale_id', 'products.name')
            ->get()
            ->groupBy('sale_id');


        $data = [];

        foreach ($deliveries as $key => $delivery) {

            $product_names = $productNames[$delivery->sale_id] ?? collect();
            $product_names = $product_names->pluck('name')->toArray();

            $packing_slip_references = $delivery->packing_slip_ids
                ? \App\Models\PackingSlip::whereIn('id', explode(",", $delivery->packing_slip_ids))->pluck('reference_no')->toArray()
                : ['N/A'];

            $customer = $delivery->sale->customer ?? null;

            $status = __('db.Packing');

            if ($delivery->tracking_code) {
                if ($delivery->status == 2)
                    $status = __('db.Delivering');
                elseif ($delivery->status == 3)
                    $status = __('db.Delivered');
                else
                    $status = __('db.Delivering');
            } else {
                $status = __('db.Packing');
            }



            $barcode = \DNS2D::getBarcodePNG($delivery->reference_no, 'QRCODE');

            $nestedData = [];

            $nestedData['key'] = $key;
            $nestedData['reference_no'] = $delivery->reference_no;
            $nestedData['sale_reference'] = $delivery->sale->reference_no ?? '';
            $nestedData['packing_slip_references'] = implode(",", $packing_slip_references);
            $nestedData['customer'] = $customer ? $customer->name . '<br>' . $customer->phone_number : '';
            $nestedData['courier'] = $delivery->courier->name ?? 'N/A';
            $nestedData['address'] = $delivery->address;
            $nestedData['products'] = implode(",", $product_names);
            $nestedData['grand_total'] = number_format($delivery->sale->grand_total ?? 0, 2);
            $nestedData['status'] = '<div class="badge badge-primary">' . $status . '</div>';
            $nestedData['id'] = $delivery->id;
            $nestedData['date'] = date(config('date_format'), strtotime($delivery->created_at));
            $nestedData['barcode'] = $barcode;
            $nestedData['tracking_code'] = $delivery->tracking_code
                ? '<span class="badge badge-info">' . $delivery->tracking_code . '</span>'
                : '<span class="text-muted">N/A</span>';

            $steadfast = '';

            if (isset($delivery->courier_id)) {
                $courierName = strtolower($delivery->courier->name ?? '');
                $courierType = strtolower($delivery->courier->type ?? '');

                $hasTracking = !empty($delivery->tracking_code);
                $isPacking   = ($delivery->status == 1); // adjust if needed

                // ====== STEADFAST ======
                if ($courierName == 'steadfast' || $courierType == 'steadfast') {

                    if ($hasTracking) {
                        $steadfast  = '<form action="' . route("steadfast.track", $delivery->sale->id) . '" method="GET">';
                        $steadfast .= '
            <li>
                <button type="submit" class="btn btn-link">
                    <i class="ti ti-search"></i> ' . __("Track Order") . '
                </button>
            </li>';
                        $steadfast .= '</form>';
                    } elseif ($isPacking) {
                        $steadfast = '
            <li>
                <button type="button"
                    class="steadfast-delivery btn btn-link"
                    data-id="' . $delivery->sale_id . '" data-type="steadfast">
                    <i class="ti ti-truck"></i> ' . __('Send To SteadFast') . '
                </button>
            </li>';
                    }
                }

                // ====== PATHAO ======
                elseif ($courierName == 'pathao' || $courierType == 'pathao') {

                    if ($hasTracking) {
                        $steadfast = '
            <li>
                <button type="button"
                    class="track-delivery-btn btn btn-link"
                    data-id="' . $delivery->id . '"
                    data-tracking="' . $delivery->tracking_code . '">
                    <i class="ti ti-search"></i> ' . __("Track Order") . '
                </button>
            </li>';
                    } elseif ($isPacking) {
                        $steadfast = '
            <li>
            <button type="button"
                    class="steadfast-delivery btn btn-link"
                    data-id="' . $delivery->sale_id . '" data-type="pathao">
                    <i class="ti ti-truck"></i> ' . __('Send To Pathao') . '
                </button>
            </li>';
                    }
                }
            }

            $nestedData['options'] = '<div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' . __("db.action") . '
                              <span class="caret"></span>
                              <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button" data-id="' . $delivery->id . '" class="open-EditCategoryDialog btn btn-link" data-toggle="modal" data-target="#editModal" ><i class="ti ti-edit"></i> ' . __("db.edit") . '</button>
                                </li>' . $steadfast . '
                                <li class="divider"></li>
                                <form action="' . route("delivery.delete", $delivery->id) . '" method="POST">' . csrf_field() . '' . method_field("POST") . '
                                <li>
                                  <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="ti ti-trash"></i> ' . __("db.delete") . '</button>
                                </li></form>
                            </ul>
                        </div>';

            $data[] = $nestedData;
        }

        return response()->json([
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        ]);
    }


    public function create($id)
    {
        $lims_delivery_data = Delivery::where('sale_id', $id)->first();
        if ($lims_delivery_data) {
            $customer_sale = DB::table('sales')->join('customers', 'sales.customer_id', '=', 'customers.id')->where('sales.id', $id)->whereNull('sales.deleted_at')->select('sales.reference_no', 'customers.name')->get();

            $delivery_data[] = $lims_delivery_data->reference_no;
            $delivery_data[] = $customer_sale[0]->reference_no;
            $delivery_data[] = $lims_delivery_data->status;
            $delivery_data[] = $lims_delivery_data->delivered_by;
            $delivery_data[] = $lims_delivery_data->recieved_by;
            $delivery_data[] = $customer_sale[0]->name;
            $delivery_data[] = $lims_delivery_data->address;
            $delivery_data[] = $lims_delivery_data->note;
            $delivery_data[] = $lims_delivery_data->courier_id;
        } else {
            if (in_array('ecommerce', explode(',', config('addons'))) || in_array('restaurant', explode(',', config('addons')))) {
                $customer_sale = DB::table('sales')->join('customers', 'sales.customer_id', '=', 'customers.id')->where('sales.id', $id)->whereNull('sales.deleted_at')->select('sales.reference_no', 'customers.name', 'sales.shipping_address', 'sales.shipping_city', 'sales.shipping_country')->get();

                $delivery_data[] = 'dr-' . date("Ymd") . '-' . date("his");
                $delivery_data[] = $customer_sale[0]->reference_no;
                $delivery_data[] = '';
                $delivery_data[] = '';
                $delivery_data[] = '';
                $delivery_data[] = $customer_sale[0]->name;
                $delivery_data[] = $customer_sale[0]->shipping_address . ' ' . $customer_sale[0]->shipping_city . ' ' . $customer_sale[0]->shipping_country;
                $delivery_data[] = '';
            } else {

                $customer_sale = DB::table('sales')->join('customers', 'sales.customer_id', '=', 'customers.id')->where('sales.id', $id)->whereNull('sales.deleted_at')->select('sales.reference_no', 'customers.name', 'customers.address', 'customers.city', 'customers.country')->get();

                $delivery_data[] = 'dr-' . date("Ymd") . '-' . date("his");
                $delivery_data[] = $customer_sale[0]->reference_no;
                $delivery_data[] = '';
                $delivery_data[] = '';
                $delivery_data[] = '';
                $delivery_data[] = $customer_sale[0]->name;
                $delivery_data[] = $customer_sale[0]->address . ' ' . $customer_sale[0]->city . ' ' . $customer_sale[0]->country;
                $delivery_data[] = '';
            }
        }
        return $delivery_data;
    }

    public function store(Request $request)
    {
        try {
        DB::beginTransaction();

        $data = $request->except('file');
        $delivery = Delivery::firstOrNew(['reference_no' => $data['reference_no']]);

        // ================== FILE UPLOAD ==================
        $document = $request->file;
        if ($document) {
            $ext          = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
            $documentName = $data['reference_no'] . '.' . $ext;
            $document->move(public_path('documents/delivery'), $documentName);
            $delivery->file = $documentName;
        }

        // ================== DELIVERY SAVE ==================
        $delivery->sale_id      = $data['sale_id'];
        $delivery->user_id      = Auth::id();
        $delivery->courier_id   = $data['courier_id'];
        $delivery->address      = $data['address'];
        $delivery->delivered_by = $data['delivered_by'];
        $delivery->recieved_by  = $data['recieved_by'];
        $delivery->status       = $data['status'];
        $delivery->note         = $data['note'];
        $delivery->save();
        // ================== SALE & CUSTOMER ==================
        $lims_sale_data     = Sale::find($data['sale_id']);
        $lims_customer_data = Customer::find($lims_sale_data->customer_id);
        $message = 'Delivery created successfully';
        $courier = Courier::find($data['courier_id']);
        // if ($courier && $request->send_to_courier) {
        if ($courier) {
            $courierService = \App\Services\Courier\CourierManager::resolve($courier);
            if ($courierService) {
                try {
                    $result = $courierService->createOrder(
                        $delivery,
                        $lims_sale_data,
                        $lims_customer_data,
                        $data
                    );

                    if ($result['success'] && !empty($result['tracking_code'])) {
                        $newStatus = \App\Services\Courier\CourierStatusMapper::toDeliveryStatus(
                            $courier->type,
                            $result['status'] ?? 'in_review'
                        );

                        $delivery->tracking_code = $result['tracking_code'];
                        $delivery->status        = $newStatus;
                        $delivery->save();
                    } else {
                        Log::error('Courier Order Failed', [
                            'courier' => $courier->type,
                            'result'  => $result,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Courier Exception', [
                        'courier' => $courier->type,
                        'message' => $e->getMessage(),
                        'file'    => $e->getFile(),
                        'line'    => $e->getLine(),
                    ]);
                }
            }
        }

        DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Delivery creation failed: ' . $e->getMessage());
            return redirect()->back()->with('not_permitted', 'Delivery creation failed: ' . $e->getMessage());
        }

        // ================== MAIL SEND ==================
        $mail_setting = MailSetting::latest()->first();
        if ($lims_customer_data->email && $data['status'] != 1 && $mail_setting) {
            $mail_data = [
                'email'              => $lims_customer_data->email,
                'customer'           => $lims_customer_data->name,
                'sale_reference'     => $lims_sale_data->reference_no,
                'delivery_reference' => $delivery->reference_no,
                'status'             => $data['status'],
                'address'            => $data['address'],
                'delivered_by'       => $data['delivered_by'],
            ];
            $this->setMailInfo($mail_setting);
            try {
                Mail::to($mail_data['email'])->send(new DeliveryDetails($mail_data));
            } catch (\Exception $e) {
                $message = 'Delivery created successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
            }
        }
        return redirect('delivery')->with('message', $message);
    }

    public function productDeliveryData($id)
    {
        $lims_delivery_data = Delivery::find($id);

        $lims_product_sale_data = Product_Sale::where('sale_id', $lims_delivery_data->sale->id)->get();

        foreach ($lims_product_sale_data as $key => $product_sale_data) {
            $product = Product::select('name', 'code')->find($product_sale_data->product_id);
            if ($product_sale_data->variant_id) {
                $lims_product_variant_data = ProductVariant::select('item_code')->FindExactProduct($product_sale_data->product_id, $product_sale_data->variant_id)->first();
                $product->code = $lims_product_variant_data->item_code;
            }
            if ($product_sale_data->product_batch_id) {
                $product_batch_data = ProductBatch::select('batch_no', 'expired_date')->find($product_sale_data->product_batch_id);
                if ($product_batch_data) {
                    $batch_no = $product_batch_data->batch_no;
                    $expired_date = date(config('date_format'), strtotime($product_batch_data->expired_date));
                }
            } else {
                $batch_no = 'N/A';
                $expired_date = 'N/A';
            }
            $product_sale[0][$key] = $product->code;
            $product_sale[1][$key] = $product->name;
            $product_sale[2][$key] = $batch_no;
            $product_sale[3][$key] = $expired_date;
            $product_sale[4][$key] = $product_sale_data->qty;
        }
        return $product_sale;
    }

    public function sendMail(Request $request)
    {
        $data = $request->all();
        $lims_delivery_data = Delivery::find($data['delivery_id']);
        $lims_sale_data = Sale::find($lims_delivery_data->sale->id);
        $lims_product_sale_data = Product_Sale::where('sale_id', $lims_delivery_data->sale->id)->get();
        $lims_customer_data = Customer::find($lims_sale_data->customer_id);
        $mail_setting = MailSetting::latest()->first();
        if ($lims_customer_data->email && $mail_setting) {
            //collecting male data
            $mail_data['email'] = $lims_customer_data->email;
            $mail_data['date'] = date(config('date_format'), strtotime($lims_delivery_data->created_at->toDateString()));
            $mail_data['delivery_reference_no'] = $lims_delivery_data->reference_no;
            $mail_data['sale_reference_no'] = $lims_sale_data->reference_no;
            $mail_data['status'] = $lims_delivery_data->status;
            $mail_data['customer_name'] = $lims_customer_data->name;
            $mail_data['address'] = $lims_customer_data->address . ', ' . $lims_customer_data->city;
            $mail_data['phone_number'] = $lims_customer_data->phone_number;
            $mail_data['note'] = $lims_delivery_data->note;
            $mail_data['prepared_by'] = $lims_delivery_data->user->name;
            if ($lims_delivery_data->delivered_by)
                $mail_data['delivered_by'] = $lims_delivery_data->delivered_by;
            else
                $mail_data['delivered_by'] = 'N/A';
            if ($lims_delivery_data->recieved_by)
                $mail_data['recieved_by'] = $lims_delivery_data->recieved_by;
            else
                $mail_data['recieved_by'] = 'N/A';
            //return $mail_data;

            foreach ($lims_product_sale_data as $key => $product_sale_data) {
                $lims_product_data = Product::select('code', 'name')->find($product_sale_data->product_id);
                $mail_data['codes'][$key] = $lims_product_data->code;
                $mail_data['name'][$key] = $lims_product_data->name;
                if ($product_sale_data->variant_id) {
                    $lims_product_variant_data = ProductVariant::select('item_code')->FindExactProduct($product_sale_data->product_id, $product_sale_data->variant_id)->first();
                    $mail_data['codes'][$key] = $lims_product_variant_data->item_code;
                }
                $mail_data['qty'][$key] = $product_sale_data->qty;
            }
            $this->setMailInfo($mail_setting);
            try {
                Mail::to($mail_data['email'])->send(new DeliveryChallan($mail_data));
                $message = 'Mail sent successfully';
            } catch (\Exception $e) {
                $message = 'Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
            }
        } else
            $message = 'Customer does not have email!';

        return redirect()->back()->with('message', $message);
    }

    public function edit($id)
    {
        $lims_delivery_data = Delivery::find($id);
        $customer_sale = DB::table('sales')->join('customers', 'sales.customer_id', '=', 'customers.id')->where('sales.id', $lims_delivery_data->sale_id)->whereNull('sales.deleted_at')->select('sales.reference_no', 'customers.name')->get();

        $delivery_data[] = $lims_delivery_data->reference_no;
        $delivery_data[] = $customer_sale[0]->reference_no;
        $delivery_data[] = $lims_delivery_data->status;
        $delivery_data[] = $lims_delivery_data->delivered_by;
        $delivery_data[] = $lims_delivery_data->recieved_by;
        $delivery_data[] = $customer_sale[0]->name;
        $delivery_data[] = $lims_delivery_data->address;
        $delivery_data[] = $lims_delivery_data->note;
        $delivery_data[] = $lims_delivery_data->courier_id;
        return $delivery_data;
    }

    public function update(Request $request)
    {
        try {
        DB::beginTransaction();

        $input = $request->except('file');
        //return $input;
        $lims_delivery_data = Delivery::find($input['delivery_id']);
        $document = $request->file;
        if ($document) {
            $this->fileDelete(public_path('documents/delivery/'), $lims_delivery_data->file);
            $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
            $documentName = $input['reference_no'] . '.' . $ext;
            $document->move(public_path('documents/delivery'), $documentName);
            $input['file'] = $documentName;
        }
        $lims_delivery_data->update($input);
        $lims_sale_data = Sale::find($lims_delivery_data->sale_id);
        $lims_customer_data = Customer::find($lims_sale_data->customer_id);

        DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Delivery update failed: ' . $e->getMessage());
            return redirect()->back()->with('not_permitted', 'Delivery update failed: ' . $e->getMessage());
        }

        $message = 'Delivery updated successfully';
        $mail_setting = MailSetting::latest()->first();
        if ($lims_customer_data->email && $input['status'] != 1 && $mail_setting) {
            $mail_data['email'] = $lims_customer_data->email;
            $mail_data['customer'] = $lims_customer_data->name;
            $mail_data['sale_reference'] = $lims_sale_data->reference_no;
            $mail_data['delivery_reference'] = $lims_delivery_data->reference_no;
            $mail_data['status'] = $input['status'];
            $mail_data['address'] = $input['address'];
            $mail_data['delivered_by'] = $input['delivered_by'];
            $this->setMailInfo($mail_setting);
            try {
                Mail::to($mail_data['email'])->send(new DeliveryDetails($mail_data));
            } catch (\Exception $e) {
                $message = 'Delivery updated successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
            }
        }
        return redirect('delivery')->with('message', $message);
    }

    public function deleteBySelection(Request $request)
    {
        try {
            DB::beginTransaction();
            $delivery_id = $request['deliveryIdArray'];
            foreach ($delivery_id as $id) {
                $lims_delivery_data = Delivery::find($id);
                $this->fileDelete(public_path('documents/delivery/'), $lims_delivery_data->file);
                $lims_delivery_data->delete();
            }
            DB::commit();
            return 'Delivery deleted successfully';
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Delivery bulk deletion failed: ' . $e->getMessage());
            return 'Delivery deletion failed: ' . $e->getMessage();
        }
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $lims_delivery_data = Delivery::find($id);
            $this->fileDelete(public_path('documents/delivery/'), $lims_delivery_data->file);
            $lims_delivery_data->delete();

            DB::commit();
            return redirect('delivery')->with('not_permitted', __('db.Delivery deleted successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Delivery deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('not_permitted', 'Delivery deletion failed: ' . $e->getMessage());
        }
    }

    public function track($id)
    {
        $delivery = Delivery::with('courier')->findOrFail($id);

        if (!$delivery->tracking_code) {
            return response()->json([
                'success' => false,
                'error'   => 'No tracking code found for this delivery.',
            ]);
        }

        if (!$delivery->courier) {
            return response()->json([
                'success' => false,
                'error'   => 'Courier information not found.',
            ]);
        }

        $courierService = \App\Services\Courier\CourierManager::resolve($delivery->courier);

        if (!$courierService) {
            return response()->json([
                'success' => false,
                'error'   => ucfirst($delivery->courier->type) . ' courier tracking is not supported.',
            ]);
        }

        try {
            $result = $courierService->trackOrder($delivery->tracking_code);

            if ($result['success']) {
                // ✅ Courier status থেকে delivery status auto-update
                $newStatus = \App\Services\Courier\CourierStatusMapper::toDeliveryStatus(
                    $delivery->courier->type,
                    $result['status']
                );

                if ($delivery->status != $newStatus) {
                    $delivery->status = $newStatus;
                    $delivery->save();

                    \Log::info('Delivery Status Updated from Courier', [
                        'delivery_id'    => $delivery->id,
                        'tracking_code'  => $delivery->tracking_code,
                        'courier_status' => $result['status'],
                        'new_status'     => $newStatus,
                    ]);
                }

                $result['delivery_status'] = $newStatus;
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Tracking failed: ' . $e->getMessage(),
            ]);
        }
    }

    public function sendToPathao($id)
    {
        $delivery = Delivery::with(['sale', 'courier'])->findOrFail($id);

        // ✅ Already has tracking
        if ($delivery->tracking_code) {

            Log::warning('Pathao Send Failed: Already has tracking', [
                'delivery_id'   => $delivery->id,
                'tracking_code' => $delivery->tracking_code,
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'This delivery already has a tracking code: ' . $delivery->tracking_code,
            ]);
        }

        // ✅ Courier not found
        if (!$delivery->courier) {

            Log::error('Pathao Send Failed: Courier not found', [
                'delivery_id' => $delivery->id,
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Courier information not found.',
            ]);
        }

        // ✅ Courier service not configured
        $courierService = \App\Services\Courier\CourierManager::resolve($delivery->courier);
        if (!$courierService) {

            Log::error('Pathao Send Failed: Courier service not configured', [
                'delivery_id' => $delivery->id,
                'courier'     => $delivery->courier->name ?? null,
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Pathao courier service is not configured.',
            ]);
        }

        $sale     = Sale::find($delivery->sale_id);
        $customer = Customer::find($sale->customer_id ?? null);

        // ✅ Sale বা Customer missing
        if (!$sale || !$customer) {

            Log::error('Pathao Send Failed: Sale or Customer missing', [
                'delivery_id' => $delivery->id,
                'sale_id'     => $delivery->sale_id,
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Sale or customer information not found.',
            ]);
        }

        try {
            $result = $courierService->createOrder(
                $delivery,
                $sale,
                $customer,
                [
                    'address' => $delivery->address,
                    'note'    => $delivery->note ?? '',
                ]
            );

            // ✅ API failed response
            if (!$result['success']) {

                Log::error('Pathao API Failed', [
                    'delivery_id' => $delivery->id,
                    'response'    => $result,
                ]);

                return response()->json([
                    'success' => false,
                    'error'   => $result['error'] ?? 'Failed to create Pathao order.',
                ]);
            }

            // ✅ Success
            $newStatus = \App\Services\Courier\CourierStatusMapper::toDeliveryStatus(
                $delivery->courier->type,
                $result['status'] ?? 'in_review'
            );

            $delivery->tracking_code = $result['tracking_code'];
            $delivery->status        = $newStatus;
            $delivery->save();

            Log::info('Pathao Order Created', [
                'delivery_id'   => $delivery->id,
                'tracking_code' => $result['tracking_code'],
                'status'        => $newStatus,
            ]);

            return response()->json([
                'success'       => true,
                'tracking_code' => $result['tracking_code'],
                'message'       => 'Order sent to Pathao successfully!',
            ]);
        } catch (\Exception $e) {

            Log::critical('Pathao Exception Occurred', [
                'delivery_id' => $id,
                'message'     => $e->getMessage(),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Exception: ' . $e->getMessage(),
            ]);
        }
    }
}
