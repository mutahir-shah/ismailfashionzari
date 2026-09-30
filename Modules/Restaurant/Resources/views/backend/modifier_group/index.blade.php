@extends('backend.layout.main')
@section('content')

<div id="toast-container" style="position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;"></div>

<section>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">

                <button class="btn btn-primary mb-3" id="btn-open-add">
                    <i class="ti ti-plus"></i> {{ __('db.Add Modifier Group') }}
                </button>

                <div class="table-responsive">
                    <table id="modifier_group_table" class="table" style="width:100%">
                        <thead>
                            <tr>
                                <th class="not-exported"></th>
                                <th>{{ __('db.Name') }}</th>
                                <th>{{ __('db.Type') }}</th>
                                <th>{{ __('db.Min / Max') }}</th>
                                <th>{{ __('db.Required') }}</th>
                                <th>{{ __('db.Options') }}</th>
                                <th>{{ __('db.Linked Products') }}</th>
                                <th>{{ __('db.Status') }}</th>
                                <th class="not-exported">{{ __('db.action') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>

            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════════════
     ADD Modal
══════════════════════════════════════════════════════════════════════════════ --}}
<div id="addGroupModal" class="modal fade" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-plus"></i> {{ __('db.Add Modifier Group') }}</h5>
                <button type="button" data-dismiss="modal" class="close"><span>&times;</span></button>
            </div>
            <div class="modal-body">

                {{-- ── Group Settings ── --}}
                <div class="row">
                    <div class="col-sm-5">
                        <div class="form-group">
                            <label>{{ __('db.Group Name') }} <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" id="add_name"
                                placeholder="{{ __('db.e.g. Size, Sauce, Spice Level') }}">
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            <label>{{ __('db.Selection Type') }}</label>
                            <select class="form-control" id="add_selection_type">
                                <option value="single">{{ __('db.Single (pick one)') }}</option>
                                <option value="multiple">{{ __('db.Multiple (pick many)') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-group">
                            <label>{{ __('db.Min') }} / {{ __('db.Max') }}</label>
                            <div class="input-group input-group-sm">
                                <input class="form-control" type="number" id="add_min_selection" value="0" min="0" style="width:50%">
                                <input class="form-control" type="number" id="add_max_selection" value="1" min="1" style="width:50%">
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-2 d-flex align-items-center">
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" id="add_is_required">
                            <label class="form-check-label" for="add_is_required">{{ __('db.Required') }}</label>
                        </div>
                    </div>
                </div>

                <hr class="my-2">

                {{-- ── Options (with price) ── --}}
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>{{ __('db.Options') }}</strong>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-option-row-btn">
                        <i class="ti ti-plus"></i> {{ __('db.Add Option') }}
                    </button>
                </div>
                <table class="table table-sm table-bordered mb-1" id="add-options-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ __('db.Option Name') }} <span class="text-danger">*</span></th>
                            <th style="width:130px">{{ __('db.Price Adjustment') }}</th>
                            <th style="width:80px">{{ __('db.Sort') }}</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody id="add-options-body"></tbody>
                </table>
                <p class="text-muted small mb-3">
                    <i class="ti ti-info-circle"></i>
                    {{ __('db.Price adjustments are defaults. Per-product overrides can be set from the Products page.') }}
                </p>

                <hr class="my-2">

                {{-- ── Linked Products ── --}}
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>{{ __('db.Linked Products') }}</strong>
                    <span class="text-muted small">{{ __('db.Type to search and select products') }}</span>
                </div>
                <div class="product-tag-widget" id="add-product-widget">
                    <div class="ptw-tags" id="add-product-tags"></div>
                    <div class="ptw-input-wrap">
                        <input type="text" class="ptw-search" id="add-product-search"
                            placeholder="{{ __('db.Search products...') }}" autocomplete="off">
                    </div>
                    <div class="ptw-dropdown" id="add-product-dropdown" style="display:none;"></div>
                </div>
                <input type="hidden" id="add_product_ids" value="[]">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('db.Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save-add">
                    <span class="spinner-border spinner-border-sm d-none mr-1" id="add-spinner"></span>
                    <i class="ti ti-device-floppy"></i> {{ __('db.Save') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════════
     EDIT Modal
══════════════════════════════════════════════════════════════════════════════ --}}
<div id="editGroupModal" class="modal fade" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-pencil"></i> {{ __('db.Edit Modifier Group') }}</h5>
                <button type="button" data-dismiss="modal" class="close"><span>&times;</span></button>
            </div>
            <div class="modal-body">

                <input type="hidden" id="edit_id">
                <div class="row">
                    <div class="col-sm-5">
                        <div class="form-group">
                            <label>{{ __('db.Group Name') }} <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" id="edit_name">
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            <label>{{ __('db.Selection Type') }}</label>
                            <select class="form-control" id="edit_selection_type">
                                <option value="single">{{ __('db.Single (pick one)') }}</option>
                                <option value="multiple">{{ __('db.Multiple (pick many)') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-group">
                            <label>{{ __('db.Min') }} / {{ __('db.Max') }}</label>
                            <div class="input-group input-group-sm">
                                <input class="form-control" type="number" id="edit_min_selection" min="0" style="width:50%">
                                <input class="form-control" type="number" id="edit_max_selection" min="1" style="width:50%">
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-2 d-flex align-items-center">
                        <div class="mt-1">
                            <div class="form-check mb-1">
                                <input type="checkbox" class="form-check-input" id="edit_is_required">
                                <label class="form-check-label" for="edit_is_required">{{ __('db.Required') }}</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="edit_is_active">
                                <label class="form-check-label" for="edit_is_active">{{ __('db.Active') }}</label>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-2">

                {{-- ── Options (with price) ── --}}
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>{{ __('db.Options') }}</strong>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="edit-option-row-btn">
                        <i class="ti ti-plus"></i> {{ __('db.Add Option') }}
                    </button>
                </div>
                <table class="table table-sm table-bordered mb-1" id="edit-options-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ __('db.Option Name') }} <span class="text-danger">*</span></th>
                            <th style="width:130px">{{ __('db.Price Adjustment') }}</th>
                            <th style="width:80px">{{ __('db.Sort') }}</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody id="edit-options-body">
                        <tr><td colspan="4" class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary"></div>
                        </td></tr>
                    </tbody>
                </table>
                <p class="text-muted small mb-3">
                    <i class="ti ti-info-circle"></i>
                    {{ __('db.Removing an option will not affect previously recorded orders.') }}
                </p>

                <hr class="my-2">

                {{-- ── Linked Products ── --}}
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>{{ __('db.Linked Products') }}</strong>
                    <span class="text-muted small">{{ __('db.Type to search and select products') }}</span>
                </div>
                <div class="product-tag-widget" id="edit-product-widget">
                    <div class="ptw-tags" id="edit-product-tags"></div>
                    <div class="ptw-input-wrap">
                        <input type="text" class="ptw-search" id="edit-product-search"
                            placeholder="{{ __('db.Search products...') }}" autocomplete="off">
                    </div>
                    <div class="ptw-dropdown" id="edit-product-dropdown" style="display:none;"></div>
                </div>
                <input type="hidden" id="edit_product_ids" value="[]">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('db.Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save-edit">
                    <span class="spinner-border spinner-border-sm d-none mr-1" id="edit-spinner"></span>
                    <i class="ti ti-device-floppy"></i> {{ __('db.Save') }}
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('css')
<style>
/* ── Product Tag Widget ─────────────────────────────────────────── */
.product-tag-widget {
    border: 1px solid #ced4da;
    border-radius: .25rem;
    padding: 4px 8px 2px;
    background: #fff;
    min-height: 40px;
    position: relative;
    cursor: text;
}
.product-tag-widget:focus-within {
    border-color: #80bdff;
    box-shadow: 0 0 0 .2rem rgba(0,123,255,.25);
}
.ptw-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-bottom: 2px;
}
.ptw-tag {
    display: inline-flex;
    align-items: center;
    background: #e8f0fe;
    border: 1px solid #c2d3fc;
    border-radius: 3px;
    padding: 1px 6px 1px 8px;
    font-size: 12.5px;
    color: #1a3a7a;
    white-space: nowrap;
    max-width: 220px;
}
.ptw-tag span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.ptw-tag-remove {
    margin-left: 5px;
    cursor: pointer;
    font-size: 15px;
    line-height: 1;
    color: #5570a0;
    flex-shrink: 0;
}
.ptw-tag-remove:hover { color: #c0392b; }
.ptw-input-wrap { display: flex; align-items: center; }
.ptw-search {
    border: none;
    outline: none;
    flex: 1;
    font-size: 13px;
    padding: 3px 2px;
    background: transparent;
    min-width: 150px;
}
.ptw-dropdown {
    position: absolute;
    left: -1px; right: -1px;
    top: calc(100% + 2px);
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 4px;
    max-height: 220px;
    overflow-y: auto;
    z-index: 1060;
    box-shadow: 0 4px 12px rgba(0,0,0,.12);
}
.ptw-dropdown-item {
    padding: 7px 12px;
    cursor: pointer;
    font-size: 13px;
    border-bottom: 1px solid #f3f3f3;
}
.ptw-dropdown-item:last-child { border-bottom: none; }
.ptw-dropdown-item:hover,
.ptw-dropdown-item.active { background: #eaf0ff; }
.ptw-dropdown-item.disabled { color: #aaa; cursor: default; }
.ptw-no-results { padding: 8px 12px; color: #999; font-size: 13px; }
</style>
@endpush

@push('scripts')
@include('backend.layout.partials.datatable_js')
<script>
"use strict";

var MG = {
    dataUrl:         '{{ route("restaurant.modifier-group.data") }}',
    storeUrl:        '{{ route("restaurant.modifier-group.store") }}',
    updateUrl:       '{{ route("restaurant.modifier-group.update") }}',
    editBase:        '{{ url("restaurant/modifier-group/edit") }}',
    deleteBase:      '{{ url("restaurant/modifier-group/delete") }}',
    productsBase:    '{{ url("restaurant/modifier-group") }}',
    manageBase:      '{{ url("restaurant/modifier-group") }}',
    productSearch:   '{{ route("restaurant.modifier-group.product-search") }}',
    csrfToken:       '{{ csrf_token() }}',
    labelSingle:     '{{ __("db.Single") }}',
    labelMultiple:   '{{ __("db.Multiple") }}',
    labelYes:        '{{ __("db.Yes") }}',
    labelNo:         '{{ __("db.No") }}',
    labelActive:     '{{ __("db.Active") }}',
    labelInactive:   '{{ __("db.Inactive") }}',
    labelManage:     '{{ __("db.Manage") }}',
    labelProducts:   '{{ __("db.Products") }}',
    labelEdit:       '{{ __("db.Edit") }}',
    labelDelete:     '{{ __("db.Delete") }}',
    confirmDelete:   '{{ __("db.Delete this modifier group? All modifiers and product links will also be deleted.") }}',
    optPlaceholder:  '{{ __("db.Option name, e.g. Small") }}',
    errRequired:     '{{ __("db.An error occurred. Please try again.") }}',
    errDelFailed:    '{{ __("db.Delete failed.") }}',
    errLoadFailed:   '{{ __("db.Failed to load modifier group.") }}',
    errOneOption:    '{{ __("db.At least one option is required. Leave the name blank to skip it.") }}',
    noProducts:      '{{ __("db.No products found.") }}',
    loading:         '{{ __("db.Loading...") }}',
};

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(type, msg) {
    var bg = type === 'success' ? '#28a745' : '#dc3545';
    var $t = $('<div style="background:' + bg + ';color:#fff;padding:12px 16px;border-radius:6px;margin-bottom:8px;box-shadow:0 3px 10px rgba(0,0,0,.2);display:flex;align-items:center;gap:10px;">' +
        '<span style="flex:1">' + msg + '</span>' +
        '<span style="cursor:pointer;font-size:18px;line-height:1;opacity:.85" class="toast-close">&times;</span>' +
        '</div>');
    $t.find('.toast-close').on('click', function(){ $t.remove(); });
    $('#toast-container').append($t);
    setTimeout(function(){ $t.fadeOut(400, function(){ $t.remove(); }); }, 4000);
}

// ── Option row helpers ────────────────────────────────────────────────────────
function makeOptionRow(id, name, price, sort) {
    return '<tr class="option-row">' +
        (id ? '<input type="hidden" class="opt-id" value="' + id + '">' : '') +
        '<td><input type="text" class="form-control form-control-sm opt-name" value="' +
            $('<div>').text(name || '').html() + '" placeholder="' + MG.optPlaceholder + '"></td>' +
        '<td><div class="input-group input-group-sm">' +
            '<div class="input-group-prepend"><span class="input-group-text">+</span></div>' +
            '<input type="number" step="0.01" class="form-control form-control-sm opt-price" value="' +
            (parseFloat(price) || 0).toFixed(2) + '" min="0" placeholder="0.00">' +
        '</div></td>' +
        '<td><input type="number" class="form-control form-control-sm opt-sort" value="' + (sort != null ? sort : 0) + '" min="0"></td>' +
        '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-opt-row"><i class="ti ti-x"></i></button></td>' +
        '</tr>';
}

function collectOptions(bodyId) {
    var opts = [];
    $('#' + bodyId + ' tr.option-row').each(function(i) {
        var name = $(this).find('.opt-name').val().trim();
        if (!name) return;
        var obj = {
            name:             name,
            price_adjustment: parseFloat($(this).find('.opt-price').val()) || 0,
            sort_order:       parseInt($(this).find('.opt-sort').val()) || i
        };
        var id = $(this).find('.opt-id').val();
        if (id) obj.id = parseInt(id);
        opts.push(obj);
    });
    return opts;
}

// ── Product Tag Widget ────────────────────────────────────────────────────────
function ProductTagWidget(opts) {
    var self = this;
    self.searchUrl   = opts.searchUrl;
    self.tagsEl      = document.getElementById(opts.tagsId);
    self.searchEl    = document.getElementById(opts.searchId);
    self.dropdownEl  = document.getElementById(opts.dropdownId);
    self.hiddenEl    = document.getElementById(opts.hiddenId);
    self.selected    = []; // [{id, text}]
    self._timer      = null;

    // Render all tags
    self.renderTags = function() {
        self.tagsEl.innerHTML = '';
        self.selected.forEach(function(item) {
            var tag = document.createElement('div');
            tag.className = 'ptw-tag';
            var label = document.createElement('span');
            label.title = String(item.text || '');
            label.textContent = String(item.text || '');
            var remove = document.createElement('span');
            remove.className = 'ptw-tag-remove';
            remove.dataset.id = String(item.id);
            remove.textContent = '×';
            remove.addEventListener('click', function() {
                self.remove(item.id);
            });
            tag.appendChild(label);
            tag.appendChild(remove);
            self.tagsEl.appendChild(tag);
        });
        self.hiddenEl.value = JSON.stringify(self.selected.map(function(x){ return x.id; }));
    };

    self.add = function(id, text) {
        if (self.selected.find(function(x){ return x.id == id; })) return;
        self.selected.push({ id: id, text: text });
        self.renderTags();
        self.searchEl.value = '';
        self.hideDropdown();
    };

    self.remove = function(id) {
        self.selected = self.selected.filter(function(x){ return x.id != id; });
        self.renderTags();
    };

    self.reset = function() {
        self.selected = [];
        self.renderTags();
        self.searchEl.value = '';
        self.hideDropdown();
    };

    self.setSelected = function(items) {
        self.selected = items.slice();
        self.renderTags();
    };

    self.hideDropdown = function() {
        self.dropdownEl.style.display = 'none';
        self.dropdownEl.innerHTML = '';
    };

    self.showDropdown = function(items) {
        self.dropdownEl.innerHTML = '';
        if (!items.length) {
            self.dropdownEl.innerHTML = '<div class="ptw-no-results">' + MG.noProducts + '</div>';
        } else {
            items.forEach(function(item) {
                var alreadySelected = self.selected.find(function(x){ return x.id == item.id; });
                var div = document.createElement('div');
                div.className = 'ptw-dropdown-item' + (alreadySelected ? ' disabled' : '');
                div.textContent = item.text;
                if (!alreadySelected) {
                    div.addEventListener('mousedown', function(e) {
                        e.preventDefault(); // prevent blur
                        self.add(item.id, item.text);
                    });
                }
                self.dropdownEl.appendChild(div);
            });
        }
        self.dropdownEl.style.display = 'block';
    };

    self.search = function(q) {
        self.dropdownEl.innerHTML = '<div class="ptw-no-results">' + MG.loading + '</div>';
        self.dropdownEl.style.display = 'block';
        clearTimeout(self._timer);
        self._timer = setTimeout(function() {
            $.getJSON(self.searchUrl, { q: q }, function(data) {
                self.showDropdown(data.results || []);
            }).fail(function() {
                self.hideDropdown();
            });
        }, 220);
    };

    // Events
    self.searchEl.addEventListener('input', function() {
        var q = self.searchEl.value.trim();
        if (q.length === 0) { self.search(''); return; }
        self.search(q);
    });

    self.searchEl.addEventListener('focus', function() {
        self.search(self.searchEl.value.trim());
    });

    self.searchEl.addEventListener('blur', function() {
        setTimeout(function(){ self.hideDropdown(); }, 150);
    });
}

// ── DataTable ─────────────────────────────────────────────────────────────────
var groupsTable;

$(document).ready(function () {

    // Instantiate tag widgets
    var addWidget  = new ProductTagWidget({
        searchUrl:   MG.productSearch,
        tagsId:      'add-product-tags',
        searchId:    'add-product-search',
        dropdownId:  'add-product-dropdown',
        hiddenId:    'add_product_ids',
    });

    var editWidget = new ProductTagWidget({
        searchUrl:   MG.productSearch,
        tagsId:      'edit-product-tags',
        searchId:    'edit-product-search',
        dropdownId:  'edit-product-dropdown',
        hiddenId:    'edit_product_ids',
    });

    // Init DataTable
    groupsTable = $('#modifier_group_table').DataTable({
        processing: true,
        ajax: { url: MG.dataUrl, dataSrc: 'data' },
        order: [[1, 'asc']],
        columns: [
            { data: null, defaultContent: '', orderable: false, searchable: false,
              render: function() { return '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'; }
            },
            { data: 'name', render: function(v) { return '<strong>' + $('<div>').text(v).html() + '</strong>'; } },
            { data: 'selection_type', render: function(v) {
                return v === 'single'
                    ? '<span class="badge badge-info">' + MG.labelSingle + '</span>'
                    : '<span class="badge badge-warning">' + MG.labelMultiple + '</span>';
            }},
            { data: null, render: function(r) { return r.min_selection + ' / ' + r.max_selection; } },
            { data: 'is_required', render: function(v) {
                return v ? '<span class="badge badge-danger">' + MG.labelYes + '</span>'
                         : '<span class="badge badge-secondary">' + MG.labelNo + '</span>';
            }},
            { data: null, orderable: false, searchable: false, render: function(r) {
                return '<span class="badge badge-primary mr-1">' + r.modifiers_count + '</span>' +
                    '<a href="' + MG.manageBase + '/' + r.id + '/modifiers" class="btn btn-xs btn-outline-primary">' +
                    '<i class="ti ti-list"></i> ' + MG.labelManage + '</a>';
            }},
            { data: null, orderable: false, searchable: false, render: function(r) {
                // Products column — load lazily via tooltip on hover if needed; for now show count badge
                return '<span class="badge badge-secondary" id="products-count-' + r.id + '">—</span>&nbsp;' +
                    '<a href="' + MG.productsBase + '/' + r.id + '/products" class="btn btn-xs btn-outline-success">' +
                    '<i class="ti ti-apps"></i> ' + MG.labelProducts + '</a>';
            }},
            { data: 'is_active', render: function(v) {
                return v ? '<span class="badge badge-success">' + MG.labelActive + '</span>'
                         : '<span class="badge badge-secondary">' + MG.labelInactive + '</span>';
            }},
            { data: null, orderable: false, searchable: false, render: function(r) {
                return '<button class="btn btn-sm btn-primary btn-edit mr-1" data-id="' + r.id + '" title="' + MG.labelEdit + '">' +
                    '<i class="ti ti-pencil"></i></button>' +
                    '<button class="btn btn-sm btn-danger btn-delete" data-id="' + r.id + '" title="' + MG.labelDelete + '">' +
                    '<i class="ti ti-trash"></i></button>';
            }},
        ],
        columnDefs: [
            { targets: [0], checkboxes: { selectRow: true, selectAllRender: '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>' } }
        ],
        select: { style: 'multi', selector: 'td:first-child' },
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
        dom: '<"row"lfB>rtip',
        language: {
            lengthMenu: '_MENU_ {{ __("db.records per page") }}',
            info: '<small>{{ __("db.Showing") }} _START_ - _END_ (_TOTAL_)</small>',
            search: '{{ __("db.Search") }}',
            processing: '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>',
            paginate: { previous: '<i class="ti ti-chevron-left"></i>', next: '<i class="ti ti-chevron-right"></i>' }
        },
        buttons: [
            { extend: 'csv',   text: '<i class="ti ti-file-type-csv"></i>',  exportOptions: { columns: ':visible:Not(.not-exported)' } },
            { extend: 'print', text: '<i class="ti ti-printer"></i>',        exportOptions: { columns: ':visible:Not(.not-exported)' } },
            { extend: 'colvis',text: '<i class="ti ti-eye"></i>', columns: ':gt(0)' }
        ],
    });

    // After first draw, load product counts for each group
    groupsTable.on('draw', function() {
        groupsTable.rows().data().each(function(r) {
            $.getJSON(MG.editBase + '/' + r.id, function(data) {
                var count = (data.assigned_products || []).length;
                var $el = $('#products-count-' + r.id);
                $el.text(count).removeClass('badge-secondary').addClass(count > 0 ? 'badge-success' : 'badge-secondary');
            });
        });
    });

    // ── Remove option row ─────────────────────────────────────────────────────
    $(document).on('click', '.remove-opt-row', function() {
        var $tbody = $(this).closest('tbody');
        if ($tbody.find('tr.option-row').length <= 1) {
            showToast('error', MG.errOneOption);
            return;
        }
        $(this).closest('tr.option-row').remove();
    });

    // ─────────────────────────────────────────────────────────────────────────
    // ADD Modal
    // ─────────────────────────────────────────────────────────────────────────
    $('#btn-open-add').on('click', function() {
        $('#add_name').val('').removeClass('is-invalid');
        $('#add_selection_type').val('single');
        $('#add_min_selection').val(0);
        $('#add_max_selection').val(1);
        $('#add_is_required').prop('checked', false);
        $('#add-options-body').html(makeOptionRow(null, '', 0, 0));
        addWidget.reset();
        $('#addGroupModal').modal('show');
        setTimeout(function(){ $('#add_name').focus(); }, 400);
    });

    $('#add-option-row-btn').on('click', function() {
        var next = $('#add-options-body tr.option-row').length;
        $('#add-options-body').append(makeOptionRow(null, '', 0, next));
    });

    $('#btn-save-add').on('click', function() {
        var name = $('#add_name').val().trim();
        if (!name) { $('#add_name').addClass('is-invalid').focus(); return; }
        $('#add_name').removeClass('is-invalid');

        var payload = {
            _token:         MG.csrfToken,
            name:           name,
            selection_type: $('#add_selection_type').val(),
            min_selection:  $('#add_min_selection').val(),
            max_selection:  $('#add_max_selection').val(),
            is_required:    $('#add_is_required').is(':checked') ? 1 : 0,
            modifiers:      JSON.stringify(collectOptions('add-options-body')),
            product_ids:    $('#add_product_ids').val(),
        };

        $('#add-spinner').removeClass('d-none');
        $('#btn-save-add').prop('disabled', true);

        $.post(MG.storeUrl, payload)
            .done(function(res) {
                $('#addGroupModal').modal('hide');
                showToast('success', res.message);
                groupsTable.ajax.reload(null, false);
            })
            .fail(function(xhr) {
                showToast('error', xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : MG.errRequired);
            })
            .always(function() {
                $('#add-spinner').addClass('d-none');
                $('#btn-save-add').prop('disabled', false);
            });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // EDIT Modal
    // ─────────────────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        $('#edit-options-body').html(
            '<tr><td colspan="4" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>'
        );
        editWidget.reset();
        $('#editGroupModal').modal('show');

        $.getJSON(MG.editBase + '/' + id)
            .done(function(data) {
                $('#edit_id').val(data.id);
                $('#edit_name').val(data.name).removeClass('is-invalid');
                $('#edit_selection_type').val(data.selection_type);
                $('#edit_min_selection').val(data.min_selection);
                $('#edit_max_selection').val(data.max_selection);
                $('#edit_is_required').prop('checked', !!data.is_required);
                $('#edit_is_active').prop('checked', !!data.is_active);

                // Populate options
                var rows = '';
                if (data.modifiers && data.modifiers.length) {
                    $.each(data.modifiers, function(i, m) {
                        rows += makeOptionRow(m.id, m.name, m.price_adjustment, m.sort_order);
                    });
                } else {
                    rows = makeOptionRow(null, '', 0, 0);
                }
                $('#edit-options-body').html(rows);

                // Populate product tags
                if (data.assigned_products && data.assigned_products.length) {
                    editWidget.setSelected(data.assigned_products);
                }

                setTimeout(function(){ $('#edit_name').focus(); }, 100);
            })
            .fail(function() {
                showToast('error', MG.errLoadFailed);
                $('#editGroupModal').modal('hide');
            });
    });

    $('#edit-option-row-btn').on('click', function() {
        var next = $('#edit-options-body tr.option-row').length;
        $('#edit-options-body').append(makeOptionRow(null, '', 0, next));
    });

    $('#btn-save-edit').on('click', function() {
        var name = $('#edit_name').val().trim();
        if (!name) { $('#edit_name').addClass('is-invalid').focus(); return; }
        $('#edit_name').removeClass('is-invalid');

        var payload = {
            _token:         MG.csrfToken,
            id:             $('#edit_id').val(),
            name:           name,
            selection_type: $('#edit_selection_type').val(),
            min_selection:  $('#edit_min_selection').val(),
            max_selection:  $('#edit_max_selection').val(),
            is_required:    $('#edit_is_required').is(':checked') ? 1 : 0,
            is_active:      $('#edit_is_active').is(':checked') ? 1 : 0,
            modifiers:      JSON.stringify(collectOptions('edit-options-body')),
            product_ids:    $('#edit_product_ids').val(),
        };

        $('#edit-spinner').removeClass('d-none');
        $('#btn-save-edit').prop('disabled', true);

        $.post(MG.updateUrl, payload)
            .done(function(res) {
                $('#editGroupModal').modal('hide');
                showToast('success', res.message);
                groupsTable.ajax.reload(null, false);
            })
            .fail(function(xhr) {
                showToast('error', xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : MG.errRequired);
            })
            .always(function() {
                $('#edit-spinner').addClass('d-none');
                $('#btn-save-edit').prop('disabled', false);
            });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE
    // ─────────────────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-delete', function() {
        if (!confirm(MG.confirmDelete)) return;
        var id = $(this).data('id');
        var $btn = $(this).prop('disabled', true).html('<i class="ti ti-loader"></i>');

        $.get(MG.deleteBase + '/' + id)
            .done(function(res) {
                showToast('success', res.message);
                groupsTable.ajax.reload(null, false);
            })
            .fail(function(xhr) {
                showToast('error', xhr.responseJSON ? xhr.responseJSON.message : MG.errDelFailed);
                $btn.prop('disabled', false).html('<i class="ti ti-trash"></i>');
            });
    });

}); // end ready
</script>
@endpush
