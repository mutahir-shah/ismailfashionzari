@extends('backend.layout.main')
@section('content')
<style>
    .activation-success{max-width:760px;margin:30px auto;border:0;border-radius:8px;box-shadow:0 2px 18px rgba(34,41,47,.08)}.success-mark{display:flex;width:72px;height:72px;margin:0 auto 22px;align-items:center;justify-content:center;border-radius:50%;color:#16803c;background:#dcfce7;font-size:32px}.completion-details{margin:28px 0;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden}.completion-row{display:flex;justify-content:space-between;gap:20px;padding:14px 18px;border-bottom:1px solid #e5e7eb}.completion-row:last-child{border-bottom:0}.completion-row span{color:#6c757d}.completion-actions{display:flex;flex-wrap:wrap;justify-content:center;gap:10px}.btn-activation{background:#7c3aed;border-color:#7c3aed;color:#fff}.btn-activation:hover{background:#6d28d9;border-color:#6d28d9;color:#fff}@media(max-width:575px){.completion-row{display:block}.completion-row strong{display:block;margin-top:4px}.completion-actions .btn{width:100%}}
</style>
<section class="forms"><div class="container-fluid"><div class="card activation-success"><div class="card-body text-center p-4 p-md-5">
    <div class="success-mark"><i class="fa fa-check"></i></div>
    <h2>Double-entry accounting has been initialized successfully.</h2>
    <p class="text-muted mt-2">Your accounting reports are ready to use.</p>
    <div class="completion-details text-left">
        <div class="completion-row"><span>Activation date</span><strong>{{ $session->start_date }}</strong></div>
        <div class="completion-row"><span>Opening journal reference</span><strong>{{ $journal->reference_no ?? 'No opening journal required' }}</strong></div>
        <div class="completion-row"><span>Activated by</span><strong>{{ $activatedBy }}</strong></div>
        <div class="completion-row"><span>Activation mode</span><strong>{{ $session->mode === 'existing_business' ? 'Existing Business' : 'New Business' }}</strong></div>
    </div>
    <div class="completion-actions">
        <a href="{{ route('accounting.trialBalance') }}" class="btn btn-activation"><i class="fa fa-balance-scale mr-1"></i> Go to Trial Balance</a>
        <a href="{{ route('accounting.generalLedger') }}" class="btn btn-outline-secondary"><i class="fa fa-book mr-1"></i> Go to General Ledger</a>
        <a href="{{ route('accounting.generalLedger') }}" class="btn btn-outline-secondary"><i class="fa fa-list-alt mr-1"></i> View Journal Entries</a>
    </div>
</div></div></div></section>
@endsection
