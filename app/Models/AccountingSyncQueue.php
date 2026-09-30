<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingSyncQueue extends Model
{
    use HasFactory;

    protected $table = 'accounting_sync_queue';

    protected $fillable = [
        'source_type',
        'source_id',
        'status',
        'attempts',
        'last_error',
        'last_attempt_at',
        'last_success_at',
        'resolved_at',
        'posted_at'
    ];

    protected $casts = [
        'last_attempt_at' => 'datetime',
        'last_success_at' => 'datetime',
        'resolved_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    /**
     * Get the polymorphic source model.
     */
    public function source()
    {
        return $this->morphTo();
    }
}
