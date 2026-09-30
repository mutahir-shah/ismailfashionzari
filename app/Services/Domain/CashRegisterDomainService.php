<?php

namespace App\Services\Domain;

use App\Models\CashRegister;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CashRegisterDomainService
{
    public function open(array $data, int $userId): CashRegister
    {
        return DB::transaction(fn () => CashRegister::create([
            'cash_in_hand' => (float) $data['cash_in_hand'],
            'user_id' => $userId,
            'warehouse_id' => (int) $data['warehouse_id'],
            'status' => true,
        ]));
    }

    public function close(CashRegister $register, float $closingBalance, float $actualCash): CashRegister
    {
        return DB::transaction(function () use ($register, $closingBalance, $actualCash) {
            $register = CashRegister::withoutGlobalScopes()->lockForUpdate()->findOrFail($register->id);
            if (!$register->status) {
                throw new RuntimeException('Cash register is already closed.');
            }
            $register->closing_balance = $closingBalance;
            $register->actual_cash = $actualCash;
            $register->status = false;
            $register->save();
            return $register->fresh();
        });
    }

    public function variance(CashRegister $register): ?float
    {
        if ($register->closing_balance === null || $register->actual_cash === null) return null;
        return round((float) $register->actual_cash - (float) $register->closing_balance, 4);
    }
}
