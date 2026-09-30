<?php

namespace App\Services;

use App\Models\JournalEntry;

class AccountingResult
{
    public const POSTED = 'posted';
    public const SKIPPED_LEGACY_MODE = 'skipped_legacy_mode';
    public const SKIPPED_PRE_ACTIVATION = 'skipped_pre_activation';
    public const FAILED = 'failed';

    public bool $success;
    public ?string $error;
    public ?JournalEntry $journalEntry;
    public string $outcome;

    public function __construct(bool $success, ?string $error = null, ?JournalEntry $journalEntry = null, ?string $outcome = null)
    {
        $this->success = $success;
        $this->error = $error;
        $this->journalEntry = $journalEntry;
        $this->outcome = $outcome ?? ($success ? ($journalEntry ? self::POSTED : self::SKIPPED_PRE_ACTIVATION) : self::FAILED);
    }

    public static function success(?JournalEntry $journalEntry = null): self
    {
        return new self(true, null, $journalEntry, $journalEntry ? self::POSTED : self::SKIPPED_PRE_ACTIVATION);
    }

    public static function skippedLegacyMode(): self
    {
        return new self(true, null, null, self::SKIPPED_LEGACY_MODE);
    }

    public static function skippedPreActivation(): self
    {
        return new self(true, null, null, self::SKIPPED_PRE_ACTIVATION);
    }

    public static function failed(string $error): self
    {
        return new self(false, $error, null);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isPosted(): bool
    {
        return $this->outcome === self::POSTED && $this->journalEntry !== null;
    }

    public function isSkipped(): bool
    {
        return in_array($this->outcome, [self::SKIPPED_LEGACY_MODE, self::SKIPPED_PRE_ACTIVATION], true);
    }

    public function getMessage(): ?string
    {
        return $this->error;
    }
}
