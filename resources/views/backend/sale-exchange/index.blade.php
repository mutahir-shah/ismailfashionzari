@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')
    <x-success-message key="message" />
    <x-error-message key="not_permitted" />

    @push('styles')
        <style>
            /* Product type separation styling */
            .product-return-list tbody tr td span.badge {
                display: inline-block;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .product-return-list tbody tr[style*='#fff5f5']:hover {
                background-color: #ffe5e5 !important;
                transition: background-color 0.2s ease;
            }

            .product-return-list tbody tr[style*='#f0fff4']:hover {
                background-color: #e0ffe5 !important;
                transition: background-color 0.2s ease;
            }

            @media (max-width: 768px) {
                .product-return-list tbody tr td span.badge {
                    font-size: 8px;
                    padding: 1px 4px;
                }
            }
        </style>
    @endpush

    <section>
        <div class="container-fluid">
            <div class="card">
                <div class="card-header mt-2">
                    <h3 class="text-center">{{ __('db.Sale Exchange List') }}</h3>
                </div>
                <form action="{{ route('exchange.index') }}" method="GET">
                <div class="row mb-3">
                    <div class="col-md-4 offset-md-2 mt-3">
                        <div class="d-flex">
                            <label class="">{{ __('db.date') }} &nbsp;</label>
                            <div class="">
                                <div class="input-group">
                                    <input type="text" class="daterangepicker-field form-control"
                                        value="{{ $starting_date }} To {{ $ending_date }}" required />
                                    <input type="hidden" name="starting_date" value="{{ $starting_date }}" />
                                    <input type="hidden" name="ending_date" value="{{ $ending_date }}" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mt-3 @if (\Auth::user()->role_id > 2) {{ 'd-none' }} @endif">
                        <div class="d-flex">
                            <label class="">{{ __('db.Warehouse') }} &nbsp;</label>
                            <div class="">
                                <select id="warehouse_id" name="warehouse_id" class="selectpicker form-control"
                                    data-live-search="true" data-live-search-style="begins">
                                    <option value="0">{{ __('db.All Warehouse') }}</option>
                                    @foreach ($lims_warehouse_list as $warehouse)
                                        @if ($warehouse->id == $warehouse_id)
                                            <option selected value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                        @else
                                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mt-3">
                        <div class="form-group">
                            <button class="btn btn-primary" id="filter-btn" type="submit">{{ __('db.submit') }}</button>
                        </div>
                    </div>
                </div>
                </form>
            </div>
            @if (in_array('exchange-add', $all_permission))
                <a href="#" data-toggle="modal" data-target="#add-exchange" class="btn btn-info"><i
                        class="ti ti-plus"></i> {{ __('db.Add Exchange') }}</a>
            @endif
        </div>
        <div class="table-responsive">
            <table id="return-table" class="table return-list" style="width: 100%">
                <thead>
                    <tr>
                        <th class="not-exported"></th>
                        <th>{{ __('db.date') }}</th>
                        <th>{{ __('db.reference') }}</th>
                        <th>{{ __('db.Sale Reference') }}</th>
                        <th>{{ __('db.Warehouse') }}</th>
                        <th>{{ __('db.Biller') }}</th>
                        <th>{{ __('db.customer') }}</th>
                        <th>{{ __('db.Payment Type') }}</th>
                        <th>{{ __('db.grand total') }}</th>
                        <th class="not-exported">{{ __('db.action') }}</th>
                    </tr>
                </thead>

                <tfoot class="tfoot active">
                    <th></th>
                    <th>{{ __('db.Total') }}</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tfoot>
            </table>
        </div>

        <div id="return-details" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"
            class="modal fade text-left">
            <div role="document" class="modal-dialog">
                <div class="modal-content">
                    <div class="container mt-3 pb-2 border-bottom">
                        <div class="row">
                            <div class="col-md-6 d-print-none">
                                <button id="print-btn" type="button" class="btn btn-default btn-sm"><i
                                        class="ti ti-printer"></i> {{ __('db.print') }}</button>
                            </div>
                            <div class="col-md-6 d-print-none">
                                <button type="button" id="close-btn" data-dismiss="modal" aria-label="Close"
                                    class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
                            </div>
                            <div class="col-md-12">
                                <h3 id="exampleModalLabel" class="modal-title text-center container-fluid">
                                    {{ gen_setting()->site_title }}</h3>
                            </div>
                            <div class="col-md-12 text-center">
                                <i style="font-size: 15px;">{{ __('db.exchange_details') }}</i>
                            </div>
                        </div>
                    </div>
                    <div id="return-content" class="modal-body"></div>
                    <br>
                    <table class="table table-bordered product-return-list">
                        <thead>
                            <th>#</th>
                            <th>{{ __('db.product') }}</th>
                            <th>{{ __('db.Batch No') }}</th>
                            <th>{{ __('db.qty') }}</th>
                            <th>{{ __('db.Unit Price') }}</th>
                            <th>{{ __('db.Tax') }}</th>
                            <th>{{ __('db.Discount') }}</th>
                            <th>{{ __('db.Subtotal') }}</th>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <div id="return-footer" class="modal-body"></div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
    <script type="text/javascript">

        var all_permission = <?php echo json_encode($all_permission); ?>;
        var return_id = [];
        var user_verified = <?php echo json_encode(env('USER_VERIFIED', 1)); ?>;

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        function confirmDelete() {
            if (confirm("Are you sure want to delete?")) {
                return true;
            }
            return false;
        }

        // Click on table row to view details
        $(document).on("click", "tr.return-link td:not(:first-child, :last-child)", function() {
            var exchangeData = $(this).parent().data('return');
            returnDetails(exchangeData);
        });

        // Click on view button
        $(document).on("click", ".view", function() {
            var exchangeData = $(this).closest('tr').data('return');
            returnDetails(exchangeData);
        });

        // Print functionality
        $("#print-btn").on("click", function() {
            var divContents = document.getElementById("return-details").innerHTML;
            var a = window.open('');
            a.document.write('<html><head><title>Print</title>');
            a.document.write(
                '<style>body{font-family: sans-serif;line-height: 1.15;-webkit-text-size-adjust: 100%;}.d-print-none{display:none}.text-center{text-align:center}.row{width:100%;margin-right: -15px;margin-left: -15px;}.col-md-12{width:100%;display:block;padding: 5px 15px;}.col-md-6{width: 50%;float:left;padding: 5px 15px;}table{width:100%;margin-top:30px;}th{text-align:left}td{padding:10px}table,th,td{border: 1px solid black; border-collapse: collapse;}</style>'
                );
            a.document.write('</head><body>');
            a.document.write(divContents);
            a.document.write('</body></html>');
            a.document.close();
            setTimeout(function() {
                a.print();
                a.close();
            }, 100);
        });

        var starting_date = $("input[name=starting_date]").val();
        var ending_date = $("input[name=ending_date]").val();
        var warehouse_id = $("#warehouse_id").val();

        // Initialize DataTable
        $('#return-table').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                url: "{{ route('exchange.data') }}",
                data: {
                    all_permission: all_permission,
                    starting_date: starting_date,
                    ending_date: ending_date,
                    warehouse_id: warehouse_id
                },
                dataType: "json",
                type: "post"
            },
            "createdRow": function(row, data, dataIndex) {
                $(row).addClass('return-link');
                $(row).attr('data-return', data['exchange']);
            },
            "columns": [{
                    "data": "key"
                },
                {
                    "data": "date"
                },
                {
                    "data": "reference_no"
                },
                {
                    "data": "sale_reference"
                },
                {
                    "data": "warehouse"
                },
                {
                    "data": "biller"
                },
                {
                    "data": "customer"
                },
                {
                    "data": "payment_type"
                },
                {
                    "data": "amount"
                },
                {
                    "data": "options"
                },
            ],
            'language': {
                'lengthMenu': '_MENU_ {{ __('db.records per page') }}',
                "info": '<small>{{ __('db.Showing') }} _START_ - _END_ (_TOTAL_)</small>',
                "search": '{{ __('db.Search') }}',
                'paginate': {
                    'previous': '<i class="ti ti-chevron-left"></i>',
                    'next': '<i class="ti ti-chevron-right"></i>'
                }
            },
            order: [
                ['1', 'desc']
            ],
            'columnDefs': [{
                    "orderable": false,
                    'targets': [0, 3, 4, 5, 6, 7, 8, 9]
                },
                {
                    'render': function(data, type, row, meta) {
                        if (type === 'display') {
                            data =
                                '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                        }
                        return data;
                    },
                    'checkboxes': {
                        'selectRow': true,
                        'selectAllRender': '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                    },
                    'targets': [0]
                }
            ],
            'select': {
                style: 'multi',
                selector: 'td:first-child'
            },
            'lengthMenu': [
                [10, 25, 50, -1],
                [10, 25, 50, "All"]
            ],
            dom: '<"row"lfB>rtip',
            rowId: 'ObjectID',
            buttons: [{
                    extend: 'pdf',
                    text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    },
                    action: function(e, dt, button, config) {
                        datatable_sum(dt, true);
                        $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                        datatable_sum(dt, false);
                    },
                    footer: true
                },
                {
                    extend: 'excel',
                    text: '<i title="export to excel" class="ti ti-file-type-xls"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    },
                    action: function(e, dt, button, config) {
                        datatable_sum(dt, true);
                        $.fn.dataTable.ext.buttons.excelHtml5.action.call(this, e, dt, button, config);
                        datatable_sum(dt, false);
                    },
                    footer: true
                },
                {
                    extend: 'csv',
                    text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    },
                    action: function(e, dt, button, config) {
                        datatable_sum(dt, true);
                        $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
                        datatable_sum(dt, false);
                    },
                    footer: true
                },
                {
                    extend: 'print',
                    text: '<i title="print" class="ti ti-printer"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    },
                    action: function(e, dt, button, config) {
                        datatable_sum(dt, true);
                        $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                        datatable_sum(dt, false);
                    },
                    footer: true
                },
                {
                    extend: 'colvis',
                    text: '<i title="column visibility" class="ti ti-eye"></i>',
                    columns: ':gt(0)'
                },
            ],
            drawCallback: function() {
                var api = this.api();
                datatable_sum(api, false);
            }
        });

        function datatable_sum(dt_selector, is_calling_first) {
            if (dt_selector.rows('.selected').any() && is_calling_first) {
                var rows = dt_selector.rows('.selected').indexes();
                $(dt_selector.column(8).footer()).html(
                    dt_selector.cells(rows, 8, {
                        page: 'current'
                    }).data().sum().toFixed({{ gen_setting()->decimal }})
                );
            } else {
                $(dt_selector.column(8).footer()).html(
                    dt_selector.column(8).data().sum().toFixed({{ gen_setting()->decimal }})
                );
            }
        }

        // Main function to display exchange details in modal
        function returnDetails(exchangeData) {
            console.log('Exchange Data:', exchangeData);

            // Parse JSON data
            var returns = typeof exchangeData === 'string' ? JSON.parse(exchangeData) : exchangeData;

            // Build header HTML
            var htmltext = '<strong>{{ __('db.date') }}: </strong>' + returns[0] + '<br>';
            htmltext += '<strong>{{ __('db.reference') }}: </strong>' + returns[1] + '<br>';
            htmltext += '<strong>{{ __('db.Sale Reference') }}: </strong>' + returns[24] + '<br>';
            htmltext += '<strong>{{ __('db.Warehouse') }}: </strong>' + returns[2] + '<br>';
            htmltext += '<strong>{{ __('db.Currency') }}: </strong>' + (returns[26] || 'BDT');

            if (returns[27]) {
                htmltext += '<br><strong>{{ __('db.Exchange Rate') }}: </strong>' + returns[27] + '<br>';
            } else {
                htmltext += '<br><strong>{{ __('db.Exchange Rate') }}: </strong>N/A<br>';
            }

            if (returns[25]) {
                htmltext += '<strong>{{ __('db.Attach Document') }}: </strong><a href="documents/sale_exchange/' + returns[
                    25] + '" target="_blank">Download</a><br>';
            }

            htmltext += '<br><div class="row">';
            htmltext += '<div class="col-md-6">';
            htmltext += '<strong>{{ __('db.From') }}:</strong><br>';
            htmltext += returns[3] + '<br>';
            htmltext += returns[4] + '<br>';
            htmltext += returns[5] + '<br>';
            htmltext += returns[6] + '<br>';
            htmltext += returns[7] + '<br>';
            htmltext += returns[8];
            htmltext += '</div>';

            htmltext += '<div class="col-md-6">';
            htmltext += '<div class="float-right">';
            htmltext += '<strong>{{ __('db.To') }}:</strong><br>';
            htmltext += returns[9] + '<br>';
            htmltext += returns[10] + '<br>';
            htmltext += returns[11] + '<br>';
            htmltext += returns[12];
            htmltext += '</div></div></div>';

            $('#return-content').html(htmltext);

            // Load products with type separation via AJAX
            $.get('{{ url('exchange/product_exchange') }}/' + returns[13], function(data) {
                $(".product-return-list tbody").remove();
                var newBody = $("<tbody>");

                var hasReturned = data.returned && data.returned.length > 0;
                var hasNew = data.new && data.new.length > 0;

                // RETURNED PRODUCTS SECTION
                if (hasReturned) {
                    var returnedHeader = $("<tr style='background-color: #f8d7da;'>");
                    newBody.append(returnedHeader);
                    $.each(data.returned, function(index, product) {
                        var newRow = $("<tr style='background-color: #fff5f5;'>");
                        var cols = '';
                        cols += '<td><strong>' + (index + 1) + '</strong></td>';
                        cols += '<td>' + product.name_code +
                            ' <span class="badge badge-danger" style="font-size: 10px;">RETURNED ⮌</span></td>';
                        cols += '<td>' + product.batch_no + '</td>';
                        cols += '<td>' + product.qty + ' ' + product.unit_code + '</td>';
                        cols += '<td>' + product.unit_price + '</td>';
                        cols += '<td>' + product.tax + ' (' + product.tax_rate + '%)</td>';
                        cols += '<td>' + product.discount + '</td>';
                        cols += '<td>' + product.subtotal + '</td>';
                        newRow.append(cols);
                        newBody.append(newRow);
                    });

                    var returnedSubtotalRow = $("<tr style='background-color: #fff3cd; font-weight: bold;'>");
                    newBody.append(returnedSubtotalRow);
                }

                // NEW PRODUCTS SECTION
                if (hasNew) {
                    var newHeader = $("<tr style='background-color: #d4edda;'>");
                    newBody.append(newHeader);

                    $.each(data.new, function(index, product) {
                        var newRow = $("<tr style='background-color: #f0fff4;'>");
                        var cols = '';
                        cols += '<td><strong>' + (index + 1) + '</strong></td>';
                        cols += '<td>' + product.name_code +
                            ' <span class="badge badge-success" style="font-size: 10px;">⮕ NEW</span></td>';
                        cols += '<td>' + product.batch_no + '</td>';
                        cols += '<td>' + product.qty + ' ' + product.unit_code + '</td>';
                        cols += '<td>' + product.unit_price + '</td>';
                        cols += '<td>' + product.tax + ' (' + product.tax_rate + '%)</td>';
                        cols += '<td>' + product.discount + '</td>';
                        cols += '<td>' + product.subtotal + '</td>';
                        newRow.append(cols);
                        newBody.append(newRow);
                    });
                    var newSubtotalRow = $("<tr style='background-color: #d1ecf1; font-weight: bold;'>");
                    newBody.append(newSubtotalRow);
                }

                // TOTALS SECTION
                var totalRow = $("<tr>");
                totalRow.append(
                    '<td colspan="5"><strong>{{ __('db.Total') }} ' +
                    (returns[28] === "pay" ? 'Refund' : 'Received') +
                    ' Amount:</strong></td>' +
                    '<td></td>' +
                    '<td></td>' +
                    '<td><strong>' + data.totals.amount + '</strong></td>'
                );
                newBody.append(totalRow);

                var grandTotalRow = $("<tr>");

                newBody.append(grandTotalRow);

                $("table.product-return-list").append(newBody);
            }).fail(function(xhr, status, error) {
                console.error('Failed to load products:', error);
                alert('Error loading product details. Please try again.');
            });

            // Build footer
            var htmlfooter = '<p><strong>{{ __('db.exchange_note') }}:</strong> ' + (returns[20] || 'N/A') + '</p>';
            htmlfooter += '<p><strong>{{ __('db.Staff Note') }}:</strong> ' + (returns[21] || 'N/A') + '</p>';
            htmlfooter += '<strong>{{ __('db.Created By') }}:</strong><br>' + returns[22] + '<br>' + returns[23];

            $('#return-footer').html(htmlfooter);
            $('#return-details').modal('show');
        }

        // Hide delete button if no permission
        if (all_permission.indexOf("exchanges-delete") == -1) {
            $('.buttons-delete').addClass('d-none');
        }
    </script>
@endpush
