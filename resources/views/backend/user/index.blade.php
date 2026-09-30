@extends('backend.layout.main') @section('content')

@push('css')
    @include('backend.layout.partials.datatable_css')
<style>

/* Switch track */
.custom-control-input:checked ~ .custom-control-label::before {
    background-color: #7c5cc4; /* Green when active */
    border-color: #7c5cc4;
}

.custom-control-input:not(:checked) ~ .custom-control-label::before {
    background-color: #ddd; /* Red when inactive */
    border-color: #ddd;
}
</style>
@endpush

<x-toast :toast_notify="session('toast_notify')" />
<x-success-message key="message1" />
<x-success-message key="message2" />
<x-error-message key="message3" />
<x-error-message key="not_permitted" />

<section>
    @if(in_array("users-add", $all_permission))
        <div class="container-fluid">
            <a href="{{route('user.create')}}" class="btn btn-info"><i class="ti ti-plus"></i> {{__('db.Add User')}}</a>
        </div>
    @endif
    <div class="table-responsive">
        <table id="user-table" class="table">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.username')}}</th>
                    <th>{{__('db.email')}}</th>
                    <th>{{__('db.Company Name')}}</th>
                    <th>{{__('db.Phone Number')}}</th>
                    <th>{{__('db.Role')}}</th>
                    <th>{{__('db.status')}}</th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_user_list as $key=>$user)
                <tr data-id="{{$user->id}}">
                    <td>{{$key}}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email}}</td>
                    <td>{{ $user->company_name}}</td>
                    <td>{{ $user->phone}}</td>
                    <?php $role = DB::table('roles')->find($user->role_id);?>
                    <td>{{ $role->name }}</td>
                    <td class="text-center">
                        <div class="custom-control custom-switch">
                            <input type="checkbox"
                                class="custom-control-input user-status-toggle"
                                id="switch_{{ $user->id }}"
                                data-id="{{ $user->id }}"
                                {{ $user->is_active ? 'checked' : '' }}>
                            <label class="custom-control-label" for="switch_{{ $user->id }}"></label>
                        </div>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{__('db.action')}}
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                @if(in_array("users-edit", $all_permission))
                                <li>
                                	<a href="{{ route('user.edit', $user->id) }}" class="btn btn-link"><i class="ti ti-edit"></i> {{__('db.edit')}}</a>
                                </li>
                                @endif
                                <li class="divider"></li>
                                @if(in_array("users-delete", $all_permission))
                                <form action="{{ route('user.destroy', $user->id) }}" method="POST">
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


@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">

    $("ul#people").siblings('a').attr('aria-expanded','true');
    $("ul#people").addClass("show");
    $("ul#people #user-list-menu").addClass("active");

    @if(config('database.connections.saleprosaas_landlord'))
        if(localStorage.getItem("message")) {
            alert(localStorage.getItem("message"));
            localStorage.removeItem("message");
        }
        numberOfUserAccount = <?php echo json_encode($numberOfUserAccount)?>;
        $.ajax({
            type: 'GET',
            async: false,
            url: '{{route("package.fetchData", gen_setting()->package_id)}}',
            success: function(data) {
                if(data['number_of_user_account'] > 0 && data['number_of_user_account'] <= numberOfUserAccount) {
                    $("a.add-user-btn").addClass('d-none');
                }
            }
        });
    @endif

    var user_id = [];
    var user_verified = <?php echo json_encode(config('app.user_verified'))?>;
    var all_permission = <?php echo json_encode($all_permission) ?>;

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

    $('#user-table').DataTable( {
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
                'targets': [0, 7]
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

                    if (user_verified !== '1') {
                        alert('This feature is disable for demo!');
                        return;
                    }

                    let selectedRows = [];

                    $('#user-table tbody input.dt-checkboxes:checked').each(function () {
                        let row = $(this).closest('tr');
                        user_id.push(row.data('id'));
                        selectedRows.push(row);
                    });

                    if (!user_id.length) {
                        alert('No user is selected!');
                        return;
                    }

                    if (!confirm("Are you sure want to delete?")) return;

                    $.ajax({
                        type: 'POST',
                        url: 'user/deletebyselection',
                        data: {
                            userIdArray: user_id
                        },
                        success: function (data) {
                            // ✅ Remove only checked rows
                            selectedRows.forEach(function(row){
                                dt.row(row).remove();
                            });

                            dt.draw(false);
                            alert(data);
                        }
                    });


                }
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="ti ti-eye"></i>',
                columns: ':gt(0)'
            },
        ],
    });

    if(all_permission.indexOf("users-delete") == -1)
        $('.buttons-delete').addClass('d-none');
</script>

<script>
    $(document).on('change', '.user-status-toggle', function() {
        let userId = $(this).data('id');
        let isActive = $(this).is(':checked') ? 1 : 0;

        $.ajax({
            url: "{{ route('user.toggleStatus') }}", // create this route in your controller
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                id: userId,
                is_active: isActive
            },
            success: function(response) {
                var success = '<div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + response.message + '</div>';

                $('#content').prepend(success);
            },
            error: function(xhr) {
                var error = '<div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Someting went wrong</div>';

                $('#content').prepend(error);
            }
        });
    });
</script>

@endpush
