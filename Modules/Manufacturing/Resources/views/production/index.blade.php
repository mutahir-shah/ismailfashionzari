@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')
@if(session()->has('message'))
  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
@endif
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif

<section>
    <div class="container-fluid">

        {{-- ── Action Buttons ── --}}
        <a href="{{route('manufacturing.productions.create')}}" class="btn btn-info">
            <i class="ti ti-plus"></i> {{__('db.Add Production')}}
        </a>&nbsp;
        <button class="btn btn-secondary" type="button"
            data-toggle="collapse"
            data-target="#filterSection"
            aria-expanded="false"
            aria-controls="filterSection"
            id="filter-toggle-btn">
            <i class="ti ti-filter"></i> {{__('db.Filter')}}
            <i class="ti ti-chevron-down ml-1" id="filter-chevron"></i>
        </button>

        {{-- ── Filter Card (collapsible, hidden by default) ── --}}
        <div class="collapse" id="filterSection">
            <div class="card mt-2">
                <div class="row ml-1 mt-2">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><strong>{{__('db.date')}}</strong></label>
                            <input type="text" id="daterange-display" class="daterangepicker-field form-control" value="{{$starting_date}} To {{$ending_date}}" required />
                            <input type="hidden" id="starting_date" name="starting_date" value="{{$starting_date}}" />
                            <input type="hidden" id="ending_date" name="ending_date" value="{{$ending_date}}" />
                        </div>
                    </div>
                    <div class="col-md-3 @if(\Auth::user()->role_id > 2){{'d-none'}}@endif">
                        <div class="form-group">
                            <label><strong>{{__('db.Warehouse')}}</strong></label>
                            <select id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins">
                                <option value="0">{{__('db.All Warehouse')}}</option>
                                @foreach($lims_warehouse_list as $warehouse)
                                    <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><strong>{{__('db.status')}}</strong></label>
                            <select id="status" class="form-control" name="production_status">
                                <option value="0">{{__('db.All')}}</option>
                                <option value="1">{{__('db.Completed')}}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── DataTable ── --}}
    <div class="table-responsive">
        <table id="production-table" class="table production-list" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.date')}}</th>
                    <th>{{__('db.reference')}}</th>
                    <th>{{__('db.status')}}</th>
                    <th>{{__('db.product')}}</th>
                    <th>{{__('db.Warehouse')}}</th>
                    <th>{{__('db.Quantity')}}</th>
                    <th>{{__('db.grand total')}}</th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
            <tfoot class="tfoot active">
                <th></th>
                <th>{{__('db.Total')}}</th>
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
</section>

{{-- ── Production Details Modal ── --}}
<div id="production-details" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">
        <div class="container mt-3 pb-2 border-bottom">
            <div class="row">
                <div class="col-md-6 d-print-none">
                    <button id="print-btn" type="button" class="btn btn-default btn-sm"><i class="ti ti-printer"></i> {{__('db.print')}}</button>
                </div>
                <div class="col-md-6 d-print-none">
                    <button type="button" id="close-btn" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
                </div>
                <div class="col-md-12">
                    <h3 id="exampleModalLabel" class="modal-title text-center container-fluid">{{$general_setting->site_title}}</h3>
                </div>
                <div class="col-md-12 text-center">
                    <i style="font-size: 15px;">{{__('db.Production Details')}}</i>
                </div>
            </div>
        </div>
        <div id="production-content" class="modal-body"></div>
        <br>
        <table class="table table-bordered product-production-list">
            <thead>
                <th>#</th>
                <th>{{__('db.product')}}</th>
                <th>{{__('db.qty')}}</th>
                <th>{{__('db.Wastage Percent')}}</th>
                <th>{{__('db.Price')}}</th>
                <th>{{__('db.Subtotal')}}</th>
            </thead>
            <tbody></tbody>
        </table>
        <div id="production-footer" class="modal-body"></div>
      </div>
    </div>
</div>

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">

    // ─── Sidebar Active ──────────────────────────────────────────────────────
    $("ul#manufacturing").siblings('a').attr('aria-expanded','true');
    $("ul#manufacturing").addClass("show");
    $("ul#manufacturing #production-list-menu").addClass("active");

    // ─── PHP Variables ───────────────────────────────────────────────────────
    var user_verified = <?php echo json_encode(config('app.user_verified'))?>;
    var starting_date = <?php echo json_encode($starting_date); ?>;
    var ending_date   = <?php echo json_encode($ending_date); ?>;
    var warehouse_id  = <?php echo json_encode($warehouse_id); ?>;
    var status        = <?php echo json_encode($status); ?>;

    // ─── CSRF ────────────────────────────────────────────────────────────────
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // ─── Set Default Filter Values ───────────────────────────────────────────
    $("#warehouse_id").val(warehouse_id);
    $("#status").val(status);
    $('.selectpicker').selectpicker('refresh');

    // ─── Daterangepicker ─────────────────────────────────────────────────────
    $(".daterangepicker-field").daterangepicker({
        callback: function(startDate, endDate, period) {
            var sd = startDate.format('YYYY-MM-DD');
            var ed = endDate.format('YYYY-MM-DD');
            $(this).val(sd + ' To ' + ed);
            $('#starting_date').val(sd);
            $('#ending_date').val(ed);
            reloadTable();
        }
    });

    // apply.daterangepicker — কিছু library version এ callback কাজ না করলে এটা কাজ করে
    $(document).on('apply.daterangepicker', '.daterangepicker-field', function(ev, picker) {
        var sd = picker.startDate.format('YYYY-MM-DD');
        var ed = picker.endDate.format('YYYY-MM-DD');
        $(this).val(sd + ' To ' + ed);
        $('#starting_date').val(sd);
        $('#ending_date').val(ed);
        reloadTable();
    });

    // ─── Fallback: display input value watch ──────────────────────────────────
    // উপরের দুটো event কাজ না করলে এই interval টা কাজ করবে।
    // daterangepicker যেকোনো library হোক, সে display input এর value update করে।
    // সেই value change detect করে table reload করা হচ্ছে।
    var _lastDateVal = $('#daterange-display').val();
    setInterval(function () {
        var _currentVal = $('#daterange-display').val();
        if (_currentVal !== _lastDateVal) {
            _lastDateVal = _currentVal;
            var parts = _currentVal.split(' To ');
            if (parts.length === 2) {
                var sd = parts[0].trim();
                var ed = parts[1].trim();
                $('#starting_date').val(sd);
                $('#ending_date').val(ed);
                reloadTable();
            }
        }
    }, 500);

    // ─── Filter Collapse Chevron ─────────────────────────────────────────────
    $('#filterSection').on('show.bs.collapse', function () {
        $('#filter-chevron').removeClass('fa-chevron-down').addClass('fa-chevron-up');
    });
    $('#filterSection').on('hide.bs.collapse', function () {
        $('#filter-chevron').removeClass('fa-chevron-up').addClass('fa-chevron-down');
    });

    // ─── Warehouse & Status onChange ─────────────────────────────────────────
    $('#warehouse_id').on('change', function () { reloadTable(); });
    $('#status').on('change', function () { reloadTable(); });

    // ─── Reload Table ─────────────────────────────────────────────────────────
    // সবসময় ID দিয়ে DOM থেকে fresh value নেয়
    function reloadTable() {
        var sd  = $('#starting_date').val();
        var ed  = $('#ending_date').val();
        var wid = $('#warehouse_id').val();
        var st  = $('#status').val();

        if ($.fn.DataTable.isDataTable('#production-table')) {
            $('#production-table').DataTable().destroy();
        }
        loadProductionTable(sd, ed, wid, st);
    }

    // ─── Initial Load ─────────────────────────────────────────────────────────
    loadProductionTable(starting_date, ending_date, warehouse_id, status);

    // ─── DataTable Init ───────────────────────────────────────────────────────
    function loadProductionTable(sd, ed, wid, st) {
        $('#production-table').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                url:      "productions/production-data",
                dataType: "json",
                type:     "post",
                data: {
                    starting_date: sd,
                    ending_date:   ed,
                    warehouse_id:  wid,
                    status:        st,
                    _token: $('meta[name="csrf-token"]').attr('content')
                }
            },
            "createdRow": function(row, data, dataIndex) {
                $(row).addClass('production-link');
                $(row).attr('data-production', data['production']);
            },
            "columns": [
                {"data": "key"},
                {"data": "date"},
                {"data": "reference_no"},
                {"data": "status"},
                {"data": "product"},
                {"data": "warehouse"},
                {"data": "quantity"},
                {"data": "grand_total"},
                {"data": "options"},
            ],
            'language': {
                'lengthMenu': '_MENU_ {{__("db.records per page")}}',
                "info":       '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
                "search":     '{{__("db.Search")}}',
                'paginate': {
                    'previous': '<i class="ti ti-chevron-left"></i>',
                    'next':     '<i class="ti ti-chevron-right"></i>'
                }
            },
            order: [['1', 'desc']],
            'columnDefs': [
                {
                    "orderable": false,
                    'targets': [0, 3, 8]
                },
                {
                    'render': function(data, type, row, meta) {
                        if (type === 'display') {
                            data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
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
            'select': { style: 'multi', selector: 'td:first-child' },
            'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
            dom: '<"row"lfB>rtip',
            buttons: [
                {
                    extend: 'pdf',
                    text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                    exportOptions: { columns: ':visible:Not(.not-exported)', rows: ':visible' },
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
                    exportOptions: { columns: ':visible:not(.not-exported)', rows: ':visible' },
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
                    exportOptions: { columns: ':visible:not(.not-exported)', rows: ':visible' },
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
                    exportOptions: { columns: ':visible:not(.not-exported)', rows: ':visible' },
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
    }

    // ─── Footer Sum ───────────────────────────────────────────────────────────
    function datatable_sum(dt_selector, is_calling_first) {
        if (dt_selector.rows('.selected').any() && is_calling_first) {
            var rows = dt_selector.rows('.selected').indexes();
            $(dt_selector.column(7).footer()).html(
                dt_selector.cells(rows, 7, { page: 'current' }).data().sum().toFixed({{$general_setting->decimal}})
            );
        } else {
            $(dt_selector.column(7).footer()).html(
                dt_selector.column(7, { page: 'current' }).data().sum().toFixed({{$general_setting->decimal}})
            );
        }
    }

    // ─── Row Click ────────────────────────────────────────────────────────────
    $(document).on("click", "tr.production-link td:not(:first-child, :last-child)", function() {
        var production = $(this).parent().data('production');
        productionDetails(production);
    });

    $(document).on("click", ".view", function() {
        var production = $(this).parent().parent().parent().parent().parent().data('production');
        productionDetails(production);
    });

    // ─── Print Modal ──────────────────────────────────────────────────────────
    $("#print-btn").on("click", function() {
        var divContents = document.getElementById("production-details").innerHTML;
        var a = window.open('');
        a.document.write('<html>');
        a.document.write('<body><style>body{font-family:sans-serif;line-height:1.15;}.d-print-none{display:none}.text-center{text-align:center}.row{width:100%;margin-right:-15px;margin-left:-15px;}.col-md-12{width:100%;display:block;padding:5px 15px;}.col-md-6{width:50%;float:left;padding:5px 15px;}table{width:100%;margin-top:30px;}td{padding:10px}table,th,td{border:1px solid black;border-collapse:collapse;}</style><style>@media print{.modal-dialog{max-width:1000px;}}</style>');
        a.document.write(divContents);
        a.document.write('</body></html>');
        a.document.close();
        setTimeout(function() { a.close(); }, 10);
        a.print();
    });

    // ─── Production Details Modal ─────────────────────────────────────────────
    function productionDetails(production) {
        var htmltext = '<strong>{{__("db.date")}}: </strong>' + production[0] +
                       '<br><strong>{{__("db.reference")}}: </strong>' + production[1] +
                       '<br><strong>{{__("db.status")}}: </strong>' + production[2] +
                       '<br><strong>{{__("db.Warehouse")}}: </strong>' + production[4];

        if (production[13])
            htmltext += '<br><strong>{{__("db.Attach Document")}}: </strong><a href="documents/production/' + production[13] + '">Download</a>';

        $(".product-production-list tbody").remove();

        $.get('productions/product_production/' + production[3], function(response) {
            var newBody = $("<tbody>");

            if (!response.status || !response.data.length) {
                newBody.append('<tr><td colspan="6">Something is wrong!</td></tr>');
            } else {
                var data            = response.data;
                var production_info = response.production_info;

                $.each(data, function(index, item) {
                    var cols = '';
                    cols += '<td><strong>' + (index + 1) + '</strong></td>';
                    cols += '<td>' + item.name + ' [' + item.code + ']</td>';
                    cols += '<td>' + item.qty + ' [' + item.unit_name + ']</td>';
                    cols += '<td>' + item.wastage_percent + '</td>';
                    cols += '<td>' + item.unit_price + '</td>';
                    cols += '<td>' + (item.subtotal).toFixed(2) + '</td>';
                    newBody.append('<tr>' + cols + '</tr>');
                });

                var totalSubtotal = data.reduce((acc, curr) => acc + parseFloat(curr.subtotal), 0);
                newBody.append('<tr><td colspan="5"><strong>Total:</strong></td><td>' + totalSubtotal.toFixed(2) + '</td></tr>');
                newBody.append('<tr><td colspan="5"><strong>Shipping Cost:</strong></td><td>' + production_info.shipping_cost + '</td></tr>');
                newBody.append('<tr><td colspan="5"><strong>Production Cost:</strong></td><td>' + production_info.production_cost + '</td></tr>');
                newBody.append('<tr><td colspan="5"><strong>Total Quantity:</strong></td><td>' + production_info.total_qty + '</td></tr>');
                newBody.append('<tr><td colspan="5"><strong>Grand Total:</strong></td><td>' + production_info.grand_total + '</td></tr>');
            }

            $("table.product-production-list tbody").remove();
            $("table.product-production-list").append(newBody);
        });

        var htmlfooter = '<p><strong>{{__("db.note")}}:</strong> ' + production[10] + '</p>' +
                         '<strong>{{__("db.Created By")}}:</strong><br>' + production[11] + '<br>' + production[12];

        $('#production-content').html(htmltext);
        $('#production-footer').html(htmlfooter);
        $('#production-details').modal('show');
    }

</script>
@endpush
