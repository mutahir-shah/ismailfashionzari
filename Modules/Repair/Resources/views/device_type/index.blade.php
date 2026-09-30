@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')

    <x-validation-error fieldName="name" />
    <x-validation-error fieldName="category" />
    <x-success-message key="message" />
    <x-error-message key="not_permitted" />

    <section>
        <div class="container-fluid">
            <button class="btn btn-info" data-toggle="modal" data-target="#createModal"><i class="ti ti-plus"></i>
                {{ __('db.Add Type') }} </button>
        </div>
        <div class="table-responsive">
            <table id="biller-table" class="table">
                <thead>
                    <tr>
                        <th class="not-exported"></th>
                        <th>{{ __('db.name') }}</th>
                        <th>{{ __('db.category') }}</th>
                        <th>{{ __('db.Description') }}</th>
                        <th class="not-exported">{{ __('db.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lims_device_type_all as $key => $device_type)
                        <tr data-id="{{ $device_type->id }}">
                            <td>{{ $key }}</td>
                            <td>{{ $device_type->name }}</td>
                            <td>
                                <span class="badge badge-{{ $device_type->category === 'device' ? 'info' : 'warning' }}">
                                    {{ ucfirst($device_type->category) }}
                                </span>
                            </td>
                            <td>{{ $device_type->description ?? '-' }}</td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-default btn-sm dropdown-toggle"
                                        data-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false">{{ __('db.action') }}
                                        <span class="caret"></span>
                                        <span class="sr-only">Toggle Dropdown</span>
                                    </button>
                                    <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default"
                                        user="menu">
                                        <li>
                                            <button type="button" data-id="{{ $device_type->id }}"
                                                class="open-EditDeviceTypeDialog btn btn-link" data-toggle="modal"
                                                data-target="#editModal">
                                                <i class="ti ti-edit"></i>
                                                {{ __('db.edit') }}
                                            </button>
                                        </li>
                                        <li class="divider"></li>
                                        <form action="{{ route('repair.device-types.destroy', $device_type->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <li>
                                                <button type="submit" class="btn btn-link"
                                                    onclick="return confirm('Are you sure want to delete?')">
                                                    <i class="ti ti-trash"></i> {{ __('db.delete') }}
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

    {{-- Create Modal --}}
    <div id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true"
        class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('repair.device-types.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 id="createModalLabel" class="modal-title">{{ __('db.Add Device Type') }}</h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                            <span aria-hidden="true"><i class="ti ti-x"></i></span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="italic">
                            <small>{{ __('db.required_fields_notice') }}</small>
                        </p>
                        <div class="form-group">
                            <label>{{ __('db.name') }} *</label>
                            <input type="text" name="name" required class="form-control"
                                placeholder="{{ __('db.Type device type name') }}">
                        </div>
                        <div class="form-group">
                            <label>{{ __('db.category') }} *</label>
                            <select name="category" required class="form-control">
                                <option value="">{{ __('db.Select Category') }}</option>
                                <option value="device">{{ __('db.device') }}</option>
                                <option value="vehicle">{{ __('db.vehicle') }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ __('db.Description') }}</label>
                            <input type="text" name="description" class="form-control"
                                placeholder="{{ __('db.Type description') }}">
                        </div>
                        <div class="form-group">
                            <input type="submit" value="{{ __('db.submit') }}" class="btn btn-primary">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true"
        class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('repair.device-types.update', 1) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 id="editModalLabel" class="modal-title">{{ __('db.update_device_type') }}</h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                            <span aria-hidden="true"><i class="ti ti-x"></i></span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="italic">
                            <small>{{ __('db.required_fields_notice') }}</small>
                        </p>
                        <div class="form-group">
                            <label>{{ __('db.name') }} *</label>
                            <input type="text" name="name" required class="form-control">
                        </div>
                        <input type="hidden" name="device_type_id">
                        <div class="form-group">
                            <label>{{ __('db.category') }} *</label>
                            <select name="category" required class="form-control selectpicker">
                                <option value="device">{{ __('db.device') }}</option>
                                <option value="vehicle">{{ __('db.vehicle') }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ __('db.Description') }}</label>
                            <input type="text" name="description" class="form-control"
                                placeholder="{{ __('db.Type description') }}">
                        </div>
                        <div class="form-group">
                            <input type="submit" value="{{ __('db.submit') }}" class="btn btn-primary">
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
        var device_type_id = [];
        var user_verified = <?php echo json_encode(config('app.user_verified')); ?>;

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).ready(function() {
            $(document).on('click', '.open-EditDeviceTypeDialog', function() {
                var id  = $(this).data('id').toString();
                var url = "{{ route('repair.device-types.edit', ':id') }}".replace(':id', id);

                $.get(url, function(data) {
                    $("#editModal input[name='name']").val(data['name']);
                    $("#editModal input[name='device_type_id']").val(data['id']);
                    $("#editModal input[name='description']").val(data['description']);
                    $("#editModal select[name='category']").val(data['category']);
                    if ($('.selectpicker').length) {
                        $('.selectpicker').selectpicker('refresh');
                    }
                });
            });
        });

        $('#biller-table').DataTable({
            "order": [],
            'language': {
                'lengthMenu': '_MENU_ {{ __('db.records per page') }}',
                "info": '<small>{{ __('db.Showing') }} _START_ - _END_ (_TOTAL_)</small>',
                "search": '{{ __('db.Search') }}',
                'paginate': {
                    'previous': '<i class="ti ti-chevron-left"></i>',
                    'next': '<i class="ti ti-chevron-right"></i>'
                }
            },
            'columnDefs': [
                {
                    "orderable": false,
                    'targets': [0, 4]
                },
                {
                    'render': function(data, type, row, meta) {
                        if (type === 'display') {
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
                style: 'multi',
                selector: 'td:first-child'
            },
            'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
            dom: '<"row"lfB>rtip',
            buttons: [
                {
                    extend: 'pdf',
                    text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                    exportOptions: { columns: ':visible:Not(.not-exported)', rows: ':visible', stripHtml: false },
                },
                {
                    extend: 'excel',
                    text: '<i title="export to excel" class="ti ti-file-type-xls"></i>',
                    exportOptions: { columns: ':visible:Not(.not-exported)', rows: ':visible' },
                },
                {
                    extend: 'csv',
                    text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                    exportOptions: { columns: ':visible:Not(.not-exported)', rows: ':visible' },
                },
                {
                    extend: 'print',
                    text: '<i title="print" class="ti ti-printer"></i>',
                    exportOptions: { columns: ':visible:Not(.not-exported)', rows: ':visible', stripHtml: false },
                },
                {
                    text: '<i title="delete" class="ti ti-x"></i>',
                    className: 'buttons-delete',
                    action: function(e, dt, node, config) {
                        if (user_verified == '1') {
                            device_type_id.length = 0;
                            $(':checkbox:checked').each(function(i) {
                                if (i) {
                                    device_type_id[i - 1] = $(this).closest('tr').data('id');
                                }
                            });
                            if (device_type_id.length && confirm("Are you sure want to delete?")) {
                                $.ajax({
                                    type: 'POST',
                                    url: '{{ route('repair.device-types.deletebyselection') }}',
                                    data: { deviceTypeIdArray: device_type_id },
                                    success: function(data) {
                                        $(':checkbox:checked').each(function(i) {
                                            if (i) {
                                                dt.row($(this).closest('tr')).remove().draw(false);
                                            }
                                        });
                                        alert(data);
                                    }
                                });
                            } else if (!device_type_id.length) {
                                alert('No device type is selected!');
                            }
                        } else {
                            alert('This feature is disable for demo!');
                        }
                    }
                },
                {
                    extend: 'colvis',
                    text: '<i title="column visibility" class="ti ti-eye"></i>',
                    columns: ':gt(0)'
                },
            ],
        });
    </script>
@endpush
