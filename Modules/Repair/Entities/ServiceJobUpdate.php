<?php

namespace Modules\Repair\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ServiceJobUpdate extends Model
{
    protected $table = 'service_job_updates';

    protected $fillable = [
        'service_job_id',
        'status',
        'note',
        'updated_by',
    ];

    public function serviceJob()
    {
        return $this->belongsTo(ServiceJob::class, 'service_job_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
