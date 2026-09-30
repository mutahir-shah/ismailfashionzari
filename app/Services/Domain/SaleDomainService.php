<?php

namespace App\Services\Domain;

use App\Exceptions\SaleValidationException;
use App\Models\Sale;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Product_Sale;
use App\Models\Product_Warehouse;
use App\Models\ProductBatch;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Account;
use App\Models\CashRegister;
use App\Models\Unit;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\CustomerCreditService;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaleDomainService
{
    protected InvoiceService $invoiceService;
    protected AccountingService $accountingService;
    protected CustomerCreditService $creditService;

    public function __construct(
        ?InvoiceService $invoiceService = null,
        ?AccountingService $accountingService = null,
        ?CustomerCreditService $creditService = null
    ) {
        $this->invoiceService = $invoiceService ?? app(InvoiceService::class);
        $this->accountingService = $accountingService ?? app(AccountingService::class);
        $this->creditService = $creditService ?? app(CustomerCreditService::class);
    }

    /**
     * Create a Sale transaction, update stock levels, handle payments, and record GL accounting entries.
     *
     * @param array $data
     * @param User|null $user
     * @return Sale
     */
    public function createSale(array $data, ?User $user = null): Sale
    {
        $user = $user ?? auth()->user();
        if ($user) {
            $data['user_id'] = $user->id;
        }

        if (isset($data['created_at'])) {
            $data['created_at'] = normalize_to_sql_datetime($data['created_at']);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        // 1. Reference Generation
        if (!isset($data['reference_no']) || empty($data['reference_no'])) {
            if (isset($data['pos']) && $data['pos']) {
                $data['reference_no'] = $this->invoiceService->generateInvoiceName('posr-', true);
            } else {
                $data['reference_no'] = $this->invoiceService->generateInvoiceName('sr-', true);
            }
        }

        // 2. Active Cash Register
        $cashRegister = CashRegister::where([
            ['user_id', $data['user_id']],
            ['warehouse_id', $data['warehouse_id']],
            ['status', true]
        ])->first() ?? CashRegister::where('status', true)->first();

        if ($cashRegister) {
            $data['cash_register_id'] = $cashRegister->id;
        }

        // 3. Normalize Paid Amount & Payment Status
        $paidAmountArray = $data['paid_amount'] ?? 0;
        $totalPaidAmount = is_array($paidAmountArray) ? array_sum($paidAmountArray) : (float)$paidAmountArray;
        $data['paid_amount'] = $totalPaidAmount;

        $grandTotal = (float)($data['grand_total'] ?? 0);
        $balance = $grandTotal - $totalPaidAmount;

        if (!isset($data['payment_status'])) {
            if ($totalPaidAmount <= 0) {
                $data['payment_status'] = 1; // Pending/Unpaid
            } elseif ($balance > 0.001) {
                $data['payment_status'] = 2; // Due/Partial
            } else {
                $data['payment_status'] = 4; // Paid
            }
        }

        if (!isset($data['sale_status'])) {
            $data['sale_status'] = 1; // Completed
        }

        if (empty($data['currency_id'])) {
            $data['currency_id'] = data_get(cache()->get('general_setting'), 'currency', 1) ?: 1;
        }

        if (!array_key_exists('exchange_rate', $data) || $data['exchange_rate'] === null || $data['exchange_rate'] === '') {
            $data['exchange_rate'] = 1;
        } elseif ((float) $data['exchange_rate'] <= 0) {
            throw new RuntimeException('Sale exchange rate must be greater than zero.');
        }

        if (!isset($data['item']) && isset($data['product_id'])) {
            $data['item'] = count($data['product_id']);
        }
        if (!isset($data['total_qty']) && isset($data['qty'])) {
            $data['total_qty'] = array_sum($data['qty']);
        }
        if (!isset($data['total_price']) && isset($data['subtotal'])) {
            $data['total_price'] = array_sum($data['subtotal']);
        }
        $data['total_discount'] = $data['total_discount'] ?? 0;
        $data['total_tax'] = $data['total_tax'] ?? 0;
        $data['order_tax_rate'] = $data['order_tax_rate'] ?? 0;
        $data['order_tax'] = $data['order_tax'] ?? 0;
        $data['order_discount'] = $data['order_discount'] ?? 0;
        $data['shipping_cost'] = $data['shipping_cost'] ?? 0;

        return DB::transaction(function () use ($data, $user, $paidAmountArray, $cashRegister, $totalPaidAmount) {

            // Validate Credit Limit
            $isDraft = (isset($data['sale_status']) && $data['sale_status'] == 3);
            $validation = $this->creditService->validateCreditLimit(
                $data['customer_id'],
                floatval($data['grand_total']),
                $totalPaidAmount,
                null,
                $isDraft
            );

            if (!$validation['allowed']) {
                throw new SaleValidationException($validation['message']);
            }

            // Create Sale record
            $sale = Sale::create($data);

            $productIdArray = $data['product_id'] ?? [];
            $productCodeArray = $data['product_code'] ?? [];
            $qtyArray = $data['qty'] ?? [];
            $saleUnitArray = $data['sale_unit'] ?? [];
            $netUnitPriceArray = $data['net_unit_price'] ?? [];
            $discountArray = $data['discount'] ?? [];
            $taxRateArray = $data['tax_rate'] ?? [];
            $taxArray = $data['tax'] ?? [];
            $subtotalArray = $data['subtotal'] ?? [];
            $imeiNumberArray = $data['imei_number'] ?? [];
            $batchIdArray = $data['product_batch_id'] ?? [];

            foreach ($productIdArray as $i => $id) {
                $product = Product::find($id);
                if (!$product) {
                    continue;
                }

                $productSale = [
                    'sale_id' => $sale->id,
                    'product_id' => $id,
                    'product_batch_id' => $batchIdArray[$i] ?? null,
                    'variant_id' => null,
                    'qty' => $qtyArray[$i],
                    'sale_unit_id' => 0,
                    'net_unit_price' => $netUnitPriceArray[$i],
                    'discount' => $discountArray[$i] ?? 0,
                    'tax_rate' => $taxRateArray[$i] ?? 0,
                    'tax' => $taxArray[$i] ?? 0,
                    'total' => $subtotalArray[$i],
                    'imei_number' => $imeiNumberArray[$i] ?? null,
                ];

                $requestedQty = (float)$qtyArray[$i];
                $unitName = $saleUnitArray[$i] ?? 'n/a';

                // Handle Combo Products Stock Deduction
                if ($product->type == 'combo' && in_array((int)$sale->sale_status, [1, 5], true)) {
                    $productList = explode(',', $product->product_list);
                    $variantList = $product->variant_list ? explode(',', $product->variant_list) : [];
                    $qtyList = explode(',', $product->qty_list);

                    foreach ($productList as $key => $childId) {
                        $childProduct = Product::find($childId);
                        if (!$childProduct) {
                            continue;
                        }

                        $requiredQty = (float)($qtyList[$key] ?? 1);
                        $deductQty = $requestedQty * $requiredQty;

                        if (!empty($variantList) && isset($variantList[$key]) && $variantList[$key]) {
                            $pv = ProductVariant::where('product_id', $childId)->where('variant_id', $variantList[$key])->first();
                            if ($pv) {
                                $pv->qty -= $deductQty;
                                $pv->save();
                            }
                            $pw = Product_Warehouse::where('product_id', $childId)->where('variant_id', $variantList[$key])->where('warehouse_id', $sale->warehouse_id)->first();
                        } else {
                            $pw = Product_Warehouse::where('product_id', $childId)->where('warehouse_id', $sale->warehouse_id)->first();
                        }

                        $childProduct->qty -= $deductQty;
                        $childProduct->save();

                        if ($pw) {
                            $pw->qty -= $deductQty;
                            $pw->save();
                        }
                    }
                }

                // Handle Standard and Variant Stock Deduction (Excluding Service and Digital Products)
                if ($unitName != 'n/a' && !in_array($product->type, ['combo', 'digital', 'service'])) {
                    $saleUnit = Unit::where('unit_name', $unitName)->first();
                    if ($saleUnit) {
                        $productSale['sale_unit_id'] = $saleUnit->id;
                        if ($saleUnit->operator == '*') {
                            $deductQty = $requestedQty * $saleUnit->operation_value;
                        } elseif ($saleUnit->operator == '/') {
                            $deductQty = $requestedQty / $saleUnit->operation_value;
                        } else {
                            $deductQty = $requestedQty;
                        }
                    } else {
                        $deductQty = $requestedQty;
                    }

                    // Look up variant if product is variant
                    if ($product->is_variant && isset($productCodeArray[$i])) {
                        $pv = ProductVariant::select('id', 'variant_id', 'qty')
                            ->where('product_id', $id)
                            ->where('item_code', $productCodeArray[$i])
                            ->first();
                        if (!$pv) {
                            $pv = ProductVariant::where('product_id', $id)->first();
                        }
                        if ($pv) {
                            $productSale['variant_id'] = $pv->variant_id;
                        }
                    }

                    if (in_array((int)$sale->sale_status, [1, 5], true)) {
                        // Deduct from Product company stock
                        $product->qty -= $deductQty;
                        $product->save();

                        // Deduct from Variant stock if variant product
                        if ($product->is_variant && isset($productSale['variant_id']) && $productSale['variant_id']) {
                            $pvModel = ProductVariant::where('product_id', $id)->where('variant_id', $productSale['variant_id'])->first();
                            if ($pvModel) {
                                $pvModel->qty -= $deductQty;
                                $pvModel->save();
                            }
                            $pw = Product_Warehouse::where('product_id', $id)
                                ->where('variant_id', $productSale['variant_id'])
                                ->where('warehouse_id', $sale->warehouse_id)
                                ->first();
                            if (!$pw) {
                                $pw = Product_Warehouse::where('product_id', $id)
                                    ->where('warehouse_id', $sale->warehouse_id)
                                    ->first();
                            }
                        } else {
                            $pw = Product_Warehouse::where('product_id', $id)
                                ->where('warehouse_id', $sale->warehouse_id)
                                ->first();
                        }


                        if ($pw) {
                            $pw->qty -= $deductQty;
                            $pw->save();
                        } else {
                            Product_Warehouse::create([
                                'product_id' => $id,
                                'variant_id' => isset($productSale['variant_id']) ? $productSale['variant_id'] : null,
                                'warehouse_id' => $sale->warehouse_id,
                                'qty' => -$deductQty,
                            ]);
                        }
                    }
                }

                $created_product_sale = Product_Sale::create($productSale);


                // Handle Restaurant Modifiers if present in request data
                $modifierPayload = $data['topping_product'][$i] ?? ($data['modifiers'][$i] ?? null);
                if (is_array($modifierPayload)) {
                    $modifierPayload = json_encode($modifierPayload);
                }
                if (!empty($modifierPayload) && class_exists(\Modules\Restaurant\Entities\ProductSaleModifier::class)) {
                    $modifiers = app(\Modules\Restaurant\Services\ModifierSelectionService::class)
                        ->resolve((int) $id, $modifierPayload);
                    if (is_array($modifiers)) {
                        foreach ($modifiers as $modifierData) {
                            DB::table('product_sale_modifiers')->insert([
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

                            // Deduct inventory if sale is completed or restaurant processing and modifier links to ingredient stock
                            $productList = $modifierData['product_list'];
                            $qtyList = $modifierData['qty_list'];

                            if (in_array((int)$sale->sale_status, [1, 5], true) && !empty($productList)) {
                                $mod_product_ids = explode(',', $productList);
                                $mod_qtys = explode(',', $qtyList);

                                foreach ($mod_product_ids as $k => $mod_product_id) {
                                    $mod_qty = (float)($mod_qtys[$k] ?? 1) * (float)($modifierData['qty'] ?? 1) * (float)$qtyArray[$i];

                                    $mod_product = Product::find($mod_product_id);
                                    if ($mod_product && $mod_product->type == 'standard') {
                                        $mod_product->qty -= $mod_qty;
                                        $mod_product->save();

                                        $mod_warehouse = Product_Warehouse::where([
                                            'product_id' => $mod_product_id,
                                            'warehouse_id' => $sale->warehouse_id
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
            }


            // Create Payment Records & Post Accounting Payment Entries
            $paidByIdArray = $data['paid_by_id'] ?? [1];
            if (!is_array($paidByIdArray)) {
                $paidByIdArray = [$paidByIdArray];
            }
            if (!is_array($paidAmountArray)) {
                $paidAmountArray = [$totalPaidAmount];
            }

            if ($totalPaidAmount > 0) {
                $defaultAccount = Account::where('is_default', true)->first() ?? Account::first();
                $accountId = !empty($data['account_id']) ? $data['account_id'] : ($defaultAccount ? $defaultAccount->id : 1);

                foreach ($paidByIdArray as $key => $methodId) {
                    $amount = (float)($paidAmountArray[$key] ?? 0);
                    if ($amount <= 0) {
                        continue;
                    }

                    $payingMethod = match ((string)$methodId) {
                        '1' => 'Cash',
                        '2' => 'Gift Card',
                        '3' => 'Credit Card',
                        '4' => 'Cheque',
                        '5' => 'Paypal',
                        '6' => 'Deposit',
                        '7' => 'Points',
                        default => is_string($methodId) ? ucfirst($methodId) : 'Cash',
                    };

                    $payment = new Payment();
                    $payment->user_id = $data['user_id'];
                    $payment->sale_id = $sale->id;
                    $payment->payment_reference = $this->invoiceService->generateInvoiceName('spr-');
                    $payment->amount = $amount;
                    $payment->change = max(0, (float)($data['paying_amount'][$key] ?? $amount) - $amount);
                    $payment->paying_method = $payingMethod;
                    $payment->payment_note = $data['payment_note'] ?? null;
                    $payment->account_id = $accountId;
                    $payment->currency_id = $sale->currency_id ?? 1;
                    $payment->exchange_rate = $sale->exchange_rate ?? 1;
                    $payment->payment_at = $data['created_at'];

                    if ($cashRegister) {
                        $payment->cash_register_id = $cashRegister->id;
                    }

                    $payment->save();

                    // Record Payment in Accounting Engine
                    $resPay = $this->accountingService->recordPayment($payment);
                    if (!$resPay->success) {
                        \Log::error('Accounting failed for Sale Payment', ['payment_id' => $payment->id, 'error' => $resPay->error]);
                    }
                }
            }

            // Record Sale in Accounting Engine (P&L, A/R, Revenue)
            $resSale = $this->accountingService->recordSale($sale, 'sale_created');
            if (!$resSale->success) {
                \Log::error('Accounting failed for Sale', ['sale_id' => $sale->id, 'error' => $resSale->error]);
            }

            return $sale;
        });
    }

    /**
     * Add a payment to an existing sale.
     *
     * @param array $data
     * @param User|null $user
     * @return Payment
     */
    public function addSalePayment(array $data, ?User $user = null): Payment
    {
        return DB::transaction(function () use ($data, $user) {
            $userId = $user ? $user->id : (\Illuminate\Support\Facades\Auth::id() ?? 1);
            $sale = Sale::findOrFail($data['sale_id']);
            $amount = (float) ($data['amount'] ?? 0.00);

            $sale->paid_amount += $amount;
            $balance = $sale->grand_total - $sale->paid_amount;
            if (abs($balance) < 0.001) {
                $sale->payment_status = 4; // Paid
            } else {
                $sale->payment_status = 2; // Partial
            }
            $sale->save();

            $cashRegister = CashRegister::where([
                ['user_id', $userId],
                ['warehouse_id', $sale->warehouse_id],
                ['status', true]
            ])->first();

            $payingMethodId = $data['paid_by_id'] ?? 1;
            $payingMethod = match ((int)$payingMethodId) {
                1 => 'Cash',
                2 => 'Gift Card',
                3 => 'Credit Card',
                4 => 'Cheque',
                5 => 'Paypal',
                6 => 'Deposit',
                7 => 'Points',
                default => 'Cash',
            };

            $paymentReference = $data['payment_reference'] ?? $this->invoiceService->generateInvoiceName('spr-');

            $payment = new Payment();
            $payment->user_id = $userId;
            $payment->sale_id = $sale->id;
            if ($cashRegister) {
                $payment->cash_register_id = $cashRegister->id;
            }
            $payment->account_id = $data['account_id'] ?? 1;
            $payment->payment_reference = $paymentReference;
            $payment->amount = $amount;
            $payment->currency_id = $data['currency_id'] ?? $sale->currency_id ?? 1;
            $payment->exchange_rate = $data['exchange_rate'] ?? $sale->exchange_rate ?? 1;
            $payment->change = (float)($data['paying_amount'] ?? $amount) - $amount;
            $payment->paying_method = $payingMethod;
            $payment->payment_note = $data['payment_note'] ?? null;
            $payment->payment_receiver = $data['payment_receiver'] ?? null;
            $payment->payment_at = isset($data['payment_at']) ? normalize_to_sql_datetime($data['payment_at']) : date('Y-m-d H:i:s');

            $payment->save();

            // Record accounting payment entry
            $result = $this->accountingService->recordPayment($payment);
            if (!$result->success) {
                \Log::error('Accounting failed for Sale Payment', ['payment_id' => $payment->id, 'error' => $result->error]);
            }

            return $payment;
        });
    }

    /**
     * Update an existing sale payment.
     *
     * @param array $data
     * @param User|null $user
     * @return Payment
     */
    public function updateSalePayment(array $data, ?User $user = null): Payment
    {
        return DB::transaction(function () use ($data, $user) {
            $payment = Payment::findOrFail($data['payment_id']);
            $sale = Sale::findOrFail($payment->sale_id);
            $newAmount = (float) ($data['edit_amount'] ?? $payment->amount);

            $amountDiff = $payment->amount - $newAmount;
            $sale->paid_amount -= $amountDiff;
            $balance = $sale->grand_total - $sale->paid_amount;
            if (abs($balance) < 0.001) {
                $sale->payment_status = 4;
            } else {
                $sale->payment_status = 2;
            }
            $sale->save();

            $editPaidById = $data['edit_paid_by_id'] ?? 1;
            $payingMethod = match ((int)$editPaidById) {
                1 => 'Cash',
                2 => 'Gift Card',
                3 => 'Credit Card',
                4 => 'Cheque',
                5 => 'Paypal',
                6 => 'Deposit',
                7 => 'Points',
                default => 'Cash',
            };

            $payment->account_id = $data['account_id'] ?? $payment->account_id;
            $payment->amount = $newAmount;
            $payment->change = (float)($data['edit_paying_amount'] ?? $newAmount) - $newAmount;
            $payment->paying_method = $payingMethod;
            $payment->payment_note = $data['edit_payment_note'] ?? $payment->payment_note;
            $payment->payment_receiver = $data['payment_receiver'] ?? $payment->payment_receiver;
            if (isset($data['payment_at'])) {
                $payment->payment_at = normalize_to_sql_datetime($data['payment_at']);
            }
            $payment->save();

            // Reverse previous accounting entry and post updated entry
            $this->accountingService->reverseTransaction(get_class($payment), $payment->id, '_reversed');
            $result = $this->accountingService->recordPayment($payment, 'payment_updated');
            if (!$result->success) {
                \Log::error('Accounting failed for Sale Payment Update', ['payment_id' => $payment->id, 'error' => $result->error]);
            }

            return $payment;
        });
    }

    /**
     * Delete/reverse an existing sale payment.
     *
     * @param int $paymentId
     * @param User|null $user
     * @return bool
     */
    public function deleteSalePayment(int $paymentId, ?User $user = null): bool
    {
        return DB::transaction(function () use ($paymentId) {
            $payment = Payment::findOrFail($paymentId);
            $sale = Sale::findOrFail($payment->sale_id);

            $sale->paid_amount -= $payment->amount;
            $balance = $sale->grand_total - $sale->paid_amount;
            if (abs($balance) < 0.001) {
                $sale->payment_status = 4;
            } elseif ($sale->paid_amount > 0) {
                $sale->payment_status = 2;
            } else {
                $sale->payment_status = 1; // Pending
            }
            $sale->save();

            // Reverse GL accounting entries for this payment
            $this->accountingService->reverseTransaction(get_class($payment), $payment->id, '_deleted');

            $payment->delete();

            return true;
        });
    }
}
