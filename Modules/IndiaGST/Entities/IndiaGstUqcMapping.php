<?php

namespace Modules\IndiaGST\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\IndiaGST\Database\factories\IndiaGstUqcMappingFactory;

class IndiaGstUqcMapping extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];
    
    protected static function newFactory(): IndiaGstUqcMappingFactory
    {
        //return IndiaGstUqcMappingFactory::new();
    }
}
