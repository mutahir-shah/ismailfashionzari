@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />
<x-error-message key="coupon_no" />

<section>
    <div class="container-fluid">
        <button class="btn btn-info" data-toggle="modal" data-target="#create-modal"><i class="ti ti-plus"></i> {{__('db.Add Coupon')}}</button>
    </div>
    <div class="table-responsive">
        <table id="coupon-table" class="table" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.Coupon Code')}}</th>
                    <th>{{__('db.Type')}}</th>
                    <th>{{__('db.Amount')}}</th>
                    <th>{{__('db.Minimum Amount')}}</th>
                    <th>Qty</th>
                    <th>{{__('db.Available')}}</th>
                    <th>{{__('db.Expired Date')}}</th>
                    <th>{{__('db.Created By')}}</th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_coupon_all as $key=>$coupon)
                <?php
                    $created_by = DB::table('users')->find($coupon->user_id);
                ?>
                <tr data-id="{{$coupon->id}}">
                    <td>{{$key}}</td>
                    <td>{{ $coupon->code }}</td>
                    @if($coupon->type == 'percentage')
                    <td><div class="badge badge-primary">{{$coupon->type}}</div></td>
                    @else
                    <td><div class="badge badge-info">{{$coupon->type}}</div></td>
                    @endif
                    <td>{{ $coupon->amount }}</td>
                    @if($coupon->minimum_amount)
                    <td>{{ $coupon->minimum_amount }}</td>
                    @else
                    <td>N/A</td>
                    @endif
                    <td>{{ $coupon->quantity }}</td>
                    @if($coupon->quantity - $coupon->used)
                    <td class="text-center"><div class="badge badge-success">{{ $coupon->quantity - $coupon->used }}</div></td>
                    @else
                    <td class="text-center"><div class="badge badge-danger">{{ $coupon->quantity - $coupon->used }}</div></td>
                    @endif
                    @if($coupon->expired_date >= date("Y-m-d"))
                      <td><div class="badge badge-success">{{date('d-m-Y', strtotime($coupon->expired_date))}}</div></td>
                    @else
                      <td><div class="badge badge-danger">{{date('d-m-Y', strtotime($coupon->expired_date))}}</div></td>
                    @endif
                    <td>{{ $created_by->name }}</td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{__('db.action')}}
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li><button type="button" data-id="{{$coupon->id}}" data-code="{{$coupon->code}}" data-type="{{$coupon->type}}" data-amount="{{$coupon->amount}}" data-minimum_amount="{{$coupon->minimum_amount}}" data-quantity="{{$coupon->quantity}}" data-expired_date="{{$coupon->expired_date}}" class="edit-btn btn btn-link" data-toggle="modal" data-target="#editModal"><i class="ti ti-edit"></i> {{__('db.edit')}}</button></li>
                                <form action="{{ route('coupons.destroy', $coupon->id) }}" method="POST">
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

<div id="create-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Coupon')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
              <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                <form action="{{ route('coupons.store') }}" method="POST">
                    @csrf
                  <div class="row">
                      <div class="col-md-6 form-group">
                          <label>{{__('db.Coupon Code')}} *</label>
                          <div class="input-group">
                              <input type="text" name="code" class="form-control" required>
                              <div class="input-group-append">
                                  <button type="button" class="btn btn-default btn-sm genbutton">{{__('db.Generate')}}</button>
                              </div>
                          </div>
                      </div>
                      <div class="col-md-6 form-group">
                          <label>{{__('db.Type')}} *</label>
                          <select class="form-control" name="type">
                            <option value="percentage">Percentage</option>
                            <option value="fixed">Fixed Amount</option>
                          </select>
                      </div>
                      <div class="col-md-6 form-group minimum-amount">
                          <label>{{__('db.Minimum Amount')}} *</label>
                          <input type="number" name="minimum_amount" step="any" class="form-control">
                      </div>
                      <div class="col-md-6 form-group">
                          <label>{{__('db.Amount')}} *</label>
                          <div class="input-group">
                              <input type="number" name="amount" step="any" required class="form-control">&nbsp;&nbsp;
                              <div class="input-group-append mt-1">
                                  <span class="icon-text" style="font-size: 22px;"><strong>%</strong></span>
                              </div>
                          </div>
                      </div>
                      <div class="col-md-6 form-group">
                          <label>Qty *</label>
                          <input type="number" name="quantity" step="any" required class="form-control">
                      </div>
                      <div class="col-md-6 form-group">
                          <label>{{__('db.Expired Date')}}</label>
                          <input type="text" name="expired_date" class="expired_date form-control">
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

<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
  <div role="document" class="modal-dialog">
      <div class="modal-content">
          <div class="modal-header">
              <h5 id="exampleModalLabel" class="modal-title">{{__('db.Update Coupon')}}</h5>
              <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
          </div>
          <div class="modal-body">
            <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
              <form action="{{ route('coupons.update', 1) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                  <div class="col-md-6 form-group">
                      <label>{{__('db.Coupon')}} {{__('db.Code')}} *</label>
                      <div class="input-group">
                          <input type="hidden" name="coupon_id">
                        <input type="text" name="code" class="form-control" required>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default btn-sm genbutton">{{__('db.Generate')}}</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label>{{__('db.Type')}} *</label>
                    <select class="form-control" name="type">
                      <option value="percentage">Percentage</option>
                      <option value="fixed">Fixed Amount</option>
                    </select>
                </div>
                <div class="col-md-6 form-group minimum-amount">
                    <label>{{__('db.Minimum Amount')}} *</label>
                    <input type="number" name="minimum_amount" step="any" class="form-control">
                </div>
                <div class="col-md-6 form-group">
                    <label>{{__('db.Amount')}} *</label>
                    <div class="input-group">
                        <input type="number" name="amount" step="any" required class="form-control">&nbsp;&nbsp;
                        <div class="input-group-append mt-1">
                            <span class="icon-text" style="font-size: 22px;"><strong>%</strong></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label>Qty *</label>
                    <input type="number" name="quantity" step="any" required class="form-control">
                </div>
                <div class="col-md-6 form-group">
                    <label>{{__('db.Expired Date')}}</label>
                    <input type="text" name="expired_date" class="expired_date form-control">
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

    $("ul#sale").siblings('a').attr('aria-expanded','true');
    $("ul#sale").addClass("show");
    $("ul#sale #coupon-menu").addClass("active");

    var coupon_id = [];
    var user_verified = <?php echo json_encode(config('app.user_verified'))?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $("#create-modal .expired_date").val($.datepicker.formatDate('yy-mm-dd', new Date()));
    $(".minimum-amount").hide();

    $("#create-modal select[name='type']").on("change", function() {
      if ($(this).val() == 'fixed') {
          $("#create-modal .minimum-amount").show();
          $("#create-modal .minimum-amount").prop('required',true);
          $("#create-modal .icon-text").text('$');
      }
      else {
          $("#create-modal .minimum-amount").hide();
          $("#create-modal .minimum-amount").prop('required',false);
          $("#create-modal .icon-text").text('%');
      }
    });

    $(document).on("change", "#editModal select[name='type']", function() {
      if ($(this).val() == 'fixed') {
          $("#editModal .minimum-amount").show();
          $("#editModal .minimum-amount").prop('required',true);
          $("#editModal .icon-text").text('$');
      }
      else {
          $("#editModal .minimum-amount").hide();
          $("#editModal .minimum-amount").prop('required',false);
          $("#editModal .icon-text").text('%');
      }
    });

    $(document).on("click", '#create-modal .genbutton', function(){
      $.get('coupons/gencode', function(data){
        $("input[name='code']").val(data);
      });
    });

    $(document).on("click", '#editModal .genbutton', function(){
      $.get('coupons/gencode', function(data){
        $("#editModal input[name='code']").val(data);
      });
    });

    $(document).ready(function() {
        $(document).on('click', '.edit-btn', function() {
            $("#editModal input[name='code']").val($(this).data('code'));
            $("#editModal select[name='type']").val($(this).data('type'));
            $("#editModal input[name='amount']").val($(this).data('amount'));
            $("#editModal input[name='minimum_amount']").val($(this).data('minimum_amount'));
            $("#editModal input[name='quantity']").val($(this).data('quantity'));
            $("#editModal input[name='expired_date']").val($(this).data('expired_date'));
            $("#editModal input[name='coupon_id']").val($(this).data('id'));
            if($(this).data('type') == 'fixed'){
                $("#editModal .minimum-amount").show();
                $("#editModal .minimum-amount").prop('required',true);
                $("#editModal .icon-text").text('$');
            }
            $('.selectpicker').selectpicker('refresh');
        });
    });

    var expired_date = $('.expired_date');
    expired_date.datepicker({
     format: "yyyy-mm-dd",
     startDate: "<?php echo date('Y-m-d'); ?>",
     autoclose: true,
     todayHighlight: true
     });

function confirmDelete() {
    if (confirm("Are you sure want to delete?")) {
        return true;
    }
    return false;
}

    var table = $('#coupon-table').DataTable( {
        responsive: true,
        fixedHeader: {
            header: true,
            footer: true
        },
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
                }
            },
            {
                extend: 'excel',
                text: '<i title="export to excel" class="ti ti-file-type-xls"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                }
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                }
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                }
            },
            {
                text: '<i title="delete" class="ti ti-x"></i>',
                className: 'buttons-delete',
                action: function ( e, dt, node, config ) {
                    if(user_verified == '1') {
                        coupon_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                coupon_id[i-1] = $(this).closest('tr').data('id');
                            }
                        });
                        if(coupon_id.length && confirm("Are you sure want to delete?")) {
                            $.ajax({
                                type:'POST',
                                url:'coupons/deletebyselection',
                                data:{
                                    couponIdArray: coupon_id
                                },
                                success:function(data){
                                    alert(data);
                                }
                            });
                            dt.rows({ page: 'current', selected: true }).remove().draw(false);
                        }
                        else if(!coupon_id.length)
                            alert('No coupon is selected!');
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
        ]
    } );

</script>
@endpush
