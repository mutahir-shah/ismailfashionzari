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
                        <a class="nav-link active" href="{{ route('report.profitability.product', $profitabilityTabParams) }}">{{ __('db.profit_by_product') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('report.profitability.category', $profitabilityTabParams) }}">{{ __('db.profit_by_category') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('report.profitability.brand', $profitabilityTabParams) }}">{{ __('db.profit_by_brand') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('report.profitability.customer', $profitabilityTabParams) }}">{{ __('db.profit_by_customer') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('report.profitability.location', $profitabilityTabParams) }}">{{ __('db.profit_by_location') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('report.profitability.invoice', $profitabilityTabParams) }}">{{ __('db.profit_by_invoice') }}</a>
                    </li>
                </ul>

                <form id="profitability-product-filter">
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

                        <div class="col-md-3 mt-3 d-flex align-items-center">
                            <div class="form-check mt-3">
                                <input type="checkbox" class="form-check-input" id="group-variants" name="group_variants" value="1" @checked($group_variants)>
                                <label class="form-check-label" for="group-variants">{{ __('db.profitability_group_variants') }}</label>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="card profitability-summary-card">
                            <div class="card-body">
                                <div class="summary-label">{{ __('db.profitability_net_sales') }}</div>
                                <div class="summary-value" id="profitability-total-net-sales">{{ format_currency(0) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
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

                <div id="profitability-estimated-note" class="alert alert-warning profitability-note">
                    {{ __('db.profitability_estimated_cost_note') }}
                    <span id="profitability-cost-basis-detail" class="d-block mt-1"></span>
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table id="profitability-product-table" class="table table-hover" style="width:100%">
            <thead>
                <tr>
                    <th>{{ __('db.product') }}</th>
                    <th>{{ __('db.profitability_product_code_sku') }}</th>
                    <th>{{ __('db.profitability_net_quantity') }}</th>
                    <th>{{ __('db.profitability_gross_sales') }}</th>
                    <th>{{ __('db.Discount') }}</th>
                    <th>{{ __('db.Sale Return') }}</th>
                    <th>{{ __('db.profitability_net_sales') }}</th>
                    <th>{{ __('db.profitability_net_cost') }}</th>
                    <th>{{ __('db.profitability_gross_profit') }}</th>
                    <th>{{ __('db.profitability_gross_margin') }}</th>
                    <th>{{ __('db.profitability_invoice_count') }}</th>
                </tr>
            </thead>
            <tfoot class="tfoot active">
                <tr>
                    <th>{{ __('db.Total') }}</th>
                    <th></th>
                    <th id="profitability-footer-net-quantity"></th>
                    <th id="profitability-footer-gross-sales"></th>
                    <th id="profitability-footer-discounts"></th>
                    <th id="profitability-footer-returns"></th>
                    <th id="profitability-footer-net-sales"></th>
                    <th id="profitability-footer-net-cost"></th>
                    <th id="profitability-footer-gross-profit"></th>
                    <th id="profitability-footer-gross-margin"></th>
                    <th id="profitability-footer-invoice-count"></th>
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

    const profitabilityTable = $('#profitability-product-table').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        ordering: true,
        ajax: {
            url: "{{ route('report.profitability.productData') }}",
            type: 'POST',
            dataType: 'json',
            data: function (d) {
                d.start_date = $('#profitability-product-filter input[name=start_date]').val();
                d.end_date = $('#profitability-product-filter input[name=end_date]').val();
                d.warehouse_id = $('#profitability-product-filter select[name=warehouse_id]').val();
                d.group_variants = $('#group-variants').is(':checked') ? 1 : 0;
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
        columns: [
            { data: 'product', name: 'product' },
            { data: 'product_code', name: 'product_code' },
            { data: 'net_quantity', name: 'net_quantity' },
            { data: 'gross_sales', name: 'gross_sales' },
            { data: 'discounts', name: 'discounts' },
            { data: 'returns', name: 'returns' },
            { data: 'net_sales', name: 'net_sales' },
            { data: 'net_cost', name: 'net_cost' },
            { data: 'gross_profit', name: 'gross_profit' },
            { data: 'gross_margin', name: 'gross_margin' },
            { data: 'invoice_count', name: 'invoice_count' }
        ],
        order: [[8, 'desc']],
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
        $('#profitability-product-filter input[name=start_date]').val(picker.startDate.format('YYYY-MM-DD'));
        $('#profitability-product-filter input[name=end_date]').val(picker.endDate.format('YYYY-MM-DD'));
        profitabilityTable.ajax.reload();
    });

    $('#profitability-product-filter select[name=warehouse_id]').on('changed.bs.select change', function () {
        profitabilityTable.ajax.reload();
    });

    $('#group-variants').on('change', function () {
        profitabilityTable.ajax.reload();
    });

    function updateProfitabilityTotals(totals) {
        $('#profitability-total-net-sales').text(totals.net_sales || '{{ format_currency(0) }}');
        $('#profitability-total-net-cost').text(totals.net_cost || '{{ format_currency(0) }}');
        $('#profitability-total-gross-profit').text(totals.gross_profit || '{{ format_currency(0) }}');
        $('#profitability-total-gross-margin').text(totals.gross_margin || profitabilityLabels.notAvailable);

        $('#profitability-footer-net-quantity').text(totals.net_quantity || '0');
        $('#profitability-footer-gross-sales').text(totals.gross_sales || '{{ format_currency(0) }}');
        $('#profitability-footer-discounts').text(totals.discounts || '{{ format_currency(0) }}');
        $('#profitability-footer-returns').text(totals.returns || '{{ format_currency(0) }}');
        $('#profitability-footer-net-sales').text(totals.net_sales || '{{ format_currency(0) }}');
        $('#profitability-footer-net-cost').text(totals.net_cost || '{{ format_currency(0) }}');
        $('#profitability-footer-gross-profit').text(totals.gross_profit || '{{ format_currency(0) }}');
        $('#profitability-footer-gross-margin').text(totals.gross_margin || profitabilityLabels.notAvailable);
        $('#profitability-footer-invoice-count').text(totals.invoice_count || 0);

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
