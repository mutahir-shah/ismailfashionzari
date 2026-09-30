@extends('backend.layout.main')
@section('content')

<div id="toast-container" style="position:fixed;top:20px;right:20px;z-index:9999;min-width:280px;"></div>

<section>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">

                {{-- Group info --}}
                <div class="alert alert-info mb-3">
                    <strong>{{ $group->name }}</strong> &mdash;
                    @if($group->selection_type === 'single')
                        {{ __('db.Single') }}
                    @else
                        {{ __('db.Multiple') }}
                    @endif
                    &middot;
                    {{ __('db.Min') }}: {{ $group->min_selection }} / {{ __('db.Max') }}: {{ $group->max_selection }} &middot;
                    @if($group->is_required)
                        <span class="badge badge-danger">{{ __('db.Required') }}</span>
                    @else
                        <span class="badge badge-secondary">{{ __('db.Optional') }}</span>
                    @endif
                    &middot; <strong>{{ count($assigned) }}</strong> {{ __('db.products assigned') }}
                </div>

                {{-- Products table --}}
                <div class="table-responsive">
                    <table id="products_table" class="table" style="width:100%">
                        <thead>
                            <tr>
                                <th class="not-exported"></th>
                                <th>{{ __('db.Product') }}</th>
                                <th>{{ __('db.Code') }}</th>
                                <th>{{ __('db.Type') }}</th>
                                <th>{{ __('db.Status') }}</th>
                                <th class="not-exported">{{ __('db.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($products as $product)
                            <tr class="{{ in_array($product->id, $assigned) ? 'table-success' : '' }}">
                                <td></td>
                                <td><strong>{{ $product->name }}</strong></td>
                                <td><code>{{ $product->code }}</code></td>
                                <td><span class="badge badge-secondary">{{ $product->type }}</span></td>
                                <td>
                                    @if(in_array($product->id, $assigned))
                                        <span class="badge badge-success"><i class="ti ti-check"></i> {{ __('db.Assigned') }}</span>
                                    @else
                                        <span class="badge badge-light">{{ __('db.Not assigned') }}</span>
                                    @endif
                                </td>
                                <td class="not-exported">
                                    <button type="button"
                                        class="btn btn-sm btn-primary btn-configure"
                                        data-product-id="{{ $product->id }}"
                                        data-product-name="{{ $product->name }}"
                                        data-toggle="modal"
                                        data-target="#configModal">
                                        <i class="ti ti-settings"></i>
                                        {{ in_array($product->id, $assigned) ? __('db.Edit Pricing') : __('db.Assign & Price') }}
                                    </button>

                                    @if(in_array($product->id, $assigned))
                                    <button type="button" class="btn btn-sm btn-danger btn-unassign"
                                        data-product-id="{{ $product->id }}"
                                        data-group-id="{{ $group->id }}">
                                        <i class="ti ti-x"></i> {{ __('db.Remove') }}
                                    </button>
                                    @endif
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

{{-- ── Configure/Assign Modal ─────────────────────────────────────────────── --}}
<div id="configModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('db.Configure') }}: <span id="modal_product_name"></span> → {{ $group->name }}</h5>
                <button type="button" data-dismiss="modal" class="close"><span>&times;</span></button>
            </div>
            <form id="config-form" action="{{ route('restaurant.modifier-group.assign-product') }}" method="POST">
                @csrf
                <input type="hidden" name="product_id" id="form_product_id">
                <input type="hidden" name="modifier_group_id" value="{{ $group->id }}">

                <div class="modal-body">

                    {{-- Group Settings Override hidden (not needed for initial setup) --}}
                    <input type="hidden" name="sort_order" id="form_sort" value="0">
                    <input type="hidden" name="min_selection_override" id="form_min" value="">
                    <input type="hidden" name="max_selection_override" id="form_max" value="">

                    {{-- Per-modifier pricing --}}
                    <div class="card">
                        <div class="card-header"><strong>{{ __('db.Modifier Pricing') }}</strong></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>{{ __('db.Modifier') }}</th>
                                            <th class="text-center">{{ __('db.Active') }}</th>
                                            <th>{{ __('db.Price Adjustment') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modifier_pricing_rows">
                                    @foreach($group->modifiers as $modifier)
                                        <tr data-modifier-id="{{ $modifier->id }}">
                                            <td><strong>{{ $modifier->name }}</strong></td>
                                            <td class="text-center">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input modifier-active"
                                                        id="active_{{ $modifier->id }}"
                                                        name="modifiers[{{ $modifier->id }}][is_active]"
                                                        checked>
                                                    <label class="custom-control-label" for="active_{{ $modifier->id }}"></label>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" step="0.0001" class="form-control form-control-sm modifier-price"
                                                    name="modifiers[{{ $modifier->id }}][price_adjustment]"
                                                    value="0" placeholder="0.00" style="width:130px">
                                            </td>
                                            {{-- Hidden fields: ingredient deduction (reserved for future inventory integration) --}}
                                            <input type="hidden" name="modifiers[{{ $modifier->id }}][sort_order]" value="{{ $modifier->sort_order }}">
                                            <input type="hidden" name="modifiers[{{ $modifier->id }}][product_list]" class="modifier-product-list" value="">
                                            <input type="hidden" name="modifiers[{{ $modifier->id }}][qty_list]" class="modifier-qty-list" value="">
                                            <input type="hidden" name="modifiers[{{ $modifier->id }}][variant_list]" class="modifier-variant-list" value="">
                                            <input type="hidden" name="modifiers[{{ $modifier->id }}][wastage_percent]" class="modifier-wastage" value="">
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>{{-- /modal-body --}}

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('db.Cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="btn-save-config">
                        <span class="spinner-border spinner-border-sm d-none mr-1" id="config-spinner"></span>
                        <i class="ti ti-device-floppy"></i> {{ __('db.Save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
@include('backend.layout.partials.datatable_js')
<script>
"use strict";

var assignUrl   = '{{ route("restaurant.modifier-group.assign-product") }}';
var unassignUrl = '{{ route("restaurant.modifier-group.unassign-product") }}';
var configUrl   = '{{ route("restaurant.modifier-group.product-config") }}';
var csrfToken   = '{{ csrf_token() }}';
var groupId     = {{ $group->id }};

function showToast(type, message) {
    var cls = type === 'success' ? 'alert-success' : 'alert-danger';
    var $t = $('<div class="alert ' + cls + ' alert-dismissible shadow-sm">' +
        '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>' +
        message + '</div>');
    $('#toast-container').append($t);
    setTimeout(function () { $t.fadeOut(400, function () { $(this).remove(); }); }, 3500);
}

$(document).ready(function() {
    var productsTable = $('#products_table').DataTable({
        order: [[4, 'asc']],
        language: {
            lengthMenu: '_MENU_ {{ __("db.records per page") }}',
            info: '<small>{{ __("db.Showing") }} _START_ - _END_ (_TOTAL_)</small>',
            search: '{{ __("db.Search") }}',
            paginate: { previous: '<i class="ti ti-chevron-left"></i>', next: '<i class="ti ti-chevron-right"></i>' }
        },
        columnDefs: [
            { orderable: false, targets: [0, 5] },
            {
                render: function (data, type, row, meta) {
                    if (type === 'display') data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                    return data;
                },
                checkboxes: { selectRow: true },
                targets: [0]
            }
        ],
        lengthMenu: [[25, 50, -1], [25, 50, 'All']],
        dom: '<"row"lfB>rtip',
        buttons: [
            { extend: 'csv',    text: '<i class="ti ti-file-type-csv"></i>' },
            { extend: 'colvis', text: '<i class="ti ti-eye"></i>', columns: ':gt(0)' }
        ],
    });
});

// ── Unassign (AJAX) ───────────────────────────────────────────────────────────
$(document).on('click', '.btn-unassign', function () {
    if (!confirm('{{ __("db.Remove this product from the modifier group?") }}')) return;
    var productId = $(this).data('product-id');
    var $btn = $(this).prop('disabled', true).html('<i class="ti ti-loader"></i>');
    $.post(unassignUrl, { _token: csrfToken, product_id: productId, modifier_group_id: groupId })
        .done(function (res) {
            showToast('success', res.message);
            // Reload page to update assigned badge/button state
            location.reload();
        })
        .fail(function (xhr) {
            showToast('error', xhr.responseJSON ? xhr.responseJSON.message : '{{ __("db.An error occurred. Please try again.") }}');
            $btn.prop('disabled', false).html('<i class="ti ti-x"></i> {{ __("db.Remove") }}');
        });
});

// ── Populate config modal ─────────────────────────────────────────────────────
$(document).on('click', '.btn-configure', function () {
    var productId   = $(this).data('product-id');
    var productName = $(this).data('product-name');

    $('#modal_product_name').text(productName);
    $('#form_product_id').val(productId);

    $('#modifier_pricing_rows tr').each(function () {
        $(this).find('.modifier-price').val('0');
        $(this).find('.modifier-active').prop('checked', true);
    });
    $('#form_sort').val(0); $('#form_min').val(''); $('#form_max').val('');

    $.getJSON(configUrl, { product_id: productId, modifier_group_id: groupId }, function (data) {
        if (data.assignment) {
            $('#form_sort').val(data.assignment.sort_order || 0);
        }
        if (data.pricing) {
            $.each(data.pricing, function (modifierId, row) {
                var $tr = $('#modifier_pricing_rows tr[data-modifier-id="' + modifierId + '"]');
                if ($tr.length) {
                    $tr.find('.modifier-price').val(row.price_adjustment || 0);
                    $tr.find('.modifier-active').prop('checked', row.is_active == 1);
                }
            });
        }
    });
});

// ── Save config (AJAX) ────────────────────────────────────────────────────────
$('#btn-save-config').on('click', function () {
    var formData = $('#config-form').serializeArray();
    formData.push({ name: '_token', value: csrfToken });

    $('#config-spinner').removeClass('d-none');
    $('#btn-save-config').prop('disabled', true);

    $.post(assignUrl, formData)
        .done(function (res) {
            $('#configModal').modal('hide');
            showToast('success', res.message);
            // Reload page so assigned/not-assigned state updates
            location.reload();
        })
        .fail(function (xhr) {
            showToast('error', xhr.responseJSON ? xhr.responseJSON.message : '{{ __("db.An error occurred. Please try again.") }}');
        })
        .always(function () {
            $('#config-spinner').addClass('d-none');
            $('#btn-save-config').prop('disabled', false);
        });
});
</script>
@endpush
