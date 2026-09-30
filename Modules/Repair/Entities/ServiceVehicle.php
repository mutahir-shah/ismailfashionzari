<?php

namespace Modules\Repair\Entities;

use Illuminate\Database\Eloquent\Model;

class ServiceVehicle extends Model
{
    protected $table = 'service_vehicles';

    protected $fillable = [
        'service_job_id',
        'vehicle_type',
        'brand',
        'model',
        'year',
        'registration_no',
        'engine_no',
        'chassis_no',
        'mileage',
        'fuel_level',
        'issue_reported',
        'condition_notes',
    ];

    public function serviceJob()
    {
        return $this->belongsTo(ServiceJob::class, 'service_job_id');
    }

    public function vehicleTypeInfo()
    {
        return $this->belongsTo(DeviceType::class, 'vehicle_type', 'id');
    }
}
