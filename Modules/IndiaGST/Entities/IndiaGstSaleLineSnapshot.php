<?php

namespace Modules\IndiaGST\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\IndiaGST\Database\factories\IndiaGstSaleLineSnapshotFactory;

class IndiaGstSaleLineSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'india_gst_sale_snapshot_id',
        'product_sale_id',
        'product_id',
        'variant_id',
        'product_name',
        'product_code',
        'classification',
        'hsn_sac_code',
        'quantity',
        'unit_price',
        'gross_value',
        'line_discount',
        'allocated_invoice_discount',
        'taxable_charges',
        'non_taxable_charges',
        'taxable_value',
        'taxability_type',
        'gst_tax_profile_id',
        'gst_tax_profile_code',
        'gst_tax_profile_version',
        'profile_resolution_source',
        'cgst_rate',
        'cgst_amount',
        'sgst_rate',
        'sgst_amount',
        'utgst_rate',
        'utgst_amount',
        'igst_rate',
        'igst_amount',
        'cess_calculation_type',
        'cess_rate',
        'cess_amount_per_unit',
        'cess_amount',
        'rounding_adjustment',
        'line_total',
        'calculation_rule_code'
    ];
    
    protected static function newFactory(): IndiaGstSaleLineSnapshotFactory
    {
        //return IndiaGstSaleLineSnapshotFactory::new();
    }
}
