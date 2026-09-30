<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\TransferResource;
use App\Http\Requests\StoreTransferRequest;
use DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\Product;
use App\Models\Transfer;
use App\Models\Warehouse;
use App\Models\MailSetting;
use App\Models\ProductBatch;
use App\Mail\TransferDetails;
use App\Models\ProductVariant;
use App\Models\ProductPurchase;
use App\Models\ProductTransfer;
use App\Models\Product_Warehouse;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;
use App\Traits\TenantInfo;
use App\Traits\APIPaginationTrait;

class TransferController extends Controller
{
    use \App\Traits\MailInfo;
    use TenantInfo, APIPaginationTrait;
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the transfers module
            if (!$role->hasPermissionTo('transfers-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $search = $request->input('search', '');

            $query = Transfer::with(['fromWarehouse', 'toWarehouse', 'user']);

            if (!empty($search)) {
                $query->where('reference_no', 'LIKE', "%{$search}%");
            }

            $transfers = $this->resolveCollection($query->orderBy('created_at', 'desc'), $request);
            $pagination = $this->resolvePagination($query->orderBy('created_at', 'desc'), $request);

            // Format transfers for datatable
            $transfersTable = $transfers->map(function ($transfer) {
                $statusHtml = '';
                if ($transfer->status == 1) {
                    $statusHtml = "<span class='badge badge-success'>Completed</span>";
                } elseif ($transfer->status == 2) {
                    $statusHtml = "<span class='badge badge-warning'>Sent</span>";
                } else {
                    $statusHtml = "<span class='badge badge-danger'>Pending</span>";
                }

                $emailSentHtml = '';
                if ($transfer->is_sent == 1) {
                    $emailSentHtml = "<div class='badge badge-success'>Yes</div>";
                } else {
                    $emailSentHtml = "<div class='badge badge-danger'>No</div>";
                }

                return [
                    'id' => $transfer->id,
                    'date' => date("d-m-Y", strtotime($transfer->created_at)),
                    'reference_no' => $transfer->reference_no,
                    'from_warehouse' => $transfer->fromWarehouse->name ?? 'N/A',
                    'to_warehouse' => $transfer->toWarehouse->name ?? 'N/A',
                    'item' => (int) $transfer->item,
                    'total_qty' => (float) $transfer->total_qty,
                    'total_tax' => number_format((float) $transfer->total_tax, 2),
                    'total_cost' => number_format((float) $transfer->total_cost, 2),
                    'grand_total' => number_format((float) $transfer->grand_total, 2),
                    'status' => $statusHtml,
                    'status_value' => $transfer->status,
                    'email_sent' => $emailSentHtml,
                    'is_sent' => $transfer->is_sent,
                ];
            });

            return $this->withDashBackground([
                'title' => "Transfers",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Transfer',
                'add_url' => '/transfers/create',
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Reference No', 'field' => 'reference_no', 'type' => 'text'],
                    ['label' => 'From Warehouse', 'field' => 'from_warehouse', 'type' => 'text'],
                    ['label' => 'To Warehouse', 'field' => 'to_warehouse', 'type' => 'text'],
                    ['label' => 'Product Cost', 'field' => 'total_cost', 'type' => 'text'],
                    ['label' => 'Product Tax', 'field' => 'total_tax', 'type' => 'text'],
                    ['label' => 'Grand Total', 'field' => 'grand_total', 'type' => 'text'],
                    ['label' => 'Status', 'field' => 'status', 'type' => 'html'],
                    ['label' => 'Email Sent', 'field' => 'email_sent', 'type' => 'html'],
                    [
                        'label' => 'Action',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/transfers/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/transfers/{id}',
                                    'type' => 'delete'
                                ]
                            ]
                        ]
                    ],
                ],
                'totals' => [
                    [
                        'name' => 'total_transfers',
                        'label' => 'Total Transfers',
                        'formula' => 'COUNT(*)',
                        'position' => 'reference_no',
                    ],
                    [
                        'name' => 'total_amount',
                        'label' => 'Total Amount',
                        'formula' => 'SUM({total_cost_raw})',
                        'position' => 'total_cost',
                        'format' => 'currency',
                        'decimal_places' => 2,
                        'style' => [
                            'font_weight' => 'bold',
                            'background_color' => '#f8f9fa',
                            'border_top' => '2px solid #007bff',
                        ],
                    ],
                ],
                'totals_style' => [
                    'background_color' => '#f8f9fa',
                    'border_top' => '1px solid #dee2e6',
                    'font_weight' => '500',
                    'padding' => '10px 8px',
                ],
                'rows' => $transfersTable,
                'pagination' => $pagination
            ], 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving transfer data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to add transfers
            if (!$role->hasPermissionTo('transfers-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Get warehouses
            $warehouses = Warehouse::where('is_active', true)->get();
            $warehouseOptions = $warehouses->map(function ($warehouse) {
                return [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id
                ];
            })->toArray();

            $formSchema = [
                "title" => "Create Transfer",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/transfers",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "datepicker",
                        "name" => "created_at",
                        "label" => "Date",
                        "placeholder" => "Select date",
                        "format_specifier" => "yyyy-MM-dd",
                    ],
                    [
                        "type" => "datagenerator",
                        "name" => "reference_no",
                        "label" => "Reference No",
                        "placeholder" => "Enter reference number",
                        "generator_url" => "/generate/transfer-reference",
                    ],
                    [
                        "type" => "select",
                        "name" => "from_warehouse_id",
                        "label" => "From Warehouse",
                        "options" => $warehouseOptions,
                    ],
                    [
                        "type" => "select",
                        "name" => "to_warehouse_id",
                        "label" => "To Warehouse",
                        "options" => $warehouseOptions,
                    ],
                    [
                        "type" => "table_generator",
                        "name" => "products",
                        "label" => "Transfer Products",
                        "search_url" => "/products/search",
                        "search_placeholder" => "Scan/Search product by name or code",
                        "info" => "Use barcode scanner or type product code/name to add products",
                        "show_info_icon" => true,
                        "duplicate_handling" => [
                            "strategy" => "update_quantity",
                            "identifier_field" => "id",
                            "update_fields" => ["qty"],
                            "error_message" => "This product is already added to the table",
                        ],
                        "style" => [
                            "header_background" => "#f8f9fa",
                            "header_text_color" => "#212529",
                            "row_background" => "#ffffff",
                            "alternate_row_background" => "#f8f9fa",
                            "border_color" => "#dee2e6",
                            "input_border_color" => "#ced4da",
                        ],
                        "columns" => [
                            [
                                "name" => "name",
                                "label" => "Product Name",
                                "type" => "text",
                                "editable" => false,
                                "width" => 200,
                            ],
                            [
                                "name" => "code",
                                "label" => "Code",
                                "type" => "text",
                                "editable" => false,
                                "width" => 120,
                            ],
                            [
                                "name" => "batch_no",
                                "label" => "Batch No",
                                "type" => "text",
                                "editable" => true,
                                "width" => 120,
                            ],
                            [
                                "name" => "qty",
                                "label" => "Quantity",
                                "type" => "number",
                                "editable" => true,
                                "width" => 100,
                                "decimal_places" => 0,
                            ],
                            [
                                "name" => "cost",
                                "label" => "Net Unit Cost",
                                "type" => "number",
                                "editable" => true,
                                "width" => 120,
                                "decimal_places" => 2,
                            ],
                            [
                                "name" => "tax",
                                "label" => "Tax %",
                                "type" => "number",
                                "editable" => true,
                                "width" => 100,
                                "decimal_places" => 2,
                            ],
                            [
                                "name" => "subtotal",
                                "label" => "Subtotal",
                                "type" => "formula",
                                "formula" => "qty * cost * (1 + tax / 100)",
                                "width" => 120,
                                "decimal_places" => 2,
                            ],
                        ],
                        "formula_engine" => [
                            "enabled" => true,
                            "auto_calculate" => true,
                        ],
                        "totals" => [
                            [
                                "label" => "Total Items",
                                "formula" => "COUNT(*)",
                                "position" => "left",
                            ],
                            [
                                "label" => "Total Quantity",
                                "formula" => "SUM({qty})",
                                "position" => "left",
                            ],
                            [
                                "label" => "Total Cost",
                                "formula" => "SUM({subtotal})",
                                "position" => "right",
                                "prefix" => config('currency'),
                            ],
                        ],
                        "totals_style" => [
                            "background" => "#e9ecef",
                            "text_color" => "#212529",
                            "font_weight" => "bold",
                        ],
                    ],
                    [
                        "type" => "text",
                        "name" => "shipping_cost",
                        "label" => "Shipping Cost",
                        "placeholder" => "Enter shipping cost",
                        "keyboard_type" => "number",
                    ],
                    [
                        "type" => "file",
                        "name" => "document",
                        "label" => "Attach Document",
                        "allowed_extensions" => ["pdf", "jpg", "jpeg", "png", "gif"],
                        "multiple" => false,
                    ],
                    [
                        "type" => "text",
                        "name" => "note",
                        "label" => "Note",
                        "placeholder" => "Enter transfer note",
                        "multiline" => true,
                    ],
                    [
                        "type" => "hidden",
                        "name" => "status",
                        "value" => 1,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the transfer form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreTransferRequest $request)
    {
        $data = $request->except('document', 'token');
        $data['user_id'] = Auth::id();
        $data['reference_no'] = 'tr-' . date("Ymd") . '-' . date("his");
        if (isset($data['created_at']))
            $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
        else
            $data['created_at'] = date("Y-m-d H:i:s");
        if ($request->hasFile('document')) {
            $document = $request->file('document');
            $v = Validator::make(
                [
                    'extension' => strtolower($document->getClientOriginalExtension()),
                ],
                [
                    'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                ]
            );
            if ($v->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid document format',
                    'errors' => $v->errors()
                ], 422);
            }

            $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
            $documentName = date("Ymdhis");
            if (!config('database.connections.saleprosaas_landlord')) {
                $documentName = $documentName . '.' . $ext;
            } else {
                $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
            }
            $document->move(public_path('documents/transfer'), $documentName);
            $data['document'] = $documentName;
        }

        // Calculate required fields
        $data['item'] = count($data['product_id'] ?? []);
        $data['total_qty'] = array_sum($data['qty'] ?? []);
        $data['total_tax'] = array_sum($data['tax'] ?? []);
        $data['total_cost'] = array_sum($data['subtotal'] ?? []);
        $data['grand_total'] = $data['total_cost'] + ($data['shipping_cost'] ?? 0);

        $lims_transfer_data = Transfer::create($data);

        $product_id = $data['product_id'];
        $imei_number = $data['imei_number'];
        $product_batch_id = $data['product_batch_id'];
        $product_code = $data['product_code'];
        $qty = $data['qty'];
        $purchase_unit = $data['purchase_unit'];
        $net_unit_cost = $data['net_unit_cost'];
        $tax_rate = $data['tax_rate'];
        $tax = $data['tax'];
        $total = $data['subtotal'];
        $product_transfer = [];

        foreach ($product_id as $i => $id) {
            $lims_purchase_unit_data  = Unit::where('unit_name', $purchase_unit[$i])->first();
            $product_transfer['variant_id'] = null;
            $product_transfer['product_batch_id'] = null;

            //get product data
            $lims_product_data = Product::select('is_variant')->find($id);
            if ($lims_product_data->is_variant) {
                $lims_product_variant_data = ProductVariant::select('variant_id')->FindExactProductWithCode($id, $product_code[$i])->first();
                $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($id, $lims_product_variant_data->variant_id, $data['from_warehouse_id'])->first();
                $product_transfer['variant_id'] = $lims_product_variant_data->variant_id;
            } elseif ($product_batch_id[$i]) {
                $lims_product_warehouse_data = Product_Warehouse::where([
                    ['product_batch_id', $product_batch_id[$i]],
                    ['warehouse_id', $data['from_warehouse_id']]
                ])->first();
                $product_transfer['product_batch_id'] = $product_batch_id[$i];
            } else {
                $lims_product_warehouse_data = Product_Warehouse::where([
                    ['product_id', $id],
                    ['warehouse_id', $data['from_warehouse_id']],
                ])->first();
            }

            if ($data['status'] != 2) {
                if ($lims_purchase_unit_data->operator == '*')
                    $quantity = $qty[$i] * $lims_purchase_unit_data->operation_value;
                else
                    $quantity = $qty[$i] / $lims_purchase_unit_data->operation_value;
                //deduct imei number if available
                if ($imei_number[$i]) {
                    $imei_numbers = explode(",", $imei_number[$i]);
                    $all_imei_numbers = explode(",", $lims_product_warehouse_data->imei_number);
                    foreach ($imei_numbers as $number) {
                        if (($j = array_search($number, $all_imei_numbers)) !== false) {
                            unset($all_imei_numbers[$j]);
                        }
                    }
                    $lims_product_warehouse_data->imei_number = implode(",", $all_imei_numbers);
                }
            } else
                $quantity = 0;
            //deduct quantity from sending warehouse
            $lims_product_warehouse_data->qty -= $quantity;
            $lims_product_warehouse_data->save();

            if ($data['status'] == 1) {
                if ($lims_product_data->is_variant) {
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($id, $lims_product_variant_data->variant_id, $data['to_warehouse_id'])->first();
                } elseif ($product_batch_id[$i]) {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_batch_id', $product_batch_id[$i]],
                        ['warehouse_id', $data['to_warehouse_id']]
                    ])->first();
                } else {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $id],
                        ['warehouse_id', $data['to_warehouse_id']],
                    ])->first();
                }
                //add quantity to destination warehouse
                if ($lims_product_warehouse_data)
                    $lims_product_warehouse_data->qty += $quantity;
                else {
                    $lims_product_warehouse_data = new Product_Warehouse();
                    $lims_product_warehouse_data->product_id = $id;
                    $lims_product_warehouse_data->product_batch_id = $product_transfer['product_batch_id'];
                    $lims_product_warehouse_data->variant_id = $product_transfer['variant_id'];
                    $lims_product_warehouse_data->warehouse_id = $data['to_warehouse_id'];
                    $lims_product_warehouse_data->qty = $quantity;
                }
                //add imei number if available
                if ($imei_number[$i]) {
                    if ($lims_product_warehouse_data->imei_number)
                        $lims_product_warehouse_data->imei_number .= ',' . $imei_number[$i];
                    else
                        $lims_product_warehouse_data->imei_number = $imei_number[$i];
                }

                $lims_product_warehouse_data->save();
            }

            $product_transfer['transfer_id'] = $lims_transfer_data->id;
            $product_transfer['product_id'] = $id;
            $product_transfer['imei_number'] = $imei_number[$i];
            $product_transfer['qty'] = $qty[$i];
            $product_transfer['purchase_unit_id'] = $lims_purchase_unit_data->id;
            $product_transfer['net_unit_cost'] = $net_unit_cost[$i];
            $product_transfer['tax_rate'] = $tax_rate[$i];
            $product_transfer['tax'] = $tax[$i];
            $product_transfer['total'] = $total[$i];
            ProductTransfer::create($product_transfer);
        }

        $message = 'Transfer created successfully';

        // Mail Send Start
        $mail_setting = MailSetting::latest()->first();
        $fromWareHouse = Warehouse::find($data['from_warehouse_id']);
        $toWareHouse = Warehouse::find($data['to_warehouse_id']);
        $mailData = [];

        //Data

        $mailData['date'] = date("Y-m-d", strtotime(str_replace("/", "-", $lims_transfer_data->created_at)));;
        $mailData['reference_no'] = $lims_transfer_data->reference_no;
        $mailData['status'] = $lims_transfer_data->status;
        $mailData['total_cost'] = $lims_transfer_data->total_cost;
        $mailData['shipping_cost'] = $lims_transfer_data->shipping_cost;
        $mailData['grand_total'] = $lims_transfer_data->grand_total;

        //From: Warehouse
        $mailData['from_warehouse'] = $fromWareHouse->name;
        $mailData['from_phone'] = $fromWareHouse->phone;
        $mailData['from_email'] = $fromWareHouse->email;
        $mailData['from_address'] = $fromWareHouse->address;

        //To: Warehouse
        $mailData['to_warehouse'] = $toWareHouse->name;
        $mailData['to_phone'] = $toWareHouse->phone;
        $mailData['to_email'] = $toWareHouse->email;
        $mailData['to_address'] = $toWareHouse->address;

        return response()->json([
            'success' => true,
            'message' => $message,
        ], 201);
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit transfers
            if (!$role->hasPermissionTo('transfers-edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $transfer = Transfer::with(['fromWarehouse', 'toWarehouse'])->findOrFail($id);

            // Get warehouses
            $warehouses = Warehouse::where('is_active', true)->get();
            $warehouseOptions = $warehouses->map(function ($warehouse) {
                return [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id
                ];
            })->toArray();

            // Get transfer products for editing
            $transferProducts = ProductTransfer::where('transfer_id', $id)->get();
            $productRows = [];

            foreach ($transferProducts as $item) {
                $product = Product::find($item->product_id);
                if (!$product) continue;

                $productRows[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'batch_no' => $item->batch_no ?? '',
                    'qty' => (int)$item->qty,
                    'cost' => number_format((float)$item->net_unit_cost, 2, '.', ''),
                    'tax' => number_format((float)$item->tax_rate, 2, '.', ''),
                    'subtotal' => number_format((float)$item->total, 2, '.', ''),
                ];
            }

            $formSchema = [
                "title" => "Edit Transfer",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/transfers/{$id}",
                "method" => "PUT",
                "navigate_url" => "/transfers",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Transfer Information",
                        "items" => [
                            [
                                "type" => "datepicker",
                                "name" => "created_at",
                                "label" => "Date",
                                "placeholder" => "Select date",
                                "format_specifier" => "dd MMMM, yyyy",
                                "value" => $transfer->created_at ? $transfer->created_at->format('d F, Y') : now()->format('d F, Y'),
                            ],
                            [
                                "type" => "text",
                                "name" => "reference_no_display",
                                "label" => "Reference No",
                                "value" => $transfer->reference_no,
                                "disabled" => true,
                            ],
                            [
                                "type" => "hidden",
                                "name" => "reference_no",
                                "value" => $transfer->reference_no,
                            ],
                            [
                                "type" => "select",
                                "name" => "from_warehouse_id",
                                "label" => "From Warehouse *",
                                "placeholder" => "Select source warehouse",
                                "options" => $warehouseOptions,
                                "value" => $transfer->from_warehouse_id,
                                "required" => true,
                            ],
                            [
                                "type" => "select",
                                "name" => "to_warehouse_id",
                                "label" => "To Warehouse *",
                                "placeholder" => "Select destination warehouse",
                                "options" => $warehouseOptions,
                                "value" => $transfer->to_warehouse_id,
                                "required" => true,
                            ],
                            [
                                "type" => "select",
                                "name" => "status",
                                "label" => "Status",
                                "placeholder" => "Select status",
                                "options" => [
                                    ["label" => "Pending", "value" => 1],
                                    ["label" => "Sent", "value" => 2],
                                    ["label" => "Completed", "value" => 3],
                                ],
                                "value" => $transfer->status,
                            ],
                        ]
                    ],
                    [
                        "type" => "group",
                        "label" => "Product Information",
                        "items" => [
                            [
                                "type" => "table_generator",
                                "name" => "products",
                                "label" => "Transfer Products",
                                "search_url" => "/products/search",
                                "search_placeholder" => "Scan/Search product by name or code",
                                "info" => "Use barcode scanner or type product code/name to add products",
                                "show_info_icon" => true,
                                "value" => $productRows,
                                "duplicate_handling" => [
                                    "strategy" => "update_quantity",
                                    "identifier_field" => "id",
                                    "update_fields" => ["qty"],
                                    "error_message" => "This product is already added to the table",
                                ],
                                "style" => [
                                    "header_background" => "#f8f9fa",
                                    "header_text_color" => "#212529",
                                    "row_background" => "#ffffff",
                                    "alternate_row_background" => "#f8f9fa",
                                    "border_color" => "#dee2e6",
                                    "input_border_color" => "#ced4da",
                                ],
                                "columns" => [
                                    [
                                        "name" => "name",
                                        "label" => "Product Name",
                                        "type" => "text",
                                        "editable" => false,
                                        "width" => 200,
                                    ],
                                    [
                                        "name" => "code",
                                        "label" => "Code",
                                        "type" => "text",
                                        "editable" => false,
                                        "width" => 120,
                                    ],
                                    [
                                        "name" => "batch_no",
                                        "label" => "Batch No",
                                        "type" => "text",
                                        "editable" => true,
                                        "width" => 120,
                                    ],
                                    [
                                        "name" => "qty",
                                        "label" => "Quantity",
                                        "type" => "number",
                                        "editable" => true,
                                        "width" => 100,
                                        "decimal_places" => 0,
                                    ],
                                    [
                                        "name" => "cost",
                                        "label" => "Net Unit Cost",
                                        "type" => "number",
                                        "editable" => true,
                                        "width" => 120,
                                        "decimal_places" => 2,
                                    ],
                                    [
                                        "name" => "tax",
                                        "label" => "Tax %",
                                        "type" => "number",
                                        "editable" => true,
                                        "width" => 100,
                                        "decimal_places" => 2,
                                    ],
                                    [
                                        "name" => "subtotal",
                                        "label" => "Subtotal",
                                        "type" => "formula",
                                        "formula" => "qty * cost * (1 + tax / 100)",
                                        "width" => 120,
                                        "decimal_places" => 2,
                                    ],
                                ],
                                "formula_engine" => [
                                    "enabled" => true,
                                    "auto_calculate" => true,
                                ],
                                "totals" => [
                                    [
                                        "label" => "Total Items",
                                        "formula" => "COUNT(*)",
                                        "position" => "left",
                                    ],
                                    [
                                        "label" => "Total Quantity",
                                        "formula" => "SUM({qty})",
                                        "position" => "left",
                                    ],
                                    [
                                        "label" => "Total Cost",
                                        "formula" => "SUM({subtotal})",
                                        "position" => "right",
                                        "prefix" => config('currency'),
                                    ],
                                ],
                                "totals_style" => [
                                    "background" => "#e9ecef",
                                    "text_color" => "#212529",
                                    "font_weight" => "bold",
                                ],
                            ],
                        ]
                    ],
                    [
                        "type" => "group",
                        "label" => "Additional Information",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "shipping_cost",
                                "label" => "Shipping Cost",
                                "placeholder" => "Enter shipping cost",
                                "keyboard_type" => "number",
                                "value" => $transfer->shipping_cost ?? "0.00",
                            ],
                            [
                                "type" => "file",
                                "name" => "document",
                                "label" => "Attach Document",
                                "allowed_extensions" => ["pdf", "jpg", "jpeg", "png", "gif"],
                                "multiple" => false,
                            ],
                            [
                                "type" => "editor",
                                "name" => "note",
                                "label" => "Note",
                                "placeholder" => "Enter transfer note",
                                "value" => $transfer->note,
                            ],
                        ]
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while editing the transfer form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit transfers
            if (!$role->hasPermissionTo('transfers-edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $transfer = Transfer::findOrFail($id);
            $data = $request->all();

            if (isset($data['created_at'])) {
                $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
            }

            if ($request->hasFile('document')) {
                $document = $request->file('document');
                $v = Validator::make(
                    [
                        'extension' => strtolower($document->getClientOriginalExtension()),
                    ],
                    [
                        'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                    ]
                );
                if ($v->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid file extension.',
                        'errors' => $v->errors(),
                    ], 422);
                }

                $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
                $documentName = date("Ymdhis");
                if (!config('database.connections.saleprosaas_landlord')) {
                    $documentName = $documentName . '.' . $ext;
                } else {
                    $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                }

                $document->move(public_path('documents/transfer'), $documentName);
                $data['document'] = $documentName;
            }

            $transfer->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Transfer updated successfully.',
                'navigate_url' => '/transfers'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the transfer.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to view transfers
            if (!$role->hasPermissionTo('transfers-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $transfer = Transfer::with(['fromWarehouse', 'toWarehouse', 'user'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new TransferResource($transfer),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving transfer details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to delete transfers
            if (!$role->hasPermissionTo('transfers-delete')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $transfer = Transfer::findOrFail($id);

            // Delete associated product transfers
            ProductTransfer::where('transfer_id', $id)->delete();

            // Delete the transfer
            $transfer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Transfer deleted successfully.',
                'navigate_url' => '/transfers'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the transfer.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getProductTransferData($id)
    {
        $lims_product_transfer_data = ProductTransfer::where('transfer_id', $id)->get();
        foreach ($lims_product_transfer_data as $key => $product_transfer_data) {
            $product = Product::find($product_transfer_data->product_id);
            $unit = Unit::find($product_transfer_data->purchase_unit_id);
            if ($product_transfer_data->variant_id) {
                $lims_product_variant_data = ProductVariant::select('item_code')->FindExactProduct($product_transfer_data->product_id, $product_transfer_data->variant_id)->first();
                $product->code = $lims_product_variant_data->item_code;
            }
            $product_transfer['products'][$key] = $product->name . ' [' . $product->code . ']';
            // if($product_transfer_data->imei_number)
            //     $product_transfer['imei_number'][$key] .= '<br>IMEI or Serial Number: ' . $product_transfer_data->imei_number;
            if (isset($product_transfer_data->imei_number)) {
                if (!isset($product_transfer['imei_number'][$key])) {
                    $product_transfer['imei_number'][$key] = '';  // Initialize the key if not already set
                }
                $product_transfer['imei_number'][$key] .= '<br>IMEI or Serial Number: ' . $product_transfer_data->imei_number;
            }
            $product_transfer['qty'][$key] = $product_transfer_data->qty;
            $product_transfer['unit'][$key] = $unit->unit_code;
            $product_transfer['tax'][$key] = $product_transfer_data->tax;
            $product_transfer['tax_rate'][$key] = $product_transfer_data->tax_rate;
            $product_transfer['total'][$key] = $product_transfer_data->total;
            if ($product_transfer_data->product_batch_id) {
                $product_batch_data = ProductBatch::select('batch_no')->find($product_transfer_data->product_batch_id);
                $product_transfer['batch_no'][$key] = $product_batch_data->batch_no;
            } else
                $product_transfer['batch_no'][$key] = 'N/A';
        }
        return $product_transfer;
    }
}
