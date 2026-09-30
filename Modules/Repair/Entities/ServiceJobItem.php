<?php

namespace Modules\Repair\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class ServiceJobItem extends Model
{
    protected $table = 'service_job_items';

    protected $fillable = [
        'service_job_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount',
        'tax',
        'total',
    ];

    public function serviceJob()
    {
        return $this->belongsTo(ServiceJob::class, 'service_job_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
