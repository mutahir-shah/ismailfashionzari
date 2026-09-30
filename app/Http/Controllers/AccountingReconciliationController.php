<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AccountingSyncQueue;
use App\Services\AccountingHealthService;
use App\Services\AccountingService;
use App\Services\PaymentAccountMappingRepairService;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Returns;
use App\Models\ReturnPurchase;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Payroll;
use App\Models\MoneyTransfer;
use DB;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AccountingReconciliationController extends Controller
{
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    private const HEALTH_ACCESS_PERMISSIONS = [
        'account-index',
        'chart-of-accounts-manage',
        'semantic-account-mappings-manage',
        'money-transfer',
        'balance-sheet',
        'account-statement',
    ];

    private const TECHNICAL_ACCESS_PERMISSIONS = [
        'chart-of-accounts-manage',
        'semantic-account-mappings-manage',
    ];

    private const PAYMENT_MAPPING_REPAIR_PERMISSION = 'semantic-account-mappings-manage';

    public function index(Request $request, AccountingHealthService $healthService, PaymentAccountMappingRepairService $repairService)
    {
        if (!$this->userHasAnyAccountingPermission(self::HEALTH_ACCESS_PERMISSIONS)) {
            return redirect('/')->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        $queue = AccountingSyncQueue::orderBy('updated_at', 'desc')->paginate(50);
        
        $stats = [
            'total' => AccountingSyncQueue::count(),
            'failed' => AccountingSyncQueue::where('status', 'failed')->count(),
            'pending' => AccountingSyncQueue::where('status', 'pending')->count(),
            'posted' => AccountingSyncQueue::where('status', 'posted')->count(),
        ];

        $lastCertification = session('accounting_health.last_certification');
        $health = $healthService->build(is_array($lastCertification) ? $lastCertification : null);
        $technicalExpanded = $request->boolean('technical');
        $canViewTechnicalDetails = $this->userHasAnyAccountingPermission(self::TECHNICAL_ACCESS_PERMISSIONS);
        $canRepairPaymentMappings = $this->userHasAnyAccountingPermission([self::PAYMENT_MAPPING_REPAIR_PERMISSION]);
        $paymentMappingRepair = $canRepairPaymentMappings ? $repairService->inspect() : null;

        return view('backend.accounting.reconciliation.index', compact(
            'queue',
            'stats',
            'health',
            'technicalExpanded',
            'canViewTechnicalDetails',
            'canRepairPaymentMappings',
            'paymentMappingRepair'
        ));
    }

    public function repairPaymentAccountMappings(PaymentAccountMappingRepairService $repairService)
    {
        if (!$this->userHasAnyAccountingPermission([self::PAYMENT_MAPPING_REPAIR_PERMISSION])) {
            abort(403);
        }

        $result = $repairService->apply();
        session()->forget('accounting_health.last_certification');

        if (!empty($result['errors'])) {
            return redirect()->route('accounting.reconciliation.index')
                ->with('not_permitted', __('db.accounting_health_payment_mapping_repair_failed', [
                    'count' => count($result['errors']),
                ]));
        }

        return redirect()->route('accounting.reconciliation.index')
            ->with('message', __('db.accounting_health_payment_mapping_repair_success', [
                'count' => $result['repaired_count'],
                'skipped' => count($result['skipped']) + $result['invalid_count'],
            ]));
    }

    public function runHealthCheck(AccountingHealthService $healthService)
    {
        if (!$this->userHasAnyAccountingPermission(self::HEALTH_ACCESS_PERMISSIONS)) {
            return redirect('/')->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        $result = $healthService->runCertification();
        session()->put('accounting_health.last_certification', $result);

        $message = $result['status'] === 'fail'
            ? __('db.accounting_health_check_completed_with_issues')
            : __('db.accounting_health_check_completed_success');

        return redirect()
            ->route('accounting.reconciliation.index')
            ->with($result['status'] === 'fail' ? 'not_permitted' : 'message', $message);
    }

    public function retry($id)
    {
        if (!$this->userHasAnyAccountingPermission(self::TECHNICAL_ACCESS_PERMISSIONS)) {
            return redirect('/')->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        $record = AccountingSyncQueue::findOrFail($id);
        $attemptedAt = now();
        
        $modelClass = $record->source_type;
        if (!class_exists($modelClass)) {
            $this->markRetryFailed($record, 'Model class not found: ' . $modelClass, $attemptedAt);
            return redirect()->back()->with('error', __('db.accounting_health_retry_model_missing', ['model' => $modelClass]));
        }
        
        $model = $modelClass::find($record->source_id);
        if (!$model) {
            $this->markRetryFailed($record, 'Source record deleted or not found.', $attemptedAt);
            return redirect()->back()->with('error', __('db.accounting_health_retry_source_missing'));
        }

        // Determine which method to call based on model
        $result = null;
        try {
            switch ($modelClass) {
                case Sale::class:
                    $result = $this->accountingService->recordSale($model);
                    break;
                case Purchase::class:
                    $result = $this->accountingService->recordPurchase($model);
                    break;
                case Returns::class:
                    $result = $this->accountingService->recordSaleReturn($model);
                    break;
                case ReturnPurchase::class:
                    $result = $this->accountingService->recordPurchaseReturn($model);
                    break;
                case Payment::class:
                    $result = $this->accountingService->recordPayment($model);
                    break;
                case Expense::class:
                    $result = $this->accountingService->recordExpense($model);
                    break;
                case Income::class:
                    $result = $this->accountingService->recordIncome($model);
                    break;
                case Payroll::class:
                    $result = $this->accountingService->recordPayroll($model);
                    break;
                case MoneyTransfer::class:
                    $result = $this->accountingService->recordMoneyTransfer($model);
                    break;
                // Additional types can be added here
                default:
                    $this->markRetryFailed($record, 'Retry logic not implemented for this model type.', $attemptedAt);
                    return redirect()->back()->with('error', __('db.accounting_health_retry_not_supported'));
            }
        } catch (Throwable $e) {
            report($e);
            $this->markRetryFailed($record, $e->getMessage(), $attemptedAt);
            return redirect()->back()->with('error', __('db.accounting_health_retry_failed', ['error' => $e->getMessage()]));
        }

        if ($result && $result->success) {
            $this->markRetrySucceeded($record, $attemptedAt);
            return redirect()->back()->with('message', __('db.accounting_health_retry_success'));
        } else {
            $error = $result ? $result->error : __('db.accounting_health_unknown_error');
            $this->markRetryFailed($record, $error, $attemptedAt);
            return redirect()->back()->with('error', __('db.accounting_health_retry_failed', ['error' => $error]));
        }
    }

    private function markRetrySucceeded(AccountingSyncQueue $record, $timestamp): void
    {
        $record->status = 'posted';
        $record->attempts = (int) $record->attempts + 1;
        $record->last_attempt_at = $timestamp;
        $record->last_error = null;
        $record->posted_at = $record->posted_at ?: $timestamp;
        $record->resolved_at = $record->resolved_at ?: $timestamp;
        $record->last_success_at = $timestamp;
        $record->save();
    }

    private function markRetryFailed(AccountingSyncQueue $record, string $error, $timestamp): void
    {
        $record->status = 'failed';
        $record->attempts = (int) $record->attempts + 1;
        $record->last_attempt_at = $timestamp;
        $record->last_error = $error;
        $record->save();
    }

    private function userHasAnyAccountingPermission(array $permissions): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return DB::table('permissions')
            ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('role_has_permissions.role_id', $user->role_id)
            ->whereIn('permissions.name', $permissions)
            ->exists();
    }
}
