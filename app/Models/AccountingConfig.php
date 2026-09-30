<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingConfig extends Model
{
    protected $fillable = [
        'id',
        'enabled',
        'status',
        'activation_mode',
        'start_date',
        'opening_journal_entry_id',
        'activated_by',
        'activated_at',
    ];

    protected static function booted()
    {
        static::creating(function (AccountingConfig $config) {
            if (!$config->id) {
                $config->id = 1;
            }

            if ((int) $config->id !== 1) {
                throw new \RuntimeException('AccountingConfig is a singleton and must use id 1.');
            }

            if (static::where('id', 1)->exists()) {
                throw new \RuntimeException('AccountingConfig singleton already exists.');
            }
        });
    }
}
