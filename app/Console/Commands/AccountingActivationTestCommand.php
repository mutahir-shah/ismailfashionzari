<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AccountingConfig;
use App\Models\AccountingActivationSession;
use App\Models\JournalEntry;
use App\Services\AccountingActivationService;

class AccountingActivationTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:accounting-activation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifies the accounting activation flow and state.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting Accounting Activation Test...');
        
        $service = app(AccountingActivationService::class);
        $userId = 1; // System user

        // Step 1: Ensure initial state
        $this->info('Resetting accounting state...');
        try {
            $service->reset();
        } catch (\Exception $e) {
            // Already reset
        }
        
        $config = AccountingConfig::firstOrCreate(['id' => 1]);
        if ($config->enabled) {
            $this->error('Failed: Accounting config should be disabled after reset.');
            return 1;
        }

        // Step 2: Check requires existing mode
        $requiresExistingMode = $service->requiresExistingBusinessMode();
        $this->info('Requires Existing Mode: ' . ($requiresExistingMode ? 'Yes' : 'No'));

        $mode = $requiresExistingMode ? 'existing_business' : 'new_business';

        // Step 3: Activate
        $this->info("Activating in mode: $mode");
        try {
            $service->activate($mode, $userId);
        } catch (\Exception $e) {
            $this->error('Activation failed: ' . $e->getMessage());
            return 1;
        }

        $config->refresh();
        if (!$config->enabled) {
            $this->error('Failed: Config is not enabled after activation.');
            return 1;
        }

        if ($config->activation_mode !== $mode) {
            $this->error("Failed: Expected mode $mode but got {$config->activation_mode}");
            return 1;
        }

        // Step 4: Verify session audit
        $session = AccountingActivationSession::orderBy('id', 'desc')->first();
        if (!$session || $session->mode !== $mode) {
            $this->error('Failed: Activation session log not found or mismatched mode.');
            return 1;
        }

        // Step 5: Verify Opening Balance Journal (if existing business)
        if ($mode === 'existing_business') {
            $journal = JournalEntry::where('event_type', 'opening_balance')->first();
            
            if (!$journal) {
                $this->error('Failed: Opening balance journal not found.');
                return 1;
            }

            if ($config->opening_journal_entry_id !== $journal->id) {
                $this->error('Failed: Opening journal ID does not match config.');
                return 1;
            }

            // Check if journal balances (Total Debits == Total Credits)
            $totalDebit = $journal->lines()->sum('debit');
            $totalCredit = $journal->lines()->sum('credit');

            if (round($totalDebit, 4) !== round($totalCredit, 4)) {
                $this->error("Failed: Opening journal does not balance. Debits: $totalDebit, Credits: $totalCredit");
                return 1;
            }
            
            $this->info("Opening journal balances! Debits: $totalDebit, Credits: $totalCredit");
        }

        $this->info('Test passed successfully.');
        return 0;
    }
}
