<?php

namespace App\Http\Controllers;

use App\Models\AccountingAccount;
use App\Models\AccountMapping;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SemanticAccountMappingController extends Controller
{
    public function index(AccountingService $accountingService)
    {
        $roles = $this->semanticRoles();
        $mappings = AccountMapping::query()
            ->whereIn('mapped_type', array_keys($roles))
            ->where('mapped_id', 0)
            ->with('account')
            ->get()
            ->keyBy('mapped_type');

        $validation = $accountingService->validateSemanticRoleMappings(array_keys($roles), false);
        $compatibleAccounts = [];

        foreach (array_keys($roles) as $role) {
            $compatibleAccounts[$role] = $this->compatibleAccountsForRole($role);
        }

        return view('backend.accounting.semantic_mappings.index', compact(
            'roles',
            'mappings',
            'validation',
            'compatibleAccounts'
        ));
    }

    public function update(Request $request, string $role)
    {
        $this->assertSupportedRole($role);

        $validated = $request->validate([
            'accounting_account_id' => ['required', 'integer', 'exists:accounting_accounts,id'],
        ], [
            'accounting_account_id.required' => 'A semantic role must always be mapped to an accounting account.',
        ]);

        $account = AccountingAccount::findOrFail($validated['accounting_account_id']);

        $this->saveMapping($role, $account);

        return redirect()
            ->route('accounting.semantic-mappings.index')
            ->with('message', 'Semantic account mapping updated successfully.');
    }

    public function restoreDefault(string $role)
    {
        $this->assertSupportedRole($role);

        $defaultAccount = $this->defaultAccountForRole($role);

        if (!$defaultAccount) {
            throw ValidationException::withMessages([
                'accounting_account_id' => "No active compatible default account exists for {$this->roleLabel($role)}.",
            ]);
        }

        $this->saveMapping($role, $defaultAccount);

        return redirect()
            ->route('accounting.semantic-mappings.index')
            ->with('message', 'Default semantic mapping restored successfully.');
    }

    private function semanticRoles(): array
    {
        $roles = [];

        foreach (array_keys(AccountingService::DEFAULT_ROLE_CODES) as $role) {
            $roles[$role] = [
                'label' => $this->roleLabel($role),
                'allowed_types' => AccountingService::ROLE_ACCOUNT_TYPES[$role] ?? [],
                'default_codes' => AccountingService::DEFAULT_ROLE_CODES[$role] ?? [],
                'cash_only' => $role === AccountingService::ROLE_CASH,
            ];
        }

        return $roles;
    }

    private function compatibleAccountsForRole(string $role)
    {
        $allowedTypes = AccountingService::ROLE_ACCOUNT_TYPES[$role] ?? [];
        $mappedToOtherRoleAccountIds = AccountMapping::query()
            ->where('mapped_id', 0)
            ->whereIn('mapped_type', array_keys(AccountingService::DEFAULT_ROLE_CODES))
            ->where('mapped_type', '!=', $role)
            ->pluck('accounting_account_id')
            ->all();

        return AccountingAccount::query()
            ->where('is_active', true)
            ->when($allowedTypes, fn ($query) => $query->whereIn('account_type', $allowedTypes))
            ->when($role === AccountingService::ROLE_CASH, fn ($query) => $query->where('is_cash_account', true))
            ->when($mappedToOtherRoleAccountIds, fn ($query) => $query->whereNotIn('id', $mappedToOtherRoleAccountIds))
            ->orderBy('code')
            ->get();
    }

    private function assertSupportedRole(string $role): void
    {
        if (!array_key_exists($role, AccountingService::DEFAULT_ROLE_CODES)) {
            abort(404);
        }
    }

    private function assertAccountCanFulfillRole(string $role, AccountingAccount $account): void
    {
        if (!$account->is_active) {
            throw ValidationException::withMessages([
                'accounting_account_id' => "{$account->code} - {$account->name} is inactive.",
            ]);
        }

        $allowedTypes = AccountingService::ROLE_ACCOUNT_TYPES[$role] ?? [];
        if ($allowedTypes && !in_array($account->account_type, $allowedTypes, true)) {
            throw ValidationException::withMessages([
                'accounting_account_id' => "{$this->roleLabel($role)} requires " . implode(' or ', $allowedTypes) . " account type.",
            ]);
        }

        if ($role === AccountingService::ROLE_CASH && !$account->is_cash_account) {
            throw ValidationException::withMessages([
                'accounting_account_id' => 'Cash must be mapped to an active asset account marked as a cash/payment account.',
            ]);
        }
    }

    private function assertNoDuplicateSemanticAccount(string $role, AccountingAccount $account): void
    {
        $duplicate = AccountMapping::query()
            ->where('mapped_id', 0)
            ->whereIn('mapped_type', array_keys(AccountingService::DEFAULT_ROLE_CODES))
            ->where('mapped_type', '!=', $role)
            ->where('accounting_account_id', $account->id)
            ->first();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'accounting_account_id' => "{$account->code} - {$account->name} is already mapped to {$this->roleLabel($duplicate->mapped_type)}.",
            ]);
        }
    }

    private function saveMapping(string $role, AccountingAccount $account): void
    {
        $this->assertAccountCanFulfillRole($role, $account);
        $this->assertNoDuplicateSemanticAccount($role, $account);

        $previous = AccountMapping::where('mapped_type', $role)
            ->where('mapped_id', 0)
            ->first();

        AccountMapping::updateOrCreate(
            ['mapped_type' => $role, 'mapped_id' => 0],
            ['accounting_account_id' => $account->id]
        );

        try {
            $this->assertSemanticConfigurationIsValid();
        } catch (ValidationException $e) {
            if ($previous) {
                AccountMapping::updateOrCreate(
                    ['mapped_type' => $role, 'mapped_id' => 0],
                    ['accounting_account_id' => $previous->accounting_account_id]
                );
            } else {
                AccountMapping::where('mapped_type', $role)
                    ->where('mapped_id', 0)
                    ->delete();
            }

            throw $e;
        }
    }

    private function assertSemanticConfigurationIsValid(): void
    {
        $roles = array_keys(AccountingService::DEFAULT_ROLE_CODES);
        $results = app(AccountingService::class)->validateSemanticRoleMappings($roles, false);
        $failures = array_filter($results, fn ($result) => ($result['status'] ?? null) !== 'pass');

        if ($failures) {
            throw ValidationException::withMessages([
                'accounting_account_id' => implode(' ', array_map(fn ($result) => $result['message'], $failures)),
            ]);
        }

        foreach ($results as $role => $result) {
            if (($result['status'] ?? null) === 'pass' && isset($result['account'])) {
                $this->assertAccountCanFulfillRole($role, $result['account']);
            }
        }

        $duplicates = AccountMapping::query()
            ->where('mapped_id', 0)
            ->whereIn('mapped_type', $roles)
            ->select('accounting_account_id', DB::raw('COUNT(*) as role_count'))
            ->groupBy('accounting_account_id')
            ->having('role_count', '>', 1)
            ->exists();

        if ($duplicates) {
            throw ValidationException::withMessages([
                'accounting_account_id' => 'Each semantic role must map to a distinct accounting account.',
            ]);
        }
    }

    private function defaultAccountForRole(string $role): ?AccountingAccount
    {
        foreach (AccountingService::DEFAULT_ROLE_CODES[$role] ?? [] as $code) {
            $account = AccountingAccount::where('code', $code)->first();
            if (!$account) {
                continue;
            }

            try {
                $this->assertAccountCanFulfillRole($role, $account);
            } catch (ValidationException $e) {
                continue;
            }

            return $account;
        }

        return null;
    }

    private function roleLabel(string $role): string
    {
        return ucwords(str_replace('_', ' ', $role));
    }
}
