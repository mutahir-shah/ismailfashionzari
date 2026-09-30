<?php

namespace App\Services\Domain;

use DB;
use Auth;
use App\Models\Unit;
use App\Models\Product;
use App\Models\Transfer;
use App\Models\ProductVariant;
use App\Models\ProductTransfer;
use App\Models\Product_Warehouse;
use App\Services\InvoiceService;

class TransferDomainService
{
    public function __construct(private InvoiceService $invoiceService)
    {
    }

    /**
     * Create a Transfer and execute stock movements between warehouses.
     *
     * @param array $data
     * @param object|null $user
     * @return Transfer
     */
    public function createTransfer(array $data, $user = null): Transfer
    {
        $userId = $user ? $user->id : (Auth::id() ?? 1);
        $data['user_id'] = $userId;

        if (empty($data['reference_no'])) {
            $data['reference_no'] = $this->invoiceService->generateInvoiceName('tr-');
        }

        if (empty($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        } else {
            $data['created_at'] = normalize_to_sql_datetime($data['created_at']);
        }

        if (!isset($data['item']) && isset($data['product_id'])) {
            $data['item'] = count($data['product_id']);
        }

        return DB::transaction(function () use ($data) {

            $lims_transfer_data = Transfer::create($data);

            $product_id = $data['product_id'];
            $imei_number = $data['imei_number'] ?? null;
            $product_batch_id = $data['product_batch_id'] ?? null;
            $product_code = $data['product_code'];
            $qty = $data['qty'];
            $purchase_unit = $data['purchase_unit'];
            $net_unit_cost = $data['net_unit_cost'];
            $tax_rate = $data['tax_rate'];
            $tax = $data['tax'];
            $total = $data['subtotal'];
            $status = $data['status'] ?? 1;

            foreach ($product_id as $i => $id) {
                $unitName = $purchase_unit[$i];
                $lims_purchase_unit_data = Unit::where('unit_name', $unitName)->first()
                    ?? Unit::where('unit_code', $unitName)->first()
                    ?? Unit::find($unitName)
                    ?? Unit::first();

                $product_transfer = [
                    'variant_id' => null,
                    'product_batch_id' => null,
                ];

                $lims_product_data = Product::select('is_variant')->find($id);

                if ($lims_product_data && $lims_product_data->is_variant) {
                    $lims_product_variant_data = ProductVariant::FindExactProductWithCode($id, $product_code[$i])->first();
                    $variantId = $lims_product_variant_data ? $lims_product_variant_data->variant_id : null;

                    if (!$variantId) {
                        $pv = ProductVariant::where('product_id', $id)->first();
                        $variantId = $pv ? $pv->variant_id : null;
                    }

                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($id, $variantId, $data['from_warehouse_id'])->first();
                    $product_transfer['variant_id'] = $variantId;
                } elseif (isset($product_batch_id[$i]) && $product_batch_id[$i]) {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_batch_id', $product_batch_id[$i]],
                        ['warehouse_id', $data['from_warehouse_id']],
                    ])->first();
                    $product_transfer['product_batch_id'] = $product_batch_id[$i];
                } else {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $id],
                        ['warehouse_id', $data['from_warehouse_id']],
                    ])->first();
                }

                // If source Product_Warehouse row does not exist, create it with 0 qty
                if (!$lims_product_warehouse_data) {
                    $lims_product_warehouse_data = new Product_Warehouse();
                    $lims_product_warehouse_data->product_id = $id;
                    $lims_product_warehouse_data->variant_id = $product_transfer['variant_id'];
                    $lims_product_warehouse_data->product_batch_id = $product_transfer['product_batch_id'];
                    $lims_product_warehouse_data->warehouse_id = $data['from_warehouse_id'];
                    $lims_product_warehouse_data->qty = 0;
                }

                if ($status != 2) {
                    if ($lims_purchase_unit_data && $lims_purchase_unit_data->operator == '*') {
                        $quantity = $qty[$i] * $lims_purchase_unit_data->operation_value;
                    } elseif ($lims_purchase_unit_data && $lims_purchase_unit_data->operation_value > 0) {
                        $quantity = $qty[$i] / $lims_purchase_unit_data->operation_value;
                    } else {
                        $quantity = $qty[$i];
                    }

                    if (isset($imei_number[$i]) && $imei_number[$i]) {
                        $imei_numbers = explode(",", $imei_number[$i]);
                        $all_imei_numbers = explode(",", $lims_product_warehouse_data->imei_number ?? '');
                        foreach ($imei_numbers as $number) {
                            if (($j = array_search($number, $all_imei_numbers)) !== false) {
                                unset($all_imei_numbers[$j]);
                            }
                        }
                        $lims_product_warehouse_data->imei_number = implode(",", $all_imei_numbers);
                    }
                } else {
                    $quantity = 0;
                }

                // Deduct quantity from sending warehouse
                $lims_product_warehouse_data->qty -= $quantity;
                $lims_product_warehouse_data->save();

                // Add quantity to destination warehouse if Completed (status == 1)
                if ($status == 1) {
                    if ($lims_product_data && $lims_product_data->is_variant) {
                        $dest_pw = Product_Warehouse::FindProductWithVariant($id, $product_transfer['variant_id'], $data['to_warehouse_id'])->first();
                    } elseif (isset($product_batch_id[$i]) && $product_batch_id[$i]) {
                        $dest_pw = Product_Warehouse::where([
                            ['product_batch_id', $product_batch_id[$i]],
                            ['warehouse_id', $data['to_warehouse_id']],
                        ])->first();
                    } else {
                        $dest_pw = Product_Warehouse::where([
                            ['product_id', $id],
                            ['warehouse_id', $data['to_warehouse_id']],
                        ])->first();
                    }

                    if ($dest_pw) {
                        $dest_pw->qty += $quantity;
                    } else {
                        $dest_pw = new Product_Warehouse();
                        $dest_pw->product_id = $id;
                        $dest_pw->product_batch_id = $product_transfer['product_batch_id'];
                        $dest_pw->variant_id = $product_transfer['variant_id'];
                        $dest_pw->warehouse_id = $data['to_warehouse_id'];
                        $dest_pw->qty = $quantity;
                    }

                    if (isset($imei_number[$i]) && $imei_number[$i]) {
                        if ($dest_pw->imei_number) {
                            $dest_pw->imei_number .= ',' . $imei_number[$i];
                        } else {
                            $dest_pw->imei_number = $imei_number[$i];
                        }
                    }

                    $dest_pw->save();
                }

                $product_transfer['transfer_id'] = $lims_transfer_data->id;
                $product_transfer['product_id'] = $id;
                $product_transfer['imei_number'] = $imei_number[$i] ?? null;
                $product_transfer['qty'] = $qty[$i];
                $product_transfer['purchase_unit_id'] = $lims_purchase_unit_data ? $lims_purchase_unit_data->id : 1;
                $product_transfer['net_unit_cost'] = $net_unit_cost[$i];
                $product_transfer['tax_rate'] = $tax_rate[$i];
                $product_transfer['tax'] = $tax[$i];
                $product_transfer['total'] = $total[$i];
                ProductTransfer::create($product_transfer);
            }

            return $lims_transfer_data;
        });
    }
}
