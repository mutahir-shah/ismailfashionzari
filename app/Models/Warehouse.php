<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use \App\Traits\WarehouseVisibilityScoped;

    protected $fillable = [
        "name", "phone", "email", "address", "qr_code_id", "pos_type", "is_active"
    ];

    public function product()
    {
    	return $this->hasMany('App\Models\Product');

    }

    public function products()
    {
        return $this->belongsToMany(Product::class)->withPivot('qty');
    }

    public function printers()
    {
        return $this->hasMany(Printer::class, 'warehouse_id');
    }

    /**
     * Deactivate warehouse and delete related printers
     */
    public function deactivate()
    {
        // set warehouse inactive
        $this->is_active = false;
        $this->save();
        // HARD delete related printers
        $this->printers()->delete();
    }

    public function qrCode()
    {
        return $this->morphOne(QrCode::class, 'qrable');
    }
}
