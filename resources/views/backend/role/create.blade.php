@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')

<validation-error fieldName="name" />
<success-message key="message" />
<error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <a href="#" data-toggle="modal" data-target="#createModal" class="btn btn-info"><i class="ti ti-plus"></i> {{__('db.Add Role')}} </a>
    </div>
    <div class="table-responsive">
        <table id="role-table" class="table table-hover">
            <thead>
                <tr>
                    <th class="not-exported" style="width: 3%;"></th>
                    <th>{{__('db.name')}}</th>
                    <th>{{__('db.Description')}}</th>
                    <th class="not-exported text-right" style="width: 10%;">{{__('db.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_role_all as $key=>$role)
                <tr>
                    <td style="width: 3%;">{{$key}}</td>
                    <td>{{ $role->name }}</td>
                    <td>{{ $role->description }}</td>
                    <td class="text-right" style="width: 10%;">
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{__('db.action')}}
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button" data-id="{{$role->id}}" class="open-EditroleDialog btn btn-link" data-toggle="modal" data-target="#editModal"><i class="ti ti-edit"></i> {{__('db.edit')}}
                                </button>
                                </li>
                                <li class="divider"></li>
                                <li>
                                    <a href="{{ route('role.permission', ['id' => $role->id]) }}" class="btn btn-link"><i class="ti ti-lock-open"></i> {{__('db.Change Permission')}}</a>
                                </li>
                                @if($role->id > 2 && $role->id != 5)
                                <form action="{{ route('role.destroy', $role->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <li>
                                        <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="ti ti-trash"></i> {{__('db.delete')}}</button>
                                    </li>
                                </form>
                                @endif
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
            <form action="{{ route('role.store') }}" method="POST">
                @csrf
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Role')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                <form>
                    <div class="form-group">
                    <label>{{__('db.name')}} *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="form-control" placeholder="{{ __('db.type_role_name') }}">
                    </div>
                    <div class="form-group">
                        <label>{{__('db.Description')}}</label>
                        <textarea name="description" rows="5" class="form-control" placeholder="{{ __('db.type_role_description') }}">{{ old('description') }}</textarea>
                    </div>
                    <input type="hidden" name="is_active" value="1">
                    <input type="hidden" name="guard_name" value="web">
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
		    <form action="{{ route('role.update', 1) }}" method="POST">
		      @csrf
		      @method('PUT')
		    <div class="modal-header">
		      <h5 id="exampleModalLabel" class="modal-title">{{__('db.Update Role')}}</h5>
		      <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
		    </div>
		    <div class="modal-body">
		      <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
		        <form>
		            <input type="hidden" name="role_id">
		            <div class="form-group">
		                <label>{{__('db.name')}} *</label>
		                <input type="text" name="name" value="{{ old('name') }}" required class="form-control" placeholder="{{ __('db.type_role_name') }}">
		            </div>
		            <div class="form-group">
		                <label>{{__('db.Description')}}</label>
		                <textarea name="description" rows="5" class="form-control" placeholder="{{ __('db.type_role_description') }}">{{ old('description') }}</textarea>
		            </div>
		            <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
		        </form>
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
    $("ul#setting #role-menu").addClass("active");

	 function confirmDelete() {
        if (confirm("Are you sure want to delete?")) {
            return true;
        }
        return false;
    }

    $(document).ready(function() {
    $(document).on('click', '.open-EditroleDialog', function() {
        var url = "role/"
        var id = $(this).data('id').toString();
        url = url.concat(id).concat("/edit");

        $.get(url, function(data) {
            $("input[name='name']").val(data['name']);
            $("textarea[name='description']").val(data['description']);
            $("input[name='role_id']").val(data['id']);
        });
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('#role-table').DataTable( {
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
                extend: 'colvis',
                text: '<i title="column visibility" class="ti ti-eye"></i>',
                columns: ':gt(0)'
            },
        ],
    } );
});
</script>
@endpush
