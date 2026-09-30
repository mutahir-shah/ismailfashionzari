@extends('backend.layout.main')

@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')
@php
    $profitabilityTabParams = request()->only(['start_date', 'end_date', 'warehouse_id', 'group_variants']);
@endphp
<style>
    .top-fields{margin-top:10px;position:relative;}
    .top-fields label{font-size:11px;font-weight:600;margin-left:10px;padding:0 3px;position:absolute;top:-8px;z-index:9;}
    .top-fields input{font-size:13px;height:45px}
    .profitability-summary-card{min-height:118px}
    .profitability-summary-card .summary-label{font-size:13px;color:#6c757d;margin-bottom:6px}
    .profitability-summary-card .summary-value{font-size:22px;font-weight:700}
    .profitability-note{display:none}
</style>

<x-error-message key="not_permitted" />

<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">{{ __('db.profitability_analysis') }}</h3>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('report.profitability.product', $profitabilityTabParams) }}">{{ __('db.profit_by_product') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $active_tab === 'category' ? 'active' : '' }}" href="{{ route('report.profitability.category', $profitabilityTabParams) }}">{{ __('db.profit_by_category') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $active_tab === 'brand' ? 'active' : '' }}" href="{{ route('report.profitability.brand', $profitabilityTabParams) }}">{{ __('db.profit_by_brand') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $active_tab === 'customer' ? 'active' : '' }}" href="{{ route('report.profitability.customer', $profitabilityTabParams) }}">{{ __('db.profit_by_customer') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $active_tab === 'location' ? 'active' : '' }}" href="{{ route('report.profitability.location', $profitabilityTabParams) }}">{{ __('db.profit_by_location') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $active_tab === 'invoice' ? 'active' : '' }}" href="{{ route('report.profitability.invoice', $profitabilityTabParams) }}">{{ __('db.profit_by_invoice') }}</a>
                    </li>
                </ul>

                <form id="profitability-dimension-filter">
                    @csrf
                    <div class="row mb-3 justify-content-center">
                        <div class="col-md-3 mt-3">
                            <div class="form-group top-fields">
                                <label>{{ __('db.Choose Your Date') }}</label>
                                <div class="input-group">
                                    <input type="text" class="daterangepicker-field form-control" value="{{ $start_date }} {{ __('db.To') }} {{ $end_date }}" required />
                                    <input type="hidden" name="start_date" value="{{ $start_date }}" />
                                    <input type="hidden" name="end_date" value="{{ $end_date }}" />
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mt-3">
                            <div class="form-group top-fields">
                                <label>{{ __('db.Choose Warehouse') }}</label>
                                <select name="warehouse_id" class="selectpicker form-control" data-live-search="true">
                                    <option value="0">{{ __('db.All Warehouse') }}</option>
                                    @foreach($lims_warehouse_list as $warehouse)
                                        <option value="{{ $warehouse->id }}" @selected((int) $warehouse_id === (int) $warehouse->id)>{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="row">
                    @foreach($summary_counts as $summary)
                        <div class="col-md-2 mb-3">
                            <div class="card profitability-summary-card">
                                <div class="card-body">
                                    <div class="summary-label">{{ $summary['label'] }}</div>
                                    <div class="summary-value" id="profitability-total-{{ str_replace('_', '-', $summary['key']) }}">0</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <div class="col-md-2 mb-3">
                        <div class="card profitability-summary-card">
                            <div class="card-body">
                                <div class="summary-label">{{ __('db.profitability_net_sales') }}</div>
                                <div class="summary-value" id="profitability-total-net-sales">{{ format_currency(0) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card profitability-summary-card">
                            <div class="card-body">
                                <div class="summary-label">{{ __('db.profitability_net_cost') }}</div>
                                <div class="summary-value" id="profitability-total-net-cost">{{ format_currency(0) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card profitability-summary-card">
                            <div class="card-body">
                                <div class="summary-label">{{ __('db.profitability_gross_profit') }}</div>
                                <div class="summary-value" id="profitability-total-gross-profit">{{ format_currency(0) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card profitability-summary-card">
                            <div class="card-body">
                                <div class="summary-label">{{ __('db.profitability_gross_margin') }}</div>
                                <div class="summary-value" id="profitability-total-gross-margin">{{ __('db.profitability_not_available') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    {{ __('db.profitability_mixed_units_note') }}
                </div>

                <div id="profitability-estimated-note" class="alert alert-warning profitability-note">
                    {{ __('db.profitability_estimated_cost_note') }}
                    <span id="profitability-cost-basis-detail" class="d-block mt-1"></span>
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table id="profitability-dimension-table" class="table table-hover" style="width:100%">
            <thead>
                <tr>
                    @foreach($table_columns as $column)
                        <th>{{ $column['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tfoot class="tfoot active">
                <tr>
                    @foreach($table_columns as $index => $column)
                        <th id="profitability-footer-{{ str_replace('_', '-', $column['key']) }}">{{ $index === 0 ? __('db.Total') : '' }}</th>
                    @endforeach
                </tr>
            </tfoot>
        </table>
    </div>
</section>
@endsection

@push('scripts')
@include('backend.layout.partials.datatable_js')
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    const profitabilityLabels = {
        costBasisPrefix: @json(__('db.profitability_cost_basis')),
        loading: @json(__('db.Loading')),
        emptyTable: @json(__('db.profitability_empty_state')),
        zeroRecords: @json(__('db.profitability_empty_state')),
        error: @json(__('db.profitability_report_error')),
        notAvailable: @json(__('db.profitability_not_available')),
        recordsPerPage: @json(__('db.records per page')),
        showing: @json(__('db.Showing')),
        search: @json(__('db.Search'))
    };
    const profitabilityColumns = @json(collect($table_columns)->map(fn ($column) => ['data' => $column['key'], 'name' => $column['key']])->values());
    const profitabilitySummaryCounts = @json(collect($summary_counts)->pluck('key')->values());

    const profitabilityTable = $('#profitability-dimension-table').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        ordering: true,
        ajax: {
            url: @json($data_route),
            type: 'POST',
            dataType: 'json',
            data: function (d) {
                d.start_date = $('#profitability-dimension-filter input[name=start_date]').val();
                d.end_date = $('#profitability-dimension-filter input[name=end_date]').val();
                d.warehouse_id = $('#profitability-dimension-filter select[name=warehouse_id]').val();
            },
            dataSrc: function (json) {
                updateProfitabilityTotals(json.totals || {});
                return json.data || [];
            },
            error: function (xhr) {
                const message = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : profitabilityLabels.error;
                alert(message);
            }
        },
        columns: profitabilityColumns,
        order: [[@json($default_order_column), 'desc']],
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: '<"row"lf>rtip',
        language: {
            lengthMenu: '_MENU_ ' + profitabilityLabels.recordsPerPage,
            info: '<small>' + profitabilityLabels.showing + ' _START_ - _END_ (_TOTAL_)</small>',
            search: profitabilityLabels.search,
            emptyTable: profitabilityLabels.emptyTable,
            zeroRecords: profitabilityLabels.zeroRecords,
            processing: profitabilityLabels.loading,
            paginate: {
                previous: '<i class="ti ti-chevron-left"></i>',
                next: '<i class="ti ti-chevron-right"></i>'
            }
        }
    });

    $('.daterangepicker-field').on('apply.daterangepicker', function(ev, picker) {
        $('#profitability-dimension-filter input[name=start_date]').val(picker.startDate.format('YYYY-MM-DD'));
        $('#profitability-dimension-filter input[name=end_date]').val(picker.endDate.format('YYYY-MM-DD'));
        profitabilityTable.ajax.reload();
    });

    $('#profitability-dimension-filter select[name=warehouse_id]').on('changed.bs.select change', function () {
        profitabilityTable.ajax.reload();
    });

    function updateProfitabilityTotals(totals) {
        profitabilitySummaryCounts.forEach(function (key) {
            $('#profitability-total-' + key.replace(/_/g, '-')).text(totals[key] || 0);
        });
        $('#profitability-total-net-sales').text(totals.net_sales || '{{ format_currency(0) }}');
        $('#profitability-total-net-cost').text(totals.net_cost || '{{ format_currency(0) }}');
        $('#profitability-total-gross-profit').text(totals.gross_profit || '{{ format_currency(0) }}');
        $('#profitability-total-gross-margin').text(totals.gross_margin || profitabilityLabels.notAvailable);

        profitabilityColumns.forEach(function (column, index) {
            const fallback = index === 0 ? @json(__('db.Total')) : '';
            $('#profitability-footer-' + column.data.replace(/_/g, '-')).text(totals[column.data] || fallback);
        });

        if (totals.estimated_cost) {
            $('#profitability-estimated-note').show();
            $('#profitability-cost-basis-detail').text(profitabilityLabels.costBasisPrefix + ': ' + (totals.cost_basis || ''));
        } else {
            $('#profitability-estimated-note').hide();
            $('#profitability-cost-basis-detail').text('');
        }
    }
</script>
@endpush
