<?php

namespace App\Services\Domain;

use App\Models\Account;
use App\Models\CashRegister;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductPurchase;
use App\Models\ProductVariant;
use App\Models\Product_Warehouse;
use App\Models\Purchase;
use App\Models\PurchaseProductReturn;
use App\Models\ReturnPurchase;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Variant;
use App\Services\AccountingService;
use App\Services\InvoiceService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class ReturnPurchaseDomainService
{
    public function __construct(
        private AccountingService $accountingService,
        private InvoiceService $invoiceService
    ) {}

    /**
     * Create a canonical Purchase Return transaction.
     *
     * @param array $data
     * @param User|null $user
     * @return ReturnPurchase
     */
    public function createPurchaseReturn(array $data, ?User $user = null): ReturnPurchase
    {
        return DB::transaction(function () use ($data, $user) {
            $user = $user ?? Auth::user() ?? User::where('role_id', 1)->first();
            $userId = $user ? $user->id : 1;

            $lims_purchase_data = Purchase::findOrFail($data['purchase_id']);

            if (empty($data['reference_no'])) {
                $data['reference_no'] = $this->invoiceService->generateInvoiceName('prr-');
            }

            $data['user_id'] = $userId;
            $data['supplier_id'] = $lims_purchase_data->supplier_id;
            $data['warehouse_id'] = $lims_purchase_data->warehouse_id;
            $data['currency_id'] = $lims_purchase_data->currency_id;
            $data['exchange_rate'] = $lims_purchase_data->exchange_rate ?? 1;

            $refundAmount = (float) ($data['refund_amount'] ?? 0);
            $hasRefund = $lims_purchase_data->paid_amount > 0 && $refundAmount > 0;

            if ($hasRefund) {
                if (empty($data['account_id'])) {
                    $lims_account_data = Account::where('is_default', true)->first();
                    $data['account_id'] = $lims_account_data?->id;
                }
            } else {
                $data['account_id'] = null;
            }

            if (!isset($data['total_cost'])) {
                $data['total_cost'] = $data['grand_total'] ?? 0;
            }

            $lims_return_data = ReturnPurchase::create($data);


            if ($hasRefund) {
                $cash_register_data = CashRegister::where([
                    ['user_id', $userId],
                    ['warehouse_id', $data['warehouse_id']],
                    ['status', true]
                ])->first();

                if ($cash_register_data) {
                    $data['cash_register_id'] = $cash_register_data->id;
                }

                $paying_method = $data['paying_method'] ?? 'Cash';
                $payment_reference = $this->invoiceService->generateInvoiceName('sppr-');

                $payment_data = [
                    'payment_reference' => $payment_reference,
                    'purchase_id' => $lims_purchase_data->id,
                    'purchase_return_id' => $lims_return_data->id,
                    'cash_register_id' => $data['cash_register_id'] ?? null,
                    'user_id' => $userId,
                    'account_id' => $data['account_id'],
                    'amount' => $refundAmount,
                    'paying_method' => $paying_method,
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $refundPayment = Payment::create($payment_data);
                $result = $this->accountingService->recordPayment($refundPayment);
                if (!$result->success) {
                    \Log::error('Accounting failed for Purchase Return Refund', ['payment_id' => $refundPayment->id, 'error' => $result->error]);
                }
            }

            $product_purchase_ids = $data['is_return'] ?? [];
            $imei_number = $data['imei_number'] ?? [];
            $product_batch_id = $data['product_batch_id'] ?? [];
            $product_code = $data['product_code'] ?? [];
            $qty = $data['qty'] ?? [];
            $purchase_unit = $data['purchase_unit'] ?? [];
            $net_unit_cost = $data['net_unit_cost'] ?? [];
            $discount = $data['discount'] ?? [];
            $tax_rate = $data['tax_rate'] ?? [];
            $tax = $data['tax'] ?? [];
            $total = $data['subtotal'] ?? [];

            foreach ($product_purchase_ids as $product_purchase_id) {
                $key = array_search($product_purchase_id, $data['product_purchase_id']);
                if ($key === false) continue;

                $pro_id = $data['product_id'][$key];
                $lims_product_data = Product::find($pro_id);
                $variant_id = null;

                if (isset($purchase_unit[$key]) && $purchase_unit[$key] != 'n/a') {
                    $lims_purchase_unit_data = Unit::where('unit_name', $purchase_unit[$key])->first();
                    $purchase_unit_id = $lims_purchase_unit_data ? $lims_purchase_unit_data->id : 0;
                    $operator = $lims_purchase_unit_data ? $lims_purchase_unit_data->operator : '*';
                    $opValue = $lims_purchase_unit_data ? $lims_purchase_unit_data->operation_value : 1;

                    if ($operator == '*') {
                        $quantity = $qty[$key] * $opValue;
                    } elseif ($operator == '/') {
                        $quantity = $qty[$key] / $opValue;
                    } else {
                        $quantity = $qty[$key];
                    }

                    if ($lims_product_data->is_variant) {
                        $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')
                            ->FindExactProductWithCode($pro_id, $product_code[$key])
                            ->first();
                        if ($lims_product_variant_data) {
                            $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($pro_id, $lims_product_variant_data->variant_id, $data['warehouse_id'])->first();
                            $lims_product_variant_data->qty -= $quantity;
                            $lims_product_variant_data->save();
                            $variant_id = $lims_product_variant_data->variant_id;
                        } else {
                            $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($pro_id, $data['warehouse_id'])->first();
                        }
                    } elseif (!empty($product_batch_id[$key])) {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_batch_id', $product_batch_id[$key]],
                            ['warehouse_id', $data['warehouse_id']]
                        ])->first();
                        $lims_product_batch_data = ProductBatch::find($product_batch_id[$key]);
                        if ($lims_product_batch_data) {
                            $lims_product_batch_data->qty -= $quantity;
                            $lims_product_batch_data->save();
                        }
                    } else {
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($pro_id, $data['warehouse_id'])->first();
                    }

                    $lims_product_data->qty -= $quantity;
                    if ($lims_product_warehouse_data) {
                        $lims_product_warehouse_data->qty -= $quantity;
                        $lims_product_warehouse_data->save();
                    }
                    $lims_product_data->save();
                } else {
                    $purchase_unit_id = 0;
                }

                PurchaseProductReturn::create([
                    'return_id' => $lims_return_data->id,
                    'product_id' => $pro_id,
                    'product_batch_id' => $product_batch_id[$key] ?? null,
                    'variant_id' => $variant_id,
                    'imei_number' => $imei_number[$key] ?? null,
                    'qty' => $qty[$key],
                    'purchase_unit_id' => $purchase_unit_id,
                    'net_unit_cost' => $net_unit_cost[$key] ?? 0,
                    'discount' => $discount[$key] ?? 0,
                    'tax_rate' => $tax_rate[$key] ?? 0,
                    'tax' => $tax[$key] ?? 0,
                    'total' => $total[$key] ?? 0,
                ]);

                $product_purchase_data = ProductPurchase::where([
                    ['product_id', $pro_id],
                    ['purchase_id', $data['purchase_id']]
                ])->first();

                if ($product_purchase_data) {
                    $product_purchase_data->return_qty += $qty[$key];
                    $product_purchase_data->save();
                }
            }

            $res = $this->accountingService->recordPurchaseReturn($lims_return_data, 'purchase_return_created');
            if (!$res->success) {
                \Log::error('Accounting error on Purchase Return Store: ' . $res->error);
            }
            if ($res->isPosted() && \Schema::hasColumn($lims_return_data->getTable(), 'accounting_status')) {
                $lims_return_data->accounting_status = 'posted';
                $lims_return_data->save();
            }

            return $lims_return_data;
        });
    }
}
