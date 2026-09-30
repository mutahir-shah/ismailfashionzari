<?php

namespace Modules\IndiaGST\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\IndiaGST\Database\factories\GstTaxProfileFactory;

class GstTaxProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'code',
        'taxability_type',
        'total_gst_rate',
        'cess_calculation_type',
        'cess_rate',
        'cess_amount_per_unit',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];
    
    protected static function newFactory(): GstTaxProfileFactory
    {
        //return GstTaxProfileFactory::new();
    }
}
