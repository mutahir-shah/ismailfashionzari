<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingActivationSession extends Model
{
    //
    protected $fillable = [
        'mode',
        'start_date',
        'summary_json',
        'validation_json',
        'opening_journal_entry_id',
        'activated_by',
        'activated_at',
    ];
}
