<style>
    .activation-shell{max-width:1120px;margin:0 auto}.activation-card{border:0;border-radius:8px;box-shadow:0 2px 18px rgba(34,41,47,.08)}.activation-header{padding:28px 30px 18px}.activation-header h2{font-size:24px;margin-bottom:8px}.activation-header p,.wizard-copy{color:#6c757d}.activation-stepper{display:flex;padding:0 30px 26px}.activation-step{flex:1;position:relative;text-align:center;color:#8a8f98;font-size:13px;font-weight:600}.activation-step:not(:last-child):before{content:"";position:absolute;top:16px;left:50%;width:100%;height:2px;background:#e5e7eb}.activation-step-number{position:relative;z-index:1;display:flex;width:34px;height:34px;margin:0 auto 8px;align-items:center;justify-content:center;border:2px solid #d9dce1;border-radius:50%;background:#fff}.activation-step.active,.activation-step.complete{color:#7c3aed}.activation-step.active .activation-step-number,.activation-step.complete .activation-step-number{border-color:#7c3aed;background:#7c3aed;color:#fff}.wizard-panel{display:none;padding:0 30px 30px}.wizard-panel.active{display:block}.wizard-title{font-size:18px;margin-bottom:6px}.wizard-copy{margin-bottom:22px}.business-option{display:block;height:100%;padding:22px;border:2px solid #e4e6eb;border-radius:8px;cursor:pointer;transition:.2s}.business-option:hover,.business-option.selected{border-color:#7c3aed;box-shadow:0 4px 14px rgba(124,58,237,.1)}.business-option.disabled{background:#f6f7f9;color:#8a8f98;cursor:not-allowed;box-shadow:none}.business-option input{position:absolute;opacity:0}.business-option h4{font-size:17px;margin:12px 0 10px}.business-option p{line-height:1.6;margin-bottom:0}.badge-soft-primary{color:#6d28d9;background:#ede9fe}.badge-soft-secondary{color:#495057;background:#edf0f3}.readiness-row{display:flex;align-items:flex-start;padding:16px 0;border-bottom:1px solid #edf0f3}.readiness-icon{display:flex;width:32px;height:32px;flex:0 0 32px;align-items:center;justify-content:center;border-radius:50%;margin-right:14px}.readiness-icon.pass{color:#16803c;background:#dcfce7}.readiness-icon.warn{color:#9a6700;background:#fef3c7}.readiness-icon.fail{color:#b42318;background:#fee2e2}.status-summary{border-left:4px solid;border-radius:4px;padding:15px 18px;margin-bottom:18px;font-weight:700}.status-summary.pass{color:#166534;background:#f0fdf4;border-color:#22c55e}.status-summary.warn{color:#854d0e;background:#fffbeb;border-color:#f59e0b}.status-summary.fail{color:#991b1b;background:#fef2f2;border-color:#ef4444}.equation-card{height:100%;border:1px solid #e4e6eb;border-radius:8px;padding:20px}.equation-card h5{font-size:15px;text-transform:uppercase;color:#6c757d;margin-bottom:16px}.amount-row{display:flex;justify-content:space-between;gap:15px;padding:8px 0}.amount-total{border-top:1px solid #dfe2e6;margin-top:8px;padding-top:14px;font-weight:700}.activation-meta{display:flex;flex-wrap:wrap;gap:14px 28px;padding:16px 18px;margin:20px 0;background:#f7f7fa;border-radius:6px}.activation-meta small{display:block;color:#6c757d}.wizard-actions{display:flex;justify-content:space-between;align-items:center;padding-top:24px}.btn-activation{background:#7c3aed;border-color:#7c3aed;color:#fff}.btn-activation:hover{background:#6d28d9;border-color:#6d28d9;color:#fff}@media(max-width:767px){.activation-header,.wizard-panel{padding-left:18px;padding-right:18px}.activation-stepper{padding-left:8px;padding-right:8px}.activation-step-label{font-size:10px}.wizard-actions{gap:10px}}
</style>

<section class="forms"><div class="container-fluid"><div class="activation-shell"><div class="card activation-card">
    <div class="activation-header">
        <h2>Initialize Double-Entry Accounting</h2>
        <p>Set up your accounting foundation. This process only needs to be completed once.</p>
    </div>
    <div class="activation-stepper" aria-label="Accounting activation progress">
        @foreach(['Business Type','Certification','Opening Balance','Complete'] as $index => $label)
            <div class="activation-step {{ $index === 0 ? 'active' : '' }}" data-step-indicator="{{ $index + 1 }}"><span class="activation-step-number">{{ $index + 1 }}</span><span class="activation-step-label">{{ $label }}</span></div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('accounting.activation.activate') }}" id="activation-form">@csrf
        <div class="wizard-panel active" data-step="1">
            <h3 class="wizard-title">Choose your business type</h3>
            <p class="wizard-copy">This determines whether SalePro creates an opening balance journal from your existing operational data.</p>
            <div class="row">
                <div class="col-lg-6 mb-3"><label class="business-option {{ $mode === 'existing_business' ? 'selected' : '' }}">
                    <input type="radio" name="mode" value="existing_business" {{ $mode === 'existing_business' ? 'checked' : '' }}>
                    <span class="badge badge-soft-primary">Recommended</span> <span class="badge badge-soft-secondary">Safest for existing SalePro users</span>
                    <h4>Existing Business</h4>
                    <p>You already have transactions, stock, customers, suppliers, or balances in SalePro. SalePro will verify your current data, calculate opening balances, create one Opening Balance Journal, and start double-entry accounting from today.</p>
                </label></div>
                <div class="col-lg-6 mb-3"><label class="business-option {{ $hasHistoricalData ? 'disabled' : ($mode === 'new_business' ? 'selected' : '') }}">
                    <input type="radio" name="mode" value="new_business" {{ $mode === 'new_business' ? 'checked' : '' }} {{ $hasHistoricalData ? 'disabled' : '' }}>
                    <span class="badge badge-soft-secondary">Clean start</span><h4>New Business</h4>
                    <p>Use this only if this company has no historical transactions or balances. Accounting will start clean from today with no opening balances.</p>
                    @if($hasHistoricalData)<div class="alert alert-warning mt-3 mb-0">Historical data was detected, so Existing Business mode is required.</div>@endif
                </label></div>
            </div>
            <div class="wizard-actions justify-content-end"><button type="button" class="btn btn-activation wizard-next">Continue <i class="fa fa-arrow-right ml-1"></i></button></div>
        </div>

        <div class="wizard-panel" data-step="2">
            <h3 class="wizard-title">Readiness check</h3>
            <p class="wizard-copy">SalePro checked the operational data needed to initialize accounting safely.</p>
            <div class="status-summary {{ $overallStatus }}"><i class="fa {{ $overallStatus === 'pass' ? 'fa-check-circle' : ($overallStatus === 'warn' ? 'fa-exclamation-triangle' : 'fa-times-circle') }} mr-2"></i>{{ $overallStatus === 'pass' ? 'READY FOR ACTIVATION' : ($overallStatus === 'warn' ? 'READY WITH WARNINGS' : 'CANNOT ACTIVATE') }}</div>
            @foreach($validationResults as $validation)
                <div class="readiness-row">
                    <span class="readiness-icon {{ $validation['status'] }}"><i class="fa {{ $validation['status'] === 'pass' ? 'fa-check' : ($validation['status'] === 'warn' ? 'fa-exclamation' : 'fa-times') }}"></i></span>
                    <div class="flex-grow-1"><div class="d-flex justify-content-between align-items-start"><strong>{{ $validation['label'] }}</strong><span class="badge badge-{{ $validation['status'] === 'pass' ? 'success' : ($validation['status'] === 'warn' ? 'warning' : 'danger') }}">{{ strtoupper($validation['status']) }}</span></div>
                    @if($validation['message'])<div class="text-muted mt-1">{{ $validation['message'] }}</div>@endif</div>
                </div>
            @endforeach
            <div class="wizard-actions"><button type="button" class="btn btn-outline-secondary wizard-back"><i class="fa fa-arrow-left mr-1"></i> Back</button><button type="button" class="btn btn-activation wizard-next" {{ !$canActivate ? 'disabled' : '' }}>Review opening balance <i class="fa fa-arrow-right ml-1"></i></button></div>
        </div>

        <div class="wizard-panel" data-step="3">
            <h3 class="wizard-title">Opening balance review</h3>
            <p class="wizard-copy">Review the accounting foundation SalePro will create. No operational transaction will be changed.</p>
            <div id="existing-balance-review" class="{{ $mode === 'new_business' ? 'd-none' : '' }}">
                <label><input type="checkbox" name="opening_balances_confirmed" value="1" {{ $mode === 'existing_business' ? 'required' : '' }}> {{ __('db.accounting_opening_balances_review_confirmation') }}</label>
                <div class="row">
                    <div class="col-lg-4 mb-3"><div class="equation-card"><h5>Assets</h5><div class="amount-row"><span>Cash / Bank</span><strong>{{ number_format((float)$assets['cash_and_bank'], config('decimal',2)) }}</strong></div><div class="amount-row"><span>Accounts Receivable</span><strong>{{ number_format((float)$assets['accounts_receivable'], config('decimal',2)) }}</strong></div><div class="amount-row"><span>Inventory</span><strong>{{ number_format((float)$assets['inventory_value'], config('decimal',2)) }}</strong></div><div class="amount-row amount-total"><span>Total Assets</span><strong>{{ number_format((float)$assets['total'], config('decimal',2)) }}</strong></div></div></div>
                    <div class="col-lg-4 mb-3"><div class="equation-card"><h5>Liabilities</h5><div class="amount-row"><span>Accounts Payable</span><strong>{{ number_format((float)$liabilities['accounts_payable'], config('decimal',2)) }}</strong></div><div class="amount-row amount-total"><span>Total Liabilities</span><strong>{{ number_format((float)$liabilities['total'], config('decimal',2)) }}</strong></div></div></div>
                    <div class="col-lg-4 mb-3"><div class="equation-card"><h5>Equity</h5><div class="amount-row"><span>Opening Balance Equity</span><strong>{{ number_format((float)$equity, config('decimal',2)) }}</strong></div></div></div>
                </div>
                <div class="table-responsive mt-3"><table class="table table-bordered">
                    <thead><tr><th>Opening journal preview</th><th class="text-right">Debit</th><th class="text-right">Credit</th></tr></thead>
                    <tbody>@foreach($openingJournalPreview['lines'] as $line)<tr><td>{{ $line['account'] }}</td><td class="text-right">{{ number_format((float)$line['debit'],config('decimal',2)) }}</td><td class="text-right">{{ number_format((float)$line['credit'],config('decimal',2)) }}</td></tr>@endforeach</tbody>
                    <tfoot><tr><th>Total</th><th class="text-right">{{ number_format((float)$openingJournalPreview['debit_total'],config('decimal',2)) }}</th><th class="text-right">{{ number_format((float)$openingJournalPreview['credit_total'],config('decimal',2)) }}</th></tr><tr><th colspan="2">Difference</th><th class="text-right {{ abs($openingJournalPreview['difference']) < .0001 ? 'text-success' : 'text-danger' }}">{{ number_format((float)$openingJournalPreview['difference'],config('decimal',2)) }}</th></tr></tfoot>
                </table></div>
            </div>
            <div id="new-business-review" class="alert alert-info {{ $mode === 'existing_business' ? 'd-none' : '' }}">This company has no historical data. Accounting will start clean with no opening balance journal.</div>
            <div class="activation-meta"><div><small>Activation date</small><strong>{{ $activationDate }}</strong></div><div><small>Activation mode</small><strong id="activation-mode-label">{{ $mode === 'existing_business' ? 'Existing Business' : 'New Business' }}</strong></div></div>
            <div class="wizard-actions"><button type="button" class="btn btn-outline-secondary wizard-back"><i class="fa fa-arrow-left mr-1"></i> Back</button><button type="submit" class="btn btn-activation" {{ !$canActivate ? 'disabled' : '' }}><i class="fa fa-check-circle mr-1"></i> Initialize Double-Entry Accounting</button></div>
        </div>
    </form>
</div></div></div></section>

@push('scripts')
<script>
(function(){
    var step=1,panels=document.querySelectorAll('[data-step]'),indicators=document.querySelectorAll('[data-step-indicator]');
    function showStep(next){step=next;panels.forEach(function(p){p.classList.toggle('active',Number(p.dataset.step)===step)});indicators.forEach(function(i){var n=Number(i.dataset.stepIndicator);i.classList.toggle('active',n===step);i.classList.toggle('complete',n<step)});window.scrollTo({top:0,behavior:'smooth'})}
    document.querySelectorAll('.wizard-next').forEach(function(b){b.addEventListener('click',function(){showStep(Math.min(step+1,3))})});
    document.querySelectorAll('.wizard-back').forEach(function(b){b.addEventListener('click',function(){showStep(Math.max(step-1,1))})});
    document.querySelectorAll('input[name="mode"]').forEach(function(r){r.addEventListener('change',function(){document.querySelectorAll('.business-option').forEach(function(o){o.classList.remove('selected')});r.closest('.business-option').classList.add('selected');var existing=r.value==='existing_business';document.getElementById('existing-balance-review').classList.toggle('d-none',!existing);document.getElementById('new-business-review').classList.toggle('d-none',existing);document.getElementById('activation-mode-label').textContent=existing?'Existing Business':'New Business'})});
}());
</script>
@endpush
