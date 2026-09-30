<?php

namespace App\Console\Commands;

use App\Services\PaymentAccountMappingRepairService;
use Illuminate\Console\Command;

class RepairPaymentAccountMappingsCommand extends Command
{
    protected $signature = 'accounting:repair-payment-account-mappings {--apply : Create mappings for unambiguously missing active payment accounts}';

    protected $description = 'Audit or repair missing legacy payment-account mappings without overwriting existing mappings.';

    public function handle(PaymentAccountMappingRepairService $repair): int
    {
        $apply = (bool) $this->option('apply');
        $result = $apply ? $repair->apply() : $repair->inspect();

        if (!$result['authoritative']) {
            $this->error('Double-entry accounting is not authoritative; no mappings were changed.');
            return self::FAILURE;
        }

        foreach ($result['ambiguous'] as $item) {
            $this->warn("AMBIGUOUS account {$item['account_id']} [{$item['account_name']}] has invalid mapping {$item['mapping_id']}; left unchanged.");
        }

        foreach ($result['missing'] as $item) {
            $this->line("MISSING account {$item['account_id']} [{$item['account_name']}]");
        }

        if ($apply && !$result['schema_safe']) {
                $this->error('Repair refused: required AUTO_INCREMENT metadata is missing from: ' . implode(', ', $result['schema_issues']) . '.');
                $this->error('Restore the database schema before creating accounting records. No mappings were changed.');
                return self::FAILURE;
        }

        foreach ($result['repaired'] ?? [] as $item) {
            $this->info("CREATED account {$item['account_id']} -> {$item['accounting_code']} [{$item['accounting_name']}]");
        }
        foreach ($result['errors'] ?? [] as $error) $this->error($error);

        $this->newLine();
        $this->info(sprintf(
            '%s: %d missing, %d invalid existing, %d changed.',
            $apply ? 'Apply complete' : 'Dry run complete',
            $result['missing_count'],
            $result['invalid_count'],
            $result['repaired_count'] ?? 0
        ));

        if (!$apply && $result['missing_count'] > 0) {
            $this->comment('Run again with --apply to create only the missing mappings.');
        }

        return !empty($result['errors']) ? self::FAILURE : self::SUCCESS;
    }
}
