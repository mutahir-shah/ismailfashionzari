<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\PeriodicInventoryClose;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PeriodicInventoryCloseService
{
    public function __construct(
        private InventoryValuationService $valuation,
        private AccountingService $accounting
    ) {}

    public function preview(string $periodStart, string $periodEnd): array
    {
        $periodEndDate = Carbon::parse($periodEnd)->toDateString();
        $today = Carbon::today()->toDateString();
        $valuation = $this->valuation->currentValuation();
        $inventoryId = $this->accounting->getRoleAccountId(AccountingService::ROLE_INVENTORY);
        $book = (float) DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.accounting_account_id', $inventoryId)
            ->whereDate('journal_entries.entry_date', '<=', $periodEndDate)
            ->selectRaw('COALESCE(SUM(journal_lines.debit - journal_lines.credit), 0) balance')
            ->value('balance');

        $active = PeriodicInventoryClose::where('period_start', $periodStart)
            ->where('period_end', $periodEndDate)->where('scope', 'company')
            ->whereIn('status', ['posted', 'zero'])->latest('id')->first();
        $blocking = collect();
        if ($periodEndDate !== $today) $blocking->push('historical_cutoff_unsupported');
        if ($valuation['missing_cost']->isNotEmpty()) $blocking->push('missing_cost');
        if ($valuation['negative_stock']->isNotEmpty()) $blocking->push('negative_stock');
        if ($valuation['quantity_mismatches']->isNotEmpty()) $blocking->push('quantity_mismatch');
        if (AccountingPeriod::where('start_date', '<=', $periodEndDate)->where('end_date', '>=', $periodEndDate)->where('is_closed', true)->exists()) {
            $blocking->push('accounting_period_closed');
        }

        return [
            'period_start' => Carbon::parse($periodStart)->toDateString(),
            'period_end' => $periodEndDate,
            'book_inventory' => round($book, 4),
            'operational_inventory' => $valuation['value'],
            'adjustment' => round($book - $valuation['value'], 4),
            'valuation' => $valuation,
            'blocking_errors' => $blocking,
            'can_post' => $blocking->isEmpty() && !$active,
            'active_close' => $active,
            'scope' => 'company',
            'calculated_at' => now(),
        ];
    }

    public function post(string $periodStart, string $periodEnd, ?int $userId = null): PeriodicInventoryClose
    {
        return DB::transaction(function () use ($periodStart, $periodEnd, $userId) {
            $existing = PeriodicInventoryClose::where('period_start', $periodStart)->where('period_end', $periodEnd)
                ->where('scope', 'company')->whereIn('status', ['posted', 'zero'])->lockForUpdate()->first();
            if ($existing) return $existing;

            $preview = $this->preview($periodStart, $periodEnd);
            if ($preview['blocking_errors']->isNotEmpty()) {
                throw new RuntimeException('inventory_close_blocked:' . $preview['blocking_errors']->implode(','));
            }

            $close = PeriodicInventoryClose::create([
                'period_start' => $preview['period_start'], 'period_end' => $preview['period_end'],
                'posting_date' => $preview['period_end'], 'scope' => 'company',
                'active_key' => 'company:' . $preview['period_start'] . ':' . $preview['period_end'],
                'status' => abs($preview['adjustment']) < 0.0001 ? 'zero' : 'posted',
                'book_inventory' => $preview['book_inventory'],
                'operational_inventory' => $preview['operational_inventory'],
                'adjustment' => $preview['adjustment'],
                'calculation_basis' => [
                    'method' => $preview['valuation']['method'],
                    'warehouse_totals' => $preview['valuation']['warehouses']->values()->all(),
                    'item_count' => $preview['valuation']['items']->count(),
                    'items' => $preview['valuation']['items']->map(fn ($item) => [
                        'product_id' => (int) $item->product_id,
                        'variant_id' => (int) $item->variant_id,
                        'warehouse_id' => (int) $item->warehouse_id,
                        'quantity' => (float) $item->qty,
                        'unit_cost' => (float) $item->cost,
                        'cost_source' => $item->source,
                        'value' => round((float) $item->value, 4),
                    ])->values()->all(),
                ],
                'created_by' => $userId, 'calculated_at' => now(), 'posted_at' => now(),
            ]);

            if (abs($preview['adjustment']) >= 0.0001) {
                $inventory = $this->accounting->getRoleAccountId(AccountingService::ROLE_INVENTORY);
                $cogs = $this->accounting->getRoleAccountId(AccountingService::ROLE_COST_OF_GOODS_SOLD);
                $builder = JournalBuilder::create()->setReference('INV-CLOSE-' . $close->id)
                    ->setDate($preview['period_end'])->setSource(PeriodicInventoryClose::class, $close->id)
                    ->setEventType('periodic_inventory_close')
                    ->setNote('Periodic inventory close ' . $preview['period_start'] . ' to ' . $preview['period_end']);
                if ($preview['adjustment'] > 0) {
                    $builder->addDebit($cogs, $preview['adjustment'], 'Periodic COGS')
                        ->addCredit($inventory, $preview['adjustment'], 'Ending inventory adjustment');
                } else {
                    $amount = abs($preview['adjustment']);
                    $builder->addDebit($inventory, $amount, 'Ending inventory adjustment')
                        ->addCredit($cogs, $amount, 'Periodic COGS true-up');
                }
                $journal = $builder->save();
                $close->update(['journal_entry_id' => $journal->id]);
            }

            return $close->fresh();
        });
    }

    public function reverse(PeriodicInventoryClose $close): PeriodicInventoryClose
    {
        return DB::transaction(function () use ($close) {
            $close = PeriodicInventoryClose::lockForUpdate()->findOrFail($close->id);
            if ($close->status === 'reversed') return $close;
            $reversal = $close->journal_entry_id
                ? JournalBuilder::reverse($close->journalEntry, '_reversed', 'Periodic inventory close reversal')
                : null;
            $close->update([
                'status' => 'reversed', 'active_key' => null,
                'reversal_journal_entry_id' => $reversal?->id, 'reversed_at' => now(),
            ]);
            return $close->fresh();
        });
    }

    public function recalculate(PeriodicInventoryClose $close, ?int $userId = null): PeriodicInventoryClose
    {
        return DB::transaction(function () use ($close, $userId) {
            $start = $close->period_start->toDateString();
            $end = $close->period_end->toDateString();
            $this->reverse($close);
            return $this->post($start, $end, $userId);
        });
    }
}
