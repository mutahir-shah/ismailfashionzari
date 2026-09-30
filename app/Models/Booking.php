<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes, \App\Traits\WarehouseScoped;

    protected $fillable = [
    'warehouse_id',
    'customer_id',
    'user_id',
    'created_by',
    'product_id',
    'price',
    'status',
    'start_date',
    'end_date',
    'note',
];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
    ];

    // Status color for FullCalendar
    public function getCalendarColorAttribute(): string
    {
        return match ($this->status) {
            'Booked'    => '#696cff', // primary
            'Waiting'   => '#ffab00', // warning
            'Completed' => '#28c76f', // success
            'Cancelled' => '#ea5455', // danger
            default     => '#696cff',
        };
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
