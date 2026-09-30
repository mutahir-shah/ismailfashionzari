<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Demo\GoldenDemoScenarioBuilder;
use Throwable;
use App\Services\Demo\GoldenDemoPhase14Service;
use App\Services\Demo\GoldenDemoPhase15Service;
use App\Services\Demo\GoldenDemoPhase16Service;
use Illuminate\Support\Facades\File;
use App\Services\Demo\GoldenDemoCatalogService;
use App\Services\Demo\GoldenDemoManifestService;

class SaleProDemoBuildCommand extends Command
{
    protected $signature = 'salepro:demo-build {--phase= : Build a specific next phase} {--force : Return existing certification without mutation} {--new-master : Start a new manifest on the explicitly allowed clean master database} {--reset-phase= : Explicit phase reset}';
    protected $description = 'Build deterministic Golden Demo Scenario (Phases 1-4 baseline, masters, register opening, purchases)';

    public function handle(GoldenDemoScenarioBuilder $builder, GoldenDemoPhase14Service $phase14, GoldenDemoPhase15Service $phase15, GoldenDemoPhase16Service $phase16, GoldenDemoCatalogService $catalog, GoldenDemoManifestService $manifestService): int
    {
        if ((bool) $this->option('force') && !(bool) $this->option('new-master') && !$this->option('phase') && !$this->option('reset-phase')) {
            $manifestPath = $manifestService->path();
            if (File::exists($manifestPath)) {
                $manifest = json_decode(File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
                if (isset($manifest['phase15_checkpoint'], $manifest['phase16'])) {
                    if (!isset($manifest['catalog_baseline'])) {
                        $this->error('Existing economic certification is not a Master Golden Demo: catalog baseline is absent.');
                        return 1;
                    }
                    $catalogResult = $catalog->validateCompleteness($manifest['catalog_baseline']);
                    if (!$catalogResult['passed']) {
                        $this->error('Existing Master Golden Demo catalog certification failed: '.json_encode($catalogResult['failures']));
                        return 1;
                    }
                    $existing = $phase16->validate($manifest['phase15_checkpoint'], $manifest['phase16']);
                    if (($existing['ready_for_final_certification_phase'] ?? 'NO') === 'YES') {
                        $this->info('Golden Demo is already at the immutable Phase-16 closed-register state.');
                        $this->info('NON-DESTRUCTIVE IDEMPOTENT --force: no records or manifest data were changed.');
                        $this->line(json_encode($existing, JSON_PRETTY_PRINT));
                        return 0;
                    }
                }
            }
        }

        if (in_array((string) $this->option('phase'), ['14', '15', '16'], true)) {
            $manifestPath = $manifestService->path();
            if (!File::exists($manifestPath)) {
                $this->error('Master Golden Demo manifest is missing.');
                return 1;
            }
            $manifest = json_decode(File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
            if (!isset($manifest['catalog_baseline'])) {
                $this->error('Master catalog baseline is missing; later phases are blocked.');
                return 1;
            }
            $catalogResult = $catalog->validateCompleteness($manifest['catalog_baseline']);
            if (!$catalogResult['passed']) {
                $this->error('Master catalog completeness failed before Phase '.$this->option('phase').': '.json_encode($catalogResult['failures']));
                return 1;
            }
        }

        if ((string) $this->option('reset-phase') === '16') {
            $this->error('Phase 16 reset is unsupported because canonical register closing is immutable.');
            return 1;
        }
        if ((string) $this->option('phase') === '16') {
            try {
                $result = $phase16->build((bool) $this->option('force'));
                $this->info('Phase 16 Cash Register Audit & Closing: PASSED');
                $this->line(json_encode($result, JSON_PRETTY_PRINT));
                return 0;
            } catch (Throwable $e) {
                $this->error('PHASE 16 BUILD FAILED: '.$e->getMessage());
                return 1;
            }
        }
        if ((string) $this->option('reset-phase') === '15') {
            $this->error('Phase 15 reset is unsupported because completed Repairs require canonical economic reversal and retained demo scenarios are permanent.');
            return 1;
        }
        if ((string) $this->option('phase') === '15') {
            try {
                $result = $phase15->build((bool) $this->option('force'));
                $this->info('Phase 15 Repairs / Service Jobs: PASSED');
                $this->line(json_encode($result, JSON_PRETTY_PRINT));
                return 0;
            } catch (Throwable $e) {
                $this->error('PHASE 15 BUILD FAILED: '.$e->getMessage());
                return 1;
            }
        }
        if ((string) $this->option('reset-phase') === '14') {
            $this->error('Phase 14 reset is unsupported because canonical completed Productions cannot be deleted.');
            return 1;
        }
        if ((string) $this->option('phase') === '14') {
            try {
                $result = $phase14->build((bool) $this->option('force'));
                $this->info('Phase 14 Recipes / BOM & Production: PASSED');
                $this->line(json_encode($result, JSON_PRETTY_PRINT));
                return 0;
            } catch (Throwable $e) {
                $this->error('PHASE 14 BUILD FAILED: '.$e->getMessage());
                return 1;
            }
        }
        $this->info("==================================================");
        $this->info("  SalePro Golden Demo Scenario Builder (Phases 1-13)");
        $this->info("==================================================\n");

        $force = (bool) $this->option('force') || (bool) $this->option('new-master');

        try {
            $result = $builder->buildPhases1To13($force);

            $this->info("✔ Phase 1: Baseline Recorded");
            $this->info("✔ Phase 2: Supporting Masters Created");
            $this->info("✔ Phase 3: Cash Register Opened");
            $this->info("✔ Phase 4: Purchases & Purchase Payments Created");
            $this->info("✔ Phase 5: Warehouse Transfers Created");
            $this->info("✔ Phase 6: Stock Adjustments Created");
            $this->info("✔ Phase 7: Sales & POS Sales Created");
            $this->info("✔ Phase 8: Sale Payment Lifecycle & Mutations Created");
            $this->info("✔ Phase 9: Sale Returns Created");
            $this->info("✔ Phase 10: Product Exchanges Created");
            $this->info("✔ Phase 11: Purchase Payment Lifecycle & Mutations Created");
            $this->info("✔ Phase 12: Purchase Returns Created");
            $this->info("✔ Phase 13: Restaurant Orders, Modifiers, Tables & KDS Created");
            $this->info("✔ Phase 13 Hard Validation Gate: PASSED");
            $this->info("\nManifest Path: " . $manifestService->path());
            $this->info("Ready for BOM & Production Phase: YES\n");

            return 0;

        } catch (Throwable $e) {
            $this->error("\n❌ GOLDEN DEMO BUILD FAILED: " . $e->getMessage());
            return 1;
        }




    }

}
