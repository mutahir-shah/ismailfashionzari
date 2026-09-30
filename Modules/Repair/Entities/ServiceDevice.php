<?php
// ─── ServiceDevice.php ────────────────────────────────────────────────────────
namespace Modules\Repair\Entities;

use Illuminate\Database\Eloquent\Model;

class ServiceDevice extends Model
{
    protected $table = 'service_devices';

    protected $fillable = [
        'service_job_id',
        'device_type',
        'brand',
        'model',
        'serial_number',
        'imei',
        'password_hint',
        'accessories',
        'issue_reported',
        'condition_notes',
    ];

    public function serviceJob()
    {
        return $this->belongsTo(ServiceJob::class, 'service_job_id');
    }

    public function deviceTypeInfo()
    {
        return $this->belongsTo(DeviceType::class, 'device_type', 'id');
    }
}
