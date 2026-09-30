<?php

namespace Modules\IndiaGST\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\IndiaGST\Database\factories\GstGenericTaxMappingFactory;

class GstGenericTaxMapping extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tax_id',
        'gst_tax_profile_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tax()
    {
        return $this->belongsTo(\App\Models\Tax::class, 'tax_id');
    }

    public function gstTaxProfile()
    {
        return $this->belongsTo(GstTaxProfile::class, 'gst_tax_profile_id');
    }

    protected static function newFactory(): GstGenericTaxMappingFactory
    {
        //return GstGenericTaxMappingFactory::new();
    }
}
