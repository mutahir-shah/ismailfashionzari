<?php

namespace App\Services\Domain;

use App\Models\SaleExchange;
use App\Models\ProductExchange;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Product_Sale;
use App\Models\Product_Warehouse;
use App\Models\ProductVariant;
use App\Models\ProductBatch;
use App\Models\Unit;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ExchangeDomainService
{
    protected InvoiceService $invoiceService;
    protected AccountingService $accountingService;

    public function __construct(?InvoiceService $invoiceService = null, ?AccountingService $accountingService = null)
    {
        $this->invoiceService = $invoiceService ?? app(InvoiceService::class);
        $this->accountingService = $accountingService ?? app(AccountingService::class);
    }

    /**
     * Create a product exchange transaction, update stock, adjust sale grand total & paid amount, post accounting entries.
     *
     * @param array $data
     * @param User|null $user
     * @return SaleExchange
     */
    public function createExchange(array $data, ?User $user = null): SaleExchange
    {
        return DB::transaction(function () use ($data, $user) {
            $userId = $user ? $user->id : (Auth::id() ?? 1);

            $referenceNo = $data['reference_no'] ?? $this->invoiceService->generateInvoiceName('exc-');

            $saleObj = null;
            if (!empty($data['sale_id']) && is_numeric($data['sale_id'])) {
                $saleObj = Sale::whereNull('deleted_at')
                    ->select('id', 'warehouse_id', 'customer_id', 'biller_id', 'grand_total', 'paid_amount', 'payment_status', 'exchange_rate', 'reference_no')
                    ->find($data['sale_id']);
            }

            $exchangeData = [
                'reference_no' => $referenceNo,
                'sale_id' => $saleObj ? $saleObj->id : 0,
                'customer_id' => $data['customer_id'],
                'warehouse_id' => $data['warehouse_id'],
                'biller_id' => $data['biller_id'] ?? 1,
                'user_id' => $userId,
                'item' => count($data['product_id'] ?? []),
                'total_qty' => array_sum($data['qty'] ?? []),
                'total_discount' => $data['total_discount'] ?? 0.00,
                'total_tax' => $data['total_tax'] ?? 0.00,
                'amount' => $data['amount'] ?? 0.00,
                'payment_type' => $data['payment_type'] ?? null,
                'order_tax_rate' => $data['order_tax_rate'] ?? 0.00,
                'order_tax' => $data['order_tax'] ?? 0.00,
                'grand_total' => $data['grand_total'] ?? 0.00,
                'exchange_note' => $data['exchange_note'] ?? null,
                'staff_note' => $data['staff_note'] ?? null,
                'created_at' => isset($data['created_at']) ? normalize_to_sql_datetime($data['created_at']) : date('Y-m-d H:i:s'),
            ];

            $exchangeObj = SaleExchange::create($exchangeData);

            $typeArray = $data['type'] ?? [];
            $productIdList = $data['product_id'] ?? [];
            $productBatchIdList = $data['product_batch_id'] ?? [];
            $imeiList = $data['imei_number'] ?? [];
            $productCodeList = $data['product_code'] ?? [];
            $qtyList = $data['qty'] ?? [];
            $saleUnitList = $data['sale_unit'] ?? [];
            $netUnitPriceList = $data['net_unit_price'] ?? [];
            $discountList = $data['discount'] ?? [];
            $taxRateList = $data['tax_rate'] ?? [];
            $taxList = $data['tax'] ?? [];
            $totalList = $data['subtotal'] ?? [];
            $productSaleIdList = $data['product_sale_id'] ?? [];
            $isExchangeList = $data['is_exchange'] ?? [];

            foreach ($productIdList as $index => $proId) {
                $prodType = $typeArray[$index] ?? 'new';

                if ($prodType === 'return') {
                    $prodCodeVal = $productCodeList[$index] ?? null;
                    $shouldReturn = $prodCodeVal && (empty($isExchangeList) || in_array($prodCodeVal, $isExchangeList));

                    if ($shouldReturn) {
                        $origSaleId = $productSaleIdList[$index] ?? null;
                        $origProductSale = $origSaleId ? Product_Sale::find($origSaleId) : null;

                        $this->processReturnProduct(
                            $proId,
                            $index,
                            $exchangeObj->id,
                            $data['warehouse_id'],
                            $qtyList,
                            $saleUnitList,
                            $netUnitPriceList,
                            $discountList,
                            $taxRateList,
                            $taxList,
                            $totalList,
                            $productCodeList,
                            $productBatchIdList,
                            $imeiList,
                            $origProductSale
                        );
                    }
                } elseif ($prodType === 'new') {
                    $this->processNewProduct(
                        $proId,
                        $index,
                        $exchangeObj->id,
                        $data['warehouse_id'],
                        $qtyList,
                        $saleUnitList,
                        $netUnitPriceList,
                        $discountList,
                        $taxRateList,
                        $taxList,
                        $totalList,
                        $productCodeList,
                        $productBatchIdList,
                        $imeiList
                    );
                }
            }

            $returnedTotal = $exchangeObj->products()->where('type', 'returned')->sum('total');
            $newTotal = $exchangeObj->products()->where('type', 'new')->sum('total');

            if ($saleObj) {
                $previousGrandTotal = (float) $saleObj->grand_total;
                $previousPaidAmount = (float) $saleObj->paid_amount;
                $adjustedGrandTotal = max(0, $previousGrandTotal - $returnedTotal + $newTotal);
                $paidAmount = $previousPaidAmount;

                if ($exchangeObj->payment_type === 'receive' && $exchangeObj->amount > 0) {
                    $paidAmount += (float) $exchangeObj->amount;
                } elseif ($exchangeObj->payment_type === 'pay' && $exchangeObj->amount > 0) {
                    $overpaidAfterExchange = max(0, $previousPaidAmount - $adjustedGrandTotal);
                    $refundAmount = min((float) $exchangeObj->amount, $overpaidAfterExchange);
                    $paidAmount -= $refundAmount;

                    if (bccomp((string) $refundAmount, (string) $exchangeObj->amount, 4) !== 0) {
                        $exchangeObj->amount = $refundAmount;
                        if ($refundAmount <= 0) {
                            $exchangeObj->payment_type = null;
                        }
                        $exchangeObj->save();
                    }
                }

                $saleObj->grand_total = $adjustedGrandTotal;
                $saleObj->paid_amount = max(0, $paidAmount);
                $balance = $saleObj->grand_total - $saleObj->paid_amount;
                $saleObj->payment_status = abs($balance) < 0.01 ? 4 : 2;
                $saleObj->save();
            }

            $accountingResult = $this->accountingService->recordSaleExchange($exchangeObj);
            if (!$accountingResult->success) {
                throw new \App\Exceptions\AccountingException($accountingResult->error);
            }

            return $exchangeObj;
        });
    }

    private function processNewProduct(
        $product_id,
        $index,
        $exchange_id,
        $warehouse_id,
        $qty,
        $sale_unit,
        $net_unit_price,
        $discount,
        $tax_rate,
        $tax,
        $total,
        $product_code,
        $product_batch_id,
        $imei_number
    ) {
        $product = Product::find($product_id);
        if (!$product) {
            throw new \Exception("Product not found: {$product_id}");
        }

        $saleUnitId = 0;
        $quantity = floatval($qty[$index] ?? 0);

        if (!empty($sale_unit[$index]) && $sale_unit[$index] != 'n/a') {
            $unitObj = Unit::where('unit_name', $sale_unit[$index])->first();
            if ($unitObj) {
                $saleUnitId = $unitObj->id;
                if ($unitObj->operator == '*') {
                    $quantity = floatval($qty[$index]) * floatval($unitObj->operation_value);
                } elseif ($unitObj->operator == '/') {
                    $quantity = floatval($qty[$index]) / floatval($unitObj->operation_value);
                }
            }
        }

        $productWarehouse = null;
        $variantId = null;
        $tracksStock = !in_array($product->type, ['service', 'digital'], true);

        if (!$tracksStock) {
            // Non-stock products participate financially in an exchange, but must
            // not create physical product or warehouse inventory movements.
        } elseif ($product->is_variant) {
            $product->qty -= $quantity;
            $product->save();

            $pv = ProductVariant::select('id', 'variant_id', 'qty')
                ->FindExactProductWithCode($product_id, $product_code[$index] ?? '')
                ->first();

            if ($pv) {
                $variantId = $pv->variant_id;
                $pv->qty -= $quantity;
                $pv->save();
                $productWarehouse = Product_Warehouse::FindProductWithVariant(
                    $product_id,
                    $pv->variant_id,
                    $warehouse_id
                )->first();
            }
        } else {
            $product->qty -= $quantity;
            $product->save();

            $productWarehouse = Product_Warehouse::FindProductWithoutVariant(
                $product_id,
                $warehouse_id
            )->first();
        }

        if ($productWarehouse) {
            $productWarehouse->qty -= $quantity;
            $productWarehouse->save();
        }

        ProductExchange::create([
            'exchange_id' => $exchange_id,
            'product_id' => $product_id,
            'variant_id' => $variantId,
            'qty' => $quantity,
            'sale_unit_id' => $saleUnitId,
            'net_unit_price' => (float) ($net_unit_price[$index] ?? 0),
            'discount' => (float) ($discount[$index] ?? 0),
            'tax_rate' => (float) ($tax_rate[$index] ?? 0),
            'tax' => (float) ($tax[$index] ?? 0),
            'total' => (float) ($total[$index] ?? 0),
            'type' => 'new',
        ]);
    }

    private function processReturnProduct(
        $product_id,
        $index,
        $exchange_id,
        $warehouse_id,
        $qty,
        $sale_unit,
        $net_unit_price,
        $discount,
        $tax_rate,
        $tax,
        $total,
        $product_code,
        $product_batch_id,
        $imei_number,
        $original_product_sale = null
    ) {
        $product = Product::find($product_id);
        if (!$product) {
            throw new \Exception("Product not found: {$product_id}");
        }

        $saleUnitId = 0;
        $quantity = floatval($qty[$index] ?? 0);

        if (!empty($sale_unit[$index]) && $sale_unit[$index] != 'n/a') {
            $unitObj = Unit::where('unit_name', $sale_unit[$index])->first();
            if ($unitObj) {
                $saleUnitId = $unitObj->id;
                if ($unitObj->operator == '*') {
                    $quantity = floatval($qty[$index]) * floatval($unitObj->operation_value);
                } elseif ($unitObj->operator == '/') {
                    $quantity = floatval($qty[$index]) / floatval($unitObj->operation_value);
                }
            }
        }

        $productWarehouse = null;
        $variantId = $original_product_sale->variant_id ?? null;
        $tracksStock = !in_array($product->type, ['service', 'digital'], true);

        if (!$tracksStock) {
            // See processNewProduct(): digital/service exchanges have no stock.
        } elseif ($product->is_variant && $variantId) {
            $product->qty += $quantity;
            $product->save();

            $pv = ProductVariant::where([
                ['product_id', $product_id],
                ['variant_id', $variantId],
            ])->first();
            if ($pv) {
                $pv->qty += $quantity;
                $pv->save();
            }
            $productWarehouse = Product_Warehouse::FindProductWithVariant(
                $product_id,
                $variantId,
                $warehouse_id
            )->first();
        } else {
            $product->qty += $quantity;
            $product->save();

            $productWarehouse = Product_Warehouse::FindProductWithoutVariant(
                $product_id,
                $warehouse_id
            )->first();
        }

        if ($productWarehouse) {
            $productWarehouse->qty += $quantity;
            $productWarehouse->save();
        }

        ProductExchange::create([
            'exchange_id' => $exchange_id,
            'product_id' => $product_id,
            'variant_id' => $variantId,
            'qty' => $quantity,
            'sale_unit_id' => $saleUnitId,
            'net_unit_price' => (float) ($net_unit_price[$index] ?? 0),
            'discount' => (float) ($discount[$index] ?? 0),
            'tax_rate' => (float) ($tax_rate[$index] ?? 0),
            'tax' => (float) ($tax[$index] ?? 0),
            'total' => (float) ($total[$index] ?? 0),
            'type' => 'returned',
        ]);
    }
}
