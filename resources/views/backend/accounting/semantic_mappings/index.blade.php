@extends('backend.layout.main')

@section('content')
<section>
    <div class="container-fluid">
        <div class="card mt-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h3 class="mb-1">Semantic Account Mappings</h3>
                        <p class="mb-0 text-muted">
                            Choose which accounting account fulfills each semantic accounting role. Changes affect future postings only.
                        </p>
                    </div>
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

                <div class="alert alert-info">
                    Historical journal entries are not rewritten from this page. Existing `journal_lines.accounting_account_id`
                    values remain unchanged; only future posting resolution changes.
                </div>

                <div class="table-responsive">
                    <table class="table table-hover" id="semantic-account-mappings-table">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Current Account</th>
                                <th>Allowed Type</th>
                                <th>Status</th>
                                <th>Change Mapping</th>
                                <th>Default</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $role => $meta)
                                @php
                                    $mapping = $mappings->get($role);
                                    $account = optional($mapping)->account;
                                    $roleValidation = $validation[$role] ?? ['status' => 'fail', 'message' => 'Not mapped.'];
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $meta['label'] }}</strong>
                                        <div class="small text-muted">{{ $role }}</div>
                                    </td>
                                    <td>
                                        @if($account)
                                            <strong>{{ $account->code }} - {{ $account->name }}</strong>
                                            <div class="small text-muted">
                                                {{ ucwords(str_replace('_', ' ', $account->account_type)) }}
                                                @if($account->is_cash_account)
                                                    · Cash
                                                @endif
                                                · {{ $account->is_active ? 'Active' : 'Inactive' }}
                                            </div>
                                        @else
                                            <span class="text-danger">Missing</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ collect($meta['allowed_types'])->map(fn($type) => ucwords(str_replace('_', ' ', $type)))->implode(' / ') }}
                                        @if($meta['cash_only'])
                                            <div class="small text-muted">Cash/payment accounts only</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if(($roleValidation['status'] ?? null) === 'pass')
                                            <span class="badge badge-success">✓ Valid</span>
                                        @else
                                            <span class="badge badge-danger">Invalid</span>
                                            <div class="small text-danger mt-1">{{ $roleValidation['message'] ?? 'Mapping failed validation.' }}</div>
                                        @endif
                                    </td>
                                    <td style="min-width: 330px;">
                                        <form method="POST" action="{{ route('accounting.semantic-mappings.update', $role) }}" class="form-inline">
                                            @csrf
                                            @method('PATCH')
                                            <select name="accounting_account_id" class="form-control selectpicker mr-2" data-live-search="true" title="Select compatible account" required>
                                                @foreach($compatibleAccounts[$role] as $candidate)
                                                    <option value="{{ $candidate->id }}" {{ $account && $account->id === $candidate->id ? 'selected' : '' }}>
                                                        {{ $candidate->code }} - {{ $candidate->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('accounting.semantic-mappings.restore-default', $role) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                Restore Default Mapping
                                            </button>
                                            <div class="small text-muted mt-1">
                                                {{ implode(', ', $meta['default_codes']) }}
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    $("ul#account").addClass("show");
    $("#semantic-account-mappings-menu").addClass("active");
</script>
@endpush
