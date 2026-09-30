<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Payroll;

class EmployeeAdvanceService
{
    public function totalsForEmployee(int $employeeId, ?int $excludePayrollId = null): array
    {
        $issued = (float) Expense::where('employee_id', $employeeId)
            ->where('type', 'advance')
            ->where('accounting_status', 'posted')
            ->sum('amount');

        $payrolls = Payroll::where('employee_id', $employeeId)
            ->where('accounting_status', 'posted')
            ->when($excludePayrollId, fn ($query) => $query->whereKeyNot($excludePayrollId))
            ->get(['amount_array']);

        $recovered = (float) $payrolls->sum(function ($payroll) {
            $amounts = is_array($payroll->amount_array)
                ? $payroll->amount_array
                : (json_decode((string) $payroll->amount_array, true) ?: []);

            return (float) ($amounts['advance_recovery'] ?? 0);
        });

        return [
            'issued' => $issued,
            'recovered' => $recovered,
            'outstanding' => max(0, $issued - $recovered),
        ];
    }

    public function outstandingForEmployee(int $employeeId, ?int $excludePayrollId = null): float
    {
        return $this->totalsForEmployee($employeeId, $excludePayrollId)['outstanding'];
    }
}
