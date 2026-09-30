@extends('backend.layout.main')

@section('content')
<section>
    <div class="container-fluid">
        <div class="card mt-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h3 class="mb-1">Edit Accounting Account</h3>
                        <p class="mb-0 text-muted">Only safe fields are editable in Phase 4A. Semantic role mappings are not exposed here.</p>
                    </div>
                    <a href="{{ route('accounting.chart-of-accounts.index') }}" class="btn btn-secondary">{{ __('db.Back') }}</a>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($protectionReasons)
                    <div class="alert alert-warning">
                        <strong>Protected account.</strong>
                        Code and name can still be edited, but this account cannot be deleted or deactivated.
                        <ul class="mb-0 mt-2">
                            @foreach($protectionReasons as $reason)
                                <li>{{ $reason }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('accounting.chart-of-accounts.update', $account) }}">
                    @csrf
                    @method('PATCH')

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Account Code *</label>
                                <input type="text" name="code" class="form-control" value="{{ old('code', $account->code) }}" required>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Account Name *</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $account->name) }}" required>
                            </div>
                        </div>
                    </div>

                    @if($hasDescription)
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $account->description) }}</textarea>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Account Type</label>
                                <input type="text" class="form-control" value="{{ ucwords(str_replace('_', ' ', $account->account_type)) }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Parent Account</label>
                                <input type="text" class="form-control" value="{{ optional($account->parent)->code ? optional($account->parent)->code . ' - ' . optional($account->parent)->name : '—' }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>System Flags</label>
                                <input type="text" class="form-control" value="@if($account->is_system) System @endif @if($account->is_control_account) Control @endif @if($account->is_cash_account) Cash @endif" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="form-group form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is-active" {{ old('is_active', $account->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is-active">Active</label>
                        @if($protectionReasons)
                            <small class="form-text text-muted">Protected accounts must remain active.</small>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-primary">{{ __('db.submit') }}</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    $("ul#account").addClass("show");
    $("#chart-of-accounts-menu").addClass("active");
</script>
@endpush
