<?php

namespace Modules\Repair\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Warehouse;

class ServiceJob extends Model
{
    use \App\Traits\WarehouseScoped;

    protected $table = 'service_jobs';

    protected $fillable = [
        'reference_no',
        'customer_id',
        'service_type',
        'title',
        'description',
        'status',
        'priority',
        'assigned_to',
        'created_by',
        'expected_delivery_date',
        'delivery_date',
        'service_charge',
        'discount',
        'tax',
        'total_amount',
        'paid_amount',
        'due_amount',
        'warehouse_id',
        'note',
    ];

    protected $casts = [
        'expected_delivery_date' => 'date',
        'delivery_date'          => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function device()
    {
        return $this->hasOne(ServiceDevice::class, 'service_job_id');
    }

    public function vehicle()
    {
        return $this->hasOne(ServiceVehicle::class, 'service_job_id');
    }

    public function items()
    {
        return $this->hasMany(ServiceJobItem::class, 'service_job_id');
    }

    public function updates()
    {
        return $this->hasMany(ServiceJobUpdate::class, 'service_job_id')->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeDevices($query)
    {
        return $query->where('service_type', 'device');
    }

    public function scopeVehicles($query)
    {
        return $query->where('service_type', 'vehicle');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function getStatusBadgeAttribute(): string
    {
        $map = [
            'pending'     => 'warning',
            'diagnosed'   => 'info',
            'in_progress' => 'primary',
            'completed'   => 'success',
            'delivered'   => 'secondary',
            'cancelled'   => 'danger',
        ];
        $color = $map[$this->status] ?? 'secondary';
        return '<span class="badge badge-' . $color . '">' . ucfirst(str_replace('_', ' ', $this->status)) . '</span>';
    }

    public function getPriorityBadgeAttribute(): string
    {
        $map = [
            'low'    => 'success',
            'medium' => 'warning',
            'high'   => 'danger',
        ];
        $color = $map[$this->priority] ?? 'secondary';
        return '<span class="badge badge-' . $color . '">' . ucfirst($this->priority) . '</span>';
    }
}
