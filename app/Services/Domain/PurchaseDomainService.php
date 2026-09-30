<?php

namespace App\Services\Domain;

use App\Models\Purchase;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Product_Warehouse;
use App\Models\ProductBatch;
use App\Models\ProductPurchase;
use App\Models\Unit;
use App\Models\CustomField;
use App\Models\PosSetting;
use App\Models\PaymentWithCheque;
use App\Models\PaymentWithCreditCard;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class PurchaseDomainService
{
    public function __construct(
        private InvoiceService $invoiceService,
        private PaymentService $paymentService,
        private AccountingService $accountingService
    ) {}

    /**
     * Create a new purchase transaction using SalePro domain logic.
     *
     * @param array $data
     * @param mixed|null $user
     * @return Purchase
     */
    public function createPurchase(array $data, $user = null): Purchase
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            throw new RuntimeException("Purchase creation requires an authenticated acting user.");
        }

        DB::beginTransaction();

        try {
            $data['user_id'] = $user->id;

            if (!isset($data['reference_no']) || empty($data['reference_no'])) {
                $data['reference_no'] = $this->invoiceService->generateInvoiceName('pr-');
            }

            if (isset($data['created_at'])) {
                $data['created_at'] = normalize_to_sql_datetime($data['created_at']);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
            }

            // Due date calculation from payment terms
            if (!empty($data['pay_term_no']) && !empty($data['pay_term_period'])) {
                $purchaseDate = \Carbon\Carbon::parse($data['created_at']);
                if ($data['pay_term_period'] === 'days') {
                    $data['due_date'] = $purchaseDate->addDays((int)$data['pay_term_no'])->format('Y-m-d');
                } else {
                    $data['due_date'] = $purchaseDate->addMonths((int)$data['pay_term_no'])->format('Y-m-d');
                }
            } elseif (empty($data['due_date'])) {
                $data['due_date'] = null;
            }

            $data['paid_amount'] = 0; // Updated dynamically by PaymentService if payments exist

            $lims_purchase_data = Purchase::create($data);

            // Custom fields support
            $custom_field_data = [];
            $custom_fields = CustomField::where('belongs_to', 'purchase')->select('name', 'type')->get();
            foreach ($custom_fields as $type => $custom_field) {
                $field_name = str_replace(' ', '_', strtolower($custom_field->name));
                if (isset($data[$field_name])) {
                    if ($custom_field->type == 'checkbox' || $custom_field->type == 'multi_select') {
                        $custom_field_data[$field_name] = implode(",", $data[$field_name]);
                    } else {
                        $custom_field_data[$field_name] = $data[$field_name];
                    }
                }
            }
            if (count($custom_field_data)) {
                DB::table('purchases')->where('id', $lims_purchase_data->id)->update($custom_field_data);
            }

            $product_id = $data['product_id'];
            $product_code = $data['product_code'];
            $qty = $data['qty'];
            $recieved = $data['recieved'];
            $batch_no = $data['batch_no'] ?? null;
            $expired_date = $data['expired_date'] ?? null;
            $purchase_unit = $data['purchase_unit'];
            $unit_cost = $data['unit_cost'];
            $net_unit_cost = $data['net_unit_cost'];
            $net_unit_margin = $data['net_unit_margin'];
            $net_unit_margin_type = $data['net_unit_margin_type'];
            $net_unit_price = $data['net_unit_price'];
            $discount = $data['discount'];
            $tax_rate = $data['tax_rate'];
            $tax = $data['tax'];
            $total = $data['subtotal'];
            $imei_numbers = $data['imei_number'] ?? array_fill(0, count($product_id), '');

            foreach ($product_id as $i => $id) {
                $lims_purchase_unit_data = Unit::where('unit_name', $purchase_unit[$i])->first();
                if (!$lims_purchase_unit_data) {
                    $lims_purchase_unit_data = Unit::first();
                }

                if ($lims_purchase_unit_data->operator == '*') {
                    $quantity = $recieved[$i] * $lims_purchase_unit_data->operation_value;
                } else {
                    $quantity = $recieved[$i] / $lims_purchase_unit_data->operation_value;
                }

                $lims_product_data = Product::find($id);
                $price = $lims_product_data->price;

                // Product Batch handling
                if (isset($batch_no[$i]) && !empty($batch_no[$i])) {
                    $product_batch_data = ProductBatch::where([
                        ['product_id', $lims_product_data->id],
                        ['batch_no', $batch_no[$i]]
                    ])->first();
                    if ($product_batch_data) {
                        $product_batch_data->expired_date = $expired_date[$i];
                        $product_batch_data->qty += $quantity;
                        $product_batch_data->save();
                    } else {
                        $product_batch_data = ProductBatch::create([
                            'product_id' => $lims_product_data->id,
                            'batch_no' => $batch_no[$i],
                            'expired_date' => $expired_date[$i],
                            'qty' => $quantity
                        ]);
                    }
                    $product_purchase['product_batch_id'] = $product_batch_data->id;
                } else {
                    $product_purchase['product_batch_id'] = null;
                }

                if ($lims_product_data->is_variant) {
                    $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')
                        ->FindExactProductWithCode($lims_product_data->id, $product_code[$i])
                        ->first();

                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $id],
                        ['variant_id', $lims_product_variant_data->variant_id],
                        ['warehouse_id', $data['warehouse_id']]
                    ])->first();

                    $product_purchase['variant_id'] = $lims_product_variant_data->variant_id;
                    $lims_product_variant_data->qty += $quantity;
                    $lims_product_variant_data->save();
                } else {
                    $product_purchase['variant_id'] = null;
                    if ($product_purchase['product_batch_id']) {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $id],
                            ['warehouse_id', $data['warehouse_id']],
                        ])->whereNotNull('price')->select('price')->first();

                        $price = $lims_product_warehouse_data ? $lims_product_warehouse_data->price : null;

                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $id],
                            ['product_batch_id', $product_purchase['product_batch_id']],
                            ['warehouse_id', $data['warehouse_id']],
                        ])->first();
                    } else {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $id],
                            ['warehouse_id', $data['warehouse_id']],
                        ])->first();
                    }
                }

                // Update product total stock & cost
                $marginType = $net_unit_margin_type[$i] ?? 'flat';
                if ($marginType === 'amount') {
                    $marginType = 'flat';
                }
                $lims_product_data->qty += $quantity;
                $exchange_rate = $data['exchange_rate'] ?? 1;
                $lims_product_data->cost = $unit_cost[$i] / $exchange_rate;
                $lims_product_data->profit_margin = $net_unit_margin[$i];
                $lims_product_data->profit_margin_type = $marginType;
                $lims_product_data->save();


                // Add or update Product_Warehouse stock
                if ($lims_product_warehouse_data) {
                    $lims_product_warehouse_data->qty += $quantity;
                    $lims_product_warehouse_data->product_batch_id = $product_purchase['product_batch_id'];
                } else {
                    $lims_product_warehouse_data = new Product_Warehouse();
                    $lims_product_warehouse_data->product_id = $id;
                    $lims_product_warehouse_data->product_batch_id = $product_purchase['product_batch_id'];
                    $lims_product_warehouse_data->warehouse_id = $data['warehouse_id'];
                    $lims_product_warehouse_data->qty = $quantity;
                    if ($price) {
                        $lims_product_warehouse_data->price = $price;
                    }
                    if ($lims_product_data->is_variant) {
                        $lims_product_warehouse_data->variant_id = $lims_product_variant_data->variant_id;
                    }
                }

                if (!empty($imei_numbers[$i])) {
                    if ($lims_product_warehouse_data->imei_number) {
                        $lims_product_warehouse_data->imei_number .= ',' . $imei_numbers[$i];
                    } else {
                        $lims_product_warehouse_data->imei_number = $imei_numbers[$i];
                    }
                }
                $lims_product_warehouse_data->save();

                $product_purchase['purchase_id'] = $lims_purchase_data->id;
                $product_purchase['product_id'] = $id;
                $product_purchase['imei_number'] = $imei_numbers[$i];
                $product_purchase['qty'] = $qty[$i];
                $product_purchase['recieved'] = $recieved[$i];
                $product_purchase['purchase_unit_id'] = $lims_purchase_unit_data->id;
                $product_purchase['net_unit_cost'] = $net_unit_cost[$i];
                $product_purchase['net_unit_margin'] = $net_unit_margin[$i];
                $product_purchase['net_unit_margin_type'] = $marginType;
                $product_purchase['net_unit_price'] = $net_unit_price[$i];

                $product_purchase['discount'] = $discount[$i];
                $product_purchase['tax_rate'] = $tax_rate[$i];
                $product_purchase['tax'] = $tax[$i];
                $product_purchase['total'] = $total[$i];
                ProductPurchase::create($product_purchase);
            }

            // Payment handling if payment status is paid/partial (3 or 4) or if paid_amount is provided
            if (isset($data['payment_status']) && ($data['payment_status'] == 3 || $data['payment_status'] == 4 || $data['payment_status'] == 1 || $data['payment_status'] == 2)) {
                $payingAmount = isset($data['paying_amount']) ? (is_array($data['paying_amount']) ? array_sum($data['paying_amount']) : $data['paying_amount']) : 0;
                $amount = isset($data['amount']) ? (is_array($data['amount']) ? array_sum($data['amount']) : $data['amount']) : 0;

                if ($amount > 0 || $payingAmount > 0) {
                    $paymentAt = isset($data['payment_at']) ? normalize_to_sql_datetime($data['payment_at']) : date('Y-m-d H:i:s');
                    $paidById = isset($data['paid_by_id']) ? (is_array($data['paid_by_id']) ? $data['paid_by_id'][0] : $data['paid_by_id']) : 1;
                    $accountId = $data['account_id'] ?? 1;

                    $pay_data = [
                        'paying_amount' => $payingAmount,
                        'amount' => $amount,
                        'paid_by_id' => $paidById,
                        'cheque_no' => $data['cheque_no'] ?? '',
                        'account_id' => $accountId,
                        'payment_note' => $data['payment_note'] ?? 'Purchase Payment',
                        'purchase_id' => $lims_purchase_data->id,
                        'currency_id' => $lims_purchase_data->currency_id,
                        'exchange_rate' => $lims_purchase_data->exchange_rate ?? 1,
                        'payment_at' => $paymentAt,
                    ];

                    $response = $this->paymentService->payForPurchase($pay_data);
                    if (!$response['status']) {
                        DB::rollBack();
                        throw new RuntimeException($response['message']);
                    }
                }
            }

            DB::commit();

            // Record accounting entry
            try {
                $res = $this->accountingService->recordPurchase($lims_purchase_data, 'purchase_created');
                if ($res && $res->isPosted()) {
                    $lims_purchase_data->accounting_status = 'posted';
                    $lims_purchase_data->save();
                }
            } catch (\Exception $e) {
                \Log::error('Accounting error on PurchaseDomainService: ' . $e->getMessage());
            }

            return $lims_purchase_data->fresh();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
