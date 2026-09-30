<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstallmentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_type',
        'reference_id',
        'name',
        'price',
        'additional_amount',
        'total_amount',
        'down_payment',
        'months',
    ];

    public function reference()
    {
        return $this->morphTo(null, 'reference_type', 'reference_id');
    }

    public function installments()
    {
        return $this->hasMany(Installment::class);
    }
}
