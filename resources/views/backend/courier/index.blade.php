@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')
    <x-success-message key="message" />
    <x-error-message key="not_permitted" />

    <section>
        <div class="container-fluid">
            <button class="btn btn-info" data-toggle="modal" data-target="#create-modal">
                <i class="ti ti-plus"></i> {{ __('db.Add Courier') }}
            </button>
        </div>
        <div class="table-responsive">
            <table id="courier-table" class="table" style="width: 100%">
                <thead>
                    <tr>
                        <th class="not-exported"></th>
                        <th>{{ __('db.name') }}</th>
                        <th>{{ __('db.Type') }}</th>
                        <th>{{ __('db.Phone Number') }}</th>
                        <th>{{ __('db.Address') }}</th>
                        <th class="not-exported">{{ __('db.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lims_courier_all as $key => $courier)
                        <tr data-id="{{ $courier->id }}">
                            <td>{{ $key }}</td>
                            <td>{{ $courier->name }}</td>
                            <td>{{ ucfirst($courier->type ?? 'N/A') }}</td>
                            <td>{{ $courier->phone_number }}</td>
                            <td>{{ $courier->address }}</td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-default btn-sm dropdown-toggle"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        {{ __('db.action') }} <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default"
                                        user="menu">
                                        <li>
                                            <button type="button"
                                                onclick="editCourier({{ $courier->makeVisible(['api_key', 'secret_key', 'client_id', 'client_secret', 'username', 'password', 'base_url', 'api_token'])->toJson() }})"
                                                class="btn btn-link">
                                                <i class="ti ti-edit"></i> {{ __('db.edit') }}
                                            </button>
                                        </li>
                                        <form action="{{ route('couriers.destroy', $courier->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <li>
                                                <button type="submit" class="btn btn-link"
                                                    onclick="return confirmDelete()">
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
                <tfoot class="tfoot active">
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tfoot>
            </table>
        </div>
    </section>

    {{-- ======================== CREATE MODAL ======================== --}}
    <div id="create-modal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('db.Add Courier') }}</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                        <span aria-hidden="true"><i class="ti ti-x"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="italic">
                        <small>{{ __('db.The field labels marked with are required input fields') }}.</small>
                    </p>
                    <form action="{{ route('couriers.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>{{ __('db.name') }} *</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('db.courier_type') }} *</label>
                                <select name="type" id="courier_type" class="form-control" required>
                                    <option value="">-- {{ __('db.select_type') }} --</option>
                                    <option value="steadfast">Steadfast</option>
                                    <option value="pathao">Pathao</option>
                                    <option value="redx">Redx</option>
                                    <option value="paperfly">Paperfly</option>
                                    <option value="other">{{ __('db.Other') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('db.Phone Number') }}</label>
                                <input type="text" name="phone_number" class="form-control">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('db.Address') }}</label>
                                <input type="text" name="address" class="form-control">
                            </div>

                            {{-- STEADFAST --}}
                            <div class="col-md-12 courier-fields" id="steadfast_fields" style="display: none;">
                                <hr>
                                <h6 class="text-primary mb-3">🚚 {{ __('db.steadfast_api_settings') }}</h6>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>{{ __('db.API Key') }} *</label>
                                        <input type="text" name="api_key" class="form-control"
                                            placeholder="{{ __('db.steadfast_api_key') }}">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>{{ __('db.Secret Key') }} *</label>
                                        <input type="text" name="secret_key" class="form-control"
                                            placeholder="{{ __('db.steadfast_secret_key') }}">
                                    </div>
                                </div>
                            </div>

                            {{-- PATHAO --}}
                            <div class="col-md-12 courier-fields" id="pathao_fields" style="display: none;">
                                <hr>
                                <h6 class="text-primary mb-3">🛵 {{ __('db.pathao_api_settings') }}</h6>
                                <div class="row">
                                    <div class="col-md-12 form-group">
                                        <label>{{ __('db.base_url') }} *</label>
                                        <input type="text" name="base_url" class="form-control"
                                            placeholder="{{ __('db.courier_api_url_example') }}">
                                        <small class="text-muted">
                                            {{ __('db.sandbox') }}: <code>https://courier-api-sandbox.pathao.com</code> |
                                            {{ __('db.production') }}: <code>https://api-hermes.pathao.com</code>
                                        </small>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>{{ __('db.client_id') }} *</label>
                                        <input type="text" name="client_id" class="form-control">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>{{ __('db.client_secret') }} *</label>
                                        <input type="text" name="client_secret" class="form-control">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>{{ __('db.username') }} *</label>
                                        <input type="text" name="pathao_username" class="form-control">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>{{ __('db.Password') }} *</label>
                                        <input type="password" name="pathao_password" class="form-control">
                                    </div>
                                </div>
                            </div>

                            {{-- REDX --}}
                            <div class="col-md-12 courier-fields" id="redx_fields" style="display: none;">
                                <hr>
                                <h6 class="text-primary mb-3">📦 {{ __('db.redx_api_settings') }}</h6>
                                <div class="row">
                                    <div class="col-md-12 form-group">
                                        <label>{{ __('db.api_token') }} *</label>
                                        <input type="text" name="api_token" class="form-control"
                                            placeholder="{{ __('db.redx_api_token') }}">
                                    </div>
                                </div>
                            </div>

                            {{-- PAPERFLY --}}
                            <div class="col-md-12 courier-fields" id="paperfly_fields" style="display: none;">
                                <hr>
                                <h6 class="text-primary mb-3">✈️ {{ __('db.paperfly_api_settings') }}</h6>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>{{ __('db.username') }} *</label>
                                        <input type="text" name="paperfly_username" class="form-control">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>{{ __('db.Password') }} *</label>
                                        <input type="password" name="paperfly_password" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="is_active" value="1">
                        </div>
                        <div class="form-group mt-2">
                            <button type="submit" class="btn btn-primary">{{ __('db.submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================== EDIT MODAL ======================== --}}
    <div id="editModal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('db.Update Courier') }}</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                        <span aria-hidden="true"><i class="ti ti-x"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="italic">
                        <small>{{ __('db.The field labels marked with are required input fields') }}.</small>
                    </p>
                    <form action="{{ route('couriers.update', 1) }}" method="POST" id="edit-form">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>{{ __('db.name') }} *</label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('db.courier_type') }} *</label>
                                <select name="type" id="edit_courier_type" class="form-control" required>
                                    <option value="">-- {{ __('db.select_type') }} --</option>
                                    <option value="steadfast">Steadfast</option>
                                    <option value="pathao">Pathao</option>
                                    {{-- <option value="redx">Redx</option>
                                <option value="paperfly">Paperfly</option>
                                <option value="other">{{ __('db.Other') }}</option> --}}
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('db.Phone Number') }}</label>
                                <input type="text" name="phone_number" id="edit_phone_number" class="form-control">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('db.Address') }}</label>
                                <input type="text" name="address" id="edit_address" class="form-control">
                            </div>

                            {{-- STEADFAST --}}
                            <div class="col-md-12 edit-courier-fields" id="edit_steadfast_fields" style="display: none;">
                                <hr>
                                <h6 class="text-primary mb-3">🚚 {{ __('db.steadfast_api_settings') }}</h6>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>{{ __('db.API Key') }}</label>
                                        <input type="text" name="api_key" id="edit_api_key" class="form-control">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>{{ __('db.Secret Key') }}</label>
                                        <input type="text" name="secret_key" id="edit_secret_key"
                                            class="form-control">
                                    </div>
                                </div>
                            </div>

                            {{-- PATHAO --}}
                            <div class="col-md-12 edit-courier-fields" id="edit_pathao_fields" style="display: none;">
                                <hr>
                                <h6 class="text-primary mb-3">🛵 {{ __('db.pathao_api_settings') }}</h6>
                                <div class="row">
                                    <div class="col-md-12 form-group">
                                        <label>{{ __('db.base_url') }}</label>
                                        <input type="text" name="base_url" id="edit_base_url" class="form-control"
                                            placeholder="{{ __('db.courier_api_url_example') }}">
                                        <small class="text-muted">
                                            {{ __('db.sandbox') }}: <code>https://courier-api-sandbox.pathao.com</code> |
                                            {{ __('db.production') }}: <code>https://api-hermes.pathao.com</code>
                                        </small>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>{{ __('db.client_id') }}</label>
                                        <input type="text" name="client_id" id="edit_client_id" class="form-control">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>{{ __('db.client_secret') }}</label>
                                        <input type="text" name="client_secret" id="edit_client_secret"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>{{ __('db.username') }}</label>
                                        <input type="text" name="pathao_username" id="edit_username"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>{{ __('db.Password') }}</label>
                                        <input type="password" name="pathao_password" id="edit_password"
                                            class="form-control">
                                    </div>
                                </div>
                            </div>

                            {{-- REDX --}}
                            <div class="col-md-12 edit-courier-fields" id="edit_redx_fields" style="display: none;">
                                <hr>
                                <h6 class="text-primary mb-3">📦 {{ __('db.redx_api_settings') }}</h6>
                                <div class="row">
                                    <div class="col-md-12 form-group">
                                        <label>{{ __('db.api_token') }}</label>
                                        <input type="text" name="api_token" id="edit_api_token" class="form-control">
                                    </div>
                                </div>
                            </div>

                            {{-- PAPERFLY --}}
                            <div class="col-md-12 edit-courier-fields" id="edit_paperfly_fields" style="display: none;">
                                <hr>
                                <h6 class="text-primary mb-3">✈️ {{ __('db.paperfly_api_settings') }}</h6>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>{{ __('db.username') }}</label>
                                        <input type="text" name="paperfly_username" id="edit_pf_username"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>{{ __('db.Password') }}</label>
                                        <input type="password" name="paperfly_password" id="edit_pf_password"
                                            class="form-control">
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="id" id="edit_id">
                        </div>
                        <div class="form-group mt-2">
                            <button type="submit" class="btn btn-primary">{{ __('db.submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
    <script type="text/javascript">
        $("ul#sale").siblings('a').attr('aria-expanded', 'true');
        $("ul#sale").addClass("show");
        $("ul#sale #courier-menu").addClass("active");

        document.getElementById('courier_type').addEventListener('change', function() {
            document.querySelectorAll('.courier-fields').forEach(el => el.style.display = 'none');
            const target = document.getElementById(this.value + '_fields');
            if (target) target.style.display = 'block';
        });

        document.getElementById('edit_courier_type').addEventListener('change', function() {
            document.querySelectorAll('.edit-courier-fields').forEach(el => el.style.display = 'none');
            const target = document.getElementById('edit_' + this.value + '_fields');
            if (target) target.style.display = 'block';
        });

        function editCourier(data) {
            document.getElementById('edit-form').action = "{{ url('couriers') }}/" + data.id;
            document.getElementById('edit_id').value = data.id ?? '';
            document.getElementById('edit_name').value = data.name ?? '';
            document.getElementById('edit_phone_number').value = data.phone_number ?? '';
            document.getElementById('edit_address').value = data.address ?? '';

            const knownTypes = ['steadfast', 'pathao', 'redx', 'paperfly', 'other'];
            let resolvedType = '';
            if (data.type && knownTypes.includes(data.type.toLowerCase())) {
                resolvedType = data.type.toLowerCase();
            } else if (data.name) {
                const nameLower = data.name.toLowerCase();
                resolvedType = knownTypes.find(t => nameLower.includes(t)) ?? '';
            }

            const typeSelect = document.getElementById('edit_courier_type');
            typeSelect.value = resolvedType;
            typeSelect.dispatchEvent(new Event('change'));

            ['edit_api_key', 'edit_secret_key', 'edit_client_id', 'edit_client_secret',
                'edit_username', 'edit_password', 'edit_base_url', 'edit_api_token',
                'edit_pf_username', 'edit_pf_password'
            ].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });

            if (resolvedType === 'steadfast') {
                document.getElementById('edit_api_key').value = data.api_key ?? '';
                document.getElementById('edit_secret_key').value = data.secret_key ?? '';
            }
            if (resolvedType === 'pathao') {
                document.getElementById('edit_base_url').value = data.base_url ?? '';
                document.getElementById('edit_client_id').value = data.client_id ?? '';
                document.getElementById('edit_client_secret').value = data.client_secret ?? '';
                document.getElementById('edit_username').value = data.username ?? '';
                document.getElementById('edit_password').value = data.password ?? '';
            }
            if (resolvedType === 'redx') {
                document.getElementById('edit_api_token').value = data.api_token ?? '';
            }
            if (resolvedType === 'paperfly') {
                document.getElementById('edit_pf_username').value = data.username ?? '';
                document.getElementById('edit_pf_password').value = data.password ?? '';
            }

            $('#editModal').modal('show');
        }

        function confirmDelete() {
            return confirm("{{ __('db.delete_confirmation') }}");
        }

        var courier_id = [];
        var table = $('#courier-table').DataTable({
            responsive: true,
            fixedHeader: {
                header: true,
                footer: true
            },
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
                    'targets': [0, 2, 3]
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
                [10, 25, 50, "{{ __('db.All') }}"]
            ],
            dom: '<"row"lfB>rtip',
            buttons: [{
                    extend: 'pdf',
                    text: '<i title="{{ __('db.export_pdf') }}" class="ti ti-file-type-pdf"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    }
                },
                {
                    extend: 'excel',
                    text: '<i title="{{ __('db.export_excel') }}" class="ti ti-file-type-xls"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    }
                },
                {
                    extend: 'csv',
                    text: '<i title="{{ __('db.export_csv') }}" class="ti ti-file-type-csv"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    }
                },
                {
                    extend: 'print',
                    text: '<i title="{{ __('db.print') }}" class="ti ti-printer"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    }
                },
                {
                    text: '<i title="{{ __('db.delete') }}" class="ti ti-x"></i>',
                    className: 'buttons-delete',
                    action: function(e, dt, node, config) {
                        if (user_verified == '1') {
                            courier_id.length = 0;
                            $(':checkbox:checked').each(function(i) {
                                if (i) courier_id[i - 1] = $(this).closest('tr').data('id');
                            });
                            if (courier_id.length && confirm(
                                "{{ __('db.delete_confirmation') }}")) {
                                $.ajax({
                                    type: 'POST',
                                    url: 'couriers/deletebyselection',
                                    data: {
                                        courierIdArray: courier_id,
                                        _token: '{{ csrf_token() }}'
                                    },
                                    success: function(data) {
                                        alert(data);
                                        location.reload();
                                    }
                                });
                                dt.rows({
                                    page: 'current',
                                    selected: true
                                }).remove().draw(false);
                            } else if (!courier_id.length) {
                                alert('{{ __('db.no_courier_selected') }}');
                            }
                        } else {
                            alert('{{ __('db.This feature is disable for demo!') }}');
                        }
                    }
                },
                {
                    extend: 'colvis',
                    text: '<i title="{{ __('db.column_visibility') }}" class="ti ti-eye"></i>',
                    columns: ':gt(0)'
                },
            ]
        });
    </script>
@endpush
