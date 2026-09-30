@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
    <style type="text/css">
        .btn-icon i { margin-right: 5px; }
        .top-fields { margin-top: 10px; position: relative; }
        .top-fields label { font-size: 11px; font-weight: 600; margin-left: 10px; padding: 0 3px; position: absolute; top: -8px; z-index: 9; background: #fff; }
        .top-fields input, .top-fields select { font-size: 13px; height: 45px; }
        
        .tax-summary-card {
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            margin-bottom: 20px;
            background: #fff;
            border: 1px solid #e9ecef;
        }
        .tax-summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .tax-card-body {
            padding: 18px 20px;
        }
        .tax-card-title {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            margin-bottom: 8px;
        }
        .tax-card-value {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .tax-card-sub {
            font-size: 11px;
            color: #888;
        }
        
        .nav-tabs .nav-link {
            font-weight: 600;
            color: #495057;
            padding: 10px 20px;
            border: none;
            border-bottom: 2px solid transparent;
        }
        .nav-tabs .nav-link.active {
            color: #7c5cc4;
            border-bottom: 2px solid #7c5cc4;
            background: transparent;
        }
        .tab-content {
            background: #fff;
            padding: 20px 15px;
            border-radius: 0 0 8px 8px;
            border: 1px solid #dee2e6;
            border-top: none;
        }
        .export-btn-group .btn {
            margin-left: 5px;
        }
        .table-totals-row {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .net-position-payable {
            color: #d9534f;
        }
        .net-position-credit {
            color: #5cb85c;
        }
    </style>
@endpush

@section('content')
<section>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="font-weight-bold"><i class="ti ti-receipt-tax"></i> {{ __('db.Tax Report') }}</h3>
            <button type="button" class="btn btn-warning btn-icon" id="toggle-filter">
                <i class="ti ti-filter"></i> {{ __('db.Filter') }}
            </button>
        </div>

        <!-- Filter Card -->
        <div class="card mb-4" id="filter-card">
            <div class="card-body">
                <form id="tax-filter-form" method="get">
                    <div class="row">
                        <div class="col-md-4 mt-2">
                            <div class="form-group top-fields">
                                <label>{{ __('db.Choose Your Date') }}</label>
                                <input type="text" class="daterangepicker-field form-control"
                                    value="{{ $starting_date }} To {{ $ending_date }}" required />
                                <input type="hidden" name="starting_date" id="starting_date" value="{{ $starting_date }}" />
                                <input type="hidden" name="ending_date" id="ending_date" value="{{ $ending_date }}" />
                            </div>
                        </div>

                        <div class="col-md-4 mt-2 @if(\Auth::user()->role_id > 2) d-none @endif">
                            <div class="form-group top-fields">
                                <label>{{ __('db.Choose Warehouse') }}</label>
                                <select id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true">
                                    <option value="0">{{ __('db.All Warehouse') }}</option>
                                    @foreach ($lims_warehouse_list as $warehouse)
                                        <option value="{{ $warehouse->id }}" {{ $warehouse->id == $warehouse_id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4 mt-2" id="customer-filter-wrapper">
                            <div class="form-group top-fields">
                                <label>{{ __('db.customer') }}</label>
                                <select id="customer_id" name="customer_id" class="selectpicker form-control" data-live-search="true">
                                    <option value="0">{{ __('db.All Customers') }}</option>
                                    @foreach ($lims_customer_list as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }} {{ $customer->tax_no ? '(' . $customer->tax_no . ')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4 mt-2 d-none" id="supplier-filter-wrapper">
                            <div class="form-group top-fields">
                                <label>{{ __('db.Supplier') }}</label>
                                <select id="supplier_id" name="supplier_id" class="selectpicker form-control" data-live-search="true">
                                    <option value="0">{{ __('db.All Suppliers') }}</option>
                                    @foreach ($lims_supplier_list as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }} {{ $supplier->vat_number ? '(' . $supplier->vat_number . ')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row" id="summary-cards-container">
            <div class="col-md-3 col-sm-6">
                <div class="card tax-summary-card">
                    <div class="tax-card-body">
                        <div class="tax-card-title text-primary"><i class="ti ti-arrow-up-right"></i> {{ __('db.Output Tax (Sales)') }}</div>
                        <div class="tax-card-value text-primary" id="card-output-tax">0.00</div>
                        <div class="tax-card-sub">{{ __('db.Net Sales Excl. Tax') }}: <span id="card-output-taxable">0.00</span></div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card tax-summary-card">
                    <div class="tax-card-body">
                        <div class="tax-card-title text-success"><i class="ti ti-arrow-down-left"></i> {{ __('db.Input Tax (Purchases)') }}</div>
                        <div class="tax-card-value text-success" id="card-input-tax">0.00</div>
                        <div class="tax-card-sub">{{ __('db.Net Purchases Excl. Tax') }}: <span id="card-input-taxable">0.00</span></div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card tax-summary-card">
                    <div class="tax-card-body">
                        <div class="tax-card-title text-info"><i class="ti ti-file-dollar"></i> {{ __('db.Expense Tax') }}</div>
                        <div class="tax-card-value text-info" id="card-expense-tax">0.00</div>
                        <div class="tax-card-sub">{{ __('db.Total Expenses') }}: <span id="card-expense-total">0.00</span></div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card tax-summary-card">
                    <div class="tax-card-body">
                        <div class="tax-card-title text-dark"><i class="ti ti-scale"></i> {{ __('db.Net Tax Position') }}</div>
                        <div class="tax-card-value" id="card-net-tax">0.00</div>
                        <div class="tax-card-sub" id="card-net-tax-label">{{ __('db.Output Tax - Input Tax') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Tabs -->
        <ul class="nav nav-tabs" id="taxReportTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="output-tab" data-toggle="tab" href="#output-content" role="tab" aria-controls="output-content" aria-selected="true">
                    <i class="ti ti-arrow-up-right"></i> {{ __('db.Output Tax (Sales)') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="input-tab" data-toggle="tab" href="#input-content" role="tab" aria-controls="input-content" aria-selected="false">
                    <i class="ti ti-arrow-down-left"></i> {{ __('db.Input Tax (Purchases)') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="expense-tab" data-toggle="tab" href="#expense-content" role="tab" aria-controls="expense-content" aria-selected="false">
                    <i class="ti ti-file-dollar"></i> {{ __('db.Expense Tax') }}
                </a>
            </li>
        </ul>

        <div class="tab-content" id="taxReportTabsContent">
            <!-- TAB 1: OUTPUT TAX -->
            <div class="tab-pane fade show active" id="output-content" role="tabpanel" aria-labelledby="output-tab">
                <div class="d-flex justify-content-end mb-3 export-btn-group">
                    <a href="#" class="btn btn-outline-secondary btn-sm btn-export" data-type="output" data-format="csv"><i class="ti ti-file-type-csv"></i> CSV</a>
                    <a href="#" class="btn btn-outline-secondary btn-sm btn-export" data-type="output" data-format="excel"><i class="ti ti-file-type-xls"></i> Excel</a>
                    <a href="#" class="btn btn-outline-secondary btn-sm btn-export" data-type="output" data-format="pdf"><i class="ti ti-file-type-pdf"></i> PDF</a>
                    <a href="#" class="btn btn-outline-secondary btn-sm btn-print" data-type="output"><i class="ti ti-printer"></i> {{ __('db.print') }}</a>
                </div>
                <div class="table-responsive">
                    <table id="output-tax-table" class="table table-hover" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('db.Type') }}</th>
                                <th>{{ __('db.date') }}</th>
                                <th>{{ __('db.reference') }}</th>
                                <th>{{ __('db.customer') }}</th>
                                <th>{{ __('db.Tax Number') }}</th>
                                <th>{{ __('db.Warehouse') }}</th>
                                <th class="text-right">{{ __('db.Net Amount Excl. Tax') }}</th>
                                <th class="text-right">{{ __('db.Discount') }}</th>
                                <th class="text-center">{{ __('db.Tax Name / Rate') }}</th>
                                <th class="text-right">{{ __('db.Tax Amount') }}</th>
                                <th class="text-right">{{ __('db.Total Amount') }}</th>
                                <th>{{ __('db.Payment Status') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr class="table-totals-row">
                                <th colspan="7" class="text-right">{{ __('db.Total') }}:</th>
                                <th class="text-right" id="output-footer-taxable">0.00</th>
                                <th class="text-right" id="output-footer-discount">0.00</th>
                                <th></th>
                                <th class="text-right" id="output-footer-tax">0.00</th>
                                <th class="text-right" id="output-footer-total">0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- TAB 2: INPUT TAX -->
            <div class="tab-pane fade" id="input-content" role="tabpanel" aria-labelledby="input-tab">
                <div class="d-flex justify-content-end mb-3 export-btn-group">
                    <a href="#" class="btn btn-outline-secondary btn-sm btn-export" data-type="input" data-format="csv"><i class="ti ti-file-type-csv"></i> CSV</a>
                    <a href="#" class="btn btn-outline-secondary btn-sm btn-export" data-type="input" data-format="excel"><i class="ti ti-file-type-xls"></i> Excel</a>
                    <a href="#" class="btn btn-outline-secondary btn-sm btn-export" data-type="input" data-format="pdf"><i class="ti ti-file-type-pdf"></i> PDF</a>
                    <a href="#" class="btn btn-outline-secondary btn-sm btn-print" data-type="input"><i class="ti ti-printer"></i> {{ __('db.print') }}</a>
                </div>
                <div class="table-responsive">
                    <table id="input-tax-table" class="table table-hover" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('db.Type') }}</th>
                                <th>{{ __('db.date') }}</th>
                                <th>{{ __('db.reference') }}</th>
                                <th>{{ __('db.Supplier') }}</th>
                                <th>{{ __('db.Tax Number') }}</th>
                                <th>{{ __('db.Warehouse') }}</th>
                                <th class="text-right">{{ __('db.Net Amount Excl. Tax') }}</th>
                                <th class="text-right">{{ __('db.Discount') }}</th>
                                <th class="text-center">{{ __('db.Tax Name / Rate') }}</th>
                                <th class="text-right">{{ __('db.Tax Amount') }}</th>
                                <th class="text-right">{{ __('db.Total Amount') }}</th>
                                <th>{{ __('db.Payment Status') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr class="table-totals-row">
                                <th colspan="7" class="text-right">{{ __('db.Total') }}:</th>
                                <th class="text-right" id="input-footer-taxable">0.00</th>
                                <th class="text-right" id="input-footer-discount">0.00</th>
                                <th></th>
                                <th class="text-right" id="input-footer-tax">0.00</th>
                                <th class="text-right" id="input-footer-total">0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- TAB 3: EXPENSE TAX -->
            <div class="tab-pane fade" id="expense-content" role="tabpanel" aria-labelledby="expense-tab">
                <div class="d-flex justify-content-end mb-3 export-btn-group">
                    <a href="#" class="btn btn-outline-secondary btn-sm btn-export" data-type="expense" data-format="csv"><i class="ti ti-file-type-csv"></i> CSV</a>
                    <a href="#" class="btn btn-outline-secondary btn-sm btn-export" data-type="expense" data-format="excel"><i class="ti ti-file-type-xls"></i> Excel</a>
                    <a href="#" class="btn btn-outline-secondary btn-sm btn-export" data-type="expense" data-format="pdf"><i class="ti ti-file-type-pdf"></i> PDF</a>
                    <a href="#" class="btn btn-outline-secondary btn-sm btn-print" data-type="expense"><i class="ti ti-printer"></i> {{ __('db.print') }}</a>
                </div>
                <div class="table-responsive">
                    <table id="expense-tax-table" class="table table-hover" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('db.Type') }}</th>
                                <th>{{ __('db.date') }}</th>
                                <th>{{ __('db.reference') }}</th>
                                <th>{{ __('db.category') }}</th>
                                <th>{{ __('db.Warehouse') }}</th>
                                <th class="text-right">{{ __('db.Net Amount Excl. Tax') }}</th>
                                <th class="text-center">{{ __('db.Tax Name / Rate') }}</th>
                                <th class="text-right">{{ __('db.Tax Amount') }}</th>
                                <th class="text-right">{{ __('db.Total Amount') }}</th>
                                <th>{{ __('db.Payment Method') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr class="table-totals-row">
                                <th colspan="6" class="text-right">{{ __('db.Total') }}:</th>
                                <th class="text-right" id="expense-footer-taxable">0.00</th>
                                <th></th>
                                <th class="text-right" id="expense-footer-tax">0.00</th>
                                <th class="text-right" id="expense-footer-total">0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
@include('backend.layout.partials.datatable_js')
<script type="text/javascript">
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('#toggle-filter').on('click', function() {
        $('#filter-card').slideToggle('slow');
    });

    // Tab switching contact filter visibility
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("href");
        if (target === '#output-content') {
            $('#customer-filter-wrapper').removeClass('d-none');
            $('#supplier-filter-wrapper').addClass('d-none');
            outputTable.ajax.reload();
        } else if (target === '#input-content') {
            $('#customer-filter-wrapper').addClass('d-none');
            $('#supplier-filter-wrapper').removeClass('d-none');
            inputTable.ajax.reload();
        } else {
            $('#customer-filter-wrapper').addClass('d-none');
            $('#supplier-filter-wrapper').addClass('d-none');
            expenseTable.ajax.reload();
        }
    });

    function getFilterData() {
        return {
            starting_date: $('#starting_date').val(),
            ending_date: $('#ending_date').val(),
            warehouse_id: $('#warehouse_id').val(),
            customer_id: $('#customer_id').val(),
            supplier_id: $('#supplier_id').val(),
        };
    }

    function reloadAllData() {
        outputTable.ajax.reload();
        inputTable.ajax.reload();
        expenseTable.ajax.reload();
        loadSummary();
    }

    function loadSummary() {
        $.ajax({
            url: "{{ route('report.tax.summary') }}",
            type: "POST",
            data: getFilterData(),
            dataType: "json",
            success: function(res) {
                var dec = res.decimal || 2;
                $('#card-output-tax').text(parseFloat(res.output_tax).toLocaleString(undefined, {minimumFractionDigits: dec, maximumFractionDigits: dec}));
                $('#card-output-taxable').text(parseFloat(res.output_taxable).toLocaleString(undefined, {minimumFractionDigits: dec, maximumFractionDigits: dec}));

                $('#card-input-tax').text(parseFloat(res.input_tax).toLocaleString(undefined, {minimumFractionDigits: dec, maximumFractionDigits: dec}));
                $('#card-input-taxable').text(parseFloat(res.input_taxable).toLocaleString(undefined, {minimumFractionDigits: dec, maximumFractionDigits: dec}));

                $('#card-expense-tax').text(parseFloat(res.expense_tax).toLocaleString(undefined, {minimumFractionDigits: dec, maximumFractionDigits: dec}));
                $('#card-expense-total').text(parseFloat(res.expense_total).toLocaleString(undefined, {minimumFractionDigits: dec, maximumFractionDigits: dec}));

                var net = parseFloat(res.net_tax_position);
                var netEl = $('#card-net-tax');
                netEl.text(net.toLocaleString(undefined, {minimumFractionDigits: dec, maximumFractionDigits: dec}));

                if (net > 0) {
                    netEl.removeClass('net-position-credit text-dark').addClass('net-position-payable');
                    $('#card-net-tax-label').text('{{ __("db.Tax Payable to Authority") }}');
                } else if (net < 0) {
                    netEl.removeClass('net-position-payable text-dark').addClass('net-position-credit');
                    $('#card-net-tax-label').text('{{ __("db.Net Tax Credit Available") }}');
                } else {
                    netEl.removeClass('net-position-payable net-position-credit').addClass('text-dark');
                    $('#card-net-tax-label').text('{{ __("db.Output Tax - Input Tax") }}');
                }
            },
            error: function() {
                console.error("Failed to load tax summary.");
            }
        });
    }

    // ── Output Tax DataTable ──────────────────────────────────────
    var outputTable = $('#output-tax-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('report.tax.outputData') }}",
            type: "POST",
            data: function(d) {
                $.extend(d, getFilterData());
            },
            dataSrc: function(json) {
                if (json.totals) {
                    $('#output-footer-taxable').text(json.totals.taxable_amount);
                    $('#output-footer-discount').text(json.totals.discount);
                    $('#output-footer-tax').text(json.totals.tax_amount);
                    $('#output-footer-total').text(json.totals.total_amount);
                }
                return json.data;
            }
        },
        columns: [
            { data: 'index', orderable: false },
            { data: 'type_badge' },
            { data: 'date' },
            { data: 'reference' },
            { data: 'contact_name' },
            { data: 'tax_number' },
            { data: 'warehouse_name' },
            { data: 'taxable_amount', className: 'text-right' },
            { data: 'discount', className: 'text-right' },
            { data: 'tax_name_rate', className: 'text-center' },
            { data: 'tax_amount', className: 'text-right' },
            { data: 'total_amount', className: 'text-right' },
            { data: 'payment_method' },
        ],
        order: [[2, 'desc']],
        pageLength: 10,
        dom: '<"row"lf>rtip',
    });

    // ── Input Tax DataTable ───────────────────────────────────────
    var inputTable = $('#input-tax-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('report.tax.inputData') }}",
            type: "POST",
            data: function(d) {
                $.extend(d, getFilterData());
            },
            dataSrc: function(json) {
                if (json.totals) {
                    $('#input-footer-taxable').text(json.totals.taxable_amount);
                    $('#input-footer-discount').text(json.totals.discount);
                    $('#input-footer-tax').text(json.totals.tax_amount);
                    $('#input-footer-total').text(json.totals.total_amount);
                }
                return json.data;
            }
        },
        columns: [
            { data: 'index', orderable: false },
            { data: 'type_badge' },
            { data: 'date' },
            { data: 'reference' },
            { data: 'contact_name' },
            { data: 'tax_number' },
            { data: 'warehouse_name' },
            { data: 'taxable_amount', className: 'text-right' },
            { data: 'discount', className: 'text-right' },
            { data: 'tax_name_rate', className: 'text-center' },
            { data: 'tax_amount', className: 'text-right' },
            { data: 'total_amount', className: 'text-right' },
            { data: 'payment_method' },
        ],
        order: [[2, 'desc']],
        pageLength: 10,
        dom: '<"row"lf>rtip',
    });

    // ── Expense Tax DataTable ─────────────────────────────────────
    var expenseTable = $('#expense-tax-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('report.tax.expenseData') }}",
            type: "POST",
            data: function(d) {
                $.extend(d, getFilterData());
            },
            dataSrc: function(json) {
                if (json.totals) {
                    $('#expense-footer-taxable').text(json.totals.taxable_amount);
                    $('#expense-footer-tax').text(json.totals.tax_amount);
                    $('#expense-footer-total').text(json.totals.total_amount);
                }
                return json.data;
            }
        },
        columns: [
            { data: 'index', orderable: false },
            { data: 'type_badge' },
            { data: 'date' },
            { data: 'reference' },
            { data: 'contact_name' },
            { data: 'warehouse_name' },
            { data: 'taxable_amount', className: 'text-right' },
            { data: 'tax_name_rate', className: 'text-center' },
            { data: 'tax_amount', className: 'text-right' },
            { data: 'total_amount', className: 'text-right' },
            { data: 'payment_method' },
        ],
        order: [[2, 'desc']],
        pageLength: 10,
        dom: '<"row"lf>rtip',
    });

    // ── Date Range and Filter Event Handlers ──────────────────────
    $('.daterangepicker-field').on('apply.daterangepicker', function(ev, picker) {
        $('#starting_date').val(picker.startDate.format('YYYY-MM-DD'));
        $('#ending_date').val(picker.endDate.format('YYYY-MM-DD'));
        reloadAllData();
    });

    $('#warehouse_id, #customer_id, #supplier_id').on('change', function() {
        reloadAllData();
    });

    // ── Server-Side Full Exports ──────────────────────────────────
    $('.btn-export').on('click', function(e) {
        e.preventDefault();
        var type = $(this).data('type');
        var format = $(this).data('format');
        var params = $.param($.extend({ type: type, format: format }, getFilterData()));
        window.location.href = "{{ route('report.tax.export') }}?" + params;
    });

    $('.btn-print').on('click', function(e) {
        e.preventDefault();
        var type = $(this).data('type');
        var params = $.param($.extend({ type: type }, getFilterData()));
        window.open("{{ route('report.tax.print') }}?" + params, '_blank');
    });

    // Initial load
    loadSummary();
</script>
@endpush
