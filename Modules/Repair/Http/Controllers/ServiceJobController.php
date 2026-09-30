<?php

namespace Modules\Repair\Http\Controllers;

use App\Models\Account;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\GeneralSetting;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Product_Warehouse;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use DNS1D;
use Modules\Repair\Entities\DeviceType;
use Modules\Repair\Entities\ServiceDevice;
use Modules\Repair\Entities\ServiceJob;
use Modules\Repair\Entities\ServiceJobItem;
use Modules\Repair\Entities\ServiceJobUpdate;
use Modules\Repair\Entities\ServiceVehicle;
use App\Services\Domain\RepairDomainService;

class ServiceJobController extends Controller
{
    public function index(Request $request)
    {
        $lims_warehouse_list = Warehouse::where("is_active", true)->get();
        $warehouse_id = $request->input("warehouse_id", 0);
        $status = $request->input("status", "");
        $service_type = $request->input("service_type", "");
        $starting_date = $request->input(
            "starting_date",
            date("Y-m-d", strtotime("-1 year"))
        );
        $ending_date = $request->input("ending_date", date("Y-m-d"));
        return view(
            "repair::service.index",
            compact(
                "lims_warehouse_list",
                "warehouse_id",
                "status",
                "service_type",
                "starting_date",
                "ending_date"
            )
        );
    }

    public function serviceData(Request $request)
    {
        $columns = [
            1 => "created_at",
            2 => "reference_no",
            3 => "customer_id",
            4 => "service_type",
            5 => "status",
            6 => "total_amount",
        ];
        $q = ServiceJob::with("customer", "warehouse", "assignedTo", "device.deviceTypeInfo", "vehicle.vehicleTypeInfo")
            ->whereDate("created_at", ">=", $request->starting_date)
            ->whereDate("created_at", "<=", $request->ending_date);
        if ($request->warehouse_id) {
            $q->where("warehouse_id", $request->warehouse_id);
        }
        if ($request->status) {
            $q->where("status", $request->status);
        }
        if ($request->service_type) {
            $q->where("service_type", $request->service_type);
        }
        if (Auth::user()->role_id > 2) {
            if (config("staff_access") == "own") {
                $q->where("created_by", Auth::id());
            } elseif (config("staff_access") == "warehouse") {
                $q->where("warehouse_id", Auth::user()->warehouse_id);
            }
        }
        $totalData = $q->count();
        $totalFiltered = $totalData;
        $limit =
            $request->input("length") != -1
                ? $request->input("length")
                : $totalData;
        $start = $request->input("start");
        $order =
            "service_jobs." .
            ($columns[$request->input("order.0.column")] ?? "created_at");
        $dir = $request->input("order.0.dir", "desc");
        if ($request->input("search.value")) {
            $search = $request->input("search.value");
            $q->where(function ($query) use ($search) {
                $query
                    ->where("reference_no", "LIKE", "%{$search}%")
                    ->orWhere("title", "LIKE", "%{$search}%")
                    ->orWhereHas(
                        "customer",
                        fn($c) => $c->where("name", "LIKE", "%{$search}%")
                    );
            });
            $totalFiltered = $q->count();
        }
        $jobs = $q
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();
        $data = [];
        foreach ($jobs as $key => $job) {
            $customerName = optional($job->customer)->name ?? "N/A";
            $warehouseName = optional($job->warehouse)->name ?? "N/A";
            $techName = optional($job->assignedTo)->name ?? "Unassigned";
            $nestedData["key"] = $key;
            $nestedData["date"] = date(
                config("date_format"),
                strtotime($job->created_at)
            );
            $nestedData["reference_no"] = $job->reference_no;
            $nestedData["customer"] = $customerName;
            $nestedData["service_type"] =
                $job->service_type === "device"
                    ? '<span class="badge badge-info"><i class="ti ti-mobile"></i> ' .
                        __("db.device") .
                        "</span>"
                    : '<span class="badge badge-warning"><i class="ti ti-car"></i> ' .
                        __("db.vehicle") .
                        "</span>";
            $nestedData["title"] = $job->title;
            $nestedData["status"] = $job->status_badge;
            $nestedData["priority"] = $job->priority_badge;
            $nestedData["warehouse"] = $warehouseName;
            $nestedData["total_amount"] = number_format(
                $job->total_amount,
                config("decimal")
            );
            $nestedData["due_amount"] = number_format(
                $job->due_amount,
                config("decimal")
            );
            $nestedData["options"] =
                '
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                        ' .
                __("db.action") .
                ' <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default">
                        <li><a href="' .
                route("repair.service.show", $job->id) .
                '" class="btn btn-link"><i class="ti ti-eye"></i> ' .
                __("db.View") .
                '</a></li>
                        <li><a href="' .
                route("repair.service.parts", $job->id) .
                '" class="btn btn-link"><i class="ti ti-settings"></i> Parts & Billing</a></li>
                        <li><a href="' .
                route("repair.service.edit", $job->id) .
                '" class="btn btn-link"><i class="ti ti-edit"></i> ' .
                __("db.edit") .
                '</a></li>
                        <form method="POST" action="' .
                route("repair.service.destroy", $job->id) .
                '">
                            ' .
                csrf_field() .
                method_field("DELETE") .
                '
                            <li><button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="ti ti-trash"></i> ' .
                __("db.delete") .
                '</button></li>
                        </form>
                    </ul>
                </div>';
            $nestedData["job_data"] = json_encode([
                "id" => $job->id,
                "reference_no" => $job->reference_no,
                "customer" => $customerName,
                "customer_phone" => optional($job->customer)->phone_number ?? "",
                "service_type" => $job->service_type,
                "title" => $job->title,
                "status" => $job->status,
                "priority" => $job->priority,
                "warehouse" => $warehouseName,
                "technician" => $techName,
                "total_amount" => $job->total_amount,
                "paid_amount" => $job->paid_amount,
                "due_amount" => $job->due_amount,
                "service_charge" => $job->service_charge,
                "description" => $job->description ?? "",
                "note" => $job->note,
                "date" => date(
                    config("date_format"),
                    strtotime($job->created_at)
                ),
                "expected_date" => $job->expected_delivery_date
                    ? $job->expected_delivery_date->format(
                        config("date_format")
                    )
                    : "N/A",
                "device" => $job->service_type === 'device' && $job->device ? [
                    "device_type" => optional($job->device->deviceTypeInfo)->name ?? $job->device->device_type,
                    "brand" => $job->device->brand ?? "-",
                    "model" => $job->device->model ?? "-",
                    "serial_number" => $job->device->serial_number ?? "-",
                    "imei" => $job->device->imei ?? "-",
                    "password_hint" => $job->device->password_hint ?? "-",
                    "accessories" => $job->device->accessories ?? "-",
                    "issue_reported" => $job->device->issue_reported ?? "",
                    "condition_notes" => $job->device->condition_notes ?? "",
                ] : null,
                "vehicle" => $job->service_type === 'vehicle' && $job->vehicle ? [
                    "vehicle_type" => optional($job->vehicle->vehicleTypeInfo)->name ?? $job->vehicle->vehicle_type,
                    "brand" => $job->vehicle->brand ?? "-",
                    "model" => $job->vehicle->model ?? "-",
                    "year" => $job->vehicle->year ?? "-",
                    "registration_no" => $job->vehicle->registration_no ?? "-",
                    "engine_no" => $job->vehicle->engine_no ?? "-",
                    "chassis_no" => $job->vehicle->chassis_no ?? "-",
                    "mileage" => $job->vehicle->mileage ? number_format($job->vehicle->mileage) . " km" : "-",
                    "fuel_level" => $job->vehicle->fuel_level ? ucfirst(str_replace('_', '/', $job->vehicle->fuel_level)) : "-",
                    "issue_reported" => $job->vehicle->issue_reported ?? "",
                    "condition_notes" => $job->vehicle->condition_notes ?? "",
                ] : null,
            ]);
            $data[] = $nestedData;
        }
        echo json_encode([
            "draw" => intval($request->input("draw")),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        ]);
    }

    public function create()
    {
        $lims_warehouse_list =
            Auth::user()->role_id > 2
                ? Warehouse::where([
                    ["is_active", true],
                    ["id", Auth::user()->warehouse_id],
                ])->get()
                : Warehouse::where("is_active", true)->get();

        $lims_customer_list = Customer::where("is_active", true)->get();
        $lims_technician_list = User::where("is_active", true)->get();
        $lims_brand_list = Brand::where("is_active", true)->get();
        $lims_product_list = Product::where([
            ["featured", 1],
            ["is_active", true],
        ])->get();

        // Load device types grouped by category column
        $device_types = DeviceType::active()
            ->where("category", "device")
            ->get();
        $vehicle_types = DeviceType::active()
            ->where("category", "vehicle")
            ->get();
        $lims_customer_group_all = CustomerGroup::all();
        $general_setting = GeneralSetting::first();

        return view(
            "repair::service.create",
            compact(
                "lims_warehouse_list",
                "lims_customer_list",
                "lims_technician_list",
                "lims_product_list",
                "lims_brand_list",
                "device_types",
                "vehicle_types",
                "lims_customer_group_all",
                "general_setting"
            )
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            "customer_id" => "required",
            "service_type" => "required|in:device,vehicle",
            "title" => "required|string|max:255",
            "warehouse_id" => "required",
        ]);
        try {
            $data = $request->all();
            $data['expected_delivery_date'] = $request->expected_delivery_date
                ? date('Y-m-d', strtotime(str_replace('/', '-', $request->expected_delivery_date))) : null;
            $job = app(RepairDomainService::class)->create($data, Auth::id());
            if ($request->input("submit_action") === "parts") {
                return redirect()->route("repair.service.parts", $job->id);
            }

            return redirect()->route("repair.service.index");
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput();
        }
    }

    public function partsAndBilling($id)
    {
        $job = ServiceJob::with(
        'customer', 'warehouse', 'items.product', 'payments.account'
        )->findOrFail($id);
        $lims_product_list = Product::where([['is_active', true], ['type', 'standard']])->get();
        $lims_account_list = app(\App\Services\PaymentAccountService::class)->validOperationalAccounts();

        // POS Setting থেকে payment options
        $lims_pos_setting_data = \App\Models\PosSetting::latest()->first();
        $payment_options = $lims_pos_setting_data ? explode(',', $lims_pos_setting_data->payment_options) : ['cash'];

        $general_setting = GeneralSetting::first();

        $productArray = [];
        foreach ($lims_product_list as $product) {
            $productArray[] = htmlspecialchars($product->code) . ' (' . htmlspecialchars($product->name) . ')';
        }

        return view('repair::service.parts', compact(
            'job', 'lims_product_list', 'lims_account_list',
            'productArray', 'payment_options', 'general_setting'
        ));
    }

    public function addPart(Request $request, $id)
    {
        $request->validate([
            "product_id" => "required|exists:products,id",
            "quantity" => "required|numeric|min:0.01",
            "unit_price" => "required|numeric|min:0",
        ]);
        try {
            $job = ServiceJob::findOrFail($id);
            $product = Product::findOrFail($request->product_id);
            $qty = (float) $request->quantity;
            $price = (float) $request->unit_price;
            $item = app(RepairDomainService::class)->addPart($job, $request->only('product_id', 'quantity', 'unit_price'));
            return response()->json([
                "success" => true,
                "item" => [
                    "id" => $item->id,
                    "product_name" =>
                        $product->name . " [" . $product->code . "]",
                    "quantity" => $qty,
                    "unit_price" => number_format($price, config("decimal")),
                    "total" => number_format($item->total, config("decimal")),
                ],
                "job_totals" => $this->jobTotals($job),
            ]);
        } catch (\Throwable $e) {
            return response()->json(
                ["success" => false, "message" => $e->getMessage()],
                500
            );
        }
    }

    public function removePart(Request $request, $jobId, $itemId)
    {
        try {
            DB::beginTransaction();
            $job = ServiceJob::findOrFail($jobId);
            $item = ServiceJobItem::where([
                ["id", $itemId],
                ["service_job_id", $jobId],
            ])->firstOrFail();
            $item->delete();
            $job->recalculateTotals();
            DB::commit();
            return response()->json([
                "success" => true,
                "job_totals" => $this->jobTotals($job),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(
                ["success" => false, "message" => $e->getMessage()],
                500
            );
        }
    }

    public function updateCharges(Request $request, $id)
    {
        $job = ServiceJob::findOrFail($id);
        $job = app(RepairDomainService::class)->updateCharges($job, $request->only('service_charge', 'discount', 'tax'));
        return response()->json([
            "success" => true,
            "job_totals" => $this->jobTotals($job),
        ]);
    }

    public function addPayment(Request $request, $id)
    {
        $request->validate([
            "amount" => "required|numeric|min:0.01",
            "paying_method" => "required|string",
            "account_id" => "required|exists:accounts,id",
        ]);
        app(\App\Services\PaymentAccountService::class)->assertValidId((int) $request->account_id);

        try {
            DB::beginTransaction();

            $job = ServiceJob::findOrFail($id);
            $amount = (float) $request->amount;

            if ($amount > $job->due_amount + 0.001) {
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Payment exceeds due (" .
                            number_format($job->due_amount, config("decimal")) .
                            ")",
                    ],
                    422
                );
            }

            // Sale খুঁজে নেওয়া (যদি না থাকে তাহলে create করবে syncToSale)
            $sale = $job->sale ?? $job->syncToSale();

            $payment = Payment::create([
                "service_job_id" => $job->id,
                "sale_id" => $sale->id, // ← Sale এর সাথে link
                "user_id" => Auth::id(),
                "account_id" => $request->account_id,
                "amount" => $amount,
                "paying_method" => $request->paying_method,
                "payment_reference" => "rep-" . date("Ymd") . "-" . date("his"),
                "payment_note" => $request->payment_reference,
                "payment_at" => now(),
                "change" => 0,
                "exchange_rate" => 1,
            ]);

            // Totals recalculate → Sale ও update হবে
            $job->recalculateTotals();

            // Sale paid_amount ও update করতে হবে আলাদাভাবে
            // কারণ Sale এ Payment গুলো sale_id দিয়ে linked
            $sale->paid_amount = $sale->payments()->sum("amount");
            $balance = $sale->grand_total - $sale->paid_amount;
            $sale->payment_status = $balance > 0.001 ? 3 : 4;
            $sale->save();

            if (class_exists(\App\Services\AccountingService::class)) {
                app(\App\Services\AccountingService::class)->recordPayment($payment, 'repair_payment_received');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'payment' => [
                    'id'        => $payment->id,
                    'amount'    => number_format($amount, config('decimal')),
                    'method'    => $payment->paying_method,
                    'account'   => optional($payment->account)->name ?? '—',  // ← এটা যোগ করুন
                    'reference' => $payment->payment_reference,
                    'date'      => $payment->payment_at->format(config('date_format')),
                    'note'      => $payment->payment_note,
                ],
                'job_totals' => $this->jobTotals($job),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(
                ["success" => false, "message" => $e->getMessage()],
                500
            );
        }
    }

    public function deletePayment(Request $request, $jobId, $paymentId)
    {
        try {
            DB::beginTransaction();

            $job = ServiceJob::findOrFail($jobId);
            $payment = Payment::where([["id", $paymentId], ["service_job_id", $jobId]])->firstOrFail();
            
            if (class_exists(\App\Services\AccountingService::class)) {
                app(\App\Services\AccountingService::class)->reverseTransaction(get_class($payment), $payment->id, '_deleted');
            }
            
            $payment->delete();

            $job->recalculateTotals(); // ← এটাই Sale ও update করবে

            // Sale payment_status refresh
            if ($sale = $job->sale) {
                $sale->paid_amount = $sale->payments()->sum("amount");
                $balance = $sale->grand_total - $sale->paid_amount;
                $sale->payment_status =
                    $balance > 0.001 ? ($sale->paid_amount > 0 ? 3 : 1) : 4;
                $sale->save();
            }

            DB::commit();

            return response()->json([
                "success" => true,
                "job_totals" => $this->jobTotals($job),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(
                ["success" => false, "message" => $e->getMessage()],
                500
            );
        }
    }

    public function show($id)
    {
        $job = ServiceJob::with(
            "customer",
            "warehouse",
            "assignedTo",
            "createdBy",
            "device",
            "vehicle",
            "items.product",
            "payments",
            "updates.updatedBy"
        )->findOrFail($id);
        return view("repair::service.show", compact("job"));
    }

    public function edit($id)
    {
        $job = ServiceJob::with("device", "vehicle")->findOrFail($id);
        $lims_warehouse_list = Warehouse::where("is_active", true)->get();
        $lims_customer_list = Customer::where("is_active", true)->get();
        $lims_technician_list = User::where("is_active", true)->get();
        $lims_brand_list = Brand::where("is_active", true)->get();

        $device_types = DeviceType::active()->where("category", "device")->get();
        $vehicle_types = DeviceType::active()->where("category", "vehicle")->get();

        return view(
            "repair::service.edit",
            compact(
                "job",
                "lims_warehouse_list",
                "lims_customer_list",
                "lims_technician_list",
                "lims_brand_list",
                "device_types",
                "vehicle_types"
            )
        );
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            "title" => "required|string|max:255",
            "warehouse_id" => "required",
        ]);
        try {
            DB::beginTransaction();
            $job = ServiceJob::findOrFail($id);
            $old_status = $job->status;
            $job->update([
                "title" => $request->title,
                "description" => $request->description,
                "status" => $request->status,
                "priority" => $request->priority,
                "assigned_to" => $request->assigned_to,
                "warehouse_id" => $request->warehouse_id,
                "note" => $request->note,
                "expected_delivery_date" => $request->expected_delivery_date
                    ? date(
                        "Y-m-d",
                        strtotime(
                            str_replace(
                                "/",
                                "-",
                                $request->expected_delivery_date
                            )
                        )
                    )
                    : null,
                "delivery_date" => in_array($request->status, [
                    "completed",
                    "delivered",
                ])
                    ? now()->toDateString()
                    : $job->delivery_date,
            ]);
            if ($job->service_type === "device" && $job->device) {
                $job->device->update([
                    "device_type" => $request->device_type_id ?? "Other",
                    "brand" => $request->device_brand,
                    "model" => $request->device_model,
                    "serial_number" => $request->serial_number,
                    "imei" => $request->imei,
                    "password_hint" => $request->password_hint,
                    "accessories" => $request->accessories,
                    "issue_reported" => $request->device_issue_reported,
                    "condition_notes" => $request->device_condition_notes,
                ]);
            } elseif ($job->service_type === "vehicle" && $job->vehicle) {
                $job->vehicle->update([
                    "vehicle_type" => $request->vehicle_type_id ?? "other",
                    "brand" => $request->vehicle_brand,
                    "model" => $request->vehicle_model,
                    "year" => $request->vehicle_year,
                    "registration_no" => $request->registration_no,
                    "engine_no" => $request->engine_no,
                    "chassis_no" => $request->chassis_no,
                    "mileage" => $request->mileage,
                    "fuel_level" => $request->fuel_level,
                    "issue_reported" => $request->vehicle_issue_reported,
                    "condition_notes" => $request->vehicle_condition_notes,
                ]);
            }
            if ($old_status !== $request->status) {
                ServiceJobUpdate::create([
                    "service_job_id" => $job->id,
                    "status" => $request->status,
                    "note" => $request->status_note ?? "Status updated.",
                    "updated_by" => Auth::id(),
                ]);
            }
            DB::commit();
            return redirect()
                ->route("repair.service.show", $id)
                ->with("message", "Updated successfully.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->with("not_permitted", "Error: " . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $job = ServiceJob::with("items.product", "payments", "sale")->find($id);
            if (!$job) {
                return redirect()->route("repair.service.index");
            }
            app(RepairDomainService::class)->cancel($job);

            return redirect()->route("repair.service.index");
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with("not_permitted", "Error: " . $e->getMessage());
        }
    }

    public function printContent($id)
    {
        $job = ServiceJob::with([
            'customer', 'warehouse', 'assignedTo', 'createdBy',
            'device.deviceTypeInfo', 'vehicle.vehicleTypeInfo',
            'items.product'
        ])->findOrFail($id);
        
        $general_setting = DB::table('general_settings')->latest()->first();

        return view('repair::service.partials.print_content', compact('job', 'general_setting'));
    }

    public function addUpdate(Request $request, $id)
    {
        $job = ServiceJob::findOrFail($id);
        $oldStatus = $job->status;
        $oldPriority = $job->priority;

        $job->status = $request->status;
        if ($request->has('priority')) {
            $job->priority = $request->priority;
        }
        $job->save();

        $note = $request->note;
        if (!$note && $oldPriority !== $job->priority) {
            $note = 'Priority updated to ' . ucfirst($job->priority) . '.';
        }

        ServiceJobUpdate::create([
            "service_job_id" => $id,
            "status" => $request->status,
            "note" => $note,
            "updated_by" => Auth::id(),
        ]);
        return response()->json(["success" => true]);
    }

    private function jobTotals(ServiceJob $job): array
    {
        $job->refresh();
        return [
            "parts_total" => number_format(
                $job->items()->sum("total"),
                config("decimal")
            ),
            "service_charge" => number_format(
                $job->service_charge,
                config("decimal")
            ),
            "discount" => number_format($job->discount, config("decimal")),
            "tax" => number_format($job->tax, config("decimal")),
            "total_amount" => number_format(
                $job->total_amount,
                config("decimal")
            ),
            "paid_amount" => number_format(
                $job->paid_amount,
                config("decimal")
            ),
            "due_amount" => number_format($job->due_amount, config("decimal")),
        ];
    }

    public function partsData($id)
    {
        $job = ServiceJob::with("items.product")->findOrFail($id);
        $items = $job->items->map(
            fn($i) => [
                "product_name" =>
                    $i->product->name . " [" . $i->product->code . "]",
                "quantity" => $i->quantity,
                "unit_price" => number_format(
                    $i->unit_price,
                    config("decimal")
                ),
                "discount" => number_format($i->discount, config("decimal")),
                "tax" => number_format($i->tax, config("decimal")),
                "total" => number_format($i->total, config("decimal")),
            ]
        );
        return response()->json(
            array_merge(["items" => $items], $this->jobTotals($job))
        );
    }

    public function updatePart(Request $request, $jobId, $itemId)
    {
        $request->validate([
            "quantity" => "required|numeric|min:0.01",
            "unit_price" => "required|numeric|min:0",
        ]);

        try {
            DB::beginTransaction();

            $job = ServiceJob::findOrFail($jobId);
            $item = ServiceJobItem::where([
                ["id", $itemId],
                ["service_job_id", $jobId],
            ])->firstOrFail();
            $product = Product::findOrFail($item->product_id);

            $old_qty = (float) $item->quantity;
            $new_qty = (float) $request->quantity;
            $new_price = (float) $request->unit_price;
            $diff_qty = $new_qty - $old_qty; // positive = need more stock, negative = return stock

            // Stock check when increasing qty
            if ($diff_qty > 0) {
                $pw = Product_Warehouse::where([
                    ["product_id", $product->id],
                    ["warehouse_id", $job->warehouse_id],
                ])->first();

                $available = $pw ? $pw->qty : 0;

                if ($diff_qty > $available) {
                    return response()->json(
                        [
                            "success" => false,
                            "message" => "Insufficient stock! Available: {$available}",
                        ],
                        422
                    );
                }
            }

            // Update item
            $item->quantity = $new_qty;
            $item->unit_price = $new_price;
            $item->total = $new_qty * $new_price;
            $item->save();

            $job->recalculateTotals();

            DB::commit();

            return response()->json([
                "success" => true,
                "item_total" => number_format($item->total, config("decimal")),
                "job_totals" => $this->jobTotals($job),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(
                ["success" => false, "message" => $e->getMessage()],
                500
            );
        }
    }

    public function limsProductSearch(Request $request)
    {
        $warehouse_id = $request->warehouse_id;

        $product_code = explode("(", $request["data"]);
        $product_code[0] = rtrim($product_code[0], " ");

        $lims_product_list = Product::where([
            ["code", $product_code[0]],
            ["is_active", true],
        ])->get();

        if (count($lims_product_list) == 0) {
            $lims_product_list = Product::join(
                "product_variants",
                "products.id",
                "product_variants.product_id"
            )
                ->select(
                    "products.*",
                    "product_variants.item_code",
                    "product_variants.variant_id",
                    "product_variants.additional_price"
                )
                ->where("product_variants.item_code", $product_code[0])
                ->get();
        } elseif ($lims_product_list[0]->is_variant) {
            $lims_product_list = Product::join(
                "product_variants",
                "products.id",
                "product_variants.product_id"
            )
                ->select(
                    "products.*",
                    "product_variants.item_code",
                    "product_variants.variant_id",
                    "product_variants.additional_price"
                )
                ->where(
                    "product_variants.product_id",
                    $lims_product_list[0]->id
                )
                ->get();
        }

        $products = [];

        foreach ($lims_product_list as $lims_product_data) {
            $product = [];

            // [0] name
            $product[] = $lims_product_data->name;

            // [1] code or item_code
            if ($lims_product_data->is_variant) {
                $product[] = $lims_product_data->item_code;
                $variant_id = $lims_product_data->variant_id;
                $additional_price = $lims_product_data->additional_price;
            } else {
                $product[] = $lims_product_data->code;
                $variant_id = "";
                $additional_price = 0;
            }

            // [2] price + additional_price  ← repair module এ unit_price হিসেবে এটাই ব্যবহার হবে
            $product[] = $lims_product_data->price + $additional_price;

            // [3] barcode PNG (base64)
            $product[] = DNS1D::getBarcodePNG(
                $product[1],
                $lims_product_data->barcode_symbology
            );

            // [4] promotion_price
            $product[] = $lims_product_data->promotion_price;

            // [5] currency symbol
            $product[] = config("currency");

            // [6] currency position
            $product[] = config("currency_position");

            // [7] qty (stock)
            $product[] = $lims_product_data->qty;

            // [8] product id
            $product[] = $lims_product_data->id;

            // [9] variant_id
            $product[] = $variant_id;

            // [10] cost
            $product[] = $lims_product_data->cost;

            // [11] brand title
            $brand = Brand::find($lims_product_data->brand_id);
            $product[] = $brand->title ?? "N/A";

            // [12] unit_id
            $product[] = $lims_product_data->unit_id ?? "N/A";

            // [13] unit select HTML
            $unit = Unit::query()
                ->where("id", $lims_product_data->unit_id)
                ->orWhere("base_unit", $lims_product_data->unit_id)
                ->get()
                ->unique("id");

            $unitOptions = "";
            foreach ($unit as $row) {
                $selected =
                    $lims_product_data->unit_id == $row->id ? "selected" : "";
                $unitOptions .=
                    '<option value="' .
                    $row->id .
                    '"' .
                    ' data-operation_value="' .
                    $row->operation_value .
                    '"' .
                    ' data-operator="' .
                    $row->operator .
                    '"' .
                    " " .
                    $selected .
                    ">" .
                    $row->unit_name .
                    "</option>";
            }

            $product[] =
                '
            <select name="combo_unit_id[]"
                    class="btn btn-outline-secondary form-control combo_unit_id"
                    onchange="calculate_price()">
                ' .
                $unitOptions .
                '
            </select>';

            // [14] diff_price flag  (barcode=true হলে warehouse price আলাদা কিনা)
            $diff_price = false;
            $warehouse_product = collect();

            if ($request->barcode == true) {
                $warehouse_product = Product_Warehouse::select(
                    "product_warehouse.*",
                    "warehouses.name as warehouse_name"
                )
                    ->join(
                        "warehouses",
                        "product_warehouse.warehouse_id",
                        "=",
                        "warehouses.id"
                    )
                    ->where(
                        "product_warehouse.product_id",
                        $lims_product_data->id
                    )
                    ->whereNotNull("product_warehouse.price")
                    ->latest()
                    ->get();

                foreach ($warehouse_product as $wh) {
                    if ($lims_product_data->price != $wh->price) {
                        $diff_price = true;
                    }
                }
            }

            $product[] = $diff_price; // [14]
            $product[] = $warehouse_product; // [15]

            $products[] = $product;
        }

        return $products;
    }
}
