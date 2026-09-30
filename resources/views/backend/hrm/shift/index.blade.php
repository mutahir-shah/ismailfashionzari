@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
@extends('backend.layout.main')
@section('content')

@push('css')
    @include('backend.layout.partials.datatable_css')
    <link rel="preload" href="{{ asset($asset_prefix . 'vendor/jquery-timepicker/jquery.timepicker.min.css') }}" as="style"
            onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="{{ asset($asset_prefix . 'vendor/jquery-timepicker/jquery.timepicker.min.css') }}" rel="stylesheet">
    </noscript>
@endpush


<x-error-message key="name" />
<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#createModal"><i class="ti ti-plus"></i> {{__('db.Add Shift')}}</button>
    </div>
    <div class="table-responsive">
        <table id="shift-table" class="table">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.Shift')}}</th>
                    <th>{{__('db.start_time')}}</th>
                    <th>{{__('db.end_time')}}</th>
                    <th>{{__('db.Grace In (min)')}}</th>
                    <th>{{__('db.Grace Out (min)')}}</th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_shift_all as $key=>$shift)
                <tr data-id="{{$shift->id}}">
                    <td>{{$key}}</td>
                    <td>{{ $shift->name }}</td>
                    <td>{{ $shift->start_time }}</td>
                    <td>{{ $shift->end_time }}</td>
                    <td>{{ $shift->grace_in }}</td>
                    <td>{{ $shift->grace_out }}</td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{__('db.action')}}
                              <span class="caret"></span>
                              <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button"
                                        data-id="{{$shift->id}}"
                                        data-name="{{$shift->name}}"
                                        data-start="{{$shift->start_time}}"
                                        data-end="{{$shift->end_time}}"
                                        data-grace-in="{{$shift->grace_in}}"
                                        data-grace-out="{{$shift->grace_out}}"
                                        class="edit-btn btn btn-link"
                                        data-toggle="modal" data-target="#editModal" >
                                        <i class="ti ti-edit"></i>  {{__('db.edit')}}
                                    </button>
                                </li>
                                <li class="divider"></li>
                                <form action="{{ route('shift.destroy', $shift->id) }}" method="POST">
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

<!-- Create Modal -->
<div id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('shift.store') }}" method="POST">
          @csrf
        <div class="modal-header">
          <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Shift')}}</h5>
          <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
        </div>
        <div class="modal-body">
          <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>

          <div class="form-group">
              <label>{{__('db.name')}} * <x-info title="Enter the name of the shift, e.g., Morning, Evening" /></label>
              <input type="text" name="name" required class="form-control" placeholder="{{ __('db.Type shift name') }}">
          </div>

          <div class="form-group">
              <label>{{__('db.start_time')}} * <x-info title="Enter the shift start time, e.g., 09:00 AM" /></label>
              <input type="text" id="start_time" name="start_time" class="form-control" value="@if($lims_hrm_setting_data){{$lims_hrm_setting_data->start_time}}@endif" required>
          </div>

          <div class="form-group">
              <label>{{__('db.end_time')}} * <x-info title="Enter the shift end time, e.g., 05:00 PM" /></label>
              <input type="text" id="end_time" name="end_time" class="form-control" value="@if($lims_hrm_setting_data){{$lims_hrm_setting_data->start_time}}@endif" required>
          </div>

          <div class="form-group">
              <label>{{__('db.Grace In (min)')}} <x-info title="Enter allowed grace time for check-in in minutes" /></label>
              <input type="number" name="grace_in" value="{{ old('grace_in') }}" class="form-control" min="0">
          </div>

          <div class="form-group">
              <label>{{__('db.Grace Out (min)')}} <x-info title="Enter allowed grace time for check-out in minutes" /></label>
              <input type="number" name="grace_out" value="{{ old('grace_out') }}" class="form-control" min="0">
          </div>

          <div class="form-group">
            <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
          </div>

        </div>
        </form>
      </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
  <div role="document" class="modal-dialog">
    <div class="modal-content">
        <form action="{{ route('shift.update', 1) }}" method="POST">
            @csrf
            @method('PUT')
      <div class="modal-header">
        <h5 id="exampleModalLabel" class="modal-title">{{__('db.update_shift')}}</h5>
        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
      </div>
      <div class="modal-body">
        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>

        <div class="form-group">
            <label>{{__('db.name')}} * <x-info title="Edit the name of the shift" /></label>
            <input type="text" name="name" value="{{ old('name') }}" required class="form-control" placeholder="{{ __('db.Type shift name') }}">
        </div>

        <div class="form-group">
            <label>{{__('db.start_time')}} * <x-info title="Edit the shift start time" /></label>
            <input type="text" id="start_time" name="start_time" class="form-control" value="@if($lims_hrm_setting_data){{$lims_hrm_setting_data->start_time}}@endif" required>
        </div>

        <div class="form-group">
            <label>{{__('db.end_time')}} * <x-info title="Edit the shift end time" /></label>
            <input type="text" id="end_time" name="end_time" class="form-control" value="@if($lims_hrm_setting_data){{$lims_hrm_setting_data->start_time}}@endif" required>
        </div>

        <div class="form-group">
            <label>{{__('db.Grace In (min)')}} <x-info title="Update allowed grace time for check-in in minutes" /></label>
            <input type="number" name="grace_in" value="{{ old('grace_in') }}" class="form-control" min="0">
        </div>

        <div class="form-group">
            <label>{{__('db.Grace Out (min)')}} <x-info title="Update allowed grace time for check-out in minutes" /></label>
            <input type="number" name="grace_out" value="{{ old('grace_out') }}" class="form-control" min="0">
        </div>

        <input type="hidden" name="shift_id">
        <div class="form-group">
            <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
        </div>

      </div>
      </form>
    </div>
  </div>
</div>


@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/jquery/jquery.timepicker.min.js') }}"></script>
    <script type="text/javascript">
        $("ul#hrm").siblings('a').attr('aria-expanded','true');
        $("ul#hrm").addClass("show");
        $("ul#hrm #shift-menu").addClass("active");

        var shift_id = [];
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
            $('.edit-btn').on('click', function(){
                $("#editModal input[name='shift_id']").val($(this).data('id'));
                $("#editModal input[name='name']").val($(this).data('name'));
                $("#editModal input[name='start_time']").val($(this).data('start'));
                $("#editModal input[name='end_time']").val($(this).data('end'));
                $("#editModal input[name='grace_in']").val($(this).data('grace-in'));
                $("#editModal input[name='grace_out']").val($(this).data('grace-out'));
            });
        });

        $('#shift-table').DataTable( {
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
                    'targets': [0, 6]
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
                    text: '<i title="delete" class="ti ti-x"></i>',
                    className: 'buttons-delete',
                    action: function ( e, dt, node, config ) {
                        if(user_verified == '1') {
                            shift_id.length = 0;
                            $(':checkbox:checked').each(function(i){
                                if(i){
                                    shift_id[i-1] = $(this).closest('tr').data('id');
                                }
                            });
                            if(shift_id.length && confirm("Are you sure want to delete?")) {
                                $.ajax({
                                    type:'POST',
                                    url:'shift/deletebyselection',
                                    data:{
                                        shiftIdArray: shift_id
                                    },
                                    success:function(data){
                                        alert(data);
                                    }
                                });
                                dt.rows({ page: 'current', selected: true }).remove().draw(false);
                            }
                            else if(!shift_id.length)
                                alert('No shift is selected!');
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

        var date = $('.date');
            date.datepicker({
            format: "dd-mm-yyyy",
            autoclose: true,
            todayHighlight: true
            });

        $('#start_time, #end_time').timepicker({
            'step': 15,
        });
    </script>
@endpush
