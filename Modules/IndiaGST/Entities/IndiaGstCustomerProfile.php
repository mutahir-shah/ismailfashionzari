<?php

namespace Modules\IndiaGST\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\IndiaGST\Database\factories\IndiaGstCustomerProfileFactory;

class IndiaGstCustomerProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'customer_id',
        'registration_type',
        'gstin',
        'state_id',
        'is_sez',
        'trade_name',
        'legal_name',
        'place_of_supply'
    ];
    
    public function state()
    {
        return $this->belongsTo(IndiaGstState::class, 'state_id');
    }
    
    protected static function newFactory(): IndiaGstCustomerProfileFactory
    {
        //return IndiaGstCustomerProfileFactory::new();
    }
}
