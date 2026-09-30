@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible text-center">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        {{ $errors->first() }}
    </div>
@endif

<section>
    <div class="container-fluid">
        <button class="btn btn-info" data-toggle="modal" data-target="#create-money-transfer-modal"><i class="ti ti-plus"></i> {{__('db.Add Money Transfer')}}</button>
    </div>
    <div class="table-responsive">
        <table id="money-transfer-table" class="table">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.date')}}</th>
                    <th>{{__('db.Reference No')}}</th>
                    <th>{{__('db.From Account')}}</th>
                    <th>{{__('db.To Account')}}</th>
                    <th>{{__('db.Amount')}}</th>
                    <th>{{__('db.Currency')}}</th>
                    <th>{{__('db.Exchange Rate')}}</th>
                    <th>{{__('db.note')}}</th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_money_transfer_all as $key=>$money_transfer)
                <tr data-id="{{$money_transfer->id}}">
                    <td>{{$key}}</td>
                    <td>{{date(gen_setting()->date_format, strtotime($money_transfer->created_at->toDateString())) . ' '. $money_transfer->created_at->toTimeString() }}</td>
                    <td>{{ $money_transfer->reference_no }}</td>
                    <td>{{ $money_transfer->fromAccount->name }}</td>
                    <td>{{ $money_transfer->toAccount->name }}</td>
                    <td>{{ number_format((float)$money_transfer->amount, gen_setting()->decimal, '.', '')}}</td>
                    <td>{{ optional($money_transfer->currency)->code ?? 'N/A' }}</td>
                    <td>{{ number_format((float)($money_transfer->exchange_rate ?? 1), gen_setting()->decimal, '.', '') }}</td>
                    <td>{{ $money_transfer->note }}</td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{__('db.action')}}
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li><button type="button" id="edit-btn" data-id="{{$money_transfer->id}}" data-created_at="{{date(config('date_format'), strtotime($money_transfer->created_at->toDateString()))}}" data-from_id="{{$money_transfer->from_account_id}}" data-to_id="{{$money_transfer->to_account_id}}" data-amount="{{$money_transfer->amount}}" data-currency_id="{{$money_transfer->currency_id}}" data-exchange_rate="{{$money_transfer->exchange_rate ?? 1}}" data-note="{{$money_transfer->note}}" class=" btn btn-link" data-toggle="modal" data-target="#edit-money-transfer-modal"><i class="ti ti-edit"></i> {{__('db.edit')}}</button></li>
                                <li class="divider"></li>
                                <form action="{{ route('money-transfers.destroy', $money_transfer->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <li>
                                        <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="ti ti-trash"></i> {{__('db.delete')}}</button>
                                    </li>
                                </form>
                            </ul>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
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
                <th></th>
            </tfoot>
        </table>
    </div>
</section>

<!-- Create Money Transfer modal -->
<div id="create-money-transfer-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Money Transfer')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
              <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                <form action="{{ route('money-transfers.store') }}" method="POST">
                    @csrf
                  <div class="row">
                      <div class="col-md-6 form-group">
                          <label> {{__('db.From Account')}} *</label>
                          <select class="form-control selectpicker" name="from_account_id" data-live-search="true" data-live-search-style="begins" title="Select from account..." required>
                          @foreach($lims_account_list as $account)
                              <option value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]@if(isset($account->journal_balance)) — {{ number_format($account->journal_balance, 2) }}@endif</option>
                          @endforeach
                          </select>
                      </div>
                      <div class="col-md-6 form-group">
                          <label> {{__('db.To Account')}} *</label>
                          <select class="form-control selectpicker" name="to_account_id" data-live-search="true" data-live-search-style="begins" title="Select to account..." required>
                          @foreach($lims_account_list as $account)
                              <option value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]@if(isset($account->journal_balance)) — {{ number_format($account->journal_balance, 2) }}@endif</option>
                          @endforeach
                          </select>
                      </div>

                      <div class="col-md-6 form-group">
                          <label>{{__('db.Amount')}} *</label>
                          <input type="number" name="amount" class="form-control" step="any" required>
                      </div>

                      <div class="col-md-6 form-group">
                          <label>{{__('db.Currency')}} *</label>
                          <select class="form-control selectpicker currency-select" name="currency_id" data-live-search="true" data-live-search-style="begins" required>
                              @foreach($currency_list as $currency_data)
                                  <option value="{{$currency_data->id}}" data-rate="{{$currency_data->exchange_rate}}" @if(isset($currency) && $currency->id == $currency_data->id) selected @endif>{{$currency_data->code}}</option>
                              @endforeach
                          </select>
                      </div>

                      <div class="col-md-6 form-group">
                          <label>{{__('db.Exchange Rate')}} *</label>
                          <input type="number" name="exchange_rate" class="form-control" step="any" min="0.000001" value="{{ $currency->exchange_rate ?? 1 }}" required>
                      </div>

                      <div class="col-md-12 form-group">
                          <label>{{__('db.note')}}</label>
                          <textarea name="note" rows="3" class="form-control" maxlength="1000"></textarea>
                      </div>
                  </div>
                  <div class="form-group">
                      <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                  </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Money Transfer modal -->
<div id="edit-money-transfer-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Update Money Transfer')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                <form action="{{ route('money-transfers.update', 1) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <input type="hidden" name="id">
                        <div class="col-md-6 form-group">
                            <label>{{__('db.date')}}</label>
                            <input type="text" name="created_at" class="form-control date" placeholder="{{__('db.Choose date')}}"/>
                        </div>

                      <div class="col-md-6 form-group">
                          <label> {{__('db.From Account')}} *</label>
                          <select class="form-control selectpicker" name="from_account_id" data-live-search="true" data-live-search-style="begins" title="Select from account..." required>
                          @foreach($lims_account_list as $account)
                              <option value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]@if(isset($account->journal_balance)) — {{ number_format($account->journal_balance, 2) }}@endif</option>
                          @endforeach
                          </select>
                      </div>
                      <div class="col-md-6 form-group">
                          <label> {{__('db.To Account')}} *</label>
                          <select class="form-control selectpicker" name="to_account_id" data-live-search="true" data-live-search-style="begins" title="Select to account..." required>
                          @foreach($lims_account_list as $account)
                              <option value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]@if(isset($account->journal_balance)) — {{ number_format($account->journal_balance, 2) }}@endif</option>
                          @endforeach
                          </select>
                      </div>

                      <div class="col-md-6 form-group">
                          <label>{{__('db.Amount')}} *</label>
                          <input type="number" name="amount" class="form-control" step="any" required>
                      </div>

                      <div class="col-md-6 form-group">
                          <label>{{__('db.Currency')}} *</label>
                          <select class="form-control selectpicker currency-select" name="currency_id" data-live-search="true" data-live-search-style="begins" required>
                              @foreach($currency_list as $currency_data)
                                  <option value="{{$currency_data->id}}" data-rate="{{$currency_data->exchange_rate}}">{{$currency_data->code}}</option>
                              @endforeach
                          </select>
                      </div>

                      <div class="col-md-6 form-group">
                          <label>{{__('db.Exchange Rate')}} *</label>
                          <input type="number" name="exchange_rate" class="form-control" step="any" min="0.000001" required>
                      </div>

                      <div class="col-md-12 form-group">
                          <label>{{__('db.note')}}</label>
                          <textarea name="note" rows="3" class="form-control" maxlength="1000"></textarea>
                      </div>
                  </div>
                  <div class="form-group">
                      <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                  </div>
                </form>
            </div>
        </div>
    </div>
</div>



@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">

    $("ul#account").siblings('a').attr('aria-expanded','true');
    $("ul#account").addClass("show");
    $("ul#account #money-transfer-menu").addClass("active");

    var money_transfer_id = [];
    var user_verified = <?php echo json_encode(config('app.user_verified'))?>;
    var moneyTransferUpdateUrl = "{{ route('money-transfers.update', ':id') }}";


    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $(document).on('click', '#edit-btn', function() {
        var id = $(this).data('id');
        $("#edit-money-transfer-modal form").attr('action', moneyTransferUpdateUrl.replace(':id', id));
        $("#edit-money-transfer-modal input[name='created_at']").val($(this).data('created_at'));
        $("#edit-money-transfer-modal select[name='from_account_id']").val($(this).data('from_id'));
        $("#edit-money-transfer-modal select[name='to_account_id']").val($(this).data('to_id'));
        $("#edit-money-transfer-modal input[name='id']").val(id);
        $("#edit-money-transfer-modal input[name='amount']").val($(this).data('amount'));
        $("#edit-money-transfer-modal select[name='currency_id']").val($(this).data('currency_id'));
        $("#edit-money-transfer-modal input[name='exchange_rate']").val($(this).data('exchange_rate'));
        $("#edit-money-transfer-modal textarea[name='note']").val($(this).data('note'));
        $('.selectpicker').selectpicker('refresh');
    });

    $(document).on('change', '.currency-select', function() {
        var rate = $(this).find(':selected').data('rate') || 1;
        $(this).closest('form').find('input[name="exchange_rate"]').val(rate);
    });

    function confirmDelete() {
        if (confirm("Are you sure want to delete?")) {
            return true;
        }
        return false;
    }

    $('#money-transfer-table').DataTable( {
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
                'targets': [0, 9]
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
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
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
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.excelHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
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
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
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
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                text: '<i title="delete" class="ti ti-x"></i>',
                className: 'buttons-delete',
                action: function ( e, dt, node, config ) {
                    if(user_verified == '1') {
                        money_transfer_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                money_transfer_id[i-1] = $(this).closest('tr').data('id');
                            }
                        });
                        if(money_transfer_id.length && confirm("Are you sure want to delete?")) {
                            $.ajax({
                                type:'POST',
                                url:'money_transfers/deletebyselection',
                                data:{
                                    money_transferIdArray: money_transfer_id
                                },
                                success:function(data){
                                    alert(data);
                                }
                            });
                            dt.rows({ page: 'current', selected: true }).remove().draw(false);
                        }
                        else if(!money_transfer_id.length)
                            alert('No money_transfer is selected!');
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
        drawCallback: function () {
            var api = this.api();
            datatable_sum(api, false);
        }
    } );

    function datatable_sum(dt_selector, is_calling_first) {
        if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
            var rows = dt_selector.rows( '.selected' ).indexes();
            $( dt_selector.column( 5 ).footer() ).html(dt_selector.cells( rows, 5, { page: 'current' } ).data().sum().toFixed({{gen_setting()->decimal}}));
        }
        else {
            $( dt_selector.column( 5 ).footer() ).html(dt_selector.cells( rows, 5, { page: 'current' } ).data().sum().toFixed({{gen_setting()->decimal}}));
        }
    }

    /*if(all_permission.indexOf("money_transfers-delete") == -1)
        $('.buttons-delete').addClass('d-none');*/

</script>
@endpush
