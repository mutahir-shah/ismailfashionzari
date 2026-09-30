<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Demo\GoldenDemoValidator;
use Illuminate\Support\Facades\File;
use Throwable;
use App\Services\Demo\GoldenDemoPhase15Service;
use App\Services\Demo\GoldenDemoPhase16Service;
use App\Services\Demo\GoldenDemoPhase17Service;
use App\Services\Demo\GoldenDemoManifestService;

class SaleProDemoValidateCommand extends Command
{
    protected $signature = 'salepro:demo-validate {--report : Generate detailed report}';
    protected $description = 'Validate Golden Demo Scenario integrity and audit metrics';

    public function handle(GoldenDemoValidator $validator, GoldenDemoPhase15Service $phase15, GoldenDemoPhase16Service $phase16, GoldenDemoPhase17Service $phase17, GoldenDemoManifestService $manifestService): int
    {
        $this->info("==================================================");
        $this->info("  SalePro Golden Demo Scenario Validator");
        $this->info("==================================================\n");

        $manifestPath = $manifestService->path();
        if (!File::exists($manifestPath)) {
            $this->error("No Golden Manifest found at: " . $manifestPath);
            $this->error("Run 'php artisan salepro:demo-build' first.");
            return 1;
        }

        $manifest = json_decode(File::get($manifestPath), true);
        $baseline = $manifest['baseline'];
        $mastersCreated = $manifest['masters_created'];
        $cashRegisterData = $manifest['cash_register'];

        if (isset($manifest['phase16'], $manifest['phase15_checkpoint'])) {
            $phase16Result = $phase16->validate($manifest['phase15_checkpoint'], $manifest['phase16']);
            if (($phase16Result['ready_for_final_certification_phase'] ?? 'NO') !== 'YES') {
                $this->line(json_encode($phase16Result, JSON_PRETTY_PRINT));
                return 1;
            }
            $result = $phase17->certify();
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
            return $result['passed'] ? 0 : 1;
        } elseif (isset($manifest['phase15'], $manifest['phase14_checkpoint'])) {
            $result = $phase15->validate($manifest['phase14_checkpoint'], $manifest['phase15']);
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
            return $result['passed'] ? 0 : 1;
        } elseif (isset($manifest['phase14'], $manifest['phase13_checkpoint'])) {
            $result = $validator->validatePhase14($manifest['phase13_checkpoint'], $manifest['phase14']);
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
            return $result['passed'] ? 0 : 1;
        } elseif (isset($manifest['restaurant_sales_created'])) {
            $result = $validator->validatePhase13($baseline, $manifest['purchases_created'], $manifest['payments_created'] ?? [], $manifest['transfers_created'], $manifest['sales_created'], $manifest['payment_mutations'] ?? [], $manifest['returns_created'] ?? [], $manifest['exchanges_created'] ?? [], $manifest['purchase_mutations'] ?? [], $manifest['purchase_returns_created'] ?? [], $manifest['restaurant_sales_created']);
        } elseif (isset($manifest['purchase_returns_created'])) {
            $result = $validator->validatePhase12($baseline, $manifest['purchases_created'], $manifest['payments_created'] ?? [], $manifest['transfers_created'], $manifest['sales_created'], $manifest['payment_mutations'] ?? [], $manifest['returns_created'] ?? [], $manifest['exchanges_created'] ?? [], $manifest['purchase_mutations'] ?? [], $manifest['purchase_returns_created']);
        } elseif (isset($manifest['purchase_mutations'])) {
            $result = $validator->validatePhase11($baseline, $manifest['purchases_created'], $manifest['payments_created'] ?? [], $manifest['transfers_created'], $manifest['sales_created'], $manifest['payment_mutations'] ?? [], $manifest['returns_created'] ?? [], $manifest['exchanges_created'] ?? [], $manifest['purchase_mutations']);
        } elseif (isset($manifest['exchanges_created'])) {
            $result = $validator->validatePhase10($baseline, $manifest['purchases_created'], $manifest['payments_created'] ?? [], $manifest['transfers_created'], $manifest['sales_created'], $manifest['payment_mutations'] ?? [], $manifest['returns_created'] ?? [], $manifest['exchanges_created']);
        } elseif (isset($manifest['returns_created'])) {
            $result = $validator->validatePhase9($baseline, $manifest['purchases_created'], $manifest['payments_created'] ?? [], $manifest['transfers_created'], $manifest['sales_created'], $manifest['payment_mutations'] ?? [], $manifest['returns_created']);
        } elseif (isset($manifest['payment_mutations'])) {
            $result = $validator->validatePhase8($baseline, $manifest['purchases_created'], $manifest['payments_created'] ?? [], $manifest['transfers_created'], $manifest['sales_created'], $manifest['payment_mutations']);
        } elseif (isset($manifest['sales_created'])) {
            $result = $validator->validatePhase7($baseline, $manifest['purchases_created'], $manifest['payments_created'] ?? [], $manifest['transfers_created'], $manifest['sales_created']);
        } elseif (isset($manifest['transfers_created'])) {
            $result = $validator->validatePhase5($baseline, $manifest['purchases_created'], $manifest['payments_created'] ?? [], $manifest['transfers_created']);
        } elseif (isset($manifest['purchases_created'])) {
            $result = $validator->validatePhase4($baseline, $manifest['purchases_created'], $manifest['payments_created'] ?? []);
        } else {
            $result = $validator->validatePhase3($baseline, $mastersCreated, $cashRegisterData);
        }

        $this->info("--- Baseline Metrics ---");
        $this->line(" Products Count:       " . $baseline['counts']['products']);
        $this->line(" Variants Count:       " . $baseline['counts']['variants']);
        $this->line(" Warehouses Count:     " . $baseline['counts']['warehouses']);
        $this->line(" Total Stock (Units):  " . $baseline['stock']['total_product_qty']);
        $this->line(" Customer Dues (A/R):  " . number_format($baseline['financial']['customer_operational_dues'], 2));
        $this->line(" Supplier Dues (A/P):  " . number_format($baseline['financial']['supplier_operational_dues'], 2));
        $this->line(" GL Debits / Credits:  " . number_format($baseline['accounting']['total_debits'], 2) . " / " . number_format($baseline['accounting']['total_credits'], 2));

        $this->info("\n--- Phase 13 Audit Summary ---");
        $this->line(" Sales Count:             " . ($result['sales_count'] ?? 0));
        $this->line(" Returns Count:           " . ($result['returns_count'] ?? 0));
        $this->line(" Exchanges Count:         " . ($result['exchanges_count'] ?? 0));
        $this->line(" Purchase Mutations Count: " . ($result['purchase_mutations_count'] ?? 0));
        $this->line(" Purchase Returns Count:   " . ($result['purchase_returns_count'] ?? 0));
        $this->line(" Restaurant Sales Count:   " . ($result['restaurant_sales_count'] ?? 0));
        $this->line(" Stock Reconciled:        " . ($result['stock_reconciliation']['reconciled'] ?? 'N/A'));
        $this->line(" Customer A/R Reconciled: " . ($result['customer_reconciliation']['reconciled'] ?? 'N/A'));
        $this->line(" Supplier A/P Reconciled: " . ($result['supplier_reconciliation']['reconciled'] ?? 'N/A'));
        $this->line(" Outstanding Supplier AP: $" . number_format($result['supplier_reconciliation']['total_operational_ap'] ?? 0, 2));
        $this->line(" Accounts Reconciled:     " . ($result['account_reconciliation']['reconciled'] ?? 'N/A'));
        $this->line(" Register Reconciled:     " . ($result['register_reconciliation']['reconciled'] ?? 'N/A'));
        $this->line(" GL Journal Reconciled:   " . ($result['accounting']['reconciled'] ?? 'N/A'));

        $this->info("\n==================================================");
        if ($result['passed']) {
            $this->info("  FINAL VERDICT: READY FOR BOM & PRODUCTION PHASE: YES");
            $this->info("==================================================\n");
            return 0;
        } else {
            $this->error("  FINAL VERDICT: READY FOR BOM & PRODUCTION PHASE: NO");
            $this->info("==================================================\n");
            return 1;
        }









    }
}
