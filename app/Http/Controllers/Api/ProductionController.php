<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Manufacturing\Entities\Production;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Account;
use Illuminate\Support\Facades\Validator;
use Auth;
use DB;

class ProductionController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            if ($request->input('warehouse_id'))
                $warehouse_id = $request->input('warehouse_id');
            else
                $warehouse_id = 0;

            if ($request->input('status'))
                $status = $request->input('status');
            else
                $status = 0;

            if ($request->input('starting_date')) {
                $starting_date = $request->input('starting_date');
                $ending_date = $request->input('ending_date');
            } else {
                $starting_date = date("Y-m-d", strtotime(date('Y-m-d', strtotime('-1 year', strtotime(date('Y-m-d'))))));
                $ending_date = date("Y-m-d");
            }

            $query = Production::with('user', 'warehouse')
                ->whereDate('created_at', '>=', $starting_date)
                ->whereDate('created_at', '<=', $ending_date);

            if ($user->role_id > 2 && config('staff_access') == 'own') {
                $query->where('user_id', $user->id);
            } elseif ($user->role_id > 2 && config('staff_access') == 'warehouse') {
                $query->where('warehouse_id', $user->warehouse_id);
            }

            if ($warehouse_id)
                $query->where('warehouse_id', $warehouse_id);
            if ($status)
                $query->where('status', $status);

            $productions = $query->orderBy('id', 'desc')->get();

            $rows = $productions->map(function ($production) {
                $status = 'N/A';
                if ($production->status == 1) {
                    $status = '<div class="badge badge-success">' . trans('file.Completed') . '</div>';
                }

                return [
                    'id' => $production->id,
                    'date' => date(config('date_format'), strtotime($production->created_at)),
                    'reference_no' => $production->reference_no,
                    'product' => $production->product->name ?? 'N/A',
                    'warehouse' => $production->warehouse->name ?? 'N/A',
                    'quantity' => $production->total_qty,
                    'grand_total' => number_format($production->grand_total, config('decimal')),
                    'status' => $status,
                ];
            });

            return $this->withDashBackground([
                'title' => 'Production List',
                'add_url' => '/productions/create',
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Reference', 'field' => 'reference_no', 'type' => 'text'],
                    ['label' => 'Product', 'field' => 'product', 'type' => 'text'],
                    ['label' => 'Warehouse', 'field' => 'warehouse', 'type' => 'text'],
                    ['label' => 'Quantity', 'field' => 'quantity', 'type' => 'text'],
                    ['label' => 'Grand Total', 'field' => 'grand_total', 'type' => 'text'],
                    ['label' => 'Status', 'field' => 'status', 'type' => 'html'],
                    ['label' => 'Action', 'type' => 'action', 'action' => [
                        'type' => 'delete',
                        'api_url' => '/productions/{id}',
                    ]],
                ],
                'rows' => $rows,
            ], 'app');
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function create()
    {
        $warehouses = Warehouse::where('is_active', true)->get()->map(function ($warehouse) {
            return ['label' => $warehouse->name, 'value' => $warehouse->id];
        });

        $products = Product::where('is_recipe', 1)->where('is_active', true)->get()->map(function ($product) {
            return ['label' => $product->name . ' (' . $product->code . ')', 'value' => $product->id];
        });

        $accounts = Account::where('is_active', true)->get()->map(function ($account) {
            return ['label' => $account->name . ' [' . $account->account_no . ']', 'value' => $account->id];
        });

        $formSchema = [
            'title' => 'Add Production',
            'submit_url' => '/productions',
            'method' => 'POST',
            'fields' => [
                [
                    'type' => 'select',
                    'name' => 'warehouse_id',
                    'label' => 'Warehouse',
                    'options' => $warehouses,
                ],
                [
                    'type' => 'datepicker',
                    'name' => 'created_at',
                    'label' => 'Date',
                    'format_specifier' => 'yyyy-MM-dd HH:mm:ss',
                ],
                [
                    'type' => 'select',
                    'name' => 'product_id',
                    'label' => 'Product',
                    'options' => $products,
                    'info' => 'Select a product that has a recipe defined.',
                ],
                [
                    'type' => 'text',
                    'name' => 'total_qty',
                    'label' => 'Quantity',
                    'keyboard_type' => 'number',
                ],
                [
                    'type' => 'file',
                    'name' => 'document',
                    'label' => 'Attach Document',
                    'multiple' => false,
                ],
                [
                    'type' => 'table_generator',
                    'name' => 'products',
                    'label' => 'Ingredients',
                    'search_url' => '/products/lims_product_search',
                    'search_placeholder' => 'Search ingredients by name or code',
                    'columns' => [
                        [
                            'name' => 'name',
                            'label' => 'Name',
                            'type' => 'text',
                            'editable' => false,
                            'width' => 200,
                        ],
                        [
                            'name' => 'code',
                            'label' => 'Code',
                            'type' => 'text',
                            'editable' => false,
                            'width' => 100,
                        ],
                        [
                            'name' => 'qty',
                            'label' => 'Quantity',
                            'type' => 'number',
                            'editable' => true,
                            'width' => 100,
                            'default' => 1,
                        ],
                        [
                            'name' => 'net_unit_price',
                            'label' => 'Unit Cost',
                            'type' => 'number',
                            'editable' => true,
                            'width' => 120,
                            'decimal_places' => 2,
                        ],
                        [
                            'name' => 'subtotal',
                            'label' => 'Subtotal',
                            'type' => 'formula',
                            'formula' => 'qty * net_unit_price',
                            'width' => 120,
                            'decimal_places' => 2,
                        ],
                    ],
                    'totals' => [
                        [
                            'label' => 'Total Cost',
                            'formula' => 'SUM(subtotal)',
                            'position' => 'right',
                            'prefix' => config('currency'),
                            'decimal_places' => 2,
                        ],
                    ],
                ],
                [
                    'type' => 'textarea',
                    'name' => 'note',
                    'label' => 'Note',
                ],
            ],
        ];

        return response()->json($this->withDashBackground($formSchema, 'app'));
    }

    public function store(Request $request)
    {
        // Replicate logic from Web Controller
        $data = $request->except('document');
        $data['user_id'] = Auth::id();
        $data['reference_no'] = 'production-' . date("Ymd") . '-' . date("his");

        // Validate
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required',
            'product_id' => 'required',
            'products' => 'required', // table generator data
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::findOrFail($request->product_id);

        try {
            DB::beginTransaction();

            if ($request->hasFile('document')) {
                $document = $request->file('document');
                $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
                $documentName = date("Ymdhis");

                $documentName = $documentName . '.' . $ext;
                $document->move('public/documents/production', $documentName);

                $data['document'] = $documentName;
            }

            if (isset($data['created_at']))
                $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
            else
                $data['created_at'] = date("Y-m-d H:i:s");

            // Process Table Generator Data
            $products_table = json_decode($request->products, true);

            $product_list = [];
            $qty_list = [];
            $price_list = [];
            $production_unit_ids = [];

            foreach ($products_table as $item) {
                $product_list[] = $item['id'];
                $qty_list[] = $item['qty'];
                $price_list[] = $item['net_unit_price'];

                // Get unit
                $prod = Product::find($item['id']);
                $production_unit_ids[] = $prod->unit_id; // Simplication. Web uses complex unit logic.
            }

            $data['product_list'] = implode(",", $product_list);
            $data['qty_list'] = implode(",", $qty_list);
            $data['price_list'] = implode(",", $price_list);
            $data['production_units_ids'] = implode(",", $production_unit_ids);

            // Handle wastage percent (default 0 as it's not in my form schema directly yet, or assume 0)
            $data['wastage_percent'] = implode(",", array_fill(0, count($product_list), 0));

            $data['item'] = count($product_list);
            $data['total_qty'] = $request->total_qty ?? 1;
            $data['total_tax'] = 0; // Simplified

            // Calculate costs
            $data['total_cost'] = 0;
            foreach ($products_table as $item) {
                $data['total_cost'] += ($item['qty'] * $item['net_unit_price']);
            }
            $data['grand_total'] = $data['total_cost']; // + tax + shipping if applicable

            $lims_production_data = Production::create($data);

            // Stock Logic (Simplified for API)
            $product->qty += $data['total_qty'];
            $product->save();

            $lims_product_warehouse = \App\Models\Product_Warehouse::where([
                ['product_id', $request->product_id],
                ['warehouse_id', $request->warehouse_id]
            ])->latest()->first();

            if ($lims_product_warehouse) {
                $lims_product_warehouse->qty += $data['total_qty'];
                $lims_product_warehouse->save();
            } else {
                \App\Models\Product_Warehouse::create([
                    'product_id' => $request->product_id,
                    'warehouse_id' => $request->warehouse_id,
                    'qty' => $data['total_qty']
                ]);
            }

            // Deduct ingredients stock
            foreach ($products_table as $index => $item) {
                $ingredient_product = Product::find($item['id']);
                $ingredient_product->qty -= $item['qty'];
                $ingredient_product->save();

                $pt_warehouse = \App\Models\Product_Warehouse::where([
                    ['product_id', $item['id']],
                    ['warehouse_id', $request->warehouse_id]
                ])->latest()->first();

                if ($pt_warehouse) {
                    $pt_warehouse->qty -= $item['qty'];
                    $pt_warehouse->save();
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Production created successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $production = Production::findOrFail($id);
            $production->delete();
            return response()->json(['success' => true, 'message' => 'Production deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
