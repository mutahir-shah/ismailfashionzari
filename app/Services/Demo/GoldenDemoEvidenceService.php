<?php

namespace App\Services\Demo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Produces expected economic ledgers from immutable baseline evidence and
 * canonical transaction sources at each successful build boundary.
 */
class GoldenDemoEvidenceService
{
    public function capture(array $manifest): array
    {
        $stock = $this->stockLedger($manifest);
        $customerAr = $this->customerArLedger($manifest);
        $supplierAp = $this->supplierApLedger($manifest);
        $paymentAccounts = $this->paymentAccountLedger($manifest);
        $accounting = $this->accountingExpectationSet();
        $register = data_get($manifest, 'phase16_validation.register', []);
        $phase = collect($manifest['phases_completed'] ?? [])->last();

        $complete = isset($manifest['baseline']['stock']['tuple_map'])
            && isset($manifest['baseline']['financial']['customer_opening_map'])
            && isset($manifest['baseline']['financial']['supplier_opening_map'])
            && isset($stock['expected_tuple_map'], $customerAr['expected_by_customer'], $supplierAp['expected_by_supplier'])
            && isset($paymentAccounts['expected_by_account'])
            && is_array($accounting);

        return [
            'source_ledgers' => [
                'stock' => $stock,
                'customer_ar' => $customerAr,
                'supplier_ap' => $supplierAp,
                'payment_accounts' => $paymentAccounts,
                'register' => [
                    'opening' => data_get($manifest, 'cash_register.cash_in_hand'),
                    'trace' => $register['trace'] ?? [],
                    'expected_close' => $register['expected'] ?? null,
                    'actual_close' => $register['counted'] ?? null,
                    'status' => $register['status'] ?? null,
                ],
                'accounting' => $accounting,
            ],
            'accounting_expectation_set' => $accounting,
            'phase_evidence_snapshots' => $phase ? [
                $phase => [
                    'captured_at' => now()->toIso8601String(),
                    'stock_source_count' => count($stock['movements']),
                    'customer_ar_source_count' => count($customerAr['movements']),
                    'supplier_ap_source_count' => count($supplierAp['movements']),
                    'payment_account_source_count' => count($paymentAccounts['movements']),
                    'accounting_source_count' => count($accounting),
                    'evidence_hash' => hash('sha256', json_encode([
                        $stock, $customerAr, $supplierAp, $paymentAccounts, $accounting,
                    ], JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION)),
                ],
            ] : [],
            'phase17_expected_state' => [
                'independent_reconstruction_completed' => $complete,
                'stock_product_qty' => $stock['expected_product_qty'],
                'stock_warehouse_qty' => array_sum($stock['expected_tuple_map']),
                'stock_tuple_map' => $stock['expected_tuple_map'],
                'customer_ar' => array_sum($customerAr['expected_by_customer']),
                'customer_ar_by_customer' => $customerAr['expected_by_customer'],
                'supplier_ap' => array_sum($supplierAp['expected_by_supplier']),
                'supplier_ap_by_supplier' => $supplierAp['expected_by_supplier'],
                'payment_accounts' => $paymentAccounts['expected_by_account'],
                'register_expected_close' => $register['expected'] ?? null,
                'accounting_sources' => $accounting,
            ],
        ];
    }

    private function stockLedger(array $manifest): array
    {
        $movements = [];
        $add = function (string $source, $id, string $reference, $product, $variant, $warehouse, float $qty) use (&$movements): void {
            $movements[] = [
                'source_type' => $source,
                'source_id' => (int) $id,
                'reference' => $reference,
                'tuple' => $this->tuple($product, $variant, $warehouse),
                'signed_qty' => round($qty, 6),
            ];
        };

        if (Schema::hasTable('purchases')) {
            $rows = DB::table('purchases as p')->join('product_purchases as pp', 'pp.purchase_id', '=', 'p.id')
                ->where('p.reference_no', 'like', 'DEMO-%')
                ->whereNull('p.deleted_at')
                ->select('p.id', 'p.reference_no', 'p.warehouse_id', 'pp.product_id', 'pp.variant_id', 'pp.qty', 'pp.recieved', 'pp.return_qty', 'pp.purchase_unit_id')
                ->orderBy('p.id')->orderBy('pp.id')->get();
            foreach ($rows as $row) {
                $unit = DB::table('units')->where('id', $row->purchase_unit_id)->first();
                $received = (float) $row->recieved;
                $baseQuantity = !$unit || (float) $unit->operation_value == 0
                    ? $received
                    : ($unit->operator === '*'
                        ? $received * (float) $unit->operation_value
                        : $received / (float) $unit->operation_value);
                $add('purchase', $row->id, $row->reference_no, $row->product_id, $row->variant_id, $row->warehouse_id, $baseQuantity);
                if ((float) $row->return_qty > 0) {
                    $add('purchase_return', $row->id, $row->reference_no, $row->product_id, $row->variant_id, $row->warehouse_id, -(float) $row->return_qty);
                }
            }
        }

        if (Schema::hasTable('transfers')) {
            $rows = DB::table('transfers as t')->join('product_transfer as pt', 'pt.transfer_id', '=', 't.id')
                ->where('t.reference_no', 'like', 'DEMO-%')
                ->select('t.id', 't.reference_no', 't.from_warehouse_id', 't.to_warehouse_id', 'pt.product_id', 'pt.variant_id', 'pt.qty')
                ->orderBy('t.id')->orderBy('pt.id')->get();
            foreach ($rows as $row) {
                $add('transfer_out', $row->id, $row->reference_no, $row->product_id, $row->variant_id, $row->from_warehouse_id, -(float) $row->qty);
                $add('transfer_in', $row->id, $row->reference_no, $row->product_id, $row->variant_id, $row->to_warehouse_id, (float) $row->qty);
            }
        }

        if (Schema::hasTable('adjustments')) {
            $rows = DB::table('adjustments as a')->join('product_adjustments as pa', 'pa.adjustment_id', '=', 'a.id')
                ->where('a.reference_no', 'like', 'DEMO-%')
                ->select('a.id', 'a.reference_no', 'a.warehouse_id', 'pa.product_id', 'pa.variant_id', 'pa.qty', 'pa.action')
                ->orderBy('a.id')->orderBy('pa.id')->get();
            foreach ($rows as $row) {
                $add('adjustment', $row->id, $row->reference_no, $row->product_id, $row->variant_id, $row->warehouse_id, ($row->action === '-' ? -1 : 1) * (float) $row->qty);
            }
        }

        if (Schema::hasTable('sales')) {
            $rows = DB::table('sales as s')->join('product_sales as ps', 'ps.sale_id', '=', 's.id')
                ->join('products as stock_product', 'stock_product.id', '=', 'ps.product_id')
                ->where('s.reference_no', 'like', 'DEMO-%')
                ->whereNotIn('stock_product.type', ['service', 'digital'])
                ->select('s.id', 's.reference_no', 's.warehouse_id', 's.deleted_at', 'ps.product_id', 'ps.variant_id', 'ps.qty')
                ->orderBy('s.id')->orderBy('ps.id')->get();
            foreach ($rows as $row) {
                $add('sale', $row->id, $row->reference_no, $row->product_id, $row->variant_id, $row->warehouse_id, -(float) $row->qty);
                if ($row->deleted_at !== null) {
                    $add('sale_cancellation_restoration', $row->id, $row->reference_no, $row->product_id, $row->variant_id, $row->warehouse_id, (float) $row->qty);
                }
            }
        }

        if (Schema::hasTable('returns')) {
            $rows = DB::table('returns as r')->join('product_returns as pr', 'pr.return_id', '=', 'r.id')
                ->where('r.reference_no', 'like', 'DEMO-%')
                ->select('r.id', 'r.reference_no', 'r.warehouse_id', 'pr.product_id', 'pr.variant_id', 'pr.qty')
                ->orderBy('r.id')->orderBy('pr.id')->get();
            foreach ($rows as $row) {
                $add('sale_return', $row->id, $row->reference_no, $row->product_id, $row->variant_id, $row->warehouse_id, (float) $row->qty);
            }
        }

        if (Schema::hasTable('sale_exchanges')) {
            $rows = DB::table('sale_exchanges as e')->join('product_exchanges as pe', 'pe.exchange_id', '=', 'e.id')
                ->join('products as stock_product', 'stock_product.id', '=', 'pe.product_id')
                ->where('e.reference_no', 'like', 'DEMO-%')
                ->whereNotIn('stock_product.type', ['service', 'digital'])
                ->select('e.id', 'e.reference_no', 'e.warehouse_id', 'pe.product_id', 'pe.variant_id', 'pe.qty', 'pe.type')
                ->orderBy('e.id')->orderBy('pe.id')->get();
            foreach ($rows as $row) {
                $add('exchange_'.$row->type, $row->id, $row->reference_no, $row->product_id, $row->variant_id, $row->warehouse_id, ($row->type === 'returned' ? 1 : -1) * (float) $row->qty);
            }
        }

        foreach (data_get($manifest, 'phase14.scenarios', []) as $scenario) {
            if (($scenario['state'] ?? null) !== 'created') continue;
            foreach ($scenario['movements'] ?? [] as $movement) {
                [$product, $variant, $warehouse] = array_map('intval', explode(':', $movement['tuple']));
                $add('production', $scenario['id'] ?? 0, $scenario['reference_no'] ?? 'production', $product, $variant ?: null, $warehouse, (float) $movement['expected_movement']);
            }
        }

        $expected = collect(data_get($manifest, 'baseline.stock.tuple_map', []))->map(fn ($qty) => (float) $qty)->all();
        foreach ($movements as $movement) {
            $expected[$movement['tuple']] = round((float) ($expected[$movement['tuple']] ?? 0) + $movement['signed_qty'], 6);
        }
        ksort($expected);

        return [
            'baseline_tuple_map' => data_get($manifest, 'baseline.stock.tuple_map', []),
            'movements' => $movements,
            'expected_tuple_map' => $expected,
            'expected_product_qty' => round((float) data_get($manifest, 'baseline.stock.total_product_qty', 0) + collect($movements)->sum('signed_qty'), 6),
        ];
    }

    private function customerArLedger(array $manifest): array
    {
        $expected = collect(data_get($manifest, 'baseline.financial.customer_opening_map', []))->map(fn ($v) => (float) $v)->all();
        $movements = [];
        $add = function ($customer, string $source, $id, string $reference, float $amount) use (&$expected, &$movements): void {
            $key = (string) $customer;
            $expected[$key] = round((float) ($expected[$key] ?? 0) + $amount, 6);
            $movements[] = ['customer_id' => (int) $customer, 'source_type' => $source, 'source_id' => (int) $id, 'reference' => $reference, 'signed_amount' => round($amount, 6)];
        };
        $sales = DB::table('sales')->where('reference_no', 'like', 'DEMO-%')->whereNull('deleted_at')->orderBy('id')->get();
        foreach ($sales as $sale) $add($sale->customer_id, 'sale', $sale->id, $sale->reference_no, (float) $sale->grand_total);
        $payments = DB::table('payments as p')->join('sales as s', 's.id', '=', 'p.sale_id')
            ->where('s.reference_no', 'like', 'DEMO-%')->whereNull('s.deleted_at')->whereNull('p.return_id')
            ->select('p.*', 's.customer_id', 's.reference_no')->orderBy('p.id')->get();
        foreach ($payments as $payment) $add($payment->customer_id, 'sale_payment', $payment->id, $payment->reference_no, -(float) $payment->amount / ((float) $payment->exchange_rate ?: 1));
        $returns = DB::table('returns')->where('reference_no', 'like', 'DEMO-%')->orderBy('id')->get();
        foreach ($returns as $return) {
            $refund = (float) DB::table('payments')->where('return_id', $return->id)->whereNotNull('sale_id')->sum('amount');
            $add($return->customer_id, 'sale_return', $return->id, $return->reference_no, -($refund ?: (float) $return->grand_total));
        }
        ksort($expected);
        return ['baseline_by_customer' => data_get($manifest, 'baseline.financial.customer_opening_map', []), 'movements' => $movements, 'expected_by_customer' => $expected];
    }

    private function supplierApLedger(array $manifest): array
    {
        $expected = collect(data_get($manifest, 'baseline.financial.supplier_opening_map', []))->map(fn ($v) => (float) $v)->all();
        $movements = [];
        $add = function ($supplier, string $source, $id, string $reference, float $amount) use (&$expected, &$movements): void {
            $key = (string) $supplier;
            $expected[$key] = round((float) ($expected[$key] ?? 0) + $amount, 6);
            $movements[] = ['supplier_id' => (int) $supplier, 'source_type' => $source, 'source_id' => (int) $id, 'reference' => $reference, 'signed_amount' => round($amount, 6)];
        };
        $purchases = DB::table('purchases')->where('reference_no', 'like', 'DEMO-%')->whereNull('deleted_at')->orderBy('id')->get();
        foreach ($purchases as $purchase) $add($purchase->supplier_id, 'purchase', $purchase->id, $purchase->reference_no, (float) $purchase->grand_total);
        $payments = DB::table('payments as p')->join('purchases as u', 'u.id', '=', 'p.purchase_id')
            ->where('u.reference_no', 'like', 'DEMO-%')->whereNull('u.deleted_at')->whereNull('p.purchase_return_id')
            ->select('p.*', 'u.supplier_id', 'u.reference_no')->orderBy('p.id')->get();
        foreach ($payments as $payment) $add($payment->supplier_id, 'purchase_payment', $payment->id, $payment->reference_no, -(float) $payment->amount / ((float) $payment->exchange_rate ?: 1));
        $returns = DB::table('return_purchases')->where('reference_no', 'like', 'DEMO-%')->orderBy('id')->get();
        foreach ($returns as $return) $add($return->supplier_id, 'purchase_return', $return->id, $return->reference_no, -(float) $return->grand_total / ((float) $return->exchange_rate ?: 1));
        ksort($expected);
        return ['baseline_by_supplier' => data_get($manifest, 'baseline.financial.supplier_opening_map', []), 'movements' => $movements, 'expected_by_supplier' => $expected, 'refund_payments_excluded_from_outbound' => true];
    }

    private function paymentAccountLedger(array $manifest): array
    {
        $baseline = collect(data_get($manifest, 'baseline.financial.payment_account_balances', []))->keyBy('id')->map(fn ($row) => (float) $row['balance'])->all();
        $expected = $baseline;
        $movements = [];
        $add = function ($account, string $source, $id, string $reference, float $amount) use (&$expected, &$movements): void {
            if (!$account) return;
            $key = (string) $account;
            $expected[$key] = round((float) ($expected[$key] ?? 0) + $amount, 6);
            $movements[] = ['account_id' => (int) $account, 'source_type' => $source, 'source_id' => (int) $id, 'reference' => $reference, 'signed_amount' => round($amount, 6)];
        };
        foreach (DB::table('accounts')->orderBy('id')->get() as $account) {
            if (!array_key_exists((string) $account->id, $baseline) && !array_key_exists($account->id, $baseline)) {
                $add($account->id, 'account_opening', $account->id, (string) $account->account_no, (float) $account->initial_balance);
            }
        }
        $payments = DB::table('payments')->orderBy('id')->get();
        foreach ($payments as $payment) {
            $rate = (float) $payment->exchange_rate ?: 1;
            if ($payment->sale_id && !$payment->return_id) $add($payment->account_id, 'sale_receipt', $payment->id, (string) $payment->payment_reference, (float) $payment->amount / $rate);
            elseif ($payment->sale_id && $payment->return_id) $add($payment->account_id, 'customer_refund', $payment->id, (string) $payment->payment_reference, -(float) $payment->amount / $rate);
            elseif ($payment->purchase_return_id) $add($payment->account_id, 'supplier_refund', $payment->id, (string) $payment->payment_reference, (float) $payment->amount / $rate);
            elseif ($payment->purchase_id) $add($payment->account_id, 'purchase_payment', $payment->id, (string) $payment->payment_reference, -(float) $payment->amount / $rate);
        }
        if (Schema::hasTable('expenses')) {
            foreach (DB::table('expenses')->whereNotNull('account_id')->whereNotNull('cash_register_id')->orderBy('id')->get() as $expense) {
                $add($expense->account_id, 'expense', $expense->id, (string) $expense->reference_no, -(float) $expense->amount);
            }
        }
        ksort($expected);
        return ['baseline_by_account' => $baseline, 'movements' => $movements, 'expected_by_account' => $expected];
    }

    private function accountingExpectationSet(): array
    {
        if (!Schema::hasTable('journal_entries')) return [];
        return DB::table('journal_entries')->orderBy('id')->get()->map(fn ($journal) => [
            'economic_key' => $journal->source_type.':'.$journal->source_id.':'.$journal->event_type,
            'source_type' => $journal->source_type,
            'source_id' => (int) $journal->source_id,
            'source_reference' => $journal->reference_no,
            'event_type' => $journal->event_type,
            'expected_state' => str_ends_with((string) $journal->event_type, '_deleted') || str_ends_with((string) $journal->event_type, '_reversed') ? 'reversal' : 'active',
            'related_journal_entry_id' => $journal->related_journal_entry_id ? (int) $journal->related_journal_entry_id : null,
        ])->all();
    }

    private function tuple($product, $variant, $warehouse): string
    {
        return (int) $product.':'.((int) $variant ?: 0).':'.(int) $warehouse;
    }
}
