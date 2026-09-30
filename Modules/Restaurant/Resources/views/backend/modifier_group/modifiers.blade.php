@extends('backend.layout.main')
@section('content')

<div id="toast-container" style="position:fixed;top:20px;right:20px;z-index:9999;min-width:280px;"></div>

<section>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">

                <div class="alert alert-info d-flex justify-content-between align-items-center">
                    <div>
                        <strong>{{ $group->name }}</strong> &mdash;
                        {{ $group->selection_type === 'single' ? __('db.Single') : __('db.Multiple') }}
                        &middot; {{ __('db.Min') }}: {{ $group->min_selection }} / {{ __('db.Max') }}: {{ $group->max_selection }}
                        &middot; @if($group->is_required) <span class="badge badge-danger">{{ __('db.Required') }}</span>
                               @else <span class="badge badge-secondary">{{ __('db.Optional') }}</span> @endif
                    </div>
                    <a href="{{ route('restaurant.modifier-group.products', $group->id) }}" class="btn btn-success btn-sm">
                        <i class="ti ti-apps"></i> {{ __('db.Assign to Products') }}
                    </a>
                </div>

                <button class="btn btn-primary mb-3" id="btn-open-add-modifier">
                    <i class="ti ti-plus"></i> {{ __('db.Add Modifier') }}
                </button>

                <div class="table-responsive">
                    <table id="modifiers_table" class="table" style="width:100%">
                        <thead>
                            <tr>
                                <th class="not-exported"></th>
                                <th>#</th>
                                <th>{{ __('db.Name') }}</th>
                                <th>{{ __('db.Price Adjustment') }}</th>
                                <th>{{ __('db.Sort Order') }}</th>
                                <th>{{ __('db.Status') }}</th>
                                <th class="not-exported">{{ __('db.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($modifiers as $i => $modifier)
                            <tr id="modifier-row-{{ $modifier->id }}">
                                <td></td>
                                <td>{{ $i + 1 }}</td>
                                <td><strong>{{ $modifier->name }}</strong></td>
                                <td>{{ number_format((float)$modifier->price_adjustment, 2) }}</td>
                                <td>{{ $modifier->sort_order }}</td>
                                <td>
                                    @if($modifier->is_active)
                                        <span class="badge badge-success">{{ __('db.Active') }}</span>
                                    @else
                                        <span class="badge badge-secondary">{{ __('db.Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="not-exported">
                                    <button class="btn btn-sm btn-primary btn-edit-modifier"
                                        data-id="{{ $modifier->id }}"
                                        data-name="{{ $modifier->name }}"
                                        data-price="{{ $modifier->price_adjustment }}"
                                        data-sort="{{ $modifier->sort_order }}"
                                        data-active="{{ $modifier->is_active }}">
                                        <i class="ti ti-pencil"></i>
                                    </button>&nbsp;
                                    <button class="btn btn-sm btn-danger btn-delete-modifier"
                                        data-id="{{ $modifier->id }}">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</section>

{{-- Add Modal --}}
<div id="addModifierModal" class="modal fade" role="dialog" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-plus"></i> {{ __('db.Add Modifier') }} — {{ $group->name }}</h5>
                <button type="button" data-dismiss="modal" class="close"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>{{ __('db.Modifier Name') }} <span class="text-danger">*</span></label>
                    <input class="form-control" type="text" id="add_modifier_name" placeholder="{{ __('db.size_example') }}" autofocus>
                </div>
                <div class="row">
                    <div class="col-sm-5">
                        <div class="form-group">
                            <label>{{ __('db.Price Adjustment') }}</label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text">+</span></div>
                                <input class="form-control" type="number" step="0.01" id="add_modifier_price" value="0.00" min="0" placeholder="0.00">
                            </div>
                            <small class="text-muted">{{ __('db.Default price for all products') }}</small>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label>{{ __('db.Sort Order') }}</label>
                            <input class="form-control" type="number" id="add_modifier_sort" value="{{ $modifiers->count() }}" min="0">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('db.Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save-add-modifier">
                    <span class="spinner-border spinner-border-sm d-none mr-1" id="add-mod-spinner"></span>
                    <i class="ti ti-device-floppy"></i> {{ __('db.Save') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div id="editModifierModal" class="modal fade" role="dialog" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-pencil"></i> {{ __('db.Edit Modifier') }}</h5>
                <button type="button" data-dismiss="modal" class="close"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_modifier_id">
                <div class="form-group">
                    <label>{{ __('db.Modifier Name') }} <span class="text-danger">*</span></label>
                    <input class="form-control" type="text" id="edit_modifier_name">
                </div>
                <div class="row">
                    <div class="col-sm-5">
                        <div class="form-group">
                            <label>{{ __('db.Price Adjustment') }}</label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text">+</span></div>
                                <input class="form-control" type="number" step="0.01" id="edit_modifier_price" min="0" placeholder="0.00">
                            </div>
                            <small class="text-muted">{{ __('db.Default price for all products') }}</small>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label>{{ __('db.Sort Order') }}</label>
                            <input class="form-control" type="number" id="edit_modifier_sort" min="0">
                        </div>
                    </div>
                    <div class="col-sm-3 d-flex align-items-center">
                        <div class="form-group mt-3">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="edit_modifier_active">
                                <label class="custom-control-label" for="edit_modifier_active">{{ __('db.Active') }}</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('db.Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save-edit-modifier">
                    <span class="spinner-border spinner-border-sm d-none mr-1" id="edit-mod-spinner"></span>
                    <i class="ti ti-device-floppy"></i> {{ __('db.Save') }}
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
@include('backend.layout.partials.datatable_js')
<script>
"use strict";

var groupId    = {{ $group->id }};
var storeUrl   = '{{ route("restaurant.modifier-group.modifier.store", $group->id) }}';
var updateUrl  = '{{ route("restaurant.modifier-group.modifier.update", $group->id) }}';
var deleteBase = '{{ url("restaurant/modifier-group/".$group->id."/modifiers/delete") }}';
var csrfToken  = '{{ csrf_token() }}';

function showToast(type, message) {
    var cls = type === 'success' ? 'alert-success' : 'alert-danger';
    var $t = $('<div class="alert ' + cls + ' alert-dismissible shadow-sm">' +
        '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>' +
        message + '</div>');
    $('#toast-container').append($t);
    setTimeout(function () { $t.fadeOut(400, function () { $(this).remove(); }); }, 3500);
}

var modifiersTable;

$(document).ready(function () {
    modifiersTable = $('#modifiers_table').DataTable({
        order: [[4, 'asc']],
        language: {
            lengthMenu: '_MENU_ {{ __("db.records per page") }}',
            info: '<small>{{ __("db.Showing") }} _START_ - _END_ (_TOTAL_)</small>',
            search: '{{ __("db.Search") }}',
            paginate: { previous: '<i class="ti ti-chevron-left"></i>', next: '<i class="ti ti-chevron-right"></i>' }
        },
        columnDefs: [
            { orderable: false, targets: [0, 6] },
            {
                render: function (data, type, row, meta) {
                    if (type === 'display') data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                    return data;
                },
                checkboxes: { selectRow: true },
                targets: [0]
            }
        ],
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
        dom: '<"row"lfB>rtip',
        buttons: [
            { extend: 'csv',    text: '<i class="ti ti-file-type-csv"></i>' },
            { extend: 'print',  text: '<i class="ti ti-printer"></i>' },
            { extend: 'colvis', text: '<i class="ti ti-eye"></i>', columns: ':gt(0)' }
        ],
    });

    // ── Add ───────────────────────────────────────────────────────────────────
    $('#btn-open-add-modifier').on('click', function () {
        $('#add_modifier_name').val('');
        $('#add_modifier_sort').val(modifiersTable.rows().count());
        $('#addModifierModal').modal('show');
        setTimeout(function () { $('#add_modifier_name').focus(); }, 400);
    });

    $('#btn-save-add-modifier').on('click', function () {
        var name = $('#add_modifier_name').val().trim();
        if (!name) { $('#add_modifier_name').addClass('is-invalid').focus(); return; }
        $('#add_modifier_name').removeClass('is-invalid');

        $('#add-mod-spinner').removeClass('d-none');
        $('#btn-save-add-modifier').prop('disabled', true);

        $.post(storeUrl, { _token: csrfToken, name: name, sort_order: $('#add_modifier_sort').val(), price_adjustment: $('#add_modifier_price').val() })
            .done(function (res) {
                $('#addModifierModal').modal('hide');
                showToast('success', res.message);
                // Add new row to DataTable
                var rowCount = modifiersTable.rows().count() + 1;
                var m = res.modifier;
                modifiersTable.row.add([
                    '',
                    rowCount,
                    '<strong>' + $('<div>').text(m.name).html() + '</strong>',
                    parseFloat(m.price_adjustment || 0).toFixed(2),
                    m.sort_order,
                    '<span class="badge badge-success">{{ __("db.Active") }}</span>',
                    '<button class="btn btn-sm btn-primary btn-edit-modifier" data-id="' + m.id + '" data-name="' + $('<div>').text(m.name).html() + '" data-price="' + (m.price_adjustment || 0) + '" data-sort="' + m.sort_order + '" data-active="1"><i class="ti ti-pencil"></i></button>&nbsp;' +
                    '<button class="btn btn-sm btn-danger btn-delete-modifier" data-id="' + m.id + '"><i class="ti ti-trash"></i></button>'
                ]).draw(false);
            })
            .fail(function (xhr) {
                showToast('error', xhr.responseJSON ? xhr.responseJSON.message : '{{ __("db.An error occurred. Please try again.") }}');
            })
            .always(function () {
                $('#add-mod-spinner').addClass('d-none');
                $('#btn-save-add-modifier').prop('disabled', false);
            });
    });

    // ── Edit ──────────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-edit-modifier', function () {
        var $btn = $(this);
        $('#edit_modifier_id').val($btn.data('id'));
        $('#edit_modifier_name').val($btn.data('name'));
        $('#edit_modifier_price').val(parseFloat($btn.data('price') || 0).toFixed(2));
        $('#edit_modifier_sort').val($btn.data('sort'));
        $('#edit_modifier_active').prop('checked', $btn.data('active') == 1);
        $('#editModifierModal').modal('show');
        setTimeout(function () { $('#edit_modifier_name').focus(); }, 400);
    });

    $('#btn-save-edit-modifier').on('click', function () {
        var name = $('#edit_modifier_name').val().trim();
        if (!name) { $('#edit_modifier_name').addClass('is-invalid').focus(); return; }
        $('#edit_modifier_name').removeClass('is-invalid');

        $('#edit-mod-spinner').removeClass('d-none');
        $('#btn-save-edit-modifier').prop('disabled', true);

        $.post(updateUrl, {
            _token:           csrfToken,
            id:               $('#edit_modifier_id').val(),
            name:             name,
            price_adjustment: $('#edit_modifier_price').val(),
            sort_order:       $('#edit_modifier_sort').val(),
            is_active:        $('#edit_modifier_active').is(':checked') ? 1 : 0,
        })
            .done(function (res) {
                $('#editModifierModal').modal('hide');
                showToast('success', res.message);
                // Reload page to refresh table (DataTable here is static DOM)
                location.reload();
            })
            .fail(function (xhr) {
                showToast('error', xhr.responseJSON ? xhr.responseJSON.message : '{{ __("db.An error occurred. Please try again.") }}');
            })
            .always(function () {
                $('#edit-mod-spinner').addClass('d-none');
                $('#btn-save-edit-modifier').prop('disabled', false);
            });
    });

    // ── Delete ────────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-delete-modifier', function () {
        if (!confirm('{{ __("db.Delete this modifier? Existing orders retain their snapshot data.") }}')) return;
        var id = $(this).data('id');
        var $row = $(this).closest('tr');
        var $btn = $(this).prop('disabled', true);

        $.get(deleteBase + '/' + id)
            .done(function (res) {
                showToast('success', res.message);
                modifiersTable.row($row).remove().draw(false);
            })
            .fail(function (xhr) {
                showToast('error', xhr.responseJSON ? xhr.responseJSON.message : '{{ __("db.Delete failed.") }}');
                $btn.prop('disabled', false);
            });
    });
});
</script>
@endpush
