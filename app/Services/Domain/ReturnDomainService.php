<?php

namespace App\Services\Domain;

use App\Models\Returns;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Product_Warehouse;
use App\Models\ProductBatch;
use App\Models\ProductReturn;
use App\Models\Product_Sale;
use App\Models\Payment;
use App\Models\Account;
use App\Models\CashRegister;
use App\Models\Unit;
use App\Models\User;
use App\Models\Variant;
use App\Services\AccountingService;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReturnDomainService
{
    protected InvoiceService $invoiceService;
    protected AccountingService $accountingService;

    public function __construct(?InvoiceService $invoiceService = null, ?AccountingService $accountingService = null)
    {
        $this->invoiceService = $invoiceService ?? app(InvoiceService::class);
        $this->accountingService = $accountingService ?? app(AccountingService::class);
    }

    /**
     * Create a Sale Return transaction, restore stock, issue refund if applicable, and post GL entries.
     *
     * @param array $data
     * @param User|null $user
     * @return Returns
     */
    public function createReturn(array $data, ?User $user = null): Returns
    {
        return DB::transaction(function () use ($data, $user) {
            $userId = $user ? $user->id : (Auth::id() ?? 1);

            $sale = Sale::whereNull('deleted_at')->findOrFail($data['sale_id']);

            $returnReference = $data['reference_no'] ?? $this->invoiceService->generateInvoiceName('rr-');

            $refund = $data['refund'] ?? 0;
            $hasRefund = $refund && $sale->paid_amount > 0;

            $accountId = null;
            if ($hasRefund) {
                $accountId = $data['account_id'] ?? Account::where('is_default', true)->value('id') ?? 1;
            }

            $returnData = [
                'reference_no' => $returnReference,
                'sale_id' => $sale->id,
                'user_id' => $userId,
                'customer_id' => $sale->customer_id,
                'warehouse_id' => $sale->warehouse_id,
                'biller_id' => $sale->biller_id,
                'currency_id' => $sale->currency_id,
                'exchange_rate' => $sale->exchange_rate,
                'account_id' => $accountId,
                'item' => count($data['product_id']),
                'total_qty' => array_sum($data['qty']),
                'total_discount' => $data['total_discount'] ?? 0,
                'total_tax' => $data['total_tax'] ?? 0,
                'total_price' => $data['total_price'] ?? 0,
                'order_tax_rate' => $data['order_tax_rate'] ?? 0,
                'order_tax' => $data['order_tax'] ?? 0,
                'grand_total' => $data['grand_total'],
                'return_note' => $data['return_note'] ?? null,
                'staff_note' => $data['staff_note'] ?? null,
                'created_at' => isset($data['created_at']) ? normalize_to_sql_datetime($data['created_at']) : date('Y-m-d H:i:s'),
            ];

            $returnObj = Returns::create($returnData);

            // Refund payment logic if applicable
            if ($hasRefund) {
                $cashRegister = CashRegister::where([
                    ['user_id', $userId],
                    ['warehouse_id', $sale->warehouse_id],
                    ['status', true]
                ])->first();

                $refundAmount = $data['refund_amount'] ?? $data['grand_total'];
                $payingMethod = $data['paying_method'] ?? 'Cash';
                $paymentRef = $this->invoiceService->generateInvoiceName('spr-');

                $paymentData = [
                    'payment_reference' => $paymentRef,
                    'sale_id' => $sale->id,
                    'return_id' => $returnObj->id,
                    'cash_register_id' => $cashRegister ? $cashRegister->id : null,
                    'user_id' => $userId,
                    'account_id' => $accountId,
                    'amount' => $refundAmount,
                    'paying_method' => $payingMethod,
                    'created_at' => $returnObj->created_at,
                    'updated_at' => now(),
                ];

                $refundPayment = Payment::create($paymentData);
                $resPayment = $this->accountingService->recordPayment($refundPayment);
                if (!$resPayment->success) {
                    \Log::error('Accounting failed for Sale Return Refund', ['payment_id' => $refundPayment->id, 'error' => $resPayment->error]);
                }
            }

            // Restore product stock and record ProductReturn
            foreach ($data['product_id'] as $key => $proId) {
                $product = Product::findOrFail($proId);
                $saleUnitName = $data['sale_unit'][$key] ?? 'n/a';
                $variantId = null;
                $quantity = (float) $data['qty'][$key];

                if ($saleUnitName !== 'n/a') {
                    $saleUnit = Unit::where('unit_name', $saleUnitName)->first();
                    if ($saleUnit) {
                        if ($saleUnit->operator == '*') {
                            $quantity = $quantity * $saleUnit->operation_value;
                        } elseif ($saleUnit->operator == '/') {
                            $quantity = $quantity / $saleUnit->operation_value;
                        }
                    }

                    if ($product->is_variant) {
                        $productCode = $data['product_code'][$key] ?? '';
                        $productVariant = ProductVariant::select('id', 'variant_id', 'qty')
                            ->FindExactProductWithCode($proId, $productCode)
                            ->first();

                        if ($productVariant) {
                            $variantId = $productVariant->variant_id;
                            $productWarehouse = Product_Warehouse::FindProductWithVariant($proId, $variantId, $sale->warehouse_id)->first();
                            $productVariant->qty += $quantity;
                            $productVariant->save();
                        } else {
                            $productWarehouse = Product_Warehouse::FindProductWithoutVariant($proId, $sale->warehouse_id)->first();
                        }
                    } elseif (!empty($data['product_batch_id'][$key])) {
                        $batchId = $data['product_batch_id'][$key];
                        $productWarehouse = Product_Warehouse::where([
                            ['product_batch_id', $batchId],
                            ['warehouse_id', $sale->warehouse_id]
                        ])->first();
                        $productBatch = ProductBatch::find($batchId);
                        if ($productBatch) {
                            $productBatch->qty += $quantity;
                            $productBatch->save();
                        }
                    } else {
                        $productWarehouse = Product_Warehouse::FindProductWithoutVariant($proId, $sale->warehouse_id)->first();
                    }

                    if (!$productWarehouse) {
                        $productWarehouse = Product_Warehouse::create([
                            'product_id' => $proId,
                            'warehouse_id' => $sale->warehouse_id,
                            'qty' => 0,
                        ]);
                    }

                    $product->qty += $quantity;
                    $productWarehouse->qty += $quantity;
                    $product->save();
                    $productWarehouse->save();
                } elseif ($product->type === 'combo') {
                    // Combo product child stock restoration
                    $productList = explode(',', $product->product_list);
                    $variantList = !empty($product->variant_list) ? explode(',', $product->variant_list) : [];
                    $qtyList = explode(',', $product->qty_list);

                    foreach ($productList as $idx => $childId) {
                        $childProduct = Product::find($childId);
                        if (!$childProduct) continue;

                        $req = (float) $qtyList[$idx];
                        $returnQty = $data['qty'][$key] * $req;

                        if (count($variantList) && !empty($variantList[$idx])) {
                            $childPV = ProductVariant::where([
                                ['product_id', $childId],
                                ['variant_id', $variantList[$idx]]
                            ])->first();
                            $childPW = Product_Warehouse::where([
                                ['product_id', $childId],
                                ['variant_id', $variantList[$idx]],
                                ['warehouse_id', $sale->warehouse_id]
                            ])->first();

                            if ($childPV) {
                                $childPV->qty += $returnQty;
                                $childPV->save();
                            }
                        } else {
                            $childPW = Product_Warehouse::where([
                                ['product_id', $childId],
                                ['warehouse_id', $sale->warehouse_id]
                            ])->first();
                        }

                        $childProduct->qty += $returnQty;
                        if ($childPW) {
                            $childPW->qty += $returnQty;
                            $childPW->save();
                        }
                        $childProduct->save();
                    }
                }

                ProductReturn::create([
                    'return_id' => $returnObj->id,
                    'product_id' => $proId,
                    'product_batch_id' => $data['product_batch_id'][$key] ?? null,
                    'variant_id' => $variantId,
                    'imei_number' => $data['imei_number'][$key] ?? null,
                    'qty' => $data['qty'][$key],
                    'sale_unit_id' => isset($saleUnit) ? $saleUnit->id : 0,
                    'net_unit_price' => $data['net_unit_price'][$key],
                    'discount' => $data['discount'][$key] ?? 0,
                    'tax_rate' => $data['tax_rate'][$key] ?? 0,
                    'tax' => $data['tax'][$key] ?? 0,
                    'total' => $data['subtotal'][$key],
                ]);

                $productSale = Product_Sale::where([
                    ['product_id', $proId],
                    ['sale_id', $sale->id]
                ])->first();

                if ($productSale) {
                    $productSale->return_qty += $data['qty'][$key];
                    $productSale->save();
                }
            }

            if (!empty($data['change_sale_status'])) {
                $sale->sale_status = 4; // Returned
                $sale->save();
            }

            // Post Accounting GL Entries for Sale Return
            $resReturn = $this->accountingService->recordSaleReturn($returnObj, 'sale_return_created');
            if (!$resReturn->success) {
                \Log::error('Accounting failed for Sale Return', ['return_id' => $returnObj->id, 'error' => $resReturn->error]);
            }

            return $returnObj;
        });
    }
}
