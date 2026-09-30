@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush


@section('content')
@php
    $tr = fn ($key, $fallback) => __($key) === $key ? $fallback : __($key);
@endphp
<div class="container-fluid">

    <x-success-message key="message" />
    <x-error-message key="not_permitted" />

    <button id="full-page-print-btn" type="button" class="btn btn-default btn-sm">
        <i class="ti ti-printer"></i> {{__('db.print')}}
    </button>
    <div class="card mt-5" id="supplier-info">
        <div class="card-header d-flex justify-content-between align-items-start">
            <!-- Left side: Name, Company, Phone -->
            <div>
                <span><h4 class="mb-1">{{ $lims_supplier_data->name ?? '-' }}</h4>{{ __('db.Supplier') }}</span>
                <p class="mb-0"><strong>{{ $tr('db.Company', 'Company') }}:</strong> {{ $lims_supplier_data->company_name ?? '-' }}</p>
                <p class="mb-0"><strong>{{ $tr('db.Phone Number', 'Phone') }}:</strong> {{ $lims_supplier_data->phone_number ?? '-' }}</p>
            </div>

            <!-- Right side: Address, city & country -->
            <div class="text-end">
                <p class="mb-0">
                    <strong>{{ $tr('db.Address', 'Address') }}:</strong> {{ $lims_supplier_data->address ?? '-' }}<br>
                    <strong>{{ $tr('db.City', 'City') }}:</strong> {{ $lims_supplier_data->city ?? '-' }}<br>
                    <strong>{{ $tr('db.Country', 'Country') }}:</strong> {{ $lims_supplier_data->country ?? '-' }}
                </p>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs" id="lpp-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" href="#ledger-latest" role="tab" data-toggle="tab">
                {{ $tr('db.Ledger', 'Ledger') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#purchase-latest" role="tab" data-toggle="tab">
                {{ __('db.Purchase') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#payment-latest" role="tab" data-toggle="tab">
                {{ __('db.Payment') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#purchase-return-latest" role="tab" data-toggle="tab">
                {{ $tr('db.Purchase Return', 'Purchase Return') }}
            </a>
        </li>
    </ul>

    <div id="ledger-summery"
     class="d-flex mt-3 text-center d-none gap-2" style="gap: 0.75rem;">

        <div class="flex-fill p-2 border rounded bg-light">
            <strong>{{ $tr('db.Opening Balance', 'Opening Balance') }}</strong><br>
            <h5>{{ number_format($opening_balance, 2) }}</h5>
        </div>

        <div class="flex-fill p-2 border rounded bg-light">
            <strong>{{ $tr('db.Gross Purchase', 'Gross Purchase') }}</strong><br>
            <h5>{{ number_format($total_purchase, 2) }}</h5>
        </div>

        <div class="flex-fill p-2 border rounded bg-light">
            <strong>{{ $tr('db.Total Returns', 'Total Returns') }}</strong><br>
            <h5>{{ number_format($total_returns, 2) }}</h5>
        </div>

        <div class="flex-fill p-2 border rounded bg-light">
            <strong>{{ $tr('db.Net Purchase', 'Net Purchase') }}</strong><br>
            <h5>{{ number_format($net_purchase, 2) }}</h5>
        </div>

        <div class="flex-fill p-2 border rounded bg-light">
            <strong>{{ $tr('db.Total Paid', 'Total Paid') }}</strong><br>
            <h5>{{ number_format($total_paid, 2) }}</h5>
        </div>

        <div class="flex-fill p-2 border rounded bg-light">
            <strong>{{ $tr('db.Balance Due', 'Balance Due') }}</strong><br>
            <h5 class="text-danger">{{ number_format($balance_due, 2) }}</h5>
        </div>

    </div>


    <div class="tab-content mb-5">
        <div role="tabpanel" class="tab-pane fade show active" id="ledger-latest">
            <div class="table-responsive">
                <table id="recent-ledger" class="table w-100">
                    <thead>
                        <tr>
                            <th>{{ __('db.date') }}</th>
                            <th>{{ $tr('db.Type', 'Type') }}</th>
                            <th>{{ __('db.reference') }}</th>
                            <th>{{ $tr('db.Debit', 'Debit') }}</th>
                            <th>{{ $tr('db.Credit', 'Credit') }}</th>
                            <th>{{ $tr('db.Balance', 'Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <div role="tabpanel" class="tab-pane fade" id="purchase-latest">
            <div class="table-responsive">
                <table id="recent-purchase" class="table w-100">
                    <thead>
                        <tr>
                            <th>{{ __('db.date') }}</th>
                            <th>{{ __('db.reference') }}</th>
                            <th>{{ __('db.Warehouse') }}</th>
                            <th>{{ __('db.Purchase Status') }}</th>
                            <th>{{ __('db.Payment Status') }}</th>
                            <th>{{ __('db.grand total') }}</th>
                            <th>{{ __('db.Paid Amount') }}</th>
                            <th>{{ __('db.Due') }}</th>
                            <th class="not-exported">{{ __('db.action') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane fade" id="payment-latest">
            <div class="table-responsive">
                <table id="recent-payment" class="table w-100">
                    <thead>
                        <tr>
                            <th>{{ __('db.date') }}</th>
                            <th>{{ __('db.reference') }}</th>
                            <th>{{ __('db.Amount') }}</th>
                            <th>{{ __('db.Payment Method') }}</th>
                            <th>{{ __('db.payment_at') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <div role="tabpanel" class="tab-pane fade" id="purchase-return-latest">
            <div class="table-responsive">
                <table id="recent-purchase-return" class="table w-100">
                    <thead>
                        <tr>
                            <th>{{ __('db.date') }}</th>
                            <th>{{ __('db.reference') }}</th>
                            <th>{{ $tr('db.Purchase Reference', 'Purchase Reference') }}</th>
                            <th>{{ __('db.Warehouse') }}</th>
                            <th>{{ $tr('db.Items', 'Items') }}</th>
                            <th>{{ $tr('db.Quantity', 'Quantity') }}</th>
                            <th>{{ __('db.grand total') }}</th>
                            <th>{{ $tr('db.Return Note', 'Return Note') }}</th>
                            <th>{{ $tr('db.Staff Note', 'Staff Note') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="purchase-details" class="modal fade text-left" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="container mt-3 pb-2 border-bottom">
                    <div class="row">
                        <div class="col-md-6 d-print-none">
                            <button id="print-btn" type="button" class="btn btn-default btn-sm">
                                <i class="ti ti-printer"></i> {{__('db.print')}}
                            </button>
                        </div>
                        <div class="col-md-6 d-print-none">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true"><i class="ti ti-x"></i></span>
                            </button>
                        </div>
                        <div class="col-md-12 text-center">
                            <h3 class="modal-title">{{gen_setting()->site_title}}</h3>
                            <i style="font-size: 15px;">{{__('db.Purchase Details')}}</i>
                        </div>
                    </div>
                </div>

                <div id="purchase-content" class="modal-body"></div>

                <table class="table table-bordered product-purchase-list">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{__('db.Product')}}</th>
                            <th>{{__('db.Batch No')}}</th>
                            <th>Qty</th>
                            <th>{{__('db.Returned')}}</th>
                            <th>{{__('db.Unit Cost')}}</th>
                            <th>{{__('db.Tax')}}</th>
                            <th>{{__('db.Discount')}}</th>
                            <th>{{__('db.Subtotal')}}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <div id="purchase-footer" class="modal-body"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script>
    $(document).ready(function () {
        const exportTitles = {
            ledger: @json($tr('db.Supplier Ledger', 'Supplier Ledger')),
            purchases: @json($tr('db.Supplier Purchases', 'Supplier Purchases')),
            payments: @json($tr('db.Supplier Payments', 'Supplier Payments')),
            returns: @json($tr('db.Supplier Purchase Returns', 'Supplier Purchase Returns')),
            print: @json($tr('db.Supplier Details', 'Supplier Details'))
        };

        // SUPPLIER LEDGER
        $('#recent-ledger').DataTable({
            ajax: "{{ route('suppliers.ledger', $lims_supplier_data->id) }}",
            columns: [
                { data: 'date' },
                { data: 'type' },
                { data: 'reference' },
                { data: 'debit' },
                { data: 'credit' },
                { data: 'balance' }
            ],
            order: [[0, 'desc']],
            responsive: true,
            dom: '<"row"lfB>rtip',
            buttons: [
                {
                    extend: "pdfHtml5",
                    text: '<i class="ti ti-file-type-pdf"></i>',
                    className: 'btn btn-sm btn-danger me-1',
                    title: exportTitles.ledger,
                    footer: true,
                    exportOptions: {
                        columns: ':visible:not(.not-exported)'
                    },
                    customize: function (doc) {
                        doc.defaultStyle.fontSize = 9;
                        doc.styles.tableHeader.fontSize = 10;
                        doc.content.splice(0, 1); // remove auto title if needed
                    }
                },
                {
                    extend: "csvHtml5",
                    text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                    className: 'btn btn-sm btn-primary',
                    footer: true,
                    exportOptions: {
                        columns: ':visible:not(.not-exported)',
                        rows: ':visible'
                    }
                },
            ]
        });
        
        $('#recent-purchase').DataTable({
            ajax: "{{ route('purchase.supplier', $lims_supplier_data->id) }}",
            columns: [
                { data: 'date' },
                { data: 'reference' },
                { data: 'warehouse' },
                { data: 'purchase_status' },
                { data: 'payment_status' },
                { data: 'grand_total' },
                { data: 'paid_amount' },
                { data: 'payment_due' },
                {
                    data: 'id',
                    render: function (data, type, row) {
                        return `
                            <a href="javascript:void(0)" class="btn btn-sm btn-info view-purchase"
                            data-id="${data}" title="View">
                                <i class="ti ti-eye"></i>
                            </a>
                            <a href="{{ url('purchases') }}/${data}/edit" class="btn btn-sm btn-warning" title="Edit">
                                <i class="ti ti-edit"></i>
                            </a>
                        `;
                    },
                    orderable: false,
                    searchable: false
                }
            ],
            order: [[0, 'desc']],
            autoWidth: false,
            responsive: true,
            dom: '<"row"lfB>rtip',
            buttons: [
                {
                    extend: "pdfHtml5",
                    text: '<i class="ti ti-file-type-pdf"></i>',
                    className: 'btn btn-sm btn-danger me-1',
                    title: exportTitles.purchases,
                    footer: true,
                    exportOptions: {
                        columns: ':visible:not(.not-exported)'
                    },
                    customize: function (doc) {
                        doc.defaultStyle.fontSize = 9;
                        doc.styles.tableHeader.fontSize = 10;
                        doc.content.splice(0, 1); // remove auto title if needed
                    }
                },
                {
                    extend: "csvHtml5",
                    text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                    className: 'btn btn-sm btn-primary',
                    footer: true,
                    exportOptions: {
                        columns: ':visible:not(.not-exported)',
                        rows: ':visible'
                    }
                },
            ]
        });

        $('#recent-payment').DataTable({
            ajax: "{{ route('suppliers.payments', $lims_supplier_data->id) }}",
            columns: [
                { data: 'created_at' },
                { data: 'payment_reference' },
                { data: 'amount' },
                { data: 'paying_method' },
                { data: 'payment_at' }
            ],
            order: [[0, 'desc']],
            autoWidth: false,
            responsive: true,
            dom: '<"row"lfB>rtip',
            buttons: [
                {
                    extend: "pdfHtml5",
                    text: '<i class="ti ti-file-type-pdf"></i>',
                    className: 'btn btn-sm btn-danger me-1',
                    title: exportTitles.payments,
                    footer: true,
                    exportOptions: {
                        columns: ':visible:not(.not-exported)'
                    },
                    customize: function (doc) {
                        doc.defaultStyle.fontSize = 9;
                        doc.styles.tableHeader.fontSize = 10;
                        doc.content.splice(0, 1); // remove auto title if needed
                    }
                },
                {
                    extend: "csvHtml5",
                    text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                    className: 'btn btn-sm btn-primary',
                    footer: true,
                    exportOptions: {
                        columns: ':visible:not(.not-exported)',
                        rows: ':visible'
                    }
                },
            ]
        });

        $('#recent-purchase-return').DataTable({
            ajax: "{{ route('suppliers.returns', $lims_supplier_data->id) }}",
            columns: [
                { data: 'date' },
                { data: 'reference' },
                { data: 'purchase_reference' },
                { data: 'warehouse' },
                { data: 'item' },
                { data: 'total_qty' },
                { data: 'grand_total' },
                { data: 'return_note' },
                { data: 'staff_note' }
            ],
            order: [[0, 'desc']],
            autoWidth: false,
            responsive: true,
            dom: '<"row"lfB>rtip',
            buttons: [
                {
                    extend: "pdfHtml5",
                    text: '<i class="ti ti-file-type-pdf"></i>',
                    className: 'btn btn-sm btn-danger me-1',
                    title: exportTitles.returns,
                    footer: true,
                    exportOptions: {
                        columns: ':visible:not(.not-exported)'
                    }
                },
                {
                    extend: "csvHtml5",
                    text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                    className: 'btn btn-sm btn-primary',
                    footer: true,
                    exportOptions: {
                        columns: ':visible:not(.not-exported)',
                        rows: ':visible'
                    }
                }
            ]
        });

        $("#print-btn").on("click", function(){
            var divContents = document.getElementById("purchase-details").innerHTML;
            var a = window.open('');
            a.document.write('<html>');
            a.document.write('<body><style>body{font-family: sans-serif;line-height: 1.15;-webkit-text-size-adjust: 100%;}.d-print-none{display:none}.text-center{text-align:center}.row{width:100%;margin-right: -15px;margin-left: -15px;}.col-md-12{width:100%;display:block;padding: 5px 15px;}.col-md-6{width: 50%;float:left;padding: 5px 15px;}table{width:100%;margin-top:30px;}th{text-aligh:left;}td{padding:10px}table, th, td{border: 1px solid black; border-collapse: collapse;}</style><style>@media print {.modal-dialog { max-width: 1000px;} }</style>');
            a.document.write(divContents);
            a.document.write('</body></html>');
            a.document.close();
            setTimeout(function(){a.close();},10);
            a.print();
        });

        $("#full-page-print-btn").on("click", function () {
            var supplierInfo = $("#supplier-info").prop("outerHTML");

            // Clone tabs and highlight only the active one
            var tabsNav = $("#lpp-tabs").clone();
            tabsNav.find("li").removeClass("active"); // remove all active
            var activeIndex = $(".tab-pane.active").index();
            tabsNav.find("li").eq(activeIndex).addClass("active");
            tabsNav = tabsNav.prop("outerHTML");

            // Ledger summary
            var ledgerSummary = $("#ledger-summery").prop("outerHTML");

            // Active tab content
            var activeTab = $(".tab-pane.active").prop("outerHTML");

            var printWindow = window.open('', '', 'height=900,width=1400');

            printWindow.document.write('<html><head><title>' + exportTitles.print + '</title>');

            printWindow.document.write(`
                <style>
                    @page { size: landscape; }
                    body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }

                    /* NAV TABS HORIZONTAL */
                    .nav-tabs {
                        display: flex;
                        border-bottom: 2px solid #000;
                        margin-bottom: 15px;
                        padding-left: 0;
                        list-style: none;
                    }
                    .nav-tabs li { margin-right: 8px; }
                    .nav-tabs li a {
                        text-decoration: none;
                        padding: 6px 14px;
                        border: 1px solid #000;
                        display: inline-block;
                        color: #000;
                        background: #f2f2f2;
                    }
                    .nav-tabs li.active a {
                        background: #719cdd;
                        color: #fff;
                        font-weight: bold;
                        border-color: #000;
                    }

                    /* Ledger summary horizontal */
                    #ledger-summery {
                        display: flex !important;
                        flex-wrap: wrap;
                        gap: 10px;
                        margin-top: 15px;
                    }
                    #ledger-summery > div {
                        flex: 1;
                        padding: 10px;
                        border: 1px solid #000;
                        border-radius: 4px;
                        background: #f8f8f8;
                        text-align: center;
                    }

                    /* Hide buttons & datatable controls */
                    .btn,
                    .dataTables_length,
                    .dataTables_filter,
                    .dataTables_info,
                    .dataTables_paginate,
                    .dt-buttons {
                        display: none !important;
                    }

                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 15px;
                    }
                    table, th, td { border: 1px solid #000; }
                    th, td { padding: 6px; text-align: left; }
                    .text-end { text-align: right; }
                    .text-center { text-align: center; }

                    /* Hide action column */
                    table th:last-child,
                    table td:last-child {
                        display: none !important;
                    }
                </style>
            `);

            printWindow.document.write('</head><body>');
            printWindow.document.write(supplierInfo);
            printWindow.document.write(tabsNav);
            printWindow.document.write(ledgerSummary);
            printWindow.document.write(activeTab);
            printWindow.document.write('</body></html>');

            printWindow.document.close();

            setTimeout(function () {
                printWindow.print();
                printWindow.close();
            }, 700);
        });

        $(document).on("click", ".view-purchase", function(){
            var table = $('#recent-purchase').DataTable();
            var rowData = table.row($(this).parents('tr')).data();
            purchaseDetails(rowData);
        });
    });

    function purchaseDetails(purchase) {
        var currencyText = purchase.currency ? (purchase.currency.code || purchase.currency.name) : 'N/A';
        var noteText = purchase.note ?? '';

        var htmltext = `
            {{__("db.date")}}: ${purchase.date}<br>
            {{__("db.reference")}}: ${purchase.reference}<br>
            {{__("db.Purchase Status")}}: ${purchase.purchase_status}<br>
            {{__("db.Currency")}}: ${currencyText}<br>
        `;

        if (purchase.document) {
            htmltext += '{{__("db.Attach Document")}}: <a href="documents/purchase/' + purchase.document + '" target="_blank">Download</a><br>';
        }

        htmltext += `
            <br>
            <div class="row">
                <div class="col-md-6">
                    {{__("db.From")}}:<br>
                    ${purchase.supplier_name}<br>
                    ${purchase.supplier_company}<br>
                    ${purchase.supplier_address}
                </div>
                <div class="col-md-6">
                    <div class="float-right">
                        {{__("db.To")}}:<br>
                        ${purchase.warehouse}
                    </div>
                </div>
            </div>
        `;

        // Clear previous table content
        var $table = $("table.product-purchase-list");
        $table.find("tbody").remove();

        // Fetch product purchase items
        $.get("{{url('purchases/product_purchase')}}/" + purchase.id, function(data) {
            var $newBody = $("<tbody>");

            if (data && data[0] && data[0].length > 0) {
                for (var i = 0; i < data[0].length; i++) {
                    var $newRow = $(`
                        <tr>
                            <td>${i + 1}</td>
                            <td>${data[0][i]}</td> <!-- name + code -->
                            <td>${data[7][i]}</td> <!-- batch_no -->
                            <td>${data[1][i]} ${data[2][i]}</td> <!-- qty + unit -->
                            <td>${data[8][i]}</td> <!-- returned qty -->
                            <td>${data[6][i]}</td> <!-- unit cost / total -->
                            <td>${data[3][i]} (${data[4][i]}%)</td> <!-- tax -->
                            <td>${data[5][i]}</td> <!-- discount -->
                            <td>${data[6][i]}</td> <!-- subtotal -->
                        </tr>
                    `);
                    $newBody.append($newRow);
                }
            } else {
                $newBody.append('<tr><td colspan="9" class="text-center">No products found</td></tr>');
            }

            $table.append($newBody);
        }).fail(function() {
            $table.append('<tbody><tr><td colspan="9" class="text-center text-danger">Failed to load products</td></tr></tbody>');
        });

        $('#purchase-content').html(htmltext);
        $('#purchase-footer').html(`<p>{{__("db.note")}}: ${noteText}</p>`);
        $('#purchase-details').modal('show');
    }

    $('#ledger-summery').removeClass('d-none');

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        if ($(e.target).attr('href') === '#ledger-latest') {
            $('#ledger-summery').removeClass('d-none');
        } else {
            $('#ledger-summery').addClass('d-none');
        }
    });

</script>
@endpush
