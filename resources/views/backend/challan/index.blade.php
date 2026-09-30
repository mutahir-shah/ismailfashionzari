@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')

<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">Challan List</h3>
            </div>
            <form action="{{route('challan.index')}}", method="GET">
                <div class="row mb-3 offset-1">
                    <div class="col-md-3 mt-3">
                        <label class="">Courier</label>
                        <select name="courier_id" id="courier-id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select courier...">
                            <option value="All Courier">All Courier</option>
                            @foreach($courier_list as $courier)
                                @if($courier_id == $courier->id)
                                    <option value="{{$courier->id}}" selected>{{$courier->name.' ['.$courier->phone_number.']'}}</option>
                                @else
                                    <option value="{{$courier->id}}">{{$courier->name.' ['.$courier->phone_number.']'}}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mt-3">
                        <label class="">Status</label>
                        <select id="status" name="status" class="selectpicker form-control">
                            <option value="0">All</option>
                            @if($status === 'Active')
                                <option value="Active" selected>Active</option>
                                <option value="Close">Close</option>
                            @elseif($status === 'Close')
                                <option value="Active">Active</option>
                                <option value="Close" selected>Close</option>
                            @else
                                <option value="Active">Active</option>
                                <option value="Close">Close</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-2 mt-5">
                        <div class="form-group">
                            <button class="btn btn-primary" id="filter-btn" type="submit">Submit</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="table-responsive">
        <table id="challan-data-table" class="table challan-list" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>Date</th>
                    <th>Reference No</th>
                    <th>Order No</th>
                    <th>Courier</th>
                    <th>Status</th>
                    <th>Closing Date</th>
                    <th>Total Amount</th>
                    <th>Created By</th>
                    <th>Closed By</th>
                    <th class="not-exported">Action</th>
                </tr>
            </thead>

            <tfoot class="tfoot active">
                <th></th>
                <th>Total</th>
                <th></th>
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
</section>

<div id="add-payment" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">Finalized</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                    <span aria-hidden="true"><i class="ti ti-x"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('challan.add-payment') }}" method="post" enctype="multipart/form-data" class="payment-form" id="add-payment-form">
                    @csrf
                    <div class="row">
                        <input type="hidden" name="balance">
                        <input type="hidden" name="challan_id">

                        <div class="col-md-12 mt-3">
                            <h5>Order List</h5>
                            <div class="table-responsive">
                                <table class="table table-hover" id="modal-order-table">
                                    <thead>
                                        <tr>
                                            <th>PS Ref</th>
                                            <th>Order Ref</th>
                                            <th>Payment Method</th>
                                            <th>Amt Received</th>
                                            <th>Del. Charge</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modal-order-list">
                                        <!-- AJAX populated -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Account --}}
                        <div class="col-md-4">
                            <label>{{__('db.Account')}}</label>
                            <select class="form-control selectpicker" name="account_id">
                                @foreach($lims_account_list as $account)
                                    @if(auth()->user()->account_id === $account->id)
                                        <option selected value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                                    @elseif($account->is_default)
                                        <option selected value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                                    @else
                                        <option value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        {{-- Payment Receiver --}}
                        <div class="col-md-4">
                            <label>{{__('db.Payment Receiver')}}</label>
                            <input type="text" name="payment_receiver" class="form-control">
                        </div>

                        {{-- Payment Date --}}
                        <div class="col-md-4">
                            <label>{{ __('db.Payment Date') }}</label>
                            <input type="text" name="payment_at" id="payment_at" class="form-control"
                                value="{{ date('Y-m-d') }}" required>
                        </div>

                        {{-- Attach Document --}}
                        <div class="col-md-12 mt-2">
                            <label>{{__('db.Attach Document')}}</label>
                            <input type="file" name="document" class="form-control" />
                        </div>

                        {{-- Payment Note --}}
                        <div class="col-md-12 mt-2">
                            <label>{{__('db.Payment Note')}}</label>
                            <textarea rows="3" class="form-control" name="payment_note"></textarea>
                        </div>
                    </div>

                    {{-- <div class="d-none">
                        <select name="paid_by_id">
                            <option value="1">Cash</option>
                        </select>
                    </div> --}}

                    <button type="submit" class="btn btn-primary" id="add-payment-submit-btn">
                        {{__('db.submit')}}
                    </button>
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
        $("ul#sale #challan-list-menu").addClass("active");

        function confirmDelete() {
            if (confirm("Are you sure want to delete?")) {
                return true;
            }
            return false;
        }

        $("#courier-id").val(<?php echo json_encode($courier_id) ?>);
        var challan_id = [];
        var balance = {};
        var expired_date = {};
        var current_date = <?php echo json_encode(date("Y-m-d")) ?>;
        var paymentOptions = <?php echo json_encode($options) ?>;

        $(document).on("click", "table.challan-list tbody .add-payment", function() {
            $("#cheque").hide();
            $(".gift-card").hide();
            $(".card-element").hide();
            $('select[name="paid_by_id"]').val(1);
            $('.selectpicker').selectpicker('refresh');

            var id = $(this).data('id').toString();
            $('input[name="challan_id"]').val(id);
            
            // Fetch Packing Slips via AJAX
            $.get('{{url("challans/get-packing-slips")}}/' + id, function(data) {
                var html = '';
                $.each(data, function(key, ps) {
                    var order_amount = parseFloat(ps.amount) || 0;
                    var sale_due = parseFloat(ps.due) || 0;
                    var max_payable = Math.min(order_amount, sale_due);
                    if (max_payable < 0) max_payable = 0;

                    var is_disabled = (ps.is_paid || sale_due <= 0) ? 'readonly' : '';
                    var paid_amount = is_disabled ? 0 : max_payable;

                    html += '<tr>';
                    html += '<td><span class="badge badge-info">' + ps.reference + '</span></td>';
                    html += '<td>' + ps.order_reference + '</td>';
                    html += '<td>';
                    html += '    <select name="paying_method_list[]" class="form-control modal_paying_method" ' + (is_disabled ? 'disabled' : '') + '>';
                    if(paymentOptions.length > 0) {
                        $.each(paymentOptions, function(i, option) {
                            html += '        <option value="' + option + '">' + option + '</option>';
                        });
                    } else {
                        html += '        <option value="Cash">Cash</option>';
                    }
                    html += '    </select>';
                    html += '    <div class="mt-1 modal-payment-note-container d-none">';
                    html += '        <input type="text" name="payment_note_list[]" class="form-control form-control-sm" placeholder="Note/Cheque No">';
                    html += '    </div>';
                    html += '</td>';
                    html += '<td>';
                    html += '    <input type="number" name="paid_amount_list[]" class="form-control modal_paid_amount" step="any" value="' + paid_amount.toFixed(2) + '" ' + is_disabled + ' required>';
                    html += '    <input type="hidden" class="modal-max-payable" value="' + max_payable + '">';
                    html += '    <input type="hidden" class="modal-sale-due" value="' + sale_due + '">';
                    html += '    <small class="text-muted"><b>PS Amt:</b> ' + order_amount.toFixed(2) + ' | <b>Sale Due:</b> ' + sale_due.toFixed(2) + '</small>';
                    html += '</td>';
                    html += '<td><input type="number" name="delivery_charge_list[]" class="form-control modal_delivery_charge_list" step="any" value="0"></td>';
                    html += '</tr>';
                });
                $('#modal-order-list').html(html);
                $('.selectpicker').selectpicker('refresh');
            });
        });

        // Toggle payment note in modal
        $(document).on('change', '.modal_paying_method', function() {
            var method = $(this).val();
            var noteContainer = $(this).closest('td').find('.modal-payment-note-container');
            if(method && method.toLowerCase() === 'cheque') {
                noteContainer.removeClass('d-none');
            } else {
                noteContainer.addClass('d-none');
                noteContainer.find('input').val('');
            }
        });

        // Real-time validation for paid amount
        $(document).on('input', '.modal_paid_amount', function() {
            var maxPayable = parseFloat($(this).closest('tr').find('.modal-max-payable').val()) || 0;
            var val = parseFloat($(this).val()) || 0;
            if(val > (maxPayable + 0.01)) {
                $(this).addClass('is-invalid').css('border-color', '#dc3545');
                if(!$(this).next('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback d-block text-danger" style="font-size: 11px;">Max allowed: ' + maxPayable.toFixed(2) + '</div>');
                }
            } else {
                $(this).removeClass('is-invalid').css('border-color', '');
                $(this).next('.invalid-feedback').remove();
            }
        });

        // Validation for Modal Submission
        $('#add-payment-form').on('submit', function(e) {
            var isValid = true;
            $('#modal-order-table tbody tr').each(function() {
                var maxPayable = parseFloat($(this).find('.modal-max-payable').val()) || 0;
                var paidAmount = parseFloat($(this).find('.modal_paid_amount').val()) || 0;
                var isReadonly = $(this).find('.modal_paid_amount').attr('readonly');
                var orderRef = $(this).find('td:nth-child(2)').text().trim();

                if (!isReadonly && paidAmount > 0) {
                    if (paidAmount > (maxPayable + 0.01)) {
                        alert('Error: Paid amount (' + paidAmount + ') cannot exceed the maximum payable amount (' + maxPayable.toFixed(2) + ') for Order: ' + orderRef);
                        isValid = false;
                        return false;
                    }
                }
            });

            if (!isValid) {
                e.preventDefault();
            }
        });

        $('input[name="paying_amount"]').on("input", function() {
            $(".change").text(parseFloat($(this).val() - $('input[name="amount"]').val()).toFixed(2));
        });

        $('input[name="amount"]').on("input", function() {
            if (parseFloat($(this).val()) > parseFloat($('input[name="paying_amount"]').val())) {
                alert('Paying amount cannot be bigger than recieved amount');
                $(this).val('');
            }
            else if (parseFloat($(this).val()) > parseFloat($('input[name="balance"]').val())) {
                alert('Paying amount cannot be bigger than due amount');
                $(this).val('');
            }
            $(".change").text(parseFloat($('input[name="paying_amount"]').val() - $(this).val()).toFixed(2));
        });

        $('select[name="paid_by_id"]').on("change", function() {
            var id = $(this).val();
            $(".payment-form").off("submit");
            if(id == 4) {
                $("#cheque").show();
                $('input[name="cheque_no"]').attr('required', true);
            } else {
                $("#cheque").hide();
                $('input[name="cheque_no"]').attr('required', false);
            }
        });

        $(document).on('submit', '#challan-deposit-form', function(e) {
            challan_id.length = 0;
            $(':checkbox:checked').each(function(i) {
                if(i){
                    challan_id[i-1] = $(this).closest('tr').data('id');
                }
            });
            $("input[name=challan_id]").val(challan_id.toString());
        });

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        var courier_id = $("#courier-id").val();
        var status = $("select[name=status]").val();

        $('#challan-data-table').DataTable( {
            "processing": true,
            "serverSide": true,
            "ajax":{
                url:"challans/challan-data",
                data:{
                    courier_id: courier_id,
                    status: status
                },
                dataType: "json",
                type:"post"
            },
            "createdRow": function( row, data, dataIndex ) {
                $(row).attr('data-id', data['id']);
            },
            "columns": [
                {"data": "id"},
                {"data": "date"},
                {"data": "reference"},
                {"data": "sale_reference"},
                {"data": "courier"},
                {"data": "status"},
                {"data": "closing_date"},
                {"data": "total_amount"},
                {"data": "created_by"},
                {"data": "closed_by"},
                {"data": "options"},
            ],
            order:[['2', 'desc']],
            'columnDefs': [
                {
                    "orderable": false,
                    'targets': [3, 4, 5, 6, 7, 8, 9, 10]
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
            'select': { style: 'multi', selector: 'td:first-child'},
            'lengthMenu': [[50, 100, 150, -1], [50, 100, 150, "All"]],
            dom: '<"row"lfB>rtip',
            buttons: [
                {
                    extend: 'pdf',
                    text: 'PDF',
                    exportOptions: {
                        columns: ':visible:not(.not-exported)',
                        rows: ':visible',
                    },
                    action: function(e, dt, button, config) {
                        datatable_sum(dt, true);
                        $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                        datatable_sum(dt, false);
                    },
                    footer:true
                },
                {
                    extend: 'csv',
                    text: 'CSV',
                    exportOptions: {
                        columns: ':visible:not(.not-exported)',
                        rows: ':visible',
                    },
                    action: function(e, dt, button, config) {
                        datatable_sum(dt, true);
                        $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
                        datatable_sum(dt, false);
                    },
                    footer:true
                },
                {
                    extend: 'print',
                    text: 'Print',
                    exportOptions: {
                        columns: ':visible:not(.not-exported)',
                        rows: ':visible',
                    },
                    action: function(e, dt, button, config) {
                        datatable_sum(dt, true);
                        $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                        datatable_sum(dt, false);
                    },
                    footer:true
                },
                {
                    extend: 'colvis',
                    text: 'Column visibility',
                    columns: ':gt(0)'
                },
            ],
            drawCallback: function () {
                var api = this.api();
                datatable_sum(api, false);
            }
        } );

        function datatable_sum(dt_selector, is_calling_first) {
            if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
                var rows = dt_selector.rows( '.selected' ).indexes();

                $( dt_selector.column( 7 ).footer() ).html(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum().toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
            }
            else {
                $( dt_selector.column( 7 ).footer() ).html(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum().toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
            }
        }

    </script>
@endpush
