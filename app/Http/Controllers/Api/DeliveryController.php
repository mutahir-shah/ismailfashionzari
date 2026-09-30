<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\ErrorResource;
use App\Models\Delivery;
use App\Models\Sale;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\APIPaginationTrait;
use App\Traits\TenantInfo;

class DeliveryController extends Controller
{

    use APIPaginationTrait, TenantInfo;
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access deliveries
            if (!$role->hasPermissionTo('delivery')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $search = $request->input('search', '');

            // Filter by user if staff access is restricted
            $query = Delivery::with(['sale.customer', 'courier', 'user']);

            if ($user->role_id > 2 && config('staff_access') == 'own') {
                $query->where('user_id', $user->id);
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('reference_no', 'LIKE', "%{$search}%")
                        ->orWhere('delivered_by', 'LIKE', "%{$search}%")
                        ->orWhere('recieved_by', 'LIKE', "%{$search}%")
                        ->orWhereHas('sale', function ($subQuery) use ($search) {
                            $subQuery->where('reference_no', 'LIKE', "%{$search}%");
                        })
                        ->orWhereHas('sale.customer', function ($subQuery) use ($search) {
                            $subQuery->where('name', 'LIKE', "%{$search}%");
                        });
                });
            }

            $query = $query->orderBy('id', 'desc');

            $deliveries = $this->resolveCollection($query, $request);
            $pagination = $this->resolvePagination($query, $request);

            // Format deliveries for datatable
            $deliveriesTable = $deliveries->map(function ($delivery) {
                // Get sale and customer information
                $sale = $delivery->sale;
                $customer = $sale ? $sale->customer : null;

                // Get product names for this delivery
                $productNames = [];
                if ($sale) {
                    $productNames = DB::table('product_sales')
                        ->join('products', 'products.id', '=', 'product_sales.product_id')
                        ->where('product_sales.sale_id', $sale->id)
                        ->pluck('products.name')
                        ->toArray();
                }

                // Get packing slip references
                $packingSlipRefs = ['N/A'];
                if ($delivery->packing_slip_ids) {
                    $packingSlipRefs = \App\Models\PackingSlip::whereIn('id', explode(',', $delivery->packing_slip_ids))
                        ->pluck('reference_no')
                        ->toArray();
                }

                // Determine status
                $statusText = '';
                $statusBadge = '';
                switch ($delivery->status) {
                    case 1:
                        $statusText = 'Packing';
                        $statusBadge = '<span class="badge badge-info">Packing</span>';
                        break;
                    case 2:
                        $statusText = 'Delivering';
                        $statusBadge = '<span class="badge badge-primary">Delivering</span>';
                        break;
                    case 3:
                        $statusText = 'Delivered';
                        $statusBadge = '<span class="badge badge-success">Delivered</span>';
                        break;
                    default:
                        $statusText = 'Unknown';
                        $statusBadge = '<span class="badge badge-secondary">Unknown</span>';
                }

                return [
                    'id' => $delivery->id,
                    'reference_no' => $delivery->reference_no,
                    'sale_reference' => $sale ? $sale->reference_no : 'N/A',
                    'packing_slip_reference' => implode(', ', $packingSlipRefs),
                    'customer' => $customer ? $customer->name . "\n" . $customer->phone_number : 'N/A',
                    'courier' => $delivery->courier ? $delivery->courier->name : 'N/A',
                    'address' => $delivery->address ?: 'N/A',
                    'products' => implode(', ', $productNames),
                    'grand_total' => $sale ? number_format($sale->grand_total, 2) : '0.00',
                    'status' => $statusBadge,
                ];
            });

            return $this->withDashBackground([
                'title' => "Deliveries",
                'row_height' => 5,
                'add_url' => null,
                'import_url' => null,
                'columns' => [
                    ['label' => 'Delivery Reference', 'field' => 'reference_no', 'type' => 'text'],
                    ['label' => 'Sale Reference', 'field' => 'sale_reference', 'type' => 'text'],
                    ['label' => 'Packing Slip Reference', 'field' => 'packing_slip_reference', 'type' => 'text'],
                    ['label' => 'Customer', 'field' => 'customer', 'type' => 'text'],
                    ['label' => 'Courier', 'field' => 'courier', 'type' => 'text'],
                    ['label' => 'Address', 'field' => 'address', 'type' => 'text'],
                    ['label' => 'Products', 'field' => 'products', 'type' => 'text'],
                    ['label' => 'Grand Total', 'field' => 'grand_total', 'type' => 'text'],
                    ['label' => 'Status', 'field' => 'status', 'type' => 'html'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/deliveries/{id}/edit',
                                    'type' => 'form'
                                ]
                            ]
                        ]
                    ],
                ],
                'rows' => $deliveriesTable,
                'pagination' => $pagination,
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving delivery data.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function create($saleId)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('delivery')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            $existingDelivery = Delivery::where('sale_id', $saleId)->first();

            if ($existingDelivery) {
                // Return existing delivery data for edit
                return $this->edit($existingDelivery->id);
            }

            // Get sale and customer data
            $saleData = DB::table('sales')
                ->join('customers', 'sales.customer_id', '=', 'customers.id')
                ->where('sales.id', $saleId)
                ->select(
                    'sales.reference_no as sale_reference',
                    'customers.name',
                    'customers.address',
                    'customers.city',
                    'customers.country',
                    'sales.shipping_address',
                    'sales.shipping_city',
                    'sales.shipping_country'
                )
                ->first();

            if (!$saleData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sale not found.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ], 404);
            }

            // Determine address based on addons
            $addons = explode(',', config('addons'));
            if (in_array('ecommerce', $addons) || in_array('restaurant', $addons)) {
                $address = trim($saleData->shipping_address . ' ' . $saleData->shipping_city . ' ' . $saleData->shipping_country);
            } else {
                $address = trim($saleData->address . ' ' . $saleData->city . ' ' . $saleData->country);
            }

            // Get couriers
            $couriers = Courier::where('is_active', true)->get();
            $courierOptions = $couriers->map(function ($courier) {
                return [
                    'label' => $courier->name,
                    'value' => $courier->id,
                ];
            })->toArray();

            $formSchema = [
                'title' => 'Add Delivery',
                'submit_url' => '/deliveries',
                'navigate_url' => '/deliveries',
                'method' => 'POST',
                'fields' => [
                    [
                        'type' => 'group',
                        'label' => 'Delivery Information',
                        'items' => [
                            [
                                'type' => 'text',
                                'name' => 'reference_no',
                                'label' => 'Delivery Reference',
                                'value' => 'dr-' . date("Ymd") . '-' . date("his"),
                                'readonly' => true,
                            ],
                            [
                                'type' => 'text',
                                'name' => 'sale_reference',
                                'label' => 'Sale Reference',
                                'value' => $saleData->sale_reference,
                                'readonly' => true,
                            ],
                            [
                                'type' => 'hidden',
                                'name' => 'sale_id',
                                'value' => $saleId,
                            ],
                            [
                                'type' => 'select',
                                'name' => 'status',
                                'label' => 'Status *',
                                'options' => [
                                    ['label' => 'Pending', 'value' => 1],
                                    ['label' => 'Packing', 'value' => 2],
                                    ['label' => 'Delivering', 'value' => 3],
                                    ['label' => 'Delivered', 'value' => 4],
                                ],
                                'required' => true,
                            ],
                            [
                                'type' => 'select',
                                'name' => 'courier_id',
                                'label' => 'Courier',
                                'options' => $courierOptions,
                            ],
                        ]
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Customer & Delivery Details',
                        'items' => [
                            [
                                'type' => 'text',
                                'name' => 'customer_name',
                                'label' => 'Customer Name',
                                'value' => $saleData->name,
                                'readonly' => true,
                            ],
                            [
                                'type' => 'text',
                                'name' => 'address',
                                'label' => 'Delivery Address *',
                                'value' => $address,
                                'multiline' => true,
                                'required' => true,
                            ],
                            [
                                'type' => 'text',
                                'name' => 'delivered_by',
                                'label' => 'Delivered By',
                                'placeholder' => 'Enter delivery person name',
                            ],
                            [
                                'type' => 'text',
                                'name' => 'recieved_by',
                                'label' => 'Received By',
                                'placeholder' => 'Enter receiver name',
                            ],
                            [
                                'type' => 'text',
                                'name' => 'note',
                                'label' => 'Note',
                                'placeholder' => 'Enter delivery note',
                                'multiline' => true,
                            ],
                            [
                                'type' => 'file',
                                'name' => 'file',
                                'label' => 'Attach Document',
                                'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx'],
                                'multiple' => false,
                            ],
                        ]
                    ],
                ],
            ];

            return response()->json($this->withDashBackground($formSchema, 'app'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the delivery form.',
                'error' => $e->getMessage(),
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('delivery')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            $data = $request->except('file');
            $delivery = Delivery::firstOrNew(['reference_no' => $data['reference_no']]);

            $document = $request->file('file');
            if ($document) {
                $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
                $documentName = $data['reference_no'] . '.' . $ext;

                if (config('database.connections.saleprosaas_landlord')) {
                    $documentName = $this->getTenantId() . '_' . $documentName;
                }

                $document->move(public_path('documents/delivery'), $documentName);
                $delivery->file = $documentName;
            }

            $delivery->sale_id = $data['sale_id'];
            $delivery->user_id = Auth::id();
            $delivery->courier_id = $data['courier_id'] ?? null;
            $delivery->address = $data['address'];
            $delivery->delivered_by = $data['delivered_by'] ?? null;
            $delivery->recieved_by = $data['recieved_by'] ?? null;
            $delivery->status = $data['status'];
            $delivery->note = $data['note'] ?? null;
            $delivery->save();

            return response()->json([
                'success' => true,
                'message' => 'Delivery created successfully.',
                'navigate_url' => '/deliveries',
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the delivery.',
                'error' => $e->getMessage(),
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access deliveries
            if (!$role->hasPermissionTo('delivery')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $delivery = Delivery::with(['sale.customer', 'courier', 'user'])->findOrFail($id);

            // Get couriers
            $couriers = Courier::where('is_active', true)->get();
            $courierOptions = $couriers->map(function ($courier) {
                return [
                    'label' => $courier->name,
                    'value' => $courier->id,
                ];
            })->toArray();

            $formSchema = [
                "title" => "Update Delivery",
                "submit_url" => "/deliveries/" . $id,
                "method" => "PUT",
                "navigate_url" => "/deliveries",
                "fields" => [
                    [
                        "type" => "helpertext",
                        "text" => "Update delivery status and tracking information.",
                    ],
                    [
                        "type" => "group",
                        "label" => "Delivery Information",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "delivery_reference",
                                "label" => "Delivery Reference",
                                "value" => $delivery->reference_no,
                                "readonly" => true,
                            ],
                            [
                                "type" => "text",
                                "name" => "sale_reference",
                                "label" => "Sale Reference",
                                "value" => $delivery->sale ? $delivery->sale->reference_no : 'N/A',
                                "readonly" => true,
                            ],
                            [
                                "type" => "text",
                                "name" => "customer_name",
                                "label" => "Customer",
                                "value" => $delivery->sale && $delivery->sale->customer ? $delivery->sale->customer->name : 'N/A',
                                "readonly" => true,
                            ],
                            [
                                "type" => "select",
                                "name" => "status",
                                "label" => "Status",
                                "value" => $delivery->status,
                                "options" => [
                                    ["label" => "Packing", "value" => 1],
                                    ["label" => "Delivering", "value" => 2],
                                    ["label" => "Delivered", "value" => 3],
                                ],
                            ],
                            [
                                "type" => "select",
                                "name" => "courier_id",
                                "label" => "Courier",
                                "value" => $delivery->courier_id,
                                "options" => $courierOptions,
                            ],
                            [
                                "type" => "text",
                                "name" => "delivered_by",
                                "label" => "Delivered By",
                                "placeholder" => "Enter delivered by",
                                "value" => $delivery->delivered_by,
                            ],
                            [
                                "type" => "text",
                                "name" => "recieved_by",
                                "label" => "Received By",
                                "placeholder" => "Enter received by",
                                "value" => $delivery->recieved_by,
                            ],
                            [
                                "type" => "text",
                                "name" => "address",
                                "label" => "Address",
                                "placeholder" => "Enter delivery address",
                                "value" => $delivery->address,
                                "multiline" => true,
                            ],
                            [
                                "type" => "text",
                                "name" => "note",
                                "label" => "Note",
                                "placeholder" => "Enter delivery notes",
                                "value" => $delivery->note,
                                "multiline" => true,
                            ],
                            [
                                "type" => "file",
                                "name" => "file",
                                "label" => "Attach File",
                                "allowed_extensions" => ["jpeg", "jpg", "png", "gif", "pdf", "doc", "docx"],
                                "multiple" => false,
                            ],
                        ],
                    ],
                    [
                        "type" => "hidden",
                        "name" => "delivery_id",
                        "value" => $delivery->id,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the edit form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access deliveries
            if (!$role->hasPermissionTo('delivery')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $delivery = Delivery::findOrFail($id);

            // Validate delivery data
            $request->validate([
                'status' => 'required|integer|in:1,2,3',
                'courier_id' => 'nullable|exists:couriers,id',
                'delivered_by' => 'nullable|string|max:255',
                'recieved_by' => 'nullable|string|max:255',
                'address' => 'required|string',
                'note' => 'nullable|string',
                'file' => 'nullable|file|mimes:jpeg,jpg,png,gif,pdf,doc,docx|max:10000',
            ]);

            $data = $request->only([
                'status',
                'courier_id',
                'delivered_by',
                'recieved_by',
                'address',
                'note'
            ]);

            // Handle file upload
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();

                if (config('database.connections.saleprosaas_landlord')) {
                    $fileName = $this->getTenantId() . '_' . $fileName;
                }

                $file->move(public_path('documents/delivery'), $fileName);

                // Delete old file if exists
                if ($delivery->file && file_exists(public_path('documents/delivery/' . $delivery->file))) {
                    unlink(public_path('documents/delivery/' . $delivery->file));
                }

                $data['file'] = $fileName;
            }

            $delivery->update($data);

            return new SuccessResource([
                'message' => 'Delivery updated successfully.',
                'navigate_url' => '/deliveries'
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while updating the delivery.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access deliveries
            if (!$role->hasPermissionTo('delivery')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $delivery = Delivery::with(['sale.customer', 'courier', 'user'])->findOrFail($id);

            return new SuccessResource([
                'data' => $delivery,
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving delivery details.',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
