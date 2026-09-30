@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')
    <x-validation-error fieldName="name" />
    <x-validation-error fieldName="warehouse_id" />
    <x-success-message key="message" />
    <x-error-message key="not_permitted" />

    <section>
        <div class="container-fluid">
            <a href="#" data-toggle="modal" data-target="#createModal" class="btn btn-info add-printer-btn"><i
                    class="ti ti-plus"></i> {{ __('db.Add Printer') }}</a>
        </div>
        <div class="table-responsive">
            <table id="printer-table" class="table">
                <thead>
                    <tr>
                        <th class="not-exported"></th>
                        <th>{{ __('db.Printer Name') }}</th>
                        <th>{{ __('db.Warehouse') }}</th>
                        <th>{{ __('db.Connection Type') }}</th>
                        <th>{{ __('db.capability_profile') }}</th>
                        <th>{{ __('db.Characters per line') }}</th>
                        <th>{{ __('db.IP Address') }}</th>
                        <th>{{ __('db.Port') }}</th>
                        <th>{{ __('db.Path') }}</th>
                        <th class="not-exported">{{ __('db.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lims_printer_all as $key => $printer)
                        <tr data-id="{{ $printer->id }}">
                            <td>{{ $key }}</td>
                            <td>{{ $printer->name }}</td>
                            <td>{{ $printer->warehouse->name }}</td>
                            <td>{{ $printer->connection_type_str }}</td>
                            <td>{{ $printer->capability_profile_str }}</td>
                            <td>{{ $printer->char_per_line }}</td>
                            <td>{{ $printer->ip_address }}</td>
                            <td>{{ $printer->port }}</td>
                            <td>{{ $printer->path }}</td>
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
                                            <button type="button" data-id="{{ $printer->id }}"
                                                class="open-EditPrinterDialog btn btn-link" data-toggle="modal"
                                                data-target="#editModal"><i class="ti ti-edit"></i>
                                                {{ __('db.edit') }}
                                            </button>
                                        </li>
                                        <li class="divider"></li>
                                        <form action="{{ route('printers.destroy', $printer->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <li>
                                                <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i
                                                        class="ti ti-trash"></i> {{ __('db.delete') }}</button>
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

    <div id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"
        class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('printers.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 id="exampleModalLabel" class="modal-title">{{ __('db.Add Printer') }}</h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                aria-hidden="true"><i class="ti ti-x"></i></span></button>
                    </div>
                    <div class="modal-body">
                        <p class="italic">
                            <small>{{ __('db.The field labels marked with are required input fields') }}.</small>
                        </p>
                        <p class="text-danger">{{ __('db.When you assign a receipt printer to this warehouse, browser printing will be turned off. Receipts will be printed using the assigned printer, following the template you set in the invoice settings') }}</p>
                        <div class="form-group">
                            <label>{{ __('db.name') }} *</label>
                            <input type="text" placeholder="{{ __('db.Printer Name') }}" name="name"
                                required="required" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>{{ __('db.Warehouse') }} *</label>
                            <select name="warehouse_id" class="form-control selectpicker" id="warehouse_id">
                                @foreach($lims_warehouse_all as $warehouse)
                                <option value="{{$warehouse->id}}" @selected($loop->first)>{{$warehouse->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ __('db.Connection Type') }} *</label>
                            <select name="connection_type" class="form-control selectpicker" id="connection_type">
                                @foreach($connection_types as $key => $connection_type)
                                <option value="{{$key}}" @selected($loop->first)>{{$connection_type}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ __('db.capability_profile') }} *</label> <i class="ti ti-info-circle" data-toggle="tooltip" title="{{__('db.Different printers support different commands and code pages. If you are not sure, it is safest to use the Simple Capability Profile')}}"></i>
                            <select name="capability_profile" class="form-control selectpicker" id="capability_profile">
                                @foreach($capability_profiles as $key => $capability_profile)
                                <option value="{{$key}}" @selected($loop->first)>{{$capability_profile}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ __('db.Characters per line') }} *</label>
                            <input type="number" value="42" placeholder="{{ __('db.Maximum characters printable per line') }}" name="char_per_line"
                                required="required" class="form-control">
                        </div>
                        <div class="form-group field-for-network">
                            <label>{{ __('db.IP Address') }} *</label>
                            <input type="text" placeholder="{{ __('db.Printer IP address') }}" name="ip_address"
                                required="required" class="form-control">
                        </div>
                        <div class="form-group field-for-network">
                            <label>{{ __('db.Port') }} *</label> <i class="ti ti-info-circle" data-toggle="tooltip" title="{{__('db.Most printers use port 9100')}}"></i>
                            <input type="text" value="9100" name="port" required="required" class="form-control">
                        </div>
                        <div class="form-group field-for-win-linux">
                            <label>{{ __('db.Path') }} *</label>
                            <input type="text" name="path" required="required" class="form-control">
                            <span class="help-block">
                                <b>{{ __('db.Windows connection type') }}: </b> {{ __('db.Device files are typically') }}<code>LPT1</code> (parallel) / <code>COM1</code> (serial). <br/>
                                <b>{{ __('db.Linux connection type') }}: </b> {{ __('db.Device files are typically') }} <code>/dev/lp0</code> (parallel), <code>/dev/usb/lp1</code> (USB), <code>/dev/ttyUSB0</code> (USB-Serial), <code>/dev/ttyS0</code> (serial). <br/>
                            </span>
                        </div>
                        <div class="form-group">
                            <input type="submit" value="{{ __('db.submit') }}" class="btn btn-primary">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"
        class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('printers.update', 1) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 id="exampleModalLabel" class="modal-title"> {{ __('db.update_printer') }}</h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                aria-hidden="true"><i class="ti ti-x"></i></span></button>
                    </div>
                    <div class="modal-body">
                        <p class="italic">
                            <small>{{ __('db.The field labels marked with are required input fields') }}.</small></p>
                        <div class="form-group">
                            <input type="hidden" name="printer_id">
                            <label>{{ __('db.name') }} *</label>
                            <input type="text" placeholder="{{ __('db.Printer Name') }}" name="name"
                                required="required" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>{{ __('db.Warehouse') }} *</label>
                            <select name="warehouse_id" class="form-control selectpicker" id="warehouse_id">
                                @foreach($lims_warehouse_all as $warehouse)
                                <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ __('db.Connection Type') }} *</label>
                            <select name="connection_type" class="form-control selectpicker" id="connection_type">
                                @foreach($connection_types as $key => $connection_type)
                                <option value="{{$key}}">{{$connection_type}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ __('db.capability_profile') }} *</label> <i class="ti ti-info-circle" data-toggle="tooltip" title="{{__('db.Different printers support different commands and code pages. If you are not sure, it is safest to use the Simple Capability Profile')}}"></i>
                            <select name="capability_profile" class="form-control selectpicker" id="capability_profile">
                                @foreach($capability_profiles as $key => $capability_profile)
                                <option value="{{$key}}">{{$capability_profile}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ __('db.Characters per line') }} *</label>
                            <input type="number" placeholder="{{ __('db.Maximum characters printable per line') }}" name="char_per_line"
                                required="required" class="form-control">
                        </div>
                        <div class="form-group field-for-network">
                            <label>{{ __('db.IP Address') }} *</label>
                            <input type="text" placeholder="{{ __('db.Printer IP address') }}" name="ip_address"
                                required="required" class="form-control">
                        </div>
                        <div class="form-group field-for-network">
                            <label>{{ __('db.Port') }} *</label> <i class="ti ti-info-circle" data-toggle="tooltip" title="{{__('db.Most printers use port 9100')}}"></i>
                            <input type="text" name="port" required="required" class="form-control">
                        </div>
                        <div class="form-group field-for-win-linux">
                            <label>{{ __('db.Path') }} *</label>
                            <input type="text" name="path" required="required" class="form-control">
                            <span class="help-block">
                                <b>{{ __('db.Windows connection type') }}: </b> {{ __('db.Device files are typically') }}<code>LPT1</code> (parallel) / <code>COM1</code> (serial). <br/>
                                <b>{{ __('db.Linux connection type') }}: </b> {{ __('db.Device files are typically') }} <code>/dev/lp0</code> (parallel), <code>/dev/usb/lp1</code> (USB), <code>/dev/ttyUSB0</code> (USB-Serial), <code>/dev/ttyS0</code> (serial). <br/>
                            </span>
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

        var user_verified = <?php echo json_encode(config('app.user_verified')); ?>;

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

            $(document).on('click', '.open-EditPrinterDialog', function() {
                var url = "printers/"
                var id = $(this).data('id').toString();
                url = url.concat(id).concat("/edit");
                $.get(url, function(data) {
                    $("#editModal input[name='printer_id']").val(data['id']);
                    $("#editModal input[name='name']").val(data['name']);
                    $("#editModal select[name='warehouse_id']").val(data['warehouse_id']).selectpicker('refresh');
                    $("#editModal select[name='connection_type']").val(data['connection_type']).selectpicker('refresh');
                    printer_con_type_field_show_hide(data['connection_type']);
                    $("#editModal select[name='capability_profile']").val(data['capability_profile']).selectpicker('refresh');
                    $("#editModal input[name='char_per_line']").val(data['char_per_line']);
                    $("#editModal input[name='ip_address']").val(data['ip_address']);
                    $("#editModal input[name='port']").val(data['port']);
                    $("#editModal input[name='path']").val(data['path']);
                });
            });
        });

        $('#printer-table').DataTable({
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
            'columnDefs': [{
                    "orderable": false,
                    'targets': [0, 5, 6, 7, 8, 9]
                },
                {
                    'render': function(data, type, row, meta) {
                        if (type === 'display') {
                            data =
                                '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
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
            'lengthMenu': [
                [10, 25, 50, -1],
                [10, 25, 50, "All"]
            ],
            dom: '<"row"lfB>rtip',
            buttons: [{
                    extend: 'pdf',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    },
                },
                {
                    extend: 'excel',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    },
                },
                {
                    extend: 'csv',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    },
                },
                {
                    extend: 'print',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    },
                },
                {
                    text: '<i title="delete" class="ti ti-x"></i>',
                    className: 'buttons-delete',
                    action: function(e, dt, node, config) {
                        if (user_verified == '1') {
                            var printer_id = [];
                            $('table tbody :checkbox:checked').each(function(i) {
                                printer_id[i] = $(this).closest('tr').data('id');
                            });
                            if (printer_id.length && confirm("Are you sure want to delete?")) {
                                $.ajax({
                                    type: 'DELETE',
                                    url: '/printer/0',
                                    data: {
                                        printerIdArray: printer_id
                                    },
                                    success: function(data) {
                                        $(':checkbox:checked').each(function(i) {
                                            if (i) {
                                                dt.row($(this).closest('tr')).remove()
                                                    .draw(false);
                                            }
                                        });
                                        alert(data);
                                    }
                                });
                            } else if (!printer_id.length)
                                alert('No printer is selected!');
                        } else
                            alert('This feature is disable for demo!');
                    }
                },
                {
                    extend: 'colvis',
                    text: '<i title="" class="ti ti-eye"></i>',
                    columns: ':gt(0)'
                },
            ],
        });

        printer_con_type_field_show_hide($('select#connection_type').val());
        $('select#connection_type').change(function() {
            var contype = $(this).val();
            printer_con_type_field_show_hide(contype);
        });

        function printer_con_type_field_show_hide(contype) {
            if (contype == 'network') {
                $('div.field-for-win-linux').addClass('d-none')
                    .find('input').prop('required', false);  // remove required
                $('div.field-for-network').removeClass('d-none')
                    .find('input').prop('required', true);   // add required
            } else if (contype == 'windows' || contype == 'linux') {
                $('div.field-for-network').addClass('d-none')
                    .find('input').prop('required', false);
                $('div.field-for-win-linux').removeClass('d-none')
                    .find('input').prop('required', true);
            }
        }

    </script>
@endpush
