<?php

namespace App\Services\Demo;

use App\Models\Account;
use App\Models\CashRegister;
use App\Models\JournalLine;
use App\Models\Product;
use App\Models\Product_Warehouse;
use App\Services\Domain\ProductionDomainService;
use App\Services\Domain\RecipeDomainService;
use Illuminate\Support\Facades\DB;
use Modules\Manufacturing\Entities\Production;
use RuntimeException;

class GoldenDemoPhase14Service
{
    public function __construct(
        private RecipeDomainService $recipes,
        private ProductionDomainService $productions,
        private GoldenDemoValidator $validator,
        private GoldenDemoManifestService $manifestService
    ) {}

    public function build(bool $force = false): array
    {
        GoldenDemoSafety::assertExactDemoDatabase();
        $manifest = $this->manifest();
        if (isset($manifest['phase13_checkpoint'])) {
            $this->assertCheckpointMatchesPhase13($manifest['phase13_checkpoint'], $manifest['phase13_validation'] ?? []);
        }
        if (($manifest['phase14_validation']['passed'] ?? false) === true) {
            return $manifest['phase14_validation'];
        }

        if (!($manifest['phase13_validation']['passed'] ?? false)) {
            throw new RuntimeException('Phase 13 certification is required before Phase 14.');
        }

        if (!isset($manifest['phase13_checkpoint'])) {
            $manifest['phase13_checkpoint'] = $this->checkpoint($manifest['phase13_validation']);
            $this->write($manifest);
        }
        $checkpoint = $manifest['phase13_checkpoint'];
        if ($this->checkpointHash($checkpoint) !== ($manifest['phase13_checkpoint_hash'] ?? null)) {
            if (isset($manifest['phase13_checkpoint_hash'])) {
                throw new RuntimeException('Immutable Phase 13 checkpoint drift detected.');
            }
            $manifest['phase13_checkpoint_hash'] = $this->checkpointHash($checkpoint);
            $this->write($manifest);
        }

        if (!isset($manifest['phase14'])) {
            $manifest['phase14'] = $this->initializePhase14($checkpoint);
            $this->write($manifest);
        }

        $state = $manifest['phase14'];
        $expected = $checkpoint['stock_tuple_map'];
        foreach ($state['scenarios'] ?? [] as $scenario) {
            if (($scenario['state'] ?? null) === 'created') {
                $this->applyExpected($expected, $scenario['movements']);
            }
        }

        foreach ($this->scenarioDefinitions($state) as $definition) {
            $reference = $definition['reference_no'];
            $existingState = $state['scenarios'][$reference] ?? null;
            if ($existingState) {
                continue;
            }

            $this->assertTupleState($expected);
            $production = $this->productions->create($definition['input'], $state['user_id']);
            $movements = $this->movementsFor($definition, $expected);
            $this->applyExpected($expected, $movements);

            if ($definition['delete_after_create']) {
                $this->productions->delete($production);
                $state['scenarios'][$reference] = [
                    'state' => 'deleted',
                    'production_id' => $production->id,
                    'movements' => $movements,
                    'net_stock_effect' => 0,
                ];
            } else {
                $state['scenarios'][$reference] = [
                    'state' => 'created',
                    'production_id' => $production->id,
                    'status' => (int) $production->status,
                    'movements' => $movements,
                    'total_material_cost' => (float) $production->total_cost,
                    'output_unit_cost' => (float) $production->total_cost / (float) $production->total_qty,
                ];
            }

            $manifest['phase14'] = $state;
            $this->write($manifest);
        }

        $validation = $this->validator->validatePhase14($checkpoint, $state);
        if (!$validation['passed']) {
            throw new RuntimeException('PHASE 14 VALIDATION FAILED: '.json_encode($validation));
        }

        $manifest['phase14'] = $state;
        $manifest['phase14_validation'] = $validation;
        $manifest['phases_completed'] = array_values(array_unique(array_merge($manifest['phases_completed'], ['Phase 14'])));
        $this->write($manifest);
        return $validation;
    }

    public function checkpoint(array $phase13Validation): array
    {
        $baseline = $this->validator->recordBaseline();
        $journalAudit = $this->validator->auditJournalIntegrity();
        $accounts = collect($baseline['financial']['payment_account_balances']);
        $find = fn (string $pattern) => (float) ($accounts->first(fn ($a) => str_contains(strtolower(($a['name'] ?? '').' '.($a['account_no'] ?? '')), strtolower($pattern)))['balance'] ?? 0);

        $certifiedSupplierAp = (float) ($phase13Validation['supplier_reconciliation']['total_operational_ap'] ?? NAN);
        if (is_nan($certifiedSupplierAp) || abs($certifiedSupplierAp - (float) $baseline['financial']['supplier_operational_dues']) >= .0001) {
            throw new RuntimeException('Phase 13 certified Supplier A/P disagrees with checkpoint capture semantics.');
        }

        $checkpoint = [
            'captured_at' => now()->toIso8601String(),
            'stock_tuple_map' => $this->tupleMap(),
            'company_stock' => (float) $baseline['stock']['total_product_qty'],
            'customer_ar' => (float) $baseline['financial']['customer_operational_dues'],
            'supplier_ap' => $certifiedSupplierAp,
            'cash' => $find('cash'),
            'bank' => $find('bank'),
            'card_pos' => $find('card'),
            'cash_register' => (float) (CashRegister::where('status', true)->value('cash_in_hand') ?? 0),
            'journal_count' => $journalAudit['journals_count'],
            'journal_line_count' => JournalLine::count(),
            'gl_debit' => (float) JournalLine::sum('debit'),
            'gl_credit' => (float) JournalLine::sum('credit'),
            'source_integrity_failures' => $journalAudit['source_integrity_failures_count'],
            'payment_account_balances' => $accounts->keyBy('id')->map(fn ($a) => (float) $a['balance'])->all(),
        ];
        $this->assertCheckpointMatchesPhase13($checkpoint, $phase13Validation);
        return $checkpoint;
    }

    public function assertCheckpointMatchesPhase13(array $checkpoint, array $phase13Validation): void
    {
        $certified = $phase13Validation['supplier_reconciliation']['total_operational_ap'] ?? null;
        if ($certified === null || abs((float) $checkpoint['supplier_ap'] - (float) $certified) >= .0001) {
            throw new RuntimeException('Immutable checkpoint Supplier A/P differs from Phase 13 certification.');
        }
        if (($phase13Validation['passed'] ?? false) !== true) {
            throw new RuntimeException('Phase 13 must be independently certified before checkpoint capture.');
        }
    }

    private function initializePhase14(array $checkpoint): array
    {
        $rows = Product_Warehouse::query()->join('products', 'products.id', '=', 'product_warehouse.product_id')
            ->whereNull('product_warehouse.variant_id')->where('product_warehouse.qty', '>=', 20)
            ->where('products.is_active', true)->whereNotIn('products.type', ['service', 'digital'])
            ->orderBy('product_warehouse.warehouse_id')->orderBy('products.code')
            ->select('products.*', 'product_warehouse.warehouse_id')->get();
        $warehouseGroup = $rows->groupBy('warehouse_id')->first(fn ($group) => $group->count() >= 6);
        if (!$warehouseGroup) {
            throw new RuntimeException('Insufficient deterministic non-variant stock for Phase 14.');
        }
        $selected = $warehouseGroup->take(6)->values();
        $unitId = (int) $selected[0]->unit_id;
        $boms = [
            'DEMO-BOM-001' => ['finished' => $selected[0], 'components' => [$selected[2], $selected[3]], 'qty' => [2, 1], 'wastage' => [5, 0]],
            'DEMO-BOM-002' => ['finished' => $selected[1], 'components' => [$selected[4], $selected[5]], 'qty' => [1, 2], 'wastage' => [0, 10]],
        ];
        $saved = [];
        foreach ($boms as $reference => $bom) {
            $finished = Product::findOrFail($bom['finished']->id);
            $snapshot = $this->recipes->snapshot($finished);
            $componentIds = collect($bom['components'])->pluck('id')->all();
            $componentCosts = collect($bom['components'])->pluck('cost')->map(fn ($v) => (float) $v)->all();
            $this->recipes->save($finished, [
                'product_list' => $componentIds,
                'product_qty' => $bom['qty'],
                'unit_price' => $componentCosts,
                'product_unit_cost' => $componentCosts,
                'combo_unit_id' => [$unitId, $unitId],
                'wastage_percent' => $bom['wastage'],
                'variant_id' => ['', ''],
            ]);
            $saved[$reference] = [
                'finished_product_id' => $finished->id,
                'finished_product_code' => $finished->code,
                'component_ids' => $componentIds,
                'component_codes' => collect($bom['components'])->pluck('code')->all(),
                'quantities' => $bom['qty'],
                'wastage_percent' => $bom['wastage'],
                'unit_ids' => [$unitId, $unitId],
                'cost_basis' => $componentCosts,
                'warehouse_id' => (int) $bom['finished']->warehouse_id,
                'original_recipe' => $snapshot,
            ];
        }

        return ['user_id' => (int) DB::table('users')->where('role_id', 1)->value('id'), 'boms' => $saved, 'scenarios' => []];
    }

    private function scenarioDefinitions(array $state): array
    {
        $make = function (string $ref, string $bomRef, int $status, float $output, bool $delete) use ($state) {
            $bom = $state['boms'][$bomRef];
            return ['reference_no' => $ref, 'bom' => $bom, 'delete_after_create' => $delete, 'input' => [
                'reference_no' => $ref, 'warehouse_id' => $bom['warehouse_id'], 'product_id' => $bom['finished_product_id'],
                'product_list' => $bom['component_ids'], 'product_qty' => $bom['quantities'], 'production_unit_ids' => $bom['unit_ids'],
                'unit_price' => $bom['cost_basis'], 'wastage_percent' => $bom['wastage_percent'], 'variant_id' => ['', ''],
                'total_qty' => $output, 'status' => $status, 'note' => $bomRef,
            ]];
        };
        return [
            $make('DEMO-PROD-001', 'DEMO-BOM-001', 1, 3, false),
            $make('DEMO-PROD-002', 'DEMO-BOM-002', 1, 2, false),
            $make('DEMO-PROD-003', 'DEMO-BOM-001', 0, 1, false),
            $make('DEMO-PROD-004', 'DEMO-BOM-002', 0, 1, true),
        ];
    }

    private function movementsFor(array $definition, array $expected): array
    {
        $bom = $definition['bom'];
        $components = $this->productions->componentMovements($bom['component_ids'], $bom['quantities'], $bom['unit_ids'], $bom['wastage_percent'], $bom['cost_basis']);
        $movements = [];
        foreach ($components as $component) {
            $key = $this->key($component['product_id'], null, $bom['warehouse_id']);
            $movements[] = ['tuple' => $key, 'expected_starting_qty' => (float) ($expected[$key] ?? 0), 'expected_movement' => -$component['consumed_qty'], 'expected_ending_qty' => (float) ($expected[$key] ?? 0) - $component['consumed_qty'], 'costing' => $component];
        }
        $finishedKey = $this->key($bom['finished_product_id'], null, $bom['warehouse_id']);
        $movements[] = ['tuple' => $finishedKey, 'expected_starting_qty' => (float) ($expected[$finishedKey] ?? 0), 'expected_movement' => (float) $definition['input']['total_qty'], 'expected_ending_qty' => (float) ($expected[$finishedKey] ?? 0) + (float) $definition['input']['total_qty'], 'finished' => true];
        return $movements;
    }

    private function applyExpected(array &$expected, array $movements): void { foreach ($movements as $m) { $expected[$m['tuple']] = (float) $m['expected_ending_qty']; } }
    private function assertTupleState(array $expected): void { foreach ($expected as $key => $qty) { if (abs(($this->tupleMap()[$key] ?? 0) - $qty) > .0001) throw new RuntimeException("Phase 14 pre-execution tuple drift: {$key}"); } }
    private function tupleMap(): array { return DB::table('product_warehouse')->orderBy('product_id')->orderBy('variant_id')->orderBy('warehouse_id')->get()->mapWithKeys(fn ($r) => [$this->key($r->product_id, $r->variant_id, $r->warehouse_id) => (float) $r->qty])->all(); }
    private function key($p, $v, $w): string { return $p.':'.($v ?: 0).':'.$w; }
    private function checkpointHash(array $checkpoint): string { $copy=$checkpoint; unset($copy['captured_at']); return hash('sha256', json_encode($copy)); }
    private function manifest(): array { return $this->manifestService->read(); }
    private function write(array $manifest): void { $this->manifestService->merge($manifest); }
}
