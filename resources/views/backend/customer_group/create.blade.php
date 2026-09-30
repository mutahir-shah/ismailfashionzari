@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')

<x-error-message key="name" />
<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <a href="#" data-toggle="modal" data-target="#createModal" class="btn btn-info"><i class="ti ti-plus"></i> {{__('db.Add Customer Group')}}</a>
        <a href="#" data-toggle="modal" data-target="#importcustomer_group" class="btn btn-primary"><i class="ti ti-copy"></i> {{__('db.Import Customer Group')}}</a>
    </div>
    <div class="table-responsive">
        <table id="customer-grp-table" class="table">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.name')}}</th>
                    <th>{{__('db.Percentage')}}</th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_customer_group_all as $key=>$customer_group)
                <tr data-id="{{$customer_group->id}}">
                    <td>{{$key}}</td>
                    <td>{{ $customer_group->name }}</td>
                    <td>{{ $customer_group->percentage}}</td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{__('db.action')}}
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button" data-id="{{$customer_group->id}}" class="open-EditCustomerGroupDialog btn btn-link" data-toggle="modal" data-target="#editModal"><i class="ti ti-edit"></i> {{__('db.edit')}}
                                    </button>
                                </li>
                                <li class="divider"></li>
                                <form action="{{ route('customer_group.destroy', $customer_group->id) }}" method="POST">
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
        </table>
    </div>
</section>

<div id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
<div role="document" class="modal-dialog">
  <div class="modal-content">
    <form action="{{ route('customer_group.store') }}" method="POST">
      @csrf
      <div class="modal-header">
        <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Customer Group')}}</h5>
        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
      </div>
      <div class="modal-body">
      <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
      <form>
        <div class="form-group">
          <label>{{__('db.name')}} *</label>
          <input type="text" name="name" required="required" class="form-control">
        </div>
        <div class="form-group">
          <label>{{__('db.Percentage')}}(%) * <i class="ti ti-info-circle" data-toggle="tooltip" title="{{__('db.If you want to sell your product at default price, then the percentage value must be zero')}}"></i></label>
          <input type="number" min="0" name="percentage" required="required" class="form-control">
        </div>
        <div class="form-group">
          <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
        </div>
      </form>
    </div>

    </form>
  </div>
</div>
</div>

<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
<div role="document" class="modal-dialog">
  <div class="modal-content">
    <form action="{{ route('customer_group.update', 1) }}" method="POST">
      @csrf
      @method('PUT')
      <div class="modal-header">
        <h5 id="exampleModalLabel" class="modal-title">{{__('db.Update Customer Group')}}</h5>
        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
      </div>
      <div class="modal-body">
      <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
        <div class="form-group">
            <input type="hidden" name="customer_group_id">
          <label>{{__('db.name')}} *</label>
          <input type="text" name="name" required="required" class="form-control">
        </div>
        <div class="form-group">
          <label>{{__('db.Percentage')}}(%) *  <i class="ti ti-info-circle" data-toggle="tooltip" title="{{__('db.If you want to sell your product at default price, then the percentage value must be zero')}}"></i></label>
          <input type="text" name="percentage" required="required" class="form-control">
        </div>
        <div class="form-group">
          <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
        </div>
    </div>
    </form>
  </div>
</div>
</div>

<div id="importcustomer_group" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
<div role="document" class="modal-dialog">
  <div class="modal-content">
    <form action="{{ route('customer_group.import') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="modal-header">
        <h5 id="exampleModalLabel" class="modal-title"> {{__('db.Import Customer Group')}}</h5>
        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
      </div>
      <div class="modal-body">
        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
       <p>{{__('db.The correct column order is')}} (name*, percentage*) {{__('db.and you must follow this')}}.</p>
      <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{__('db.Upload CSV File')}} *</label>
                    <input type="file" name="file" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label> {{__('db.Sample File')}}</label>
                    <a href="sample_file/sample_customer_group.csv" class="btn btn-info btn-block btn-md"><i class="ti ti-download"></i>  {{__('db.Download')}}</a>
                </div>
            </div>
      </div>

        <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
    </div>
    </form>
  </div>
</div>
</div>


@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">
    $("ul#setting").siblings('a').attr('aria-expanded','true');
    $("ul#setting").addClass("show");
    $("ul#setting #customer-group-menu").addClass("active");

    var customer_group_id = [];
    var user_verified = <?php echo json_encode(config('app.user_verified'))?>;

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
    $(document).ready(function() {

        $(document).on('click', '.open-EditCustomerGroupDialog', function() {
            var url = "customer_group/"
            var id = $(this).data('id').toString();
            url = url.concat(id).concat("/edit");

            $.get(url, function(data) {
                $("input[name='name']").val(data['name']);
                $("input[name='percentage']").val(data['percentage']);
                $("input[name='customer_group_id']").val(data['id']);
            });
        });
    });

    $('#customer-grp-table').DataTable( {
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
                'targets': [0, 3]
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
                        customer_group_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                customer_group_id[i-1] = $(this).closest('tr').data('id');
                            }
                        });
                        if(customer_group_id.length && confirm("Are you sure want to delete?")) {
                            $.ajax({
                                type:'POST',
                                url:'customer_group/deletebyselection',
                                data:{
                                    customer_groupIdArray: customer_group_id
                                },
                                success:function(data){
                                    $(':checkbox:checked').each(function(i) {
                                            if (i) {
                                                 dt.row($(this).closest('tr')).remove().draw(false);
                                            }
                                        });
                                        alert(data);
                                }
                            });
                            // dt.rows({ page: 'current', selected: true }).remove().draw(false);
                        }
                        else if(!customer_group_id.length)
                            alert('No customer group is selected!');
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

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $( "#select_all" ).on( "change", function() {
        if ($(this).is(':checked')) {
            $("tbody input[type='checkbox']").prop('checked', true);
        }
        else {
            $("tbody input[type='checkbox']").prop('checked', false);
        }
    });

    $("#export").on("click", function(e){
    e.preventDefault();
    var customer_group = [];
    $(':checkbox:checked').each(function(i){
      customer_group[i] = $(this).val();
    });
    $.ajax({
       type:'POST',
       url:'/exportcustomer_group',
       data:{
            customer_groupArray: customer_group
        },
       success:function(data){
         alert('Exported to CSV file successfully! Click Ok to download file');
         window.location.href = data;
       }
    });
});
</script>

@endpush
