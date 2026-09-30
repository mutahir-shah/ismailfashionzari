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
        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#createModal">
            <i class="ti ti-plus"></i> {{__('db.Add Leave Type')}}
        </button>
    </div>
    <div class="table-responsive">
        <table id="leave-type-table" class="table">
            <thead>
                <tr>
                    <th class="not-exported"> </th>
                    <th>{{__('db.name')}}</th>
                    <th>{{__('db.Annual Quota')}} </th>
                    <th>{{__('db.paid_or_unpaid')}}</th>
                    <th>{{__('db.Carry Forward Limit')}} </th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($leaveTypes as $key=>$type)
                <tr data-id="{{$type->id}}">
                    <td>{{$key+1}}</td>
                    <td>{{$type->name}}</td>
                    <td>{{$type->annual_quota}}</td>
                    <td>{{ $type->encashable ? 'Yes' : 'No' }}</td>
                    <td>{{$type->carry_forward_limit}}</td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                {{__('db.action')}} <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default">
                                <li>
                                    <button type="button"
                                        data-id="{{$type->id}}"
                                        data-name="{{$type->name}}"
                                        data-annual_quota="{{$type->annual_quota}}"
                                        data-encashable="{{$type->encashable}}"
                                        data-carry_forward_limit="{{$type->carry_forward_limit}}"
                                        class="edit-btn btn btn-link"
                                        data-toggle="modal" data-target="#editModal" >
                                        <i class="ti ti-edit"></i> {{__('db.edit')}}
                                    </button>
                                </li>
                                <li class="divider"></li>
                                <form action="{{ route('leave-type.destroy', $type->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <li>
                                        <button type="submit" class="btn btn-link" onclick="return confirmDelete()">
                                            <i class="ti ti-trash"></i> {{__('db.delete')}}
                                        </button>
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
<div id="createModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('leave-type.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5>{{__('db.Add Leave Type')}}</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">

                <div class="form-group">
                    <label>{{__('db.name')}} <x-info title="Example: Casual Leave, Sick Leave, Earned Leave" /></label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>{{__('db.Annual Quota')}} <x-info title="Total number of leave days allowed for this type per year" /></label>
                    <input type="number" name="annual_quota" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>{{__('db.paid_or_unpaid')}} <x-info title="Select Yes if paid leave, No if unpaid" /></label>
                    <select name="encashable" class="form-control" required>
                        <option value="">—</option>
                        <option value="1">{{__('db.Yes')}}</option>
                        <option value="0">{{__('db.No')}}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>{{__('db.Carry Forward Limit')}} <x-info title="Maximum number of days that can be carried forward to next year" /></label>
                    <input type="number" name="carry_forward_limit" class="form-control" required>
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
<div id="editModal" class="modal fade">
  <div class="modal-dialog">
    <div class="modal-content">
        <form action="{{ route('leave-type.update', 1) }}" method="POST" id="editForm">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5>{{__('db.Update Leave Type')}}</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">

                <div class="form-group">
                    <label>{{__('db.name')}} <x-info title="Enter new name to update leave type" /></label>
                    <input type="text" name="name" class="form-control" id="edit_name" required>
                </div>

                <div class="form-group">
                    <label>{{__('db.Annual Quota')}} <x-info title="Update the annual quota for this leave type" /></label>
                    <input type="number" name="annual_quota" class="form-control" id="edit_annual_quota" required>
                </div>

                <div class="form-group">
                    <label>{{__('db.paid_or_unpaid')}} <x-info title="Yes = Paid leave, No = Unpaid leave" /></label>
                    <select name="encashable" class="form-control" id="edit_encashable" required>
                        <option value="">—</option>
                        <option value="1">{{__('db.Yes')}}</option>
                        <option value="0">{{__('db.No')}}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>{{__('db.Carry Forward Limit')}} <x-info title="Update maximum carry forward days" /></label>
                    <input type="number" name="carry_forward_limit" class="form-control" id="edit_carry_forward_limit" required>
                </div>

                <input type="hidden" name="leave_types" id="edit_id">
                <div class="form-group">
                    <input type="submit" value="{{__('db.update')}}" class="btn btn-primary">
                </div>
            </div>
        </form>
    </div>
  </div>
</div>

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">
    $("ul#hrm").siblings('a').attr('aria-expanded','true');
    $("ul#hrm").addClass("show");
    $("ul#hrm #leave-type-menu").addClass("active");

    var leave_types = [];
    var user_verified = <?php echo json_encode(config('app.user_verified'))?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function confirmDelete() {
      return confirm("Are you sure want to delete?");
    }

    $(document).ready(function() {
        $('.edit-btn').on('click', function(){
            $('#edit_id').val($(this).data('id'));
            $('#edit_name').val($(this).data('name'));
            $('#edit_annual_quota').val($(this).data('annual_quota'));
            $('#edit_encashable').val($(this).data('encashable'));
            $('#edit_carry_forward_limit').val($(this).data('carry_forward_limit'));
            $('#editForm').attr('action','/leave-type/'+$(this).data('id'));
        });
    });

    $('#leave-type-table').DataTable({
        "order": [],
        'language': {
            'lengthMenu': '_MENU_ {{__("db.records per page")}}',
            "info": '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search": '{{__("db.Search")}}',
            'paginate': {
                'previous': '<i class="ti ti-chevron-left"></i>',
                'next': '<i class="ti ti-chevron-right"></i>'
            }
        },
        'columnDefs': [
            { "orderable": false, 'targets': [0, 5] },
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
        'lengthMenu': [[10,25,50,-1],[10,25,50,"All"]],
        dom: '<"row"lfB>rtip',
        buttons:[
            { extend:'pdf', text:'<i class="ti ti-file-type-pdf"></i>', exportOptions:{columns:':visible:Not(.not-exported)',rows:':visible'}, footer:true },
            { extend:'excel', text:'<i class="ti ti-file-type-xls"></i>', exportOptions:{columns:':visible:Not(.not-exported)',rows:':visible'}, footer:true },
            { extend:'csv', text:'<i class="ti ti-file-type-csv"></i>', exportOptions:{columns:':visible:Not(.not-exported)',rows:':visible'}, footer:true },
            { extend:'print', text:'<i class="ti ti-printer"></i>', exportOptions:{columns:':visible:Not(.not-exported)',rows:':visible'}, footer:true },
            {
                text:'<i class="ti ti-x"></i>',
                className:'buttons-delete',
                action: function(e, dt, node, config){
                    if(user_verified=='1'){
                        leave_types.length=0;
                        $(':checkbox:checked').each(function(i){
                            if(i) leave_types[i-1]=$(this).closest('tr').data('id');
                        });
                        if(leave_types.length && confirm("Are you sure want to delete?")){
                            $.ajax({
                                type:'POST',
                                url:'leave-type/deletebyselection',
                                data:{leaveTypeIdArray:leave_types},
                                success:function(data){ alert(data); }
                            });
                            dt.rows({ page:'current', selected:true }).remove().draw(false);
                        } else if(!leave_types.length) alert('No Leave Type selected!');
                    } else alert('This feature is disabled for demo!');
                }
            },
            { extend:'colvis', text:'<i class="ti ti-eye"></i>', columns:':gt(0)'}
        ]
    });
</script>
@endpush
