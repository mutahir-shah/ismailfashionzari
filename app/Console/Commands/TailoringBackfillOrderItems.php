<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Modules\Tailoring\Services\TailoringOrderItemService;
use Throwable;

class TailoringBackfillOrderItems extends Command
{
    protected $signature = 'tailoring:backfill-order-items {--chunk=200 : Number of orders to inspect per chunk}';

    protected $description = 'Ensure Tailoring orders have their legacy primary child item.';

    public function handle(TailoringOrderItemService $service): int
    {
        if (!Schema::hasTable('tailoring_orders')) {
            $this->warn(__('Tailoring order item backfill skipped: tailoring_orders table is missing.'));

            return self::SUCCESS;
        }

        if (!Schema::hasTable('tailoring_order_items')) {
            $this->warn(__('Tailoring order item backfill skipped: tailoring_order_items table is missing.'));

            return self::SUCCESS;
        }

        $chunkSize = max(1, (int) $this->option('chunk'));

        try {
            $result = $service->backfillMissingPrimaryItems($chunkSize);
            $materialResult = $service->backfillItemMaterials($chunkSize);
        } catch (Throwable $exception) {
            $this->error(__('Tailoring order item backfill failed.'));
            $this->line(__('Failures: :count', ['count' => 1]));

            return self::FAILURE;
        }

        $inspected = (int) ($result['eligible'] ?? 0);
        $created = (int) ($result['created'] ?? 0);
        $skipped = (int) ($result['duplicatesAvoided'] ?? 0);
        $assignedMaterials = (int) ($materialResult['assignedMaterials'] ?? 0);
        $copiedFabric = (int) ($materialResult['copiedFabric'] ?? 0);
        $ambiguous = (int) ($materialResult['ambiguous'] ?? 0);

        $this->info(__('Tailoring order item backfill completed.'));
        $this->line(__('Orders inspected: :count', ['count' => $inspected]));
        $this->line(__('Child items created: :count', ['count' => $created]));
        $this->line(__('Orders skipped because items already existed: :count', ['count' => $skipped]));
        $this->line(__('Materials assigned to primary items: :count', ['count' => $assignedMaterials]));
        $this->line(__('Fabric configurations copied to primary items: :count', ['count' => $copiedFabric]));
        $this->line(__('Ambiguous multi-piece orders needing manual material allocation: :count', ['count' => $ambiguous]));
        $this->line(__('Failures: :count', ['count' => 0]));

        return self::SUCCESS;
    }
}
