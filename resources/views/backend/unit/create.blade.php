@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')

<x-validation-error fieldName="unit_code" />
<x-validation-error fieldName="unit_name" />
<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <a href="#" data-toggle="modal" data-target="#createUnitModal" class="btn btn-info"><i class="ti ti-plus"></i> {{__('db.Add Unit')}}</a>&nbsp;
        <a href="#" data-toggle="modal" data-target="#importUnit" class="btn btn-primary"><i class="ti ti-copy"></i> {{__('db.Import Unit')}}</a>
    </div>
    <div class="table-responsive">
        <table id="unit-table" class="table">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.Code')}}</th>
                    <th>{{__('db.name')}}</th>
                    <th>{{__('db.Base Unit')}}</th>
                    <th>{{__('db.Operator')}}</th>
                    <th>{{__('db.Operation Value')}}</th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_unit_list as $key=>$unit)
                <tr data-id="{{$unit->id}}">
                    <td>{{$key}}</td>
                    <td>{{ $unit->unit_code }}</td>
                    <td>{{ $unit->unit_name }}</td>
                    @if($unit->base_unit)
                        <?php $base_unit = DB::table('units')->where('id', $unit->base_unit)->first(); ?>
                        <td>{{ $base_unit->unit_name }}</td>
                    @else
                        <td>N/A</td>
                    @endif
                    @if($unit->operator)
                        <td>{{ $unit->operator }}</td>
                    @else
                        <td>N/A</td>
                    @endif
                    @if($unit->operation_value)
                        <td>{{ $unit->operation_value }}</td>
                    @else
                        <td>N/A</td>
                    @endif
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{__('db.action')}}
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button" data-id="{{$unit->id}}" class="open-EditUnitDialog btn btn-link" data-toggle="modal" data-target="#editModal"><i class="ti ti-edit"></i> {{__('db.edit')}}
                                </button>
                                </li>
                                <li class="divider"></li>
                                <form action="{{ route('unit.destroy', $unit->id) }}" method="POST">
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
<!-- Modal -->



<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('unit.update', 1) }}" method="POST">
          @csrf
          @method('PUT')
          <div class="modal-header">
            <h5 id="exampleModalLabel" class="modal-title"> {{__('db.Update Unit')}}</h5>
            <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
          </div>
          <div class="modal-body">
          <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
            <form>
                <input type="hidden" name="unit_id">
                <div class="form-group">
                <label>{{__('db.Code')}} *</label>
                <input type="text" name="unit_code" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>{{__('db.name')}} *</label>
                    <input type="text" name="unit_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>{{__('db.Base Unit')}}</label>
                    <select class="form-control selectpicker" id="base_unit_edit" name="base_unit">
                        <option value="">No Base Unit</option>
                        @foreach($lims_unit_list as $unit)
                            @if($unit->base_unit==null)
                            <option value="{{$unit->id}}">{{$unit->unit_name}}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="form-group operator">
                    <label>{{ __('db.Operator') }}</label>
                    <select name="operator" class="form-control selectpicker">
                        <option value="">{{ __('Select an operator') }}</option>
                        <option value="*" {{ old('operator') == '*' ? 'selected' : '' }}>*</option>
                        <option value="/" {{ old('operator') == '/' ? 'selected' : '' }}>/</option>
                    </select>
                </div>
                <div class="form-group operation_value">
                    <label>{{__('db.Operation Value')}}</label><input type="number" name="operation_value" placeholder="{{ __('db.Enter operation value') }}" class="form-control" step="any"/>
                </div>
                <div class="form-text text-muted mt-2 mb-4">
                    <strong>Example conversions:</strong><br>
                    1 Dozen = 1<strong>*</strong>12 Piece<br>
                    1 Gram = 1<strong>/</strong>1000 KG
                </div>

                <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
            </form>
        </div>
        </form>
      </div>
    </div>
</div>

<div id="importUnit" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('unit.import') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="modal-header">
            <h5 id="exampleModalLabel" class="modal-title"> {{__('db.Import Unit')}}</h5>
            <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
          </div>
          <div class="modal-body">
            <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
            <p>{{__('db.The correct column order is')}} (unit_code*, unit_name*, base_unit [unit code], operator, operation_value) {{__('db.and you must follow this')}}.</p>
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
                        <a href="sample_file/sample_unit.csv" class="btn btn-info btn-block btn-md"><i class="ti ti-download"></i>  {{__('db.Download')}}</a>
                    </div>
                </div>
            </div>
            <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
        </div>
        </form>
      </div>
    </div>
</div>

@include('backend.unit.add_unit_modal')

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">
    $("ul#setting").siblings('a').attr('aria-expanded','true');
    $("ul#setting").addClass("show");
    $("ul#setting #unit-menu").addClass("active");

    var unit_id = [];
    var user_verified = <?php echo json_encode(config('app.user_verified'))?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $(".operator").hide();
    $(".operation_value").hide();
     function confirmDelete() {
        if (confirm("Are you sure want to delete?")) {
            return true;
        }
        return false;
    }
    $(document).ready(function() {
    $(document).on('click', '.open-EditUnitDialog', function() {
        var url = "unit/"
        var id = $(this).data('id').toString();
        url = url.concat(id).concat("/edit");

        $.get(url, function(data) {
            $("input[name='unit_code']").val(data['unit_code']);
            $("input[name='unit_name']").val(data['unit_name']);
            $("select[name='operator']").val(data['operator']);
            $("input[name='operation_value']").val(data['operation_value']);
            $("input[name='unit_id']").val(data['id']);
            $("#base_unit_edit").val(data['base_unit']);

            if (data['base_unit'] != null) {
                $(".operator").show();
                $(".operation_value").show();
            } else {
                $(".operator").hide();
                $(".operation_value").hide();
            }

            $('.selectpicker').selectpicker('refresh');
        });

    });

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
        var unit = [];
        $(':checkbox:checked').each(function(i){
          unit[i] = $(this).val();
        });
        $.ajax({
           type:'POST',
           url:'/exportunit',
           data:{

                unitArray: unit
            },
           success:function(data){
            alert('Exported to CSV file successfully! Click Ok to download file');
            window.location.href = data;
           }
        });
    });

    $('.open-CreateUnitDialog').on('click', function() {
        $(".operator").hide();
        $(".operation_value").hide();

    });

    $('#base_unit_create').on('change', function() {
        if($(this).val()){
            $("#createUnitModal .operator").attr('required',true).show();
            $("#createUnitModal .operation_value").show();
        }
        else{
            $("#createUnitModal .operator").hide();
            $("#createUnitModal .operation_value").hide();
        }
    });

    $('#base_unit_edit').on('change', function() {
        if($(this).val()){
            $("#editModal .operator").show();
            $("#editModal .operation_value").show();
        }
        else{
            $("#editModal .operator").hide();
            $("#editModal .operation_value").hide();
        }
    });
});

    $('#unit-table').DataTable( {
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
                        unit_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                unit_id[i-1] = $(this).closest('tr').data('id');
                            }
                        });
                        if(unit_id.length && confirm("Are you sure want to delete?")) {
                            $.ajax({
                                type:'POST',
                                url:'unit/deletebyselection',
                                data:{
                                    unitIdArray: unit_id
                                },
                                success:function(data){
                                    alert(data);
                                }
                            });
                            dt.rows({ page: 'current', selected: true }).remove().draw(false);
                        }
                        else if(!unit_id.length)
                            alert('No unit is selected!');
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
</script>
@endpush
