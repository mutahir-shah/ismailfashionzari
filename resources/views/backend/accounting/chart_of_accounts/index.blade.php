@extends('backend.layout.main')

@section('content')
<section>
    <div class="container-fluid">
        <div class="card mt-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h3 class="mb-1">Chart of Accounts</h3>
                        <p class="mb-0 text-muted">Safely edit account codes and names without changing semantic role mappings.</p>
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

                <div class="table-responsive">
                    <table class="table table-hover" id="chart-of-accounts-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Parent</th>
                                <th>Status</th>
                                <th>Protection</th>
                                <th class="not-exported">{{ __('db.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($accounts as $account)
                                @php
                                    $isMapped = in_array($account->id, $mappedAccountIds);
                                    $isProtected = $isMapped
                                        || $account->journal_lines_count > 0
                                        || $account->children_count > 0
                                        || $account->is_cash_account
                                        || $account->is_system
                                        || $account->is_control_account;
                                @endphp
                                <tr>
                                    <td>{{ $account->code }}</td>
                                    <td>{{ $account->name }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $account->account_type)) }}</td>
                                    <td>{{ optional($account->parent)->code ? optional($account->parent)->code . ' - ' . optional($account->parent)->name : '—' }}</td>
                                    <td>
                                        @if($account->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($isProtected)
                                            <span class="badge badge-warning">Protected</span>
                                        @else
                                            <span class="badge badge-light">Editable</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('accounting.chart-of-accounts.edit', $account) }}" class="btn btn-sm btn-primary">
                                            <i class="ti ti-pencil"></i> {{ __('db.edit') }}
                                        </a>
                                        <form action="{{ route('accounting.chart-of-accounts.destroy', $account) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this accounting account? Protected accounts will be blocked automatically.');">
                                                <i class="ti ti-trash"></i> {{ __('db.delete') }}
                                            </button>
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
    $("#chart-of-accounts-menu").addClass("active");
</script>
@endpush
