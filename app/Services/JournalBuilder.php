<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Exceptions\UnbalancedJournalException;
use App\Exceptions\ClosedPeriodException;
use App\Exceptions\DuplicateJournalException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class JournalBuilder
{
    private $reference_no;
    private $entry_date;
    private $source_type;
    private $source_id;
    private $source_subtype;
    private $event_type;
    private $note;
    private $created_by;
    private $lines = [];

    public function __construct()
    {
        $this->entry_date = Carbon::now()->toDateString();
        $this->created_by = auth()->id();
    }

    public static function create(): self
    {
        return new self();
    }

    public function setReference(string $reference_no): self
    {
        $this->reference_no = $reference_no;
        return $this;
    }

    public function setDate($date): self
    {
        $this->entry_date = Carbon::parse($date)->toDateString();
        return $this;
    }

    public function setSource(string $source_type, int $source_id): self
    {
        $this->source_type = $source_type;
        $this->source_id = $source_id;
        return $this;
    }

    public function setSourceSubtype(string $source_subtype): self
    {
        $this->source_subtype = $source_subtype;
        return $this;
    }

    public function setEventType(string $event_type): self
    {
        $this->event_type = $event_type;
        return $this;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function setCreatedBy(int $userId): self
    {
        $this->created_by = $userId;
        return $this;
    }

    public function addDebit(int $accountId, $amount, ?string $description = null): self
    {
        if ($amount > 0) {
            $this->lines[] = [
                'accounting_account_id' => $accountId,
                'debit' => number_format((float) $amount, 4, '.', ''),
                'credit' => '0.0000',
                'description' => $description,
            ];
        }
        return $this;
    }

    public function addCredit(int $accountId, $amount, ?string $description = null): self
    {
        if ($amount > 0) {
            $this->lines[] = [
                'accounting_account_id' => $accountId,
                'debit' => '0.0000',
                'credit' => number_format((float) $amount, 4, '.', ''),
                'description' => $description,
            ];
        }
        return $this;
    }

    public function save(): JournalEntry
    {
        $this->validatePeriod();
        $this->validateBalance();
        $this->validateIdempotency();

        return DB::transaction(function () {
            if (!$this->reference_no) {
                $this->reference_no = 'JE-' . date('YmdHis') . '-' . rand(1000, 9999);
            }

            $entry = JournalEntry::create([
                'reference_no' => $this->reference_no,
                'entry_date' => $this->entry_date,
                'source_type' => $this->source_type,
                'source_id' => $this->source_id,
                'source_subtype' => $this->source_subtype,
                'event_type' => $this->event_type,
                'note' => $this->note,
                'created_by' => $this->created_by ?? \Illuminate\Support\Facades\Auth::id() ?? 1,

            ]);

            foreach ($this->lines as $line) {
                $entry->lines()->create($line);
            }

            return $entry;
        });
    }

    private function validatePeriod(): void
    {
        $period = AccountingPeriod::where('start_date', '<=', $this->entry_date)
            ->where('end_date', '>=', $this->entry_date)
            ->first();

        if ($period && $period->is_closed) {
            throw new ClosedPeriodException();
        }
    }

    private function validateBalance(): void
    {
        $totalDebit = '0.0000';
        $totalCredit = '0.0000';

        foreach ($this->lines as $line) {
            $totalDebit = bcadd($totalDebit, $line['debit'], 4);
            $totalCredit = bcadd($totalCredit, $line['credit'], 4);
        }

        if (bccomp($totalDebit, $totalCredit, 4) !== 0) {
            throw new UnbalancedJournalException("Journal entry unbalanced. Debits: {$totalDebit}, Credits: {$totalCredit}");
        }
    }

    private function validateIdempotency(): void
    {
        if ($this->source_type && $this->source_id && $this->event_type) {
            $exists = JournalEntry::where('source_type', $this->source_type)
                ->where('source_id', $this->source_id)
                ->where('event_type', $this->event_type)
                ->exists();

            if ($exists) {
                throw new DuplicateJournalException();
            }
        }
    }

    /**
     * Strictly enforce line-by-line reversal.
     */
    public static function reverse(JournalEntry $originalEntry, string $eventSuffix = '_reversed', ?string $note = null): JournalEntry
    {
        $builder = self::create()
            ->setSource($originalEntry->source_type, $originalEntry->source_id)
            ->setSourceSubtype($originalEntry->source_subtype ?? '')
            ->setEventType($originalEntry->event_type . $eventSuffix)
            ->setReference($originalEntry->reference_no . '-REV')
            ->setDate(now()->toDateString())
            ->setNote($note ?? 'Reversal of ' . $originalEntry->reference_no);

        foreach ($originalEntry->lines as $line) {
            if ($line->debit > 0) {
                $builder->addCredit($line->accounting_account_id, $line->debit, 'Reversal: ' . $line->description);
            }
            if ($line->credit > 0) {
                $builder->addDebit($line->accounting_account_id, $line->credit, 'Reversal: ' . $line->description);
            }
        }

        $reversal = $builder->save();

        if (Schema::hasColumn('journal_entries', 'related_journal_entry_id')) {
            $reversal->related_journal_entry_id = $originalEntry->id;
            $reversal->save();
        }

        return $reversal;
    }
}
