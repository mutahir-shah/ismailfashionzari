<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingLiveRemediationApproval extends Model
{
    protected $fillable = [
        'uuid',
        'status',
        'target_database_name',
        'dump_filename',
        'dump_sha256',
        'database_fingerprint',
        'remediation_plan_hash',
        'expected_application_commit',
        'expected_key_row_counts',
        'expected_financial_balances',
        'backup_restore_test_confirmed',
        'maintenance_mode_required',
        'approved_by',
        'approved_at',
        'expires_at',
        'consumed_at',
        'remediation_manifest_id',
        'failure_reason',
        'revocation_reason',
    ];

    protected $casts = [
        'expected_key_row_counts' => 'array',
        'expected_financial_balances' => 'array',
        'backup_restore_test_confirmed' => 'boolean',
        'maintenance_mode_required' => 'boolean',
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];
}
