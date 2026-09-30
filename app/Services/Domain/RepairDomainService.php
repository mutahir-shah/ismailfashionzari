<?php

namespace App\Services\Domain;

use App\Models\CashRegister;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Product_Warehouse;
use App\Services\AccountingService;
use App\Services\PaymentAccountService;
use Illuminate\Support\Facades\DB;
use Modules\Repair\Entities\ServiceDevice;
use Modules\Repair\Entities\ServiceJob;
use Modules\Repair\Entities\ServiceJobItem;
use Modules\Repair\Entities\ServiceJobUpdate;
use Modules\Repair\Entities\ServiceVehicle;
use RuntimeException;

class RepairDomainService
{
    public const STATUSES = ['pending', 'diagnosed', 'in_progress', 'completed', 'delivered', 'cancelled'];

    public function create(array $data, int $userId): ServiceJob
    {
        return DB::transaction(function () use ($data, $userId) {
            $job = ServiceJob::create([
                'reference_no' => $data['reference_no'] ?? ('SRV-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -5))),
                'customer_id' => $data['customer_id'],
                'service_type' => $data['service_type'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'priority' => $data['priority'] ?? 'medium',
                'assigned_to' => $data['assigned_to'] ?? null,
                'created_by' => $userId,
                'warehouse_id' => $data['warehouse_id'],
                'note' => $data['note'] ?? null,
                'service_charge' => 0,
                'discount' => 0,
                'tax' => 0,
                'total_amount' => 0,
                'paid_amount' => 0,
                'due_amount' => 0,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            ]);

            if ($job->service_type === 'device') {
                ServiceDevice::create([
                    'service_job_id' => $job->id,
                    'device_type' => $data['device_type'] ?? $data['device_type_id'] ?? 'Other',
                    'brand' => $data['device_brand'] ?? null,
                    'model' => $data['device_model'] ?? null,
                    'serial_number' => $data['serial_number'] ?? null,
                    'imei' => $data['imei'] ?? null,
                    'password_hint' => $data['password_hint'] ?? null,
                    'accessories' => $data['accessories'] ?? null,
                    'issue_reported' => $data['device_issue_reported'] ?? null,
                    'condition_notes' => $data['device_condition_notes'] ?? null,
                ]);
            } else {
                ServiceVehicle::create([
                    'service_job_id' => $job->id,
                    'vehicle_type' => $data['vehicle_type'] ?? $data['vehicle_type_id'] ?? 'other',
                    'brand' => $data['vehicle_brand'] ?? null,
                    'model' => $data['vehicle_model'] ?? null,
                    'year' => $data['vehicle_year'] ?? null,
                    'registration_no' => $data['registration_no'] ?? null,
                    'engine_no' => $data['engine_no'] ?? null,
                    'chassis_no' => $data['chassis_no'] ?? null,
                    'mileage' => $data['mileage'] ?? null,
                    'fuel_level' => $data['fuel_level'] ?? null,
                    'issue_reported' => $data['vehicle_issue_reported'] ?? null,
                    'condition_notes' => $data['vehicle_condition_notes'] ?? null,
                ]);
            }

            ServiceJobUpdate::create(['service_job_id' => $job->id, 'status' => $job->status, 'note' => 'Service job created.', 'updated_by' => $userId]);
            return $job->fresh(['device', 'vehicle']);
        });
    }

    public function configure(ServiceJob $job, array $parts, array $charges): ServiceJob
    {
        return DB::transaction(function () use ($job, $parts, $charges) {
            foreach ($parts as $part) {
                $this->persistPart($job, $part);
            }
            $job->service_charge = (float) ($charges['service_charge'] ?? $job->service_charge);
            $job->discount = (float) ($charges['discount'] ?? $job->discount);
            $job->tax = (float) ($charges['tax'] ?? $job->tax);
            $job->saveQuietly();
            $job->recalculateTotals();
            return $job->fresh(['items', 'sale']);
        });
    }

    public function addPart(ServiceJob $job, array $part): ServiceJobItem
    {
        return DB::transaction(function () use ($job, $part) {
            $item = $this->persistPart($job, $part);
            $job->recalculateTotals();
            return $item->fresh('product');
        });
    }

    public function updateCharges(ServiceJob $job, array $charges): ServiceJob
    {
        return $this->configure($job, [], $charges);
    }

    public function addPayment(ServiceJob $job, array $data, int $userId): Payment
    {
        app(PaymentAccountService::class)->assertValidId((int) $data['account_id']);

        return DB::transaction(function () use ($job, $data, $userId) {
            $job->refresh();
            $amount = (float) $data['amount'];
            if ($amount <= 0 || $amount > (float) $job->due_amount + .001) {
                throw new RuntimeException('Payment exceeds Repair due or is not positive.');
            }
            $sale = $job->sale ?: $job->syncToSale();
            $method = (string) $data['paying_method'];
            $register = strcasecmp($method, 'cash') === 0
                ? CashRegister::where('user_id', $userId)->where('status', true)->first()
                : null;
            $payment = Payment::create([
                'service_job_id' => $job->id,
                'sale_id' => $sale->id,
                'user_id' => $userId,
                'cash_register_id' => $register?->id,
                'account_id' => $data['account_id'],
                'amount' => $amount,
                'paying_method' => $method,
                'payment_reference' => $data['payment_reference'] ?? ('rep-'.date('Ymd-His')),
                'payment_note' => $data['payment_note'] ?? null,
                'payment_at' => $data['payment_at'] ?? now(),
                'change' => 0,
                'exchange_rate' => 1,
            ]);
            $job->recalculateTotals();
            $sale->refresh();
            $sale->paid_amount = (float) $sale->payments()->sum('amount');
            $balance = (float) $sale->grand_total - (float) $sale->paid_amount;
            $sale->payment_status = $balance > .001 ? 3 : 4;
            $sale->saveQuietly();
            $result = app(AccountingService::class)->recordPayment($payment, 'repair_payment_received');
            if (!$result->isSuccess()) {
                throw new RuntimeException($result->getMessage() ?? 'Repair payment accounting failed.');
            }
            return $payment->fresh('account');
        });
    }

    public function transition(ServiceJob $job, string $status, int $userId, ?string $note = null): ServiceJob
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new RuntimeException("Unsupported Repair status: {$status}");
        }
        if ($status === 'cancelled') {
            throw new RuntimeException('Use canonical cancellation for cancelled Repair state.');
        }
        return DB::transaction(function () use ($job, $status, $userId, $note) {
            if ($job->status === $status) return $job->fresh();
            $job->status = $status;
            if (in_array($status, ['completed', 'delivered'], true)) $job->delivery_date = now()->toDateString();
            $job->saveQuietly();
            ServiceJobUpdate::create(['service_job_id' => $job->id, 'status' => $status, 'note' => $note ?? 'Status updated.', 'updated_by' => $userId]);
            if ($job->sale) $job->syncToSale();
            return $job->fresh();
        });
    }

    public function cancel(ServiceJob $job): void
    {
        DB::transaction(function () use ($job) {
            $job->load('items.product', 'payments', 'sale');
            foreach ($job->items as $item) {
                if (!$item->product) continue;
                $item->product->increment('qty', (float) $item->quantity);
                $stock = Product_Warehouse::where('product_id', $item->product_id)
                    ->where('warehouse_id', $job->warehouse_id)->whereNull('variant_id')->first();
                if (!$stock) throw new RuntimeException('Repair cancellation stock tuple is missing.');
                $stock->increment('qty', (float) $item->quantity);
            }
            $accounting = app(AccountingService::class);
            foreach ($job->payments as $payment) {
                $result = $accounting->reverseTransaction(get_class($payment), $payment->id, '_deleted');
                if (!$result->isSuccess()) throw new RuntimeException($result->getMessage() ?? 'Repair payment reversal failed.');
                $payment->delete();
            }
            if ($job->sale) {
                $result = $accounting->reverseTransaction(get_class($job->sale), $job->sale->id, '_deleted');
                if (!$result->isSuccess()) throw new RuntimeException($result->getMessage() ?? 'Repair invoice reversal failed.');
                $job->sale->accounting_status = 'reversed';
                $job->sale->saveQuietly();
                $job->sale->delete();
            }
            $job->status = 'cancelled';
            $job->saveQuietly();
            $job->delete();
        });
    }

    private function persistPart(ServiceJob $job, array $part): ServiceJobItem
    {
        $product = Product::findOrFail((int) $part['product_id']);
        if ($product->is_variant) throw new RuntimeException('Repair item schema does not persist variant identity.');
        $qty = (float) $part['quantity'];
        $stock = Product_Warehouse::where('product_id', $product->id)->where('warehouse_id', $job->warehouse_id)->whereNull('variant_id')->lockForUpdate()->first();
        if (!$stock || $qty <= 0 || $qty > (float) $stock->qty + .0001) throw new RuntimeException('Insufficient Repair part stock.');
        return ServiceJobItem::create([
            'service_job_id' => $job->id, 'product_id' => $product->id, 'quantity' => $qty,
            'unit_price' => (float) $part['unit_price'], 'discount' => 0, 'tax' => 0,
            'total' => $qty * (float) $part['unit_price'],
        ]);
    }
}
