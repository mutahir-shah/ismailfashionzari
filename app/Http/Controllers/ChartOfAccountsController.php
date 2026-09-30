<?php

namespace App\Http\Controllers;

use App\Models\AccountingAccount;
use App\Models\AccountMapping;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ChartOfAccountsController extends Controller
{
    public function index()
    {
        $accounts = AccountingAccount::query()
            ->with('parent')
            ->withCount(['children', 'journalLines'])
            ->orderBy('code')
            ->get();

        $mappedAccountIds = AccountMapping::query()
            ->select('accounting_account_id')
            ->distinct()
            ->pluck('accounting_account_id')
            ->all();

        return view('backend.accounting.chart_of_accounts.index', compact('accounts', 'mappedAccountIds'));
    }

    public function edit(AccountingAccount $account)
    {
        $account->loadCount(['children', 'journalLines']);

        $protectionReasons = $this->protectionReasons($account);
        $hasDescription = Schema::hasColumn($account->getTable(), 'description');

        return view('backend.accounting.chart_of_accounts.edit', compact('account', 'protectionReasons', 'hasDescription'));
    }

    public function update(Request $request, AccountingAccount $account)
    {
        $request->merge([
            'code' => trim((string) $request->input('code')),
            'name' => trim((string) $request->input('name')),
        ]);

        $rules = [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('accounting_accounts', 'code')->ignore($account->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if (Schema::hasColumn($account->getTable(), 'description')) {
            $rules['description'] = ['nullable', 'string'];
        }

        $validated = $request->validate($rules);
        $nextActiveState = $request->has('is_active')
            ? $request->boolean('is_active')
            : (bool) $account->is_active;

        if ($account->is_active && !$nextActiveState) {
            $protectionReasons = $this->protectionReasons($account);

            if ($protectionReasons) {
                throw ValidationException::withMessages([
                    'is_active' => 'This account cannot be deactivated: ' . implode(' ', $protectionReasons),
                ]);
            }
        }

        $data = [
            'code' => $validated['code'],
            'name' => $validated['name'],
            'is_active' => $nextActiveState,
        ];

        if (array_key_exists('description', $validated)) {
            $data['description'] = $validated['description'];
        }

        $account->update($data);

        return redirect()
            ->route('accounting.chart-of-accounts.index')
            ->with('message', __('db.Data updated successfully'));
    }

    public function destroy(AccountingAccount $account)
    {
        $protectionReasons = $this->protectionReasons($account);

        if ($protectionReasons) {
            throw ValidationException::withMessages([
                'account' => 'This account cannot be deleted: ' . implode(' ', $protectionReasons),
            ]);
        }

        $account->delete();

        return redirect()
            ->route('accounting.chart-of-accounts.index')
            ->with('not_permitted', __('db.Data deleted successfully'));
    }

    private function protectionReasons(AccountingAccount $account): array
    {
        $reasons = [];

        $mappingLabels = AccountMapping::query()
            ->where('accounting_account_id', $account->id)
            ->get()
            ->map(fn (AccountMapping $mapping) => $this->mappingLabel($mapping))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($mappingLabels) {
            $reasons[] = 'It is assigned to accounting mappings (' . implode(', ', $mappingLabels) . ').';
        }

        if ($account->journalLines()->exists()) {
            $reasons[] = 'It is used by journal entries.';
        }

        if ($account->children()->exists()) {
            $reasons[] = 'It has child accounts.';
        }

        if ($account->is_cash_account) {
            $reasons[] = 'It is marked as a cash/payment account.';
        }

        if ($account->is_system) {
            $reasons[] = 'It is a system account.';
        }

        if ($account->is_control_account) {
            $reasons[] = 'It is a control account.';
        }

        return $reasons;
    }

    private function mappingLabel(AccountMapping $mapping): string
    {
        if ((int) $mapping->mapped_id === 0 && array_key_exists($mapping->mapped_type, AccountingService::DEFAULT_ROLE_CODES)) {
            return str_replace('_', ' ', $mapping->mapped_type);
        }

        return class_basename($mapping->mapped_type) . '#' . $mapping->mapped_id;
    }
}
