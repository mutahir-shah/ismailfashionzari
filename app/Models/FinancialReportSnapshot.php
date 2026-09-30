<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialReportSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_type',
        'period_start',
        'period_end',
        'generated_by',
        'generated_at',
        'checksum',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'generated_at' => 'datetime',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function generateChecksum(): string
    {
        $payload = [
            'report_type' => $this->report_type,
            'period_start' => $this->period_start instanceof \Carbon\Carbon ? $this->period_start->format('Y-m-d') : $this->period_start,
            'period_end' => $this->period_end instanceof \Carbon\Carbon ? $this->period_end->format('Y-m-d') : $this->period_end,
            'report_data' => $this->metadata,
        ];

        return hash(
            'sha256',
            json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES |
                JSON_PRESERVE_ZERO_FRACTION
            )
        );
    }
}
