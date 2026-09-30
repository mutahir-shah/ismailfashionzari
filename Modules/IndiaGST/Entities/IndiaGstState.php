<?php

namespace Modules\IndiaGST\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\IndiaGST\Database\factories\IndiaGstStateFactory;

class IndiaGstState extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];
    
    protected static function newFactory(): IndiaGstStateFactory
    {
        //return IndiaGstStateFactory::new();
    }
}
