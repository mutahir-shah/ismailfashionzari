@php
if (config('database.connections.saleprosaas_landlord') && !tenant()) {
    $layout = 'landlord.layout.main';
    $routePrefix = 'superadminSetting.';
} else {
    $layout = 'backend.layout.main';
    $routePrefix = 'setting.';
}
@endphp

@extends($layout)
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush


@section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />
<x-error-message key="name" />

<section>
    <div class="container-fluid">
        <a href="{{ route($routePrefix . 'themeSettings.create') }}" class="btn btn-info"><i class="ti ti-plus"></i> Add Theme Setting</a>
    </div>

    <div class="table-responsive">
        <table id="theme-setting-table" class="table">
            <thead>
            <tr>
                <th class="not-exported"></th>
                <th>Name</th>
                <th>Appearance</th>
                <th>Primary Color</th>
                <th>Font</th>
                <th>Active For</th>
                <th>Status</th>
                <th class="not-exported">Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach($themeSettings as $key => $themeSetting)
                @php($activeFor = $themeSetting->active_for ?? [])
                <tr data-id="{{ $themeSetting->id }}" data-active_for='@json($activeFor)'>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $themeSetting->name }}</td>
                    <td>{{ $themeSetting->theme_appearance }}</td>
                    <td>
                        <span style="display:inline-block;width:16px;height:16px;border-radius:3px;background:{{ $themeSetting->theme_color }};border:1px solid #ddd;vertical-align:middle;"></span>
                        <span class="ml-1">{{ $themeSetting->theme_color }}</span>
                    </td>
                    <td>{{ $themeSetting->font_family }}</td>
                    <td class="active-for-cell">
                        @if(empty($activeFor))
                            <span class="text-muted">—</span>
                        @else
                            @foreach($activeFor as $tag)
                                <span class="badge badge-primary mr-1">{{ strtoupper($tag) }}</span>
                            @endforeach
                        @endif
                    </td>
                    <td>
                        @if($themeSetting->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-danger">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{ __('db.action') }}
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>

                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button"
                                            class="btn btn-link active-for-btn"
                                            data-id="{{ $themeSetting->id }}"
                                            data-name="{{ $themeSetting->name }}"
                                            data-active_for='@json($activeFor)'
                                            data-url="{{ route($routePrefix . 'themeSettings.activeFor', $themeSetting) }}"
                                            data-toggle="modal"
                                            data-target="#activeForModal">
                                        <i class="ti ti-checkmark"></i> Active
                                    </button>
                                </li>
                                <li>
                                    <a class="btn btn-link" href="{{ route($routePrefix . 'themeSettings.edit', $themeSetting) }}"><i class="ti ti-edit"></i> Edit</a>
                                </li>
                                <li class="divider"></li>
                                <form action="{{ route($routePrefix . 'themeSettings.destroy', $themeSetting->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <li>
                                        <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="ti ti-trash"></i> Delete</button>
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

<div id="activeForModal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Set Active For</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>

            <div class="modal-body">
                <p class="italic"><small>Select a target and it will be added to the list (like tags).</small></p>

                <input type="hidden" id="active_for_theme_id" />
                <input type="hidden" id="active_for_url" />

                <div class="form-group">
                    <label>Choose Target</label>
                    <select id="active_for_select" class="selectpicker form-control" data-live-search="false" title="Select...">
                        <option value="app">App</option>
                        <option value="dash">Dashboard</option>
                        <option value="site">Site</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Selected</label>
                    <div id="active_for_tags" style="min-height: 42px;"></div>
                    <small class="text-muted">Click a tag to remove.</small>
                </div>

                <div class="alert alert-danger d-none" id="active_for_error"></div>

                <div class="form-group">
                    <button type="button" class="btn btn-primary" id="active_for_save_btn">Save</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script>
    (function ($) {
        "use strict";

        function refreshSelectPicker($el) {
            if ($el && typeof $el.selectpicker === 'function') {
                $el.selectpicker('refresh');
            }
        }

        function normalizeActiveFor(value) {
            if (!value) return [];
            if (Array.isArray(value)) return value;
            try {
                var parsed = JSON.parse(value);
                return Array.isArray(parsed) ? parsed : [];
            } catch (e) {
                return [];
            }
        }

        function renderTags(activeFor) {
            var container = $('#active_for_tags');
            container.empty();

            if (!activeFor || !activeFor.length) {
                container.append('<span class="text-muted">—</span>');
                return;
            }

            activeFor.forEach(function (tag) {
                var label = String(tag || '').toUpperCase();
                var badge = $('<span class="badge badge-primary mr-1" style="cursor:pointer;">' + label + ' ×</span>');
                badge.attr('data-value', tag);
                container.append(badge);
            });
        }

        function updateRowActiveFor(rowId, activeFor) {
            var row = $('tr[data-id="' + rowId + '"]');
            row.attr('data-active_for', JSON.stringify(activeFor));
            row.find('.active-for-btn').attr('data-active_for', JSON.stringify(activeFor));
            row.find('.active-for-cell').html(
                activeFor.length
                    ? activeFor.map(function (t) { return '<span class="badge badge-primary mr-1">' + String(t).toUpperCase() + '</span>'; }).join('')
                    : '<span class="text-muted">—</span>'
            );
        }

        $(document).on('click', '.active-for-btn', function () {
            var id = $(this).data('id');
            var url = $(this).data('url');
            var activeFor = normalizeActiveFor($(this).attr('data-active_for'));

            $('#active_for_theme_id').val(id);
            $('#active_for_url').val(url);
            $('#active_for_error').addClass('d-none').text('');

            renderTags(activeFor);

            // reset select
            var $select = $('#active_for_select');
            $select.val('');
            refreshSelectPicker($select);
        });

        $('#active_for_select').on('change changed.bs.select', function () {
            var selected = $(this).val();
            if (!selected) return;

            var tags = [];
            $('#active_for_tags span.badge').each(function () {
                tags.push($(this).attr('data-value'));
            });

            if (tags.indexOf(selected) === -1) {
                tags.push(selected);
            }

            renderTags(tags);
            $(this).val('');
            refreshSelectPicker($(this));
        });

        $(document).on('click', '#active_for_tags span.badge', function () {
            var remove = $(this).attr('data-value');
            var tags = [];
            $('#active_for_tags span.badge').each(function () {
                var v = $(this).attr('data-value');
                if (v !== remove) tags.push(v);
            });

            renderTags(tags);
        });

        $('#active_for_save_btn').on('click', function () {
            var id = $('#active_for_theme_id').val();
            var url = $('#active_for_url').val();
            var activeFor = [];
            $('#active_for_tags span.badge').each(function () {
                activeFor.push($(this).attr('data-value'));
            });

            $('#active_for_error').addClass('d-none').text('');

            if (!activeFor.length) {
                $('#active_for_error').removeClass('d-none').text('Please select at least one target.');
                return;
            }

            $.ajax({
                url: url,
                type: 'PUT',
                data: {
                    _token: '{{ csrf_token() }}',
                    active_for: activeFor
                },
                success: function (res) {
                    if (res && res.success) {
                        updateRowActiveFor(id, res.data && res.data.active_for ? res.data.active_for : activeFor);
                        $('#activeForModal').modal('hide');
                    } else {
                        $('#active_for_error').removeClass('d-none').text((res && res.message) ? res.message : 'Request failed.');
                    }
                },
                error: function (xhr) {
                    var msg = 'Request failed.';
                    if (xhr && xhr.responseJSON) {
                        if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        if (xhr.responseJSON.errors) {
                            msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
                        }
                    }
                    $('#active_for_error').removeClass('d-none').text(msg);
                }
            });
        });

        $(function () {
            $('#theme-setting-table').DataTable({
                order: [],
                language: {
                    lengthMenu: '_MENU_ records per page',
                    info: '<small>Showing _START_ - _END_ (_TOTAL_)</small>',
                    search: 'Search',
                    paginate: { previous: '<i class="ti ti-chevron-left"></i>', next: '<i class="ti ti-chevron-right"></i>' }
                },
                columnDefs: [
                    {
                        orderable: false,
                        targets: [0, 7]
                    }
                ]
            });
        });

    })(jQuery);
</script>
@endpush
