<?php

namespace App\Services\Domain;

use App\Models\Adjustment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Product_Warehouse;
use App\Models\ProductAdjustment;
use App\Models\StockCount;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdjustmentDomainService
{
    protected InvoiceService $invoiceService;

    public function __construct(?InvoiceService $invoiceService = null)
    {
        $this->invoiceService = $invoiceService ?? app(InvoiceService::class);
    }

    /**
     * Create a stock adjustment transaction, mutate product/warehouse/variant stock.
     *
     * @param array $data
     * @param User|null $user
     * @return Adjustment
     */
    public function createAdjustment(array $data, ?User $user = null): Adjustment
    {
        return DB::transaction(function () use ($data, $user) {
            $userId = $user ? $user->id : (Auth::id() ?? 1);

            if (isset($data['stock_count_id'])) {
                $stockCount = StockCount::find($data['stock_count_id']);
                if ($stockCount) {
                    $stockCount->is_adjusted = true;
                    $stockCount->save();
                }
            }

            $referenceNo = $data['reference_no'] ?? $this->invoiceService->generateInvoiceName('adr-');

            $adjData = [
                'reference_no' => $referenceNo,
                'warehouse_id' => $data['warehouse_id'],
                'user_id' => $userId,
                'item' => count($data['product_id']),
                'total_qty' => array_sum($data['qty']),
                'note' => $data['note'] ?? null,
                'created_at' => isset($data['created_at']) ? normalize_to_sql_datetime($data['created_at']) : date('Y-m-d H:i:s'),
            ];

            $adjustmentObj = Adjustment::create($adjData);

            $productIdList = $data['product_id'];
            $productCodeList = $data['product_code'];
            $qtyList = $data['qty'];
            $actionList = $data['action'];
            $unitCostList = $data['unit_cost'] ?? [];

            foreach ($productIdList as $key => $proId) {
                $product = Product::findOrFail($proId);
                $variantId = null;
                $action = $actionList[$key];
                $adjustQty = (float) $qtyList[$key];

                if ($product->is_variant) {
                    $productVariant = ProductVariant::select('id', 'variant_id', 'qty')
                        ->FindExactProductWithCode($proId, $productCodeList[$key])
                        ->first();

                    if ($productVariant) {
                        $variantId = $productVariant->variant_id;
                        $productWarehouse = Product_Warehouse::where([
                            ['product_id', $proId],
                            ['variant_id', $variantId],
                            ['warehouse_id', $data['warehouse_id']],
                        ])->first();

                        if ($action === '-') {
                            $productVariant->qty -= $adjustQty;
                        } elseif ($action === '+') {
                            $productVariant->qty += $adjustQty;
                        }
                        $productVariant->save();
                    } else {
                        $productWarehouse = Product_Warehouse::where([
                            ['product_id', $proId],
                            ['warehouse_id', $data['warehouse_id']],
                        ])->first();
                    }
                } else {
                    $productWarehouse = Product_Warehouse::where([
                        ['product_id', $proId],
                        ['warehouse_id', $data['warehouse_id']],
                    ])->first();
                }

                if (!$productWarehouse) {
                    $productWarehouse = Product_Warehouse::create([
                        'product_id' => $proId,
                        'warehouse_id' => $data['warehouse_id'],
                        'variant_id' => $variantId,
                        'qty' => 0,
                    ]);
                }

                if ($action === '-') {
                    $product->qty -= $adjustQty;
                    $productWarehouse->qty -= $adjustQty;
                } elseif ($action === '+') {
                    $product->qty += $adjustQty;
                    $productWarehouse->qty += $adjustQty;
                }

                $product->save();
                $productWarehouse->save();

                ProductAdjustment::create([
                    'product_id' => $proId,
                    'variant_id' => $variantId,
                    'adjustment_id' => $adjustmentObj->id,
                    'qty' => $adjustQty,
                    'unit_cost' => $unitCostList[$key] ?? $product->cost,
                    'action' => $action,
                ]);
            }

            return $adjustmentObj;
        });
    }
}
