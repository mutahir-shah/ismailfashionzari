<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodicInventoryClose extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'posting_date' => 'date',
        'calculated_at' => 'datetime',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
        'calculation_basis' => 'array',
        'book_inventory' => 'decimal:4',
        'operational_inventory' => 'decimal:4',
        'adjustment' => 'decimal:4',
    ];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function reversalJournalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_journal_entry_id');
    }
}
