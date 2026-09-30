<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ErrorResource;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use DNS1D;
use Exception;
use Keygen\Keygen;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\Brand;
use App\Models\Barcode;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Variant;
use App\Models\Category;
use App\Models\Purchase;
use App\Models\Warehouse;
use App\Models\GeneralSetting;
use App\Traits\TenantInfo;
use App\Models\CustomField;
use App\Traits\CacheForget;
use Illuminate\Support\Str;
use App\Models\ProductBatch;
use App\Models\ProductVariant;
use App\Models\ProductPurchase;
use Illuminate\Validation\Rule;
use App\Models\Product_Warehouse;
use App\Services\DataRetrievalService;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Http;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use App\Traits\APIPaginationTrait;

class ProductController extends Controller
{
    use CacheForget;
    use TenantInfo;
    use APIPaginationTrait;
    use ProvidesThemeBackgrounds;

    private $_dataRetrievalService;

    public function __construct(DataRetrievalService $dataRetrievalService)
    {
        $this->_dataRetrievalService = $dataRetrievalService;
    }

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the product module
            if (!$role->hasPermissionTo('products-index')) {
                return new ErrorResource('Sorry! You are not allowed to access this module.');
            }
            // Pagination and search
            $search = $request->input('search', '');

            $query = Product::with(['category', 'brand', 'unit', 'tax'])->where('is_active', true);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('code', 'LIKE', "%{$search}%");
                });
            }

            $query = $query->orderBy('id', 'desc');
            $products = $this->resolveCollection($query, $request);
            $pagination = $this->resolvePagination($query, $request);

            return $this->withDashBackground([
                'title' => "Products",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Product',
                'add_url' => '/products/create',
                'import_url' => '/products/import',
                'columns' => [
                    ['label' => 'Image', 'field' => 'image_url', 'type' => 'image'],
                    ['label' => 'Product', 'field' => 'name', 'type' => 'text'],
                    ['label' => 'Code', 'field' => 'code', 'type' => 'text'],
                    ['label' => 'Brand', 'field' => 'brand', 'type' => 'text'],
                    ['label' => 'Category', 'field' => 'category', 'type' => 'text'],
                    ['label' => 'Quantity', 'field' => 'quantity', 'type' => 'html'],
                    ['label' => 'Unit', 'field' => 'unit', 'type' => 'text'],
                    ['label' => 'Price', 'field' => 'price', 'type' => 'text'],
                    ['label' => 'Cost', 'field' => 'cost', 'type' => 'text'],
                    ['label' => 'Stock Worth (Price/Cost)', 'field' => 'stock_worth', 'type' => 'text'],
                    ['label' => 'Manage', 'type' => 'row', 'children' => [
                        [
                            'type' => 'button',
                            'label' => 'Print Barcode',
                            'action' => [
                                'api_url' => '/products/print-barcode/form',
                                'type' => 'form'
                            ]
                        ],
                        [
                            'type' => 'action',
                            'icon' => 'edit',
                            'action' => [
                                'api_url' => '/products/{id}/edit',
                                'type' => 'form'
                            ]
                        ],
                        [
                            'type' => 'action',
                            'icon' => 'delete',
                            'action' => [
                                'api_url' => '/products/{id}',
                                'type' => 'delete'
                            ]
                        ],
                    ]],
                ],
                'rows' => ProductResource::collection($products),
                'pagination' => $pagination,
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource('An error occurred while retrieving product data.');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to add products
            if (!$role->hasPermissionTo('products-add')) {
                return new ErrorResource('Sorry! You are not allowed to access this module.');
            }

            // Get general settings for module checks
            $general_setting = DB::table('general_settings')->select('modules')->first();

            $product_types = $this->_dataRetrievalService->getProductTypes();
            $barcode_symbologies = $this->_dataRetrievalService->getBarcodeSymbologies();
            $brands = $this->_dataRetrievalService->getAllBrands()->map(function ($brand) {
                return [
                    'value' => $brand->id,
                    'label' => $brand->title,
                ];
            });
            $categories = $this->_dataRetrievalService->getAllCategories()->map(function ($category) {
                return [
                    'value' => $category->id,
                    'label' => $category->name,
                ];
            });
            $units = $this->_dataRetrievalService->getAllUnits()->map(function ($unit) {
                return [
                    'value' => $unit->id,
                    'label' => ucwords($unit->unit_name) . " (" . strtoupper($unit->unit_code) . ")",
                ];
            });
            $logical_units = $this->_dataRetrievalService->getAllUnits()->map(function ($unit) {
                if ($unit->base_unit != null) {
                    return [
                        'value' => $unit->id,
                        'label' => ucwords($unit->unit_name) . " (" . strtoupper($unit->unit_code) . ")",
                        "logics" => [
                            [
                                "field" => "unit_id",
                                "values" => [$unit->base_unit],
                            ],
                        ]
                    ];
                } else {
                    return [
                        'value' => $unit->id,
                        'label' => ucwords($unit->unit_name) . " (" . strtoupper($unit->unit_code) . ")",
                    ];
                }
            })->toArray();
            $taxes = $this->_dataRetrievalService->getProductTaxes();
            $tax_methods = $this->_dataRetrievalService->getTaxMethods();
            $warranty_types = $this->_dataRetrievalService->getWarrentyType();
            $warehouses = Warehouse::where('is_active', true)->get();

            $formSchema = [
                "title" => "Add New Product",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/products",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "select",
                        "name" => "type",
                        "label" => "Type",
                        "enable_filter" => false,
                        "enable_search" => false,
                        "options" => $product_types,
                        "value" => "standard",
                    ],
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Product Name",
                    ],
                    [
                        "type" => "datagenerator",
                        "name" => "code",
                        "label" => "Product Code",
                        "generator_url" => "/generate-code"
                    ],
                    [
                        "type" => "select",
                        "name" => "barcode_symbology",
                        "label" => "Barcode Symbology",
                        "enable_filter" => false,
                        "enable_search" => false,
                        "options" => $barcode_symbologies,
                    ],
                    [
                        "type" => "file",
                        "name" => "file",
                        "label" => "Attach File",
                        "allowed_extensions" => ["pdf", "doc", "docx", "zip", "rar"],
                        "multiple" => false,
                        "logics" => [
                            [
                                "field" => "type",
                                "values" => ["digital"],
                            ],
                        ]
                    ],
                    [
                        "type" => "text",
                        "name" => "production_cost",
                        "label" => "Production Cost",
                        "placeholder" => "Enter production cost",
                        "keyboard_type" => "number",
                        "logics" => [
                            [
                                "field" => "type",
                                "values" => ["combo"],
                            ],
                        ]
                    ],
                    [
                        "type" => "select",
                        "name" => "brand_id",
                        "label" => "Brand",
                        "enable_filter" => false,
                        "enable_search" => false,
                        "options" => $brands,
                        "new_screen" => "/brands/create",
                    ],
                    [
                        "type" => "select",
                        "name" => "category_id",
                        "label" => "Category",
                        "enable_filter" => false,
                        "enable_search" => false,
                        "options" => $categories,
                        "new_screen" => "/categories/create",
                    ],
                    [
                        "type" => "select",
                        "name" => "unit_id",
                        "label" => "Product Unit",
                        "enable_filter" => false,
                        "enable_search" => false,
                        "options" => $units,
                        "new_screen" => "/units/create",
                        "logics" => [
                            [
                                "field" => "type",
                                "values" => ["standard", "combo"],
                            ],
                        ]
                    ],
                    [
                        "type" => "select",
                        "name" => "sale_unit_id",
                        "label" => "Sale Unit",
                        "enable_filter" => false,
                        "enable_search" => false,
                        "options" => array_filter($logical_units, function ($item) {
                            return !is_null($item);
                        }),
                        "logics" => [
                            [
                                "field" => "type",
                                "values" => ["standard"],
                            ],
                        ]
                    ],
                    [
                        "type" => "select",
                        "name" => "purchase_unit_id",
                        "label" => "Purchase Unit",
                        "enable_filter" => false,
                        "enable_search" => false,
                        "options" => array_filter($logical_units, function ($item) {
                            return !is_null($item);
                        }),
                        "logics" => [
                            [
                                "field" => "type",
                                "values" => ["standard"],
                            ],
                        ]
                    ],
                    [
                        "type" => "text",
                        "name" => "cost",
                        "label" => "Product Cost",
                        "keyboard_type" => "number",
                        "logics" => [
                            [
                                "field" => "type",
                                "values" => ["standard", "combo"],
                            ],
                        ]
                    ],
                    [
                        "type" => "text",
                        "name" => "profit_margin",
                        "label" => "Profit Margin (%)",
                        "keyboard_type" => "number",
                        "logics" => [
                            [
                                "field" => "type",
                                "values" => ["standard"],
                            ],
                        ]
                    ],
                    [
                        "type" => "text",
                        "name" => "price",
                        "label" => "Product Price",
                        "keyboard_type" => "number",
                    ],
                    [
                        "type" => "text",
                        "name" => "wholesale_price",
                        "label" => "Wholesale Price",
                        "keyboard_type" => "number",
                    ],
                    [
                        "type" => "text",
                        "name" => "daily_sale_objective",
                        "label" => "Daily Sale Objective",
                        "keyboard_type" => "number",
                        "info" => "Minimum qty which must be sold in a day. If not, you will be notified on dashboard. But you have to set up the cron job properly for that. Follow the documentation in that regard.",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "text",
                        "name" => "alert_quantity",
                        "label" => "Alert Quantity",
                        "keyboard_type" => "number",
                        "logics" => [
                            [
                                "field" => "type",
                                "values" => ["standard"],
                            ],
                        ]
                    ],
                    [
                        "type" => "select",
                        "name" => "tax_id",
                        "label" => "Product Tax",
                        "enable_filter" => false,
                        "enable_search" => false,
                        "options" => $taxes,
                        "new_screen" => "/taxes/create",
                    ],
                    [
                        "type" => "select",
                        "name" => "tax_method",
                        "label" => "Tax Method",
                        "enable_filter" => false,
                        "enable_search" => false,
                        "options" => $tax_methods,
                    ],
                    [
                        "type" => "group",
                        "label" => "Warranty",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "warranty",
                                "label" => "Period",
                                "keyboard_type" => "number",
                            ],
                            [
                                "type" => "select",
                                "name" => "warranty_type",
                                "label" => "Type",
                                "options" => $warranty_types,
                            ],
                        ],
                    ],
                    [
                        "type" => "group",
                        "label" => "Guarantee",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "guarantee",
                                "label" => "Period",
                                "keyboard_type" => "number",
                            ],
                            [
                                "type" => "select",
                                "name" => "guarantee_type",
                                "label" => "Type",
                                "options" => $warranty_types,
                            ],
                        ],
                    ],
                    [
                        "type" => "checkbox",
                        "name" => "featured",
                        "label" => "Featured",
                        "info" => "Featured product will be displayed in POS",
                        "logics" => [
                            [
                                "field" => "is_imei",
                                "values" => [false, null],
                            ],
                            [
                                "field" => "is_batch",
                                "values" => [false, null],
                            ],
                        ]
                    ],
                    [
                        "type" => "checkbox",
                        "name" => "is_embeded",
                        "label" => "Embedded Barcode",
                        "info" => "Check this if this product will be used in weight scale machine.",
                    ],
                    [
                        "type" => "checkbox",
                        "name" => "is_initial_stock",
                        "label" => "Initial Stock",
                        "info" => "This feature will not work for product with variants and batches",
                        "logics" => [
                            [
                                "field" => "is_variant",
                                "values" => [false, null],
                            ],
                            [
                                "field" => "is_batch",
                                "values" => [false, null],
                            ],
                            [
                                "field" => "is_imei",
                                "values" => [false, null],
                            ]
                        ]
                    ],
                ],
            ];

            // Add warehouse-specific initial stock fields
            foreach ($warehouses as $warehouse) {
                $formSchema['fields'][] = [
                    "type" => "group",
                    "label" => "Stock for " . $warehouse->name,
                    "logics" => [
                        [
                            "field" => "is_initial_stock",
                            "values" => [true],
                        ],
                    ],
                    "items" => [
                        [
                            "type" => "text",
                            "name" => "warehouse_name_stock_" . $warehouse->id,
                            "label" => "Warehouse",
                            "value" => $warehouse->name,
                            "info" => "Read only",
                            "show_info_icon" => true,
                        ],
                        [
                            "type" => "text",
                            "name" => "stock_" . $warehouse->id,
                            "label" => "Quantity",
                            "placeholder" => "Enter stock quantity for " . $warehouse->name,
                            "keyboard_type" => "number",
                        ],
                    ],
                ];
            }

            $formSchema['fields'][] = [
                "type" => "file",
                "name" => "image",
                "label" => "Product Image(s)",
                "allowed_extensions" => ["jpeg", "jpg", "png", "gif"],
                "multiple" => true,
                "info" => "You can upload multiple image. Only .jpeg, .jpg, .png, .gif file can be uploaded. First image will be base image.",
                "show_info_icon" => true,
            ];
            $formSchema['fields'][] = [
                "type" => "editor",
                "name" => "product_details",
                "label" => "Product Details",
            ];
            $formSchema['fields'][] = [
                "type" => "checkbox",
                "name" => "is_variant",
                "label" => "This product has variant",
                "logics" => [
                    [
                        "field" => "is_batch",
                        "values" => [false, null],
                    ],
                    [
                        "field" => "type",
                        "values" => ["standard"],
                    ],
                ]
            ];
            $formSchema['fields'][] = [
                "type" => "variant_generator",
                "name" => "variants",
                "label" => "Product Variants",
                "code_field" => "code", // Reference to product code field for auto-generating item codes
                "logics" => [
                    [
                        "field" => "is_variant",
                        "values" => [true],
                    ],
                ],
                "fields" => [
                    [
                        "type" => "tags",
                        "name" => "variant_option",
                        "label" => "Variant Options",
                        "placeholder" => "Enter option and press enter (e.g., Size, Color)",
                        "info" => "Add variant options like Size, Color, Material etc. Press enter after each option.",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "tags",
                        "name" => "variant_value",
                        "label" => "Variant Values",
                        "placeholder" => "Enter value and press enter (e.g., S, M, L, Red, Blue)",
                        "info" => "Add ALL variant values like S, M, L for Size AND Red, Blue for Color. Press enter after each value.",
                        "show_info_icon" => true,
                    ],
                ],
                "generated_fields" => [
                    [
                        "name" => "variant_name",
                        "type" => "hidden", // This will be generated from combinations
                    ],
                    [
                        "name" => "item_code",
                        "label" => "Item Code",
                        "type" => "text",
                        "readonly" => false,
                    ],
                    [
                        "name" => "additional_cost",
                        "label" => "Additional Cost",
                        "type" => "number",
                        "default" => "0",
                    ],
                    [
                        "name" => "additional_price",
                        "label" => "Additional Price",
                        "type" => "number",
                        "default" => "0",
                    ],
                ],
            ];
            $formSchema['fields'][] = [
                "type" => "checkbox",
                "name" => "is_diffPrice",
                "label" => "This product has different price for different warehouse",
                "logics" => [
                    [
                        "field" => "type",
                        "values" => ["standard"],
                    ],
                ]
            ];

            // Add warehouse-specific price fields
            foreach ($warehouses as $warehouse) {
                $formSchema['fields'][] = [
                    "type" => "group",
                    "label" => "Price for " . $warehouse->name,
                    "logics" => [
                        [
                            "field" => "is_diffPrice",
                            "values" => [true],
                        ],
                    ],
                    "items" => [
                        [
                            "type" => "text",
                            "name" => "warehouse_name_" . $warehouse->id,
                            "label" => "Warehouse",
                            "value" => $warehouse->name,
                            "info" => "Read only",
                            "readonly" => true,
                            "show_info_icon" => true,
                        ],
                        [
                            "type" => "text",
                            "name" => "diff_price_" . $warehouse->id,
                            "label" => "Price",
                            "placeholder" => "Enter price for " . $warehouse->name,
                            "keyboard_type" => "number",
                        ],
                    ],
                ];
            }

            $formSchema['fields'][] = [
                "type" => "checkbox",
                "name" => "is_batch",
                "label" => "This product has batch and expired date",
                "logics" => [
                    [
                        "field" => "is_variant",
                        "values" => [false, null],
                    ],
                    [
                        "field" => "type",
                        "values" => ["standard"],
                    ],
                ]
            ];
            $formSchema['fields'][] = [
                "type" => "checkbox",
                "name" => "is_imei",
                "label" => "This product has IMEI or Serial Numbers",
                "logics" => [
                    [
                        "field" => "type",
                        "values" => ["standard"],
                    ],
                ]
            ];
            $formSchema['fields'][] = [
                "type" => "checkbox",
                "name" => "promotion",
                "label" => "Add Promotional Price",
            ];
            $formSchema['fields'][] = [
                "type" => "text",
                "name" => "promotion_price",
                "label" => "Promotional Price",
                "placeholder" => "Enter promotional price",
                "keyboard_type" => "number",
                "logics" => [
                    [
                        "field" => "promotion",
                        "values" => [true],
                    ],
                ]
            ];
            $formSchema['fields'][] = [
                "type" => "datepicker",
                "name" => "starting_date",
                "label" => "Promotion Starting Date",
                "placeholder" => "Select starting date",
                "format_specifier" => "dd-MM-yyyy",
                "logics" => [
                    [
                        "field" => "promotion",
                        "values" => [true],
                    ],
                ]
            ];
            $formSchema['fields'][] = [
                "type" => "datepicker",
                "name" => "last_date",
                "label" => "Promotion Ending Date",
                "placeholder" => "Select ending date",
                "format_specifier" => "dd-MM-yyyy",
                "logics" => [
                    [
                        "field" => "promotion",
                        "values" => [true],
                    ],
                ]
            ];
            $formSchema['fields'][] = [
                "type" => "checkbox",
                "name" => "is_sync_disable",
                "label" => "Disable Woocommerce Sync",
            ];
            $formSchema['fields'][] = [
                "type" => "hidden",
                "name" => "is_active",
                "value" => 1,
            ];

            // Add CustomField support
            $custom_fields = \App\Models\CustomField::where(['belongs_to' => 'product'])->get();
            foreach ($custom_fields as $field) {
                $fieldName = str_replace(' ', '_', strtolower($field->name));
                $fieldConfig = [
                    "name" => $fieldName,
                    "label" => $field->name,
                ];

                switch ($field->type) {
                    case 'text':
                        $fieldConfig['type'] = 'text';
                        $fieldConfig['placeholder'] = $field->name;
                        break;
                    case 'number':
                        $fieldConfig['type'] = 'text';
                        $fieldConfig['keyboard_type'] = 'number';
                        $fieldConfig['placeholder'] = $field->name;
                        break;
                    case 'textarea':
                        $fieldConfig['type'] = 'text';
                        $fieldConfig['multiline'] = true;
                        $fieldConfig['placeholder'] = $field->name;
                        break;
                    case 'checkbox':
                        $fieldConfig['type'] = 'checkbox';
                        $fieldConfig['value'] = $field->default_value ?? false;
                        break;
                    case 'radio':
                        $fieldConfig['type'] = 'select';
                        $options = explode(',', $field->option);
                        $fieldConfig['options'] = array_map(function ($opt) {
                            return ['value' => trim($opt), 'label' => trim($opt)];
                        }, $options);
                        break;
                    case 'select':
                        $fieldConfig['type'] = 'select';
                        $options = explode(',', $field->option);
                        $fieldConfig['options'] = array_map(function ($opt) {
                            return ['value' => trim($opt), 'label' => trim($opt)];
                        }, $options);
                        break;
                    case 'multiselect':
                        $fieldConfig['type'] = 'select';
                        $fieldConfig['name'] = $fieldName . '[]';
                        $options = explode(',', $field->option);
                        $fieldConfig['options'] = array_map(function ($opt) {
                            return ['value' => trim($opt), 'label' => trim($opt)];
                        }, $options);
                        break;
                    case 'date':
                        $fieldConfig['type'] = 'datepicker';
                        $fieldConfig['format_specifier'] = 'dd MMMM, yyyy';
                        break;
                }

                $formSchema['fields'][] = $fieldConfig;
            }

            // Add SEO fields if ecommerce module is enabled
            if (in_array('ecommerce', explode(',', $general_setting->modules))) {
                $formSchema['fields'][] = [
                    "type" => "group",
                    "label" => "SEO",
                    "items" => [
                        [
                            "type" => "text",
                            "name" => "meta_title",
                            "label" => "Meta Title",
                        ],
                        [
                            "type" => "text",
                            "name" => "meta_description",
                            "label" => "Meta Description",
                        ],
                    ],
                ];
            }

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the form.',
                'errors' => ['An error occurred while loading the form.'],
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
                'error_details' => env("APP_DEBUG", false) ? $e->getMessage() : null,
                'trace' => env("APP_DEBUG", false) ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    public function store(StoreProductRequest $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to add products
            if (!$role->hasPermissionTo('products-add')) {
                return new ErrorResource('Sorry! You are not allowed to access this module.');
            }

            $data = $request->except('image', 'file', 'token');

            // handle warranty and guarantee
            if (!isset($data['warranty'])) {
                unset($data['warranty']);
                unset($data['warranty_type']);
            }
            if (!isset($data['guarantee'])) {
                unset($data['guarantee']);
                unset($data['guarantee_type']);
            }

            if (isset($data['is_variant'])) {
                // Ensure variant_option and variant_value are arrays
                $variant_option = isset($data['variant_option']) && is_array($data['variant_option'])
                    ? $data['variant_option']
                    : [];
                $variant_value = isset($data['variant_value']) && is_array($data['variant_value'])
                    ? $data['variant_value']
                    : [];

                $data['variant_option'] = json_encode(array_unique($variant_option));
                $data['variant_value'] = json_encode(array_unique($variant_value));
            } else {
                $data['variant_option'] = $data['variant_value'] = null;
            }

            $data['name'] = preg_replace('/[\n\r]/', "<br>", htmlspecialchars(trim($data['name']), ENT_QUOTES));

            if (in_array('ecommerce', explode(',', config('addons')))) {
                $data['slug'] = Str::slug($data['name'], '-');
                $data['slug'] = preg_replace('/[^A-Za-z0-9\-]/', '', $data['slug']);
                $data['slug'] = str_replace('\/', '/', $data['slug']);
            }

            if (in_array('restaurant', explode(',', config('addons')))) {
                $data['menu_type'] = implode(",", $request->menu_type);
            }

            if ($data['type'] == 'combo' || (isset($data['is_recipe']) && $data['is_recipe'] == 1)) {
                $data['product_list'] = implode(",", $data['product_id']);
                $data['variant_list'] = implode(",", $data['variant_id']);
                $data['qty_list'] = implode(",", $data['product_qty']);
                $data['price_list'] = implode(",", $data['unit_price']);
                if (isset($data['wastage_percent'])) {
                    $data['wastage_percent'] = implode(",", $data['wastage_percent']);
                }
                if (isset($data['combo_unit_id'])) {
                    $data['combo_unit_id'] = implode(",", $data['combo_unit_id']);
                }
            } elseif ($data['type'] == 'digital' || $data['type'] == 'service')
                $data['cost'] = $data['unit_id'] = $data['purchase_unit_id'] = $data['sale_unit_id'] = 0;

            // Handle date fields - filter out placeholder values
            if (isset($data['starting_date']) && ($data['starting_date'] == 'Select a Date' || empty($data['starting_date']))) {
                $data['starting_date'] = null;
            }
            if (isset($data['last_date']) && ($data['last_date'] == 'Select a Date' || empty($data['last_date']))) {
                $data['last_date'] = null;
            }

            $data['is_active'] = true;
            $images = $request->image;
            $image_names = [];
            if ($images) {
                // Ensure the necessary directories exist using public_path()
                $this->diffSizeOfImagePathExistOrCreate();

                foreach ($images as $key => $image) {
                    $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
                    $imageName = date("Ymdhis") . ($key + 1);

                    // Handle multi-tenant logic if necessary
                    if (!config('database.connections.saleprosaas_landlord')) {
                        $imageName = $imageName . '.' . $ext;
                    } else {
                        $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                    }

                    $image->move(public_path('images/product'), $imageName);

                    $manager = new ImageManager(new GdDriver());
                    $image = $manager->read(public_path('images/product/' . $imageName));

                    $image->resize(1000, 1250)->save(public_path('images/product/xlarge/' . $imageName));
                    $image->resize(500, 500)->save(public_path('images/product/large/' . $imageName));
                    $image->resize(250, 250)->save(public_path('images/product/medium/' . $imageName));
                    $image->resize(100, 100)->save(public_path('images/product/small/' . $imageName));

                    // Collect image names for saving in the database
                    $image_names[] = $imageName;
                }

                // Save the image names in the database
                $data['image'] = implode(",", $image_names);
            } else {
                $data['image'] = 'zummXD2dvAtI.png';
            }
            $file = $request->file;
            if ($file) {
                $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                $fileName = strtotime(date('Y-m-d H:i:s'));
                if (!config('database.connections.saleprosaas_landlord')) {
                    $fileName = $fileName . '.' . $ext;
                } else {
                    $fileName = $this->getTenantId() . '_' . $fileName . '.' . $ext;
                }
                $file->move(public_path('product/files'), $fileName);
                $data['file'] = $fileName;
            }
            if (!isset($data['is_sync_disable']) && \Schema::hasColumn('products', 'is_sync_disable'))
                $data['is_sync_disable'] = null;

            $lims_product_data = Product::create($data);

            $custom_field_data = [];
            $custom_fields = CustomField::where('belongs_to', 'product')->select('name', 'type')->get();
            foreach ($custom_fields as $type => $custom_field) {
                $field_name = str_replace(' ', '_', strtolower($custom_field->name));
                if (isset($data[$field_name])) {
                    if ($custom_field->type == 'checkbox' || $custom_field->type == 'multi_select')
                        $custom_field_data[$field_name] = implode(",", $data[$field_name]);
                    else
                        $custom_field_data[$field_name] = $data[$field_name];
                }
            }
            if (count($custom_field_data))
                DB::table('products')->where('id', $lims_product_data->id)->update($custom_field_data);

            // Parse warehouse-specific stock fields (stock_1, stock_2, etc.) into arrays
            $stock_warehouse_ids = [];
            $stocks = [];
            foreach ($request->all() as $key => $value) {
                if (strpos($key, 'stock_') === 0 && strpos($key, 'warehouse_name_stock_') === false) {
                    $warehouse_id = str_replace('stock_', '', $key);
                    if (is_numeric($warehouse_id) && $value > 0) {
                        $stock_warehouse_ids[] = $warehouse_id;
                        $stocks[] = $value;
                    }
                }
            }
            if (count($stock_warehouse_ids) > 0) {
                $data['stock_warehouse_id'] = $stock_warehouse_ids;
                $data['stock'] = $stocks;
            }

            //dealing with initial stock and auto purchase
            $initial_stock = 0;
            if (
                isset($data['is_initial_stock']) && !isset($data['is_variant']) && !isset($data['is_batch'])
                && isset($data['stock_warehouse_id']) && is_array($data['stock_warehouse_id'])
            ) {
                foreach ($data['stock_warehouse_id'] as $key => $warehouse_id) {
                    $stock = $data['stock'][$key] ?? 0;
                    if ($stock > 0) {
                        $this->autoPurchase($lims_product_data, $warehouse_id, $stock);
                        $initial_stock += $stock;
                    }
                }
            }
            if ($initial_stock > 0) {
                $lims_product_data->qty += $initial_stock;
                $lims_product_data->save();
            }

            //dealing with product variant
            // Variants are sent as:
            // - variant_option and variant_value (tags arrays) - stored as JSON
            // - variant_name[] (generated combinations) - e.g., "Red-Large", "Blue-Small"
            // - item_code[] - product codes for each variant
            // - additional_cost[] - extra cost for each variant
            // - additional_price[] - extra price for each variant
            if (!isset($data['is_batch']))
                $data['is_batch'] = null;

            $variant_ids = [];
            if (isset($data['is_variant']) && isset($data['variant_name']) && is_array($data['variant_name'])) {
                foreach ($data['variant_name'] as $key => $variant_name) {
                    $lims_variant_data = Variant::firstOrCreate(['name' => $variant_name]);
                    $variant_ids[] = $lims_variant_data->id;
                    $product_variant = ProductVariant::firstOrNew([
                        'product_id' => $lims_product_data->id,
                        'variant_id' => $lims_variant_data->id,
                    ]);
                    $product_variant->item_code = $data['item_code'][$key] ?? '';
                    $product_variant->additional_cost = $data['additional_cost'][$key] ?? 0;
                    $product_variant->additional_price = $data['additional_price'][$key] ?? 0;
                    $product_variant->qty = 0;
                    $product_variant->position = $key + 1;
                    $product_variant->save();
                }
            }

            // Parse warehouse-specific price fields (diff_price_1, diff_price_2, etc.) into arrays
            $warehouse_ids = [];
            $diff_prices = [];
            foreach ($request->all() as $key => $value) {
                if (strpos($key, 'diff_price_') === 0 && strpos($key, 'warehouse_name_') === false) {
                    $warehouse_id = str_replace('diff_price_', '', $key);
                    if (is_numeric($warehouse_id) && $value > 0) {
                        $warehouse_ids[] = $warehouse_id;
                        $diff_prices[] = $value;
                    }
                }
            }
            if (count($warehouse_ids) > 0) {
                $data['warehouse_id'] = $warehouse_ids;
                $data['diff_price'] = $diff_prices;
            }

            if (isset($data['is_diffPrice']) && isset($data['diff_price']) && is_array($data['diff_price'])) {
                foreach ($data['diff_price'] as $key => $diff_price) {
                    if ($diff_price) {
                        Product_Warehouse::firstOrCreate([
                            "product_id" => $lims_product_data->id,
                            "warehouse_id" => $data["warehouse_id"][$key],
                            "qty" => 0,
                            "price" => $diff_price
                        ]);
                    }
                }
            } elseif (!isset($data['is_initial_stock']) && !isset($data['is_batch']) && config('without_stock') == 'yes') {
                $warehouse_ids = Warehouse::where('is_active', true)->pluck('id');
                foreach ($warehouse_ids as $warehouse_id) {
                    if (count($variant_ids)) {
                        foreach ($variant_ids as $variant_id) {
                            Product_Warehouse::firstOrCreate([
                                "product_id" => $lims_product_data->id,
                                "variant_id" => $variant_id,
                                "warehouse_id" => $warehouse_id,
                                "qty" => 0,
                            ]);
                        }
                    } else {
                        Product_Warehouse::firstOrCreate([
                            "product_id" => $lims_product_data->id,
                            "warehouse_id" => $warehouse_id,
                            "qty" => 0,
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully.',
                'data' => $lims_product_data,
                'navigate_url' => '/products',
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the product.',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => env('APP_DEBUG', false) ? $e->getTraceAsString() : null,
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to view products
            if (!$role->hasPermissionTo('products-index')) {
                return new ErrorResource('Sorry! You are not allowed to access this module.');
            }

            $product = Product::with(['category', 'brand', 'unit', 'tax'])->where('id', $id)->where('is_active', true)->first();

            if (!$product) {
                return new ErrorResource('Product not found.');
            }

            return new ProductResource($product);
        } catch (\Exception $e) {
            return new ErrorResource('An error occurred while retrieving the product.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit products
            if (!$role->hasPermissionTo('products-edit')) {
                return new ErrorResource('Sorry! You are not allowed to access this module.');
            }

            $product = Product::where('id', $id)->first();

            if (!$product) {
                return new ErrorResource('Product not found.');
            }

            $product_types = $this->_dataRetrievalService->getProductTypes();
            $barcode_symbologies = $this->_dataRetrievalService->getBarcodeSymbologies();
            $brands = $this->_dataRetrievalService->getAllBrands()->map(function ($brand) {
                return [
                    'value' => $brand->id,
                    'label' => $brand->title,
                ];
            });
            $categories = $this->_dataRetrievalService->getAllCategories()->map(function ($category) {
                return [
                    'value' => $category->id,
                    'label' => $category->name,
                ];
            });
            $units = $this->_dataRetrievalService->getAllUnits()->map(function ($unit) {
                return [
                    'value' => $unit->id,
                    'label' => ucwords($unit->unit_name) . " (" . strtoupper($unit->unit_code) . ")",
                ];
            });
            $logical_units = $this->_dataRetrievalService->getAllUnits()->map(function ($unit) {
                if ($unit->base_unit != null) {
                    return [
                        'value' => $unit->id,
                        'label' => ucwords($unit->unit_name) . " (" . strtoupper($unit->unit_code) . ")",
                        "logics" => [
                            [
                                "field" => "unit_id",
                                "values" => [$unit->base_unit],
                            ],
                        ]
                    ];
                } else {
                    return [
                        'value' => $unit->id,
                        'label' => ucwords($unit->unit_name) . " (" . strtoupper($unit->unit_code) . ")",
                    ];
                }
            })->toArray();
            $taxes = $this->_dataRetrievalService->getProductTaxes();
            $tax_methods = $this->_dataRetrievalService->getTaxMethods();
            $warranty_types = $this->_dataRetrievalService->getWarrentyType();

            // Get warehouses and existing warehouse prices for this product
            $warehouses = Warehouse::where('is_active', true)->get();
            $productWarehouses = \DB::table('product_warehouse')
                ->where('product_id', $product->id)
                ->get()
                ->keyBy('warehouse_id');

            $formSchema = [
                "title" => "Edit " . $product->name,
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/products/" . $product->id,
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "select",
                        "name" => "type",
                        "label" => "Type",
                        "enable_filter" => false,
                        "enable_search" => false,
                        "options" => $product_types,
                        "value" => $product->type,
                    ],
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Product Name",
                        "value" => $product->name,
                    ],
                    [
                        "type" => "datagenerator",
                        "name" => "code",
                        "label" => "Product Code",
                        'generator_url' => "/generate-code",
                        "value" => $product->code,
                    ],
                    [
                        "type" => "select",
                        "name" => "barcode_symbology",
                        "label" => "Barcode Symbology",
                        "enable_filter" => false,
                        "enable_search" => false,
                        "options" => $barcode_symbologies,
                        "value" => $product->barcode_symbology,
                    ],
                    [
                        "type" => "text",
                        "name" => "production_cost",
                        "label" => "Production Cost",
                        "placeholder" => "Enter production cost",
                        "keyboard_type" => "number",
                        "value" => $product->production_cost,
                        "logics" => [
                            [
                                "field" => "type",
                                "values" => ["combo"],
                            ],
                        ]
                    ],
                    [
                        "type" => "select",
                        "name" => "brand_id",
                        "label" => "Brand",
                        "enable_filter" => false,
                        "enable_search" => false,
                        "options" => $brands,
                        "new_screen" => "/brands/create",
                        "value" => $product->brand_id,
                    ],
                    [
                        "type" => "select",
                        "name" => "category_id",
                        "label" => "Category",
                        "enable_filter" => false,
                        "enable_search" => false,
                        "options" => $categories,
                        "new_screen" => "/categories/create",
                        "value" => $product->category_id,
                    ],
                    [
                        "type" => "select",
                        "name" => "unit_id",
                        "label" => "Product Unit",
                        "enable_filter" => false,
                        "enable_search" => false,
                        "options" => $units,
                        "new_screen" => "/units/create",
                        "value" => $product->unit_id,
                    ],
                    [
                        "type" => "select",
                        "name" => "sale_unit_id",
                        "label" => "Sale Unit",
                        "enable_filter" => false,
                        "enable_search" => false,
                        "options" => array_filter($logical_units, function ($item) {
                            return !is_null($item);
                        }),
                        "value" => $product->sale_unit_id,
                    ],
                    [
                        "type" => "select",
                        "name" => "purchase_unit_id",
                        "label" => "Purchase Unit",
                        "enable_filter" => false,
                        "enable_search" => false,
                        "options" => array_filter($logical_units, function ($item) {
                            return !is_null($item);
                        }),
                        "value" => $product->purchase_unit_id,
                    ],
                    [
                        "type" => "text",
                        "name" => "cost",
                        "label" => "Product Cost",
                        "keyboard_type" => "number",
                        "value" => $product->cost,
                    ],
                    [
                        "type" => "text",
                        "name" => "price",
                        "label" => "Product Price",
                        "keyboard_type" => "number",
                        "value" => $product->price,
                    ],
                    [
                        "type" => "text",
                        "name" => "wholesale_price",
                        "label" => "Wholesale Price",
                        "keyboard_type" => "number",
                        "value" => $product->wholesale_price,
                    ],
                    [
                        "type" => "text",
                        "name" => "daily_sale_objective",
                        "label" => "Daily Sale Objective",
                        "keyboard_type" => "number",
                        "info" => "Minimum qty which must be sold in a day. If not, you will be notified on dashboard. But you have to set up the cron job properly for that. Follow the documentation in that regard.",
                        "show_info_icon" => true,
                        "value" => $product->daily_sale_objective,
                    ],
                    [
                        "type" => "text",
                        "name" => "alert_quantity",
                        "label" => "Alert Quantity",
                        "keyboard_type" => "number",
                        "value" => $product->alert_quantity,
                    ],
                    [
                        "type" => "select",
                        "name" => "tax_id",
                        "label" => "Product Tax",
                        "enable_filter" => false,
                        "enable_search" => false,
                        "options" => $taxes,
                        "new_screen" => "/taxes/create",
                        "value" => $product->tax_id,
                    ],
                    [
                        "type" => "select",
                        "name" => "tax_method",
                        "label" => "Tax Method",
                        "enable_filter" => false,
                        "enable_search" => false,
                        "options" => $tax_methods,
                        "value" => $product->tax_method,
                    ],
                    [
                        "type" => "group",
                        "label" => "Warranty",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "warranty",
                                "label" => "Period",
                                "keyboard_type" => "number",
                                "value" => $product->warranty,
                            ],
                            [
                                "type" => "select",
                                "name" => "warranty_type",
                                "label" => "Type",
                                "options" => $warranty_types,
                                "value" => $product->warranty_type,
                            ],
                        ],
                    ],
                    [
                        "type" => "group",
                        "label" => "Guarantee",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "guarantee",
                                "label" => "Period",
                                "keyboard_type" => "number",
                                "value" => $product->guarantee,
                            ],
                            [
                                "type" => "select",
                                "name" => "guarantee_type",
                                "label" => "Type",
                                "options" => $warranty_types,
                                "value" => $product->guarantee_type,
                            ],
                        ],
                    ],
                    [
                        "type" => "checkbox",
                        "name" => "featured",
                        "label" => "Featured",
                        "info" => "Featured product will be displayed in POS",
                        "value" => $product->featured ? true : false,
                        "logics" => [
                            [
                                "field" => "is_imei",
                                "values" => [false, null],
                            ],
                            [
                                "field" => "is_batch",
                                "values" => [false, null],
                            ],
                        ]
                    ],
                    [
                        "type" => "checkbox",
                        "name" => "is_embeded",
                        "label" => "Embedded Barcode",
                        "info" => "Check this if this product will be used in weight scale machine.",
                        "value" => $product->is_embeded ? true : false,
                    ],
                    [
                        "type" => "checkbox",
                        "name" => "is_initial_stock",
                        "label" => "Initial Stock",
                        "info" => "This feature will not work for product with variants and batches",
                        "logics" => [
                            [
                                "field" => "is_variant",
                                "values" => [false, null],
                            ],
                            [
                                "field" => "is_batch",
                                "values" => [false, null],
                            ],
                            [
                                "field" => "is_imei",
                                "values" => [false, null],
                            ]
                        ]
                    ],
                    [
                        "type" => "file",
                        "name" => "image",
                        "label" => "Product Image(s)",
                        "allowed_extensions" => ["jpeg", "jpg", "png", "gif"],
                        "multiple" => true,
                        "info" => "You can upload multiple image. Only .jpeg, .jpg, .png, .gif file can be uploaded. First image will be base image.",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "editor",
                        "name" => "product_details",
                        "label" => "Product Details",
                        "value" => $product->product_details,
                    ],
                    [
                        "type" => "checkbox",
                        "name" => "is_variant",
                        "label" => "This product has variant",
                        "value" => $product->is_variant ? true : false,
                        "logics" => [
                            [
                                "field" => "is_batch",
                                "values" => [false, null],
                            ],
                            [
                                "field" => "type",
                                "values" => ['standard', null],
                            ],
                        ]
                    ],
                    [
                        "type" => "group",
                        "name" => "variant[]",
                        "label" => "Variant",
                        "items" => [
                            [
                                "type" => "tags",
                                "name" => "variant_option",
                                "label" => "Option",
                                "placeholder" => "Enter option and press enter (e.g., Size, Color)",
                                "info" => "Add variant options like Size, Color, Material etc. Press enter after each option.",
                                "show_info_icon" => true,
                                "value" => $product->variant_option ? json_decode($product->variant_option) : [],
                            ],
                            [
                                "type" => "tags",
                                "name" => "variant_value",
                                "label" => "Value",
                                "placeholder" => "Enter value and press enter (e.g., S, M, L)",
                                "info" => "Add variant values like S, M, L for Size or Red, Blue for Color. Press enter after each value.",
                                "show_info_icon" => true,
                                "value" => $product->variant_value ? json_decode($product->variant_value) : [],
                            ],
                        ],
                        "logics" => [
                            [
                                "field" => "is_variant",
                                "values" => [true],
                            ],
                        ]
                    ],
                    [
                        "type" => "checkbox",
                        "name" => "is_diffPrice",
                        "label" => "This product has different price for different warehouse",
                        "value" => $product->is_diffPrice ? true : false,
                    ],
                ] // Close fields array
            ]; // Close formSchema array

            // Add warehouse differential pricing groups
            foreach ($warehouses as $warehouse) {
                $warehouseData = $productWarehouses->get($warehouse->id);
                $formSchema['fields'][] = [
                    "type" => "group",
                    "name" => "warehouse_diff_price_" . $warehouse->id,
                    "label" => "Warehouse Differential Price - " . $warehouse->name,
                    "logics" => [
                        [
                            "field" => "is_diffPrice",
                            "values" => [true],
                        ],
                    ],
                    "items" => [
                        [
                            "type" => "text",
                            "name" => "warehouse_name_" . $warehouse->id,
                            "label" => "Warehouse",
                            "value" => $warehouse->name,
                            "info" => "Read only",
                            "readonly" => true,
                            "show_info_icon" => true,
                        ],
                        [
                            "type" => "text",
                            "name" => "diff_price_" . $warehouse->id,
                            "label" => "Price",
                            "placeholder" => "Enter price for " . $warehouse->name,
                            "keyboard_type" => "number",
                            "value" => $warehouseData ? $warehouseData->price : null,
                        ],
                    ],
                ];
            }

            // Add remaining fields
            $formSchema['fields'][] = [
                "type" => "checkbox",
                "name" => "is_batch",
                "label" => "This product has batch and expired date",
                "value" => $product->is_batch ? true : false,
                "logics" => [
                    [
                        "field" => "is_variant",
                        "values" => [false, null],
                    ],
                ]
            ];

            $formSchema['fields'][] = [
                "type" => "checkbox",
                "name" => "is_imei",
                "label" => "This product has IMEI or Serial Numbers",
                "value" => $product->is_imei ? true : false,
            ];

            $formSchema['fields'][] = [
                "type" => "checkbox",
                "name" => "promotion",
                "label" => "Add Promotional Price",
                "value" => $product->promotion ? true : false,
            ];

            $formSchema['fields'][] = [
                "type" => "text",
                "name" => "promotion_price",
                "label" => "Promotional Price",
                "placeholder" => "Enter promotional price",
                "keyboard_type" => "number",
                "value" => $product->promotion_price,
                "logics" => [
                    [
                        "field" => "promotion",
                        "values" => [true],
                    ],
                ]
            ];

            $formSchema['fields'][] = [
                "type" => "datepicker",
                "name" => "starting_date",
                "label" => "Promotion Starting Date",
                "placeholder" => "Select starting date",
                "format_specifier" => "dd-MM-yyyy",
                "value" => $product->starting_date,
                "logics" => [
                    [
                        "field" => "promotion",
                        "values" => [true],
                    ],
                ]
            ];

            $formSchema['fields'][] = [
                "type" => "datepicker",
                "name" => "last_date",
                "label" => "Promotion Ending Date",
                "placeholder" => "Select ending date",
                "format_specifier" => "dd-MM-yyyy",
                "value" => $product->last_date,
                "logics" => [
                    [
                        "field" => "promotion",
                        "values" => [true],
                    ],
                ]
            ];

            $formSchema['fields'][] = [
                "type" => "checkbox",
                "name" => "is_sync_disable",
                "label" => "Disable Woocommerce Sync",
                "value" => $product->is_sync_disable ? true : false,
            ];

            // Add CustomField support with values
            $custom_fields = \App\Models\CustomField::where(['belongs_to' => 'product'])->get();
            foreach ($custom_fields as $field) {
                $fieldName = str_replace(' ', '_', strtolower($field->name));
                $fieldValue = $product->$fieldName ?? $field->default_value;
                $fieldConfig = [
                    "name" => $fieldName,
                    "label" => $field->name,
                ];

                switch ($field->type) {
                    case 'text':
                        $fieldConfig['type'] = 'text';
                        $fieldConfig['placeholder'] = $field->name;
                        $fieldConfig['value'] = $fieldValue;
                        break;
                    case 'number':
                        $fieldConfig['type'] = 'text';
                        $fieldConfig['keyboard_type'] = 'number';
                        $fieldConfig['placeholder'] = $field->name;
                        $fieldConfig['value'] = $fieldValue;
                        break;
                    case 'textarea':
                        $fieldConfig['type'] = 'text';
                        $fieldConfig['multiline'] = true;
                        $fieldConfig['placeholder'] = $field->name;
                        $fieldConfig['value'] = $fieldValue;
                        break;
                    case 'checkbox':
                        $fieldConfig['type'] = 'checkbox';
                        $fieldConfig['value'] = $fieldValue ? true : false;
                        break;
                    case 'radio':
                        $fieldConfig['type'] = 'select';
                        $options = explode(',', $field->option);
                        $fieldConfig['options'] = array_map(function ($opt) {
                            return ['value' => trim($opt), 'label' => trim($opt)];
                        }, $options);
                        $fieldConfig['value'] = $fieldValue;
                        break;
                    case 'select':
                        $fieldConfig['type'] = 'select';
                        $options = explode(',', $field->option);
                        $fieldConfig['options'] = array_map(function ($opt) {
                            return ['value' => trim($opt), 'label' => trim($opt)];
                        }, $options);
                        $fieldConfig['value'] = $fieldValue;
                        break;
                    case 'multiselect':
                        $fieldConfig['type'] = 'select';
                        $fieldConfig['name'] = $fieldName . '[]';
                        $options = explode(',', $field->option);
                        $fieldConfig['options'] = array_map(function ($opt) {
                            return ['value' => trim($opt), 'label' => trim($opt)];
                        }, $options);
                        $fieldConfig['value'] = $fieldValue ? explode(',', $fieldValue) : [];
                        break;
                    case 'date':
                        $fieldConfig['type'] = 'datepicker';
                        $fieldConfig['format_specifier'] = 'dd MMMM, yyyy';
                        $fieldConfig['value'] = $fieldValue;
                        break;
                }

                $formSchema['fields'][] = $fieldConfig;
            }

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the edit form.',
                'errors' => ['An error occurred while loading the edit form.'],
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
                'error_details' => env("APP_DEBUG", false) ? $e->getMessage() : null,
                'trace' => env("APP_DEBUG", false) ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    public function update(UpdateProductRequest $request, $id)
    {
        $lims_product_data = Product::find($id);
        $data = $request->except('image', 'file', 'prev_img', 'token');
        $data['name'] = htmlspecialchars(trim($data['name']), ENT_QUOTES);

        $general_setting = DB::table('general_settings')->select('modules')->first();
        if (in_array('ecommerce', explode(',', $general_setting->modules))) {
            $data['slug'] = Str::slug($data['name'], '-');
            $data['slug'] = preg_replace('/[^A-Za-z0-9\-]/', '', $data['slug']);
            $data['slug'] = str_replace('\/', '/', $data['slug']);
            $data['related_products'] = rtrim($request->products, ",");

            if (isset($request->in_stock))
                $data['in_stock'] = $request->input('in_stock');
            else
                $data['in_stock'] = 0;

            if (isset($request->is_online))
                $data['is_online'] = $request->input('is_online');
            else
                $data['is_online'] = 0;
        }

        if (in_array('restaurant', explode(',', $general_setting->modules))) {
            $data['slug'] = Str::slug($data['name'], '-');
            $data['slug'] = preg_replace('/[^A-Za-z0-9\-]/', '', $data['slug']);
            $data['slug'] = str_replace('\/', '/', $data['slug']);
            $data['related_products'] = rtrim($request->products, ",");
            $data['extras'] = rtrim($request->extras, ",");

            if (isset($request->is_online))
                $data['is_online'] = $request->input('is_online');
            else
                $data['is_online'] = 0;

            if (isset($request->is_addon))
                $data['is_addon'] = $request->input('is_addon');
            else
                $data['is_addon'] = 0;

            $data['kitchen_id'] = $request->kitchen_id;
            $data['menu_type'] = implode(",", $request->menu_type);
        }


        if ($data['type'] == 'combo' || (isset($data['is_recipe']) && $data['is_recipe'] == 1)) {
            $data['product_list'] = implode(",", $data['product_id']);
            $data['variant_list'] = implode(",", $data['variant_id']);
            $data['qty_list'] = implode(",", $data['product_qty']);
            $data['price_list'] = implode(",", $data['unit_price']);
            if (isset($data['wastage_percent'])) {
                $data['wastage_percent'] = implode(",", $data['wastage_percent']);
            }
            if (isset($data['combo_unit_id'])) {
                $data['combo_unit_id'] = implode(",", $data['combo_unit_id']);
            }
        } elseif ($data['type'] == 'digital' || $data['type'] == 'service')
            $data['cost'] = $data['unit_id'] = $data['purchase_unit_id'] = $data['sale_unit_id'] = 0;

        if (!isset($data['featured']))
            $data['featured'] = 0;

        if (!isset($data['is_embeded']))
            $data['is_embeded'] = 0;

        if (!isset($data['promotion']))
            $data['promotion'] = null;

        if (!isset($data['is_batch']))
            $data['is_batch'] = null;

        if (!isset($data['is_imei']))
            $data['is_imei'] = null;

        if (!isset($data['is_sync_disable']) && \Schema::hasColumn('products', 'is_sync_disable'))
            $data['is_sync_disable'] = null;

        if (isset($data['short_description']))
            $data['short_description'] = $data['short_description'];
        if (isset($data['product_details']))
            $data['product_details'] = str_replace('"', '@', $data['product_details']);

        // Handle date fields - filter out placeholder values and convert valid dates
        if (isset($data['starting_date'])) {
            if ($data['starting_date'] == 'Select a Date' || empty($data['starting_date'])) {
                $data['starting_date'] = null;
            } else {
                $data['starting_date'] = date('Y-m-d', strtotime($data['starting_date']));
            }
        }
        if (isset($data['last_date'])) {
            if ($data['last_date'] == 'Select a Date' || empty($data['last_date'])) {
                $data['last_date'] = null;
            } else {
                $data['last_date'] = date('Y-m-d', strtotime($data['last_date']));
            }
        }

        $previous_images = [];
        //dealing with previous images
        if ($request->prev_img) {
            foreach ($request->prev_img as $key => $prev_img) {
                if (!in_array($prev_img, $previous_images))
                    $previous_images[] = $prev_img;
            }
            $lims_product_data->image = implode(",", $previous_images);
            $lims_product_data->save();
        } else {
            $lims_product_data->image = null;
            $lims_product_data->save();
        }

        //dealing with new images
        if ($request->image) {
            // Ensure the necessary directories exist using public_path()
            $this->diffSizeOfImagePathExistOrCreate();

            $images = $request->image;
            $image_names = [];
            $length = count(explode(",", $lims_product_data->image));

            foreach ($images as $key => $image) {
                $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);

                if (!config('database.connections.saleprosaas_landlord')) {
                    $imageName = date("Ymdhis") . ($length + $key + 1) . '.' . $ext;
                } else {
                    $imageName = $this->getTenantId() . '_' . date("Ymdhis") . ($length + $key + 1) . '.' . $ext;
                }

                $image->move(public_path('images/product'), $imageName);

                $manager = new ImageManager(new GdDriver());
                $image = $manager->read(public_path('images/product/' . $imageName));

                $image->resize(1000, 1250)->save(public_path('images/product/xlarge/' . $imageName));
                $image->resize(500, 500)->save(public_path('images/product/large/' . $imageName));
                $image->resize(250, 250)->save(public_path('images/product/medium/' . $imageName));
                $image->resize(100, 100)->save(public_path('images/product/small/' . $imageName));

                $image_names[] = $imageName;
            }

            // Append or set the image field with the new image names
            if ($lims_product_data->image)
                $data['image'] = $lims_product_data->image . ',' . implode(",", $image_names);
            else
                $data['image'] = implode(",", $image_names);
        } else
            $data['image'] = $lims_product_data->image;

        $file = $request->file;
        if ($file) {
            $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            $fileName = strtotime(date('Y-m-d H:i:s'));
            if (!config('database.connections.saleprosaas_landlord')) {
                $fileName = $fileName . '.' . $ext;
            } else {
                $fileName = $this->getTenantId() . '_' . $fileName . '.' . $ext;
            }
            $file->move(public_path('product/files'), $fileName);
            $data['file'] = $fileName;
        }

        $old_product_variant_ids = ProductVariant::where('product_id', $request->input('id'))->pluck('id')->toArray();
        $new_product_variant_ids = [];
        //dealing with product variant
        if (isset($data['is_variant']) && isset($data['variant_name']) && is_array($data['variant_name'])) {
            if (isset($data['variant_option']) && isset($data['variant_value'])) {
                // Ensure variant_option and variant_value are arrays
                $variant_option = is_array($data['variant_option']) ? $data['variant_option'] : [];
                $variant_value = is_array($data['variant_value']) ? $data['variant_value'] : [];

                $data['variant_option'] = json_encode(array_unique($variant_option));
                $data['variant_value'] = json_encode(array_unique($variant_value));
            }
            foreach ($data['variant_name'] as $key => $variant_name) {
                $lims_variant_data = Variant::firstOrCreate(['name' => $data['variant_name'][$key]]);
                $lims_product_variant_data = ProductVariant::where([
                    ['product_id', $lims_product_data->id],
                    ['variant_id', $lims_variant_data->id]
                ])->first();
                if ($lims_product_variant_data) {
                    $lims_product_variant_data->update([
                        'position' => $key + 1,
                        'item_code' => $data['item_code'][$key],
                        'additional_cost' => $data['additional_cost'][$key],
                        'additional_price' => $data['additional_price'][$key]
                    ]);
                } else {
                    $lims_product_variant_data = ProductVariant::firstOrNew([
                        'product_id' => $lims_product_data->id,
                        'variant_id' => $lims_variant_data->id,
                        'item_code' => $data['item_code'][$key],
                        'additional_cost' => $data['additional_cost'][$key],
                        'additional_price' => $data['additional_price'][$key],
                        'qty' => 0,
                    ]);
                    $lims_product_variant_data->position = $key + 1;
                    $lims_product_variant_data->save();
                }
                $new_product_variant_ids[] = $lims_product_variant_data->id;
            }
        } else {
            $data['is_variant'] = null;
            $data['variant_option'] = null;
            $data['variant_value'] = null;
        }
        //deleting old product variant if not exist
        foreach ($old_product_variant_ids as $key => $product_variant_id) {
            if (!in_array($product_variant_id, $new_product_variant_ids))
                ProductVariant::find($product_variant_id)->delete();
        }

        // Parse warehouse-specific price fields (diff_price_1, diff_price_2, etc.) into arrays
        $warehouse_ids = [];
        $diff_prices = [];
        foreach ($request->all() as $key => $value) {
            if (strpos($key, 'diff_price_') === 0 && strpos($key, 'warehouse_name_') === false) {
                $warehouse_id = str_replace('diff_price_', '', $key);
                if (is_numeric($warehouse_id) && $value > 0) {
                    $warehouse_ids[] = $warehouse_id;
                    $diff_prices[] = $value;
                }
            }
        }
        if (count($warehouse_ids) > 0) {
            $data['warehouse_id'] = $warehouse_ids;
            $data['diff_price'] = $diff_prices;
        }

        if (isset($data['is_diffPrice']) && isset($data['diff_price'])) {
            foreach ($data['diff_price'] as $key => $diff_price) {
                if ($diff_price) {
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($lims_product_data->id, $data['warehouse_id'][$key])->first();
                    if ($lims_product_warehouse_data) {
                        $lims_product_warehouse_data->price = $diff_price;
                        $lims_product_warehouse_data->save();
                    } else {
                        Product_Warehouse::firstOrCreate([
                            "product_id" => $lims_product_data->id,
                            "warehouse_id" => $data["warehouse_id"][$key],
                            "qty" => 0,
                            "price" => $diff_price
                        ]);
                    }
                }
            }
        } else {
            $data['is_diffPrice'] = false;
            if (isset($data['warehouse_id'])) {
                foreach ($data['warehouse_id'] as $key => $warehouse_id) {
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($lims_product_data->id, $warehouse_id)->first();
                    if ($lims_product_warehouse_data) {
                        $lims_product_warehouse_data->price = null;
                        $lims_product_warehouse_data->save();
                    }
                }
            }
        }
        // handle warranty and guarantee
        if (!isset($data['warranty'])) {
            $data['warranty'] = null;
            $data['warranty_type'] = null;
        }
        if (!isset($data['guarantee'])) {
            $data['guarantee'] = null;
            $data['guarantee_type'] = null;
        }
        $lims_product_data->update($data);
        //inserting data for custom fields
        $custom_field_data = [];
        $custom_fields = CustomField::where('belongs_to', 'product')->select('name', 'type')->get();
        foreach ($custom_fields as $type => $custom_field) {
            $field_name = str_replace(' ', '_', strtolower($custom_field->name));
            if (isset($data[$field_name])) {
                if ($custom_field->type == 'checkbox' || $custom_field->type == 'multi_select')
                    $custom_field_data[$field_name] = implode(",", $data[$field_name]);
                else
                    $custom_field_data[$field_name] = $data[$field_name];
            }
        }
        if (count($custom_field_data))
            DB::table('products')->where('id', $lims_product_data->id)->update($custom_field_data);
        $this->cacheForget('product_list');
        $this->cacheForget('product_list_with_variant');
        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'navigate_url' => '/products',
        ], 200);
    }

    public function import(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create products
            if (!$role->hasPermissionTo('products-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to import products.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            // Handle POST request - CSV processing
            if ($request->isMethod('post')) {
                $request->validate([
                    'file' => 'required|file|mimes:csv,txt',
                ]);

                $upload = $request->file('file');
                $filePath = $upload->getRealPath();

                // Open and read file
                $file = fopen($filePath, 'r');
                $header = fgetcsv($file);
                if (!$header) {
                    fclose($file);
                    return response()->json([
                        'success' => false,
                        'message' => 'CSV file is empty or invalid',
                        'debug_bar' => env("APP_DEBUG", false) ? true : false,
                    ], 422);
                }

                $escapedHeader = [];
                foreach ($header as $key => $value) {
                    $lheader = strtolower(trim($value));
                    $escapedItem = preg_replace('/[^a-z]/', '', $lheader);
                    $escapedHeader[] = $escapedItem;
                }

                DB::beginTransaction();
                $counter = 1;
                $importedCount = 0;

                try {
                    while ($columns = fgetcsv($file)) {
                        if (count($escapedHeader) !== count($columns)) {
                            fclose($file);
                            throw new \Exception('CSV file format is incorrect at row ' . $counter);
                        }

                        $data = array_combine($escapedHeader, $columns);

                        // Validate and sanitize input
                        $data['name'] = htmlspecialchars(trim($data['name']));
                        $data['cost'] = is_numeric($data['cost']) ? str_replace(",", "", $data['cost']) : 0;

                        // Default margin from general settings
                        $general_setting = GeneralSetting::first();
                        $defaultMargin = $general_setting->default_margin_value ?? 25;

                        if (!empty($data['profitmargin']) && !empty($data['price'])) {
                            $data['price'] = is_numeric($data['price']) ? str_replace(",", "", $data['price']) : 0;
                            $data['profitmargin'] = ($data['cost'] > 0) ? (($data['price'] - $data['cost']) / $data['cost']) * 100 : $defaultMargin;
                        } else if (!empty($data['profitmargin'])) {
                            $profitMargin = (float) $data['profitmargin'];
                            $data['price'] = $data['cost'] * (1 + $profitMargin / 100);
                        } else if (!empty($data['price'])) {
                            $data['price'] = is_numeric($data['price']) ? str_replace(",", "", $data['price']) : 0;
                            $data['profitmargin'] = ($data['cost'] > 0) ? (($data['price'] - $data['cost']) / $data['cost']) * 100 : $defaultMargin;
                        } else {
                            $data['profitmargin'] = $defaultMargin;
                            $data['price'] = $data['cost'] * (1 + $defaultMargin / 100);
                        }

                        // Handle brand
                        $brand_id = null;
                        if (isset($data['brand']) && $data['brand'] !== 'N/A' && $data['brand'] !== '') {
                            $lims_brand_data = Brand::firstOrCreate(['title' => $data['brand'], 'is_active' => true]);
                            $brand_id = $lims_brand_data->id;
                        }

                        // Handle category
                        $lims_category_data = Category::firstOrCreate(['name' => $data['category'], 'is_active' => true]);

                        // Handle unit
                        $lims_unit_data = Unit::where('unit_code', $data['unitcode'])->first();
                        if (!$lims_unit_data) {
                            fclose($file);
                            throw new \Exception('Unit code does not exist in the database at row ' . $counter);
                        }

                        // Create or update product
                        $product = Product::firstOrNew([
                            'name' => $data['name'],
                            'is_active' => true
                        ]);

                        $product->fill([
                            'code' => $data['code'],
                            'type' => strtolower($data['type'] ?? 'standard'),
                            'barcode_symbology' => 'C128',
                            'brand_id' => $brand_id,
                            'category_id' => $lims_category_data->id,
                            'unit_id' => $lims_unit_data->id,
                            'purchase_unit_id' => $lims_unit_data->id,
                            'sale_unit_id' => $lims_unit_data->id,
                            'cost' => $data['cost'],
                            'profit_margin' => $data['profitmargin'],
                            'price' => $data['price'],
                            'tax_method' => 1,
                            'qty' => 0,
                            'product_details' => $data['productdetails'] ?? '',
                            'is_active' => true,
                            'image' => 'zummXD2dvAtI.png',
                        ]);

                        // Handle ecommerce slug
                        if (in_array('ecommerce', explode(',', config('addons')))) {
                            $data['slug'] = \Illuminate\Support\Str::slug($data['name'], '-');
                            $product->slug = preg_replace('/[^A-Za-z0-9\-]/', '', $data['slug']);
                            $product->in_stock = true;
                        }

                        // Handle image download if URL provided
                        $image_names = [];
                        if (!empty($data['image']) && $data['image'] != 'zummXD2dvAtI.png') {
                            $imageUrls = explode(',', $data['image']);
                            $this->diffSizeOfImagePathExistOrCreate();

                            foreach ($imageUrls as $url) {
                                $url = trim($url);
                                if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                                    continue;
                                }

                                try {
                                    $response = \Illuminate\Support\Facades\Http::get($url);
                                    if (!$response->successful()) {
                                        continue;
                                    }

                                    $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                                    $imageName = date("Ymdhis") . uniqid() . '.' . $ext;

                                    $manager = new \Intervention\Image\ImageManager();
                                    $image = $manager->make($response->body());

                                    $image->save(public_path('images/product/') . $imageName);
                                    $image->fit(1000, 1250)->save(public_path('images/product/xlarge/') . $imageName, 100);
                                    $image->fit(500, 500)->save(public_path('images/product/large/') . $imageName, 100);
                                    $image->fit(250, 250)->save(public_path('images/product/medium/') . $imageName, 100);
                                    $image->fit(100, 100)->save(public_path('images/product/small/') . $imageName, 100);

                                    $image_names[] = $imageName;
                                } catch (\Exception $e) {
                                    // Log error but continue
                                }
                            }

                            if (!empty($image_names)) {
                                $product->image = implode(",", $image_names);
                            }
                        }

                        $product->save();

                        // Handle variants
                        $warehouse_ids = Warehouse::where('is_active', true)->pluck('id');
                        if (!empty($data['variantvalue']) && !empty($data['variantname'])) {
                            $variant_option = [];
                            $variant_value = [];
                            $variantInfo = explode(",", $data['variantvalue']);

                            foreach ($variantInfo as $key => $info) {
                                if (!strpos($info, "[")) {
                                    fclose($file);
                                    throw new \Exception('Invalid variant value format at row ' . $counter);
                                }
                                $variant_option[] = strtok($info, "[");
                                $variant_value[] = str_replace("/", ",", substr($info, strpos($info, "[") + 1, (strpos($info, "]") - strpos($info, "[") - 1)));
                            }

                            $product->variant_option = json_encode($variant_option);
                            $product->variant_value = json_encode($variant_value);
                            $product->is_variant = true;
                            $product->save();

                            $variant_names = explode(",", $data['variantname']);
                            $item_codes = explode(",", $data['itemcode'] ?? '');
                            $additional_costs = explode(",", $data['additionalcost'] ?? '');
                            $additional_prices = explode(",", $data['additionalprice'] ?? '');

                            $productVariants = [];
                            $productWarehouses = [];

                            foreach ($variant_names as $key => $variant_name) {
                                $variant = Variant::firstOrCreate(['name' => $variant_name]);

                                $productVariants[] = [
                                    'product_id' => $product->id,
                                    'variant_id' => $variant->id,
                                    'position' => $key + 1,
                                    'item_code' => $item_codes[$key] ?? $variant_name . '-' . $data['code'],
                                    'additional_cost' => $additional_costs[$key] ?? 0,
                                    'additional_price' => $additional_prices[$key] ?? 0,
                                    'qty' => 0,
                                ];

                                foreach ($warehouse_ids as $warehouse_id) {
                                    $productWarehouses[] = [
                                        'product_id' => $product->id,
                                        'variant_id' => $variant->id,
                                        'warehouse_id' => $warehouse_id,
                                        'qty' => 0,
                                    ];
                                }
                            }

                            ProductVariant::insert($productVariants);
                            if (config('without_stock') === 'yes') {
                                Product_Warehouse::insert($productWarehouses);
                            }
                        } elseif (config('without_stock') === 'yes') {
                            $productWarehouses = [];
                            foreach ($warehouse_ids as $warehouse_id) {
                                $productWarehouses[] = [
                                    'product_id' => $product->id,
                                    'warehouse_id' => $warehouse_id,
                                    'qty' => 0,
                                ];
                            }
                            Product_Warehouse::insert($productWarehouses);
                        }

                        $importedCount++;
                        $counter++;
                    }

                    fclose($file);
                    $this->cacheForget('product_list');
                    $this->cacheForget('product_list_with_variant');
                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => "Successfully imported {$importedCount} products.",
                        'navigate_url' => '/products',
                        'debug_bar' => env("APP_DEBUG", false) ? true : false,
                    ], 200);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Error in row {$counter}: " . $e->getMessage(),
                        'debug_bar' => env("APP_DEBUG", false) ? true : false,
                    ], 422);
                }
            }

            // Return import form schema for GET request
            $formSchema = [
                "title" => "Import Products",
                "submit_url" => "/products/import",
                "navigate_url" => "/products",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "helpertext",
                        "text" => "The correct column order is (name, code, category, cost, price, unit, product_details, qty, alert_qty) and you must follow this.",
                    ],
                    [
                        "type" => "importdata",
                        "name" => "file",
                        "hint_text" => "Upload CSV File",
                        "file_link" => url('sample_file/sample_product.csv'),
                        "sample_file_name" => "sample_product.csv",
                        "download_title" => "Download Sample File",
                    ],
                ],
            ];

            return response()->json($this->withDashBackground($formSchema, 'app'), 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the import.',
                'error' => $e->getMessage(),
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    public function destroy(Product $product)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to delete products
            if (!$role->hasPermissionTo('products-delete')) {
                return new ErrorResource('Sorry! You are not allowed to access this module.');
            }

            $product->is_active = false;
            if ($product->image != 'zummXD2dvAtI.png') {
                $images = explode(",", $product->image);
                foreach ($images as $key => $image) {
                    $this->fileDelete(public_path('images/product/'), $image);
                    $this->fileDelete(public_path('images/product/large/'), $image);
                    $this->fileDelete(public_path('images/product/medium/'), $image);
                    $this->fileDelete(public_path('images/product/small/'), $image);
                }
            }
            $product->save();
            $this->cacheForget('product_list');
            $this->cacheForget('product_list_with_variant');

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource('An error occurred while deleting the product.');
        }
    }

    public function generateCode()
    {
        $id = Keygen::numeric(8)->generate();
        return $id;
    }

    /**
     * Search products for table generator (transfer, sale, purchase forms)
     */
    public function searchProducts(Request $request, $query = '')
    {
        try {
            $user = Auth::user();
            $warehouse_id = $request->input('warehouse_id');
            $search = $query ?: $request->input('query', '');

            if (empty($search)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ], 200);
            }

            // Search products by name or code
            $products = Product::where('is_active', true)
                ->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('code', 'LIKE', "%{$search}%");
                })
                ->with(['tax', 'unit', 'brand', 'category'])
                ->limit(20)
                ->get();

            $results = $products->map(function ($product) use ($warehouse_id) {
                // Get warehouse-specific data if warehouse_id provided
                $warehouse_qty = 0;
                $warehouse_price = $product->price;

                if ($warehouse_id) {
                    $productWarehouse = Product_Warehouse::where('product_id', $product->id)
                        ->where('warehouse_id', $warehouse_id)
                        ->first();

                    if ($productWarehouse) {
                        $warehouse_qty = $productWarehouse->qty;
                        if ($productWarehouse->price) {
                            $warehouse_price = $productWarehouse->price;
                        }
                    }
                }

                // Get units data
                $units = [];
                if ($product->type == 'standard' && $product->unit_id) {
                    $baseUnit = Unit::find($product->unit_id);
                    $relatedUnits = Unit::where('base_unit', $product->unit_id)
                        ->orWhere('id', $product->unit_id)
                        ->get();

                    foreach ($relatedUnits as $unit) {
                        $units[] = [
                            'id' => $unit->id,
                            'name' => $unit->unit_name,
                            'code' => $unit->unit_code,
                            'operator' => $unit->operator,
                            'operation_value' => $unit->operation_value,
                        ];
                    }
                }

                // Check if product has batch
                $batch_enabled = $product->is_batch ? true : false;

                // Check if product has IMEI
                $imei_enabled = $product->is_imei ? true : false;

                // Check if product has variants
                $has_variant = $product->is_variant ? true : false;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'type' => $product->type,
                    'cost' => (float) $product->cost,
                    'price' => (float) $warehouse_price,
                    'qty' => (float) ($warehouse_id ? $warehouse_qty : $product->qty),
                    'alert_quantity' => (float) $product->alert_quantity,
                    'tax_id' => $product->tax_id,
                    'tax_rate' => $product->tax ? (float) $product->tax->rate : 0,
                    'tax_name' => $product->tax ? $product->tax->name : null,
                    'tax_method' => $product->tax_method,
                    'unit_id' => $product->unit_id,
                    'units' => $units,
                    'batch_enabled' => $batch_enabled,
                    'imei_enabled' => $imei_enabled,
                    'has_variant' => $has_variant,
                    'image' => $product->image ? url('images/product', $product->image) : url('images/zummXD2dvAtI.png'),
                    'label' => $product->name . ' [' . $product->code . ']',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $results,
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searching products.',
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
                'error_details' => env("APP_DEBUG", false) ? $e->getMessage() : null,
                'trace' => env("APP_DEBUG", false) ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Get form for printing product barcodes
     */
    public function printBarcodeForm(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('print_barcode')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            // Get barcode settings
            $barcodeSettings = Barcode::select(DB::raw('CONCAT(name, ", ", COALESCE(description, "")) as display_name'), 'id', 'name', 'description', 'is_default')
                ->get()
                ->map(function ($barcode) {
                    return [
                        'value' => $barcode->id,
                        'label' => $barcode->display_name,
                    ];
                })->toArray();

            $defaultBarcode = Barcode::where('is_default', 1)->first();

            // Get paper sizes
            $paperSizes = [
                ['value' => 'a4', 'label' => 'A4 (210 x 297 mm)'],
                ['value' => 'letter', 'label' => 'Letter (8.5 x 11 in)'],
                ['value' => 'label', 'label' => 'Label (4 x 1 in)'],
                ['value' => 'label_small', 'label' => 'Small Label (2 x 1 in)'],
            ];

            $formSchema = [
                'title' => 'Print Product Barcode',
                'submit_url' => '/products/print-barcode',
                'navigate_url' => '/products',
                'method' => 'POST',
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
                'fields' => [
                    [
                        'type' => 'group',
                        'label' => 'Barcode Settings',
                        'items' => [
                            [
                                'type' => 'select',
                                'name' => 'barcode_id',
                                'label' => 'Barcode Format *',
                                'placeholder' => 'Select barcode format',
                                'options' => $barcodeSettings,
                                'required' => true,
                                'value' => $defaultBarcode ? $defaultBarcode->id : null,
                            ],
                            [
                                'type' => 'select',
                                'name' => 'paper_size',
                                'label' => 'Paper Size *',
                                'placeholder' => 'Select paper size',
                                'options' => $paperSizes,
                                'required' => true,
                                'value' => 'a4',
                            ],
                            [
                                'type' => 'text',
                                'name' => 'copies',
                                'label' => 'Number of Copies *',
                                'placeholder' => 'Enter number of copies',
                                'keyboard_type' => 'number',
                                'required' => true,
                                'value' => '1',
                            ],
                        ]
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Additional Options',
                        'items' => [
                            [
                                'type' => 'checkbox',
                                'name' => 'show_price',
                                'label' => 'Show Price on Barcode',
                                'value' => true,
                            ],
                            [
                                'type' => 'checkbox',
                                'name' => 'show_product_name',
                                'label' => 'Show Product Name on Barcode',
                                'value' => true,
                            ],
                            [
                                'type' => 'checkbox',
                                'name' => 'show_business_name',
                                'label' => 'Show Business Name on Barcode',
                                'value' => false,
                            ],
                        ]
                    ],
                ],
            ];

            return response()->json($this->withDashBackground($formSchema, 'app'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the barcode print form.',
                'error' => $e->getMessage(),
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }
}
