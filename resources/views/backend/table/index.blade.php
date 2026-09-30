@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')

<x-validation-error fieldName="name" />
<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#createModal"><i class="ti ti-plus"></i> {{__('db.Add Table')}}</button>
    </div>
    <div class="table-responsive">
        <table id="table-table" class="table">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.name')}}</th>
                    <th>{{__('db.Number of Person')}}</th>
                    <th>{{__('db.Description')}}</th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_table_all as $key=>$table)
                <tr data-id="{{$table->id}}">
                    <td>{{$key}}</td>
                    <td>{{ $table->name }}</td>
                    <td>{{ $table->number_of_person }}</td>
                    <td>{{ $table->description }}</td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{__('db.action')}}
                              <span class="caret"></span>
                              <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button" data-id="{{$table->id}}" data-name="{{$table->name}}" data-number_of_person="{{$table->number_of_person}}" data-description="{{$table->description}}" data-floor_id="{{$table->floor_id}}" class="edit-btn btn btn-link" data-toggle="modal" data-target="#editModal" ><i class="ti ti-edit"></i>  {{__('db.edit')}}</button>
                                </li>
                                @if(isset($floors))
                                @if($table->qr_code_id)
                                    <li>
                                        <button type="button" data-id="{{$table->qr_code_id}}" class="btn btn-link btn-view-qr"><i class="ti ti-qrcode"></i> View QR</button>
                                    </li>
                                    <li>
                                        <a href="{{ url('qr/download/'.$table->qr_code_id) }}" class="btn btn-link"><i class="ti ti-download"></i> Download QR</a>
                                    </li>
                                @else
                                    <li>
                                        <button type="button" data-id="{{$table->id}}" data-type="table" class="btn btn-link btn-generate-qr"><i class="ti ti-qrcode"></i> Generate QR</button>
                                    </li>
                                @endif
                                @endif
                                <form action="{{ route('tables.destroy', $table->id) }}" method="POST">
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

<div class="modal fade" id="qrModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            @include('backend.qr-menu.includes', ['tables' => $lims_table_all])
        </div>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('tables.store') }}" method="POST">
          @csrf
          <div class="modal-header">
            <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Table')}}</h5>
            <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
          </div>
          <div class="modal-body">
          <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
          <form>
          	<div class="row">
          		<div class="col-md-6 form-group">
	                <label>{{__('db.name')}} *</label>
	                <input type="text" name="name" value="{{ old('name') }}" required class="form-control" placeholder="{{ __('db.Type table name') }}">
	            </div>
	            <div class="col-md-6 form-group">
	                <label>{{__('db.Number of Person')}} *</label>
	                <input type="number" name="number_of_person" value="{{ old('number_of_person') }}" required class="form-control">
	            </div>
	            <div class="col-md-12 form-group">
	                <label>{{__('db.Description')}}</label>
	                <textarea class="form-control" name="description" rows="5"></textarea>
	            </div>
                @if(isset($floors))
                <div class="col-md-12 form-group">
	                <label>{{__('db.Floor')}} *</label>
	                <select class="selectpicker form-control" name="floor_id">
                        @foreach($floors as $floor)
                        <option value="{{$floor->id}}">{{$floor->name}}</option>
                        @endforeach
                    </select>
	            </div>
                @endif
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
<!-- Edit Modal -->
<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
  <div role="document" class="modal-dialog">
    <div class="modal-content">
        <form action="{{ route('tables.update', 1) }}" method="POST">
            @csrf
            @method('PUT')
      <div class="modal-header">
        <h5 id="exampleModalLabel" class="modal-title">{{__('db.Update Table')}}</h5>
        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
      </div>
      <div class="modal-body">
        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
        <form>
	      	<div class="row">
	      		<div class="col-md-6 form-group">
	      			<input type="hidden" name="table_id">
	                <label>{{__('db.name')}} *</label>
	                <input type="text" name="name" value="{{ old('name') }}" required class="form-control" placeholder="{{ __('db.Type table name') }}">
	            </div>
	            <div class="col-md-6 form-group">
	                <label>{{__('db.Number of Person')}} *</label>
	                <input type="number" name="number_of_person" value="{{ old('number_of_person') }}" required class="form-control">
	            </div>
	            <div class="col-md-12 form-group">
	                <label>{{__('db.Description')}}</label>
	                <textarea class="form-control" name="description" rows="5"></textarea>
	            </div>
                @if(isset($floors))
                <div class="col-md-12 form-group">
	                <label>{{__('db.Floor')}} *</label>
	                <select class="selectpicker form-control" id="floor_id" name="floor_id">
                        @foreach($floors as $floor)
                        <option value="{{$floor->id}}">{{$floor->name}}</option>
                        @endforeach
                    </select>
	            </div>
                @endif
	      	</div>
	        <div class="form-group">
	          <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
	        </div>
	    </form>
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
    $("ul#setting #table-menu").addClass("active");

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
    $('.edit-btn').on('click', function(){
        $("#editModal input[name='table_id']").val($(this).data('id'));
        $("#editModal input[name='name']").val($(this).data('name'));
        $("#editModal input[name='number_of_person']").val($(this).data('number_of_person'));
        $("#editModal textarea[name='description']").val($(this).data('description'));
        $("#floor_id").val($(this).data('floor_id'));
    $('.selectpicker').selectpicker('refresh');
    });

    $(document).on('click', '.btn-generate-qr', function() {
        $('#qrModal')
            .data('qr-target-id', $(this).data('id'))
            .data('qr-target-type', $(this).data('type'))
            .modal('show');
    });

    $(document).on('click', '.btn-view-qr', function() {
        $.get('{{ url("qr/show") }}/' + $(this).data('id'), function(data) {
            if (data.success) {
                $('#qrcode').html('<img src="' + data.image_url + '" style="width:220px;height:220px;">');
                $('#qr_link').val(data.url);
                $('#qr_open').attr('href', data.url);
                $('#qr_actions').removeClass('d-none');
                $('#qrModal').modal('show');
            }
        });
    });

    $('#qrModal').on('hidden.bs.modal', function() {
        $(this).removeData('qr-target-id').removeData('qr-target-type');
    });
});

    $('#table-table').DataTable( {
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
                'targets': [0, 3, 4]
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
