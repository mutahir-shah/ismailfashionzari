<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Models\GeneralSetting;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Product_Warehouse;
use App\Models\Unit;
use Modules\Manufacturing\Entities\Production;
use Modules\Manufacturing\Entities\ProductProduction;
use App\Models\Tax;
use App\Models\Account;
use App\Services\PaymentAccountService;
use App\Models\PosSetting;
use Auth;
use App\Traits\StaffAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\Domain\ProductionDomainService;

class ProductionController extends Controller
{
    use StaffAccess;

    public function index(Request $request)
    {
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

            $lims_pos_setting_data = PosSetting::select('stripe_public_key')->latest()->first();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_account_list = app(PaymentAccountService::class)->validOperationalAccounts();
            return view('manufacturing::production.index', compact('status', 'lims_account_list', 'lims_warehouse_list', 'lims_pos_setting_data', 'warehouse_id', 'starting_date', 'ending_date'));

    }

    public function productionData(Request $request)
    {
        $columns = array(
            1 => 'id',
            2 => 'reference_no',
            4 => 'product_id',
            5 => 'warehouse_id',
            6 => 'total_qty',
            7 => 'grand_total',
        );

        $warehouse_id = $request->input('warehouse_id');
        $status = $request->input('status');

        $q = Production::whereDate('created_at', '>=', $request->input('starting_date'))->whereDate('created_at', '<=', $request->input('ending_date'));
        //check staff access
        $this->staffAccessCheck($q);
        if ($warehouse_id)
            $q = $q->where('warehouse_id', $warehouse_id);
        if ($status)
            $q = $q->where('status', $status);

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if ($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'productions.' . $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        if (empty($request->input('search.value'))) {
            $q = Production::with('user', 'warehouse')
                ->whereDate('created_at', '>=', $request->input('starting_date'))
                ->whereDate('created_at', '<=', $request->input('ending_date'))
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir);
            //check staff access
            $this->staffAccessCheck($q);
            if ($warehouse_id)
                $q = $q->where('warehouse_id', $warehouse_id);
            if ($status)
                $q = $q->where('status', $status);

            $productions = $q->get();
        } else {
            $search = $request->input('search.value');
            $searchDate = date('Y-m-d', strtotime(str_replace('/', '-', $search)));

            $q = Production::leftJoin('products', 'productions.product_id', '=', 'products.id')
                ->with('warehouse', 'user')
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->where(function ($query) use ($search, $searchDate) {
                    // Search by created_at if input looks like a date
                    if ($searchDate && strtotime($search)) {
                        $query->whereDate('productions.created_at', '=', $searchDate);
                    }

                    // Search by reference_no or product name (optional product)
                    $query->orWhere('productions.reference_no', 'LIKE', "%{$search}%")
                        ->orWhere('products.name', 'LIKE', "%{$search}%");
                });

            // Role based access control
            if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $q->where('productions.user_id', Auth::id());
            } elseif (Auth::user()->role_id > 2 && config('staff_access') == 'warehouse') {
                $q->where('productions.warehouse_id', Auth::user()->warehouse_id);
            }

            // Get data and count
            $productions = $q->select('productions.*')->groupBy('productions.id')->get();
            $totalFiltered = $q->groupBy('productions.id')->count();
        }

        $data = array();
        if (!empty($productions)) {
            foreach ($productions as $key => $production) {
                //$nestedData['id'] = $production->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($production->created_at->toDateString()));
                $nestedData['reference_no'] = $production->reference_no;
                if ($production->status == 1) {
                    $nestedData['status'] = '<div class="badge badge-success">' . __('db.Completed') . '</div>';
                    $status = __('db.Completed');
                } else {
                    $nestedData['status'] = '<div class="badge badge-warning">' . __('db.Pending') . '</div>';
                    $status = __('db.Pending');
                }

                $nestedData['total_cost'] = number_format($production->total_cost, config('decimal'));
                $nestedData['product'] = @$production->product->name;
                $nestedData['quantity'] = $production->total_qty;
                $nestedData['warehouse'] = @$production->warehouse->name;
                $nestedData['total_tax'] = number_format($production->total_tax, config('decimal'));
                $nestedData['shipping_cost'] = number_format($production->shipping_cost, config('decimal'));
                $nestedData['production_cost'] = number_format($production->production_cost, config('decimal'));
                $nestedData['grand_total'] = number_format($production->grand_total, config('decimal'));

                $nestedData['options'] = '<div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' . __("db.action") . '
                              <span class="caret"></span>
                              <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button" class="btn btn-link view"><i class="ti ti-eye"></i> ' . __('db.View') . '</button>
                                </li>';
                                
                if ($production->status == 0) {
                    $nestedData['options'] .= '<li>
                        <a href="' . route('manufacturing.productions.edit', $production->id) . '" class="btn btn-link"><i class="ti ti-edit"></i> ' . __('db.edit') . '</a>
                    </li>
                    <form method="POST" action="' . route('manufacturing.productions.destroy', $production->id) . '">
                        ' . csrf_field() . '
                        ' . method_field("DELETE") . '
                        <li>
                            <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="ti ti-trash"></i> ' . __("db.delete") . '</button>
                        </li></form>';
                }

                $nestedData['options'] .= '</ul>
                </div>';

                // data for production details by one click
                $user = $production->user;
                $nestedData['production'] = array(
                    '[ "' . date(config('date_format'), strtotime($production->created_at->toDateString())) . '"',
                    ' "' . $production->reference_no . '"',
                    ' "' . $status . '"',
                    ' "' . $production->id . '"',
                    ' "' . $production->warehouse->name . '"',
                    ' "' . $production->total_tax . '"',
                    ' "' . $production->total_cost . '"',
                    ' "' . $production->production_cost . '"',
                    ' "' . $production->shipping_cost . '"',
                    ' "' . $production->grand_total . '"',
                    ' "' . preg_replace('/\s+/S', " ", $production->note) . '"',
                    ' "' . $user->name . '"',
                    ' "' . $user->email . '"',
                    ' "' . $production->document . '"]'
                );
                $data[] = $nestedData;
            }
        }
        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        );
        echo json_encode($json_data);
    }


    public function create()
    {
        if (Auth::user()->role_id > 2) {
            $lims_warehouse_list = Warehouse::where([
                ['is_active', true],
                ['id', Auth::user()->warehouse_id]
            ])->get();
        } else {
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        }
        $lims_product_list = Product::where([
            ['is_recipe', 1],
            ['is_active', true]
        ])->get();
        $lims_tax_list = Tax::where('is_active', true)->get();
        $lims_product_list_without_variant = $this->productWithoutVariant();
        $lims_product_list_with_variant = $this->productWithVariant();
        // return view('manufacturing::production-copy.create', compact('lims_product_list_with_variant','lims_product_list_without_variant', 'lims_warehouse_list', 'lims_product_list', 'lims_tax_list'));
        return view('manufacturing::production.create', compact('lims_product_list_with_variant', 'lims_product_list_without_variant', 'lims_warehouse_list', 'lims_product_list', 'lims_tax_list'));
    }

    public function store(Request $request, ProductionDomainService $productions)
    {
        $productions->create($request->except('document'), (int) Auth::id());

        return redirect('manufacturing/productions')->with('message', __('db.production_created_successfully'));
    }

    private function storeLegacy(Request $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->except('document');
            $data['user_id'] = Auth::id();
            $data['reference_no'] = 'production-' . date("Ymd") . '-' . date("his");
            $product = Product::query()->findOrFail($request->product_id);
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
                    $document->move('public/documents/production', $documentName);
                } else {
                    $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                    $document->move('public/documents/production', $documentName);
                }
                $data['document'] = $documentName;
            }
            if (isset($data['created_at']))
                $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
            else
                $data['created_at'] = date("Y-m-d H:i:s");
            $data['item'] = count($request->product_qty);
            $data['total_qty'] = $request->total_qty ?? 1;
            $data['status'] = $request->status ?? 0;
            $data['product_list'] = implode(",", $data['product_list']);
            $data['qty_list'] = implode(",", $data['product_qty']);
            $data['price_list'] = implode(",", $data['unit_price']);
            $data['wastage_percent'] = implode(",", $data['wastage_percent'] ?? []);
            $data['production_units_ids'] = implode(",", $data['production_unit_ids']);
            $data['total_tax'] = 0;
            $lims_production_data = Production::create($data);

            // stock calculate

            // product stock
            $product->qty += $request->total_qty;
            $product->save();

            // wherehouse stock
            $lims_product_warehouse = Product_Warehouse::query()
                ->where([
                    ['product_id', $request->product_id],
                    ['warehouse_id', $request->warehouse_id]
                ])
                ->latest()
                ->first();
            if ($lims_product_warehouse) {
                $lims_product_warehouse->qty = $lims_product_warehouse->qty + $request->total_qty;
                $lims_product_warehouse->save();
            } else {
                $lims_product_warehouse = new Product_Warehouse();
                $lims_product_warehouse->product_id = $request->product_id;
                $lims_product_warehouse->warehouse_id = $request->warehouse_id;
                $lims_product_warehouse->qty = $request->total_qty;
                $lims_product_warehouse->save();
            }


            $product_id = $request->product_list;
            $qty = $data['product_qty'];
            $purchase_unit = $data['production_unit_ids'];
            $net_unit_cost = $data['unit_price'];
            // $tax_rate = $data['tax_rate'];
            // $tax = $data['tax'];
            $total = $data['subtotal'];

            $child_variant_list = $request->variant_id ?? [];
            foreach ($product_id as $index => $child_id) {
                $lims_purchase_unit_data  = Unit::where('id', $purchase_unit[$index])->first();
                $child_data = Product::find($child_id);
                $wastage_percent = $request->wastage_percent[$index] ?? 0;

                if ($lims_purchase_unit_data->operator == '*') {
                    $reduced_qty = $qty[$index] * $lims_purchase_unit_data->operation_value;
                } else {
                    $reduced_qty = $qty[$index] / $lims_purchase_unit_data->operation_value;
                }
                
                // apply wastage percent
                if ($wastage_percent > 0) {
                    $reduced_qty += ($reduced_qty * $wastage_percent / 100);
                }
                if (count($child_variant_list) && isset($child_variant_list[$index]) && $child_variant_list[$index]) {
                    $child_product_variant_data = ProductVariant::where([
                        ['product_id', $child_id],
                        ['variant_id', $child_variant_list[$index]]
                    ])->first();
                    $child_product_variant_data->qty -= $reduced_qty;
                    $child_product_variant_data->save();

                    $child_warehouse_data = Product_Warehouse::where([
                        ['product_id', $child_id],
                        ['variant_id', $child_variant_list[$index]],
                        ['warehouse_id', $lims_production_data->warehouse_id],
                    ])->first();
                } else {
                    $child_warehouse_data = Product_Warehouse::where([
                        ['product_id', $child_id],
                        ['warehouse_id', $lims_production_data->warehouse_id],
                    ])->first();
                }

                if ($child_warehouse_data) {
                    $child_warehouse_data->qty -= $reduced_qty;
                    $child_warehouse_data->save();
                }

                $child_data->qty -= $reduced_qty;
                $child_data->save();
            }
            DB::commit();
            return redirect('manufacturing/productions')->with('message', __('db.production_created_successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();
            dd($e);
            return redirect('manufacturing/productions')->with('not_permitted', __('db.generic_error_please_try_again'));
        }
    }


    public function storebackup(Request $request)
    {
        $data = $request->except('document');
        $data['user_id'] = Auth::id();
        $data['reference_no'] = 'production-' . date("Ymd") . '-' . date("his");
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
                $document->move('public/documents/production', $documentName);
            } else {
                $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                $document->move('public/documents/production', $documentName);
            }
            $data['document'] = $documentName;
        }
        if (isset($data['created_at']))
            $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
        else
            $data['created_at'] = date("Y-m-d H:i:s");

        $lims_production_data = Production::create($data);
        $product_id = $data['product_id'];
        $product_code = $data['product_code'];
        $qty = $data['qty'];
        $recieved = $data['recieved'];
        $purchase_unit = $data['purchase_unit'];
        $net_unit_cost = $data['net_unit_cost'];
        $tax_rate = $data['tax_rate'];
        $tax = $data['tax'];
        $total = $data['subtotal'];
        $product_production = [];

        foreach ($product_id as $i => $id) {
            $lims_purchase_unit_data  = Unit::where('unit_name', $purchase_unit[$i])->first();

            if ($lims_purchase_unit_data->operator == '*') {
                $quantity = $recieved[$i] * $lims_purchase_unit_data->operation_value;
            } else {
                $quantity = $recieved[$i] / $lims_purchase_unit_data->operation_value;
            }
            $lims_product_data = Product::find($id);

            $child_product_list = explode(",", $lims_product_data->product_list);
            $child_variant_list = explode(",", $lims_product_data->variant_list);
            $child_qty_list = explode(",", $lims_product_data->qty_list);

            if ($lims_purchase_unit_data->operator == '*') {
                $reduced_qty = $qty[$i] * $lims_purchase_unit_data->operation_value;
            } else {
                $reduced_qty = $qty[$i] / $lims_purchase_unit_data->operation_value;
            }

            //ducting quantity from child products
            $child_product_list = explode(",", $lims_product_data->product_list);
            if ($lims_product_data->variant_list)
                $child_variant_list = explode(",", $lims_product_data->variant_list);
            else
                $child_variant_list = [];

            foreach ($child_product_list as $index => $child_id) {
                $child_data = Product::find($child_id);
                if (count($child_variant_list) && $child_variant_list[$index]) {
                    $child_product_variant_data = ProductVariant::where([
                        ['product_id', $child_id],
                        ['variant_id', $child_variant_list[$index]]
                    ])->first();

                    $child_warehouse_data = Product_Warehouse::where([
                        ['product_id', $child_id],
                        ['variant_id', $child_variant_list[$index]],
                        ['warehouse_id', $lims_production_data->warehouse_id],
                    ])->first();

                    $child_product_variant_data->qty -= $reduced_qty * $child_qty_list[$index];
                    $child_product_variant_data->save();
                } else {
                    $child_warehouse_data = Product_Warehouse::where([
                        ['product_id', $child_id],
                        ['warehouse_id', $lims_production_data->warehouse_id],
                    ])->first();
                }

                $child_data->qty -= $reduced_qty * $child_qty_list[$index];
                $child_warehouse_data->qty -= $reduced_qty * $child_qty_list[$index];

                $child_data->save();
                $child_warehouse_data->save();
            }

            $lims_product_warehouse_data = Product_Warehouse::where([
                ['product_id', $id],
                ['warehouse_id', $data['warehouse_id']],
            ])->first();

            //add quantity to product table
            $lims_product_data->qty = $lims_product_data->qty + $quantity;
            $lims_product_data->save();
            //add quantity to warehouse
            if ($lims_product_warehouse_data) {
                $lims_product_warehouse_data->qty = $lims_product_warehouse_data->qty + $quantity;
            } else {
                $lims_product_warehouse_data = new Product_Warehouse();
                $lims_product_warehouse_data->product_id = $id;
                $lims_product_warehouse_data->warehouse_id = $data['warehouse_id'];
                $lims_product_warehouse_data->qty = $quantity;
            }
            $lims_product_warehouse_data->save();

            $product_production['production_id'] = $lims_production_data->id;
            $product_production['product_id'] = $id;
            $product_production['qty'] = $qty[$i];
            $product_production['recieved'] = $recieved[$i];
            $product_production['purchase_unit_id'] = $lims_purchase_unit_data->id;
            $product_production['net_unit_cost'] = $net_unit_cost[$i];
            $product_production['tax_rate'] = $tax_rate[$i];
            $product_production['tax'] = $tax[$i];
            $product_production['total'] = $total[$i];
            ProductProduction::create($product_production);
        }
        return redirect('manufacturing/productions')->with('message', __('db.production_created_successfully'));
    }

    public function productProductionData($id)
    {
        try {
            $lims_product_production_data = Production::where('id', $id)->first();
            $proudction_units_ids = explode(',', $lims_product_production_data->production_units_ids);
            $product_list = explode(',', $lims_product_production_data->product_list);
            $wastage_percent = explode(',', $lims_product_production_data->wastage_percent);
            $qty_list = explode(',', $lims_product_production_data->qty_list);
            $variant_list = explode(',', $lims_product_production_data->variant_list);
            $price_list = explode(',', $lims_product_production_data->price_list);

            // production info
            $production_info = [];
            $production_info['shipping_cost']      = $lims_product_production_data->shipping_cost;
            $production_info['production_cost']    = $lims_product_production_data->production_cost ?? 0;
            $production_info['grand_total']        = $lims_product_production_data->grand_total;
            $production_info['total_qty']          = $lims_product_production_data->total_qty;
            $product_production = [];
            foreach ($product_list as $key => $id) {
                $product = Product::find($id);
                $variant_id = $variant_list[$key] ?? null;

                // Handle variant code
                if ($variant_id) {
                    $variant = ProductVariant::FindExactProduct($id, $variant_id)->first();
                    if ($variant) {
                        $product->code = $variant->item_code;
                    }
                }

                $unit = Unit::query()
                    ->where('id', $proudction_units_ids[$key])
                    ->first();
                if ($unit->operator == '*') {
                    $subtotal = $price_list[$key] * ($unit->operation_value * $qty_list[$key] ?? 1);
                } elseif ($unit->operator == '/') {
                    $subtotal = $price_list[$key] / $unit->operation_value;
                } else {
                    $subtotal = $price_list[$key] * 1;
                }

                $product_production[] = [
                    'id'                => $product->id,
                    'name'              => $product->name,
                    'code'              => $product->code,
                    'wastage_percent'   => $wastage_percent[$key] ?? 0,
                    'qty'               => $qty_list[$key] ?? 1,
                    'unit_cost'         => $product->cost,
                    'unit_price'        => $price_list[$key] ?? 0,
                    'subtotal'          => $subtotal,
                    'unit_name'         => $unit->unit_name,
                ];
            }

            return response()->json([
                'status' => true,
                'data' => $product_production,
                'production_info' => $production_info
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something is wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function backupproductProductionData($id)
    {
        try {
            $lims_product_production_data = ProductProduction::where('production_id', $id)->get();
            $product_production = [];
            foreach ($lims_product_production_data as $key => $product_production_data) {
                $product = Product::find($product_production_data->product_id);
                $unit = Unit::find($product_production_data->purchase_unit_id);
                $product_production[0][$key] = $product->name . ' [' . $product->code . ']';
                $product_production[1][$key] = $product_production_data->qty;
                $product_production[2][$key] = $product_production_data->recieved;
                $product_production[3][$key] = $unit->unit_code;
                $product_production[4][$key] = $product_production_data->tax;
                $product_production[5][$key] = $product_production_data->tax_rate;
                $product_production[6][$key] = $product_production_data->total;
            }
            return $product_production;
        } catch (Exception $e) {
            return 'Something is wrong!';
        }
    }

    public function show($id)
    {
        return view('manufacturing::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $lims_production_data = Production::find($id);

        if (!$lims_production_data) {
            return redirect('manufacturing/productions')->with('not_permitted', __('db.production_not_found'));
        }

        if ($lims_production_data->status == 1) {
            return redirect('manufacturing/productions')->with('not_permitted', __('db.finalized_production_cannot_be_edited'));
        }

        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        $lims_product_list = Product::where('is_recipe', 1)->where('is_active', true)->get();
        $lims_tax_list = Tax::where('is_active', true)->get();
        $lims_product_list_without_variant = $this->productWithoutVariant();
        $lims_product_list_with_variant = $this->productWithVariant();
        
        return view('manufacturing::production.edit', compact('lims_production_data', 'lims_warehouse_list', 'lims_product_list', 'lims_tax_list', 'lims_product_list_without_variant', 'lims_product_list_with_variant'));
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $lims_production_data = Production::find($id);

            if (!$lims_production_data) {
                return redirect('manufacturing/productions')->with('not_permitted', __('db.production_not_found'));
            }

            if ($lims_production_data->status == 1) {
                return redirect('manufacturing/productions')->with('not_permitted', __('db.finalized_production_cannot_be_edited'));
            }

            // === 1. REVERT OLD STOCK ===
            $product_id_list = explode(",", $lims_production_data->product_list);
            $qty_list = explode(",", $lims_production_data->qty_list);
            $production_unit_ids = explode(",", $lims_production_data->production_units_ids);

            // Deduct finished product stock (reverse of adding)
            $finished_product = Product::find($lims_production_data->product_id);
            $finished_product->qty -= $lims_production_data->total_qty;
            $finished_product->save();

            // Deduct from warehouse
            $lims_product_warehouse = Product_Warehouse::where([
                ['product_id', $lims_production_data->product_id],
                ['warehouse_id', $lims_production_data->warehouse_id]
            ])->first();

            if ($lims_product_warehouse) {
                $lims_product_warehouse->qty -= $lims_production_data->total_qty;
                $lims_product_warehouse->save();
            }

            // Add back child products stock (reverse of deduction)
            $child_variant_list = $lims_production_data->variant_list ? explode(",", $lims_production_data->variant_list) : [];
            $wastage_percent_list = explode(",", $lims_production_data->wastage_percent ?? "");

            foreach ($product_id_list as $index => $child_id) {
                $lims_production_unit_data = Unit::find($production_unit_ids[$index]);
                $child_data = Product::find($child_id);

                if (!$child_data || !$lims_production_unit_data) {
                    continue;
                }

                $wastage_percent = floatval($wastage_percent_list[$index] ?? 0);

                // Calculate the quantity to add back
                if ($lims_production_unit_data->operator == '*') {
                    $add_back_qty = $qty_list[$index] * $lims_production_unit_data->operation_value;
                } else {
                    $add_back_qty = $qty_list[$index] / $lims_production_unit_data->operation_value;
                }

                if ($wastage_percent > 0) {
                    $add_back_qty += ($add_back_qty * $wastage_percent / 100);
                }

                // Handle variant stock
                if (count($child_variant_list) && isset($child_variant_list[$index]) && $child_variant_list[$index]) {
                    $child_product_variant_data = ProductVariant::where([
                        ['product_id', $child_id],
                        ['variant_id', $child_variant_list[$index]]
                    ])->first();

                    if ($child_product_variant_data) {
                        $child_product_variant_data->qty += $add_back_qty;
                        $child_product_variant_data->save();
                    }

                    $child_warehouse_data = Product_Warehouse::where([
                        ['product_id', $child_id],
                        ['variant_id', $child_variant_list[$index]],
                        ['warehouse_id', $lims_production_data->warehouse_id],
                    ])->first();
                } else {
                    $child_warehouse_data = Product_Warehouse::where([
                        ['product_id', $child_id],
                        ['warehouse_id', $lims_production_data->warehouse_id],
                    ])->first();
                }

                // Add back to warehouse
                if ($child_warehouse_data) {
                    $child_warehouse_data->qty += $add_back_qty;
                    $child_warehouse_data->save();
                } else {
                    // Create warehouse entry if not exists
                    $child_warehouse_data = new Product_Warehouse();
                    $child_warehouse_data->product_id = $child_id;
                    $child_warehouse_data->warehouse_id = $lims_production_data->warehouse_id;
                    $child_warehouse_data->qty = $add_back_qty;
                    if (isset($child_variant_list[$index]) && $child_variant_list[$index]) {
                        $child_warehouse_data->variant_id = $child_variant_list[$index];
                    }
                    $child_warehouse_data->save();
                }
            }

            // === 2. UPDATE RECORD AND APPLY NEW STOCK ===
            $data = $request->except('document');
            $product = Product::query()->findOrFail($request->product_id);
            $document = $request->document;
            if ($document) {
                $v = Validator::make(
                    ['extension' => strtolower($request->document->getClientOriginalExtension())],
                    ['extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt']
                );
                if ($v->fails())
                    return redirect()->back()->withErrors($v->errors());

                $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
                $documentName = date("Ymdhis");
                if (!config('database.connections.saleprosaas_landlord')) {
                    $documentName = $documentName . '.' . $ext;
                    $document->move('public/documents/production', $documentName);
                } else {
                    $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                    $document->move('public/documents/production', $documentName);
                }
                $data['document'] = $documentName;
                if ($lims_production_data->document) {
                    @unlink('public/documents/production/' . $lims_production_data->document);
                }
            }

            if (isset($data['created_at']))
                $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
            else
                $data['created_at'] = date("Y-m-d H:i:s");

            $data['item'] = count($request->product_qty);
            $data['total_qty'] = $request->total_qty ?? 1;
            $data['status'] = $request->status ?? 0;
            $data['product_list'] = implode(",", $data['product_list']);
            $data['qty_list'] = implode(",", $data['product_qty']);
            $data['price_list'] = implode(",", $data['unit_price']);
            $data['wastage_percent'] = implode(",", $data['wastage_percent'] ?? []);
            $data['production_units_ids'] = implode(",", $data['production_unit_ids']);
            $data['variant_list'] = implode(",", $request->variant_id ?? []);
            $data['total_tax'] = 0;
            
            $lims_production_data->update($data);

            // product stock (finished product)
            $product->qty += $request->total_qty;
            $product->save();

            $lims_product_warehouse = Product_Warehouse::where([
                ['product_id', $request->product_id],
                ['warehouse_id', $request->warehouse_id]
            ])->first();

            if ($lims_product_warehouse) {
                $lims_product_warehouse->qty += $request->total_qty;
                $lims_product_warehouse->save();
            } else {
                $lims_product_warehouse = new Product_Warehouse();
                $lims_product_warehouse->product_id = $request->product_id;
                $lims_product_warehouse->warehouse_id = $request->warehouse_id;
                $lims_product_warehouse->qty = $request->total_qty;
                $lims_product_warehouse->save();
            }

            // child stock
            $product_id = $request->product_list;
            $qty = $request->product_qty;
            $purchase_unit = $request->production_unit_ids;
            $child_variant_list = $request->variant_id ?? [];

            foreach ($product_id as $index => $child_id) {
                $lims_purchase_unit_data  = Unit::where('id', $purchase_unit[$index])->first();
                $child_data = Product::find($child_id);
                $wastage_percent = $request->wastage_percent[$index] ?? 0;

                if ($lims_purchase_unit_data->operator == '*') {
                    $reduced_qty = $qty[$index] * $lims_purchase_unit_data->operation_value;
                } else {
                    $reduced_qty = $qty[$index] / $lims_purchase_unit_data->operation_value;
                }
                
                // apply wastage percent
                if ($wastage_percent > 0) {
                    $reduced_qty += ($reduced_qty * $wastage_percent / 100);
                }

                if (count($child_variant_list) && isset($child_variant_list[$index]) && $child_variant_list[$index]) {
                    $child_product_variant_data = ProductVariant::where([
                        ['product_id', $child_id],
                        ['variant_id', $child_variant_list[$index]]
                    ])->first();

                    if ($child_product_variant_data) {
                        $child_product_variant_data->qty -= $reduced_qty;
                        $child_product_variant_data->save();
                    }

                    $child_warehouse_data = Product_Warehouse::where([
                        ['product_id', $child_id],
                        ['variant_id', $child_variant_list[$index]],
                        ['warehouse_id', $request->warehouse_id],
                    ])->first();
                } else {
                    $child_warehouse_data = Product_Warehouse::where([
                        ['product_id', $child_id],
                        ['warehouse_id', $request->warehouse_id],
                    ])->first();
                }

                if ($child_warehouse_data) {
                    $child_warehouse_data->qty -= $reduced_qty;
                    $child_warehouse_data->save();
                } else {
                    $child_warehouse_data = new Product_Warehouse();
                    $child_warehouse_data->product_id = $child_id;
                    $child_warehouse_data->warehouse_id = $request->warehouse_id;
                    $child_warehouse_data->qty = -$reduced_qty;
                    if (isset($child_variant_list[$index]) && $child_variant_list[$index]) {
                        $child_warehouse_data->variant_id = $child_variant_list[$index];
                    }
                    $child_warehouse_data->save();
                }
            }

            DB::commit();
            return redirect('manufacturing/productions')->with('message', 'Production Updated Successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', __('db.generic_error_please_try_again'));
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id, ProductionDomainService $productions)
    {
        try {
            $productions->delete(Production::query()->findOrFail($id));
            return redirect('manufacturing/productions')->with('message', __('db.production_deleted_successfully'));
        } catch (\Throwable $e) {
            return redirect('manufacturing/productions')->with('not_permitted', $e->getMessage());
        }
    }

    private function destroyLegacy($id)
    {
        try {
            DB::beginTransaction();

            $lims_production_data = Production::find($id);

            if (!$lims_production_data) {
                return redirect('manufacturing/productions')->with('not_permitted', __('db.production_not_found'));
            }

            if ($lims_production_data->status == 1) {
                return redirect('manufacturing/productions')->with('not_permitted', __('db.finalized_production_cannot_be_deleted'));
            }

            // Get production data
            $product_id_list = explode(",", $lims_production_data->product_list);
            $qty_list = explode(",", $lims_production_data->qty_list);
            $production_unit_ids = explode(",", $lims_production_data->production_units_ids);

            // FIRST: Deduct finished product stock (reverse of adding)
            $finished_product = Product::find($lims_production_data->product_id);

            $finished_product->qty -= $lims_production_data->total_qty;
            $finished_product->save();

            // Deduct from warehouse
            $lims_product_warehouse = Product_Warehouse::where([
                ['product_id', $lims_production_data->product_id],
                ['warehouse_id', $lims_production_data->warehouse_id]
            ])->first();

            if ($lims_product_warehouse) {
                $lims_product_warehouse->qty -= $lims_production_data->total_qty;
                $lims_product_warehouse->save();
            }

            // SECOND: Add back child products stock (reverse of deduction)
            $child_variant_list = [];
            if (request()->has('variant_id')) {
                $child_variant_list = request()->variant_id;
            }

            foreach ($product_id_list as $index => $child_id) {
                $lims_production_unit_data = Unit::find($production_unit_ids[$index]);
                $child_data = Product::find($child_id);

                if (!$child_data || !$lims_production_unit_data) {
                    continue;
                }

                $wastage_percent_list = explode(",", $lims_production_data->wastage_percent ?? "");
                $wastage_percent = floatval($wastage_percent_list[$index] ?? 0);

                // Calculate the quantity to add back
                if ($lims_production_unit_data->operator == '*') {
                    $add_back_qty = $qty_list[$index] * $lims_production_unit_data->operation_value;
                } else {
                    $add_back_qty = $qty_list[$index] / $lims_production_unit_data->operation_value;
                }

                if ($wastage_percent > 0) {
                    $add_back_qty += ($add_back_qty * $wastage_percent / 100);
                }


                // Handle variant stock
                if (count($child_variant_list) && isset($child_variant_list[$index]) && $child_variant_list[$index]) {
                    $child_product_variant_data = ProductVariant::where([
                        ['product_id', $child_id],
                        ['variant_id', $child_variant_list[$index]]
                    ])->first();

                    if ($child_product_variant_data) {
                        $child_product_variant_data->qty += $add_back_qty;
                        $child_product_variant_data->save();
                    }

                    $child_warehouse_data = Product_Warehouse::where([
                        ['product_id', $child_id],
                        ['variant_id', $child_variant_list[$index]],
                        ['warehouse_id', $lims_production_data->warehouse_id],
                    ])->first();
                } else {
                    $child_warehouse_data = Product_Warehouse::where([
                        ['product_id', $child_id],
                        ['warehouse_id', $lims_production_data->warehouse_id],
                    ])->first();
                }

                // Add back to warehouse
                if ($child_warehouse_data) {
                    $child_warehouse_data->qty += $add_back_qty;
                    $child_warehouse_data->save();
                } else {
                    // Create warehouse entry if not exists
                    $child_warehouse_data = new Product_Warehouse();
                    $child_warehouse_data->product_id = $child_id;
                    $child_warehouse_data->warehouse_id = $lims_production_data->warehouse_id;
                    $child_warehouse_data->qty = $add_back_qty;
                    if (isset($child_variant_list[$index]) && $child_variant_list[$index]) {
                        $child_warehouse_data->variant_id = $child_variant_list[$index];
                    }
                    $child_warehouse_data->save();
                }

                // Add back to main product table
                $child_data->qty += $add_back_qty;
                $child_data->save();
            }

            // Delete document if exists
            if ($lims_production_data->document && file_exists('public/documents/production/' . $lims_production_data->document)) {
                unlink('public/documents/production/' . $lims_production_data->document);
            }

            // Delete production record
            $lims_production_data->delete();

            DB::commit();
            return redirect('manufacturing/productions')->with('message', __('db.production_deleted_successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Production deletion failed: ' . $e->getMessage());
            return redirect('manufacturing/productions')->with('not_permitted', __('db.generic_error_please_try_again'));
        }
    }

    public function productWithoutVariant()
    {
        return Product::ActiveStandard()->select('id', 'name', 'code')
            ->whereNull('is_variant')->get();
    }

    public function productWithVariant()
    {
        return Product::join('product_variants', 'products.id', 'product_variants.product_id')
            ->ActiveStandard()
            ->whereNotNull('is_variant')
            ->select('products.id', 'products.name', 'product_variants.item_code', 'product_variants.qty')
            ->orderBy('position')->get();
    }

    public function getIngredients(Request $request)
    {
        $lims_product_data = Product::where('id', $request->product_id)->first();
        if ($lims_product_data->variant_option) {
            $lims_product_data->variant_option = json_decode($lims_product_data->variant_option);
            $lims_product_data->variant_value = json_decode($lims_product_data->variant_value);
        }
        $lims_product_variant_data = $lims_product_data->variant()->orderBy('position')->get();
        if ($request->recipe == true) {
            $product = view('manufacturing::recipe.ingredients', compact('lims_product_data', 'lims_product_variant_data'))->render();
        } else {
            $warehouse_id = $request->warehouse_id;
            $product = view('manufacturing::production.ingredients', compact('lims_product_data', 'lims_product_variant_data', 'warehouse_id'))->render();
        }

        return response()->json(['ingredients' => $product]);
    }
}
