<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adjustment extends Model
{
    use \App\Traits\WarehouseScoped;

    protected $fillable =[
        "reference_no", 
        "warehouse_id", 
        "document", 
        "total_qty", 
        "item",
        "note"
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
