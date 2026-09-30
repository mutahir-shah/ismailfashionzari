<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingAccount extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_control_account' => 'boolean',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'is_cash_account' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(AccountingAccount::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(AccountingAccount::class, 'parent_id');
    }

    public function journalLines()
    {
        return $this->hasMany(JournalLine::class, 'accounting_account_id');
    }
}
