<?php

namespace Modules\IndiaGST\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\IndiaGST\Database\factories\IndiaGstProductProfileFactory;

class IndiaGstProductProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'hsn_sac_code',
        'description',
        'is_exempt',
        'is_nil_rated',
        'is_non_gst',
        'gst_tax_profile_id',
        'type'
    ];
    
    protected static function newFactory(): IndiaGstProductProfileFactory
    {
        //return IndiaGstProductProfileFactory::new();
    }
}
