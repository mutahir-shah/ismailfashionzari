@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="table-responsive">
        <table id="cash-register-table" class="table">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.User')}}</th>
                    <th>{{__('db.Warehouse')}}</th>
                    <th>{{__('db.Cash in Hand')}}</th>
                    <th>{{__('db.Closing Balance')}}</th>
                    <th>{{__('db.Actual Cash')}}</th>
                    <th>{{__('db.Opened at')}}</th>
                    <th>{{__('db.Closed at')}}</th>
                    <th>{{__('db.status')}}</th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_cash_register_all as $key=>$cash_register)
                <tr data-id="{{$cash_register->id}}">
                    <td>{{$key}}</td>
                    <td>{{ $cash_register->user->name }}</td>
                    <td>{{ $cash_register->warehouse->name }}</td>
                    <td>{{ $cash_register->cash_in_hand }}</td>
                    <td>{{ $cash_register->closing_balance }}</td>
                    <td>{{ $cash_register->actual_cash }}</td>
                    <td>{{ date(gen_setting()->date_format . " h:i:s", strtotime($cash_register->created_at)) }}</td>
                    @if($cash_register->status)
                        <td>N/A</td>
                        <td><div class="badge badge-success">{{__('db.Active')}}</div></td>
                    @else
                        <td>{{ date(gen_setting()->date_format . " h:i:s", strtotime($cash_register->updated_at)) }}</td>
                        <td><div class="badge badge-danger">{{__('db.Closed')}}</div></td>
                    @endif
                    <td>
                        <div class="btn-group">
                            <button type="button" data-id="{{$cash_register->id}}" class="register-details-btn btn btn-sm btn-info" data-toggle="modal" data-target="#register-details-modal" title="{{__('db.View')}}"><i class="ti ti-eye"></i></button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <!-- cash register details modal -->
    <div id="register-details-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 id="exampleModalLabel" class="modal-title">{{__('db.Cash Register Details')}}</h5>
              <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
              <p>{{__('db.Please review the transaction and payments')}}</p>
                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-hover">
                            <tbody>
                                <tr>
                                  <td>{{__('db.Cash in Hand')}}:</td>
                                  <td id="cash_in_hand" class="text-right">100</td>
                                </tr>
                                <tr>
                                  <td>{{__('db.Total Sale Amount')}}:</td>
                                  <td id="total_sale_amount" class="text-right"></td>
                                </tr>
                                <tr>
                                  <td>{{__('db.Total Payment')}}:</td>
                                  <td id="total_payment" class="text-right"></td>
                                </tr>
                                <tr>
                                  <td>{{__('db.Cash Payment')}}:</td>
                                  <td id="cash_payment" class="text-right"></td>
                                </tr>
                                <tr>
                                  <td>{{__('db.Credit Card Payment')}}:</td>
                                  <td id="credit_card_payment" class="text-right"></td>
                                </tr>
                                <tr>
                                  <td>{{__('db.Cheque Payment')}}:</td>
                                  <td id="cheque_payment" class="text-right"></td>
                                </tr>
                                <tr>
                                  <td>{{__('db.Gift Card Payment')}}:</td>
                                  <td id="gift_card_payment" class="text-right"></td>
                                </tr>
                                <tr>
                                  <td>{{__('db.Paypal Payment')}}:</td>
                                  <td id="paypal_payment" class="text-right"></td>
                                </tr>
                                <tbody id="custom-methods-container"></tbody>
                                <tr>
                                  <td>{{__('db.Total Sale Return')}}:</td>
                                  <td id="total_sale_return" class="text-right"></td>
                                </tr>
                                <tr>
                                  <td>{{__('db.Total Expense')}}:</td>
                                  <td id="total_expense" class="text-right"></td>
                                </tr>
                                <tr>
                                    <td>{{__('db.total_supplier_payment')}}:</td>
                                    <td id="total_supplier_payment" class="text-right"></td>
                                </tr>
                                <tr>
                                  <td><strong>{{__('db.Total Cash')}}:</strong></td>
                                  <td id="total_cash" class="text-right"></td>
                                </tr>
                                <tr id="closing_row">
                                    <td><strong>{{__('db.Actual Cash')}}:</strong></td>
                                    <td id="actual_cash" class="text-right"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6" id="closing-section">
                        <form action="{{route('cashRegister.close')}}" method="POST">
                            @csrf
                            <input type="hidden" name="cash_register_id">
                            <button type="submit" class="btn btn-primary" onclick="return confirmClose()">{{__('db.Close Register')}}</button>
                        </form>
                    </div>
                </div>
            </div>
          </div>
        </div>
    </div>
</section>


@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">

    function formatRegisterAmount(value) {
        const amount = Number(value ?? 0);

        return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
    }

    function confirmClose() {
        if (confirm("Are you sure want to close?")) {
            return true;
        }
        return false;
    }

    $(".register-details-btn").on("click", function (e) {
        const id = $(this).data('id');
        $.ajax({
            url: 'cash-register/getDetails/'+id,
            type: "GET",
            success:function(data) {
                if(data['status'])
                    $("#register-details-modal #closing-section").removeClass('d-none');
                else
                    $("#register-details-modal #closing-section").addClass('d-none');

                $('#register-details-modal #cash_in_hand').text(formatRegisterAmount(data['cash_in_hand']));
                $('#register-details-modal #total_sale_amount').text(formatRegisterAmount(data['total_sale_amount']));
                $('#register-details-modal #total_payment').text(formatRegisterAmount(data['total_payment']));
                $('#register-details-modal #cash_payment').text(formatRegisterAmount(data['cash_payment']));
                $('#register-details-modal #credit_card_payment').text(formatRegisterAmount(data['credit_card_payment']));
                $('#register-details-modal #cheque_payment').text(formatRegisterAmount(data['cheque_payment']));
                $('#register-details-modal #gift_card_payment').text(formatRegisterAmount(data['gift_card_payment']));
                $('#register-details-modal #paypal_payment').text(formatRegisterAmount(data['paypal_payment']));
                if (data.custom_methods) {
                        $('#custom-methods-container').empty();

                        $.each(data.custom_methods, function(key, value) {
                            let method_name = key.replace('_payment', '').replace(/_/g, ' ');
                            $('#custom-methods-container').append(
                                `<tr>
                                    <td>${method_name.charAt(0).toUpperCase() + method_name.slice(1)}:</td>
                                    <td id="${key}" class="text-right">${formatRegisterAmount(value)}</td>
                                </tr>`
                            );
                        });
                    }
                $('#register-details-modal #total_sale_return').text(formatRegisterAmount(data['total_sale_return']));
                $('#register-details-modal #total_expense').text(formatRegisterAmount(data['total_expense']));
                $('#register-details-modal #total_cash').text(formatRegisterAmount(data['total_cash']));
                $('#register-details-modal input[name=cash_register_id]').val(id);
                $('#register-details-modal #total_supplier_payment').text(formatRegisterAmount(data['total_supplier_payment']));
                $('#register-details-modal #actual_cash').text(formatRegisterAmount(data['actual_cash']));
            }
        });
    });

    $('#cash-register-table').DataTable( {
        "order": [],
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
                'targets': [0, 7]
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
                footer:true
            },
            {
                extend: 'excel',
                text: '<i title="export to excel" class="ti ti-file-type-xls"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                footer:true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                footer:true
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                footer:true
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="ti ti-eye"></i>',
                columns: ':gt(0)'
            },
        ],
    } );
</script>
@endpush
