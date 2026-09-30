@extends('backend.layout.main')
@section('content')
<section class="container-fluid">
    <div class="card mt-3">
        <div class="card-body">
            <h3>{{ __('db.inventory_close_title') }}</h3>
            <p class="text-muted">{{ __('db.inventory_close_current_cutoff_notice') }}</p>
            <form method="GET" class="form-row">
                <div class="col-md-4"><label>{{ __('db.inventory_close_period_start') }}</label><input class="form-control" type="date" name="period_start" value="{{ $preview['period_start'] }}"></div>
                <div class="col-md-4"><label>{{ __('db.inventory_close_period_end') }}</label><input class="form-control" type="date" name="period_end" value="{{ $preview['period_end'] }}"></div>
                <div class="col-md-4 align-self-end"><button class="btn btn-primary">{{ __('db.inventory_close_preview') }}</button></div>
            </form>
        </div>
    </div>
    @foreach($preview['blocking_errors'] as $error)
        <div class="alert alert-danger">{{ __('db.inventory_close_block_'.$error) }}</div>
    @endforeach
    <div class="card"><div class="card-body"><table class="table table-bordered">
        <tr><th>{{ __('db.inventory_close_book_inventory') }}</th><td>{{ number_format($preview['book_inventory'], 2) }}</td></tr>
        <tr><th>{{ __('db.inventory_close_operational_inventory') }}</th><td>{{ number_format($preview['operational_inventory'], 2) }}</td></tr>
        <tr><th>{{ __('db.inventory_close_proposed_adjustment') }}</th><td>{{ number_format($preview['adjustment'], 2) }}</td></tr>
        <tr><th>{{ __('db.inventory_close_scope') }}</th><td>{{ __('db.inventory_close_company_scope') }}</td></tr>
    </table>
    @if($preview['can_post'])
        <form method="POST" action="{{ route('accounting.inventory-close.store') }}">@csrf
            <input type="hidden" name="period_start" value="{{ $preview['period_start'] }}"><input type="hidden" name="period_end" value="{{ $preview['period_end'] }}">
            <button class="btn btn-success">{{ __('db.inventory_close_post') }}</button>
        </form>
    @elseif($preview['active_close'])
        <div class="alert alert-info">{{ __('db.inventory_close_already_posted') }}</div>
    @endif
    </div></div>
    <div class="card"><div class="card-body"><h4>{{ __('db.inventory_close_warehouse_breakdown') }}</h4><table class="table">
        @foreach($preview['valuation']['warehouses'] as $warehouse)<tr><td>{{ $warehouse['warehouse'] }}</td><td>{{ number_format($warehouse['value'], 2) }}</td></tr>@endforeach
    </table></div></div>
    <div class="card"><div class="card-body"><h4>{{ __('db.inventory_close_history') }}</h4><table class="table"><tbody>
        @foreach($history as $close)<tr><td>{{ $close->period_start->toDateString() }} — {{ $close->period_end->toDateString() }}</td><td>{{ __('db.inventory_close_status_'.$close->status) }}</td><td>{{ number_format($close->adjustment, 2) }}</td><td>
            @if(in_array($close->status, ['posted','zero']))<form method="POST" action="{{ route('accounting.inventory-close.reverse', $close) }}">@csrf<button class="btn btn-sm btn-warning">{{ __('db.inventory_close_reverse') }}</button></form>@endif
        </td></tr>@endforeach
    </tbody></table></div></div>
</section>
@endsection
