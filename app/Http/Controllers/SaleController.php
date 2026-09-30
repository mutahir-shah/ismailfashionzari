<?php

namespace App\Http\Controllers;

use App\Enums\CustomerTypeEnum;
use App\Http\Requests\Sale\StoreSaleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Warehouse;
use App\Models\Biller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Tax;
use App\Models\Sale;
use App\Models\Delivery;
use App\Models\PosSetting;
use App\Models\Product_Sale;
use App\Models\Product_Warehouse;
use App\Models\Payment;
use App\Models\Account;
use App\Models\Coupon;
use App\Models\GiftCard;
use App\Models\PaymentWithCheque;
use App\Models\PaymentWithGiftCard;
use App\Models\PaymentWithCreditCard;
use App\Models\PaymentWithPaypal;
use App\Models\Variant;
use App\Models\ProductVariant;
use App\Models\CashRegister;
use App\Models\Returns;
use App\Models\ProductReturn;
use App\Models\Expense;
use App\Models\ProductPurchase;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\RewardPointSetting;
use App\Models\CustomField;
use App\Models\Table;
use App\Models\Courier;
use App\Models\ExternalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Cache;
use App\Models\MailSetting;
use Stripe\Stripe;
use NumberToWords\NumberToWords;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use App\Mail\SaleDetails;
use App\Mail\PaymentDetails;
use Mail;
use Srmklive\PayPal\Services\ExpressCheckout;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\Currency;
use App\Models\InvoiceSetting;
use App\Models\PackingSlip;
use App\Models\RewardPoint;
use App\Models\SmsTemplate;
use App\ViewModels\ISmsModel;
use DateTime;
use Carbon\Carbon;
use App\Models\Printer;
use App\Services\PrinterService;
use App\Services\InvoiceService;
use App\Models\WhatsappSetting;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\ExpenseCategory;
use App\Models\User;

class SaleController extends Controller
{
    use \App\Traits\TenantInfo;
    use \App\Traits\MailInfo;

    public function __construct(
        private ISmsModel $_smsModel,
        private InvoiceService $invoiceService,
        private \App\Services\Domain\SaleDomainService $saleDomainService,
    ) {}


    private function restaurantModifiersAvailable(): bool
    {
        return $this->restaurantModuleAvailable()
            && Schema::hasTable('product_sale_modifiers');
    }

    private function restaurantModuleAvailable(): bool
    {
        return is_dir(base_path('Modules/Restaurant'))
            && class_exists(\Modules\Restaurant\Entities\ProductSaleModifier::class);
    }

    /**
     * Canonical restaurant-workflow check for every Sale entry point.
     *
     * A dedicated restaurant warehouse always uses the restaurant workflow.
     * Hybrid (both) warehouses are resolved by resolvePosWorkflow().
     */
    private function isRestaurantWarehouse(?Warehouse $warehouse): bool
    {
        return $warehouse !== null
            && $this->restaurantModuleAvailable()
            && strtolower((string) $warehouse->pos_type) === 'restaurant';
    }

    /**
     * Add a non-persisted computed flag to warehouse models so Blade/JS uses the
     * exact same restaurant decision as the controller.
     */
    private function addRestaurantWorkflowFlag($warehouses)
    {
        $warehouses->each(function (Warehouse $warehouse) {
            $warehouse->setAttribute(
                'restaurant_workflow',
                $this->isRestaurantWarehouse($warehouse)
            );
        });

        return $warehouses;
    }

    /**
     * POS workflow is warehouse-owned. The optional requested workflow argument
     * is retained for backward compatibility with existing callers.
     */
    public function resolvePosWorkflow(Warehouse $warehouse, ?string $requestedWorkflow = null): string
    {
        if (!$this->restaurantModuleAvailable()) {
            return 'retail';
        }

        $posType = strtolower(trim((string) ($warehouse->pos_type ?? 'regular')));
        if ($posType === 'restaurant') {
            return 'restaurant';
        }

        if ($posType === 'both' && in_array($requestedWorkflow, ['retail', 'restaurant'], true)) {
            return $requestedWorkflow;
        }

        return 'retail';
    }

    private function prepareWarehouseSaleData(array $data, ?int $currentSaleId = null): array
    {
        $warehouseValidator = Validator::make($data, [
            'warehouse_id' => [
                'required',
                'integer',
                \Illuminate\Validation\Rule::exists('warehouses', 'id')
                    ->where(fn ($query) => $query->where('is_active', 1)),
            ],
        ]);

        if ($warehouseValidator->fails()) {
            throw new ValidationException($warehouseValidator);
        }

        $warehouse = Warehouse::findOrFail((int) $data['warehouse_id']);
        $requestedWorkflow = $data['pos_workflow'] ?? ($data['workflow'] ?? request()->input('workflow'));
        $activeWorkflow = $this->resolvePosWorkflow($warehouse, $requestedWorkflow);
        $isRestaurantWorkflow = ($activeWorkflow === 'restaurant');
        $data['pos_workflow'] = $activeWorkflow;
        $isDraft = (int) ($data['sale_status'] ?? 1) === 3;
        $isPos = !empty($data['pos']);

        if ($isPos && !$isDraft) {
            // Status is server-owned for POS: restaurant=Processing, normal=Completed.
            $data['sale_status'] = $isRestaurantWorkflow ? 5 : 1;
        }

        if (!$isRestaurantWorkflow) {
            unset(
                $data['service_id'],
                $data['table_id'],
                $data['waiter_id'],
                $data['topping_product'],
                $data['topping-price'],
                $data['service_charge']
            );
            return $data;
        }

        // Drafts may intentionally be incomplete.
        if ($isDraft) {
            return $data;
        }

        $restaurantRules = [
            'service_id' => [
                'required',
                'integer',
                \Illuminate\Validation\Rule::exists('services', 'id')
                    ->where(fn ($query) => $query->where('is_active', 1)),
            ],
        ];

        if ((int) ($data['service_id'] ?? 0) === 1) {
            $warehouseId = (int) $warehouse->id;

            $restaurantRules['table_id'] = [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($warehouseId, $currentSaleId) {
                    if (class_exists(\Modules\Restaurant\Services\TableOccupancyService::class)) {
                        $error = \Modules\Restaurant\Services\TableOccupancyService::validateTableAvailability((int) $value, $warehouseId, $currentSaleId);
                        if ($error) {
                            $fail($error);
                            return;
                        }
                    } else {
                        $validTable = Table::join('floors', 'tables.floor_id', '=', 'floors.id')
                            ->where('tables.id', (int) $value)
                            ->where('tables.is_active', 1)
                            ->where('floors.warehouse_id', $warehouseId)
                            ->exists();

                        if (!$validTable) {
                            $fail(__('validation.exists', ['attribute' => __('db.Table')]));
                        }
                    }
                },
            ];

            $restaurantRules['waiter_id'] = [
                'required',
                'integer',
                \Illuminate\Validation\Rule::exists('users', 'id')
                    ->where(fn ($query) => $query
                        ->where('service_staff', 1)
                        ->where('is_active', 1)),
            ];
        } else {
            // Take Away / Delivery cannot retain stale Dine In values.
            unset($data['table_id'], $data['waiter_id']);
        }

        $restaurantValidator = Validator::make($data, $restaurantRules);
        if ($restaurantValidator->fails()) {
            throw new ValidationException($restaurantValidator);
        }

        return $data;
    }

    public function index(Request $request)
    {

        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('sales-index')) {
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if (empty($all_permission))
                $all_permission[] = 'dummy text';

            if ($request->input('warehouse_id'))
                $warehouse_id = $request->input('warehouse_id');
            else
                $warehouse_id = 0;

            if ($request->input('sale_status'))
                $sale_status = $request->input('sale_status');
            else
                $sale_status = 0;

            if ($request->input('payment_status'))
                $payment_status = $request->input('payment_status');
            else
                $payment_status = 0;

            if ($request->input('sale_type'))
                $sale_type = $request->input('sale_type');
            else
                $sale_type = 0;

            if ($request->input('payment_method'))
                $payment_method = $request->input('payment_method');
            else
                $payment_method = 0;

            if ($request->input('starting_date')) {
                $starting_date = $request->input('starting_date');
                $ending_date = $request->input('ending_date');
            } else {
                $starting_date = date("Y-m-d", strtotime(date('Y-m-d', strtotime('-1 year', strtotime(date('Y-m-d'))))));
                $ending_date = date("Y-m-d");
            }

            $lims_gift_card_list = GiftCard::where("is_active", true)->get();
            $lims_pos_setting_data = PosSetting::latest()->first();
            $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_account_list = app(\App\Services\PaymentAccountService::class)->validOperationalAccounts();
            $lims_courier_list = Courier::where('is_active', true)->get();
            if ($lims_pos_setting_data)
                $options = explode(',', $lims_pos_setting_data->payment_options);
            else
                $options = [];
            $numberOfInvoice = Sale::whereNull('deleted_at')
                ->where(function ($q) {
                    $q->where('sale_type', '!=', 'opening balance')
                        ->orWhereNull('sale_type');
                })->count();
            $custom_fields = CustomField::where([
                ['belongs_to', 'sale'],
                ['is_table', true]
            ])->pluck('name');
            $field_name = [];
            foreach ($custom_fields as $fieldName) {
                $field_name[] = str_replace(" ", "_", strtolower($fieldName));
            }
            $smsTemplates = SmsTemplate::all();
            $currency_list = cache()->get('currency_list');

            return view('backend.sale.index', compact('starting_date', 'ending_date', 'warehouse_id', 'sale_status', 'payment_status', 'sale_type', 'payment_method', 'lims_gift_card_list', 'lims_pos_setting_data', 'lims_reward_point_setting_data', 'lims_account_list', 'lims_warehouse_list', 'all_permission', 'options', 'numberOfInvoice', 'custom_fields', 'field_name', 'lims_courier_list', 'smsTemplates', 'currency_list'));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function saleData(Request $request)
    {
        // 1. Column mapping for DataTables
        $columns = array(
            2 => 'created_at',
            3 => 'reference_no',
            5 => 'customer_id',
            6 => 'warehouse_id',
            7 => 'sale_status',
            8 => 'payment_status',
            12 => 'total_tax',
            13 => 'order_tax',
            14 => 'grand_total',
            16 => 'paid_amount',
            17 => 'due',
        );

        if (!empty(gen_setting()->show_products_details_in_sales_table)) {
            $columns = array(
                2 => 'created_at',
                3 => 'reference_no',
                5 => 'customer_id',
                6 => 'warehouse_id',
                7 => 'product_sales.product_id',
                8 => 'product_sales.qty',
                9 => 'sale_status',
                10 => 'payment_status',
                14 => 'total_tax',
                15 => 'order_tax',
                16 => 'grand_total',
                18 => 'paid_amount',
                19 => 'due',
            );
        }

        // Get input parameters
        // $installment_filter = $request->input('installment_filter');
        $installment = $request->input('installment');
        $warehouse_id = $request->input('warehouse_id');
        $sale_status = $request->input('sale_status');
        $payment_status = $request->input('payment_status');
        $sale_type = $request->input('sale_type');
        $payment_method = $request->input('payment_method');
        $start = $request->input('start');
        $limit = $request->input('length') != -1 ? $request->input('length') : null;
        $orderColumnIndex = $request->input('order.0.column');
        // Default to 'created_at' if index is missing or outside array bounds
        $orderColumn = $columns[$orderColumnIndex] ?? 'created_at';
        $dir = $request->input('order.0.dir');

        // Fetch custom fields data
        $custom_fields = CustomField::where([
            ['belongs_to', 'sale'],
            ['is_table', true]
        ])->pluck('name');
        $field_names = [];
        foreach ($custom_fields as $fieldName) {
            $field_names[] = str_replace(" ", "_", strtolower($fieldName));
        }

        // --- 2. BASE QUERY FOR TOTAL COUNT AND INITIAL FILTERING ---
        // Start with the basic sales query (no payments join yet)
        $qBase = Sale::whereNull('sales.deleted_at')
            ->where(function ($q) {
                $q->where('sales.sale_type', '!=', 'opening balance')
                    ->orWhereNull('sales.sale_type');
            })
            ->whereDate('sales.created_at', '>=', $request->input('starting_date'))
            ->whereDate('sales.created_at', '<=', $request->input('ending_date'));

        // Apply Access Control
        if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
            $qBase = $qBase->where('sales.user_id', Auth::id());
        } elseif (Auth::user()->role_id > 2 && config('staff_access') == 'warehouse') {
            $qBase = $qBase->where('sales.warehouse_id', Auth::user()->warehouse_id);
        }

        // Apply Filters to the base query
        if ($warehouse_id)
            $qBase = $qBase->where('sales.warehouse_id', $warehouse_id);
        if ($sale_status)
            $qBase = $qBase->where('sales.sale_status', $sale_status);
        if ($payment_status)
            $qBase = $qBase->where('sales.payment_status', $payment_status);
        if ($sale_type)
            $qBase = $qBase->where('sales.sale_type', $sale_type);
        if ($request->input('social_channel') && $request->input('social_channel') !== '0')
            $qBase = $qBase->where('sales.social_channel', $request->input('social_channel'));

        if ($installment == 1) {
            $qBase = $qBase->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('installment_plans')
                    ->where('reference_type', 'sale')
                    ->whereColumn('reference_id', 'sales.id');
            });
        }

        if ($installment == 2) {
            $qBase = $qBase->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('installment_plans')
                    ->where('reference_type', 'sale')
                    ->whereColumn('reference_id', 'sales.id');
            });
        }

        // If payment_method filter is active, join payments table for counting
        if ($payment_method) {
            $qBase = $qBase->join('payments', 'sales.id', '=', 'payments.sale_id')
                ->whereNull('payments.return_id')
                ->where('payments.paying_method', $payment_method);
        }

        // Calculate total data count
        if ($payment_method) {
            // Count distinct sales if payments table was joined
            $totalData = $qBase->distinct('sales.id')->count('sales.id');
        } else {
            $totalData = $qBase->count();
        }
        $totalFiltered = $totalData; // Initialize totalFiltered

        // Set limit if not provided
        if (is_null($limit)) {
            $limit = $totalData;
        }

        // --- 3. EXECUTION QUERY (NO SEARCH VALUE) ---
        if (empty($request->input('search.value'))) {

            // Rebuild the query for fetching the final results (with due calculation)
            $query = Sale::select('sales.*', DB::raw('(grand_total - paid_amount) as due'))
                ->with([
                    'biller:id,name,company_name,email,phone_number',
                    'customer:id,name,phone_number,deposit,expense,points',
                    'warehouse:id,name',
                    'user:id,name'
                ])
                ->whereNull('sales.deleted_at')
                ->where(function ($q) {
                    $q->where('sales.sale_type', '!=', 'opening balance')
                        ->orWhereNull('sales.sale_type');
                })
                ->whereDate('sales.created_at', '>=', $request->input('starting_date'))
                ->whereDate('sales.created_at', '<=', $request->input('ending_date'));

            // Reapply Access Control
            if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $query = $query->where('sales.user_id', Auth::id());
            } elseif (Auth::user()->role_id > 2 && config('staff_access') == 'warehouse') {
                $query = $query->where('sales.warehouse_id', Auth::user()->warehouse_id);
            }

            // Reapply all filters
            if ($warehouse_id)
                $query = $query->where('sales.warehouse_id', $warehouse_id);
            if ($sale_status)
                $query = $query->where('sales.sale_status', $sale_status);
            if ($payment_status)
                $query = $query->where('sales.payment_status', $payment_status);
            if ($sale_type)
                $query = $query->where('sales.sale_type', $sale_type);
            if ($request->input('social_channel') && $request->input('social_channel') !== '0')
                $query = $query->where('sales.social_channel', $request->input('social_channel'));
            if ($installment == 1) {
                $query = $query->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('installment_plans')
                        ->where('reference_type', 'sale')
                        ->whereColumn('reference_id', 'sales.id');
                });
            }

            if ($installment == 2) {
                $query = $query->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('installment_plans')
                        ->where('reference_type', 'sale')
                        ->whereColumn('reference_id', 'sales.id');
                });
            }

            // Special handling for payment_method filter
            if ($payment_method) {
                $query = $query->join('payments', 'sales.id', '=', 'payments.sale_id')
                    ->whereNull('payments.return_id')
                    ->where('payments.paying_method', $payment_method)
                    ->select('sales.*', DB::raw('(grand_total - paid_amount) as due')); // Re-select for due and sales.*
            }

            // **SORTING LOGIC: Handle 'due' column sort**
            if ($orderColumn == 'due') {
                $query->orderByRaw('(grand_total - paid_amount) ' . $dir);
            } else {
                if (str_contains($orderColumn, 'product_sales')) {
                    $query->leftJoin('product_sales', 'sales.id', '=', 'product_sales.sale_id')
                        ->orderBy($orderColumn, $dir)
                        ->groupBy('sales.id');
                } else {
                    $query->orderBy('sales.' . $orderColumn, $dir);
                }
                // Apply standard column sorting (make sure to use 'sales.' prefix if payments table is joined)
                // $query->orderBy('sales.' . $orderColumn, $dir);
            }

            // Apply grouping if join was used for payment_method filter
            if ($payment_method) {
                $query = $query->groupBy('sales.id');
            }

            // Fetch results with pagination
            $sales = $query->skip($start)->take($limit)->get();
        }
        // --- 4. EXECUTION QUERY (WITH SEARCH VALUE) ---
        else {
            $search = $request->input('search.value');

            $q = Sale::query()
                ->leftJoin('product_sales', 'sales.id', '=', 'product_sales.sale_id')
                ->leftJoin('billers', 'sales.biller_id', '=', 'billers.id')
                ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
                ->leftJoin('products', 'product_sales.product_id', '=', 'products.id')
                ->whereNull('sales.deleted_at')
                ->where(function ($q) {
                    $q->where('sales.sale_type', '!=', 'opening balance')
                        ->orWhereNull('sales.sale_type');
                })
                ->whereDate('sales.created_at', '>=', $request->input('starting_date'))
                ->whereDate('sales.created_at', '<=', $request->input('ending_date'));

            // ✅ APPLY FILTERS FIRST (DO NOT MOVE THIS)
            if ($warehouse_id) {
                $q->where('sales.warehouse_id', $warehouse_id);
            }

            if ($sale_status) {
                $q->where('sales.sale_status', $sale_status);
            }

            if ($payment_status) {
                $q->where('sales.payment_status', $payment_status);
            }

            if ($sale_type) {
                $q->where('sales.sale_type', $sale_type);
            }

            if ($request->input('social_channel') && $request->input('social_channel') !== '0') {
                $q->where('sales.social_channel', $request->input('social_channel'));
            }

            if ($payment_method) {
                $q->join('payments', 'sales.id', '=', 'payments.sale_id')
                    ->whereNull('payments.return_id')
                    ->where('payments.paying_method', $payment_method);
            }

            if ($installment == 1) {
                $q = $q->whereExists(function ($q1) {
                    $q1->select(DB::raw(1))
                        ->from('installment_plans')
                        ->where('reference_type', 'sale')
                        ->whereColumn('reference_id', 'sales.id');
                });
            }

            if ($installment == 2) {
                $q = $q->whereNotExists(function ($q1) {
                    $q1->select(DB::raw(1))
                        ->from('installment_plans')
                        ->where('reference_type', 'sale')
                        ->whereColumn('reference_id', 'sales.id');
                });
            }

            // ✅ ACCESS CONTROL
            if (Auth::user()->role_id > 2) {
                if (config('staff_access') == 'own') {
                    $q->where('sales.user_id', Auth::id());
                } elseif (config('staff_access') == 'warehouse') {
                    $q->where('sales.warehouse_id', Auth::user()->warehouse_id);
                }
            }

            // ✅ SAFE SEARCH GROUP (NO FILTER ESCAPE)
            $q->where(function ($query) use ($search, $field_names) {

                // Date detection
                $date = date('Y-m-d', strtotime(str_replace('/', '-', $search)));
                if ($date) {
                    $query->orWhereDate('sales.created_at', $date);
                }

                // General search fields
                $query->orWhere('sales.reference_no', 'LIKE', "%{$search}%")
                    ->orWhere('customers.name', 'LIKE', "%{$search}%")
                    ->orWhere('customers.phone_number', 'LIKE', "%{$search}%")
                    ->orWhere('billers.name', 'LIKE', "%{$search}%")
                    ->orWhere('product_sales.imei_number', 'LIKE', "%{$search}%")
                    ->orWhere('products.name', 'LIKE', "%{$search}%")
                    ->orWhere('products.code', 'LIKE', "%{$search}%");

                // Custom fields
                foreach ($field_names as $field_name) {
                    $query->orWhere('sales.' . $field_name, 'LIKE', "%{$search}%");
                }
            });

            // ✅ COUNT (CORRECT WITH JOINS)
            $totalFiltered = $q->distinct('sales.id')->count('sales.id');

            // ✅ SORTING
            if ($orderColumn == 'due') {
                $q->orderByRaw('(sales.grand_total - sales.paid_amount) ' . $dir);
            } else {
                if (str_contains($orderColumn, 'product_sales')) {
                    $q->leftJoin('product_sales', 'sales.id', '=', 'product_sales.sale_id')
                        ->orderBy($orderColumn, $dir)
                        ->groupBy('sales.id');
                } else {
                    $q->orderBy('sales.' . $orderColumn, $dir);
                }
                // $q->orderBy('sales.' . $orderColumn, $dir);
            }

            // ✅ FETCH DATA
            $sales = $q->select(
                'sales.*',
                DB::raw('(sales.grand_total - sales.paid_amount) as due')
            )
                ->with([
                    'payments:id,sale_id,amount,paying_method',
                    'delivery:id,sale_id,status',
                    'return:id,sale_id,grand_total'
                ])
                ->groupBy('sales.id')
                ->skip($start)
                ->take($limit)
                ->get();
        }

        $currency_list = cache()->get('currency_list');

        // --- 5. PREPARING DATA FOR DATATABLES ---
        $data = array();
        if (!empty($sales)) {
            $steadfast_sent_value = [];
            foreach ($sales as $key => $sale) {

                $lims_installment_plan_data = DB::table('installment_plans')
                    ->where([
                        ['reference_type', 'sale'],
                        ['reference_id', $sale->id]
                    ])->first();

                if ($sale->currency_id) {
                    $currency_obj = $currency_list->where('id', $sale->currency_id)->first();
                    $currency_code = $currency_obj ? $currency_obj->code : 'N/A';
                    $currency = $currency_code . '/' . $sale->exchange_rate;
                } else {
                    $currency_code = 'N/A';
                    $currency = 'N/A';
                }

                $user = $sale->user;
                $nestedData['id'] = $sale->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format') . ' h:i:s a', strtotime($sale->created_at));
                $nestedData['steadfast'] = $sale->steadfast;
                $nestedData['reference_no'] = $sale->reference_no;
                $nestedData['created_by'] = $user->name;
                $nestedData['customer'] = $sale->customer->name . '<br>' . $sale->customer->phone_number . '<input type="hidden" class="deposit" value="' . ($sale->customer->deposit - $sale->customer->expense) . '" />' . '<input type="hidden" class="points" value="' . $sale->customer->points . '" />';

                $nestedData['warehouse_name'] = $sale->warehouse->name;
                $nestedData['currency'] = $currency;

                // Products details logic (make sure $sale->products relationship is working)
                if ($sale->sale_type === 'tailoring') {
                    $order = \Modules\Tailoring\Entities\TailoringOrder::where('core_sale_id', $sale->id)->first();
                    $total_items = (float) $sale->total_qty > 0 ? (int) $sale->total_qty : ((int) $sale->item > 0 ? (int) $sale->item : ($order ? $order->commercialPieceCount() : 1));
                    if ($order) {
                        $items = $order->items;
                        $productNames = [];
                        foreach ($items as $key_prod => $item) {
                            $html_tag_start = ($key_prod + 1 < $items->count()) ? '<div style="border-bottom: 1px solid #ccc; padding-bottom: 4px; margin-bottom: 4px;">' : '<div style="padding-bottom: 4px; margin-bottom: 4px;">';
                            $productNames[] = $html_tag_start . e($item->garment_name ?: $order->garment_type) . '</div>';
                        }
                        if (empty($productNames)) {
                            $productNames[] = '<div style="padding-bottom: 4px; margin-bottom: 4px;">' . e($order->garment_type ?: 'Tailoring Garment') . '</div>';
                        }
                        $nestedData['products'] = implode('', $productNames);
                    } else {
                        $nestedData['products'] = 'Tailoring Order';
                    }
                    $nestedData['qty'] = '<div style="padding-bottom: 4px; margin-bottom: 4px;"><span class="badge badge-primary">' . $total_items . '</span></div>';
                } else {
                    $productNames = [];
                    $productQtys = [];
                    $total_products = $sale->products->count();
                    foreach ($sale->products as $key_prod => $product) {
                        $product_sale = Product_Sale::where(['product_id' => $product->id, 'sale_id' => $sale->id])->first();
                        $html_tag_start = ($key_prod + 1 < $total_products) ? '<div style="border-bottom: 1px solid #ccc; padding-bottom: 4px; margin-bottom: 4px;">' : '<div style="padding-bottom: 4px; margin-bottom: 4px;">';
                        $productNames[] = $html_tag_start . e($product->name) . '</div>';
                        $productQtys[] = '<div style="padding-bottom: 4px; margin-bottom: 4px;">' . '<span class="badge badge-primary">' . e($product_sale ? $product_sale->qty : 0) . '</span></div>';
                    }
                    $nestedData['products'] = implode('', $productNames);
                    $nestedData['qty'] = implode('', $productQtys);
                }

                if (!$sale->exchange_rate || $sale->exchange_rate == 0)
                    $sale->exchange_rate = 1;

                $payments = $sale->payments;
                $paymentMethods = $payments->map(function ($payment) use ($sale) {
                    return ucfirst($payment->paying_method ?? '') .
                        '(' . number_format($payment->amount / $sale->exchange_rate, config('decimal')) . ')';
                })->implode(', ');

                $nestedData['payment_method'] = $paymentMethods;

                // Status logic (Sale Status)
                $sale_status_text = '';
                switch ($sale->sale_status) {
                    case 1:
                        $nestedData['sale_status'] = '<div class="badge badge-success">' . __('db.Completed') . '</div>';
                        $sale_status_text = __('db.Completed');
                        break;
                    case 2:
                        $nestedData['sale_status'] = '<div class="badge badge-danger">' . __('db.Pending') . '</div>';
                        $sale_status_text = __('db.Pending');
                        break;
                    case 3:
                        $nestedData['sale_status'] = '<div class="badge badge-warning">' . __('db.Draft') . '</div>';
                        $sale_status_text = __('db.Draft');
                        break;
                    case 4:
                        $nestedData['sale_status'] = '<div class="badge badge-danger">' . __('db.Returned') . '</div>';
                        $sale_status_text = __('db.Returned');
                        break;
                    case 5:
                        $nestedData['sale_status'] = '<div class="badge badge-info">' . __('db.Processing') . '</div>';
                        $sale_status_text = __('db.Processing');
                        break;
                    case 6:
                        $nestedData['sale_status'] = '<div class="badge badge-danger">' . __('db.cooked') . '</div>';
                        $sale_status_text = __('db.cooked');
                        break;
                    case 7:
                        $nestedData['sale_status'] = '<div class="badge badge-primary">' . __('db.served') . '</div>';
                        $sale_status_text = __('db.served');
                        break;
                }

                // Status logic (Payment Status)
                $payment_status_text = '';
                if ($sale->payment_status == 1) {
                    $nestedData['payment_status'] = '<div class="badge badge-danger">' . __('db.Pending') . '</div>';
                    $payment_status_text = __('db.Pending');
                } elseif ($sale->payment_status == 2) {
                    $nestedData['payment_status'] = '<div class="badge badge-danger">' . __('db.Due') . '</div>';
                    $payment_status_text = __('db.Due');
                } elseif ($sale->payment_status == 3) {
                    $nestedData['payment_status'] = '<div class="badge badge-warning">' . __('db.Partial') . '</div>';
                    $payment_status_text = __('db.Partial');
                } else {
                    $nestedData['payment_status'] = '<div class="badge badge-success">' . __('db.Paid') . '</div>';
                    $payment_status_text = __('db.Paid');
                }

                // Delivery Status Logic
                $delivery_data = $sale->delivery;
                if ($delivery_data) {
                    if ($delivery_data->status == 1)
                        $nestedData['delivery_status'] = '<div class="badge badge-primary">' . __('db.Packing') . '</div>';
                    elseif ($delivery_data->status == 2)
                        $nestedData['delivery_status'] = '<div class="badge badge-info">' . __('db.Delivering') . '</div>';
                    elseif ($delivery_data->status == 3)
                        $nestedData['delivery_status'] = '<div class="badge badge-success">' . __('db.Delivered') . '</div>';
                } else
                    $nestedData['delivery_status'] = 'N/A';

                // Financial amounts

                if ($sale->paid_amount == 0) {
                    $returned_amount = 0;
                } else {
                    $returned_amount = $sale->return?->grand_total ?? 0;
                }

                $refunded_amount = Payment::where('sale_id', $sale->id)->whereNotNull('return_id')->sum('amount');
                $non_refunded_return_total = max(0, $returned_amount - $refunded_amount);


                $nestedData['total_tax'] = number_format($sale->total_tax / $sale->exchange_rate, config('decimal'));
                $nestedData['order_tax'] = number_format($sale->order_tax / $sale->exchange_rate, config('decimal'));
                $nestedData['grand_total'] = number_format($sale->grand_total / $sale->exchange_rate, config('decimal'));
                $nestedData['returned_amount'] = number_format($returned_amount / $sale->exchange_rate, config('decimal'));
                $nestedData['paid_amount'] = number_format($sale->paid_amount / $sale->exchange_rate, config('decimal'));

                // Calculation for due
                if ($sale->sale_status == 4 && $sale->paid_amount == 0) {
                    $nestedData['due'] = number_format(0, config('decimal'));
                } else {
                    $nestedData['due'] = number_format(max(0, $sale->grand_total - $sale->paid_amount - $non_refunded_return_total) / $sale->exchange_rate, config('decimal'));
                }

                // Custom fields data
                foreach ($field_names as $field_name) {
                    $nestedData[$field_name] = $sale->$field_name;
                }

                // Options buttons (Keeping your existing logic for this section)
                // ... (The long string for $nestedData['options']) ...

                $nestedData['options'] = '<div class="btn-group">
                    <button type="button" class="action-modal btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-sale-id="' . $sale->id . '" data-invoice="' . $sale->reference_no . '">' . __("db.action") . '
                    <span class="caret"></span>
                    <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                        <li><a href="' . route('sale.invoice', $sale->id) . '" class="btn btn-link gen-invoice"><i class="ti ti-copy"></i> ' . __('db.Generate Invoice') . '</a></li>
                        <li>
                            <button type="button" class="btn btn-link view"><i class="ti ti-eye"></i> ' . __('db.View') . '</button>
                        </li>';
                if (in_array("sales-edit", $request['all_permission'])) {
                    if ($sale->sale_status != 3)
                        $nestedData['options'] .= '<li>
                            <a href="' . route('sales.edit', $sale->id) . '" class="btn btn-link"><i class="ti ti-edit"></i> ' . __('db.edit') . '</a>
                            </li>';
                    else
                        $nestedData['options'] .= '<li>
                            <a href="' . url('pos/' . $sale->id) . '" class="btn btn-link"><i class="ti ti-edit"></i> ' . __('db.edit') . '</a>
                        </li>';
                }
                if ($lims_installment_plan_data) {
                    $nestedData['options'] .= '<li>
                        <a href="' . route('installmentplan.show', $lims_installment_plan_data->id) . '" class="btn btn-link"><i class="ti ti-info-circle"></i> ' . __('db.Installment Plan') . '</a>
                    </li>';
                }
                if (config('is_packing_slip') && in_array("packing_slip_challan", $request['all_permission']) && ($sale->sale_status == 2 || $sale->sale_status == 5)) {
                    $nestedData['options'] .=
                        '<li>
                        <button type="button" class="create-packing-slip-btn btn btn-link" data-id = "' . $sale->id . '" data-toggle="modal" data-target="#packing-slip-modal"><i class="ti ti-box"></i> ' . __('db.Create Packing Slip') . '</button>
                    </li>';
                }
                if (in_array("sale-payment-index", $request['all_permission']))
                    $nestedData['options'] .=
                        '<li>
                                <button type="button" class="get-payment btn btn-link" data-id = "' . $sale->id . '"><i class="ti ti-cash-banknote"></i> ' . __('db.View Payment') . '</button>
                            </li>';
                if (in_array("sale-payment-add", $request['all_permission']) && ($sale->payment_status != 4) && ($sale->sale_status != 3) && ($sale->sale_status != 4)) {
                    $currency_code_name = $sale->currency->code ?? 'USD';
                    $nestedData['options'] .=
                        ' <li>
                                <button
                                    type="button"
                                    class="add-payment btn btn-link"
                                    data-id="' . $sale->id . '"
                                    data-due="' . $nestedData['due'] . '"
                                    data-currency_id="' . $sale->currency_id . '"
                                    data-currency_name="' . $currency_code_name . '"
                                    data-exchange_rate="' . $sale->exchange_rate . '"
                                    data-toggle="modal"
                                    data-target="#add-payment">
                                    <i class="ti ti-plus"></i> ' . __('db.Add Payment') . '
                                </button>
                            </li>';
                }
                if ($sale->sale_status !== 4)
                    $nestedData['options'] .=
                        '<li>
                            <a href="return-sale/create?reference_no=' . $nestedData['reference_no'] . '" class="add-payment btn btn-link"><i class="ti ti-arrow-back"></i> ' . __('db.Add Return') . '</a>
                        </li>';

                $nestedData['options'] .=
                    '<li>
                    <button type="button" class="send-sms btn btn-link" data-id = "' . $sale->id . '" data-customer_id="' . $sale->customer_id . '" data-reference_no="' . $nestedData['reference_no'] . '" data-sale_status="' . $sale->sale_status . '" data-payment_status="' . $sale->payment_status . '"  data-toggle="modal" data-target="#send-sms"><i class="ti ti-message"></i> ' . __('db.Send SMS') . '</button>
                </li>';

                $nestedData['options'] .=
                    '<li>
                    <form action="' . route('sale.wappnotification') . '" method="POST" style="display:inline;">
                      ' . csrf_field() . '
                        <input type="hidden" name="customer_id" value="' . $sale->customer_id . '">
                        <input type="hidden" name="sale_id" value="' . $sale->id . '">
                        <button type="submit" class="btn btn-link">
                            <i class="ti ti-brand-whatsapp"></i> ' . __('db.invoice_to_hatsapp') . '
                        </button>
                    </form>
                </li>';

                if (\Nwidart\Modules\Facades\Module::has('SocialCommerce') && \Nwidart\Modules\Facades\Module::find('SocialCommerce')->isEnabled()) {
                    $scSettings = \Modules\SocialCommerce\Entities\SocialCommerceSetting::first();
                    if ($scSettings && $scSettings->is_active) {
                        $waActions = [
                            ['action' => 'confirmation', 'icon' => 'ti ti-brand-whatsapp', 'label' => __('db.WA: Order Confirmation')],
                            ['action' => 'payment', 'icon' => 'ti ti-brand-whatsapp', 'label' => __('db.WA: Payment Request')],
                            ['action' => 'delivery', 'icon' => 'ti ti-brand-whatsapp', 'label' => __('db.WA: Delivery Update')],
                        ];
                        foreach ($waActions as $wa) {
                            $nestedData['options'] .=
                            '<li>
                                <a href="' . route('socialcommerce.wa.share.order', ['id' => $sale->id, 'action' => $wa['action']]) . '" class="btn btn-link" target="_blank">
                                    <i class="' . $wa['icon'] . '"></i> ' . $wa['label'] . '
                                </a>
                            </li>';
                        }
                    }
                }

                $nestedData['options'] .=
                    '<li>
                        <button type="button" class="add-delivery btn btn-link" data-id = "' . $sale->id . '"><i class="ti ti-truck"></i> ' . __('db.Add Delivery') . '</button>
                    </li>';

                if (in_array("sales-delete", $request['all_permission']))
                    $nestedData['options'] .= '<form action="' . route("sales.destroy", $sale->id) . '" method="POST">' . csrf_field() . '' . method_field("DELETE") . '<li><button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="ti ti-trash"></i> ' . __("db.delete") . '</button></li></form>
                        </ul>
                    </div>';

                // data for sale details by one click
                $coupon = Coupon::find($sale->coupon_id);
                $coupon_code = $coupon ? $coupon->code : null;

                $table_name = '';
                if (!empty($sale->table_id)) {
                    $table = Table::findOrFail($sale->table_id);
                    if ($table) $table_name = $table->name;
                }

                $due_date_display = '';
                $days_remaining   = '';

                if ($sale->pay_term_no) {

                    $created = \Carbon\Carbon::parse($sale->created_at);

                    if (!empty($sale->due_date)) {
                        $due_date = \Carbon\Carbon::parse($sale->due_date);
                    } else {
                        $due_date = $sale->pay_term_period == 'months'
                            ? $created->copy()->addMonths((int)$sale->pay_term_no)
                            : $created->copy()->addDays((int)$sale->pay_term_no);
                    }

                    $due_date_display = $due_date->format('Y-m-d');

                    $today = \Carbon\Carbon::today();
                    $diff  = $today->diffInDays($due_date, false);

                    if ($diff > 0) {
                        $days_remaining = $diff . ' days remaining';
                    } elseif ($diff == 0) {
                        $days_remaining = 'Due Today';
                    } else {
                        $days_remaining = 'Overdue by ' . abs($diff) . ' days';
                    }
                }

                if ($sale->payment_status == 1)
                    $payment_status = __('db.Pending');
                elseif ($sale->payment_status == 2)
                    $payment_status = __('db.Due');
                elseif ($sale->payment_status == 3)
                    $payment_status = __('db.Partial');
                else
                    $payment_status = __('db.Paid');

                $nestedData['sale'] = $sale->id;

                $data[] = $nestedData;
            }
        }

        // --- 6. FINAL JSON OUTPUT ---
        $json_data = array(
            "draw"          => intval($request->input('draw')),
            "recordsTotal"  => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"          => $data,
            "steadfast_sent_value" => $steadfast_sent_value
        );

        echo json_encode($json_data);
    }

    public function create()
    {
        $role = Role::find(Auth::user()->role_id);
        if (!$role->hasPermissionTo('sales-add')) {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        $permissions = Role::findByName($role->name)->permissions;
        $all_permission = [];
        foreach ($permissions as $permission) {
            $all_permission[] = $permission->name;
        }
        if (empty($all_permission)) {
            $all_permission[] = 'dummy text';
        }

        $lims_customer_list = Customer::where('is_active', true)->get();

        if (Auth::user()->role_id > 2) {
            $lims_warehouse_list = Warehouse::where([
                ['is_active', true],
                ['id', Auth::user()->warehouse_id]
            ])->get();

            $lims_biller_list = Biller::where([
                ['is_active', true],
                ['id', Auth::user()->biller_id]
            ])->get();
        } else {
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_biller_list = Biller::where('is_active', true)->get();
        }

        // Every Sale UI consumes the same computed restaurant flag.
        $lims_warehouse_list = $this->addRestaurantWorkflowFlag($lims_warehouse_list);

        $lims_tax_list = Tax::where('is_active', true)->get();
        $lims_pos_setting_data = PosSetting::latest()->first();
        $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
        $options = $lims_pos_setting_data
            ? explode(',', $lims_pos_setting_data->payment_options)
            : [];

        $currency_list = cache()->get('currency_list');

        $numberOfInvoice = Sale::whereNull('sales.deleted_at')
            ->where(function ($q) {
                $q->where('sales.sale_type', '!=', 'opening balance')
                    ->orWhereNull('sales.sale_type');
            })->count();

        $custom_fields = CustomField::where('belongs_to', 'sale')->get();
        $lims_customer_group_all = CustomerGroup::where('is_active', true)->get();
        $lims_account_list = app(\App\Services\PaymentAccountService::class)->validOperationalAccounts();

        // Initial warehouse uses the same priority as the page previously used.
        $selectedWarehouseId = null;
        if (Auth::user()->role_id > 2 && Auth::user()->warehouse_id) {
            $selectedWarehouseId = (int) Auth::user()->warehouse_id;
        } elseif (
            $lims_pos_setting_data
            && $lims_pos_setting_data->warehouse_id
            && $lims_warehouse_list->contains('id', (int) $lims_pos_setting_data->warehouse_id)
        ) {
            $selectedWarehouseId = (int) $lims_pos_setting_data->warehouse_id;
        } elseif ($lims_warehouse_list->isNotEmpty()) {
            $selectedWarehouseId = (int) $lims_warehouse_list->first()->id;
        }

        $selectedWarehouse = $selectedWarehouseId
            ? $lims_warehouse_list->firstWhere('id', $selectedWarehouseId)
            : null;

        $restaurantModuleEnabled = $this->restaurantModuleAvailable();
        $isRestaurantWarehouse = $this->isRestaurantWarehouse($selectedWarehouse);

        // Load restaurant data once when the module exists. Tables carry their
        // warehouse_id so the Blade can filter them when Warehouse changes.
        if ($restaurantModuleEnabled) {
            $lims_table_list = Table::join('floors', 'tables.floor_id', '=', 'floors.id')
                ->select(
                    'tables.id as id',
                    'tables.name',
                    'tables.number_of_person',
                    'floors.name as floor',
                    'floors.warehouse_id'
                )
                ->where('tables.is_active', 1)
                ->get();

            $service_list = DB::table('services')
                ->where('is_active', 1)
                ->get();

            $waiter_list = DB::table('users')
                ->where('service_staff', 1)
                ->where('is_active', 1)
                ->get();
        } else {
            $lims_table_list = collect();
            $service_list = collect();
            $waiter_list = collect();
        }

        return view('backend.sale.create', compact(
            'currency_list',
            'all_permission',
            'lims_customer_list',
            'lims_warehouse_list',
            'lims_biller_list',
            'lims_pos_setting_data',
            'lims_tax_list',
            'lims_reward_point_setting_data',
            'options',
            'numberOfInvoice',
            'custom_fields',
            'lims_customer_group_all',
            'lims_account_list',
            'restaurantModuleEnabled',
            'selectedWarehouseId',
            'selectedWarehouse',
            'isRestaurantWarehouse',
            'lims_table_list',
            'service_list',
            'waiter_list'
        ));
    }

    public function store(StoreSaleRequest $request)
    {
        $data = $this->prepareWarehouseSaleData($request->all());

        if (isset($request->reference_no)) {
            $this->validate($request, [
                'reference_no' => [
                    'max:191',
                    'required',
                    'unique:sales'
                ],
            ]);
        }

        $document = $request->document;
        if ($document) {
            $v = Validator::make(
                [
                    'extension' => strtolower($request->document->getClientOriginalExtension()),
                ],
                [
                    'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                ]
            );
            if ($v->fails()) {
                return redirect()->back()->withErrors($v->errors());
            }

            $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
            $documentName = date("Ymdhis");
            if (!config('database.connections.saleprosaas_landlord')) {
                $documentName = $documentName . '.' . $ext;
                $document->move(public_path('documents/sale'), $documentName);
            } else {
                $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                $document->move(public_path('documents/sale'), $documentName);
            }
            $data['document'] = $documentName;
        }

        try {
            // SaleDomainService owns the database transaction. When this returns,
            // the sale, product lines, stock, payments and accounting work are committed.
            $lims_sale_data = $this->saleDomainService->createSale($data, Auth::user());

            // Phase 2 must be signed/submitted before the receipt QR is rendered.
            // Failures are recorded without rolling back the committed sale.
            app(\App\Services\ZatcaIntegrationService::class)->processSale($lims_sale_data);

            if ($request->enable_installment) {
                $installment_plan_data = $request->installment_plan;
                $installment_plan_data['reference_id'] = $lims_sale_data->id;
                (new InstallmentPlanController)->store($installment_plan_data);
            }

            // Notifications are deliberately outside SaleDomainService's transaction.
            // The helper isolates notification failures from the committed sale.
            $this->dispatchSaleNotifications($lims_sale_data);

            $message = ((int) $lims_sale_data->sale_status === 3)
                ? 'Sale successfully added to draft'
                : 'Sale created successfully';

            /*
             * Keep the historical AJAX response contract used by POS/Create Sale:
             * completed/restaurant-processing sales return the numeric sale ID so
             * the browser can request sales/gen_invoice/{id}?is_print=true.
             */
            if (request()->ajax() || request()->wantsJson()) {
                if (in_array((int) $lims_sale_data->sale_status, [1, 5], true)) {
                    return response()->json($lims_sale_data->id);
                }

                if (!empty($data['pos'])) {
                    $redirectUrl = url('pos') . '?warehouse_id=' . (int) $lims_sale_data->warehouse_id;
                    return response()->json(['redirect' => $redirectUrl]);
                }

                return response()->json(['redirect' => url('sales')]);
            }

            // Preserve non-AJAX behavior for callers that submit without JavaScript.
            if (in_array((int) $lims_sale_data->sale_status, [1, 5], true)) {
                return $this->genInvoice($lims_sale_data->id);
            }

            if (!empty($data['pos'])) {
                $redirectUrl = 'pos?warehouse_id=' . (int) $lims_sale_data->warehouse_id;
                return redirect($redirectUrl)->with('message', $message);
            }

            return redirect('sales')->with('message', $message);
        } catch (\App\Exceptions\SaleValidationException $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('not_permitted', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            \Log::error('Sale creation failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id(),
            ]);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'message' => 'Sale creation failed: ' . $e->getMessage(),
                    'error' => 'Sale creation failed: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('not_permitted', 'Sale creation failed: ' . $e->getMessage());
        }
    }

    public function getSoldItem($id)
    {
        $sale = Sale::select('warehouse_id')->find($id);
        $product_sale_data = Product_Sale::where('sale_id', $id)->get();
        $data = [];
        $data['amount'] = $sale->shipping_cost - $sale->sale_discount;
        $flag = 0;
        foreach ($product_sale_data as $key => $product_sale) {
            $product = Product::select('type', 'name', 'code', 'product_list', 'qty_list')->find($product_sale->product_id);
            $data[$key]['combo_in_stock'] = 1;
            $data[$key]['child_info'] = '';
            if ($product->type == 'combo') {
                $child_ids = explode(",", $product->product_list);
                $qty_list = explode(",", $product->qty_list);
                foreach ($child_ids as $index => $child_id) {
                    $child_product = Product::select('name', 'code')->find($child_id);

                    $child_stock = $child_product->initial_qty + $child_product->received_qty;
                    $required_stock = $qty_list[$index] * $product_sale->qty;
                    if ($required_stock > $child_stock) {
                        $data[$key]['combo_in_stock'] = 0;
                        $data[$key]['child_info'] = $child_product->name . '[' . $child_product->code . '] does not have enough stock. In stock: ' . $child_stock;
                        break;
                    }
                }
            }
            $data[$key]['product_id'] = $product_sale->product_id . '|' . $product_sale->variant_id;
            $data[$key]['type'] = $product->type;
            if ($product_sale->variant_id) {
                $variant_data = Variant::select('name')->find($product_sale->variant_id);
                $product_variant_data = ProductVariant::select('item_code')->where([
                    ['product_id', $product_sale->product_id],
                    ['variant_id', $product_sale->variant_id]
                ])->first();
                $data[$key]['name'] = $product->name . ' [' . $variant_data->name . ']';
                $product->code = $product_variant_data->item_code;
            } else
                $data[$key]['name'] = $product->name;
            $data[$key]['qty'] = $product_sale->qty;
            $data[$key]['code'] = $product->code;
            $data[$key]['sold_qty'] = $product_sale->qty;
            $product_warehouse = Product_Warehouse::where([
                ['product_id', $product_sale->product_id],
                ['warehouse_id', $sale->warehouse_id]
            ])->first();
            if ($product_warehouse) {
                $data[$key]['stock'] = $product_warehouse->qty;
            } else {
                $data[$key]['stock'] = $product->qty;
            }

            $data[$key]['unit_price'] = $product_sale->total / $product_sale->qty;
            $data[$key]['total_price'] = $product_sale->total;
            if ($product_sale->is_packing) {
                $data['amount'] = 0;
            } else {
                $flag = 1;
            }
            $data[$key]['is_packing'] = $product_sale->is_packing;
        }
        if ($flag)
            return $data;
        else
            return 'All the items of this sale has already been packed';
    }

    public function sendSMS(Request $request)
    {
        $data = $request->all();

        //sms send start
        // $smsTemplate = SmsTemplate::where('is_default',1)->latest()->first();

        $smsProvider = ExternalService::where('active', true)->where('type', 'sms')->first();
        if ($smsProvider) {
            $data['type'] = 'onsite';
            $this->_smsModel->initialize($data);
            return redirect()->back();
        }
        //sms send end
        else {
            return redirect()->back()->with('not_permitted', __('db.Please setup your SMS API first!'));
        }
    }

    public function sendMail(Request $request)
    {
        $data = $request->all();
        $lims_sale_data = Sale::findOrFail($data['sale_id']);
        $lims_product_sale_data = Product_Sale::where('sale_id', $data['sale_id'])->get();
        $lims_customer_data = Customer::find($lims_sale_data->customer_id);
        $mail_setting = MailSetting::latest()->first();

        if (!$mail_setting) {
            return $this->setErrorMessage('Please Setup Your Mail Credentials First.');
        } else if ($lims_customer_data->email) {
            //collecting male data
            $mail_data['email'] = $lims_customer_data->email;
            $mail_data['reference_no'] = $lims_sale_data->reference_no;
            $mail_data['sale_status'] = $lims_sale_data->sale_status;
            $mail_data['payment_status'] = $lims_sale_data->payment_status;
            $mail_data['total_qty'] = $lims_sale_data->total_qty;
            $mail_data['total_price'] = $lims_sale_data->total_price;
            $mail_data['order_tax'] = $lims_sale_data->order_tax;
            $mail_data['order_tax_rate'] = $lims_sale_data->order_tax_rate;
            $mail_data['order_discount'] = $lims_sale_data->order_discount;
            $mail_data['shipping_cost'] = $lims_sale_data->shipping_cost;
            $mail_data['grand_total'] = $lims_sale_data->grand_total;
            $mail_data['paid_amount'] = $lims_sale_data->paid_amount;

            foreach ($lims_product_sale_data as $key => $product_sale_data) {
                $lims_product_data = Product::find($product_sale_data->product_id);
                if ($product_sale_data->variant_id) {
                    $variant_data = Variant::select('name')->find($product_sale_data->variant_id);
                    $mail_data['products'][$key] = $lims_product_data->name . ' [' . $variant_data->name . ']';
                } else
                    $mail_data['products'][$key] = $lims_product_data->name;
                if ($lims_product_data->type == 'digital')
                    $mail_data['file'][$key] = url('/product/files') . '/' . $lims_product_data->file;
                else
                    $mail_data['file'][$key] = '';
                if ($product_sale_data->sale_unit_id) {
                    $lims_unit_data = Unit::find($product_sale_data->sale_unit_id);
                    $mail_data['unit'][$key] = $lims_unit_data->unit_code;
                } else
                    $mail_data['unit'][$key] = '';

                $mail_data['qty'][$key] = $product_sale_data->qty;
                $mail_data['total'][$key] = $product_sale_data->qty;
            }
            $this->setMailInfo($mail_setting);
            try {
                Mail::to($mail_data['email'])->send(new SaleDetails($mail_data));
                return $this->setSuccessMessage('Mail sent successfully');
            } catch (\Exception $e) {
                return $this->setErrorMessage('Please Setup Your Mail Credentials First.');
            }
        } else
            return $this->setErrorMessage('Customer doesnt have email!');
    }

    public function whatsappNotificationSend(Request $request)
    {
        $data = $request->all();

        $company = gen_setting()->company_name;
        // Find the customer by ID
        $customer = Customer::find($data['customer_id']);
        if (!$customer) {
            return response()->json(['error' => 'Customer not found'], 404);
        }

        // Find the sale record by sale_id
        $sale = Sale::find($data['sale_id']);
        if (!$sale) {
            return response()->json(['error' => 'Sale not found'], 404);
        }

        $name = $customer->name;
        $phone = preg_replace('/\D/', '', $customer->wa_number ?? '');
        $referenceNo = $sale->reference_no; // Get the reference number from the sale
        $invoice = url('sales/gen_invoice/' . $sale->id); // Generate invoice URL

        // Create personalized text message
        $text = urlencode(__('db.Dear') . ' ' . $name . ', ' .
            __('db.Thank you for your purchase! Your invoice number is') . ' ' . $referenceNo . "\n" .
            __('db.If you have any questions or concerns, please don\'t hesitate to reach out to us We are here to help!') . "\n" . $invoice . "\n" .
            __('db.Best regards') . ",\n" .
            $company);

        $settings = WhatsappSetting::first();
        if (!$settings || empty($settings->phone_number_id) || empty($settings->permanent_access_token)) {
            // Construct WhatsApp URL with customer phone and personalized message
            $url = "https://web.whatsapp.com/send/?phone=$phone&text=$text";
            // Redirect to WhatsApp
            return redirect()->away($url);
        } else {
            $view  = $this->genInvoice($sale->id);
            $htmlContent = $view->render();
            // Get HTML content

            $request = new Request([
                'receiver_phone' => [$phone],
                'html_content' => $htmlContent,
                'message' =>  __('db.Invoice'),
            ]);

            $whpcon = new \App\Http\Controllers\WhatsappController();
            $result = $whpcon->sendMessage($request);
            // 6️⃣ Response
            if ($result['success'] ?? false) {
                return back()->with('message', $result['message']);
            } else {
                return back()->with('not_permitted', $result['message'] ?? __('db.fail_sent_message'));
            }
        }
    }

    public function paypalSuccess(Request $request)
    {
        $lims_sale_data = Sale::latest()->first();
        $lims_payment_data = Payment::latest()->first();
        $lims_product_sale_data = Product_Sale::where('sale_id', $lims_sale_data->id)->get();
        $provider = new ExpressCheckout;
        $token = $request->token;
        $payerID = $request->PayerID;
        $paypal_data['items'] = [];
        foreach ($lims_product_sale_data as $key => $product_sale_data) {
            $lims_product_data = Product::find($product_sale_data->product_id);
            $paypal_data['items'][] = [
                'name' => $lims_product_data->name,
                'price' => ($product_sale_data->total / $product_sale_data->qty),
                'qty' => $product_sale_data->qty
            ];
        }
        $paypal_data['items'][] = [
            'name' => 'order tax',
            'price' => $lims_sale_data->order_tax,
            'qty' => 1
        ];
        $paypal_data['items'][] = [
            'name' => 'order discount',
            'price' => $lims_sale_data->order_discount * (-1),
            'qty' => 1
        ];
        $paypal_data['items'][] = [
            'name' => 'shipping cost',
            'price' => $lims_sale_data->shipping_cost,
            'qty' => 1
        ];
        if ($lims_sale_data->grand_total != $lims_sale_data->paid_amount) {
            $paypal_data['items'][] = [
                'name' => 'Due',
                'price' => ($lims_sale_data->grand_total - $lims_sale_data->paid_amount) * (-1),
                'qty' => 1
            ];
        }

        $paypal_data['invoice_id'] = $lims_payment_data->payment_reference;
        $paypal_data['invoice_description'] = "Reference: {$paypal_data['invoice_id']}";
        $paypal_data['return_url'] = url('/sale/paypalSuccess');
        $paypal_data['cancel_url'] = url('/sale/create');

        $total = 0;
        foreach ($paypal_data['items'] as $item) {
            $total += $item['price'] * $item['qty'];
        }

        $paypal_data['total'] = $lims_sale_data->paid_amount;
        $response = $provider->getExpressCheckoutDetails($token);
        $response = $provider->doExpressCheckoutPayment($paypal_data, $token, $payerID);
        $data['payment_id'] = $lims_payment_data->id;
        $data['transaction_id'] = $response['PAYMENTINFO_0_TRANSACTIONID'];
        PaymentWithPaypal::create($data);
        return redirect('sales')->with('message', __('db.Sales created successfully'));
    }

    public function paypalPaymentSuccess(Request $request, $id)
    {
        $lims_payment_data = Payment::find($id);
        $provider = new ExpressCheckout;
        $token = $request->token;
        $payerID = $request->PayerID;
        $paypal_data['items'] = [];
        $paypal_data['items'][] = [
            'name' => 'Paid Amount',
            'price' => $lims_payment_data->amount,
            'qty' => 1
        ];
        $paypal_data['invoice_id'] = $lims_payment_data->payment_reference;
        $paypal_data['invoice_description'] = "Reference: {$paypal_data['invoice_id']}";
        $paypal_data['return_url'] = url('/sale/paypalPaymentSuccess');
        $paypal_data['cancel_url'] = url('/sale');

        $total = 0;
        foreach ($paypal_data['items'] as $item) {
            $total += $item['price'] * $item['qty'];
        }

        $paypal_data['total'] = $total;
        $response = $provider->getExpressCheckoutDetails($token);
        $response = $provider->doExpressCheckoutPayment($paypal_data, $token, $payerID);
        $data['payment_id'] = $lims_payment_data->id;
        $data['transaction_id'] = $response['PAYMENTINFO_0_TRANSACTIONID'];
        PaymentWithPaypal::create($data);
        return redirect('sales')->with('message', __('db.Payment created successfully'));
    }

    public function getProduct($id)
    {
        $query = Product::join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id');
        if (config('without_stock') == 'no') {
            $query = $query->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', $id],
                ['product_warehouse.qty', '>', 0]
            ]);
        } else {
            $query = $query->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', $id]
            ]);
        }

        $lims_product_warehouse_data = $query->whereNull('products.is_imei')
            ->whereNull('product_warehouse.variant_id')
            ->whereNull('product_warehouse.product_batch_id')
            ->select('product_warehouse.*', 'products.name', 'products.code', 'products.type', 'products.product_list', 'products.qty_list', 'products.is_embeded')
            ->get();
        //return $lims_product_warehouse_data;
        config()->set('database.connections.mysql.strict', false);
        \DB::reconnect(); //important as the existing connection if any would be in strict mode

        $query = Product::join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id');

        if (config('without_stock') == 'no') {
            $query = $query->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', $id],
                ['product_warehouse.qty', '>', 0]
            ]);
        } else {
            $query = $query->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', $id]
            ]);
        }

        $lims_product_with_batch_warehouse_data = $query->whereNull('product_warehouse.variant_id')
            ->whereNotNull('product_warehouse.product_batch_id')
            ->select('product_warehouse.*', 'products.name', 'products.code', 'products.type', 'products.product_list', 'products.qty_list', 'products.is_embeded')
            ->groupBy('product_warehouse.product_id')
            ->get();

        //now changing back the strict ON
        config()->set('database.connections.mysql.strict', true);
        \DB::reconnect();

        $query = Product::join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id');
        if (config('without_stock') == 'no') {
            $query = $query->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', $id],
                ['product_warehouse.qty', '>', 0]
            ]);
        } else {
            $query = $query->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', $id],
            ]);
        }

        $lims_product_with_imei_warehouse_data = Product::join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
            ->where([
                ['products.is_active', true],
                ['products.is_imei', true],
                ['product_warehouse.warehouse_id', $id],
                ['product_warehouse.qty', '>', 0]
            ])
            //->whereNull('product_warehouse.variant_id')
            ->whereNotNull('product_warehouse.imei_number')
            ->select('product_warehouse.*', 'products.is_embeded')
            //->groupBy('product_warehouse.product_id')
            ->get();

        $lims_product_with_variant_warehouse_data = $query->whereNotNull('product_warehouse.variant_id')
            ->select('product_warehouse.*', 'products.name', 'products.code', 'products.type', 'products.product_list', 'products.qty_list', 'products.is_embeded')
            ->get();

        $product_code = [];
        $product_name = [];
        $product_qty = [];
        $product_type = [];
        $product_id = [];
        $product_list = [];
        $qty_list = [];
        $product_price = [];
        $batch_no = [];
        $product_batch_id = [];
        $expired_date = [];
        $is_embeded = [];
        $imei_number = [];

        //product without variant
        foreach ($lims_product_warehouse_data as $product_warehouse) {
            if (!isset($product_warehouse->is_imei)) {
                if (isset($product_warehouse->imei_number)) continue;
            }

            $product_qty[] = $product_warehouse->qty;
            $product_price[] = $product_warehouse->price;
            $product_code[] =  $product_warehouse->code;
            $product_name[] = htmlspecialchars($product_warehouse->name);
            $product_type[] = $product_warehouse->type;
            $product_id[] = $product_warehouse->product_id;
            $product_list[] = $product_warehouse->product_list;
            $qty_list[] = $product_warehouse->qty_list;
            $batch_no[] = null;
            $product_batch_id[] = null;
            $expired_date[] = null;
            if ($product_warehouse->is_embeded)
                $is_embeded[] = $product_warehouse->is_embeded;
            else
                $is_embeded[] = 0;
            $imei_number[] = null;
        }
        //product with batches
        foreach ($lims_product_with_batch_warehouse_data as $product_warehouse) {
            if (!isset($product_warehouse->is_imei)) {
                if (isset($product_warehouse->imei_number)) continue;
            }

            $product_qty[] = $product_warehouse->qty;
            $product_price[] = $product_warehouse->price;
            $product_code[] =  $product_warehouse->code;
            $product_name[] = htmlspecialchars($product_warehouse->name);
            $product_type[] = $product_warehouse->type;
            $product_id[] = $product_warehouse->product_id;
            $product_list[] = $product_warehouse->product_list;
            $qty_list[] = $product_warehouse->qty_list;
            $product_batch_data = ProductBatch::select('id', 'batch_no', 'expired_date')->find($product_warehouse->product_batch_id);
            $batch_no[] = $product_batch_data->batch_no;
            $product_batch_id[] = $product_batch_data->id;
            $expired_date[] = date(config('date_format'), strtotime($product_batch_data->expired_date));
            if ($product_warehouse->is_embeded)
                $is_embeded[] = $product_warehouse->is_embeded;
            else
                $is_embeded[] = 0;

            $imei_number[] = null;
        }

        //product with imei
        foreach ($lims_product_with_imei_warehouse_data as $product_warehouse) {
            $imei_numbers = explode(",", $product_warehouse->imei_number);
            foreach ($imei_numbers as $key => $number) {
                $product_qty[] = $product_warehouse->qty;
                $product_price[] = $product_warehouse->price;
                $lims_product_data = Product::find($product_warehouse->product_id);
                //product with imei and variant
                if (!empty($product_warehouse->variant_id)) {
                    $lims_product_variant_data = ProductVariant::select('item_code')->FindExactProduct($product_warehouse->product_id, $product_warehouse->variant_id)->first();
                    $product_code[] = $lims_product_variant_data->item_code;
                } else {
                    $product_code[] =  $lims_product_data->code;
                }

                $product_name[] = htmlspecialchars($lims_product_data->name);
                $product_type[] = $lims_product_data->type;
                $product_id[] = $lims_product_data->id;
                $product_list[] = $lims_product_data->product_list;
                $qty_list[] = $lims_product_data->qty_list;
                $batch_no[] = null;
                $product_batch_id[] = null;
                $expired_date[] = null;
                $is_embeded[] = 0;
                $imei_number[] = $number;
            }
        }

        //product with variant
        foreach ($lims_product_with_variant_warehouse_data as $product_warehouse) {
            if (!isset($product_warehouse->is_imei)) {
                if (isset($product_warehouse->imei_number)) continue;
            }

            $lims_product_variant_data = ProductVariant::select('item_code')->FindExactProduct($product_warehouse->product_id, $product_warehouse->variant_id)->first();
            if ($lims_product_variant_data) {
                $product_qty[] = $product_warehouse->qty;
                $product_code[] =  $lims_product_variant_data->item_code;
                $product_name[] = htmlspecialchars($product_warehouse->name);
                $product_type[] = $product_warehouse->type;
                $product_id[] = $product_warehouse->product_id;
                $product_list[] = $product_warehouse->product_list;
                $qty_list[] = $product_warehouse->qty_list;
                $batch_no[] = null;
                $product_batch_id[] = null;
                $expired_date[] = null;
                if ($product_warehouse->is_embeded)
                    $is_embeded[] = $product_warehouse->is_embeded;
                else
                    $is_embeded[] = 0;

                $imei_number[] = null;
            }
        }

        //retrieve product with type of digital and service
        $lims_product_data = Product::whereNotIn('type', ['standard', 'combo'])->where('is_active', true)->get();
        foreach ($lims_product_data as $product) {
            if (!isset($product->is_imei)) {
                if (isset($product->imei_number)) continue;
            }

            $product_qty[] = $product->qty;
            $product_code[] =  $product->code;
            $product_name[] = $product->name;
            $product_type[] = $product->type;
            $product_id[] = $product->id;
            $product_list[] = $product->product_list;
            $qty_list[] = $product->qty_list;
            $batch_no[] = null;
            $product_batch_id[] = null;
            $expired_date[] = null;
            $is_embeded[] = 0;
            $imei_number[] = null;
        }
        $product_data = [$product_code, $product_name, $product_qty, $product_type, $product_id, $product_list, $qty_list, $product_price, $batch_no, $product_batch_id, $expired_date, $is_embeded, $imei_number];
        //return $product_id;
        return $product_data;
    }

    public function posSale($id = '')
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('sales-add')) {
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if (empty($all_permission))
                $all_permission[] = 'dummy text';

            $lims_customer_list = Cache::remember('customer_list', 60 * 60 * 24, function () {
                return Customer::where('is_active', true)->get();
            });
            $lims_customer_group_all = Cache::remember('customer_group_list', 60 * 60 * 24, function () {
                return CustomerGroup::where('is_active', true)->get();
            });

            // Warehouse is the only POS-type authority. Avoid the year-long warehouse cache
            // so a pos_type change is reflected on the next POS load.
            $warehouseQuery = Warehouse::where('is_active', true);
            if ((int) Auth::user()->role_id > 2 && Auth::user()->warehouse_id) {
                $warehouseQuery->whereKey((int) Auth::user()->warehouse_id);
            }
            $lims_warehouse_list = $this->addRestaurantWorkflowFlag($warehouseQuery->get());

            $lims_account_list = Cache::remember('account_list', 60 * 60 * 24 * 365, function () {
                return app(\App\Services\PaymentAccountService::class)->validOperationalAccounts();
            });
            $lims_biller_list = Cache::remember('biller_list', 60 * 60 * 24 * 30, function () {
                return Biller::where('is_active', true)->get();
            });
            $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
            $lims_tax_list = Cache::remember('tax_list', 60 * 60 * 24 * 30, function () {
                return Tax::where('is_active', true)->get();
            });
            $lims_pos_setting_data = Cache::remember('pos_setting', 60 * 60 * 24 * 30, function () {
                return PosSetting::latest()->first();
            });

            // Effective warehouse priority:
            // assigned warehouse -> draft warehouse -> explicit selection -> POS default -> first accessible.
            $requestedWarehouseId = (int) request()->query('warehouse_id', 0);
            $selectedPosWarehouseId = null;

            if ((int) Auth::user()->role_id > 2 && Auth::user()->warehouse_id) {
                $selectedPosWarehouseId = (int) Auth::user()->warehouse_id;
            } elseif (!empty($id)) {
                $draftWarehouseId = (int) Sale::whereKey($id)->value('warehouse_id');
                if ($draftWarehouseId && $lims_warehouse_list->contains('id', $draftWarehouseId)) {
                    $selectedPosWarehouseId = $draftWarehouseId;
                }
            }

            if (!$selectedPosWarehouseId && $requestedWarehouseId && $lims_warehouse_list->contains('id', $requestedWarehouseId)) {
                $selectedPosWarehouseId = $requestedWarehouseId;
            }

            if (
                !$selectedPosWarehouseId &&
                $lims_pos_setting_data &&
                $lims_pos_setting_data->warehouse_id &&
                $lims_warehouse_list->contains('id', (int) $lims_pos_setting_data->warehouse_id)
            ) {
                $selectedPosWarehouseId = (int) $lims_pos_setting_data->warehouse_id;
            }

            if (!$selectedPosWarehouseId && $lims_warehouse_list->isNotEmpty()) {
                $selectedPosWarehouseId = (int) $lims_warehouse_list->first()->id;
            }

            $selectedPosWarehouse = $selectedPosWarehouseId
                ? $lims_warehouse_list->firstWhere('id', $selectedPosWarehouseId)
                : null;

            $draftSale = !empty($id) ? Sale::find($id) : null;
            $restaurantAvailable = $this->restaurantModuleAvailable();

            $requestedWorkflow = request()->query('workflow');
            if (!$requestedWorkflow && $draftSale && in_array($draftSale->pos_workflow, ['retail', 'restaurant'], true)) {
                $requestedWorkflow = $draftSale->pos_workflow;
            }

            // pos_type is the warehouse-level authority. Dedicated warehouses force
            // their workflow; hybrid warehouses honor an explicit POS workflow.
            $activeWorkflow = $selectedPosWarehouse
                ? $this->resolvePosWorkflow($selectedPosWarehouse, $requestedWorkflow)
                : 'retail';
            $isRestaurantWorkflow = $activeWorkflow === 'restaurant';
            $isRestaurantWarehouse = $isRestaurantWorkflow;
            $selectedPosType = strtolower((string) ($selectedPosWarehouse->pos_type ?? 'regular'));
            $showWorkflowModal = $restaurantAvailable
                && $selectedPosType === 'both'
                && !in_array($requestedWorkflow, ['retail', 'restaurant'], true);
            $restaurantModuleMissingForRestaurant =
                $selectedPosWarehouse
                && in_array($selectedPosType, ['restaurant', 'both'], true)
                && !$restaurantAvailable;

            if ($lims_pos_setting_data)
                $options = explode(',', $lims_pos_setting_data->payment_options);
            else
                $options = [];

            $lims_brand_list = Cache::remember('brand_list', 60 * 60 * 24 * 30, function () {
                return Brand::where('is_active', true)->get();
            });
            $lims_category_list = cache()->get('categories_list');
            $lims_expense_category_list = Cache::remember('expense_category_list', 60 * 60 * 24, function () {
                return ExpenseCategory::where('is_active', true)->get();
            });

            if ($isRestaurantWorkflow) {
                $lims_table_list = Table::join('floors', 'tables.floor_id', '=', 'floors.id')
                    ->select('tables.id as id', 'tables.name', 'tables.number_of_person', 'floors.name as floor')
                    ->where('tables.is_active', 1)
                    ->where('floors.warehouse_id', $selectedPosWarehouseId)
                    ->get();

                $service_list = DB::table('services')->where('is_active', 1)->get();
                $waiter_list = DB::table('users')
                    ->where('service_staff', 1)
                    ->where('is_active', 1)
                    ->get();
            } else {
                $lims_table_list = collect();
                $service_list = collect();
                $waiter_list = collect();
            }

            $lims_coupon_list = Cache::remember('coupon_list', 60 * 60 * 24 * 30, function () {
                return Coupon::where('is_active', true)->get();
            });
            $flag = 0;
            $currency_list = cache()->get('currency_list');

            $numberOfInvoice = Sale::whereNull('sales.deleted_at')
                ->where(function ($q) {
                    $q->where('sales.sale_type', '!=', 'opening balance')
                        ->orWhereNull('sales.sale_type');
                })->count();
            $custom_fields = CustomField::where('belongs_to', 'sale')->get();

            $variables = [
                'currency_list', 'role', 'all_permission', 'lims_customer_list', 'lims_customer_group_all',
                'lims_warehouse_list', 'lims_reward_point_setting_data', 'lims_tax_list', 'lims_biller_list',
                'lims_pos_setting_data', 'options', 'lims_brand_list', 'lims_category_list', 'lims_table_list',
                'lims_coupon_list', 'flag', 'numberOfInvoice', 'custom_fields', 'lims_account_list',
                'lims_expense_category_list', 'selectedPosWarehouseId', 'selectedPosWarehouse', 'isRestaurantWarehouse',
                'isRestaurantWorkflow', 'activeWorkflow', 'showWorkflowModal', 'restaurantModuleMissingForRestaurant',
            ];

            if (!empty($id)) {
                $lims_sale_data = Sale::find($id);
                $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
                $variables[] = 'lims_sale_data';
                $variables[] = 'lims_product_sale_data';

                // $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
                $draft_product_data = [];
                $draft_product_discount = [
                    'order_discount' => $lims_sale_data->order_discount,
                    'discount' => []
                ];

                $draft_product_data = [];

                foreach ($lims_product_sale_data as $product_sale) {
                    $draft_product_discount['discount'][$product_sale->product_id] = $product_sale->discount;

                    $draft_product_list = Product::join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
                        ->where('products.id', $product_sale->product_id)
                        ->select('products.id', 'products.code', 'product_warehouse.qty')
                        ->first();

                    $product_code = $draft_product_list->code;

                    if ($product_sale->variant_id) {
                        $product_variant_data = ProductVariant::select('id', 'item_code')
                            ->FindExactProduct($draft_product_list->id, $product_sale->variant_id)
                            ->first();
                        $product_code = $product_variant_data->item_code;
                    }

                    for ($i = 0; $i < $product_sale->qty; $i++) {
                        if (!empty($product_sale->imei_number)) {
                            $imei_numbers = explode(",", $product_sale->imei_number);
                            foreach ($imei_numbers as $key => $number) {
                                $draft_product_data[] = [
                                    'code'     => $product_code,
                                    'qty'      => $draft_product_list->qty,
                                    'imei'     => $number ?: null,
                                    'embedded' => 0,
                                    'batch'    => $product_sale->product_batch_id,
                                    'price'    => $product_sale->net_unit_price, // or ->price depending on field
                                ];
                            }
                        } else {
                            $draft_product_data[] = [
                                'code'     => $product_code,
                                'qty'      => $draft_product_list->qty,
                                'imei'     => null,
                                'embedded' => 0,
                                'batch'    => $product_sale->product_batch_id,
                                'price'    => $product_sale->net_unit_price, // or ->price depending on field
                            ];
                        }
                    }
                }


                $variables[] = 'draft_product_data';
                $variables[] = 'draft_product_discount';
            }

            if ($isRestaurantWarehouse) {
                $variables[] = 'service_list';
                $variables[] = 'waiter_list';
            }

            // One POS view. Warehouse.pos_type controls the workflow.
            return view('backend.sale.pos', compact(...$variables));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function recentSale()
    {
        if ($this->restaurantModuleAvailable()) {
            if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $recent_sale = Sale::join('customers', 'sales.customer_id', '=', 'customers.id')->select('sales.id', 'sales.reference_no', 'sales.customer_id', 'sales.grand_total', 'sales.created_at', 'customers.name')->where([
                    ['sales.sale_status', 1],
                    ['sales.user_id', Auth::id()]
                ])
                    ->whereNull('sales.deleted_at')
                    ->where(function ($q) {
                        $q->where('sales.sale_type', '!=', 'opening balance')
                            ->orWhereNull('sales.sale_type');
                    })
                    ->orderBy('id', 'desc')
                    ->take(10)->get();
                return response()->json($recent_sale);
            } else {
                $recent_sale = Sale::join('customers', 'sales.customer_id', '=', 'customers.id')->select('sales.id', 'sales.reference_no', 'sales.customer_id', 'sales.grand_total', 'sales.created_at', 'customers.name')
                    ->where('sale_status', 1)
                    ->whereNull('sales.deleted_at')
                    ->where(function ($q) {
                        $q->where('sales.sale_type', '!=', 'opening balance')
                            ->orWhereNull('sales.sale_type');
                    })
                    ->orderBy('id', 'desc')
                    ->take(10)
                    ->get();
                return response()->json($recent_sale);
            }
        } else {
            if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $recent_sale = Sale::join('customers', 'sales.customer_id', '=', 'customers.id')->select('sales.id', 'sales.reference_no', 'sales.customer_id', 'sales.grand_total', 'sales.created_at', 'customers.name')
                    ->where([
                        ['sales.sale_status', 1],
                        ['sales.user_id', Auth::id()]
                    ])
                    ->whereNull('sales.deleted_at')
                    ->where(function ($q) {
                        $q->where('sales.sale_type', '!=', 'opening balance')
                            ->orWhereNull('sales.sale_type');
                    })
                    ->orderBy('id', 'desc')
                    ->take(10)
                    ->get();
                return response()->json($recent_sale);
            } else {
                $recent_sale = Sale::join('customers', 'sales.customer_id', '=', 'customers.id')->select('sales.id', 'sales.reference_no', 'sales.customer_id', 'sales.grand_total', 'sales.created_at', 'customers.name')
                    ->whereNull('sales.deleted_at')
                    ->where(function ($q) {
                        $q->where('sales.sale_type', '!=', 'opening balance')
                            ->orWhereNull('sales.sale_type');
                    })
                    ->where('sale_status', 1)
                    ->orderBy('id', 'desc')
                    ->take(10)
                    ->get();
                return response()->json($recent_sale);
            }
        }
    }

    public function recentDraft()
    {
        if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
            $recent_draft = Sale::join('customers', 'sales.customer_id', '=', 'customers.id')->select('sales.id', 'sales.reference_no', 'sales.customer_id', 'sales.grand_total', 'sales.created_at', 'customers.name')->where([
                ['sales.sale_status', 3],
                ['sales.user_id', Auth::id()]
            ])->whereNull('sales.deleted_at')->orderBy('id', 'desc')->take(10)->get();
            return response()->json($recent_draft);
        } else {
            $recent_draft = Sale::join('customers', 'sales.customer_id', '=', 'customers.id')->select('sales.id', 'sales.reference_no', 'sales.customer_id', 'sales.grand_total', 'sales.created_at', 'customers.name')->whereNull('sales.deleted_at')->where('sale_status', 3)->orderBy('id', 'desc')->take(10)->get();
            return response()->json($recent_draft);
        }
    }

    public function createSale($id)
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('sales-edit')) {
            $lims_biller_list = Biller::where('is_active', true)->get();
            $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
            $lims_customer_list = Customer::where('is_active', true)->get();
            $lims_customer_group_all = CustomerGroup::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_sale_data = Sale::findOrFail($id);
            $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
            $lims_product_list = Product::where([
                ['featured', 1],
                ['is_active', true]
            ])->get();
            foreach ($lims_product_list as $key => $product) {
                $images = array_values(array_filter(array_map('trim', explode(",", $product->image ?? ''))));
                if (!empty($images))
                    $product->base_image = $images[0];
                else
                    $product->base_image = 'zummXD2dvAtI.png';
            }
            $product_number = count($lims_product_list);
            $lims_pos_setting_data = PosSetting::latest()->first();
            $lims_brand_list = Brand::where('is_active', true)->get();
            $lims_category_list = Category::where('is_active', true)->get();
            $lims_coupon_list = Coupon::where('is_active', true)->get();

            $currency_list = cache()->get('currency_list');

            return view('backend.sale.create_sale', compact('currency_list', 'lims_biller_list', 'lims_customer_list', 'lims_warehouse_list', 'lims_tax_list', 'lims_sale_data', 'lims_product_sale_data', 'lims_pos_setting_data', 'lims_brand_list', 'lims_category_list', 'lims_coupon_list', 'lims_product_list', 'product_number', 'lims_customer_group_all', 'lims_reward_point_setting_data'));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function getProducts($warehouse_id, $key, $cat_or_brand_id)
    {
        $withoutStock = config('without_stock');
        $selectedWarehouse = $warehouse_id ? Warehouse::find($warehouse_id) : null;
        $requestedWorkflow = request()->query('workflow') ?? request()->input('workflow');
        $activeWorkflow = $selectedWarehouse ? $this->resolvePosWorkflow($selectedWarehouse, $requestedWorkflow) : 'retail';
        $isRestaurantWarehouse = ($activeWorkflow === 'restaurant');

        /*
        |--------------------------------------------------------------------------
        | Standard / Variant / Batch Products
        |--------------------------------------------------------------------------
        */

        $query = Product::leftJoin('product_warehouse', function ($join) use ($warehouse_id) {
            $join->on('products.id', '=', 'product_warehouse.product_id')
                ->where('product_warehouse.warehouse_id', '=', $warehouse_id);
        })
            ->leftJoin(
                'product_batches',
                'product_warehouse.product_batch_id',
                '=',
                'product_batches.id'
            )
            ->where('products.is_active', 1);

        /*
        |--------------------------------------------------------------------------
        | Service / Digital Products
        |--------------------------------------------------------------------------
        */

        $query2 = Product::where('products.is_active', 1)
            ->whereIn('products.type', ['service', 'digital']);

        if ($isRestaurantWarehouse) {
            $restaurantCategoryIds = Category::where('id', 83)
                ->orWhere('parent_id', 83)
                ->pluck('id')
                ->toArray();

            $restaurantFilter = function ($q) use ($restaurantCategoryIds) {
                $q->whereIn('products.category_id', $restaurantCategoryIds)
                    ->orWhereNotNull('products.kitchen_id')
                    ->orWhereNotNull('products.menu_type');
            };

            $query = $query->where($restaurantFilter);
            $query2 = $query2->where($restaurantFilter);
        }

        /*
        |--------------------------------------------------------------------------
        | Category / Brand / Featured Filters
        |--------------------------------------------------------------------------
        */

        if ($key == 'category') {
            $query = $query->join(
                'categories',
                'products.category_id',
                '=',
                'categories.id'
            )
                ->where(function ($query) use ($cat_or_brand_id) {
                    $query->where('products.category_id', $cat_or_brand_id)
                        ->orWhere('categories.parent_id', $cat_or_brand_id);
                });

            $query2 = $query2->join(
                'categories',
                'products.category_id',
                '=',
                'categories.id'
            )
                ->where(function ($query) use ($cat_or_brand_id) {
                    $query->where('products.category_id', $cat_or_brand_id)
                        ->orWhere('categories.parent_id', $cat_or_brand_id);
                });
        } elseif ($key == 'brand') {
            $query = $query->where('products.brand_id', $cat_or_brand_id);
            $query2 = $query2->where('products.brand_id', $cat_or_brand_id);
        } elseif ($key == 'featured') {
            $query = $query->where('products.featured', true);
            $query2 = $query2->where('products.featured', true);
        }

        /*
        |--------------------------------------------------------------------------
        | Exclude Combo / IMEI Products From Main Stock Query
        |--------------------------------------------------------------------------
        */

        $query = $query
            ->where('products.type', '!=', 'combo')
            ->where(function ($q) {
                $q->whereNull('products.is_imei')
                    ->orWhere('products.is_imei', 0);
            });

        /*
        |--------------------------------------------------------------------------
        | Stock Filter
        |--------------------------------------------------------------------------
        |
        | without_stock = no:
        |     Only products with positive warehouse stock.
        |
        | without_stock = yes:
        |     Products are allowed even when there is no product_warehouse row.
        |
        */

        if ($withoutStock == 'no') {
            $query = $query->where('product_warehouse.qty', '>', 0);
        }

        /*
        |--------------------------------------------------------------------------
        | Expiry Filter
        |--------------------------------------------------------------------------
        |
        | Show product if:
        | - it has no batch
        | - OR its batch has no expiry
        | - OR expiry is today/future
        |
        */

        $query = $query->where(function ($q) {
            $q->whereNull('product_warehouse.product_batch_id')
                ->orWhereNull('product_batches.expired_date')
                ->orWhereDate(
                    'product_batches.expired_date',
                    '>=',
                    now()->toDateString()
                );
        });

        $query = $query->groupBy(
            'products.id',
            'product_warehouse.product_batch_id',
            'product_batches.expired_date'
        );

        /*
        |--------------------------------------------------------------------------
        | Main Product Query
        |--------------------------------------------------------------------------
        */

        $stockQuery = $query->select(
            'products.id',
            'products.code',
            'products.name',
            'products.type',
            'products.is_imei',
            'products.is_embeded',
            'products.image',
            'products.qty',
            'products.price',
            'products.is_variant',
            'product_warehouse.product_batch_id',
            'product_batches.expired_date',
            DB::raw('0 as is_service')
        );

        /*
        |--------------------------------------------------------------------------
        | Service / Digital Query
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | UNION column order must exactly match $stockQuery.
        |
        */

        $serviceQuery = $query2->select(
            'products.id',
            'products.code',
            'products.name',
            'products.type',
            'products.is_imei',
            'products.is_embeded',
            'products.image',
            DB::raw('NULL as qty'),
            'products.price',
            'products.is_variant',
            DB::raw('NULL as product_batch_id'),
            DB::raw('NULL as expired_date'),
            DB::raw('1 as is_service')
        );

        $lims_product_list = $stockQuery
            ->unionAll($serviceQuery)
            ->orderBy('name', 'asc')
            ->simplePaginate(15);

        $index = 0;
        $data = [];

        /*
        |--------------------------------------------------------------------------
        | Build POS Product Data
        |--------------------------------------------------------------------------
        */

        foreach ($lims_product_list as $product) {

            /*
            |--------------------------------------------------------------------------
            | Variant Product
            |--------------------------------------------------------------------------
            */

            if ($product->is_variant) {

                $product_variants = ProductVariant::leftJoin(
                    'product_warehouse',
                    function ($join) use ($warehouse_id, $product) {
                        $join->on(
                            'product_variants.product_id',
                            '=',
                            'product_warehouse.product_id'
                        )
                            ->on(
                                'product_variants.variant_id',
                                '=',
                                'product_warehouse.variant_id'
                            )
                            ->where(
                                'product_warehouse.warehouse_id',
                                '=',
                                $warehouse_id
                            )
                            ->where(
                                'product_warehouse.product_id',
                                '=',
                                $product->id
                            );
                    }
                )
                    ->join(
                        'variants',
                        'product_variants.variant_id',
                        '=',
                        'variants.id'
                    )
                    ->where(
                        'product_variants.product_id',
                        $product->id
                    )
                    ->when($withoutStock == 'no', function ($q) {
                        $q->whereNotNull('product_warehouse.product_id')
                            ->where('product_warehouse.qty', '>', 0);
                    })
                    ->orderBy('product_variants.position')
                    ->select(
                        'product_variants.*',
                        'product_warehouse.qty',
                        'product_warehouse.price as warehouse_price',
                        'product_warehouse.product_batch_id',
                        'variants.name'
                    )
                    ->groupBy('product_variants.id')
                    ->get();

                $variant_list = [];

                foreach ($product_variants as $variant) {

                    $variantPrice = $variant->warehouse_price !== null
                        ? $variant->warehouse_price
                        : (
                            (float) $product->price
                            + (float) $variant->additional_price
                        );

                    $variant_list[] = [
                        'name' => $product->name . ' [' . $variant->name . ']',
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'variant_id' => $variant->variant_id,
                        'warehouse_id' => $warehouse_id,
                        'code' => $variant->item_code,
                        'is_imei' => $product->is_imei,
                        'is_embeded' => $product->is_embeded,
                        'qty' => $variant->qty !== null
                            ? $variant->qty
                            : 0,
                        'price' => $variantPrice,
                        'batch' => $variant->product_batch_id ?? null,
                        'type' => $product->type,
                    ];
                }

                if (count($variant_list) > 0) {
                    $data['name'][$index] = $product->name;
                    $data['code'][$index] = $product->code;
                    $data['is_imei'][$index] = $product->is_imei;
                    $data['is_embeded'][$index] = $product->is_embeded;

                    $images = array_values(array_filter(array_map('trim', explode(',', $product->image ?? ''))));
                    $data['image'][$index] = $images[0] ?? 'zummXD2dvAtI.png';

                    $data['qty'][$index] = count($variant_list) . ' Variants';
                    $data['price'][$index] = 0;
                    $data['batch'][$index] = '';
                    $data['type'][$index] = 'variant_parent';
                    $data['variants'][$index] = json_encode($variant_list);

                    $index++;
                }

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Service / Digital Product
            |--------------------------------------------------------------------------
            */

            if (
                $product->type == 'service'
                || $product->type == 'digital'
            ) {
                $data['name'][$index] = $product->name;
                $data['code'][$index] = $product->code;
                $data['is_imei'][$index] = 0;
                $data['is_embeded'][$index] = $product->is_embeded;

                $images = array_values(array_filter(array_map('trim', explode(',', $product->image ?? ''))));
                $data['image'][$index] = $images[0] ?? 'zummXD2dvAtI.png';

                $data['qty'][$index] = '∞';
                $data['price'][$index] = $product->price ?? 0;
                $data['batch'][$index] = '';
                $data['type'][$index] = $product->type;

                $index++;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Standard Non-Variant Product
            |--------------------------------------------------------------------------
            */

            $pw = Product_Warehouse::where([
                ['product_id', $product->id],
                ['warehouse_id', $warehouse_id],
            ])->first();

            /*
            * When stock enforcement is ON, warehouse stock must exist
            * and quantity must be greater than zero.
            *
            * When without_stock = yes, lack of product_warehouse record
            * must NOT prevent the product from appearing.
            */
            if (
                $withoutStock == 'no'
                && (!$pw || $pw->qty <= 0)
            ) {
                continue;
            }

            $data['name'][$index] = $product->name;
            $data['code'][$index] = $product->code;
            $data['is_imei'][$index] = $product->is_imei;
            $data['is_embeded'][$index] = $product->is_embeded;

            $images = array_values(array_filter(array_map('trim', explode(',', $product->image ?? ''))));
            $data['image'][$index] = $images[0] ?? 'zummXD2dvAtI.png';

            $data['qty'][$index] = $pw
                ? $pw->qty
                : 0;

            /*
            * Use warehouse-specific price when available.
            * Otherwise fall back to the product's base price.
            *
            * Explicit NULL comparison allows a legitimate price of 0.
            */
            $data['price'][$index] = (
                $pw && $pw->price !== null
            )
                ? $pw->price
                : ($product->price ?? 0);

            $data['batch'][$index] = $pw
                ? $pw->product_batch_id
                : '';

            $data['type'][$index] = $product->type;

            $index++;
        }

        /*
        |--------------------------------------------------------------------------
        | Combo Products
        |--------------------------------------------------------------------------
        |
        | Calculate maximum available combo quantity based on the
        | least-available child component.
        |
        */

        $comboQuery = Product::where(
            'products.is_active',
            true
        )
            ->where(
                'products.type',
                'combo'
            );

        if ($key == 'category') {
            $comboQuery = $comboQuery
                ->join(
                    'categories',
                    'products.category_id',
                    '=',
                    'categories.id'
                )
                ->where(function ($q) use ($cat_or_brand_id) {
                    $q->where(
                        'products.category_id',
                        $cat_or_brand_id
                    )
                        ->orWhere(
                            'categories.parent_id',
                            $cat_or_brand_id
                        );
                });
        } elseif ($key == 'brand') {
            $comboQuery = $comboQuery
                ->where(
                    'products.brand_id',
                    $cat_or_brand_id
                );
        } elseif ($key == 'featured') {
            $comboQuery = $comboQuery
                ->where(
                    'products.featured',
                    true
                );
        }

        if ($isRestaurantWarehouse) {
            $comboQuery = $comboQuery->where(function ($q) use ($restaurantCategoryIds) {
                $q->whereIn('products.category_id', $restaurantCategoryIds)
                    ->orWhereNotNull('products.kitchen_id')
                    ->orWhereNotNull('products.menu_type');
            });
        }

        $combos = $comboQuery->select(
            'products.id',
            'products.code',
            'products.name',
            'products.image',
            'products.is_imei',
            'products.is_embeded',
            'products.price',
            'products.product_list',
            'products.qty_list',
            'products.combo_unit_id'
        )->get();

        /*
        |--------------------------------------------------------------------------
        | Preload Combo Component IDs
        |--------------------------------------------------------------------------
        */

        $comboComponentIds = [];

        foreach ($combos as $combo) {
            $ids = array_filter(
                explode(',', $combo->product_list ?? '')
            );

            foreach ($ids as $cid) {
                $comboComponentIds[] = (int) $cid;
            }
        }

        $comboComponentIds = array_unique(
            $comboComponentIds
        );

        /*
        |--------------------------------------------------------------------------
        | Preload Warehouse Stocks / Child Products
        |--------------------------------------------------------------------------
        */

        $warehouseStocks = [];
        $childProducts = [];

        if (!empty($comboComponentIds)) {

            $stockRows = DB::table('product_warehouse')
                ->where(
                    'warehouse_id',
                    $warehouse_id
                )
                ->whereIn(
                    'product_id',
                    $comboComponentIds
                )
                ->select(
                    'product_id',
                    'qty'
                )
                ->get()
                ->groupBy('product_id');

            foreach ($stockRows as $productId => $rows) {
                $warehouseStocks[$productId] = $rows->sum('qty');
            }

            $childProducts = Product::whereIn(
                'id',
                $comboComponentIds
            )
                ->select(
                    'id',
                    'unit_id'
                )
                ->get()
                ->keyBy('id');
        }

        $allUnits = Unit::all()->keyBy('id');

        /*
        |--------------------------------------------------------------------------
        | Calculate Combo Availability
        |--------------------------------------------------------------------------
        */

        foreach ($combos as $combo) {

            $componentIds = array_values(
                array_filter(
                    explode(',', $combo->product_list ?? '')
                )
            );

            $requiredQtys = array_values(
                array_filter(
                    explode(',', $combo->qty_list ?? '')
                )
            );

            $comboUnitIds = $combo->combo_unit_id
                ? array_values(
                    array_filter(
                        explode(',', $combo->combo_unit_id)
                    )
                )
                : [];

            $minAvailable = PHP_INT_MAX;

            foreach ($componentIds as $i => $compId) {

                $required = isset($requiredQtys[$i])
                    ? (float) $requiredQtys[$i]
                    : 1.0;

                $stock = $warehouseStocks[$compId] ?? 0;

                $child = $childProducts[$compId] ?? null;

                if ($child) {
                    $comboUnitId = $comboUnitIds[$i] ?? null;

                    if (
                        $comboUnitId
                        && $comboUnitId != $child->unit_id
                    ) {
                        $unit = $allUnits[$comboUnitId] ?? null;

                        if ($unit) {
                            if ($unit->operator == '*') {
                                $required =
                                    $required
                                    * $unit->operation_value;
                            } elseif ($unit->operator == '/') {
                                $required =
                                    $required
                                    / $unit->operation_value;
                            }
                        }
                    }
                }

                if ($stock <= 0) {
                    $minAvailable = 0;
                    break;
                }

                $minAvailable = min(
                    $minAvailable,
                    (int) floor(
                        $stock / max(0.0001, $required)
                    )
                );
            }

            $comboQty = (
                $minAvailable === PHP_INT_MAX
            )
                ? 0
                : $minAvailable;

            /*
            * When stock enforcement is enabled,
            * do not show unavailable combos.
            *
            * When without_stock = yes,
            * combos remain available with qty = 0.
            */
            if (
                $withoutStock == 'no'
                && $comboQty <= 0
            ) {
                continue;
            }

            $images = array_values(array_filter(array_map('trim', explode(',', $combo->image ?? ''))));

            $data['name'][$index] = $combo->name;
            $data['code'][$index] = $combo->code;
            $data['is_imei'][$index] = 0;
            $data['is_embeded'][$index] = 0;
            $data['image'][$index] = $images[0] ?? 'zummXD2dvAtI.png';
            $data['qty'][$index] = $comboQty;
            $data['price'][$index] = $combo->price;
            $data['batch'][$index] = '';
            $data['type'][$index] = 'combo';

            $index++;
        }

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'data' => $data,
            'next_page_url' => $lims_product_list->nextPageUrl(),
        ]);
    }

    public function getCustomerGroup($id)
    {
        $lims_customer_data = Customer::find($id);
        $lims_customer_group_data = CustomerGroup::find($lims_customer_data->customer_group_id);
        return $lims_customer_group_data->percentage;
    }

    public function offlineProductsData($warehouse_id)
    {
        $query = Product::leftJoin('product_warehouse', function ($join) use ($warehouse_id) {
            $join->on('products.id', '=', 'product_warehouse.product_id')
                ->where('product_warehouse.warehouse_id', '=', $warehouse_id);
        })
            ->leftJoin('product_batches', 'product_warehouse.product_batch_id', '=', 'product_batches.id')
            ->where('products.is_active', 1);

        $query2 = Product::where('products.is_active', 1)->whereIn('products.type', ['service', 'digital']);

        $query = $query->where('products.type', '!=', 'combo')
            ->where(function ($q) {
                $q->whereNull('products.is_imei')
                    ->orWhere('products.is_imei', 0);
            });

        if (config('without_stock') == 'no') {
            $query = $query->where('product_warehouse.qty', '>', 0);
        }

        $query = $query->where(function ($q) {
            $q->whereNull('product_warehouse.product_batch_id')
                ->orWhereNull('product_batches.expired_date')
                ->orWhereDate('product_batches.expired_date', '>=', now()->toDateString());
        });

        $query = $query->groupBy('products.id', 'product_warehouse.product_batch_id', 'product_batches.expired_date');

        $stockQuery = $query->select(
            'products.id',
            'products.code',
            'products.name',
            'products.type',
            'products.is_imei',
            'products.is_embeded',
            'products.image',
            'products.qty',
            'products.is_variant',
            'product_warehouse.product_batch_id',
            'product_batches.expired_date',
            'products.price',
            'products.wholesale_price',
            'products.cost',
            'products.promotion',
            'products.promotion_price',
            'products.last_date',
            'products.tax_id',
            'products.tax_method',
            'products.unit_id',
            'products.sale_unit_id',
            'products.is_batch',
            'products.category_id',
            'products.brand_id',
            'products.featured',
            DB::raw('0 as is_service')
        );

        $serviceQuery = $query2->select(
            'products.id',
            'products.code',
            'products.name',
            'products.type',
            'products.is_imei',
            'products.is_embeded',
            'products.image',
            DB::raw('NULL as qty'),
            DB::raw('NULL as product_batch_id'),
            DB::raw('NULL as expired_date'),
            'products.price',
            'products.wholesale_price',
            'products.cost',
            'products.promotion',
            'products.promotion_price',
            'products.last_date',
            'products.tax_id',
            'products.tax_method',
            'products.unit_id',
            'products.sale_unit_id',
            'products.is_batch',
            'products.is_variant',
            'products.category_id',
            'products.brand_id',
            'products.featured',
            DB::raw('1 as is_service')
        );

        $lims_product_list = $stockQuery->unionAll($serviceQuery)->orderBy('name', 'asc')->get();

        $taxes = Tax::all()->keyBy('id');
        $units = Unit::all()->keyBy('id');

        $data = [];

        foreach ($lims_product_list as $product) {
            $taxRate = 0;
            $taxName = 'No Tax';
            if ($product->tax_id && isset($taxes[$product->tax_id])) {
                $taxRate = $taxes[$product->tax_id]->rate;
                $taxName = $taxes[$product->tax_id]->name;
            }

            $unitNames = $unitOperators = $unitValues = [];
            if (in_array($product->type, ['standard', 'combo'])) {
                $product_units = $units->where('base_unit', $product->unit_id)->union($units->where('id', $product->unit_id));
                foreach ($product_units as $unit) {
                    if ($product->sale_unit_id == $unit->id) {
                        array_unshift($unitNames, $unit->unit_name);
                        array_unshift($unitOperators, $unit->operator);
                        array_unshift($unitValues, $unit->operation_value);
                    } else {
                        $unitNames[] = $unit->unit_name;
                        $unitOperators[] = $unit->operator;
                        $unitValues[] = $unit->operation_value;
                    }
                }
            } else {
                $unitNames = $unitOperators = $unitValues = ['n/a'];
            }

            $tax_unit_info = [
                'taxRate' => $taxRate,
                'taxName' => $taxName,
                'unitNames' => implode(',', $unitNames) . ',',
                'unitOperators' => implode(',', $unitOperators) . ',',
                'unitValues' => implode(',', $unitValues) . ',',
            ];

            if ($product->is_variant) {
                $product_variants = ProductVariant::join('product_warehouse', function ($join) use ($warehouse_id, $product) {
                    $join->on('product_variants.product_id', '=', 'product_warehouse.product_id')
                        ->on('product_variants.variant_id', '=', 'product_warehouse.variant_id')
                        ->where('product_warehouse.warehouse_id', $warehouse_id)
                        ->where('product_warehouse.product_id', $product->id);
                })
                    ->join('variants', 'product_variants.variant_id', '=', 'variants.id')
                    ->where('product_variants.product_id', $product->id)
                    ->when(config('without_stock') == 'no', function ($q) {
                        $q->where('product_warehouse.qty', '>', 0);
                    })
                    ->orderBy('product_variants.position')
                    ->select('product_variants.*', 'product_warehouse.qty as warehouse_qty', 'product_warehouse.price as warehouse_price', 'product_warehouse.product_batch_id', 'variants.name')
                    ->groupBy('product_variants.id')
                    ->get();

                foreach ($product_variants as $variant) {
                    $data[$variant->item_code] = array_merge((array)$product->toArray(), (array)$variant->toArray(), $tax_unit_info);
                    $data[$variant->item_code]['actual_name'] = $product->name . ' [' . $variant->name . ']';
                    $data[$variant->item_code]['search_code'] = $variant->item_code;
                }
            } elseif ($product->type == 'service' || $product->type == 'digital') {
                $data[$product->code] = array_merge((array)$product->toArray(), $tax_unit_info);
                $data[$product->code]['actual_name'] = $product->name;
                $data[$product->code]['search_code'] = $product->code;
                $data[$product->code]['warehouse_qty'] = '∞';
                $data[$product->code]['warehouse_price'] = $product->price ?? 0;
            } else {
                $pw = Product_Warehouse::where([
                    ['product_id', $product->id],
                    ['warehouse_id', $warehouse_id]
                ])->first();

                if (!$pw || (config('without_stock') == 'no' && $pw->qty <= 0)) {
                    continue;
                }

                $data[$product->code] = array_merge((array)$product->toArray(), $tax_unit_info);
                $data[$product->code]['actual_name'] = $product->name;
                $data[$product->code]['search_code'] = $product->code;
                $data[$product->code]['warehouse_qty'] = $pw->qty;
                $data[$product->code]['warehouse_price'] = $pw->price;
            }
        }

        return response()->json($data);
    }

    public function limsProductSearch(Request $request)
    {
        $todayDate = date('Y-m-d');

        $code = $request->data['code'];
        $qty = $request->data['qty'];
        $is_embedded = $request->data['embedded'];
        $batch_id = $request->data['batch'] ?? null;
        $customerId = $request->data['customer_id'];
        $productVariantId = null;
        $qty = ($is_embedded == 1)
            ? ((float) substr($code, 7, 5)) / 1000
            : $request->data['pre_qty'];

        if ($is_embedded == 1) {
            $code = substr($code, 0, 7);
        }

        // Fetch customer discounts
        $discounts = cache()->remember("customer_discounts_{$customerId}", 300, function () use ($customerId) {
            return DB::table('discount_plan_customers')
                ->join('discount_plans', 'discount_plan_customers.discount_plan_id', '=', 'discount_plans.id')
                ->join('discount_plan_discounts', 'discount_plans.id', '=', 'discount_plan_discounts.discount_plan_id')
                ->join('discounts', 'discounts.id', '=', 'discount_plan_discounts.discount_id')
                ->where([
                    ['discount_plans.is_active', true],
                    ['discounts.is_active', true],
                    ['discount_plan_customers.customer_id', $customerId]
                ])
                ->select('discounts.*')
                ->get();
        });

        $general_setting = gen_setting();

        $productWarehouseId = (int) ($request->data['warehouse_id'] ?? 0);
        $wh = $productWarehouseId > 0 ? Warehouse::find($productWarehouseId) : null;
        $requestedWorkflow = $request->input('workflow') ?? ($request->input('pos_workflow') ?? ($request->data['workflow'] ?? null));
        $workflow = $wh ? $this->resolvePosWorkflow($wh, $requestedWorkflow) : 'retail';
        $isRestaurant = ($workflow === 'restaurant');

        // ✅ Single query with eager-loaded tax & unit
        $selectFields = [
            'id',
            'name',
            'code',
            'is_variant',
            'is_batch',
            'is_imei',
            'qty',
            'price',
            'wholesale_price',
            'cost',
            'promotion',
            'promotion_price',
            'last_date',
            'tax_id',
            'tax_method',
            'type',
            'unit_id',
            'sale_unit_id'
        ];

        // Temporary backward compatibility for historical sales that still use
        // the retired product extras/toppings format. New modifier groups use
        // $productArray['modifiers'] below.
        if ($isRestaurant) $selectFields[] = 'extras';

        // ✅ Eager-load tax and units in one shot
        $product = Product::select($selectFields)
            ->with([
                'tax:id,rate,name',
                'unit:id,unit_name,operator,operation_value,base_unit',
            ])
            ->where('code', $code)
            ->where('products.is_active', 1)
            ->first();

        // variant fallback
        $productVariantId = null;
        if (!$product) {
            $variantQuery = Product::with(['tax:id,rate,name'])
                ->join('product_variants', 'products.id', '=', 'product_variants.product_id')
                ->select('products.*', 'product_variants.id as product_variant_id', 'product_variants.item_code')
                ->where('product_variants.item_code', $code)
                ->where('products.is_active', 1);
            if (!empty($request->data['product_id'])) {
                $variantQuery->where('product_variants.product_id', $request->data['product_id']);
            }
            if (!empty($request->data['product_variant_id'])) {
                $variantQuery->where('product_variants.id', $request->data['product_variant_id']);
            }
            if (!empty($request->data['variant_id'])) {
                $variantQuery->where('product_variants.variant_id', $request->data['variant_id']);
            }
            $product = $variantQuery->first();
            if ($product) $productVariantId = $product->product_variant_id;
        }
        if (!$product) return response()->json(['error' => 'Product not found'], 404);

        // Handle pricing
        if (!empty($request->data['price']) && $request->data['price'] > 0)
            $price = $request->data['price'];
        else
            $price = $product->price;

        // if ($product->is_variant && isset($product->additional_price)) {
        //     $price += $product->additional_price;
        // }

        $discountedPrice = null;
        $noDiscountApplied = true;

        foreach ($discounts as $discount) {
            $applicableProducts = explode(',', $discount->product_list);
            $applicableDays = explode(',', $discount->days);
            $todayDay = date('D');

            if ((
                    $discount->applicable_for === 'All' ||
                    in_array($product->id, $applicableProducts)
                ) && (
                    $todayDate >= $discount->valid_from &&
                    $todayDate <= $discount->valid_till &&
                    in_array($todayDay, $applicableDays) &&
                    $qty >= $discount->minimum_qty &&
                    $qty <= $discount->maximum_qty
                )
            ) {
                $discountedPrice = $discount->type === 'flat'
                    ? $price - $discount->value
                    : $price - ($price * ($discount->value / 100));
                $noDiscountApplied = false;
                break;
            }
        }

        if ($noDiscountApplied && $product->promotion && $todayDate <= $product->last_date) {
            $discountedPrice = $product->promotion_price;
        } elseif ($noDiscountApplied) {
            $discountedPrice = $price;
        }

        // ✅ Tax from relationship, not a second query
        $taxRate = $product->tax?->rate ?? 0;
        $taxName = $product->tax?->name ?? 'No Tax';

        // Units
        if (in_array($product->type, ['standard', 'combo'])) {
            $units = Unit::where("base_unit", $product->unit_id)->orWhere('id', $product->unit_id)->get();
            $unitNames = $unitOperators = $unitValues = [];

            foreach ($units as $unit) {
                if ($product->sale_unit_id == $unit->id) {
                    array_unshift($unitNames, $unit->unit_name);
                    array_unshift($unitOperators, $unit->operator);
                    array_unshift($unitValues, $unit->operation_value);
                } else {
                    $unitNames[] = $unit->unit_name;
                    $unitOperators[] = $unit->operator;
                    $unitValues[] = $unit->operation_value;
                }
            }
        } else {
            $unitNames = $unitOperators = $unitValues = ['n/a'];
        }

        // check if batch product
        if (!empty($batch_id)) {
            $batch = ProductBatch::find($batch_id);
        }

        if ($product->is_imei == 1) {
            // Deduct qty by 1 for IMEI products
            $imei_number = $request->data['imei'];
        } else {
            $imei_number = null;
        }

        // Calculate the product-level discount amount (original price minus discounted price).
        // This lets the JS use it directly, eliminating the second checkDiscount AJAX call.
        $discountAmount = max(0, $price - $discountedPrice);

        // Resolve human-readable variant name if this is a variant product or matched by item_code
        $variantName = '';
        if ($productVariantId) {
            $pv = ProductVariant::select('variant_id')->find($productVariantId);
            if ($pv && $pv->variant_id) {
                $v = Variant::select('name')->find($pv->variant_id);
                if ($v) {
                    $variantName = $v->name;
                }
            }
        }

        $productArray = [
            'name' => $product->name,
            'code' => $product->is_variant ? ($product->item_code ?? $product->code) : $product->code,
            'price' => $discountedPrice,
            'discount' => $discountAmount,
            'tax_rate' => $taxRate,
            'tax_name' => $taxName,
            'tax_method' => $product->tax_method,
            'unit_name' => implode(',', $unitNames) . ',',
            'unit_operator' => implode(',', $unitOperators) . ',',
            'unit_operation_value' => implode(',', $unitValues) . ',',
            'id' => $product->id,
            'variant_id' => $productVariantId,
            'variant_name' => $variantName,
            'promotion' => $product->promotion,
            'is_batch' => $product->is_batch,
            'is_imei' => $product->is_imei,
            'is_variant' => $product->is_variant,
            'qty' => $qty,
            'wholesale_price' => $product->wholesale_price,
            'cost' => $product->cost,
            'imei_number' => $imei_number,
            'warehouse_qty' => $request->data['warehouse_qty'] ?? $request->data['qty'],
            'type' => $product->type,
            'batch_id' => $batch_id,
            'batch_no' => $batch->batch_no ?? ''
        ];

        // Restaurant modifiers are available only for restaurant warehouses.
        if ($isRestaurant) {
            $modifiers = \Modules\Restaurant\Entities\ModifierGroup::whereHas('products', function ($q) use ($product) {
                $q->where('product_id', $product->id);
            })
                ->with(['modifiers' => function ($q) use ($product) {
                    $q->leftJoin('product_modifier_group_modifiers as pmgm', function ($join) use ($product) {
                        $join->on('pmgm.modifier_id', '=', 'modifiers.id')
                            ->where('pmgm.product_id', '=', $product->id);
                    })
                        ->where('modifiers.is_active', 1)
                        ->where(function ($query) {
                            $query->whereNull('pmgm.is_active')
                                ->orWhere('pmgm.is_active', 1);
                        })
                        ->select(
                            'modifiers.*',
                            DB::raw('COALESCE(pmgm.price_adjustment, modifiers.price_adjustment) as effective_price'),
                            'pmgm.product_list',
                            'pmgm.qty_list',
                            DB::raw('COALESCE(pmgm.sort_order, modifiers.sort_order) as effective_sort')
                        )
                        ->orderBy('effective_sort');
                }, 'products' => function ($q) use ($product) {
                    $q->where('product_id', $product->id);
                }])
                ->where('is_active', 1)
                ->get()
                ->map(function ($group) {
                    $pivot = $group->products->first()->pivot ?? null;
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                        'selection_type' => $group->selection_type,
                        'min_selection' => $pivot->min_selection_override ?? $group->min_selection,
                        'max_selection' => $pivot->max_selection_override ?? $group->max_selection,
                        'is_required' => $pivot->is_required_override ?? $group->is_required,
                        'modifiers' => $group->modifiers->map(function ($mod) {
                            return [
                                'id' => $mod->id,
                                'name' => $mod->name,
                                'price_adjustment' => $mod->effective_price,
                                'product_list' => $mod->product_list,
                                'qty_list' => $mod->qty_list,
                            ];
                        })->values(),
                    ];
                })->filter(function ($group) {
                    return count($group['modifiers']) > 0;
                })->values();

            if ($modifiers->isNotEmpty()) {
                $productArray['modifiers'] = $modifiers;
            }
        }

        return response()->json($productArray);
    }

    public function checkDiscount(Request $request)
    {
        $qty = $request->input('qty');
        $customer_id = $request->input('customer_id');
        $warehouse_id = $request->input('warehouse_id');
        $productDiscount = 0;
        $lims_product_data = Product::select('id', 'price', 'promotion', 'promotion_price', 'last_date')->find($request->input('product_id'));
        $lims_product_warehouse_data = Product_Warehouse::where([
            ['product_id', $request->input('product_id')],
            ['warehouse_id', $warehouse_id]
        ])->first();
        if ($lims_product_warehouse_data && $lims_product_warehouse_data->price) {
            $lims_product_data->price = $lims_product_warehouse_data->price;
        }
        $todayDate = date('Y-m-d');
        $all_discount = DB::table('discount_plan_customers')
            ->join('discount_plans', 'discount_plans.id', '=', 'discount_plan_customers.discount_plan_id')
            ->join('discount_plan_discounts', 'discount_plans.id', '=', 'discount_plan_discounts.discount_plan_id')
            ->join('discounts', 'discounts.id', '=', 'discount_plan_discounts.discount_id')
            ->where([
                ['discount_plans.is_active', true],
                ['discounts.is_active', true],
                ['discount_plan_customers.customer_id', $customer_id]
            ])
            ->select('discounts.*')
            ->get();
        $no_discount = 1;
        foreach ($all_discount as $key => $discount) {
            $product_list = explode(",", $discount->product_list);
            $days = explode(",", $discount->days);

            if (($discount->applicable_for == 'All' || in_array($lims_product_data->id, $product_list)) && ($todayDate >= $discount->valid_from && $todayDate <= $discount->valid_till && in_array(date('D'), $days) && $qty >= $discount->minimum_qty && $qty <= $discount->maximum_qty)) {
                if ($discount->type == 'flat') {
                    $productDiscount = $discount->value;
                    $price = $lims_product_data->price - $discount->value;
                } elseif ($discount->type == 'percentage') {
                    $productDiscount = $lims_product_data->price * ($discount->value / 100);
                    $price = $lims_product_data->price - ($lims_product_data->price * ($discount->value / 100));
                }
                $no_discount = 0;
                break;
            } else {
                continue;
            }
        }

        if ($lims_product_data->promotion && $todayDate <= $lims_product_data->last_date && $no_discount) {
            $price = $lims_product_data->promotion_price;
        } elseif ($no_discount)
            $price = $lims_product_data->price;

        $data = [$price, $lims_product_data->promotion, $productDiscount];
        return $data;
    }

    public function getGiftCard()
    {
        $gift_card = GiftCard::where("is_active", true)->whereDate('expired_date', '>=', date("Y-m-d"))->get(['id', 'card_no', 'amount', 'expense']);
        return json_encode($gift_card);
    }

    public function productSaleData($id)
    {
        $sale = Sale::select('id', 'sale_type', 'created_at', 'reference_no', 'item', 'total_qty')->findOrFail($id);
        if ($sale && $sale->sale_type === 'tailoring') {
            $order = \Modules\Tailoring\Entities\TailoringOrder::where('core_sale_id', $sale->id)->first();
            if ($order) {
                return response()->json([
                    'type' => 'tailoring',
                    'tailoring_order_id' => $order->id,
                    'tailoring_order_code' => $order->order_code,
                    'reference_no' => $sale->reference_no,
                    'piece_count' => (float) $sale->total_qty > 0 ? (int) $sale->total_qty : ((int) $sale->item > 0 ? (int) $sale->item : ($order ? $order->commercialPieceCount() : 1)),
                ]);
            }
        }

        $productSaleQuery = Product_Sale::where('sale_id', $id);
        if ($this->restaurantModifiersAvailable()) {
            $productSaleQuery->with('modifiers');
        }
        $lims_product_sale_data = $productSaleQuery->get();

        // Eager-load variant names in one single query
        $variantIds = $lims_product_sale_data->pluck('variant_id')->filter()->unique();
        $variantMap = $variantIds->isNotEmpty()
            ? Variant::select('id', 'name')->whereIn('id', $variantIds)->get()->keyBy('id')
            : collect();

        foreach ($lims_product_sale_data as $key => $product_sale_data) {

            $product = Product::find($product_sale_data->product_id);

            if ($product_sale_data->variant_id) {
                $lims_product_variant_data = ProductVariant::select('item_code')
                    ->FindExactProduct($product_sale_data->product_id, $product_sale_data->variant_id)
                    ->first();

                if ($lims_product_variant_data) {
                    $product->code = $lims_product_variant_data->item_code;
                }
            }

            $unit_data = Unit::find($product_sale_data->sale_unit_id);
            $unit = $unit_data ? $unit_data->unit_code : '';

            if ($product_sale_data->product_batch_id) {
                $product_batch_data = ProductBatch::select('batch_no')
                    ->find($product_sale_data->product_batch_id);

                $product_sale['batch_no'][$key] = $product_batch_data ? $product_batch_data->batch_no : 'N/A';
            } else {
                $product_sale['batch_no'][$key] = 'N/A';
            }

            $product_sale['product'][$key] = $product->name . ' [' . $product->code . ']';
            if ($product_sale_data->variant_id && $variantMap->has($product_sale_data->variant_id)) {
                $variantName = $variantMap->get($product_sale_data->variant_id)->name;
                $product_sale['product'][$key] .= '<br><span>' . __('db.Variant') . ': ' . e($variantName) . '</span>';
            }

            $returned_imei_number_data = '';

            if (
                $product_sale_data->imei_number &&
                !str_contains($product_sale_data->imei_number, 'null')
            ) {
                $imeis = array_unique(explode(',', $product_sale_data->imei_number));
                $imeis = implode(',', $imeis);

                $product_sale['product'][$key] .=
                    '<br><span style="white-space: normal !important;word-break: break-word !important;overflow-wrap: anywhere !important;max-width: 100%;display: block;">IMEI or Serial Number: '
                    . $imeis .
                    '</span>';

                $returned_imei_number_data = DB::table('returns')
                    ->join('product_returns', 'returns.id', '=', 'product_returns.return_id')
                    ->where([
                        ['returns.sale_id', $id],
                        ['product_returns.product_id', $product_sale_data->product_id]
                    ])
                    ->select('product_returns.imei_number')
                    ->first();
            }

            $product_sale['qty'][$key] = $product_sale_data->qty;
            $product_sale['unit'][$key] = $unit;
            $product_sale['tax'][$key] = $product_sale_data->tax;
            $product_sale['tax_rate'][$key] = $product_sale_data->tax_rate;
            $product_sale['discount'][$key] = $product_sale_data->discount;
            $product_sale['total'][$key] = $product_sale_data->total;

            if ($returned_imei_number_data) {
                $imeis = array_unique(explode(',', $returned_imei_number_data->imei_number));
                $imeis = implode(',', $imeis);

                $product_sale['return_qty'][$key] =
                    $product_sale_data->return_qty .
                    '<br><span style="white-space: normal !important;word-break: break-word !important;overflow-wrap: anywhere !important;max-width: 100%;display: block;">IMEI or Serial Number: '
                    . $imeis .
                    '</span>';
            } else {
                $product_sale['return_qty'][$key] = $product_sale_data->return_qty;
            }

            $product_sale['is_delivered'][$key] = $product_sale_data->is_delivered
                ? __('db.Yes')
                : __('db.No');

            // Warranty Info
            if ($product->warranty) {
                $warranty_duration = $product->warranty . ' ' .
                    ($product->warranty === 1
                        ? str_replace('s', '', $product->warranty_type)
                        : $product->warranty_type);

                $warranty_end = $this->getWarrantyGuaranteeEndDate([
                    'sale_date' => $sale->created_at,
                    'duration'  => $product->warranty,
                    'type'      => $product->warranty_type,
                ]);

                $product_sale['product'][$key] .=
                    '<br><span style="font-weight: bold;">' . __('db.Warranty') . ':</span> ' .
                    $warranty_duration .
                    '<br><span style="font-weight: bold;">Expire At:</span> ' .
                    date(config('date_format'), strtotime($warranty_end));
            }

            // Guarantee Info
            if ($product->guarantee) {
                $guarantee_duration = $product->guarantee . ' ' .
                    ($product->guarantee === 1
                        ? str_replace('s', '', $product->guarantee_type)
                        : $product->guarantee_type);

                $guarantee_end = $this->getWarrantyGuaranteeEndDate([
                    'sale_date' => $sale->created_at,
                    'duration'  => $product->guarantee,
                    'type'      => $product->guarantee_type,
                ]);

                $product_sale['product'][$key] .=
                    '<br><span style="font-weight: bold;">' . __('db.Guarantee') . ':</span> ' .
                    $guarantee_duration .
                    '<br><span style="font-weight: bold;">Expire At:</span> ' .
                    date(config('date_format'), strtotime($guarantee_end));
            }

            if ($this->restaurantModifiersAvailable()) {
                if (!empty($product_sale_data->topping_id)) {
                    $product_sale['topping_id'][$key] = $product_sale_data->topping_id;
                } elseif ($product_sale_data->modifiers && $product_sale_data->modifiers->isNotEmpty()) {
                    $modArray = $product_sale_data->modifiers->map(function ($m) {
                        return [
                            'id' => $m->modifier_id,
                            'group_id' => $m->modifier_group_id,
                            'group_name' => $m->modifier_group_name,
                            'name' => $m->modifier_name,
                            'price' => (float) $m->price_adjustment,
                            'qty' => 1,
                        ];
                    })->toArray();
                    $product_sale['topping_id'][$key] = json_encode($modArray);
                } else {
                    $product_sale['topping_id'][$key] = null;
                }
            }
        }

        return $product_sale ?? [];
    }

    public function getSale($id)
    {
        $saleData = Sale::findOrFail($id);

        $warehouse = Warehouse::findOrFail($saleData->warehouse_id);
        $currency = $saleData->currency_id
            ? Currency::find($saleData->currency_id)
            : (cache()->get('currency') ?? Currency::first());
        $biller = Biller::findOrFail($saleData->biller_id);
        $customer = Customer::findOrFail($saleData->customer_id);

        $saleStatus = match ($saleData->sale_status) {
            1 => __('db.Completed'),
            2 => __('db.Pending'),
            3 => __('db.Draft'),
            4 => __('db.Returned'),
            5 => __('db.Processing'),
            6 => __('db.cooked'),
            7 => __('db.served'),
            default => '',
        };

        return [
            'id' => $saleData->id,
            'date' => $saleData->created_at->format('d-m-Y'),
            'reference_no' => $saleData->reference_no,
            'sale_status' => $saleStatus,
            'sale_type' => $saleData->sale_type,

            // Biller
            'biller_name' => $biller->name,
            'biller_company_name' => $biller->company_name,
            'biller_email' => $biller->email,
            'biller_phone' => $biller->phone_number,
            'biller_address' => $biller->address,
            'biller_city' => $biller->city,

            // Customer
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone_number,
            'customer_address' => $customer->address,
            'customer_city' => $customer->city,

            'coupon_code' => $saleData->coupon_code,
            'coupon_discount' => $saleData->coupon_discount,

            // Sale Totals
            'total_tax' => $saleData->total_tax,
            'total_discount' => $saleData->total_discount,
            'total_price' => $saleData->total_price,
            'order_tax' => $saleData->order_tax,
            'order_tax_rate' => $saleData->order_tax_rate,
            'order_discount' => $saleData->order_discount,
            'shipping_cost' => $saleData->shipping_cost,
            'grand_total' => $saleData->grand_total,
            'paid_amount' => $saleData->paid_amount,

            // Notes
            'sale_note' => $saleData->sale_note,
            'staff_note' => $saleData->staff_note,

            // User
            'user_name' => Auth::user()->name,
            'user_email' => Auth::user()->email,

            // Warehouse
            'warehouse_name' => $warehouse->name,

            // Restaurant Table
            'table_name' => !empty($saleData->table_id)
                ? optional(Table::find($saleData->table_id))->name
                : '',

            // Currency
            'currency_code' => $currency?->code ?? 'USD',
            'exchange_rate' => $saleData->exchange_rate,

            // Document
            'document' => $saleData->document,
        ];
    }

    public function saleByCsv()
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('sales-add')) {
            $lims_customer_list = Customer::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_biller_list = Biller::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $currency_list = Currency::where('is_active', true)->get();
            $numberOfInvoice = Sale::whereNull('sales.deleted_at')
                ->where(function ($q) {
                    $q->where('sales.sale_type', '!=', 'opening balance')
                        ->orWhereNull('sales.sale_type');
                })->count();
            return view('backend.sale.import', compact(
                'lims_customer_list',
                'lims_warehouse_list',
                'lims_biller_list',
                'lims_tax_list',
                'numberOfInvoice',
                'currency_list'
            ));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function importSale(Request $request)
    {
        try {
            DB::beginTransaction();
            //get the file
            $upload = $request->file('file');
            $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
            //checking if this is a CSV file
            if ($ext != 'csv')
                return redirect()->back()->with('message', __('db.Please upload a CSV file'));

            $filePath = $upload->getRealPath();
            $file_handle = fopen($filePath, 'r');
            $i = 0;
            $counter = 1;
            //validate the file
            while (!feof($file_handle)) {
                $current_line = fgetcsv($file_handle);
                if ($current_line && $i > 0) {
                    $product_data[] = Product::where('code', $current_line[0])->first();
                    if (!$product_data[$i - 1]) {
                        throw new \Exception(__('db.Product does not exist!'));
                        // return redirect()->back()->with('message', __('db.Product does not exist!'));
                    }
                    $unit[] = Unit::where('unit_code', $current_line[2])->first();
                    if (!$unit[$i - 1] && $current_line[2] == 'n/a')
                        $unit[$i - 1] = 'n/a';
                    elseif (!$unit[$i - 1]) {
                        throw new \Exception(__('db.Sale unit does not exist!'));
                        // return redirect()->back()->with('message', __('db.Sale unit does not exist!'));
                    }
                    if (strtolower($current_line[5]) != "no tax") {
                        $tax[] = Tax::where('name', $current_line[5])->first();
                        if (!$tax[$i - 1]) {
                            throw new \Exception(__('db.Tax name does not exist!'));
                            // return redirect()->back()->with('message', __('db.Tax name does not exist!'));
                        }
                    } else
                        $tax[$i - 1]['rate'] = 0;

                    $qty[] = $current_line[1];
                    $exchange_rate = $request->exchange_rate ?? 1;
                    $price[] = $current_line[3] / $exchange_rate;
                    $discount[] = $current_line[4] / $exchange_rate;
                    $counter++;
                }
                $i++;
            }
            //return $unit;
            $data = $request->except('document');
            // $data['reference_no'] = 'sr-' . date("Ymd") . '-'. date("his");
            $data['reference_no'] = $this->invoiceService->generateInvoiceName('sr-', true);
            $data['user_id'] = Auth::user()->id;
            $document = $request->document;
            if ($document) {
                $v = Validator::make(
                    [
                        'extension' => strtolower($request->document->getClientOriginalExtension()),
                    ],
                    [
                        'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                    ]
                );
                if ($v->fails()) {
                    throw new \Exception($v->errors());
                    // return redirect()->back()->withErrors($v->errors());
                }

                $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
                $documentName = date("Ymdhis");
                if (!config('database.connections.saleprosaas_landlord')) {
                    $documentName = $documentName . '.' . $ext;
                    $document->move(public_path('documents/sale'), $documentName);
                } else {
                    $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                    $document->move(public_path('documents/sale'), $documentName);
                }
                $data['document'] = $documentName;
            }
            $item = 0;
            $exchange_rate = $request->exchange_rate ?? 1;
            $data['order_tax'] = $request->order_tax / $exchange_rate;
            $data['order_discount'] = $request->order_discount / $exchange_rate;
            $data['shipping_cost'] = $request->shipping_cost / $exchange_rate;
            $grand_total = $data['shipping_cost'];
            Sale::create($data);
            $lims_sale_data = Sale::latest()->first();
            $lims_customer_data = Customer::find($lims_sale_data->customer_id);

            $counter = 1;
            foreach ($product_data as $key => $product) {
                if ($product['tax_method'] == 1) {
                    $net_unit_price = $price[$key] - $discount[$key];
                    $product_tax = $net_unit_price * ($tax[$key]['rate'] / 100) * $qty[$key];
                    $total = ($net_unit_price * $qty[$key]) + $product_tax;
                } elseif ($product['tax_method'] == 2) {
                    $net_unit_price = (100 / (100 + $tax[$key]['rate'])) * ($price[$key] - $discount[$key]);
                    $product_tax = ($price[$key] - $discount[$key] - $net_unit_price) * $qty[$key];
                    $total = ($price[$key] - $discount[$key]) * $qty[$key];
                }
                if ($data['sale_status'] == 1 && $unit[$key] != 'n/a') {
                    $sale_unit_id = $unit[$key]['id'];
                    if ($unit[$key]['operator'] == '*')
                        $quantity = $qty[$key] * $unit[$key]['operation_value'];
                    elseif ($unit[$key]['operator'] == '/')
                        $quantity = $qty[$key] / $unit[$key]['operation_value'];
                    $product['qty'] -= $quantity;
                    $product_warehouse = Product_Warehouse::where([
                        ['product_id', $product['id']],
                        ['warehouse_id', $data['warehouse_id']]
                    ])->first();
                    $product_warehouse->qty -= $quantity;
                    $product->save();
                    $product_warehouse->save();
                } else
                    $sale_unit_id = 0;
                //collecting mail data
                $mail_data['products'][$key] = $product['name'];
                if ($product['type'] == 'digital')
                    $mail_data['file'][$key] = url('/product/files') . '/' . $product['file'];
                else
                    $mail_data['file'][$key] = '';
                if ($sale_unit_id)
                    $mail_data['unit'][$key] = $unit[$key]['unit_code'];
                else
                    $mail_data['unit'][$key] = '';

                $product_sale = new Product_Sale();
                $product_sale->sale_id = $lims_sale_data->id;
                $product_sale->product_id = $product['id'];
                $product_sale->qty = $mail_data['qty'][$key] = $qty[$key];
                $product_sale->sale_unit_id = $sale_unit_id;
                $product_sale->net_unit_price = number_format((float)$net_unit_price, config('decimal'), '.', '');
                $product_sale->discount = $discount[$key] * $qty[$key];
                $product_sale->tax_rate = $tax[$key]['rate'];
                $product_sale->tax = number_format((float)$product_tax, config('decimal'), '.', '');
                $product_sale->total = $mail_data['total'][$key] = number_format((float)$total, config('decimal'), '.', '');
                $product_sale->save();
                $lims_sale_data->total_qty += $qty[$key];
                $lims_sale_data->total_discount += $discount[$key] * $qty[$key];
                $lims_sale_data->total_tax += number_format((float)$product_tax, config('decimal'), '.', '');
                $lims_sale_data->total_price += number_format((float)$total, config('decimal'), '.', '');
                $counter++;
            }
            $lims_sale_data->item = $key + 1;
            $lims_sale_data->order_tax = ($lims_sale_data->total_price - $lims_sale_data->order_discount) * ($data['order_tax_rate'] / 100);
            $lims_sale_data->grand_total = ($lims_sale_data->total_price + $lims_sale_data->order_tax + $lims_sale_data->shipping_cost) - $lims_sale_data->order_discount;
            $lims_sale_data->save();

            $message = 'Sale imported successfully';
            $mail_setting = MailSetting::latest()->first();
            if ($lims_customer_data->email && $mail_setting) {
                //collecting male data
                $mail_data['email'] = $lims_customer_data->email;
                $mail_data['reference_no'] = $lims_sale_data->reference_no;
                $mail_data['sale_status'] = $lims_sale_data->sale_status;
                $mail_data['payment_status'] = $lims_sale_data->payment_status;
                $mail_data['total_qty'] = $lims_sale_data->total_qty;
                $mail_data['total_price'] = $lims_sale_data->total_price;
                $mail_data['order_tax'] = $lims_sale_data->order_tax;
                $mail_data['order_tax_rate'] = $lims_sale_data->order_tax_rate;
                $mail_data['order_discount'] = $lims_sale_data->order_discount;
                $mail_data['shipping_cost'] = $lims_sale_data->shipping_cost;
                $mail_data['grand_total'] = $lims_sale_data->grand_total;
                $mail_data['paid_amount'] = $lims_sale_data->paid_amount;
                $this->setMailInfo($mail_setting);
                try {
                    Mail::to($mail_data['email'])->send(new SaleDetails($mail_data));
                    $message = 'Sale imported successfully';
                } catch (\Exception $e) {
                    $message = 'Sale imported successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
                }
            }
            DB::commit();

            app(\App\Services\ZatcaIntegrationService::class)->processSale($lims_sale_data->fresh());

            // External notifications run only after the imported sale transaction commits.
            $this->dispatchSaleNotifications($lims_sale_data);

            return redirect('sales')->with('message', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect('sales/sale_by_csv')->with('not_permitted', "Error in row $counter: " . $e->getMessage());
        }
    }

    public function edit($id)
    {
        if (app(\App\Services\ZatcaIntegrationService::class)->isSourceLocked('sale', (int) $id)) {
            return redirect()->back()->with('not_permitted', 'This sale has a Phase 2 fiscal invoice and is immutable. Use Sale Return to issue a credit note.');
        }

        $role = Role::find(Auth::user()->role_id);
        if (!$role->hasPermissionTo('sales-edit')) {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        $permissions = Role::findByName($role->name)->permissions;
        $all_permission = [];
        foreach ($permissions as $permission) {
            $all_permission[] = $permission->name;
        }
        if (empty($all_permission)) {
            $all_permission[] = 'dummy text';
        }

        $lims_customer_list = Customer::where('is_active', true)->get();
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        $lims_warehouse_list = $this->addRestaurantWorkflowFlag($lims_warehouse_list);
        $lims_biller_list = Biller::where('is_active', true)->get();
        $lims_tax_list = Tax::where('is_active', true)->get();

        $numberOfInvoice = Sale::whereNull('sales.deleted_at')
            ->where(function ($q) {
                $q->where('sales.sale_type', '!=', 'opening balance')
                    ->orWhereNull('sales.sale_type');
            })->count();

        $lims_sale_data = Sale::findOrFail($id);
        $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();

        $variantIds = $lims_product_sale_data->pluck('variant_id')->filter()->unique();
        $variantMap = $variantIds->isNotEmpty()
            ? Variant::select('id', 'name')->whereIn('id', $variantIds)->get()->keyBy('id')
            : collect();

        $currency = Currency::find($lims_sale_data->currency_id) ?? Currency::first();
        $custom_fields = CustomField::where('belongs_to', 'sale')->get();
        $alert_product = DB::table('products')
            ->where('is_active', true)
            ->whereColumn('alert_quantity', '>=', 'qty')
            ->count();

        $dso_alert_product_no = 0;
        $expire_alert_products = 0;
        $general_setting = gen_setting();

        $selectedWarehouseId = (int) $lims_sale_data->warehouse_id;
        $selectedWarehouse = $lims_warehouse_list->firstWhere('id', $selectedWarehouseId);
        $restaurantModuleEnabled = $this->restaurantModuleAvailable();
        $isRestaurantWarehouse = $selectedWarehouse
            && $this->resolvePosWorkflow($selectedWarehouse, $lims_sale_data->pos_workflow) === 'restaurant';

        if ($restaurantModuleEnabled) {
            $lims_table_list = Table::join('floors', 'tables.floor_id', '=', 'floors.id')
                ->select(
                    'tables.id as id',
                    'tables.name',
                    'tables.number_of_person',
                    'floors.name as floor',
                    'floors.warehouse_id'
                )
                ->where('tables.is_active', 1)
                ->get();

            $service_list = DB::table('services')
                ->where('is_active', 1)
                ->get();

            $waiter_list = DB::table('users')
                ->where('service_staff', 1)
                ->where('is_active', 1)
                ->get();
        } else {
            $lims_table_list = collect();
            $service_list = collect();
            $waiter_list = collect();
        }

        return view('backend.sale.edit', compact(
            'lims_customer_list',
            'all_permission',
            'lims_warehouse_list',
            'lims_biller_list',
            'lims_tax_list',
            'lims_sale_data',
            'lims_product_sale_data',
            'variantMap',
            'currency',
            'custom_fields',
            'numberOfInvoice',
            'alert_product',
            'dso_alert_product_no',
            'expire_alert_products',
            'general_setting',
            'restaurantModuleEnabled',
            'selectedWarehouseId',
            'selectedWarehouse',
            'isRestaurantWarehouse',
            'lims_table_list',
            'service_list',
            'waiter_list'
        ));
    }

    public function update(Request $request, $id)
    {
        if (app(\App\Services\ZatcaIntegrationService::class)->isSourceLocked('sale', (int) $id)) {
            return redirect()->back()->with('not_permitted', 'A Phase 2 invoice has already been generated for this sale. Issue a sale return/credit note instead of editing it.');
        }

        $data = $this->prepareWarehouseSaleData($request->except('document'), (int) $id);
        $document = $request->document;
        $lims_sale_data = Sale::findOrFail($id);

        if (isset($data['created_at'])) {
            $data['created_at'] = normalize_to_sql_datetime($data['created_at']);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        $restaurantModifiersAvailable = $this->restaurantModifiersAvailable();

        if ($restaurantModifiersAvailable) {
            $topping_product = $data['topping_product'] ?? [];
        }

        if ($document) {
            $v = Validator::make(
                [
                    'extension' => strtolower($request->document->getClientOriginalExtension()),
                ],
                [
                    'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                ]
            );
            if ($v->fails())
                return redirect()->back()->withErrors($v->errors());

            $this->fileDelete(public_path('documents/sale/'), $lims_sale_data->document);

            $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
            $documentName = date("Ymdhis");
            if (!config('database.connections.saleprosaas_landlord')) {
                $documentName = $documentName . '.' . $ext;
                $document->move(public_path('documents/sale'), $documentName);
            } else {
                $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                $document->move(public_path('documents/sale'), $documentName);
            }
            $data['document'] = $documentName;
        }
        $balance = $data['grand_total'] - $data['paid_amount'];
        if ($balance < 0 || $balance > 0)
            $data['payment_status'] = 2;
        else
            $data['payment_status'] = 4;

        $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();

        $old_modifiers_by_ps = [];
        if ($restaurantModifiersAvailable) {
            $ps_ids = $lims_product_sale_data->pluck('id')->toArray();
            if (!empty($ps_ids)) {
                $old_modifiers = \DB::table('product_sale_modifiers')->whereIn('product_sale_id', $ps_ids)->get();
                foreach ($old_modifiers as $mod) {
                    $old_modifiers_by_ps[$mod->product_sale_id][] = $mod;
                }
            }
        }

        try {
            DB::beginTransaction();

            // Validate Customer Credit Limit inside transaction
            $creditService = app(\App\Services\CustomerCreditService::class);
            $isDraft = (isset($data['sale_status']) && $data['sale_status'] == 3);
            $validation = $creditService->validateCreditLimit(
                $data['customer_id'],
                floatval($data['grand_total']),
                floatval($data['paid_amount'] ?? 0),
                $id, // exclude current sale
                $isDraft
            );

            if (!$validation['allowed']) {
                DB::rollBack();
                if (request()->wantsJson() || request()->isXmlHttpRequest()) {
                    return response()->json([
                        'message' => $validation['message'],
                        'error' => $validation['message'],
                    ], 422);
                }
                return redirect()->back()->with('not_permitted', $validation['message'])->withInput();
            }


            if ($restaurantModifiersAvailable) {
                // Delete old product sales
                Product_Sale::where('sale_id', $id)->delete();
                // Note: product_sale_modifiers should cascade delete if foreign key is set up.
                // If not, we manually delete them:
                if (!empty($ps_ids)) {
                    \DB::table('product_sale_modifiers')->whereIn('product_sale_id', $ps_ids)->delete();
                }

            }

            $product_id = $data['product_id'];
            $imei_number = $data['imei_number'];
            if (isset($data['product_batch_id'])) {
                $product_batch_id = $data['product_batch_id'];
            } else {
                $product_batch_id = null;
            }
            $product_code = $data['product_code'];
            if (!empty($data['product_variant_id']))
                $product_variant_id = $data['product_variant_id'];
            else
                $product_variant_id = null;
            $qty = $data['qty'];
            $sale_unit = $data['sale_unit'];
            $net_unit_price = $data['net_unit_price'];
            $discount = $data['discount'];
            $tax_rate = $data['tax_rate'];
            $tax = $data['tax'];
            $total = $data['subtotal'];
            $old_product_id = [];
            $product_sale = [];
            foreach ($lims_product_sale_data as  $key => $product_sale_data) {
                $old_product_id[] = $product_sale_data->product_id;
                $old_product_variant_id[] = null;
                $lims_product_data = Product::find($product_sale_data->product_id);

                if (in_array((int)$lims_sale_data->sale_status, [1, 5, 6], true) && ($lims_product_data->type == 'combo')) {
                    // if(!in_array('manufacturing',explode(',',config('addons')))) {
                    $product_list = explode(",", $lims_product_data->product_list);
                    $variant_list = explode(",", $lims_product_data->variant_list);
                    if ($lims_product_data->variant_list)
                        $variant_list = explode(",", $lims_product_data->variant_list);
                    else
                        $variant_list = [];
                    $qty_list = explode(",", $lims_product_data->qty_list);
                    $combo_unit_ids = $lims_product_data->combo_unit_id
                        ? explode(",", $lims_product_data->combo_unit_id)
                        : [];

                    $effective_combo_qty = $product_sale_data->qty;
                    if ($product_sale_data->sale_unit_id) {
                        $lims_sale_unit_data_combo = Unit::find($product_sale_data->sale_unit_id);
                        if ($lims_sale_unit_data_combo) {
                            if ($lims_sale_unit_data_combo->operator == '*')
                                $effective_combo_qty = $product_sale_data->qty * $lims_sale_unit_data_combo->operation_value;
                            elseif ($lims_sale_unit_data_combo->operator == '/')
                                $effective_combo_qty = $product_sale_data->qty / $lims_sale_unit_data_combo->operation_value;
                        }
                    }

                    foreach ($product_list as $index => $child_id) {
                        $child_data = Product::find($child_id);
                        if (!$child_data) {
                            continue;
                        }

                        $required = (float) $qty_list[$index];
                        if (isset($combo_unit_ids[$index]) && $combo_unit_ids[$index] != $child_data->unit_id) {
                            $unit = Unit::find($combo_unit_ids[$index]);
                            if ($unit) {
                                if ($unit->operator == '*') {
                                    $required = $required * $unit->operation_value;
                                } elseif ($unit->operator == '/') {
                                    $required = $required / $unit->operation_value;
                                }
                            }
                        }
                        $restore_qty = $effective_combo_qty * $required;

                        if (count($variant_list) && isset($variant_list[$index]) && $variant_list[$index]) {
                            $child_product_variant_data = ProductVariant::where([
                                ['product_id', $child_id],
                                ['variant_id', $variant_list[$index]]
                            ])->first();

                            $child_warehouse_data = Product_Warehouse::where([
                                ['product_id', $child_id],
                                ['variant_id', $variant_list[$index]],
                                ['warehouse_id', $lims_sale_data->warehouse_id],
                            ])->first();

                            if ($child_product_variant_data) {
                                $child_product_variant_data->qty += $restore_qty;
                                $child_product_variant_data->save();
                            }
                        } else {
                            $child_warehouse_data = Product_Warehouse::where([
                                ['product_id', $child_id],
                                ['warehouse_id', $lims_sale_data->warehouse_id],
                            ])->first();
                        }

                        $child_data->qty += $restore_qty;
                        if ($child_warehouse_data) {
                            $child_warehouse_data->qty += $restore_qty;
                            $child_warehouse_data->save();
                        }

                        $child_data->save();
                    }
                    // }
                }

                if (in_array((int)$lims_sale_data->sale_status, [1, 5, 6], true) && ($product_sale_data->sale_unit_id != 0)) {
                    $old_product_qty = $product_sale_data->qty;
                    $lims_sale_unit_data = Unit::find($product_sale_data->sale_unit_id);
                    if (!$lims_sale_unit_data) {
                        \Log::error("Calculation integrity error: Sale unit ID {$product_sale_data->sale_unit_id} not found for product ID {$product_sale_data->product_id} in sale ID {$id}.");
                        throw new \Exception("Data integrity error: referenced unit #{$product_sale_data->sale_unit_id} could not be resolved for sale calculation.");
                    }
                    if ($lims_sale_unit_data->operator == '*')
                        $old_product_qty = $old_product_qty * $lims_sale_unit_data->operation_value;
                    else
                        $old_product_qty = $old_product_qty / $lims_sale_unit_data->operation_value;
                    if ($product_sale_data->variant_id) {
                        $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($product_sale_data->product_id, $product_sale_data->variant_id)->first();
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($product_sale_data->product_id, $product_sale_data->variant_id, $lims_sale_data->warehouse_id)
                            ->first();
                        $old_product_variant_id[$key] = $lims_product_variant_data->id;
                        $lims_product_variant_data->qty += $old_product_qty;
                        $lims_product_variant_data->save();
                    } elseif ($product_sale_data->product_batch_id) {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $product_sale_data->product_id],
                            ['product_batch_id', $product_sale_data->product_batch_id],
                            ['warehouse_id', $lims_sale_data->warehouse_id]
                        ])->first();

                        $product_batch_data = ProductBatch::find($product_sale_data->product_batch_id);
                        $product_batch_data->qty += $old_product_qty;
                        $product_batch_data->save();
                    } else
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($product_sale_data->product_id, $lims_sale_data->warehouse_id)
                            ->first();
                    $lims_product_data->qty += $old_product_qty;

                    if ($lims_product_warehouse_data) {
                        $lims_product_warehouse_data->qty += $old_product_qty;
                    }

                    //returning imei number if exist
                    if ($product_sale_data->imei_number && !str_contains($product_sale_data->imei_number, "null")) {
                        // if(!str_contains($product_sale_data->imei_number, "null")) {
                        if ($lims_product_warehouse_data->imei_number)
                            $lims_product_warehouse_data->imei_number .= ',' . $product_sale_data->imei_number;
                        else
                            $lims_product_warehouse_data->imei_number = $product_sale_data->imei_number;
                    }

                    $lims_product_data->save();

                    if ($lims_product_warehouse_data) {
                        $lims_product_warehouse_data->save();
                    }

                    // Reverse Modifier Inventory
                    if (in_array((int)$lims_sale_data->sale_status, [1, 5, 6], true) && !empty($old_modifiers_by_ps[$product_sale_data->id])) {
                        foreach ($old_modifiers_by_ps[$product_sale_data->id] as $mod) {
                            $snapshot = new \Modules\Restaurant\Entities\ProductSaleModifier((array) $mod);
                            app(\Modules\Restaurant\Services\ModifierInventoryService::class)
                                ->adjustSnapshot(
                                    $snapshot,
                                    (float) $old_product_qty,
                                    (int) $lims_sale_data->warehouse_id,
                                    1
                                );
                        }
                    }
                } else {
                    if ($product_sale_data->variant_id) {
                        $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($product_sale_data->product_id, $product_sale_data->variant_id)->first();
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($product_sale_data->product_id, $product_sale_data->variant_id, $lims_sale_data->warehouse_id)
                            ->first();
                        $old_product_variant_id[$key] = $lims_product_variant_data->id;
                    }
                }

                if ($product_sale_data->variant_id && !(in_array($old_product_variant_id[$key], $product_variant_id))) {
                    $product_sale_data->delete();
                } elseif (!(in_array($old_product_id[$key], $product_id)))
                    $product_sale_data->delete();
            }
            //dealing with new products
            $product_variant_id = [];
            $log_data['item_description'] = '';
            foreach ($product_id as $key => $pro_id) {
                $lims_product_data = Product::find($pro_id);
                $product_sale['variant_id'] = null;
                if ($lims_product_data->type == 'combo' && in_array((int)$data['sale_status'], [1, 5, 6], true)) {
                    // if(!in_array('manufacturing',explode(',',config('addons')))) {
                    $product_list = explode(",", $lims_product_data->product_list);
                    $variant_list = explode(",", $lims_product_data->variant_list);
                    if ($lims_product_data->variant_list)
                        $variant_list = explode(",", $lims_product_data->variant_list);
                    else
                        $variant_list = [];
                    $qty_list = explode(",", $lims_product_data->qty_list);
                    $combo_unit_ids = $lims_product_data->combo_unit_id
                        ? explode(",", $lims_product_data->combo_unit_id)
                        : [];

                    $effective_combo_qty = $qty[$key];
                    if ($sale_unit[$key] != 'n/a') {
                        $lims_sale_unit_data_combo = Unit::where('unit_name', $sale_unit[$key])->first();
                        if ($lims_sale_unit_data_combo) {
                            if ($lims_sale_unit_data_combo->operator == '*')
                                $effective_combo_qty = $qty[$key] * $lims_sale_unit_data_combo->operation_value;
                            elseif ($lims_sale_unit_data_combo->operator == '/')
                                $effective_combo_qty = $qty[$key] / $lims_sale_unit_data_combo->operation_value;
                        }
                    }

                    foreach ($product_list as $index => $child_id) {
                        $child_data = Product::find($child_id);
                        if (!$child_data) {
                            continue;
                        }

                        $required = (float) $qty_list[$index];
                        if (isset($combo_unit_ids[$index]) && $combo_unit_ids[$index] != $child_data->unit_id) {
                            $unit = Unit::find($combo_unit_ids[$index]);
                            if ($unit) {
                                if ($unit->operator == '*') {
                                    $required = $required * $unit->operation_value;
                                } elseif ($unit->operator == '/') {
                                    $required = $required / $unit->operation_value;
                                }
                            }
                        }
                        $deduct_qty = $effective_combo_qty * $required;

                        if (count($variant_list) && isset($variant_list[$index]) && $variant_list[$index]) {
                            $child_product_variant_data = ProductVariant::where([
                                ['product_id', $child_id],
                                ['variant_id', $variant_list[$index]],
                            ])->first();

                            $child_warehouse_data = Product_Warehouse::where([
                                ['product_id', $child_id],
                                ['variant_id', $variant_list[$index]],
                                ['warehouse_id', $data['warehouse_id']],
                            ])->first();

                            if ($child_product_variant_data) {
                                $child_product_variant_data->qty -= $deduct_qty;
                                $child_product_variant_data->save();
                            }
                        } else {
                            $child_warehouse_data = Product_Warehouse::where([
                                ['product_id', $child_id],
                                ['warehouse_id', $data['warehouse_id']],
                            ])->first();
                        }


                        $child_data->qty -= $deduct_qty;
                        if ($child_warehouse_data) {
                            $child_warehouse_data->qty -= $deduct_qty;
                            $child_warehouse_data->save();
                        }

                        $child_data->save();
                        // }
                    }
                }
                if ($sale_unit[$key] != 'n/a') {
                    $lims_sale_unit_data = Unit::where('unit_name', $sale_unit[$key])->first();
                    $sale_unit_id = $lims_sale_unit_data->id;
                    if ($lims_product_data->is_variant) {
                        $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProductWithCode($pro_id, $product_code[$key])->first();
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($pro_id, $lims_product_variant_data->variant_id, $data['warehouse_id'])
                            ->first();
                        $product_sale['variant_id'] = $lims_product_variant_data->variant_id;
                        $product_variant_id[$key] = $lims_product_variant_data->id;
                    } else {
                        $product_variant_id[$key] = Null;
                    }

                    if (in_array((int)$data['sale_status'], [1, 5, 6], true)) {
                        $new_product_qty = $qty[$key];
                        if (!$lims_sale_unit_data) {
                            \Log::error("Calculation integrity error: Sale unit ID not resolved for product ID {$pro_id} in sale ID {$id}.");
                            throw new \Exception("Data integrity error: referenced unit could not be resolved for sale calculation.");
                        }
                        if ($lims_sale_unit_data->operator == '*') {
                            $new_product_qty = $new_product_qty * $lims_sale_unit_data->operation_value;
                        } else {
                            $new_product_qty = $new_product_qty / $lims_sale_unit_data->operation_value;
                        }

                        //return $product_batch_id;

                        if ($product_sale['variant_id']) {
                            $lims_product_variant_data->qty -= $new_product_qty;
                            $lims_product_variant_data->save();
                        } elseif ($product_batch_id != null && isset($product_batch_id[$key])) {
                            $lims_product_warehouse_data = Product_Warehouse::where([
                                ['product_id', $pro_id],
                                ['product_batch_id', $product_batch_id[$key]],
                                ['warehouse_id', $data['warehouse_id']]
                            ])->first();

                            $product_batch_data = ProductBatch::find($product_batch_id[$key]);
                            $product_batch_data->qty -= $new_product_qty;
                            $product_batch_data->save();
                        } else {
                            $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($pro_id, $data['warehouse_id'])
                                ->first();
                        }
                        $lims_product_data->qty -= $new_product_qty;

                        if ($lims_product_warehouse_data) {
                            $lims_product_warehouse_data->qty -= $new_product_qty;
                        }

                        //deduct imei number if available
                        if ($imei_number[$key] && !str_contains($imei_number[$key], "null")) {
                            // if(!str_contains($imei_number[$key], "null")) {
                            $imei_numbers = explode(",", $imei_number[$key]);
                            $all_imei_numbers = explode(",", $lims_product_warehouse_data->imei_number);
                            foreach ($imei_numbers as $number) {
                                if (($j = array_search($number, $all_imei_numbers)) !== false) {
                                    unset($all_imei_numbers[$j]);
                                }
                            }
                            if ($lims_product_warehouse_data) {
                                $lims_product_warehouse_data->imei_number = implode(",", $all_imei_numbers);
                                $lims_product_warehouse_data->save();
                            }
                        }

                        $lims_product_data->save();

                        if ($lims_product_warehouse_data) {
                            $lims_product_warehouse_data->save();
                        }
                    }
                } else
                    $sale_unit_id = 0;


                //collecting mail data
                if ($product_sale['variant_id']) {
                    $variant_data = Variant::select('name')->find($product_sale['variant_id']);
                    $mail_data['products'][$key] = $lims_product_data->name . ' [' . $variant_data->name . ']';
                } else
                    $mail_data['products'][$key] = $lims_product_data->name;

                if ($lims_product_data->type == 'digital')
                    $mail_data['file'][$key] = url('/product/files') . '/' . $lims_product_data->file;
                else
                    $mail_data['file'][$key] = '';

                if ($sale_unit_id) {
                    $log_data['item_description'] .= $lims_product_data->name . '-' . $qty[$key] . ' ' . $lims_sale_unit_data->unit_code . '<br>';
                    $mail_data['unit'][$key] = $lims_sale_unit_data->unit_code;
                } else {
                    $log_data['item_description'] .= $lims_product_data->name . '-' . $qty[$key] . '<br>';
                    $mail_data['unit'][$key] = '';
                }

                $product_sale['sale_id'] = $id;
                $product_sale['product_id'] = $pro_id;
                if ($imei_number[$key] && !str_contains($imei_number[$key], "null")) {
                    $product_sale['imei_number'] = $imei_number[$key];
                } else {
                    $product_sale['imei_number'] = null;
                }
                $product_sale['product_batch_id'] = $product_batch_id[$key] ?? null;
                $product_sale['qty'] = $mail_data['qty'][$key] = $qty[$key];
                $product_sale['sale_unit_id'] = $sale_unit_id;
                $product_sale['net_unit_price'] = $net_unit_price[$key];
                $product_sale['discount'] = $discount[$key];
                $product_sale['tax_rate'] = $tax_rate[$key];
                $product_sale['tax'] = $tax[$key];
                $product_sale['total'] = $mail_data['total'][$key] = $total[$key];
                //return $old_product_variant_id;

                if ($restaurantModifiersAvailable) {

                    $product_sale['topping_id'] = null;
                    if (!empty($topping_product[$key])) {
                        $product_sale['topping_id'] = $topping_product[$key];
                    }

                    $created_product_sale = Product_Sale::create($product_sale);

                    // Insert New Modifiers
                        $modifierPayload = $topping_product[$key] ?? null;
                        $modifiers = app(\Modules\Restaurant\Services\ModifierSelectionService::class)
                            ->resolve((int) $pro_id, $modifierPayload);
                        if (is_array($modifiers)) {
                            foreach ($modifiers as $modifierData) {
                                \DB::table('product_sale_modifiers')->insert([
                                    'product_sale_id' => $created_product_sale->id,
                                    'modifier_group_id' => $modifierData['modifier_group_id'],
                                    'modifier_id' => $modifierData['modifier_id'],
                                    'modifier_group_name' => $modifierData['modifier_group_name'],
                                    'modifier_name' => $modifierData['modifier_name'],
                                    'price_adjustment' => $modifierData['price_adjustment'],
                                    'product_list' => $modifierData['product_list'],
                                    'qty_list' => $modifierData['qty_list'],
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ]);

                                $productList = $modifierData['product_list'];
                                $qtyList = $modifierData['qty_list'];

                                // Deduct inventory if sale is completed or restaurant processing
                                if (in_array((int)$data['sale_status'], [1, 5, 6], true)) {


                                    if (!empty($productList)) {
                                        $mod_product_ids = explode(',', $productList);
                                        $mod_qtys = explode(',', $qtyList);

                                        foreach ($mod_product_ids as $k => $mod_product_id) {
                                            // $qty[$key] is the base product quantity in the current loop
                                            $mod_qty = (float)($mod_qtys[$k] ?? 1) * (float)($modifierData['qty'] ?? 1) * $qty[$key];

                                            $mod_product = \App\Models\Product::find($mod_product_id);
                                            if ($mod_product && $mod_product->type == 'standard') {
                                                $mod_product->qty -= $mod_qty;
                                                $mod_product->save();

                                                $mod_warehouse = \App\Models\Product_Warehouse::where([
                                                    ['product_id', $mod_product_id],
                                                    ['warehouse_id', $data['warehouse_id']]
                                                ])->first();
                                                if ($mod_warehouse) {
                                                    $mod_warehouse->qty -= $mod_qty;
                                                    $mod_warehouse->save();
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                } else {

                    if ($product_sale['variant_id'] && in_array($product_variant_id[$key], $old_product_variant_id)) {
                        Product_Sale::where([
                            ['product_id', $pro_id],
                            ['variant_id', $product_sale['variant_id']],
                            ['sale_id', $id]
                        ])->update($product_sale);
                    } elseif ($product_sale['variant_id'] === null && (in_array($pro_id, $old_product_id))) {
                        Product_Sale::where([
                            ['sale_id', $id],
                            ['product_id', $pro_id]
                        ])->update($product_sale);
                    } else
                        Product_Sale::create($product_sale);
                }
            }
            //return $product_variant_id;
            $lims_sale_data->update($data);

            // Do not emit sale_created when an existing sale is edited.
            // A dedicated sale_updated event can be introduced separately if required.
            $lims_sale_data->refresh();

            // inserting data for custom fields
            $custom_field_data = [];
            $custom_fields = CustomField::where('belongs_to', 'sale')->select('name', 'type')->get();

            //inserting data for custom fields
            $custom_field_data = [];
            $custom_fields = CustomField::where('belongs_to', 'sale')->select('name', 'type')->get();
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
                DB::table('sales')->where('id', $lims_sale_data->id)->update($custom_field_data);
            $lims_customer_data = Customer::find($data['customer_id']);
            $message = 'Sale updated successfully';

            //creating log
            $log_data['action'] = 'Sale Updated';
            $log_data['user_id'] = Auth::id();
            $log_data['reference_no'] = $lims_sale_data->reference_no;
            $log_data['date'] = $lims_sale_data->created_at->toDateString();
            // $log_data['admin_email'] = config('admin_email');
            $log_data['admin_message'] = Auth::user()->name . ' has updated a sale. Reference No: ' . $lims_sale_data->reference_no;
            $log_data['user_email'] = Auth::user()->email;
            $log_data['user_name'] = Auth::user()->name;
            $log_data['user_message'] = 'You just updated a sale. Reference No: ' . $lims_sale_data->reference_no;
            // $log_data['mail_setting'] = $mail_setting = MailSetting::latest()->first();
            $this->createActivityLog($log_data);


            try {
                $accountingService = app(\App\Services\AccountingService::class);
                $revRes = $accountingService->reverseTransaction(get_class($lims_sale_data), $lims_sale_data->id);
                if (!$revRes->success) {
                    throw new \App\Exceptions\AccountingException($revRes->error);
                }

                $res = $accountingService->recordSale($lims_sale_data, 'sale_updated');
                if (!$res->success) {
                    throw new \App\Exceptions\AccountingException($res->error);
                }

                if ($res->isPosted() && \Schema::hasColumn($lims_sale_data->getTable(), 'accounting_status')) {
                    $lims_sale_data->accounting_status = 'posted';
                    $lims_sale_data->save();
                }
            } catch (\App\Exceptions\AccountingException $e) {
                \Log::error('Accounting error on Sale Update: ' . $e->getMessage());
                if (\Schema::hasColumn($lims_sale_data->getTable(), 'accounting_status')) {
                    $lims_sale_data->accounting_status = 'failed';
                    $lims_sale_data->save();
                }
            }



            DB::commit();

            app(\App\Services\ZatcaIntegrationService::class)->processSale($lims_sale_data->fresh());

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Sale update failed: ' . $e->getMessage());
            return redirect()->back()->with('not_permitted', 'Sale update failed: ' . $e->getMessage());
        }

        //collecting mail data
        $mail_setting = MailSetting::latest()->first();
        if ($lims_customer_data->email && $mail_setting) {
            $mail_data['email'] = $lims_customer_data->email;
            $mail_data['reference_no'] = $lims_sale_data->reference_no;
            $mail_data['sale_status'] = $lims_sale_data->sale_status;
            $mail_data['payment_status'] = $lims_sale_data->payment_status;
            $mail_data['total_qty'] = $lims_sale_data->total_qty;
            $mail_data['total_price'] = $lims_sale_data->total_price;
            $mail_data['order_tax'] = $lims_sale_data->order_tax;
            $mail_data['order_tax_rate'] = $lims_sale_data->order_tax_rate;
            $mail_data['order_discount'] = $lims_sale_data->order_discount;
            $mail_data['shipping_cost'] = $lims_sale_data->shipping_cost;
            $mail_data['grand_total'] = $lims_sale_data->grand_total;
            $mail_data['paid_amount'] = $lims_sale_data->paid_amount;
            $this->setMailInfo($mail_setting);
            try {
                Mail::to($mail_data['email'])->send(new SaleDetails($mail_data));
            } catch (\Exception $e) {
                $message = "Sale updated successfully Please setup your <a href='setting/mail_setting'>mail setting</a> to send mail";
            }
        }

        return redirect('sales')->with('message', $message);
    }

    public function printLastReciept()
    {
        $sale = Sale::whereIn('sale_status', [1, 5])
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('sales.sale_type', '!=', 'opening balance')
                    ->orWhereNull('sales.sale_type');
            })
            ->latest('id')
            ->first();

        if (!$sale) {
            return redirect()->route('sale.pos')
                ->with('not_permitted', __('db.No completed sale receipt was found.'));
        }

        return redirect()->route('sale.invoice', $sale->id);
    }

    private function getWarrantyGuaranteeEndDate(array $date_data): string
    {
        $days = $date_data['duration'];

        if ($date_data['type'] === 'months') {
            $days = $date_data['duration'] * 30;
        }
        if ($date_data['type'] === 'years') {
            $days = $date_data['duration'] * 365;
        }

        $end_date = new DateTime($date_data['sale_date']);
        $end_date->modify("+$days days");

        return $end_date->format('Y-m-d');
    }

    public function getReceiptData(
        $invoice_settings,
        $lims_sale_data,
        $currency_code,
        $lims_product_sale_data,
        $lims_biller_data,
        $lims_warehouse_data,
        $lims_customer_data,
        $lims_payment_data,
        $numberInWords,
        $sale_custom_fields,
        $customer_custom_fields,
        $product_custom_fields,
        $qrText,
        $totalDue,
        $lims_bill_by
    ) {

        $data = [];
        $show = json_decode($invoice_settings->show_column);
        // ✅ Shop / Warehouse info
        if (isset($show->show_warehouse_info) && $show->show_warehouse_info == 1) {
            if (gen_setting()->site_logo || $invoice_settings->company_logo) {
                $data['shop_logo'] = $invoice_settings->company_logo
                    ? public_path('invoices/' . $invoice_settings->company_logo)
                    : public_path('logo/' . gen_setting()->site_logo);
            }

            $data['shop_name']    = gen_setting()->company_name ?? $lims_biller_data->company_name;
            $data['shop_address'] = $lims_warehouse_data->address;
            $data['shop_phone']   = $lims_warehouse_data->phone;
        }
        // ✅ Date
        $data['date'] = (isset($show->active_date_format) && $show->active_date_format == 1)
            ? Carbon::parse($lims_sale_data->created_at)->format($invoice_settings->invoice_date_format)
            : $lims_sale_data->created_at;

        // ✅ Reference No
        if (isset($show->show_ref_number) && $show->show_ref_number == 1) {
            $data['reference'] = $lims_sale_data->reference_no;
        }

        // ✅ Customer
        if (isset($show->show_customer_name) && $show->show_customer_name == 1) {
            $data['customer'] = $lims_customer_data->name;
        }
        // ✅ Table & Queue (restaurant mode)
        if ($lims_sale_data->table_id) {
            $data['table'] = $lims_sale_data->table->name;
            $data['queue'] = $lims_sale_data->queue;
        }

        // ✅ Sale Custom Fields
        $data['sale_custom_fields'] = [];

        foreach ($sale_custom_fields as $fieldName) {
            $field_name = str_replace(' ', '_', strtolower($fieldName));
            $data['sale_custom_fields'][] = [
                'label' => $fieldName,
                'value' => $lims_sale_data->$field_name,
            ];
        }

        // ✅ Customer Custom Fields
        $data['customer_custom_fields'] = [];
        foreach ($customer_custom_fields as $fieldName) {
            $field_name = str_replace(' ', '_', strtolower($fieldName));
            $data['customer_custom_fields'][] = [
                'label' => $fieldName,
                'value' => $lims_customer_data->$field_name,
            ];
        }

        // ✅ Sale items
        $data['items'] = [];
        $total_product_tax = 0;
        foreach ($lims_product_sale_data as $product_sale_data) {
            $lims_product_data = Product::find($product_sale_data->product_id);
            if ($product_sale_data->variant_id) {
                $variant_data = Variant::find($product_sale_data->variant_id);
                $product_name = $lims_product_data->name . ' [' . $variant_data->name . ']';
            } elseif ($product_sale_data->product_batch_id) {
                $product_batch_data = ProductBatch::select('batch_no')->find($product_sale_data->product_batch_id);
                $product_name = $lims_product_data->name . ' [' . __('db.Batch No') . ': ' . $product_batch_data->batch_no . ']';
            } else {
                $product_name = $lims_product_data->name;
            }
            // IMEI
            if ($product_sale_data->imei_number && !str_contains($product_sale_data->imei_number, 'null')) {
                $product_name .= "\n" . __('db.IMEI or Serial Numbers') . ': ' . $product_sale_data->imei_number;
            }
            // Warranty
            if (isset($product_sale_data->warranty_duration)) {
                $product_name .= "\n" . __('db.Warranty') . ': ' . $product_sale_data->warranty_duration . "\n" . __('db.Will Expire') . ': ' . $product_sale_data->warranty_end;
            }
            // Guarantee
            if (isset($product_sale_data->guarantee_duration)) {
                $product_name .= "\n" . __('db.Guarantee') . ': ' . $product_sale_data->guarantee_duration . "\n" . __('db.Will Expire') . ': ' . $product_sale_data->guarantee_end;
            }

            // Add toppings if available
            $topping_names = [];
            $topping_prices = [];
            $topping_price_sum = 0;

            if ($product_sale_data->modifiers && $product_sale_data->modifiers->isNotEmpty()) {
                foreach ($product_sale_data->modifiers as $modifier) {
                    $price = (float)($modifier->price_adjustment ?? 0);
                    $topping_names[]  = ($modifier->modifier_name ?? '') . ($price > 0 ? ' (+' . number_format($price, gen_setting()->decimal, '.', ',') . ')' : '');
                    $topping_prices[] = $price;
                    $topping_price_sum += $price;
                }
            } elseif ($product_sale_data->topping_id) {
                $decoded_topping_id = is_string($product_sale_data->topping_id)
                    ? json_decode($product_sale_data->topping_id, true)
                    : $product_sale_data->topping_id;

                if (is_array($decoded_topping_id)) {
                    foreach ($decoded_topping_id as $topping) {
                        $qty = $topping['qty'] ?? 1;
                        $price = (float)($topping['price'] ?? 0) * $qty;
                        $topping_names[]  = ($topping['name'] ?? '') . ($qty > 1 ? " (x{$qty})" : "") . ($price > 0 ? ' (+' . number_format($price, gen_setting()->decimal, '.', ',') . ')' : '');
                        $topping_prices[] = $price;
                        $topping_price_sum += $price;
                    }
                }
            }

            $net_price_with_toppings = (float) $product_sale_data->net_unit_price;
            $subtotal = (float) $product_sale_data->total;

            $custom_fields = '';

            foreach ($product_custom_fields as $fieldName) {
                $field_name = str_replace(' ', '_', strtolower($fieldName));

                if (!empty($lims_product_data->$field_name)) {
                    if ($custom_fields === '') {
                        // first field → with line break
                        $custom_fields .= "\n" . $fieldName . ': ' . $lims_product_data->$field_name;
                    } else {
                        // subsequent fields → separated by /
                        $custom_fields .= '/' . $fieldName . ': ' . $lims_product_data->$field_name;
                    }
                }
            }

            $qtyline = $product_sale_data->qty . 'x' . number_format((float) ($product_sale_data->total / $product_sale_data->qty), gen_setting()->decimal, '.', ',');

            $tax_info = '';
            if ($product_sale_data->tax_rate) {
                $total_product_tax += $product_sale_data->tax;
                $tax_info = '[' . __('db.Tax') . '(' . $product_sale_data->tax_rate . '%): ' . $product_sale_data->tax . ']';
            }
            if (isset($show->show_description) && $show->show_description == 1) {
                $data['items'][] = [
                    'name'     => $product_name,
                    'topping_names'     => !empty($topping_names) ? "\n" . implode(', ', $topping_names) : '',
                    'custom_fields'      => $custom_fields,
                    'qtyline'      => $qtyline,
                    'tax_info'      => $tax_info,
                    'subtotal' => number_format($subtotal, gen_setting()->decimal, '.', ','),
                ];
            }
        }

        $data['total'] = number_format((float) $lims_sale_data->total_price, gen_setting()->decimal, '.', ',');

        if (gen_setting()->invoice_format == 'gst' && gen_setting()->state == 1) {
            $data['igst'] = number_format((float) $total_product_tax, gen_setting()->decimal, '.', ',');
        } else if (gen_setting()->invoice_format == 'gst' && gen_setting()->state == 2) {
            $data['sgstandcgst'] = number_format((float) $total_product_tax / 2, gen_setting()->decimal, '.', ',');
        }

        if ($lims_sale_data->order_tax) {
            $data['order_tax']   = number_format((float) $lims_sale_data->order_tax, gen_setting()->decimal, '.', ',');
        }

        if ($lims_sale_data->order_discount) {
            $data['order_discount']   = number_format((float) $lims_sale_data->order_discount, gen_setting()->decimal, '.', ',');
        }

        if ($lims_sale_data->coupon_discount) {
            $data['coupon_discount']   = number_format((float) $lims_sale_data->coupon_discount, gen_setting()->decimal, '.', ',');
        }

        if ($lims_sale_data->shipping_cost) {
            $data['shipping_cost']   = number_format((float) $lims_sale_data->shipping_cost, gen_setting()->decimal, '.', ',');
        }
        // ✅ Totals
        $data['grand_total'] = number_format((float) $lims_sale_data->grand_total, gen_setting()->decimal, '.', ',');

        if ($lims_sale_data->grand_total - $lims_sale_data->paid_amount > 0) {
            $data['due'] = number_format((float) ($lims_sale_data->grand_total - $lims_sale_data->paid_amount), gen_setting()->decimal, '.', ',');
        }
        if ($totalDue && isset($show->hide_total_due)) {
            if (!$show->hide_total_due) {
                $data['total_due'] = number_format($totalDue, gen_setting()->decimal, '.', ',');
            }
        }

        // ✅ In Words (only if enabled)
        if (isset($show->show_in_words) && $show->show_in_words == 1) {
            $data['amount_in_words'] = (gen_setting()->currency_position == 'prefix')
                ? $currency_code . ' ' . str_replace('-', ' ', $numberInWords)
                : str_replace('-', ' ', $numberInWords) . ' ' . $currency_code;
        }

        // ✅ Paid Info
        if (isset($show->show_paid_info) && $show->show_paid_info == 1) {
            $data['payments'] = [];
            foreach ($lims_payment_data as $payment_data) {
                $data['payments'][] = [
                    'paid_by' => $payment_data->paying_method,
                    'amount'  => number_format(
                        (float) $payment_data->amount,
                        gen_setting()->decimal,
                        '.',
                        ','
                    ),
                    'change'  => number_format(
                        (float) $payment_data->change,
                        gen_setting()->decimal,
                        '.',
                        ','
                    ),
                ];
            }
        }

        // ✅ Served By
        if (isset($show->show_biller_info) && $show->show_biller_info == 1) {
            $data['served_by'] = $lims_bill_by['name'] . ' - (' . $lims_bill_by['user_name'] . ')';
        }

        // ✅ Footer Text
        if (isset($show->show_footer_text) && $show->show_footer_text == 1) {
            $data['footer_text'] = $invoice_settings->footer_text
                ?? __('db.Thank you for shopping with us Please come again');
        }

        // ✅ Barcode / QR (if enabled)
        if (isset($show->show_barcode) && $show->show_barcode == 1) {
            $data['barcode'] = $lims_sale_data->reference_no;
        }

        if (isset($show->show_qr_code) && $show->show_qr_code == 1) {
            $data['qrcode'] = $qrText;
        }

        return $data;
    }

    public function genInvoice($id)
    {
        $is_print = filter_var(request()->query('is_print'), FILTER_VALIDATE_BOOLEAN);
        $lims_sale_data = Sale::with(['currency', 'user'])->findOrFail($id);

        try {
            // ── 1. Core sale — eager-load currency + user (avoids lazy query later) ──
            // ── 2. Line items ──────────────────────────────────────────────────────
            $is_restaurant_active = $this->restaurantModifiersAvailable();
            if ($is_restaurant_active) {
                $lims_product_sale_data = Product_Sale::with('modifiers')->where('sale_id', $id)->get();
            } else {
                $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
            }

            // ── 3. Cache-backed lookups (single read each, one-liner ternary) ──────
            $lims_biller_data = cache()->has('biller_list')
                ? cache()->get('biller_list')->find($lims_sale_data->biller_id)
                : Biller::find($lims_sale_data->biller_id);

            $lims_warehouse_data = cache()->has('warehouse_list')
                ? cache()->get('warehouse_list')->find($lims_sale_data->warehouse_id)
                : Warehouse::find($lims_sale_data->warehouse_id);

            $lims_customer_data = cache()->has('customer_list')
                ? cache()->get('customer_list')->find($lims_sale_data->customer_id)
                : Customer::find($lims_sale_data->customer_id);

            $lims_pos_setting_data = cache()->has('pos_setting')
                ? cache()->get('pos_setting')
                : PosSetting::select('invoice_option', 'thermal_invoice_size')->latest()->first();

            // ── 4. Payments — single query, reused for paid_by_info + change_amount ─
            $lims_payment_data = Payment::where('sale_id', $id)->get();
            $paid_by_info  = '';
            $change_amount = 0;
            foreach ($lims_payment_data as $key => $payment_data) {
                $change_amount += $payment_data->change ?? 0;
                $paid_by_info   = $key
                    ? $paid_by_info . ', ' . $payment_data->paying_method
                    : $payment_data->paying_method;
            }

            // ── 5. Number-to-words transformer ────────────────────────────────────
            $supportedIdentifiers = [
                'al',
                'fr_BE',
                'pt_BR',
                'bg',
                'cs',
                'dk',
                'nl',
                'et',
                'ka',
                'de',
                'fr',
                'hu',
                'id',
                'it',
                'lt',
                'lv',
                'ms',
                'fa',
                'pl',
                'ro',
                'sk',
                'es',
                'ru',
                'sv',
                'tr',
                'tk',
                'ua',
                'yo',
            ]; // ar, az, ku, mk — not supported

            $defaultLocale     = \App::getLocale();
            $numberTransformer = (new NumberToWords())->getNumberTransformer(
                in_array($defaultLocale, $supportedIdentifiers) ? $defaultLocale : 'en'
            );

            // Computed once — both exchange-rate branches need the same value
            $numberInWords = $numberTransformer->toWords($lims_sale_data->grand_total);

            // ── 6. Currency code ──────────────────────────────────────────────────
            $currency = Currency::find($lims_sale_data->currency_id) ?? cache()->get('currency') ?? Currency::first();
            $currency_code = $currency ? $currency->code : 'USD';

            // ── 7. QR / ZATCA ─────────────────────────────────────────────────────
            $qrText = app(\App\Services\ZatcaIntegrationService::class)->qrForSale($lims_sale_data);

            // ── 8. Custom fields — one query instead of three ─────────────────────
            $allCustomFields = CustomField::where('is_invoice', true)
                ->whereIn('belongs_to', ['sale', 'customer', 'product'])
                ->get()
                ->groupBy('belongs_to');

            $sale_custom_fields     = $allCustomFields->get('sale',     collect())->pluck('name');
            $customer_custom_fields = $allCustomFields->get('customer', collect())->pluck('name');
            $product_custom_fields  = $allCustomFields->get('product',  collect())->pluck('name');

            // ── 9. Customer financials ─────────────────────────────────────────────
            $returned_amount = DB::table('sales')
                ->join('returns', 'sales.id', '=', 'returns.sale_id')
                ->where('sales.customer_id', $lims_customer_data->id)
                ->where('sales.payment_status', '!=', 4)
                ->whereNull('sales.deleted_at')
                ->sum('returns.grand_total');

            $saleData = DB::table('sales')
                ->where('customer_id', $lims_customer_data->id)
                ->where('payment_status', '!=', 4)
                ->whereNull('sales.deleted_at')
                ->selectRaw('SUM(grand_total) as grand_total, SUM(paid_amount) as paid_amount')
                ->first();

            // Compute change amount — default to 0, set positive value only when fully paid
            $change_amount = 0;
            if (abs($saleData->grand_total - $saleData->paid_amount) < 0.001) {
                $change_amount = 0;
            }

            // ── 10. Warranty / Guarantee — single whereIn, no per-row query ────────
            $productIds = $lims_product_sale_data->pluck('product_id')->unique();
            $productMap = Product::whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            foreach ($lims_product_sale_data as $sale_data) {
                // Deduplicate IMEI numbers
                if (isset($sale_data->imei_number)) {
                    $sale_data->imei_number = implode(',', array_unique(explode(',', $sale_data->imei_number)));
                }

                $product = $productMap->get($sale_data->product_id);
                if (!$product) {
                    continue;
                }

                if (!is_null($product->warranty)) {
                    $sale_data->warranty_duration = $product->warranty . ' ' .
                        ($product->warranty === 1
                            ? str_replace('s', '', $product->warranty_type)
                            : $product->warranty_type);
                    $sale_data->warranty_end = $this->getWarrantyGuaranteeEndDate([
                        'sale_date' => $lims_sale_data->created_at,
                        'duration'  => $product->warranty,
                        'type'      => $product->warranty_type,
                    ]);
                }

                if (!is_null($product->guarantee)) {
                    $sale_data->guarantee_duration = $product->guarantee . ' ' .
                        ($product->guarantee === 1
                            ? str_replace('s', '', $product->guarantee_type)
                            : $product->guarantee_type);
                    $sale_data->guarantee_end = $this->getWarrantyGuaranteeEndDate([
                        'sale_date' => $lims_sale_data->created_at,
                        'duration'  => $product->guarantee,
                        'type'      => $product->guarantee_type,
                    ]);
                }
            }

            // ── 10b. Pre-compute line item display data ─────────────────────────
            // Moves all business logic (product name, variant/batch, toppings,
            // subtotals, tax) out of blade templates into the controller.
            $variantIds = $lims_product_sale_data->pluck('variant_id')->filter()->unique();
            $variantMap = $variantIds->isNotEmpty()
                ? Variant::select('id', 'name')->whereIn('id', $variantIds)->get()->keyBy('id')
                : collect();

            $batchIds = $lims_product_sale_data->pluck('product_batch_id')->filter()->unique();
            $batchMap = $batchIds->isNotEmpty()
                ? ProductBatch::select('id', 'batch_no')->whereIn('id', $batchIds)->get()->keyBy('id')
                : collect();

            $unitIds = $lims_product_sale_data->pluck('sale_unit_id')->filter()->unique();
            $unitMap = $unitIds->isNotEmpty()
                ? Unit::select('id', 'unit_code')->whereIn('id', $unitIds)->get()->keyBy('id')
                : collect();

            $total_product_tax = 0;
            $totalPrice = 0;
            $line_items = [];

            foreach ($lims_product_sale_data as $key => $psd) {
                $product = $productMap->get($psd->product_id);
                $item = new \stdClass();

                // Product name with variant/batch
                $item->product_name = $product ? $product->name : 'Unknown Product';
                if ($psd->variant_id && $variantMap->has($psd->variant_id)) {
                    $item->variant_name = $variantMap->get($psd->variant_id)->name;
                } elseif ($psd->product_batch_id && $batchMap->has($psd->product_batch_id)) {
                    $item->product_name .= ' [' . __('db.Batch No') . ':' . $batchMap->get($psd->product_batch_id)->batch_no . ']';
                    $item->variant_name = '';
                } else {
                    $item->variant_name = '';
                }

                // Unit code
                $item->unit_code = ($psd->sale_unit_id && $unitMap->has($psd->sale_unit_id))
                    ? $unitMap->get($psd->sale_unit_id)->unit_code
                    : '';

                // IMEI
                $item->imei_number = ($psd->imei_number && !str_contains($psd->imei_number, 'null'))
                    ? $psd->imei_number
                    : null;

                // Warranty & Guarantee (already computed on $psd by section 10)
                $item->warranty_duration  = $psd->warranty_duration ?? null;
                $item->warranty_end       = $psd->warranty_end ?? null;
                $item->guarantee_duration = $psd->guarantee_duration ?? null;
                $item->guarantee_end      = $psd->guarantee_end ?? null;

                // Toppings
                $item->topping_names = [];
                $item->topping_prices = [];
                $item->topping_price_sum = 0;

                if ($is_restaurant_active && $psd->modifiers && $psd->modifiers->isNotEmpty()) {
                    foreach ($psd->modifiers as $modifier) {
                        $price = (float)($modifier->price_adjustment ?? 0);
                        $item->topping_names[]  = ($modifier->modifier_name ?? '') . ($price > 0 ? ' (+' . number_format($price, gen_setting()->decimal, '.', ',') . ')' : '');
                        $item->topping_prices[] = $price;
                        $item->topping_price_sum += $price;
                    }
                } elseif ($psd->topping_id) {
                    $decoded = is_string($psd->topping_id)
                        ? json_decode($psd->topping_id, true)
                        : $psd->topping_id;
                    if (is_array($decoded)) {
                        foreach ($decoded as $topping) {
                            $qty = $topping['qty'] ?? 1;
                            $price = (float)($topping['price'] ?? 0) * $qty;
                            $item->topping_names[]  = ($topping['name'] ?? '') . ($qty > 1 ? " (x{$qty})" : "") . ($price > 0 ? ' (+' . number_format($price, gen_setting()->decimal, '.', ',') . ')' : '');
                            $item->topping_prices[] = $price;
                            $item->topping_price_sum += $price;
                        }
                    }
                }

                // Prices
                $item->qty            = $psd->qty;
                $item->net_unit_price = $psd->net_unit_price;
                $item->discount       = $psd->discount;
                $item->tax_rate       = $psd->tax_rate;
                $item->tax            = $psd->tax;
                $item->total          = $psd->total;
                $item->subtotal       = $psd->total;
                $item->unit_price_with_toppings = $psd->net_unit_price;
                $item->line_total     = $psd->total;

                // Product custom fields
                $item->custom_fields = [];
                if ($product) {
                    foreach ($product_custom_fields as $fieldName) {
                        $field_key = str_replace(' ', '_', strtolower($fieldName));
                        $item->custom_fields[$fieldName] = $product->$field_key ?? null;
                    }
                }

                // Accumulate totals
                $total_product_tax += $psd->tax;
                $totalPrice += $psd->net_unit_price * $psd->qty;

                $line_items[] = $item;
            }

            // ── 11. Biller info (from eager-loaded ->user — no extra query) ────────
            $lims_bill_by              = $lims_sale_data->user->only(['name', 'email']);
            $lims_bill_by['user_name'] = strstr($lims_bill_by['email'], '@', true);

            $totalDue = $saleData->grand_total - $returned_amount - $saleData->paid_amount;
            $prevDue  = $totalDue - ($lims_sale_data->grand_total - $lims_sale_data->paid_amount);

            // ── 12. Invoice settings & printer ────────────────────────────────────
            $invoice_settings = InvoiceSetting::active_setting();
            $receipt_printer  = Printer::where('warehouse_id', $lims_sale_data->warehouse_id)->first();

            // Build paid_by_info as a string — the blade template echoes it directly with {{ }}
            $paid_by_info = $lims_payment_data->map(function ($payment) {
                return $payment->paying_method ?? 'Cash';
            })->unique()->implode(', ');

            // ── 13. Installment plan data ─────────────────────────────────────────
            $lims_installment_plan_data = DB::table('installment_plans')->where([
                ['reference_type', 'sale'],
                ['reference_id', $lims_sale_data->id]
            ])->first();

            $installment_info = null;
            if ($lims_installment_plan_data) {
                $inst_all   = DB::table('installments')->where('installment_plan_id', $lims_installment_plan_data->id)->get();
                $installment_info = new \stdClass();
                $installment_info->plan         = $lims_installment_plan_data;
                $installment_info->total        = $inst_all->count();
                $installment_info->paid         = $inst_all->where('status', 'completed')->count();
                $installment_info->next         = $inst_all->where('status', 'pending')->sortBy('payment_date')->first();
            }

            // Shared data bag — all view templates receive the same compact array
            $viewData = compact(
                'invoice_settings',
                'lims_sale_data',
                'currency',
                'currency_code',
                'lims_product_sale_data',
                'lims_biller_data',
                'lims_warehouse_data',
                'lims_customer_data',
                'lims_payment_data',
                'numberInWords',
                'sale_custom_fields',
                'customer_custom_fields',
                'product_custom_fields',
                'qrText',
                'prevDue',
                'totalDue',
                'lims_bill_by',
                'paid_by_info',
                'change_amount',
                'line_items',
                'total_product_tax',
                'totalPrice',
                'installment_info'
            );

            // ── 13. Routing ───────────────────────────────────────────────────────
            if ($receipt_printer && $is_print) {
                if (in_array($invoice_settings->size, ['58mm', '80mm', '80mm_bilingual'])) {
                    $data = $this->getReceiptData(
                        $invoice_settings,
                        $lims_sale_data,
                        $currency_code,
                        $lims_product_sale_data,
                        $lims_biller_data,
                        $lims_warehouse_data,
                        $lims_customer_data,
                        $lims_payment_data,
                        $numberInWords,
                        $sale_custom_fields,
                        $customer_custom_fields,
                        $product_custom_fields,
                        $qrText,
                        $prevDue,
                        $totalDue,
                        $lims_bill_by
                    );
                    app(PrinterService::class)->printReceipt($receipt_printer, $data);
                    return 'receipt_printer';
                }
                return 'invoice_settings_error';
            }

            // Map invoice sizes to their view templates (new invoice system)
            $sizeViewMap = [
                'a4'             => 'backend.setting.invoice_setting.a4',
                'a4_bilingual'   => 'backend.setting.invoice_setting.a4_bilingual',
                '58mm'           => 'backend.setting.invoice_setting.58mm',
                '80mm'           => 'backend.setting.invoice_setting.80mm',
                '80mm_bilingual' => 'backend.setting.invoice_setting.80mm_bilingual',
            ];

            // Whitelisted query overrides for preview / testing only (never derive view from input)
            $queryTemplate  = request()->query('template');
            $queryBilingual = request()->query('bilingual');

            if ($queryTemplate && isset($sizeViewMap[$queryTemplate])) {
                return view($sizeViewMap[$queryTemplate], $viewData);
            }

            if (filter_var($queryBilingual, FILTER_VALIDATE_BOOLEAN)) {
                $configuredSize = $invoice_settings->size ?? 'a4';
                if ($configuredSize === '80mm' || $configuredSize === '80mm_bilingual') {
                    return view('backend.setting.invoice_setting.80mm_bilingual', $viewData);
                }
                if ($configuredSize === 'a4' || $configuredSize === 'a4_bilingual') {
                    return view('backend.setting.invoice_setting.a4_bilingual', $viewData);
                }
            }

            if (isset($invoice_settings->size) && isset($sizeViewMap[$invoice_settings->size])) {
                return view($sizeViewMap[$invoice_settings->size], $viewData);
            }

            // Legacy invoice routing (old invoice code)
            if ($lims_pos_setting_data->invoice_option === 'A4' || $lims_sale_data->sale_type === 'online') {
                return view('backend.sale.a4_invoice', $viewData);
            }
            if ($lims_pos_setting_data->invoice_option === 'thermal' && $lims_pos_setting_data->thermal_invoice_size === '58') {
                return view('backend.sale.invoice58', $viewData);
            }

            return view('backend.sale.invoice', $viewData);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('genInvoice error: ' . $e->getMessage() . ' on line ' . $e->getLine() . ' in ' . $e->getFile());
            return redirect()->back()->with('not_permitted', 'Invoice Error: ' . $e->getMessage());
        }
    }

    public function customerDisplay()
    {
        return view('backend.sale.display');
    }

    public function addPayment(Request $request)
    {
        $data = $request->except('document');
        $data = $request->all();
        $document = $request->document;
        if ($document) {
            $v = Validator::make(
                [
                    'extension' => strtolower($request->document->getClientOriginalExtension()),
                ],
                [
                    'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                ]
            );
            if ($v->fails())
                return redirect()->back()->withErrors($v->errors());

            $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
            $documentName = date("Ymdhis");
            if (!config('database.connections.saleprosaas_landlord')) {
                $documentName = $documentName . '.' . $ext;
                $document->move(public_path('documents/add-payment'), $documentName);
            } else {
                $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                $document->move(public_path('documents/add-payment'), $documentName);
            }
            $data['document'] = $documentName;
        }
        if (!$data['amount'])
            $data['amount'] = 0.00;

        $lims_sale_data = Sale::find($data['sale_id']);

        $lims_customer_data = Customer::find($lims_sale_data->customer_id);
        $lims_sale_data->paid_amount += $data['amount'];
        $balance = $lims_sale_data->grand_total - $lims_sale_data->paid_amount;
        if ($balance > 0 || $balance < 0)
            $lims_sale_data->payment_status = 2;
        elseif ($balance == 0)
            $lims_sale_data->payment_status = 4;

        if ($data['paid_by_id'] == 1)
            $paying_method = 'Cash';
        elseif ($data['paid_by_id'] == 2)
            $paying_method = 'Gift Card';
        elseif ($data['paid_by_id'] == 3)
            $paying_method = 'Credit Card';
        elseif ($data['paid_by_id'] == 4)
            $paying_method = 'Cheque';
        elseif ($data['paid_by_id'] == 5)
            $paying_method = 'Paypal';
        elseif ($data['paid_by_id'] == 6)
            $paying_method = 'Deposit';
        elseif ($data['paid_by_id'] == 7)
            $paying_method = 'Points';
        else
            $paying_method = ucfirst($data['paid_by_id']);

        $cash_register_data = CashRegister::where([
            ['user_id', Auth::id()],
            ['warehouse_id', $lims_sale_data->warehouse_id],
            ['status', true]
        ])->first();

        if (isset($data['payment_at'])) {
            $data['payment_at'] = normalize_to_sql_datetime($data['payment_at']);
        } else {
            $data['payment_at'] = date('Y-m-d H:i:s');
        }

        $lims_payment_data = new Payment();
        $lims_payment_data->user_id = Auth::id();
        $lims_payment_data->sale_id = $lims_sale_data->id;
        if ($cash_register_data)
            $lims_payment_data->cash_register_id = $cash_register_data->id;
        $lims_payment_data->account_id = $data['account_id'];
        $data['payment_reference'] = $this->invoiceService->generateInvoiceName('spr-'); // 'spr-' . date("Ymd") . '-' . date("his");
        $lims_payment_data->payment_reference = $data['payment_reference'];
        $lims_payment_data->amount = $data['amount'];
        $lims_payment_data->currency_id = $data['currency_id'] ?? 1;
        $lims_payment_data->exchange_rate = $data['exchange_rate'] ?? 1;
        $lims_payment_data->change = $data['paying_amount'] - $data['amount'];
        $lims_payment_data->paying_method = $paying_method;
        $lims_payment_data->payment_note = $data['payment_note'];
        $lims_payment_data->payment_receiver = $data['payment_receiver'];
        if (isset($data['document'])) {
            $lims_payment_data->document = $data['document'];
        }
        $lims_payment_data->payment_at = $data['payment_at'];

        $lims_payment_data->save();
        $lims_sale_data->save();

        // ROUTE THROUGH UNIFIED PAYMENT RECEIVED CHANNEL HELPER
        $this->dispatchPaymentNotifications($lims_sale_data, $data['amount']);

        $data['payment_id'] = $lims_payment_data->id;

        if ($paying_method == 'Gift Card') {
            $lims_gift_card_data = GiftCard::find($data['gift_card_id']);
            $lims_gift_card_data->expense += $data['amount'];
            $lims_gift_card_data->save();
            PaymentWithGiftCard::create($data);
        } elseif ($paying_method == 'Credit Card') {
            $lims_pos_setting_data = PosSetting::latest()->first();
            if ($lims_pos_setting_data->stripe_secret_key) {
                Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
                $token = $data['stripeToken'];
                $amount = $data['amount'];

                $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('customer_id', $lims_sale_data->customer_id)->first();

                if (!$lims_payment_with_credit_card_data) {
                    // Create a Customer:
                    $customer = \Stripe\Customer::create([
                        'source' => $token
                    ]);

                    // Charge the Customer instead of the card:
                    $charge = \Stripe\Charge::create([
                        'amount' => $amount * 100,
                        'currency' => 'usd',
                        'customer' => $customer->id,
                    ]);
                    $data['customer_stripe_id'] = $customer->id;
                } else {
                    $customer_id =
                        $lims_payment_with_credit_card_data->customer_stripe_id;

                    $charge = \Stripe\Charge::create([
                        'amount' => $amount * 100,
                        'currency' => 'usd',
                        'customer' => $customer_id, // Previously stored, then retrieved
                    ]);
                    $data['customer_stripe_id'] = $customer_id;
                }
                $data['customer_id'] = $lims_sale_data->customer_id;
                $data['charge_id'] = $charge->id;
                PaymentWithCreditCard::create($data);
            }
        } elseif ($paying_method == 'Cheque') {
            PaymentWithCheque::create($data);
        } elseif ($paying_method == 'Paypal') {
            $provider = new ExpressCheckout;
            $paypal_data['items'] = [];
            $paypal_data['items'][] = [
                'name' => 'Paid Amount',
                'price' => $data['amount'],
                'qty' => 1
            ];
            $paypal_data['invoice_id'] = $lims_payment_data->payment_reference;
            $paypal_data['invoice_description'] = "Reference: {$paypal_data['invoice_id']}";
            $paypal_data['return_url'] = url('/sale/paypalPaymentSuccess/' . $lims_payment_data->id);
            $paypal_data['cancel_url'] = url('/sale');

            $total = 0;
            foreach ($paypal_data['items'] as $item) {
                $total += $item['price'] * $item['qty'];
            }

            $paypal_data['total'] = $total;
            $response = $provider->setExpressCheckout($paypal_data);
            return redirect($response['paypal_link']);
        } elseif ($paying_method == 'Deposit') {
            $lims_customer_data->expense += $data['amount'];
            $lims_customer_data->save();
        } elseif ($paying_method == 'Points') {
            $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
            $used_points = ceil($data['amount'] / $lims_reward_point_setting_data->redeem_amount_per_unit_rp);

            $lims_payment_data->used_points = $used_points;
            $lims_payment_data->save();

            $lims_customer_data->points -= $used_points;
            $lims_customer_data->save();

            RewardPoint::query()->create([
                'points' => 0,
                'deducted_points' => $used_points,
                'customer_id' => $lims_customer_data->id,
                'note' => 'Redeemed for adding payment (Payment #' . $lims_payment_data->payment_reference . ')',
                'sale_id' => $lims_sale_data->id,
                'expired_at' => null,
            ]);
        }


        $accountingService = app(\App\Services\AccountingService::class);
        $result = $accountingService->recordPayment($lims_payment_data);
        if (!$result->success) {
            \Log::error('Accounting failed for Sale Payment', ['payment_id' => $lims_payment_data->id, 'error' => $result->error]);
            if (\Schema::hasColumn($lims_payment_data->getTable(), 'accounting_status')) {
                $lims_payment_data->accounting_status = 'failed';
                $lims_payment_data->save();
            }
        }




        $message = 'Payment created successfully';
        $mail_setting = MailSetting::latest()->first();
        if ($lims_customer_data->email && $mail_setting) {
            $mail_data['email'] = $lims_customer_data->email;
            $mail_data['sale_reference'] = $lims_sale_data->reference_no;
            $mail_data['payment_reference'] = $lims_payment_data->payment_reference;
            $mail_data['payment_method'] = $lims_payment_data->paying_method;
            $mail_data['grand_total'] = $lims_sale_data->grand_total;
            $mail_data['paid_amount'] = $lims_payment_data->amount;
            $mail_data['currency'] = config('currency');
            $mail_data['due'] = $balance;
            $this->setMailInfo($mail_setting);
            try {
                Mail::to($mail_data['email'])->send(new PaymentDetails($mail_data));
            } catch (\Exception $e) {
                $message = 'Payment created successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
            }
        }

        if (isset($data['installment_id']) && $data['installment_id'] != 0) {
            Installment::where('id', $data['installment_id'])->update([
                'status' => 'completed',
                'payment_date' => $data['payment_at'],
            ]);
            $lims_payment_data->installment_id = $data['installment_id'];
            $lims_payment_data->save();
            return redirect()->back()->with('message', $message);
        }

        // print
        if (isset($data['installment_id']) && $data['installment_id'] != 0) {
            Installment::where('id', $data['installment_id'])->update([
                'status' => 'completed',
                'payment_date' => $data['payment_at'],
            ]);
            $lims_payment_data->installment_id = $data['installment_id'];
            $lims_payment_data->save();
            return redirect()->back()->with('message', $message);
        }

        if (isset($data['print_receipt']) && $data['print_receipt'] == 1) {
            paymentReceipt($lims_payment_data->id);
        }

        return redirect('sales')->with('message', $message);
    }

    public function paymentReceipt($id)
    {
        $lims_payment_data = Payment::find($id);
        abort_unless($lims_payment_data, 404);
        $lims_sale_data = Sale::findOrFail($lims_payment_data->sale_id);
        $lims_customer_data = Customer::find($lims_sale_data->customer_id);
        $general_setting = gen_setting();
        $invoice_settings = InvoiceSetting::latest()->first();
        $lims_warehouse_data = Warehouse::find($lims_sale_data->warehouse_id);
        $lims_product_sale_data = Product_Sale::where('sale_id', $lims_sale_data->id)->get();

        return view('backend.sale.payment_receipt', compact(
            'lims_payment_data',
            'lims_sale_data',
            'lims_customer_data',
            'lims_warehouse_data',
            'general_setting',
            'invoice_settings',
            'lims_product_sale_data'
        ));
    }

    public function getPayment($id)
    {
        Sale::findOrFail($id);
        $lims_payment_list = Payment::where('sale_id', $id)
            ->whereNull('return_id')
            ->get();
        $date = [];
        $payment_reference = [];
        $paid_amount = [];
        $paying_method = [];
        $payment_id = [];
        $payment_note = [];
        $gift_card_id = [];
        $cheque_no = [];
        $change = [];
        $paying_amount = [];
        $payment_receiver = [];
        $account_name = [];
        $account_id = [];
        $payment_proof = [];
        $document = [];
        $payment_at = [];
        $installment_id = [];

        foreach ($lims_payment_list as $payment) {
            $installment_id[] = $payment->installment_id ?? 0;
            // added currency for previously inserted data
            if (!$payment->currency_id) {
                $lims_sale_data = Sale::find($payment->sale_id);
                if ($lims_sale_data) {
                    // dd($lims_sale_data);
                    $payment->currency_id = $lims_sale_data->currency_id;
                    $payment->exchange_rate = $lims_sale_data->exchange_rate ?? 1;
                }
            }

            $date[] = date(config('date_format'), strtotime($payment->created_at->toDateString())) . ' ' . $payment->created_at->toTimeString();
            $payment_reference[] = $payment->payment_reference;
            $paid_amount[] = $payment->amount;
            $change[] = $payment->change;
            $paying_method[] = $payment->paying_method;
            $paying_amount[] = $payment->amount + $payment->change;
            $payment_receiver[] = $payment->payment_receiver;

            if ($payment->paying_method == 'Gift Card') {
                $lims_payment_gift_card_data = PaymentWithGiftCard::where('payment_id', $payment->id)->first();
                $gift_card_id[] = $lims_payment_gift_card_data->gift_card_id;
            } elseif ($payment->paying_method == 'Cheque') {
                $lims_payment_cheque_data = PaymentWithCheque::where('payment_id', $payment->id)->first();
                if ($lims_payment_cheque_data)
                    $cheque_no[] = $lims_payment_cheque_data->cheque_no;
                else
                    $cheque_no[] = null;
            } else {
                $cheque_no[] = $gift_card_id[] = null;
            }
            $payment_id[] = $payment->id;
            $payment_note[] = $payment->payment_note;
            $lims_account_data = Account::find($payment->account_id);
            $account_name[] = $lims_account_data->name;
            $account_id[] = $lims_account_data->id;
            $payment_proof[] = $payment->payment_proof;
            $document[] = $payment->document;

            $payment->payment_at = $payment->payment_at ?? $payment->created_at;
            $payment->save();
            $payment_at[] = date(config('date_format'), strtotime($payment->payment_at->toDateString()));
        }
        $payments[] = $date;
        $payments[] = $payment_reference;
        $payments[] = $paid_amount;
        $payments[] = $paying_method;
        $payments[] = $payment_id;
        $payments[] = $payment_note;
        $payments[] = $cheque_no;
        $payments[] = $gift_card_id;
        $payments[] = $change;
        $payments[] = $paying_amount;
        $payments[] = $account_name;
        $payments[] = $account_id;
        $payments[] = $payment_receiver;
        $payments[] = $payment_proof;
        $payments[] = $document;
        $payments[] = $payment_at;
        $payments[] = $installment_id;

        return $payments;
    }

    public function updatePayment(Request $request)
    {
        $data = $request->all();
        $lims_payment_data = Payment::find($data['payment_id']);
        abort_unless($lims_payment_data, 404);
        $lims_sale_data = Sale::findOrFail($lims_payment_data->sale_id);
        $lims_customer_data = Customer::find($lims_sale_data->customer_id);
        //updating sale table
        $amount_dif = $lims_payment_data->amount - $data['edit_amount'];
        $lims_sale_data->paid_amount = $lims_sale_data->paid_amount - $amount_dif;
        $balance = $lims_sale_data->grand_total - $lims_sale_data->paid_amount;
        if ($balance > 0 || $balance < 0)
            $lims_sale_data->payment_status = 2;
        elseif ($balance == 0)
            $lims_sale_data->payment_status = 4;
        $lims_sale_data->save();

        if ($lims_payment_data->paying_method == 'Deposit') {
            $lims_customer_data->expense -= $lims_payment_data->amount;
            $lims_customer_data->save();
        } elseif ($lims_payment_data->paying_method == 'Points') {
            $lims_customer_data->points += $lims_payment_data->used_points;
            $lims_customer_data->save();
            $lims_payment_data->used_points = 0;
        }
        if ($data['edit_paid_by_id'] == 1)
            $lims_payment_data->paying_method = 'Cash';
        elseif ($data['edit_paid_by_id'] == 2) {
            if ($lims_payment_data->paying_method == 'Gift Card') {
                $lims_payment_gift_card_data = PaymentWithGiftCard::where('payment_id', $data['payment_id'])->first();

                $lims_gift_card_data = GiftCard::find($lims_payment_gift_card_data->gift_card_id);
                $lims_gift_card_data->expense -= $lims_payment_data->amount;
                $lims_gift_card_data->save();

                $lims_gift_card_data = GiftCard::find($data['gift_card_id']);
                $lims_gift_card_data->expense += $data['edit_amount'];
                $lims_gift_card_data->save();

                $lims_payment_gift_card_data->gift_card_id = $data['gift_card_id'];
                $lims_payment_gift_card_data->save();
            } else {
                $lims_payment_data->paying_method = 'Gift Card';
                $lims_gift_card_data = GiftCard::find($data['gift_card_id']);
                $lims_gift_card_data->expense += $data['edit_amount'];
                $lims_gift_card_data->save();
                PaymentWithGiftCard::create($data);
            }
        } elseif ($data['edit_paid_by_id'] == 3) {
            $lims_pos_setting_data = PosSetting::latest()->first();
            if ($lims_pos_setting_data->stripe_secret_key) {
                Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
                if ($lims_payment_data->paying_method == 'Credit Card') {
                    $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $lims_payment_data->id)->first();

                    \Stripe\Refund::create(array(
                        "charge" => $lims_payment_with_credit_card_data->charge_id,
                    ));

                    $customer_id =
                        $lims_payment_with_credit_card_data->customer_stripe_id;

                    $charge = \Stripe\Charge::create([
                        'amount' => $data['edit_amount'] * 100,
                        'currency' => 'usd',
                        'customer' => $customer_id
                    ]);
                    $lims_payment_with_credit_card_data->charge_id = $charge->id;
                    $lims_payment_with_credit_card_data->save();
                } else {
                    $token = $data['stripeToken'];
                    $amount = $data['edit_amount'];
                    $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('customer_id', $lims_sale_data->customer_id)->first();

                    if (!$lims_payment_with_credit_card_data) {
                        $customer = \Stripe\Customer::create([
                            'source' => $token
                        ]);

                        $charge = \Stripe\Charge::create([
                            'amount' => $amount * 100,
                            'currency' => 'usd',
                            'customer' => $customer->id,
                        ]);
                        $data['customer_stripe_id'] = $customer->id;
                    } else {
                        $customer_id =
                            $lims_payment_with_credit_card_data->customer_stripe_id;

                        $charge = \Stripe\Charge::create([
                            'amount' => $amount * 100,
                            'currency' => 'usd',
                            'customer' => $customer_id
                        ]);
                        $data['customer_stripe_id'] = $customer_id;
                    }
                    $data['customer_id'] = $lims_sale_data->customer_id;
                    $data['charge_id'] = $charge->id;
                    PaymentWithCreditCard::create($data);
                }
            }
            $lims_payment_data->paying_method = 'Credit Card';
        } elseif ($data['edit_paid_by_id'] == 4) {
            if ($lims_payment_data->paying_method == 'Cheque') {
                $lims_payment_cheque_data = PaymentWithCheque::where('payment_id', $data['payment_id'])->first();
                if ($lims_payment_cheque_data) {
                    $lims_payment_cheque_data->cheque_no = $data['edit_cheque_no'];
                    $lims_payment_cheque_data->save();
                } elseif ($data['edit_cheque_no']) {
                    PaymentWithCheque::create([
                        'payment_id' => $lims_payment_data->id,
                        'cheque_no' => $data['edit_cheque_no']
                    ]);
                }
            } else {
                $lims_payment_data->paying_method = 'Cheque';
                $data['cheque_no'] = $data['edit_cheque_no'];
                PaymentWithCheque::create($data);
            }
        } elseif ($data['edit_paid_by_id'] == 5) {
            //updating payment data
            $lims_payment_data->amount = $data['edit_amount'];
            $lims_payment_data->paying_method = 'Paypal';
            $lims_payment_data->payment_note = $data['edit_payment_note'];
            $lims_payment_data->save();

            $provider = new ExpressCheckout;
            $paypal_data['items'] = [];
            $paypal_data['items'][] = [
                'name' => 'Paid Amount',
                'price' => $data['edit_amount'],
                'qty' => 1
            ];
            $paypal_data['invoice_id'] = $lims_payment_data->payment_reference;
            $paypal_data['invoice_description'] = "Reference: {$paypal_data['invoice_id']}";
            $paypal_data['return_url'] = url('/sale/paypalPaymentSuccess/' . $lims_payment_data->id);
            $paypal_data['cancel_url'] = url('/sale');

            $total = 0;
            foreach ($paypal_data['items'] as $item) {
                $total += $item['price'] * $item['qty'];
            }

            $paypal_data['total'] = $total;
            $response = $provider->setExpressCheckout($paypal_data);
            return redirect($response['paypal_link']);
        } elseif ($data['edit_paid_by_id'] == 6) {
            $lims_payment_data->paying_method = 'Deposit';
            $lims_customer_data->expense += $data['edit_amount'];
            $lims_customer_data->save();
        } elseif ($data['edit_paid_by_id'] == 7) {
            $lims_payment_data->paying_method = 'Points';
            $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
            $used_points = ceil($data['edit_amount'] / $lims_reward_point_setting_data->redeem_amount_per_unit_rp);
            $lims_payment_data->used_points = $used_points;
            $lims_customer_data->points -= $used_points;
            $lims_customer_data->save();

            RewardPoint::query()->create([
                'points' => 0,
                'deducted_points' => $used_points,
                'customer_id' => $lims_customer_data->id,
                'note' => 'Redeemed for updating payment (Payment #' . $lims_payment_data->payment_reference . ')',
                'sale_id' => $lims_payment_data->sale_id,
                'expired_at' => null,
            ]);
        } else {
            $lims_payment_data->paying_method = ucfirst($data['edit_paid_by_id']);
        }

        if (isset($data['payment_at'])) {
            $data['payment_at'] = normalize_to_sql_datetime($data['payment_at']);
        } else {
            $data['payment_at'] = date('Y-m-d H:i:s');
        }

        //updating payment data
        $lims_payment_data->account_id = $data['account_id'];
        $lims_payment_data->amount = $data['edit_amount'];
        $lims_payment_data->change = $data['edit_paying_amount'] - $data['edit_amount'];
        $lims_payment_data->payment_note = $data['edit_payment_note'];
        $lims_payment_data->payment_note = $data['edit_payment_note'];
        $lims_payment_data->payment_receiver = $data['payment_receiver'];
        $lims_payment_data->payment_at = $data['payment_at'];
        $lims_payment_data->currency_id = $lims_sale_data->currency_id;
        $lims_payment_data->exchange_rate = $lims_sale_data->exchange_rate ?? 1;
        $lims_payment_data->save();


        $accountingService = app(\App\Services\AccountingService::class);
        $accountingService->reverseTransaction(get_class($lims_payment_data), $lims_payment_data->id, '_reversed');
        $result = $accountingService->recordPayment($lims_payment_data, 'payment_updated');
        if (!$result->success) {
            \Log::error('Accounting failed for Sale Payment Update', ['payment_id' => $lims_payment_data->id, 'error' => $result->error]);
            if (\Schema::hasColumn($lims_payment_data->getTable(), 'accounting_status')) {
                $lims_payment_data->accounting_status = 'failed';
                $lims_payment_data->save();
            }
        }


        // ROUTE THROUGH UNIFIED PAYMENT RECEIVED CHANNEL HELPER WITH CORRECT PAYLOAD 
        $this->dispatchPaymentNotifications($lims_sale_data, $data['edit_amount']);


        $message = 'Payment updated successfully';
        //collecting male data
        $mail_setting = MailSetting::latest()->first();
        if ($lims_customer_data->email && $mail_setting) {
            $mail_data['email'] = $lims_customer_data->email;
            $mail_data['sale_reference'] = $lims_sale_data->reference_no;
            $mail_data['payment_reference'] = $lims_payment_data->payment_reference;
            $mail_data['payment_method'] = $lims_payment_data->paying_method;
            $mail_data['grand_total'] = $lims_sale_data->grand_total;
            $mail_data['paid_amount'] = $lims_payment_data->amount;
            $mail_data['currency'] = config('currency');
            $mail_data['due'] = $balance;
            $this->setMailInfo($mail_setting);
            try {
                Mail::to($mail_data['email'])->send(new PaymentDetails($mail_data));
            } catch (\Exception $e) {
                $message = 'Payment updated successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
            }
        }

        if (isset($request['installment_id']) && $request['installment_id'] != 0) {
            Installment::where('id', $request['installment_id'])->update(['payment_date' => $data['payment_at']]);
        }

        return redirect('sales')->with('message', $message);
    }

    public function deletePayment(Request $request)
    {
        $lims_payment_data = Payment::find($request['id']);
        abort_unless($lims_payment_data, 404);
        $lims_sale_data = Sale::where('id', $lims_payment_data->sale_id)->whereNull('deleted_at')->firstOrFail();
        $lims_sale_data->paid_amount -= $lims_payment_data->amount;
        $balance = $lims_sale_data->grand_total - $lims_sale_data->paid_amount;
        if ($balance > 0 || $balance < 0)
            $lims_sale_data->payment_status = 2;
        elseif ($balance == 0)
            $lims_sale_data->payment_status = 4;
        $lims_sale_data->save();

        if ($lims_payment_data->paying_method == 'Gift Card') {
            $lims_payment_gift_card_data = PaymentWithGiftCard::where('payment_id', $request['id'])->first();
            $lims_gift_card_data = GiftCard::find($lims_payment_gift_card_data->gift_card_id);
            $lims_gift_card_data->expense -= $lims_payment_data->amount;
            $lims_gift_card_data->save();
            $lims_payment_gift_card_data->delete();
        } elseif ($lims_payment_data->paying_method == 'Credit Card') {
            $lims_pos_setting_data = PosSetting::latest()->first();
            if ($lims_pos_setting_data->stripe_secret_key) {
                $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $request['id'])->first();
                Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
                \Stripe\Refund::create(array(
                    "charge" => $lims_payment_with_credit_card_data->charge_id,
                ));

                $lims_payment_with_credit_card_data->delete();
            }
        } elseif ($lims_payment_data->paying_method == 'Cheque') {
            $lims_payment_cheque_data = PaymentWithCheque::where('payment_id', $request['id'])->first();
            $lims_payment_cheque_data->delete();
        } elseif ($lims_payment_data->paying_method == 'Paypal') {
            $lims_payment_paypal_data = PaymentWithPaypal::where('payment_id', $request['id'])->first();
            if ($lims_payment_paypal_data) {
                $provider = new ExpressCheckout;
                $response = $provider->refundTransaction($lims_payment_paypal_data->transaction_id);
                $lims_payment_paypal_data->delete();
            }
        } elseif ($lims_payment_data->paying_method == 'Deposit') {
            $lims_customer_data = Customer::find($lims_sale_data->customer_id);
            $lims_customer_data->expense -= $lims_payment_data->amount;
            $lims_customer_data->save();
        } elseif ($lims_payment_data->paying_method == 'Points') {
            $lims_customer_data = Customer::find($lims_sale_data->customer_id);
            $lims_customer_data->points += $lims_payment_data->used_points;
            $lims_customer_data->save();
        }

        $accountingService = app(\App\Services\AccountingService::class);
        $accountingService->reverseTransaction(get_class($lims_payment_data), $lims_payment_data->id, '_deleted');

        $lims_payment_data->delete();

        if (isset($request['installment_id']) && $request['installment_id'] != 0) {
            Installment::where('id', $request['installment_id'])->update(['status' => 'pending']);
        }

        return redirect('sales')->with('not_permitted', __('db.Payment deleted successfully'));
    }

    public function todaySale()
    {
        // 🔹 Total sales (normalized by exchange_rate)
        $data['total_sale_amount'] = Sale::whereDate('created_at', date("Y-m-d"))
            ->select(DB::raw('SUM(grand_total  / COALESCE(NULLIF(exchange_rate, 0), 1)) as total'))
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('sales.sale_type', '!=', 'opening balance')
                    ->orWhereNull('sales.sale_type');
            })
            ->value('total');

        // 🔹 Total payments (join with sales to access exchange_rate)
        $data['total_payment'] = Payment::join('sales', 'payments.sale_id', '=', 'sales.id')
            ->whereDate('payments.created_at', date("Y-m-d"))
            ->whereNull('payments.return_id')
            ->whereNull('sales.deleted_at')
            ->select(DB::raw('SUM(payments.amount  / COALESCE(NULLIF(sales.exchange_rate, 0), 1)) as total'))
            ->value('total');

        // 🔹 Payments by method (normalized by exchange_rate)
        $methods = ['Cash', 'Credit Card', 'Gift Card', 'Deposit', 'Cheque', 'Paypal'];
        foreach ($methods as $method) {
            $key = strtolower(str_replace(' ', '_', $method)) . '_payment';
            $data[$key] = Payment::join('sales', 'payments.sale_id', '=', 'sales.id')
                ->whereNull('sales.deleted_at')
                ->whereNull('payments.return_id')
                ->where('payments.paying_method', $method)
                ->whereDate('payments.created_at', date("Y-m-d"))
                ->select(DB::raw('SUM(payments.amount  / COALESCE(NULLIF(sales.exchange_rate, 0), 1)) as total'))
                ->value('total');
        }

        // 🔹 Sale returns (normalize by exchange_rate too, assuming linked to sales)
        $data['total_sale_return'] = Returns::join('sales', 'returns.sale_id', '=', 'sales.id')
            ->whereDate('returns.created_at', date("Y-m-d"))
            ->whereNull('sales.deleted_at')
            ->select(DB::raw('SUM(returns.grand_total  / COALESCE(NULLIF(sales.exchange_rate, 0), 1)) as total'))
            ->value('total');

        // 🔹 Expenses (assuming already stored in base currency)
        $data['total_expense'] = Expense::whereDate('created_at', date("Y-m-d"))
            ->sum('amount');

        // 🔹 Net cash = payments - (returns + expenses)
        $data['total_cash'] = $data['total_payment'] - ($data['total_sale_return'] + $data['total_expense']);

        return $data;
    }

    public function todayProfit($warehouse_id)
    {
        // 🔹 Collect sales data with exchange rate normalization
        if ($warehouse_id == 0) {
            $product_sale_data = Product_Sale::join('sales', 'sales.id', '=', 'product_sales.sale_id')
                ->select(DB::raw('
                    product_sales.product_id,
                    product_sales.product_batch_id,
                    SUM(product_sales.qty) as sold_qty,
                    SUM(product_sales.total  / COALESCE(NULLIF(sales.exchange_rate, 0), 1)) as sold_amount
                '))
                ->whereNull('sales.deleted_at')
                ->whereDate('sales.created_at', date("Y-m-d"))
                ->groupBy('product_sales.product_id', 'product_sales.product_batch_id')
                ->get();
        } else {
            $product_sale_data = Sale::join('product_sales', 'sales.id', '=', 'product_sales.sale_id')
                ->select(DB::raw('
                    product_sales.product_id,
                    product_sales.product_batch_id,
                    SUM(product_sales.qty) as sold_qty,
                    SUM(product_sales.total  / COALESCE(NULLIF(sales.exchange_rate, 0), 1)) as sold_amount
                '))
                ->whereNull('sales.deleted_at')
                ->where('sales.warehouse_id', $warehouse_id)
                ->whereDate('sales.created_at', date("Y-m-d"))
                ->groupBy('product_sales.product_id', 'product_sales.product_batch_id')
                ->get();
        }

        $product_revenue = 0;
        $product_cost = 0;
        $profit = 0;

        foreach ($product_sale_data as $product_sale) {
            // 🔹 Purchases (base currency assumed)
            if ($warehouse_id == 0) {
                if ($product_sale->product_batch_id) {
                    $product_purchase_data = ProductPurchase::where([
                        ['product_id', $product_sale->product_id],
                        ['product_batch_id', $product_sale->product_batch_id]
                    ])->get();
                } else {
                    $product_purchase_data = ProductPurchase::where('product_id', $product_sale->product_id)->get();
                }
            } else {
                if ($product_sale->product_batch_id) {
                    $product_purchase_data = Purchase::join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')
                        ->where([
                            ['product_purchases.product_id', $product_sale->product_id],
                            ['product_purchases.product_batch_id', $product_sale->product_batch_id],
                            ['purchases.warehouse_id', $warehouse_id]
                        ])
                        ->whereNull('purchases.deleted_at')
                        ->select('product_purchases.*')
                        ->get();
                } else {
                    $product_purchase_data = Purchase::join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')
                        ->where([
                            ['product_purchases.product_id', $product_sale->product_id],
                            ['purchases.warehouse_id', $warehouse_id]
                        ])
                        ->whereNull('purchases.deleted_at')
                        ->select('product_purchases.*')
                        ->get();
                }
            }

            $purchased_qty = 0;
            $purchased_amount = 0;
            $sold_qty = $product_sale->sold_qty;

            // 🔹 Revenue is already normalized
            $product_revenue += $product_sale->sold_amount;

            foreach ($product_purchase_data as $product_purchase) {
                $purchased_qty += $product_purchase->qty;
                $purchased_amount += $product_purchase->total;

                if ($purchased_qty >= $sold_qty) {
                    $qty_diff = $purchased_qty - $sold_qty;
                    $unit_cost = $product_purchase->total / $product_purchase->qty;
                    $purchased_amount -= ($qty_diff * $unit_cost);
                    break;
                }
            }

            $product_cost += $purchased_amount;
            $profit += $product_sale->sold_amount - $purchased_amount;
        }

        $data['product_revenue'] = number_format($product_revenue, config('decimal'));
        $data['product_cost'] = number_format($product_cost, config('decimal'));

        // 🔹 Expenses
        if ($warehouse_id == 0) {
            $data['expense_amount'] = Expense::whereDate('created_at', date("Y-m-d"))
                ->sum('amount');
        } else {
            $data['expense_amount'] = Expense::where('warehouse_id', $warehouse_id)
                ->whereDate('created_at', date("Y-m-d"))
                ->sum('amount');
        }

        $data['profit'] = $profit - $data['expense_amount'];
        $data['profit'] = number_format($data['profit'], config('decimal'));

        return $data;
    }

    private function phase2LockedSaleIds(array $ids): array
    {
        $zatcaIntegration = app(\App\Services\ZatcaIntegrationService::class);
        $saleIds = array_values(array_unique(array_filter(array_map('intval', $ids))));

        return array_values(array_filter(
            $saleIds,
            fn (int $saleId): bool => $zatcaIntegration->isSourceLocked('sale', $saleId)
        ));
    }

    public function deleteBySelection(Request $request)
    {
        $sale_id = (array) $request['saleIdArray'];
        $lockedSaleIds = $this->phase2LockedSaleIds($sale_id);

        if ($lockedSaleIds) {
            return response(
                'Phase 2 fiscal invoices exist for the selected sale(s): '.implode(', ', $lockedSaleIds).'. Issue sale returns/credit notes instead; the original fiscal records cannot be deleted.',
                422
            );
        }

        foreach ($sale_id as $id) {
            $lims_sale_data = Sale::find($id);
            $return_ids = Returns::where('sale_id', $id)->pluck('id')->toArray();
            if (count($return_ids)) {
                ProductReturn::whereIn('return_id', $return_ids)->delete();
                Returns::whereIn('id', $return_ids)->delete();
            }
            $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
            $lims_delivery_data = Delivery::where('sale_id', $id)->get();
            $lims_packing_slip_data = PackingSlip::where('sale_id', $id)->get();
            if ($lims_sale_data->sale_status == 3)
                $message = 'Draft deleted successfully';
            else
                $message = 'Sale deleted successfully';
            foreach ($lims_product_sale_data as $product_sale) {
                $lims_product_data = Product::find($product_sale->product_id);
                //adjust product quantity
                if (in_array((int)$lims_sale_data->sale_status, [1, 5, 6], true) && ($lims_product_data->type == 'combo')) {
                    if (!in_array('manufacturing', explode(',', config('addons')))) {
                        $product_list = explode(",", $lims_product_data->product_list);
                        if ($lims_product_data->variant_list)
                            $variant_list = explode(",", $lims_product_data->variant_list);
                        else
                            $variant_list = [];
                        $qty_list = explode(",", $lims_product_data->qty_list);

                        foreach ($product_list as $index => $child_id) {
                            $child_data = Product::find($child_id);
                            if (count($variant_list) && $variant_list[$index]) {
                                $child_product_variant_data = ProductVariant::where([
                                    ['product_id', $child_id],
                                    ['variant_id', $variant_list[$index]]
                                ])->first();

                                $child_warehouse_data = Product_Warehouse::where([
                                    ['product_id', $child_id],
                                    ['variant_id', $variant_list[$index]],
                                    ['warehouse_id', $lims_sale_data->warehouse_id],
                                ])->first();

                                $child_product_variant_data->qty += $product_sale->qty * $qty_list[$index];
                                $child_product_variant_data->save();
                            } else {
                                $child_warehouse_data = Product_Warehouse::where([
                                    ['product_id', $child_id],
                                    ['warehouse_id', $lims_sale_data->warehouse_id],
                                ])->first();
                            }

                            $child_data->qty += $product_sale->qty * $qty_list[$index];
                            $child_data->save();

                            if (gen_setting()->without_stock == 'no' && $child_warehouse_data) {
                                $child_warehouse_data->qty += $product_sale->qty * $qty_list[$index];
                                $child_warehouse_data->save();
                            }
                        }
                    }
                } elseif (in_array((int)$lims_sale_data->sale_status, [1, 5, 6], true) && ($product_sale->sale_unit_id != 0)) {
                    $lims_sale_unit_data = Unit::find($product_sale->sale_unit_id);
                    if (!$lims_sale_unit_data) {
                        \Log::error("Calculation integrity error: Sale unit ID {$product_sale->sale_unit_id} not found for product ID {$product_sale->product_id} in sale ID {$id}.");
                        throw new \Exception("Data integrity error: referenced unit #{$product_sale->sale_unit_id} could not be resolved for sale calculation.");
                    }
                    if ($lims_sale_unit_data->operator == '*')
                        $product_sale->qty = $product_sale->qty * $lims_sale_unit_data->operation_value;
                    else
                        $product_sale->qty = $product_sale->qty / $lims_sale_unit_data->operation_value;
                    if ($product_sale->variant_id) {
                        $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($lims_product_data->id, $product_sale->variant_id)->first();
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($lims_product_data->id, $product_sale->variant_id, $lims_sale_data->warehouse_id)->first();
                        $lims_product_variant_data->qty += $product_sale->qty;
                        $lims_product_variant_data->save();
                    } elseif ($product_sale->product_batch_id) {
                        $lims_product_batch_data = ProductBatch::find($product_sale->product_batch_id);
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_batch_id', $product_sale->product_batch_id],
                            ['warehouse_id', $lims_sale_data->warehouse_id]
                        ])->first();

                        $lims_product_batch_data->qty -= $product_sale->qty;
                        $lims_product_batch_data->save();
                    } else {
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($lims_product_data->id, $lims_sale_data->warehouse_id)->first();
                    }

                    $lims_product_data->qty += $product_sale->qty;
                    $lims_product_data->save();

                    if (gen_setting()->without_stock == 'no' && $lims_product_warehouse_data) {
                        $lims_product_warehouse_data->qty += $product_sale->qty;
                        $lims_product_warehouse_data->save();
                    }

                    //restore imei numbers
                    if ($product_sale->imei_number && !str_contains($product_sale->imei_number, "null")) {
                        if ($lims_product_warehouse_data->imei_number)
                            $lims_product_warehouse_data->imei_number .= ',' . $product_sale->imei_number;
                        else
                            $lims_product_warehouse_data->imei_number = $product_sale->imei_number;
                        $lims_product_warehouse_data->save();
                    }
                }

                if (in_array((int)$lims_sale_data->sale_status, [1, 5, 6], true) &&
                    $this->restaurantModifiersAvailable()) {
                    $modifierSnapshots = \Modules\Restaurant\Entities\ProductSaleModifier::where(
                        'product_sale_id',
                        $product_sale->id
                    )->get();

                    foreach ($modifierSnapshots as $modifierSnapshot) {
                        app(\Modules\Restaurant\Services\ModifierInventoryService::class)
                            ->adjustSnapshot(
                                $modifierSnapshot,
                                (float) $product_sale->qty,
                                (int) $lims_sale_data->warehouse_id,
                                1
                            );
                    }
                }

                $product_sale->delete();
            }
            $lims_payment_data = Payment::where('sale_id', $id)->get();
            foreach ($lims_payment_data as $payment) {
                if ($payment->paying_method == 'Gift Card') {
                    $lims_payment_with_gift_card_data = PaymentWithGiftCard::where('payment_id', $payment->id)->first();
                    $lims_gift_card_data = GiftCard::find($lims_payment_with_gift_card_data->gift_card_id);
                    $lims_gift_card_data->expense -= $payment->amount;
                    $lims_gift_card_data->save();
                    $lims_payment_with_gift_card_data->delete();
                } elseif ($payment->paying_method == 'Cheque') {
                    $lims_payment_cheque_data = PaymentWithCheque::where('payment_id', $payment->id)->first();
                    $lims_payment_cheque_data->delete();
                } elseif ($payment->paying_method == 'Credit Card') {
                    $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $payment->id)->first();
                    $lims_payment_with_credit_card_data->delete();
                } elseif ($payment->paying_method == 'Paypal') {
                    $lims_payment_paypal_data = PaymentWithPaypal::where('payment_id', $payment->id)->first();
                    if ($lims_payment_paypal_data)
                        $lims_payment_paypal_data->delete();
                } elseif ($payment->paying_method == 'Deposit') {
                    $lims_customer_data = Customer::find($lims_sale_data->customer_id);
                    $lims_customer_data->expense -= $payment->amount;
                    $lims_customer_data->save();
                }
                $payment->delete();
            }
            if ($lims_delivery_data->isNotEmpty()) {
                $lims_delivery_data->each->delete();
            }
            if ($lims_packing_slip_data->isNotEmpty()) {
                $lims_packing_slip_data->each->delete();
            }
            if ($lims_sale_data->coupon_id) {
                $lims_coupon_data = Coupon::find($lims_sale_data->coupon_id);
                $lims_coupon_data->used -= 1;
                $lims_coupon_data->save();
            }

            InstallmentPlan::where([
                'reference_type' => 'sale',
                'reference_id' => $lims_sale_data->id,
            ])->delete();

            $lims_sale_data->deleted_by = Auth::id();
            $lims_sale_data->save();
            $lims_sale_data->delete();
            $this->fileDelete(public_path('documents/sale/'), $lims_sale_data->document);
        }
        return 'Sale deleted successfully!';
    }

    public function destroy($id)
    {
        if ($this->phase2LockedSaleIds([(int) $id])) {
            return response('A Phase 2 invoice exists for this sale. Issue a return/credit note; the original fiscal record cannot be deleted.', 422);
        }

        $url = url()->previous();

        try {
        DB::beginTransaction();

        $lims_sale_data = Sale::find($id);

        // remove this sale reward point
        $lims_reward_point = RewardPoint::query()->where('sale_id', $lims_sale_data->id)->first();
        if ($lims_reward_point) {

            // remove from customer table reward pint
            $lims_customer_data = Customer::find($lims_sale_data->customer_id);
            $lims_customer_data->points -= $lims_reward_point->points;
            $lims_customer_data->save();

            // delete reward point from reward point table
            $lims_reward_point->delete();
        }

        $return_ids = Returns::where('sale_id', $id)->pluck('id')->toArray();
        if (count($return_ids)) {
            ProductReturn::whereIn('return_id', $return_ids)->delete();
            Returns::whereIn('id', $return_ids)->delete();
        }
        $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
        $lims_delivery_data = Delivery::where('sale_id', $id)->get();
        $lims_packing_slip_data = PackingSlip::where('sale_id', $id)->get();
        if ($lims_sale_data->sale_status == 3)
            $message = 'Draft deleted successfully';
        else
            $message = 'Sale deleted successfully';


        $log_data['item_description'] = '';

        foreach ($lims_product_sale_data as $product_sale) {
            $lims_product_data = Product::find($product_sale->product_id);
            if ($product_sale->sale_unit_id != 0) {
                $lims_sale_unit_data = Unit::find($product_sale->sale_unit_id);
                $unitCode = $lims_sale_unit_data ? $lims_sale_unit_data->unit_code : '—';
                $log_data['item_description'] .= $lims_product_data->name . '-' . $product_sale->qty . ' ' . $unitCode . '<br>';
            } else {
                $log_data['item_description'] .= $lims_product_data->name . '-' . $product_sale->qty . '<br>';
            }

            //adjust product quantity
            if (in_array((int)$lims_sale_data->sale_status, [1, 5, 6], true) && ($lims_product_data->type == 'combo')) {
                // if(!in_array('manufacturing',explode(',',config('addons')))) {
                $product_list = explode(",", $lims_product_data->product_list);
                $variant_list = explode(",", $lims_product_data->variant_list);
                $qty_list = explode(",", $lims_product_data->qty_list);
                if ($lims_product_data->variant_list)
                    $variant_list = explode(",", $lims_product_data->variant_list);
                else
                    $variant_list = [];
                foreach ($product_list as $index => $child_id) {
                    $child_data = Product::find($child_id);
                    if (count($variant_list) && $variant_list[$index]) {
                        $child_product_variant_data = ProductVariant::where([
                            ['product_id', $child_id],
                            ['variant_id', $variant_list[$index]]
                        ])->first();

                        $child_warehouse_data = Product_Warehouse::where([
                            ['product_id', $child_id],
                            ['variant_id', $variant_list[$index]],
                            ['warehouse_id', $lims_sale_data->warehouse_id],
                        ])->first();

                        $child_product_variant_data->qty += $product_sale->qty * $qty_list[$index];
                        $child_product_variant_data->save();
                    } else {
                        $child_warehouse_data = Product_Warehouse::where([
                            ['product_id', $child_id],
                            ['warehouse_id', $lims_sale_data->warehouse_id],
                        ])->first();
                    }

                    $child_data->qty += $product_sale->qty * $qty_list[$index];
                    $child_data->save();

                    if ($child_warehouse_data) {
                        $child_warehouse_data->qty += $product_sale->qty * $qty_list[$index];
                        $child_warehouse_data->save();
                    }
                }
                // }
            }

            if (in_array((int)$lims_sale_data->sale_status, [1, 5, 6], true) && ($product_sale->sale_unit_id != 0)) {
                $lims_sale_unit_data = Unit::find($product_sale->sale_unit_id);
                if (!$lims_sale_unit_data) {
                    \Log::error("Calculation integrity error: Sale unit ID {$product_sale->sale_unit_id} not found for product ID {$product_sale->product_id} in sale ID {$id}.");
                    throw new \Exception("Data integrity error: referenced unit #{$product_sale->sale_unit_id} could not be resolved for sale calculation.");
                }
                if ($lims_sale_unit_data->operator == '*')
                    $product_sale->qty = $product_sale->qty * $lims_sale_unit_data->operation_value;
                else
                    $product_sale->qty = $product_sale->qty / $lims_sale_unit_data->operation_value;
                if ($product_sale->variant_id) {
                    $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($lims_product_data->id, $product_sale->variant_id)->first();
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($lims_product_data->id, $product_sale->variant_id, $lims_sale_data->warehouse_id)->first();
                    $lims_product_variant_data->qty += $product_sale->qty;
                    $lims_product_variant_data->save();
                } elseif ($product_sale->product_batch_id) {
                    $lims_product_batch_data = ProductBatch::find($product_sale->product_batch_id);
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_batch_id', $product_sale->product_batch_id],
                        ['warehouse_id', $lims_sale_data->warehouse_id]
                    ])->first();

                    $lims_product_batch_data->qty -= $product_sale->qty;
                    $lims_product_batch_data->save();
                } else {
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($lims_product_data->id, $lims_sale_data->warehouse_id)->first();
                }

                $lims_product_data->qty += $product_sale->qty;
                $lims_product_data->save();

                if ($lims_product_warehouse_data) {
                    $lims_product_warehouse_data->qty += $product_sale->qty;
                    $lims_product_warehouse_data->save();
                }

                //restore imei numbers
                if ($product_sale->imei_number && !str_contains($product_sale->imei_number, "null")) {
                    if ($lims_product_warehouse_data->imei_number)
                        $lims_product_warehouse_data->imei_number .= ',' . $product_sale->imei_number;
                    else
                        $lims_product_warehouse_data->imei_number = $product_sale->imei_number;
                    $lims_product_warehouse_data->save();
                }
            }

            if (in_array((int)$lims_sale_data->sale_status, [1, 5, 6], true) &&
                $this->restaurantModifiersAvailable()) {
                $productSaleModifiers = \Modules\Restaurant\Entities\ProductSaleModifier::where(
                    'product_sale_id',
                    $product_sale->id
                )->get();

                foreach ($productSaleModifiers as $modifierSnapshot) {
                    app(\Modules\Restaurant\Services\ModifierInventoryService::class)
                        ->adjustSnapshot(
                            $modifierSnapshot,
                            (float) $product_sale->qty,
                            (int) $lims_sale_data->warehouse_id,
                            1
                        );
                }
            }

            $product_sale->delete();
        }

        $lims_payment_data = Payment::where('sale_id', $id)->get();
        foreach ($lims_payment_data as $payment) {
            $accountingService = app(\App\Services\AccountingService::class);
            $accountingService->reverseTransaction(get_class($payment), $payment->id, '_deleted');

            if ($payment->paying_method == 'Gift Card') {
                $lims_payment_with_gift_card_data = PaymentWithGiftCard::where('payment_id', $payment->id)->first();
                $lims_gift_card_data = GiftCard::find($lims_payment_with_gift_card_data->gift_card_id);
                $lims_gift_card_data->expense -= $payment->amount;
                $lims_gift_card_data->save();
                $lims_payment_with_gift_card_data->delete();
            } elseif ($payment->paying_method == 'Cheque') {
                $lims_payment_cheque_data = PaymentWithCheque::where('payment_id', $payment->id)->first();
                if ($lims_payment_cheque_data)
                    $lims_payment_cheque_data->delete();
            } elseif ($payment->paying_method == 'Credit Card') {
                $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $payment->id)->first();
                if ($lims_payment_with_credit_card_data)
                    $lims_payment_with_credit_card_data->delete();
            } elseif ($payment->paying_method == 'Paypal') {
                $lims_payment_paypal_data = PaymentWithPaypal::where('payment_id', $payment->id)->first();
                if ($lims_payment_paypal_data)
                    $lims_payment_paypal_data->delete();
            } elseif ($payment->paying_method == 'Deposit') {
                $lims_customer_data = Customer::find($lims_sale_data->customer_id);
                $lims_customer_data->expense -= $payment->amount;
                $lims_customer_data->save();
            }
            $payment->delete();
        }
        if ($lims_delivery_data->isNotEmpty()) {
            $lims_delivery_data->each->delete();
        }
        if ($lims_packing_slip_data->isNotEmpty()) {
            $lims_packing_slip_data->each->delete();
        }
        if ($lims_sale_data->coupon_id) {
            $lims_coupon_data = Coupon::find($lims_sale_data->coupon_id);
            $lims_coupon_data->used -= 1;
            $lims_coupon_data->save();
        }
        $lims_sale_data->deleted_by = Auth::id();
        $lims_sale_data->save();

        //creating log
        $log_data['action'] = 'Sale Deleted';
        $log_data['user_id'] = Auth::id();
        $log_data['reference_no'] = $lims_sale_data->reference_no;
        $log_data['date'] = $lims_sale_data->created_at->toDateString();
        // $log_data['admin_email'] = config('admin_email');
        $log_data['admin_message'] = Auth::user()->name . ' has deleted a sale. Reference No: ' . $lims_sale_data->reference_no;
        $log_data['user_email'] = Auth::user()->email;
        $log_data['user_name'] = Auth::user()->name;
        $log_data['user_message'] = 'You just deleted a sale. Reference No: ' . $lims_sale_data->reference_no;
        // $log_data['mail_setting'] = $mail_setting = MailSetting::latest()->first();
        $this->createActivityLog($log_data);

        InstallmentPlan::where([
            'reference_type' => 'sale',
            'reference_id' => $lims_sale_data->id,
        ])->delete();

        $lims_sale_data->delete();
        $this->fileDelete(public_path('documents/sale/'), $lims_sale_data->document);

        DB::commit();

        try {
            $accountingService = app(\App\Services\AccountingService::class);
            $revRes = $accountingService->reverseTransaction(\App\Models\Sale::class, $id, '_deleted');
            if (!$revRes->success) {
                throw new \App\Exceptions\AccountingException($revRes->error);
            }
            $deletedSale = \App\Models\Sale::withTrashed()->find($id);
            if ($deletedSale && \Schema::hasColumn($deletedSale->getTable(), 'accounting_status')) {
                $deletedSale->accounting_status = 'reversed';
                $deletedSale->save();
            }
        } catch (\App\Exceptions\AccountingException $e) {
            \Log::error('Accounting error on Sale Destroy: ' . $e->getMessage());
        }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Sale deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('not_permitted', 'Sale deletion failed: ' . $e->getMessage());
        }

        return Redirect::to($url)->with('not_permitted', $message);
    }

    public function registerIPN()
    {
        $pg = DB::table('external_services')->where('name', 'Pesapal')->where('type', 'payment')->first();
        $lines = explode(';', $pg->details);
        $keys = explode(',', $lines[0]);
        $vals = explode(',', $lines[1]);

        $results = array_combine($keys, $vals);

        $APP_ENVIROMENT = $results['Mode'];

        $token = $this->accessToken();

        if ($APP_ENVIROMENT == 'sandbox') {
            $ipnRegistrationUrl = "https://cybqa.pesapal.com/pesapalv3/api/URLSetup/RegisterIPN";
        } elseif ($APP_ENVIROMENT == 'live') {
            $ipnRegistrationUrl = "https://pay.pesapal.com/v3/api/URLSetup/RegisterIPN";
        } else {
            echo "Invalid APP_ENVIROMENT";
            exit;
        }
        $headers = array(
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: Bearer $token"
        );
        $data = array(
            "url" => "https://12eb-41-81-142-80.ngrok-free.app/pesapal/pin.php",
            "ipn_notification_type" => "POST"
        );
        $ch = curl_init($ipnRegistrationUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($response);
        return $data;
        // $ipn_id = $data->ipn_id;
        // $ipn_url = $data->url;
    }

    public function pesapalIPN()
    {
        return "PESAPAL IPN";
    }

    public function accessToken()
    {
        $pg = DB::table('external_services')->where('name', 'Pesapal')->where('type', 'payment')->first();
        $lines = explode(';', $pg->details);
        $keys = explode(',', $lines[0]);
        $vals = explode(',', $lines[1]);

        $results = array_combine($keys, $vals);

        $APP_ENVIROMENT = $results['Mode'];
        // return $APP_ENVIROMENT;
        if ($APP_ENVIROMENT == 'sandbox') {
            $apiUrl = "https://cybqa.pesapal.com/pesapalv3/api/Auth/RequestToken"; // Sandbox URL
            $consumerKey = $results['Consumer Key']; //env('PESAPAL_CONSUMER_KEY');
            $consumerSecret = $results['Consumer Secret']; //env('PESAPAL_CONSUMER_SECRET');
        } elseif ($APP_ENVIROMENT == 'live') {
            $apiUrl = "https://pay.pesapal.com/v3/api/Auth/RequestToken"; // Live URL
            $consumerKey = "";
            $consumerSecret = "";
        } else {
            echo "Invalid APP_ENVIROMENT";
            exit;
        }
        $headers = [
            "Accept: application/json",
            "Content-Type: application/json"
        ];
        $data = [
            "consumer_key" => $consumerKey,
            "consumer_secret" => $consumerSecret
        ];
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($response);

        $token = $data->token;

        return $token;
    }
    public function submitOrderRequest($data, $amount)
    {
        $pg = DB::table('external_services')->where('name', 'Pesapal')->where('type', 'payment')->first();
        $lines = explode(';', $pg->details);
        $keys = explode(',', $lines[0]);
        $vals = explode(',', $lines[1]);

        $results = array_combine($keys, $vals);

        $company = gen_setting()->company_name;

        $APP_ENVIROMENT = $results['Mode'];;
        $token = $this->accessToken();
        $ipnData = $this->registerIPN();

        $merchantreference = rand(1, 1000000000000000000);
        $phone = $data->phone_number; //0768168060
        $amount = $amount;
        $callbackurl = "salepro.test/ipn";
        $branch = $company;
        $first_name = $data->name;
        //$middle_name = "Coders";
        $last_name = $data->name;
        $email_address = $data->email ? $data->email : "hello@lion-coders.com";
        if ($APP_ENVIROMENT == 'sandbox') {
            $submitOrderUrl = "https://cybqa.pesapal.com/pesapalv3/api/Transactions/SubmitOrderRequest";
        } elseif ($APP_ENVIROMENT == 'live') {
            $submitOrderUrl = "https://pay.pesapal.com/v3/api/Transactions/SubmitOrderRequest";
        } else {
            echo "Invalid APP_ENVIROMENT";
            exit;
        }
        $headers = array(
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: Bearer $token"
        );

        // Request payload
        $data = array(
            "id" => "$merchantreference",
            "currency" => "KES",
            "amount" => $amount,
            "description" => "Payment description goes here",
            "callback_url" => "$ipnData->url",
            "notification_id" => "$ipnData->ipn_id",
            "branch" => "$branch",
            "billing_address" => array(
                "email_address" => "$email_address",
                "phone_number" => "$phone",
                "country_code" => "KE",
                "first_name" => "$first_name",
                //"middle_name" => "$middle_name",
                "last_name" => "$last_name",
                "line_1" => "Pesapal Limited",
                "line_2" => "",
                "city" => "",
                "state" => "",
                "postal_code" => "",
                "zip_code" => ""
            )
        );
        $ch = curl_init($submitOrderUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response);
        $redirectUrl = $data->redirect_url;
        return $redirectUrl;
        // echo "<script>window.location.href='$redirectUrl'</script>";
    }

    public function getCredentials($pgName)
    {
        $pg = DB::table('external_services')->where('name', $pgName)->where('type', 'payment')->first();
        $lines = explode(';', $pg->details);
        $keys = explode(',', $lines[0]);
        $vals = explode(',', $lines[1]);

        $results = array_combine($keys, $vals);

        return $results;
    }

    public function moneipoint($saleData)
    {
        $merchantreference = $saleData['reference_no'];
        $amount = $saleData['amount'];
        $results = $this->getCredentials('Moneipoint');
        //Generate access token start
        $apiUrl = "https://channel.moniepoint.com/v1/auth";

        $headers = [
            "Accept: application/json",
            "Content-Type: application/json"
        ];

        $data = [
            "clientId" => $results['client_id'],
            "clientSecret" => $results['client_secret']
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($response);
        // return $data->token;
        $token = $data->accessToken;
        //Generate access token end

        // Start Transaction
        $headers = array(
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: Bearer $token"
        );

        $submitOrderUrl = "https://channel.moniepoint.com/v1/transactions";

        $data = array(
            "terminalSerial" => $results['terminal_serial'],
            "amount" => $amount,
            "merchantReference" => $merchantreference,
            "transactionType" => "PURCHASE",
            "paymentMethod" => "CARD_PURCHASE"

        );

        $ch = curl_init($submitOrderUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response);
        return $data;
    }

    public function showDeletedSales()
    {
        $lims_deleted_data = Sale::onlyTrashed()
            ->with(['user', 'customer', 'warehouse', 'deleter'])
            ->get();

        return view('backend.sale.deleted-data', compact('lims_deleted_data'));
    }

    public function forceDeleteSelected(Request $request)
    {
        $ids = (array) ($request->ids ?? []);

        $lockedSaleIds = $this->phase2LockedSaleIds($ids);
        if ($lockedSaleIds) {
            return back()->with(
                'not_permitted',
                'Phase 2 fiscal invoices exist for the selected sale(s): '.implode(', ', $lockedSaleIds).'. These fiscal records cannot be permanently deleted.'
            );
        }

        if (!empty($ids)) {
            Sale::withTrashed()->whereIn('id', $ids)->forceDelete();
            return back()->with('not_permitted', 'Selected sales permanently deleted!');
        }

        return back()->with('not_permitted', 'No sales selected!');
    }

    public function search(Request $request)
    {
        $warehouse_id = $request->warehouse_id;

        // Trim first, THEN check length
        $search = trim($request->search ?? '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $today = Carbon::now()->toDateString();

        // -------------------------------------------------------
        // Handle embedded barcode (13 digits -> truncate to first 7)
        // -------------------------------------------------------
        $product_embed_code = null;
        $embedded_barcode = null;
        $embedded_qty = null;

        if (preg_match('/^\d{13}$/', $search)) {
            $product_embed_code = substr($search, 0, 7);
            $embedded_barcode = $search;
            $embedded_qty = ((float) substr($search, 7, 5)) / 1000;
            $embeddedProduct = Product::where('is_embeded', true)
                ->where(function ($q) use ($product_embed_code) {
                    $q->where('code', $product_embed_code)
                        ->orWhere('name', 'like', '%' . $product_embed_code . '%');
                })
                ->first();

            if ($embeddedProduct) {
                $search = $product_embed_code;
            }
        }

        // -------------------------------------------------------
        // Shared SELECT columns for product + warehouse joins
        // -------------------------------------------------------
        $productColumns = [
            'products.id',
            'products.name',
            'products.code',
            'products.type',
            'products.is_imei',
            'products.is_diffPrice',
            'products.is_variant',
            'products.is_embeded',
            'products.is_batch',
            'products.product_list',
            'products.qty_list',
            'products.combo_unit_id',
        ];

        $variantPriceCase = DB::raw("CASE WHEN products.is_diffPrice = 1
                                         THEN product_warehouse.price
                                         ELSE products.price + product_variants.additional_price
                                     END as price");

        $standardPriceCase = DB::raw("CASE WHEN products.is_diffPrice = 1
                                           THEN product_warehouse.price
                                           ELSE products.price
                                       END as price");

        $without_stock = gen_setting()->without_stock ?? 'no';
        $stockFilter = function($q) use ($without_stock) {
            if ($without_stock == 'no') {
                $q->where(function($q2) {
                    $q2->where('product_warehouse.qty', '>', 0)
                       ->orWhereIn('products.type', ['service', 'digital', 'combo']);
                });
            }
        };

        // -------------------------------------------------------
        // Step 1: Exact matches (fast path)
        // -------------------------------------------------------
        $baseProducts = collect();

        // 1a. Exact variant item_code match
        $exactVariant = ProductVariant::join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('product_warehouse', function ($join) use ($warehouse_id) {
                $join->on('product_variants.variant_id', '=', 'product_warehouse.variant_id')
                    ->on('product_variants.product_id', '=', 'product_warehouse.product_id')
                    ->where('product_warehouse.warehouse_id', $warehouse_id);
            })
            ->where('product_variants.item_code', $search)
            ->where('products.is_active', 1)
            ->where($stockFilter)
            ->select(array_merge($productColumns, [
                'product_variants.item_code as code',
                $variantPriceCase,
                'product_warehouse.qty',
                'product_warehouse.imei_number',
                'product_warehouse.product_batch_id',
                'product_warehouse.variant_id as matched_variant_id',
            ]))
            ->first();

        if ($exactVariant) {
            $baseProducts->push($exactVariant);
        } else {
            // 1b. Exact IMEI match for VARIANT products
            $variantImeiMatch = ProductVariant::join('products', 'product_variants.product_id', '=', 'products.id')
                ->join('product_warehouse', function ($join) use ($warehouse_id) {
                    $join->on('product_variants.variant_id', '=', 'product_warehouse.variant_id')
                        ->on('product_variants.product_id', '=', 'product_warehouse.product_id')
                        ->where('product_warehouse.warehouse_id', $warehouse_id);
                })
                ->where('products.is_active', 1)
                ->where('product_warehouse.imei_number', 'like', '%' . $search . '%')
                ->where($stockFilter)
                ->select(array_merge($productColumns, [
                    'product_variants.item_code as code',
                    $variantPriceCase,
                    DB::raw('1 as qty'),
                    'product_warehouse.imei_number',
                    'product_warehouse.product_batch_id',
                    'product_warehouse.variant_id as matched_variant_id',
                ]))
                ->first();

            if ($variantImeiMatch) {
                $variantImeiMatch->imei_number = $search; // inject precise IMEI token
                $baseProducts->push($variantImeiMatch);
            }
        }

        // -------------------------------------------------------
        // Step 2: Broad search - ONLY if no exact match found
        // -------------------------------------------------------
        if ($baseProducts->isEmpty()) {

            // 2a & 2b. Code and Name match
            $byCodeAndName = Product::leftJoin('product_warehouse', function ($j) use ($warehouse_id) {
                $j->on('products.id', '=', 'product_warehouse.product_id')
                    ->where('product_warehouse.warehouse_id', $warehouse_id);
            })
                ->where('products.is_active', 1)
                ->where(function($q) use ($search) {
                    $q->where('products.code', 'like', '%' . $search . '%')
                      ->orWhere('products.name', 'like', '%' . $search . '%');
                })
                ->where($stockFilter)
                ->select(array_merge($productColumns, [
                    $standardPriceCase,
                    'product_warehouse.qty',
                    'product_warehouse.imei_number',
                    'product_warehouse.product_batch_id',
                ]))
                ->limit(20)
                ->get();

            // 2c. Variant item_code fuzzy match
            $byVariant = collect();
            if ($byCodeAndName->isEmpty()) {
                $byVariant = ProductVariant::join('products', 'product_variants.product_id', '=', 'products.id')
                    ->join('product_warehouse', function ($join) use ($warehouse_id) {
                        $join->on('product_variants.variant_id', '=', 'product_warehouse.variant_id')
                            ->on('product_variants.product_id', '=', 'product_warehouse.product_id')
                            ->where('product_warehouse.warehouse_id', $warehouse_id);
                    })
                    ->where('products.is_active', 1)
                    ->where('product_variants.item_code', 'like', '%' . $search . '%')
                    ->where($stockFilter)
                    ->select(array_merge($productColumns, [
                        'product_variants.item_code as code',
                        $variantPriceCase,
                        'product_warehouse.qty',
                        'product_warehouse.imei_number',
                        'product_warehouse.product_batch_id',
                    ]))
                    ->limit(20)
                    ->get();
            }

            // 2d. IMEI fuzzy match
            $byIMEI = collect();
            if ($byCodeAndName->isEmpty() && $byVariant->isEmpty()) {
                $imeiMatch = Product_Warehouse::where('warehouse_id', $warehouse_id)
                    ->where('imei_number', 'like', '%' . $search . '%')
                    ->when($without_stock == 'no', function($q) {
                        $q->where('qty', '>', 0);
                    })
                    ->select('product_id', 'qty', 'imei_number', 'price', 'product_batch_id', 'variant_id')
                    ->first();

                if ($imeiMatch) {
                    $imeiProduct = Product::leftJoin('product_warehouse', function ($j) use ($warehouse_id) {
                        $j->on('products.id', '=', 'product_warehouse.product_id')
                            ->where('product_warehouse.warehouse_id', $warehouse_id);
                    })
                        ->where('products.id', $imeiMatch->product_id)
                        ->select(array_merge($productColumns, [
                            $standardPriceCase,
                            'product_warehouse.qty',
                            'product_warehouse.imei_number',
                            'product_warehouse.product_batch_id',
                        ]))
                        ->first();

                    if ($imeiProduct) {
                        $imeiProduct->imei_number = $search;
                        $imeiProduct->matched_variant_id = $imeiMatch->variant_id;
                        $byIMEI = collect([$imeiProduct]);
                    }
                }
            }

            // 2e. Batch Number fuzzy match
            $byBatch = collect();
            if ($byCodeAndName->isEmpty() && $byVariant->isEmpty() && $byIMEI->isEmpty()) {
                $batchMatch = ProductBatch::where('batch_no', 'like', '%' . $search . '%')
                    ->select('product_id', 'id as batch_id', 'batch_no')
                    ->first();

                if ($batchMatch) {
                    $batchProduct = Product::leftJoin('product_warehouse', function ($j) use ($warehouse_id) {
                        $j->on('products.id', '=', 'product_warehouse.product_id')
                            ->where('product_warehouse.warehouse_id', $warehouse_id);
                    })
                        ->where('products.id', $batchMatch->product_id)
                        ->where('product_warehouse.product_batch_id', $batchMatch->batch_id)
                        ->where($stockFilter)
                        ->select(array_merge($productColumns, [
                            $standardPriceCase,
                            'product_warehouse.qty',
                            'product_warehouse.imei_number',
                            'product_warehouse.product_batch_id',
                        ]))
                        ->first();

                    if ($batchProduct) {
                        $batchProduct->matched_batch_id = $batchMatch->batch_id;
                        $byBatch = collect([$batchProduct]);
                    }
                }
            }

            // Merge broad results
            $baseProducts = $byCodeAndName
                ->merge($byVariant)
                ->merge($byIMEI)
                ->merge($byBatch)
                ->unique('id')
                ->take(20)
                ->values();
        }

        // -------------------------------------------------------
        // Step 4: Combo products
        // -------------------------------------------------------
        $combos = Product::where('is_active', 1)
            ->where('type', 'combo')
            ->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%');
            })
            ->select(array_merge($productColumns, ['products.price']))
            ->orderBy('name')
            ->limit(20)
            ->get();

        // -------------------------------------------------------
        // Step 5: Lazy-load supporting data scoped to found products
        // -------------------------------------------------------

        // Combo stock lookup - only the component IDs actually needed
        $comboComponentIds = [];
        foreach ($combos as $combo) {
            $ids = array_filter(explode(',', $combo->product_list ?? ''));
            foreach ($ids as $cid) {
                $comboComponentIds[] = (int) $cid;
            }
        }
        $comboComponentIds = array_unique($comboComponentIds);

        $warehouseStocks = [];
        if (!empty($comboComponentIds)) {
            $warehouseStocks = DB::table('product_warehouse')
                ->where('warehouse_id', $warehouse_id)
                ->whereIn('product_id', $comboComponentIds)
                ->select('product_id', 'qty')
                ->get()
                ->groupBy('product_id');
        }

        // Batch data - scoped to base products that are batch-tracked
        $productBatches = collect();
        if ($baseProducts->isNotEmpty()) {
            $batchProductIds = $baseProducts->where('is_batch', 1)->pluck('id')->unique()->values()->all();
            if (!empty($batchProductIds)) {
                $productBatches = ProductBatch::whereIn('product_id', $batchProductIds)
                    ->whereDate('expired_date', '>=', $today)
                    ->orderBy('expired_date', 'asc')
                    ->get()
                    ->groupBy('product_id');
            }
        }

        // Variant data - scoped to variant products in base results
        $variants = collect();
        if ($baseProducts->isNotEmpty()) {
            $variantProductIds = $baseProducts->where('is_variant', 1)->pluck('id')->unique()->values()->all();
            if (!empty($variantProductIds)) {
                $variants = ProductVariant::whereIn('product_variants.product_id', $variantProductIds)
                    ->join('product_warehouse', function ($join) use ($warehouse_id) {
                        $join->on('product_variants.variant_id', '=', 'product_warehouse.variant_id')
                            ->on('product_variants.product_id', '=', 'product_warehouse.product_id')
                            ->where('product_warehouse.warehouse_id', $warehouse_id);
                    })
                    ->select(
                        'product_variants.product_id',
                        'product_variants.item_code',
                        'product_variants.variant_id',
                        'product_variants.additional_price',
                        'product_warehouse.qty',
                        'product_warehouse.imei_number',
                        'product_warehouse.product_batch_id'
                    )
                    ->get()
                    ->groupBy('product_id');
            }
        }

        // Pre-load child product base unit IDs and all units
        $childProducts = [];
        if (!empty($comboComponentIds)) {
            $childProducts = Product::whereIn('id', $comboComponentIds)
                ->select('id', 'unit_id')
                ->get()
                ->keyBy('id');
        }
        $allUnits = Unit::all()->keyBy('id');

        // -------------------------------------------------------
        // Step 6: Resolve combo available quantities
        // -------------------------------------------------------
        foreach ($combos as $combo) {
            $componentIds = array_values(array_filter(explode(',', $combo->product_list ?? '')));
            $requiredQtys = array_values(array_filter(explode(',', $combo->qty_list ?? '')));
            $comboUnitIds = $combo->combo_unit_id ? array_values(array_filter(explode(',', $combo->combo_unit_id))) : [];
            $minAvailable = PHP_INT_MAX;

            foreach ($componentIds as $i => $compId) {
                $required  = isset($requiredQtys[$i]) ? (float) $requiredQtys[$i] : 1.0;
                $stock     = isset($warehouseStocks[$compId]) ? $warehouseStocks[$compId]->first()->qty : 0;

                $child = $childProducts[$compId] ?? null;
                if ($child) {
                    $comboUnitId = $comboUnitIds[$i] ?? null;
                    if ($comboUnitId && $comboUnitId != $child->unit_id) {
                        $unit = $allUnits[$comboUnitId] ?? null;
                        if ($unit) {
                            if ($unit->operator == '*') {
                                $required = $required * $unit->operation_value;
                            } elseif ($unit->operator == '/') {
                                $required = $required / $unit->operation_value;
                            }
                        }
                    }
                }

                if ($stock <= 0) {
                    $minAvailable = 0;
                    break;
                }

                $minAvailable = min($minAvailable, (int) floor($stock / max(0.0001, $required)));
            }

            $combo->qty = ($minAvailable === PHP_INT_MAX) ? 0 : $minAvailable;
            $baseProducts->push($combo);
        }

        // -------------------------------------------------------
        // Step 7: Build unified output array
        // -------------------------------------------------------
        $products = [];

        foreach ($baseProducts as $product) {
            $batch_no     = null;
            $expired_date = null;

            // Resolve batch info for batch-tracked products
            if ($product->is_batch == 1) {
                $batches = $productBatches[$product->id] ?? collect();

                if ($batches->isNotEmpty()) {
                    if (isset($product->matched_batch_id)) {
                        $batch = $batches->firstWhere('id', $product->matched_batch_id);
                    } else {
                        $batch = $batches->first(); // closest expiry >= today
                    }

                    if ($batch) {
                        $batch_no     = $batch->batch_no;
                        $expired_date = Carbon::parse($batch->expired_date)->format(config('date_format'));
                        $product->product_batch_id = $batch->id;
                    } else {
                        continue;
                    }
                } else {
                    // All batches expired - skip this product
                    continue;
                }
            }

            $imei_numbers = $product->imei_number ? explode(',', $product->imei_number) : [null];

            // Short-circuit: exact IMEI token match found in loop
            if ($search && in_array($search, $imei_numbers)) {
                if ($product->is_variant == 1) {
                    $vars = $variants[$product->id] ?? collect();

                    if (isset($product->matched_variant_id)) {
                        $vars = $vars->where('variant_id', $product->matched_variant_id);
                    }

                    foreach ($vars as $v) {
                        return response()->json([[
                            'id'               => $product->id,
                            'code'             => $v->item_code,
                            'name'             => $product->name,
                            'qty'              => $v->qty,
                            'price'            => $product->price + $v->additional_price,
                            'is_imei'          => $product->is_imei,
                            'is_embeded'       => $product->is_embeded,
                            'batch_no'         => $batch_no,
                            'product_batch_id' => $product->product_batch_id,
                            'expired_date'     => $expired_date,
                            'imei_number'      => $search,
                        ]]);
                    }
                } else {
                    return response()->json([[
                        'id'               => $product->id,
                        'code'             => $product->code,
                        'name'             => $product->name,
                        'qty'              => $product->qty,
                        'price'            => $product->price,
                        'is_imei'          => $product->is_imei,
                        'is_embeded'       => $product->is_embeded,
                        'batch_no'         => $batch_no,
                        'product_batch_id' => $product->product_batch_id,
                        'expired_date'     => $expired_date,
                        'imei_number'      => $search,
                    ]]);
                }
            }

            // Variant + IMEI product: expand one row per IMEI per variant
            if ($product->is_variant == 1 && $product->is_imei == 1) {
                $vars = $variants[$product->id] ?? collect();
                foreach ($vars as $v) {
                    $imeiList = array_filter(explode(',', $v->imei_number ?? ''));
                    foreach ($imeiList as $imei) {
                        $products[] = [
                            'id'               => $product->id,
                            'code'             => $v->item_code,
                            'name'             => $product->name,
                            'qty'              => 1,
                            'price'            => $product->price + $v->additional_price,
                            'is_imei'          => 1,
                            'is_embeded'       => $product->is_embeded,
                            'batch_no'         => $batch_no,
                            'product_batch_id' => $v->product_batch_id,
                            'expired_date'     => $expired_date,
                            'imei_number'      => trim($imei),
                        ];
                    }
                }

                // Variant product (no IMEI): expand one row per variant
            } elseif ($product->is_variant == 1) {
                $vars = $variants[$product->id] ?? collect();
                foreach ($vars as $v) {
                    $products[] = [
                        'id'               => $product->id,
                        'code'             => $v->item_code,
                        'name'             => $product->name,
                        'qty'              => $v->qty,
                        'price'            => $product->price + $v->additional_price,
                        'is_imei'          => $product->is_imei,
                        'is_embeded'       => $product->is_embeded,
                        'batch_no'         => $batch_no,
                        'product_batch_id' => $product->product_batch_id,
                        'expired_date'     => $expired_date,
                        'imei_number'      => null,
                    ];
                }
                // Deduplicate by variant code
                $products = collect($products)->unique('code')->values()->all();

                // Non-variant product
            } else {
                // Embedded barcode: restore the original 13-digit code; skip if no embed context
                if ($product->is_embeded == 1) {
                    if ($embedded_barcode) {
                        $product->code = $embedded_barcode;
                    } else {
                        // Embedded product surfaced without an embedded scan - skip
                        continue;
                    }
                }

                if ($product->is_imei == 1 && !empty($product->imei_number)) {
                    $imeiList = array_filter(explode(',', $product->imei_number));
                    foreach ($imeiList as $imei) {
                        $products[] = [
                            'type'             => $product->type,
                            'id'               => $product->id,
                            'code'             => $product->code,
                            'name'             => $product->name,
                            'qty'              => 1,
                            'price'            => $product->price,
                            'is_imei'          => $product->is_imei,
                            'is_embeded'       => $product->is_embeded,
                            'batch_no'         => $batch_no,
                            'product_batch_id' => $product->product_batch_id,
                            'expired_date'     => $expired_date,
                            'imei_number'      => trim($imei),
                        ];
                    }
                } else {
                    $products[] = [
                        'type'             => $product->type,
                        'id'               => $product->id,
                        'code'             => $product->code,
                        'name'             => $product->name,
                        'qty'              => $embedded_qty ?? ($product->qty ?? 0),
                        'warehouse_qty'    => $product->qty ?? 0,
                        'price'            => $product->price,
                        'is_imei'          => $product->is_imei,
                        'is_embeded'       => $product->is_embeded,
                        'batch_no'         => $batch_no,
                        'product_batch_id' => $product->product_batch_id,
                        'expired_date'     => $expired_date,
                        'imei_number'      => $product->imei_number,
                    ];
                }
            }
        }

        return response()->json($products);
    }

    public function customerSales($customer_id)
    {
        $sales = Sale::with('customer')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('sale_type', '!=', 'opening balance')
                    ->orWhereNull('sale_type');
            })
            ->where('customer_id', $customer_id)
            ->latest()
            ->get()
            ->map(function ($sale) {
                $saleStatus = match ($sale->sale_status) {
                    1 => __('db.Completed'),
                    2 => __('db.Pending'),
                    3 => __('db.Draft'),
                    4 => __('db.Returned'),
                    5 => __('db.Processing'),
                    6 => __('db.cooked'),
                    7 => __('db.served'),
                    default => 'N/A'
                };

                $returnedAmount = Returns::where('sale_id', $sale->id)->sum('grand_total');
                $netTotal = max(0, $sale->grand_total - $returnedAmount);
                $paymentStatus = $sale->paid_amount >= $netTotal ? 'Paid' : ($sale->paid_amount > 0 ? 'Partial' : 'Due');

                $paymentDue = number_format(max(0, $netTotal - $sale->paid_amount), config('decimal'));

                $warehouseName = $sale->warehouse_id ? optional(Warehouse::find($sale->warehouse_id))->name : '-';
                $customer = $sale->customer;

                return [
                    'id' => $sale->id,
                    'date' => $sale->created_at->format('Y-m-d'),
                    'reference' => $sale->reference_no,
                    'warehouse' => $warehouseName,
                    'sale_status' => $saleStatus,
                    'payment_status' => $paymentStatus,
                    'grand_total' => number_format($sale->grand_total, 2),
                    'paid_amount' => number_format($sale->paid_amount, 2),
                    'payment_due' => $paymentDue,
                    'note' => $sale->note,
                    'currency' => $sale->currency ?? null,
                    'document' => $sale->document ?? null,
                    'customer_name' => $customer->name ?? '-',
                    'customer_company' => $customer->company_name ?? '-',
                    'customer_address' => $customer->address ?? '-',
                ];
            });

        return response()->json(['data' => $sales]);
    }

    public function setPriceType(Request $request)
    {
        $type = $request->price_type;

        if (!in_array($type, ['retail', 'wholesale'], true)) {
            $type = 'retail';
        }

        session(['price_type' => $type]);

        return response()->json([
            'success' => true,
            'price_type' => $type
        ]);
    }




    protected function dispatchSaleNotifications($sale): void
    {
        // A draft is not an operationally created sale yet.
        if (!$sale || (int) $sale->sale_status === 3) {
            return;
        }

        try {
            $saleProducts = Product_Sale::where('sale_id', $sale->id)
                ->get(['product_id', 'qty']);

            if ($saleProducts->isEmpty()) {
                \Log::warning('Sale notification skipped because no persisted sale lines were found.', [
                    'sale_id' => $sale->id,
                    'reference_no' => $sale->reference_no,
                ]);
                return;
            }

            $productIds = $saleProducts->pluck('product_id')->filter()->unique()->values();
            $products = Product::whereIn('id', $productIds)
                ->get(['id', 'name', 'qty', 'alert_quantity', 'type'])
                ->keyBy('id');

            $warehouseQty = Product_Warehouse::where('warehouse_id', $sale->warehouse_id)
                ->whereIn('product_id', $productIds)
                ->selectRaw('product_id, SUM(qty) as aggregate_qty')
                ->groupBy('product_id')
                ->pluck('aggregate_qty', 'product_id');

            $compiledProducts = [];
            $lowStockSummary = [];
            $lowStockProductIds = [];

            foreach ($saleProducts as $saleProduct) {
                $product = $products->get($saleProduct->product_id);
                if (!$product) {
                    continue;
                }

                $compiledProducts[] = $product->name . ' (Qty: ' . $saleProduct->qty . ')';
            }

            foreach ($productIds as $productId) {
                $product = $products->get($productId);
                if (!$product || in_array($product->type, ['digital', 'service'], true)) {
                    continue;
                }

                if ($product->alert_quantity === null || $product->alert_quantity === '') {
                    continue;
                }

                $currentQty = $warehouseQty->has($productId)
                    ? (float) $warehouseQty->get($productId)
                    : (float) $product->qty;

                if ($currentQty <= (float) $product->alert_quantity) {
                    $lowStockSummary[] = $product->name . ' (Current: ' . $currentQty . ')';
                    $lowStockProductIds[] = (int) $productId;
                }
            }

            $notificationService = app(\App\Services\NotificationService::class);
            $admins = User::where('role_id', '<=', 2)->get();
            $customer = $sale->customer ?? Customer::find($sale->customer_id);

            if (!$customer) {
                throw new \RuntimeException("Customer data not found for Sale ID: {$sale->id}");
            }

            $notificationService->dispatch('sale_created', [
                'customer_name'  => $customer->name,
                'customer_wa'    => $customer->wa_number ?? $customer->phone,
                'customer_phone' => $customer->phone,
                'customer_email' => $customer->email,
                'reference'      => $sale->reference_no,
                'amount'         => number_format((float) $sale->grand_total, 2),
                'product'        => implode(', ', $compiledProducts),
                'qty'            => (float) $saleProducts->sum('qty'),
                'admin_users'    => $admins,
            ]);

            if (!empty($lowStockSummary)) {
                // Resolve a useful supplier from the latest purchase history for the
                // first low-stock product. SalePro does not require supplier_id on Product.
                $supplier = null;
                $firstLowProductId = $lowStockProductIds[0] ?? null;

                if ($firstLowProductId) {
                    $lastProductPurchase = ProductPurchase::where('product_id', $firstLowProductId)
                        ->latest('id')
                        ->first();

                    if ($lastProductPurchase) {
                        $purchaseSupplierId = Purchase::whereKey($lastProductPurchase->purchase_id)
                            ->value('supplier_id');

                        if ($purchaseSupplierId) {
                            $supplier = \App\Models\Supplier::find($purchaseSupplierId);
                        }
                    }
                }

                $notificationService->dispatch('low_stock', [
                    'product'        => implode(', ', $lowStockSummary),
                    'admin_users'    => $admins,
                    'supplier_email' => $supplier->email ?? null,
                    'supplier_phone' => $supplier->phone_number ?? $supplier->phone ?? null,
                    'supplier_wa'    => $supplier->phone_number ?? $supplier->phone ?? null,
                ]);
            }
        } catch (\Throwable $e) {
            // Notifications are non-transactional side effects. Never turn a successfully
            // committed sale into an HTTP failure because a channel/provider failed.
            \Log::error('Notification dispatcher failed after sale creation.', [
                'sale_id' => $sale->id ?? null,
                'reference_no' => $sale->reference_no ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function dispatchPaymentNotifications($sale, $paymentAmount)
    {
        try {
            $saleProducts = \App\Models\Product_Sale::where('sale_id', $sale->id)->get();
            if ($saleProducts->isEmpty()) {
                return;
            }

            $paymentProductIds = $saleProducts->pluck('product_id')->toArray();
            $paymentQuantities = $saleProducts->pluck('qty')->toArray();

            $notificationService = app(\App\Services\NotificationService::class);
            $admins = \App\Models\User::where('role_id', '<=', 2)->get();
            $customer = $sale->customer ?? \App\Models\Customer::find($sale->customer_id);

            if ($customer) {
                $compiledProducts = [];
                foreach ($paymentProductIds as $index => $id) {
                    $product = \App\Models\Product::find($id);
                    if ($product) {
                        $compiledProducts[] = $product->name . " (Qty: " . ($paymentQuantities[$index] ?? 0) . ")";
                    }
                }

                $notificationService->dispatch('payment_received', [
                    'customer_name'  => $customer->name,
                    'customer_wa'    => $customer->wa_number ?? $customer->phone,
                    'customer_phone' => $customer->phone,
                    'customer_email' => $customer->email,
                    'reference'      => $sale->reference_no,
                    'amount'         => number_format($paymentAmount, 2),
                    'product'        => implode(', ', $compiledProducts),
                    'admin_users'    => $admins,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error("Payment notification engine failed: " . $e->getMessage());
        }
    }

}
