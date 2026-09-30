<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\Product_Warehouse;
use App\Models\Product;
use App\Models\DamageStock;
use App\Models\ProductDamageStock;
use Illuminate\Support\Facades\DB;
use App\Models\Unit;
use App\Models\ProductVariant;
use Auth;
use Spatie\Permission\Models\Role;

class DamageStockController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('damage-stock')) {
            $lims_damage_all = DamageStock::orderBy('id', 'desc')->get();
            return view('backend.damage.index', compact('lims_damage_all'));
        } else {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }
    }

    public function getProduct($warehouseId)
    {
        $purchaseSummary = DB::table('product_purchases')
            ->join('purchases', 'product_purchases.purchase_id', '=', 'purchases.id')
            ->where('purchases.warehouse_id', $warehouseId)
            ->whereNull('purchases.deleted_at')
            ->groupBy('product_purchases.product_id', 'product_purchases.variant_id')
            ->selectRaw('
                product_purchases.product_id,
                product_purchases.variant_id,
                SUM(product_purchases.qty) AS total_qty,
                SUM(product_purchases.total) AS total_cost
            ')
            ->get()
            ->keyBy(function ($row) {
                return $row->product_id . '_' . ($row->variant_id ?? 0);
            });

        // 2. Non-variant products
        $products = DB::table('products')
            ->join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
            ->whereNull('products.is_variant')
            ->where('products.is_active', 1)
            ->where('product_warehouse.warehouse_id', $warehouseId)
            ->groupBy('products.id', 'products.code', 'products.name', 'products.cost')
            ->select(
                'products.id',
                'products.code',
                'products.name',
                'products.cost',
                DB::raw('SUM(product_warehouse.qty) as qty')
            )
            ->get();

        // 3. Variant products
        $variantProducts = DB::table('products')
            ->join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
            ->join('product_variants', 'product_warehouse.variant_id', '=', 'product_variants.id')
            ->whereNotNull('products.is_variant')
            ->where('products.is_active', 1)
            ->where('product_warehouse.warehouse_id', $warehouseId)
            ->groupBy('products.id', 'products.name', 'products.cost', 'product_variants.item_code', 'product_variants.id')
            ->select(
                'products.id',
                'products.name',
                'products.cost',
                DB::raw('SUM(product_warehouse.qty) as qty'),
                'product_variants.item_code',
                'product_variants.id as variant_id'
            )
            ->get();

        // 4. Build result
        $product_code = [];
        $product_name = [];
        $product_qty  = [];
        $product_cost = [];

        foreach ($products as $p) {
            $key     = $p->id . '_0';
            $summary = $purchaseSummary[$key] ?? null;
            $cost    = ($summary && $summary->total_qty > 0)
                ? round($summary->total_cost / $summary->total_qty, 4)
                : $p->cost;

            $product_code[] = $p->code;
            $product_name[] = $p->name;
            $product_qty[]  = $p->qty;
            $product_cost[] = $cost;
        }

        foreach ($variantProducts as $p) {
            $key     = $p->id . '_' . $p->variant_id;
            $summary = $purchaseSummary[$key] ?? null;
            $cost    = ($summary && $summary->total_qty > 0)
                ? round($summary->total_cost / $summary->total_qty, 4)
                : $p->cost;

            $product_code[] = $p->item_code;
            $product_name[] = $p->name;
            $product_qty[]  = $p->qty;
            $product_cost[] = $cost;
        }

        return [$product_code, $product_name, $product_qty, $product_cost];
    }

    public function limsProductSearch(Request $request)
    {
        $product_code    = explode("(", $request['data']);
        $product_info    = explode("|", $request['data']);
        $product_code[0] = rtrim($product_code[0], " ");

        $lims_product_data = Product::where([
            ['code', $product_code[0]],
            ['is_active', true]
        ])->first();

        if (!$lims_product_data) {
            $lims_product_data = Product::join('product_variants', 'products.id', 'product_variants.product_id')
                ->select('products.id', 'products.name', 'products.is_variant', 'product_variants.id as product_variant_id', 'product_variants.item_code')
                ->where([
                    ['product_variants.item_code', $product_code[0]],
                    ['products.is_active', true]
                ])->first();
        }

        $product            = [];
        $product[]          = $lims_product_data->name;
        $product_variant_id = null;

        if ($lims_product_data->is_variant) {
            $product[]          = $lims_product_data->item_code;
            $product_variant_id = $lims_product_data->product_variant_id;
        } else {
            $product[] = $lims_product_data->code;
        }

        $product[] = $lims_product_data->id;
        $product[] = $product_variant_id;
        $product[] = $product_info[1];

        $quantity = explode("|", $request['data']);
        if (count($quantity) >= 3) {
            $product[] = $quantity[2];
        }

        return $product;
    }

    public function create()
    {
        $lims_warehouse_list               = Warehouse::where('is_active', true)->get();
        $lims_product_list_without_variant = $this->productWithoutVariant();
        $lims_product_list_with_variant    = $this->productWithVariant();

        return view('backend.damage.create', compact(
            'lims_warehouse_list',
            'lims_product_list_without_variant',
            'lims_product_list_with_variant'
        ));
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
            ->select('products.id', 'products.name', 'product_variants.item_code')
            ->orderBy('position')
            ->get();
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->except('document');

            $data['reference_no'] = 'dmg-' . date("Ymd") . '-' . date("his");
            $data['user_id']      = Auth::id();

            $document = $request->document;
            if ($document) {
                $documentName = $document->getClientOriginalName();
                $document->move(public_path('documents/damage_stock'), $documentName);
                $data['document'] = $documentName;
            }

            $lims_damage_data = DamageStock::create($data);

            $product_id   = $data['product_id'];
            $product_code = $data['product_code'];
            $qty          = $data['qty'];
            $unit_cost    = isset($data['unit_cost']) ? $data['unit_cost'] : [];

            foreach ($product_id as $key => $pro_id) {
                $lims_product_data = Product::find($pro_id);

                if ($lims_product_data->is_variant) {
                    $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')
                        ->FindExactProductWithCode($pro_id, $product_code[$key])
                        ->first();

                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $pro_id],
                        ['variant_id', $lims_product_variant_data->variant_id],
                        ['warehouse_id', $data['warehouse_id']],
                    ])->first();

                    // Always minus for damage
                    $lims_product_variant_data->qty -= $qty[$key];
                    $lims_product_variant_data->save();

                    $variant_id = $lims_product_variant_data->variant_id;
                } else {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $pro_id],
                        ['warehouse_id', $data['warehouse_id']],
                    ])->first();

                    $variant_id = null;
                }

                // Always minus for damage (no action column)
                $lims_product_data->qty              -= $qty[$key];
                $lims_product_warehouse_data->qty    -= $qty[$key];

                $lims_product_data->save();
                $lims_product_warehouse_data->save();

                $product_damage                    = [];
                $product_damage['product_id']      = $pro_id;
                $product_damage['variant_id']       = $variant_id;
                $product_damage['damage_stock_id']  = $lims_damage_data->id;
                $product_damage['qty']              = $qty[$key];
                if (isset($unit_cost[$key]))
                    $product_damage['unit_cost'] = $unit_cost[$key];

                ProductDamageStock::create($product_damage);
            }

            DB::commit();
            return redirect('damage-stock')->with('message', __('db.Data inserted successfully'));

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect('damage-stock')->with('not_permitted', __('db.generic_error_please_try_again'));
        }
    }

    public function edit($id)
    {
        $lims_damage_data         = DamageStock::find($id);
        $lims_product_damage_data = ProductDamageStock::where('damage_stock_id', $id)->get();
        $lims_warehouse_list      = Warehouse::where('is_active', true)->get();

        return view('backend.damage.edit', compact(
            'lims_damage_data',
            'lims_warehouse_list',
            'lims_product_damage_data'
        ));
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $data             = $request->except('document');
            $lims_damage_data = DamageStock::find($id);

            $document = $request->document;
            if ($document) {
                $this->fileDelete(public_path('documents/damage_stock/'), $lims_damage_data->document);
                $documentName = $document->getClientOriginalName();
                $document->move(public_path('documents/damage_stock'), $documentName);
                $data['document'] = $documentName;
            }

            $lims_product_damage_data = ProductDamageStock::where('damage_stock_id', $id)->get();

            $product_id         = $data['product_id'];
            $product_variant_id = $data['product_variant_id'] ?? [];
            $product_code       = $data['product_code'];
            $qty                = $data['qty'];         // adjust qty (from edit form input)
            $unit_cost          = $data['unit_cost'];
            $old_product_id         = [];
            $old_product_variant_id = [];

            // ── STEP 1: Restore পুরনো damage qty → stock বাড়াও ──────
            foreach ($lims_product_damage_data as $key => $product_damage_data) {
                $old_product_id[] = $product_damage_data->product_id;
                $lims_product_data = Product::find($product_damage_data->product_id);

                if ($product_damage_data->variant_id) {
                    $lims_product_variant_data = ProductVariant::where([
                        ['product_id', $product_damage_data->product_id],
                        ['variant_id', $product_damage_data->variant_id]
                    ])->first();

                    $old_product_variant_id[$key] = $lims_product_variant_data->id;

                    // Restore variant qty (পুরনো damage ছিল minus, তাই restore = plus)
                    $lims_product_variant_data->qty += $product_damage_data->qty;
                    $lims_product_variant_data->save();

                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $product_damage_data->product_id],
                        ['variant_id', $product_damage_data->variant_id],
                        ['warehouse_id', $lims_damage_data->warehouse_id],
                    ])->first();
                } else {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $product_damage_data->product_id],
                        ['warehouse_id', $lims_damage_data->warehouse_id],
                    ])->first();
                }

                // Restore product & warehouse qty
                $lims_product_data->qty           += $product_damage_data->qty;
                if ($lims_product_warehouse_data) {
                    $lims_product_warehouse_data->qty += $product_damage_data->qty;
                    $lims_product_warehouse_data->save();
                }
                $lims_product_data->save();

                // যে product নতুন list এ নেই সেটা delete করো
                if ($product_damage_data->variant_id && !in_array($old_product_variant_id[$key], $product_variant_id)) {
                    $product_damage_data->delete();
                } elseif (!in_array($old_product_id[$key], $product_id)) {
                    $product_damage_data->delete();
                }
            }

            // ── STEP 2: নতুন qty apply করো → stock কমাও ─────────────
            foreach ($product_id as $key => $pro_id) {
                $lims_product_data = Product::find($pro_id);

                if ($lims_product_data->is_variant) {
                    $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')
                        ->FindExactProductWithCode($pro_id, $product_code[$key])
                        ->first();

                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $pro_id],
                        ['variant_id', $lims_product_variant_data->variant_id],
                        ['warehouse_id', $data['warehouse_id']],
                    ])->first();

                    // নতুন damage qty deduct
                    $lims_product_variant_data->qty -= $qty[$key];
                    $lims_product_variant_data->save();

                    $variant_id = $lims_product_variant_data->variant_id;
                } else {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $pro_id],
                        ['warehouse_id', $data['warehouse_id']],
                    ])->first();

                    $variant_id = null;
                }

                // নতুন damage qty warehouse থেকে deduct
                $lims_product_data->qty -= $qty[$key];
                if ($lims_product_warehouse_data) {
                    $lims_product_warehouse_data->qty -= $qty[$key];
                    $lims_product_warehouse_data->save();
                }
                $lims_product_data->save();

                // ── STEP 3: product_damage_stocks update বা create ───
                $product_damage = [
                    'product_id'      => $pro_id,
                    'variant_id'      => $variant_id,
                    'damage_stock_id' => $id,
                    'unit_cost'       => $unit_cost[$key],
                    'qty'             => $qty[$key],
                ];

                if ($variant_id && in_array($product_variant_id[$key] ?? null, $old_product_variant_id)) {
                    // existing variant line update
                    ProductDamageStock::where([
                        ['product_id', $pro_id],
                        ['variant_id', $variant_id],
                        ['damage_stock_id', $id],
                    ])->update($product_damage);

                } elseif ($variant_id === null && in_array($pro_id, $old_product_id)) {
                    // existing non-variant line update
                    ProductDamageStock::where([
                        ['damage_stock_id', $id],
                        ['product_id', $pro_id],
                    ])->update($product_damage);

                } else {
                    // নতুন product line
                    ProductDamageStock::create($product_damage);
                }
            }

            $lims_damage_data->update($data);

            DB::commit();
            return redirect('damage-stock')->with('message', __('db.Data updated successfully'));

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect('damage-stock')->with('not_permitted', 'Error: ' . $e->getMessage());
        }
    }

    public function deleteBySelection(Request $request)
    {
        $damage_id = $request['damageIdArray'];

        foreach ($damage_id as $id) {
            $lims_damage_data = DamageStock::find($id);
            $this->fileDelete(public_path('documents/damage_stock/'), $lims_damage_data->document);

            $lims_product_damage_data = ProductDamageStock::where('damage_stock_id', $id)->get();

            foreach ($lims_product_damage_data as $key => $product_damage_data) {
                $lims_product_data = Product::find($product_damage_data->product_id);

                if ($product_damage_data->variant_id) {
                    $lims_product_variant_data = ProductVariant::select('id', 'qty')
                        ->FindExactProduct($product_damage_data->product_id, $product_damage_data->variant_id)
                        ->first();

                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $product_damage_data->product_id],
                        ['variant_id', $product_damage_data->variant_id],
                        ['warehouse_id', $lims_damage_data->warehouse_id]
                    ])->first();

                    // Restore (was minus, so restore with plus)
                    $lims_product_variant_data->qty += $product_damage_data->qty;
                    $lims_product_variant_data->save();
                } else {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $product_damage_data->product_id],
                        ['warehouse_id', $lims_damage_data->warehouse_id]
                    ])->first();
                }

                // Restore product & warehouse qty
                $lims_product_data->qty           += $product_damage_data->qty;
                $lims_product_warehouse_data->qty += $product_damage_data->qty;

                $lims_product_data->save();
                $lims_product_warehouse_data->save();
                $product_damage_data->delete();
            }

            $lims_damage_data->delete();
        }

        return 'Data deleted successfully';
    }

    public function destroy($id)
    {
        $lims_damage_data         = DamageStock::find($id);
        $lims_product_damage_data = ProductDamageStock::where('damage_stock_id', $id)->get();

        foreach ($lims_product_damage_data as $key => $product_damage_data) {
            $lims_product_data = Product::find($product_damage_data->product_id);

            if ($product_damage_data->variant_id) {
                $lims_product_variant_data = ProductVariant::select('id', 'qty')
                    ->FindExactProduct($product_damage_data->product_id, $product_damage_data->variant_id)
                    ->first();

                $lims_product_warehouse_data = Product_Warehouse::where([
                    ['product_id', $product_damage_data->product_id],
                    ['variant_id', $product_damage_data->variant_id],
                    ['warehouse_id', $lims_damage_data->warehouse_id]
                ])->first();

                // Restore
                $lims_product_variant_data->qty += $product_damage_data->qty;
                $lims_product_variant_data->save();
            } else {
                $lims_product_warehouse_data = Product_Warehouse::where([
                    ['product_id', $product_damage_data->product_id],
                    ['warehouse_id', $lims_damage_data->warehouse_id]
                ])->first();
            }

            // Restore product & warehouse qty
            $lims_product_data->qty           += $product_damage_data->qty;
            $lims_product_warehouse_data->qty += $product_damage_data->qty;

            $lims_product_data->save();
            $lims_product_warehouse_data->save();
            $product_damage_data->delete();
        }

        $lims_damage_data->delete();
        $this->fileDelete(public_path('documents/damage_stock/'), $lims_damage_data->document);

        return redirect('damage-stock')->with('not_permitted', __('db.Data deleted successfully'));
    }
}
