@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="table-responsive">
        <table id="delivery-table" class="table delivery-list">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.Delivery Reference')}}</th>
                    <th>{{__('db.Sale Reference')}}</th>
                    <th>{{__('db.Packing Slip Reference')}}</th>
                    <th>{{__('db.customer')}}</th>
                    <th>{{__('db.Courier')}}</th>
                    <th>{{__('db.tracking_code')}}</th>
                    <th>{{__('db.Address')}}</th>
                    <th>{{__('db.Products')}}</th>
                    <th>{{__('db.grand total')}}</th>
                    <th>{{__('db.status')}}</th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</section>

<!-- Modal -->
<div id="delivery-details" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">
        <div class="container mt-3 pb-2 border-bottom">
            <div class="row">
                <div class="col-md-6 d-print-none">
                    <button id="print-btn" type="button" class="btn btn-default btn-sm d-print-none"><i class="ti ti-printer"></i> {{__('db.print')}}</button>

                    <form action="{{ route('delivery.sendMail') }}" method="POST" class="sendmail-form">
                        @csrf
                        <input type="hidden" name="delivery_id">
                        <button class="btn btn-default btn-sm d-print-none"><i class="ti ti-mail"></i> {{__('db.email')}}</button>
                    </form>
                </div>
                <div class="col-md-6">
                    <button type="button" id="close-btn" data-dismiss="modal" aria-label="Close" class="close d-print-none"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
                </div>
                <div class="col-md-12">
                    <h3 id="exampleModalLabel" class="modal-title text-center container-fluid">
                        {{gen_setting()->site_title}}
                    </h3>
                </div>
                <div class="col-md-12 text-center">
                    <i style="font-size: 15px;">{{__('db.Delivery Details')}}</i>
                </div>
            </div>
        </div>
        <div class="modal-body">
            <table class="table table-bordered" id="delivery-content">
                <tbody></tbody>
            </table>
            <br>
            <table class="table table-bordered product-delivery-list">
                <thead>
                    <th>No</th>
                    <th>Code</th>
                    <th>Description</th>
                    <th>{{__('db.Batch No')}}</th>
                    <th>{{__('db.Expired Date')}}</th>
                    <th>Qty</th>
                </thead>
                <tbody>
                </tbody>
            </table>
            <div id="delivery-footer" class="row">
            </div>
        </div>
      </div>
    </div>
</div>

<div id="edit-delivery" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Update Delivery')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('delivery.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>{{__('db.Delivery Reference')}}</label>
                            <p id="dr"></p>
                        </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.Sale Reference')}}</label>
                        <p id="sr"></p>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>{{__('db.status')}} *</label>
                        <select name="status" required class="form-control selectpicker">
                            <option value="1">{{__('db.Packing')}}</option>
                            <option value="2">{{__('db.Delivering')}}</option>
                            <option value="3">{{__('db.Delivered')}}</option>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.Courier')}}</label>
                        <select name="courier_id" id="courier_id" class="selectpicker form-control" data-live-search="true" title="Select courier...">
                            @foreach($lims_courier_list as $courier)
                            <option value="{{$courier->id}}">{{$courier->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mt-2 form-group">
                        <label>{{__('db.Delivered By')}}</label>
                        <input type="text" name="delivered_by" class="form-control">
                    </div>
                    <div class="col-md-6 mt-2 form-group">
                        <label>{{__('db.Recieved By')}}</label>
                        <input type="text" name="recieved_by" class="form-control">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.customer')}} *</label>
                        <p id="customer"></p>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.Attach File')}}</label>
                        <input type="file" name="file" class="form-control">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.Address')}} *</label>
                        <textarea rows="3" name="address" class="form-control" required></textarea>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.note')}}</label>
                        <textarea rows="3" name="note" class="form-control"></textarea>
                    </div>
                </div>
                <input type="hidden" name="reference_no">
                <input type="hidden" name="delivery_id">
                <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ====== TRACKING MODAL ====== --}}
<div id="trackingModal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: #2d6a4f;">
                <h5 class="modal-title text-white">
                    <i class="ti ti-map-marker"></i> Parcel Tracking
                </h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close text-white">
                    <span aria-hidden="true"><i class="ti ti-x"></i></span>
                </button>
            </div>
            <div class="modal-body" id="tracking-modal-body">
                {{-- JS দিয়ে inject হবে --}}
            </div>
        </div>
    </div>
</div>

<div id="steadfast-delivery" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.send_delivery')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('steadfast.create-order') }}" method="POST" id="steadfastForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>{{__('db.Invoice')}}</label>
                            <p id="invoice"></p>
                        </div>
                    <div class="col-md-6 mt-2 form-group">
                        <label>{{__('db.name')}} *</label>
                        <input type="text" name="recipient_name" class="form-control">
                    </div>
                    <div class="col-md-6 mt-2 form-group">
                        <label>{{__('db.Phone')}} *</label>
                        <input type="text" name="recipient_phone" class="form-control">
                    </div>
                    <div class="col-md-6 mt-2 form-group">
                        <label>{{__('db.alternative_phone')}}</label>
                        <input type="text" name="alternative_phone" class="form-control">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.email')}}</label>
                        <input type="email" name="recipient_email" class="form-control">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.Address')}} *</label>
                        <textarea rows="3" name="recipient_address" class="form-control" required></textarea>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.Amount')}} *</label>
                        <input type="number" name="cod_amount" class="form-control" min="0.01" step="0.01">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.Item Description')}}</label>
                        <textarea rows="3" name="item_description" class="form-control"></textarea>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.note')}}</label>
                        <textarea rows="3" name="note" class="form-control"></textarea>
                    </div>
                </div>
                <input type="hidden" name="invoice">
                <input type="hidden" name="sale_id">
                <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                </form>
            </div>
        </div>
    </div>
</div>


@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">

    $("ul#sale").siblings('a').attr('aria-expanded','true');
    $("ul#sale").addClass("show");
    $("ul#sale #delivery-menu").addClass("active");

    var delivery_id = [];
    var user_verified = <?php echo json_encode(config('app.user_verified'))?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $("#print-btn").on("click", function(){
          var divContents = document.getElementById("delivery-details").innerHTML;
        var a = window.open('');
        a.document.write('<html>');
        a.document.write('<body><style>body{font-family: sans-serif;line-height: 1.15;-webkit-text-size-adjust: 100%;}.d-print-none{display:none}.text-center{text-align:center}.row{width:100%;margin-right: -15px;margin-left: -15px;}.col-md-12{width:100%;display:block;padding: 5px 15px;}.col-md-6{width: 50%;float:left;padding: 5px 15px;}table{width:100%;margin-top:30px;}th{text-aligh:left}td{padding:10px}table,th,td{border: 1px solid black; border-collapse: collapse;}#delivery-footer{margin-left:10px}</style><style>@media print {.modal-dialog { max-width: 1000px;} }</style>');
        a.document.write(divContents);
        a.document.write('</body></html>');
        a.document.close();
        setTimeout(function(){a.close();},10);
        a.print();
    });

    function confirmDelete() {
      if (confirm("Are you sure want to delete?")) {
          return true;
      }
      return false;
    }

    // $("tr.delivery-link td:not(:first-child, :last-child)").on("click", function() {
    //     var delivery = $(this).parent().data('delivery');
    //     var barcode = $(this).parent().data('barcode');
    //     deliveryDetails(delivery, barcode);
    // });

    $(document).on(
        "click",
        "#delivery-table tbody tr td:not(:first-child, :last-child)",
        function () {
            let rowData = $('#delivery-table').DataTable().row($(this).closest('tr')).data();

            deliveryDetailsFromAjax(rowData);
        }
    );

   $(document).on("click", "table.delivery-list tbody .steadfast-delivery", function(event) {
    var id = $(this).data('id').toString();
    var type = $(this).data('type').toString();

    $.get('delivery/steadfast/'+id, function(data) {
        $('#invoice').text(data['invoice']);
        $('input[name="invoice"]').val(data['invoice']);
        $('input[name="sale_id"]').val(id);
        $('input[name="recipient_name"]').val(data['recipient_name']);
        $('input[name="recipient_email"]').val(data['recipient_email']);
        $('input[name="recipient_phone"]').val(data['recipient_phone']);
        $('textarea[name="recipient_address"]').val(data['recipient_address']);
        let amount = parseFloat(data['cod_amount']);
        $('input[name="cod_amount"]').val(isNaN(amount) ? '' : amount.toFixed(2));

        // ✅ Set form action based on type
        if (type === 'pathao') {
            // Construct URL manually or use route with ID parameter
            let pathaoUrl = "{{ route('delivery.sendToPathao', ['id' => 'PLACEHOLDER']) }}".replace('PLACEHOLDER', id);
            $('#steadfastForm').attr('action', pathaoUrl);
        } else {
            $('#steadfastForm').attr('action', "{{ route('steadfast.create-order') }}");
        }

        $('#steadfast-delivery').modal('show');
    });
});

    function deliveryDetails(delivery, barcode) {
        $('input[name="delivery_id"]').val(delivery[4]);
        $("#delivery-content tbody").remove();
        var newBody = $("<tbody>");
        var rows = '';
        rows += '<tr><td>Date</td><td>'+delivery[0]+'</td></tr>';
        rows += '<tr><td>Delivery Reference</td><td>'+delivery[1]+'</td></tr>';
        rows += '<tr><td>Sale Reference</td><td>'+delivery[2]+'</td></tr>';
        rows += '<tr><td>Status</td><td>'+delivery[3]+'</td></tr>';
        rows += '<tr><td>Customer Name</td><td>'+delivery[5]+'</td></tr>';
        rows += '<tr><td>Address</td><td>'+delivery[7]+', '+delivery[8]+'</td></tr>';
        rows += '<tr><td>Phone Number</td><td>'+delivery[6]+'</td></tr>';
        rows += '<tr><td>Note</td><td>'+delivery[9]+'</td></tr>';

        newBody.append(rows);
        $("table#delivery-content").append(newBody);

        $.get('delivery/product_delivery/' + delivery[4], function(data) {
            $(".product-delivery-list tbody").remove();
            var code = data[0];
            var description = data[1];
            var batch_no = data[2];
            var expired_date = data[3];
            var qty = data[4];
            var newBody = $("<tbody>");
            $.each(code, function(index) {
                var newRow = $("<tr>");
                var cols = '';
                cols += '<td><strong>' + (index+1) + '</strong></td>';
                cols += '<td>' + code[index] + '</td>';
                cols += '<td>' + description[index] + '</td>';
                cols += '<td>' + batch_no[index] + '</td>';
                cols += '<td>' + expired_date[index] + '</td>';
                cols += '<td>' + qty[index] + '</td>';
                newRow.append(cols);
                newBody.append(newRow);
            });
            $("table.product-delivery-list").append(newBody);
        });

        var htmlfooter = '<div class="col-md-4 form-group"><p>Prepared By: '+delivery[10]+'</p></div>';
        htmlfooter += '<div class="col-md-4 form-group"><p>Delivered By: '+delivery[11]+'</p></div>';
        htmlfooter += '<div class="col-md-4 form-group"><p>Recieved By: '+delivery[12]+'</p></div>';
        htmlfooter += '<br><br>';
        htmlfooter += '<div class="col-md-2 offset-md-5"><img style="max-width:850px;height:100%;max-height:130px" src="data:image/png;base64,'+barcode+'" alt="barcode" /></div>';

        $('#delivery-footer').html(htmlfooter);
        $('#delivery-details').modal('show');
    }

    function deliveryDetailsFromAjax(row) {
        let delivery = [
            row.date ?? '',
            row.reference_no,
            row.sale_reference,
            $(row.status).text(),
            row.id,
            row.customer.replace(/<br>.*/, ''),
            row.customer.match(/<br>(.*)/)?.[1] ?? '',
            row.address,
            '',
            '',
            '',
            '',
            ''
        ];

        let barcode = row.barcode ?? '';

        deliveryDetails(delivery, barcode);
    }

    $(document).ready(function() {
        $(document).on('click', '.open-EditCategoryDialog', function(){
          var url ="{{url('delivery')}}/"
          var id = $(this).data('id').toString();
          url = url.concat(id).concat("/edit");

          $.get(url, function(data){
                $('#dr').text(data[0]);
                $('#sr').text(data[1]);
                $('select[name="status"]').val(data[2]);
                $('.selectpicker').selectpicker('refresh');
                $('input[name="delivered_by"]').val(data[3]);
                $('input[name="recieved_by"]').val(data[4]);
                $('#customer').text(data[5]);
                $('textarea[name="address"]').val(data[6]);
                $('textarea[name="note"]').val(data[7]);
                $('select[name="courier_id"]').val(data[8]);
                $('input[name="reference_no"]').val(data[0]);
                $('input[name="delivery_id"]').val(id);
                $('.selectpicker').selectpicker('refresh');
          });
          $("#edit-delivery").modal('show');
        });
    });

    $('#delivery-table').DataTable( {
        "processing": true,
        "serverSide": true,
        "order": [],
        "ajax":{
            url:"{{url('delivery/delivery_list_data')}}",
            dataType: "json",
            type:"get",
        },
        "columns": [
            {"data": "key"},
            {"data": "reference_no"},
            {"data": "sale_reference"},
            {"data": "packing_slip_references"},
            {"data": "customer"},
            {"data": "courier"},
            {"data": 'tracking_code'},
            {"data": "address"},
            {"data": "products"},
            {"data": "grand_total"},
            {"data": "status"},
            {"data": "options"},

        ],
        'language': {
            'lengthMenu': '_MENU_ {{__("db.records per page")}}',
             "info":      '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search":  '{{__("db.Search")}}',
            'paginate': {
                    'previous': '<i class="ti ti-chevron-left"></i>',
                    'next': '<i class="ti ti-chevron-right"></i>'
            }
        },
        'columnDefs': [
            {
                "orderable": false,
                'targets': [0, 6, 10]
            },
            {
                'render': function(data, type, row, meta){
                    if(type === 'display'){
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
        'select': { style: 'multi',  selector: 'td:first-child'},
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        buttons: [
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
            },
            {
                extend: 'excel',
                text: '<i title="export to excel" class="ti ti-file-type-xls"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
            },
            {
                text: '<i title="delete" class="ti ti-x"></i>',
                className: 'buttons-delete',
                action: function ( e, dt, node, config ) {
                    if(user_verified == '1') {
                        delivery_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                delivery_id[i-1] = $(this).closest('tr').data('id');
                            }
                        });
                        if(delivery_id.length && confirm("Are you sure want to delete?")) {
                            $.ajax({
                                type:'POST',
                                url:'delivery/deletebyselection',
                                data:{
                                    deliveryIdArray: delivery_id
                                },
                                success:function(data){
                                    alert(data);
                                }
                            });
                            dt.rows({ page: 'current', selected: true }).remove().draw(false);
                        }
                        else if(!delivery_id.length)
                            alert('Nothing is selected!');
                    }
                    else
                        alert('This feature is disable for demo!');
                }
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="ti ti-eye"></i>',
                columns: ':gt(0)'
            },
        ],
    } );


    // ====== TRACK BUTTON CLICK ======
$(document).on('click', '.track-delivery-btn', function () {
    var deliveryId    = $(this).data('id');
    var trackingCode  = $(this).data('tracking');

    $('#tracking-modal-body').html(`
        <div class="text-center py-4">
            <i class="ti ti-spinner fa-spin fa-2x text-info"></i>
            <p class="mt-2 text-muted">
                Fetching tracking info for
                <strong>${trackingCode}</strong>...
            </p>
        </div>
    `);

    $('#trackingModal').modal('show');

    $.ajax({
        url: '{{url("delivery")}}/' + deliveryId + '/track',
        type: 'GET',
        success: function (res) {
            if (!res.success) {
                $('#tracking-modal-body').html(`
                    <div class="alert alert-danger">
                        <i class="ti ti-times-circle"></i> ${res.error}
                    </div>
                `);
                return;
            }

            const trackingUrlBtn = res.tracking_url
                ? `<div class="text-center mt-3">
                       <a href="${res.tracking_url}" target="_blank" class="btn btn-sm btn-outline-info">
                           <i class="ti ti-external-link"></i> View on ${res.courier} Website
                       </a>
                   </div>`
                : '';

            const recipientRow = res.recipient_name
                ? `<tr><th>Recipient</th><td>${res.recipient_name}</td></tr>` : '';

            const addressRow = res.address
                ? `<tr><th>Address</th><td>${res.address}</td></tr>` : '';

            const amountRow = res.amount
                ? `<tr><th>Amount</th><td>${res.amount} ৳</td></tr>` : '';

            const updatedRow = res.updated_at
                ? `<tr><th>Last Updated</th><td>${res.updated_at}</td></tr>` : '';

            $('#tracking-modal-body').html(`
                <div class="text-center mb-3">
                    <span class="badge badge-success" style="font-size:14px; padding:8px 18px;">
                        ${res.courier}
                    </span>
                </div>

                <div style="background:#e8f5e9; border:1px dashed #2d6a4f; border-radius:8px;
                            padding:16px; text-align:center; margin-bottom:16px;">
                    <div style="font-size:11px; color:#777; text-transform:uppercase; letter-spacing:1px;">
                        Tracking Code
                    </div>
                    <div style="font-size:22px; font-weight:bold; color:#2d6a4f; letter-spacing:3px;">
                        ${res.tracking_code}
                    </div>
                </div>

                <table class="table table-sm table-bordered">
                    <tr>
                        <th style="width:40%">Status</th>
                        <td><span class="badge badge-primary" style="font-size:13px;">${res.status}</span></td>
                    </tr>
                    ${recipientRow}
                    ${addressRow}
                    ${amountRow}
                    ${updatedRow}
                </table>

                ${trackingUrlBtn}
            `);
        },
        error: function () {
            $('#tracking-modal-body').html(`
                <div class="alert alert-danger">
                    <i class="ti ti-times-circle"></i> Server error. Please try again.
                </div>
            `);
        }
    });
});

// ====== PATHAO DELIVERY BUTTON CLICK ======
$(document).on('click', '.pathao-delivery', function () {
    var deliveryId = $(this).data('delivery-id');
    var saleId     = $(this).data('id');

    if (confirm('Are you sure you want to send this order to Pathao?')) {
        $.ajax({
            type: 'POST',
            url: '{{url("delivery")}}/' + deliveryId + '/send-to-pathao',
            data: { _token: '{{ csrf_token() }}' },
            beforeSend: function () {
                alert('Sending order to Pathao...');
            },
            success: function (response) {
                if (response.success) {
                    alert('Order sent to Pathao successfully! Tracking Code: ' + response.tracking_code);
                    location.reload();
                } else {
                    alert('Failed to send order to Pathao. Error: ' + response.error);
                }
            },
            error: function () {
                alert('Something went wrong! Please try again.');
            }
        });
    }
});
</script>
@endpush
