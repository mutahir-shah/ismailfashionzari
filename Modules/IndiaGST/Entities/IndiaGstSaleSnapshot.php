<?php

namespace Modules\IndiaGST\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\IndiaGST\Database\factories\IndiaGstSaleSnapshotFactory;

class IndiaGstSaleSnapshot extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'sale_id',
        'gst_registration_id',
        'invoice_reference',
        'invoice_date',
        'transaction_date',
        'financial_year',
        'supplier_legal_name',
        'supplier_trade_name',
        'supplier_gstin',
        'supplier_address',
        'supplier_state_code',
        'supplier_state_name',
        'customer_id',
        'customer_name',
        'customer_legal_name',
        'customer_trade_name',
        'customer_gstin',
        'customer_registration_type',
        'customer_billing_address',
        'customer_shipping_address',
        'customer_state_code',
        'place_of_supply_state_code',
        'place_of_supply_state_name',
        'supply_rule_code',
        'jurisdiction_code',
        'is_inter_state',
        'manual_pos_override_used',
        'manual_pos_override_reason',
        'manual_pos_override_user_id',
        'currency_code',
        'exchange_rate',
        'total_gross_value',
        'total_line_discount',
        'total_invoice_discount',
        'total_taxable_charges',
        'total_non_taxable_charges',
        'total_taxable_value',
        'total_cgst',
        'total_sgst',
        'total_utgst',
        'total_igst',
        'total_cess',
        'rounding_adjustment',
        'grand_total',
        'snapshot_version',
        'locked_at'
    ];
    
    protected static function newFactory(): IndiaGstSaleSnapshotFactory
    {
        //return IndiaGstSaleSnapshotFactory::new();
    }
}
