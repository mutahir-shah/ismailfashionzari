@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')

<x-validation-error fieldName="name" />
<x-validation-error fieldName="rate" />
<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <a href="#" data-toggle="modal" data-target="#createModal" class="btn btn-info"><i class="ti ti-plus"></i> {{__('db.Add Tax')}}</a>
        <a href="#" data-toggle="modal" data-target="#importTax" class="btn btn-primary"><i class="ti ti-copy"></i> {{__('db.Import Tax')}}</a>
    </div>
    <div class="table-responsive">
        <table id="tax-table" class="table">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.name')}}</th>
                    <th>{{__('db.Rate')}}(%)</th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_tax_all as $key=>$tax)
                <tr data-id="{{$tax->id}}">
                    <td>{{$key}}</td>
                    <td>{{ $tax->name }}</td>
                    <td>{{ $tax->rate }}</td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{__('db.action')}}
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button" data-id="{{$tax->id}}" class="open-EdittaxDialog btn btn-link" data-toggle="modal" data-target="#editModal"><i class="ti ti-edit"></i> {{__('db.edit')}}
                                </button>
                                </li>
                                <li class="divider"></li>
                                <form action="{{ route('tax.destroy', $tax->id) }}" method="POST">
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
            <form action="{{ route('tax.store') }}" method="POST">
                @csrf
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Tax')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                <form>
                    <div class="form-group">
                    <label>{{__('db.Tax Name')}} *</label>
                    <input type="text" name="name" required class="form-control" placeholder="{{ __('db.Tax Name') }}">
                    </div>
                    <div class="form-group">
                        <label>{{__('db.Rate')}}(%) *</label>
                        <input type="number" name="rate" required class="form-control" step="any" placeholder="{{ __('db.Tax Rate') }}">
                    </div>
                    <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
            	</form>
        	</div>
        </form>
    	</div>
	</div>
</div>

<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
	<div role="document" class="modal-dialog">
		  <div class="modal-content">
		    <form action="{{ route('tax.update', 1) }}" method="POST">
		      @csrf
		      @method('PUT')
		    <div class="modal-header">
		      <h5 id="exampleModalLabel" class="modal-title"> {{__('db.Update Tax')}}</h5>
		      <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
		    </div>
		    <div class="modal-body">
		      <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
		        <form>
		            <input type="hidden" name="tax_id">
		            <div class="form-group">
		                <label>{{__('db.Tax Name')}} *</label>
		                <input type="text" name="name" value="{{ old('name') }}" required class="form-control" placeholder="{{ __('db.Tax Name') }}">
		            </div>
		            <div class="form-group">
		                <label>{{__('db.Rate')}}(%) *</label>
		                <input type="number" name="rate" value="{{ old('rate') }}" required class="form-control" step="any" placeholder="{{ __('db.Tax Rate') }}">
		            </div>
		            <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
		        </form>
		    </div>
		    </form>
		  </div>
	</div>
</div>

<div id="importTax" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('tax.import') }}" method="POST" enctype="multipart/form-data">
          @csrf
        <div class="modal-header">
          <h5 id="exampleModalLabel" class="modal-title">{{__('db.Import Tax')}}</h5>
          <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
        </div>
        <div class="modal-body">
            <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
           <p>{{__('db.The correct column order is')}} (name*, rate*) {{__('db.and you must follow this')}}.</p>

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
                        <a href="sample_file/sample_tax.csv" class="btn btn-info btn-block btn-md"><i class="ti ti-download"></i>  {{__('db.Download')}}</a>
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
    $("ul#setting #tax-menu").addClass("active");

    var tax_id = [];
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
    $(document).on('click', '.open-EdittaxDialog', function() {
        var url = "tax/"
        var id = $(this).data('id').toString();
        url = url.concat(id).concat("/edit");

        $.get(url, function(data) {
            $("#editModal input[name='name']").val(data['name']);
            $("#editModal input[name='rate']").val(data['rate']);
            $("#editModal input[name='tax_id']").val(data['id']);
        });
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('#tax-table').DataTable( {
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
        'select': {
            'style': 'multi'
        },
        'select': { style: 'multi',  selector: 'td:first-child'},
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
                        tax_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                tax_id[i-1] = $(this).closest('tr').data('id');
                            }
                        });
                        if(tax_id.length && confirm("Are you sure want to delete?")) {
                            $.ajax({
                                type:'POST',
                                url:'tax/deletebyselection',
                                data:{
                                    taxIdArray: tax_id
                                },
                                success:function(data){
                                    alert(data);
                                }
                            });
                            dt.rows({ page: 'current', selected: true }).remove().draw(false);
                        }
                        else if(!tax_id.length)
                            alert('No tax is selected!');
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

});
</script>
@endpush
