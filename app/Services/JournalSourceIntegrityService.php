<?php

namespace App\Services;

use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\SoftDeletes;
use Throwable;

class JournalSourceIntegrityService
{
    public const ACTIVE = 'active';
    public const SOFT_DELETED_REVERSED = 'soft_deleted_reversed';
    public const HISTORICAL_ABSENT_REVERSED = 'historical_absent_reversed';
    public const LIFECYCLE_DEFECT = 'lifecycle_defect';
    public const ORPHAN = 'orphan';

    public function classify(JournalEntry $journal): array
    {
        $class = $journal->source_type;
        if ($class === 'activation') {
            $config = \App\Models\AccountingConfig::find($journal->source_id);

            if ($config && (int) $config->opening_journal_entry_id === (int) $journal->id) {
                return $this->result(self::ACTIVE, 'active_accounting_activation');
            }
        }

        if (!$class || !class_exists($class)) {
            if ($this->isSupportedHistoricalAbsence($journal)) {
                return $this->result(self::HISTORICAL_ABSENT_REVERSED, 'non_model_or_removed_source_with_supported_reversal');
            }

            return $this->result(self::ORPHAN, 'missing_source_class');
        }

        try {
            $model = new $class();
            $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($model), true);
            $source = $usesSoftDeletes
                ? $class::withTrashed()->whereKey($journal->source_id)->first()
                : $class::whereKey($journal->source_id)->first();

            if ($source) {
                if (!$usesSoftDeletes || !$source->trashed()) {
                    return $this->result(self::ACTIVE, 'active_source');
                }

                return $this->hasValidReversalLifecycle($journal)
                    ? $this->result(self::SOFT_DELETED_REVERSED, 'soft_deleted_source_with_reversal')
                    : $this->result(self::LIFECYCLE_DEFECT, 'soft_deleted_source_without_reversal');
            }

            if ($this->isSupportedHistoricalAbsence($journal)) {
                return $this->result(self::HISTORICAL_ABSENT_REVERSED, 'physically_absent_supported_reversal');
            }

            return $this->result(self::ORPHAN, 'source_row_missing');
        } catch (Throwable $e) {
            return $this->result(self::ORPHAN, 'source_resolution_failed');
        }
    }

    public function isFailure(array $classification): bool
    {
        return in_array($classification['status'], [self::LIFECYCLE_DEFECT, self::ORPHAN], true);
    }

    private function hasValidReversalLifecycle(JournalEntry $journal): bool
    {
        if ($journal->related_journal_entry_id) {
            return JournalEntry::whereKey($journal->related_journal_entry_id)
                ->where('source_type', $journal->source_type)
                ->where('source_id', $journal->source_id)
                ->exists();
        }

        return JournalEntry::where('related_journal_entry_id', $journal->id)
            ->where('source_type', $journal->source_type)
            ->where('source_id', $journal->source_id)
            ->exists();
    }

    private function isSupportedHistoricalAbsence(JournalEntry $journal): bool
    {
        if (!in_array($journal->source_type, [
            \App\Models\Sale::class,
            \App\Models\Payment::class,
            'App\\Models\\AdvancePayment',
            'activation',
        ], true)) {
            return false;
        }

        return $this->hasValidReversalLifecycle($journal);
    }

    private function result(string $status, string $reason): array
    {
        return ['status' => $status, 'reason' => $reason];
    }
}
