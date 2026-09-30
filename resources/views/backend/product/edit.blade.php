@extends('backend.layout.main')

@push('css')
@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
<link rel="stylesheet" href="{{ asset($asset_prefix . 'css/dropzone.css') }}">
<style>
/* ── Shopify-style product edit layout ── */
.product-create-page { background: #f4f6f9; min-height: 100vh; }

/* Sticky header */
.sp-sticky-header {
    position: sticky;
    top: 0;
    z-index: 100;
    padding: 10px 20px;
}

.sp-sticky-header .sp-sticky-header-inner { background: #fff; border: 1px solid #e4e6fc; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; padding: 10px 20px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }

.sp-sticky-header .page-title { font-size: 1.1rem; font-weight: 600; margin: 0 0 0 12px; color: #343a40; }
.sp-sticky-header .header-actions { display: flex; gap: 8px; flex-wrap: wrap; }

/* Section cards */
.sp-card { background: #fff; border: 1px solid #e4e6fc; border-radius: 6px; margin-bottom: 18px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
.sp-card-header { padding: 14px 18px 12px; border-bottom: 1px solid #f0f1f7; display: flex; align-items: center; gap: 8px; }
.sp-card-header h6 { margin: 0; font-size: 0.9rem; font-weight: 600; color: #343a40; }
.sp-card-header .section-icon { font-size: 1rem; color: #6c757d; }
.sp-card-body { padding: 18px; }

/* Product type pills */
.type-pills { display: flex; flex-wrap: wrap; gap: 6px; }
.type-pill { padding: 5px 14px; border: 1px solid #ced4da; border-radius: 20px; font-size: 0.82rem; cursor: pointer; background: #f8f9fa; color: #495057; transition: all .15s ease; user-select: none; }
.type-pill:hover { border-color: #6c757d; background: #e9ecef; }
.type-pill.active { background: #007bff; border-color: #007bff; color: #fff; }

/* Status toggles */
.sp-switch-list { list-style: none; margin: 0; padding: 0; }
.sp-switch-list li { padding: 10px 18px; border-bottom: 1px solid #f0f1f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.sp-switch-list li:last-child { border-bottom: none; }
.sp-switch-meta { flex: 1; }
.sp-switch-meta .switch-label { font-size: 0.85rem; font-weight: 500; color: #343a40; display: block; margin-bottom: 2px; }
.sp-switch-meta .switch-hint { font-size: 0.75rem; color: #6c757d; }

/* Inventory collapse toggles */
.sp-collapse-trigger { font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 6px; color: #343a40; font-weight: 500; padding: 10px 0; border-bottom: 1px solid #f0f1f7; margin-bottom: 0; }
.sp-collapse-trigger:last-of-type { border-bottom: none; }
.sp-collapse-trigger input[type="checkbox"] { margin: 0; }

/* Ecommerce / restaurant search result styles */
.search_result, .search_result_addon { border: 1px solid #e4e6fc; border-radius: 5px; overflow-y: scroll; }
.search_result > div, .search_result_addon > div, .selected_items > div, .selected_addons > div { border-top: 1px solid #e4e6fc; cursor: pointer; display: flex; align-items: center; padding: 10px; position: relative; }
.search_result > div > img, .search_result_addon > div > img, .selected_items > div > img, .selected_addons > div > img { margin-right: 10px; max-width: 40px; }
.search_result > div h4, .search_result_addon > div h4, .selected_items > div h4, .selected_addons > div h4 { font-size: 0.9rem; }
.search_result > div i, .search_result_addon > div i { color: #54b948; position: absolute; right: 5px; top: 30%; }
.search_result div:first-child, .search_result_addon div:first-child { border-top: none; }
.selected_items .remove_item, .selected_addons .remove_item { position: absolute; right: 20px; top: 20px; }
.delVarOption { display: flex; flex-direction: column; align-items: center; }

/* Existing image table */
.img-sort-handle { cursor: grab; }

@media (max-width: 991px) {
    .sp-sticky-header { position: relative; }
    .sp-sticky-header .page-title { font-size: 1rem; }
}

/* ── Dark Mode overrides ── */
body.dark-mode .sp-sticky-header .sp-sticky-header-inner {
    background: #283046;
    border-color: #3b4253;
}
body.dark-mode .sp-sticky-header .page-title {
    color: #d0d2d6;
}
body.dark-mode .sp-card {
    background: #283046;
    border-color: #3b4253;
}
body.dark-mode .sp-card-header {
    border-bottom-color: #3b4253;
}
body.dark-mode .sp-card-header h6 {
    color: #d0d2d6;
}
body.dark-mode .sp-card-header .section-icon {
    color: #8a90a2;
}
body.dark-mode .type-pill {
    background: #141b2e;
    border-color: #3b4253;
    color: #d0d2d6;
}
body.dark-mode .type-pill:hover {
    background: #283046;
    color: #fff;
}
body.dark-mode .type-pill.active {
    background: #007bff;
    border-color: #007bff;
    color: #fff;
}
body.dark-mode .sp-switch-list li {
    border-bottom-color: #3b4253;
}
body.dark-mode .sp-switch-meta .switch-label {
    color: #d0d2d6;
}
body.dark-mode .sp-switch-meta .switch-hint {
    color: #8a90a2;
}
body.dark-mode .sp-collapse-trigger {
    color: #d0d2d6;
    border-bottom-color: #3b4253;
}
body.dark-mode .search_result,
body.dark-mode .search_result_addon {
    border-color: #3b4253;
    background-color: #141b2e;
}
body.dark-mode .search_result>div,
body.dark-mode .search_result_addon>div,
body.dark-mode .selected_items>div,
body.dark-mode .selected_addons>div {
    border-top-color: #3b4253;
}
body.dark-mode .dropzone {
    background: #141b2e;
    border-color: #3b4253;
}
body.dark-mode .dropzone .dz-message {
    color: #8a90a2;
}
</style>
@endpush

@section('content')
<section class="product-create-page">

    <x-success-message key="edit_message" />

    {{-- ═══════════════════ STICKY HEADER ═══════════════════ --}}
    <div class="sp-sticky-header">
        <div class="sp-sticky-header-inner">
            <div>
                <span class="page-title">{{__('db.Update Product')}}</span>
            </div>
            <div class="header-actions">
                <button type="button" id="submit-btn" class="btn btn-primary btn-sm">
                    <i class="ti ti-check"></i> {{__('db.submit')}}
                </button>
            </div>
        </div>
    </div>

    <div class="container-fluid pt-3 pb-5">

        <x-error-message key="not_permitted" />

        {{-- Alert --}}
        <div id="alert-container" class="alert alert-dismissible fade show d-none" role="alert">
            <span id="alert-message"></span>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <form id="product-form">
        <input type="hidden" name="id" value="{{$lims_product_data->id}}" />

        <div class="row">

            {{-- ═══════════ MAIN COLUMN ═══════════ --}}
            <div class="col-lg-8">

                {{-- ── 1. Basic Information ── --}}
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="ti ti-package section-icon"></i>
                        <h6>{{__('db.Basic Information')}}</h6>
                    </div>
                    <div class="sp-card-body">
                        {{-- Product Type pills --}}
                        <div class="form-group">
                            <label class="d-block mb-2" style="font-size:.85rem;font-weight:500;">
                                {{__('db.Product Type')}} <span class="text-danger">*</span>
                            </label>
                            <div class="type-pills">
                                <span class="type-pill" data-value="standard">Standard</span>
                                <span class="type-pill" data-value="combo">Combo</span>
                                <span class="type-pill" data-value="digital">Digital</span>
                                <span class="type-pill" data-value="service">Service</span>
                            </div>
                            {{-- hidden select + hidden value preserved for existing JS --}}
                            <select name="type" required id="type" class="d-none">
                                <option value="standard">Standard</option>
                                <option value="combo">Combo</option>
                                <option value="digital">Digital</option>
                                <option value="service">Service</option>
                            </select>
                            <input type="hidden" name="type_hidden" value="{{$lims_product_data->type}}">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{__('db.Product Name')}} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" value="{{$lims_product_data->name}}" required class="form-control">
                                    <span class="validation-msg" id="name-error"></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{__('db.Product Code')}} <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" name="code" id="code" value="{{$lims_product_data->code}}" class="form-control" required>
                                        <div class="input-group-append">
                                            <button id="genbutton" type="button" class="btn btn-sm btn-default" title="{{__('db.Generate')}}">
                                                <i class="ti ti-refresh"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <span class="validation-msg" id="code-error"></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{__('db.Barcode Symbology')}} <span class="text-danger">*</span></label>
                                    <input type="hidden" name="barcode_symbology_hidden" value="{{$lims_product_data->barcode_symbology}}">
                                    <select name="barcode_symbology" required class="form-control selectpicker">
                                        <option value="UPCE">UPC-E</option>
                                        <option value="C128">Code 128</option>
                                        <option value="C39">Code 39</option>
                                        <option value="UPCA">UPC-A</option>
                                        <option value="EAN8">EAN-8</option>
                                        <option value="EAN13">EAN-13</option>
                                    </select>
                                </div>
                            </div>
                            {{-- Digital: attach file --}}
                            <div id="digital" class="col-md-6">
                                <div class="form-group">
                                    <label>{{__('db.Attach File')}}</label>
                                    <input id="file" type="file" name="file" class="form-control">
                                    <span class="validation-msg"></span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label>{{__('db.Product Details')}}</label>
                            <textarea name="product_details" class="form-control" rows="3">{{str_replace('@', '"', $lims_product_data->product_details)}}</textarea>
                        </div>
                    </div>
                </div>

                {{-- ── 2. Media ── --}}
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="ti ti-photo section-icon"></i>
                        <h6>{{__('db.Media')}}</h6>
                        <small class="text-muted ml-auto" style="font-size:.75rem;">
                            jpeg, jpg, png, gif &bull; First image = base image
                        </small>
                    </div>
                    <div class="sp-card-body">
                        <div class="row">
                            <div class="{{ $lims_product_data->image ? 'col-md-6' : 'col-md-12' }}">
                                <div id="imageUpload" class="dropzone"></div>
                                <span class="validation-msg" id="image-error"></span>
                                <input type="hidden" name="qty" value="{{ $lims_product_data->qty }}" class="form-control">
                            </div>
                            @if($lims_product_data->image)
                            <div class="col-md-6">
                                <table class="table table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th><i class="ti ti-list img-sort-handle"></i></th>
                                            <th>Image</th>
                                            <th>Remove</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $images = explode(",", $lims_product_data->image)?>
                                        @foreach($images as $key => $image)
                                        <tr>
                                            <td><button type="button" class="btn btn-sm"><i class="ti ti-list img-sort-handle"></i></button></td>
                                            <td>
                                                <img src="{{url('images/product', $image)}}" height="60" width="60">
                                                <input type="hidden" name="prev_img[]" value="{{$image}}">
                                            </td>
                                            <td><button type="button" class="btn btn-sm btn-danger remove-img">X</button></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ── 3. Pricing ── --}}
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="ti ti-currency-dollar section-icon"></i>
                        <h6>{{__('db.Pricing')}}</h6>
                    </div>
                    <div class="sp-card-body">
                        <div class="row">
                            @can('cost_edit_in_products')
                            <div id="cost" class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Product Cost')}} <span class="text-danger">*</span></label>
                                    <input type="number" name="cost" value="{{$lims_product_data->cost}}" required class="form-control product_cost" step="any">
                                    <div class="alert alert-warning very-small-text d-none p-2 position-absolute" id="product-cost-warning">
                                        Cost must be higher than 0!
                                    </div>
                                    <span class="validation-msg"></span>
                                </div>
                            </div>
                            @else
                            <div id="cost" class="col-md-4 d-none">
                                <div class="form-group">
                                    <label>{{__('db.Product Cost')}} <span class="text-danger">*</span></label>
                                    <input type="number" name="cost" value="{{$lims_product_data->cost}}" required class="form-control product_cost" step="any">
                                    <div class="alert alert-warning very-small-text d-none p-2 position-absolute" id="product-cost-warning">
                                        Cost must be higher than 0!
                                    </div>
                                    <span class="validation-msg"></span>
                                </div>
                            </div>
                            @endcan
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('db.profit_margin_type') }}</label>
                                    <select name="profit_margin_type" class="form-control" required>
                                        <option value="percentage" {{ $lims_product_data->profit_margin_type == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                        <option value="flat" {{ $lims_product_data->profit_margin_type == 'flat' ? 'selected' : '' }}>Flat</option>
                                    </select>
                                    <span class="validation-msg"></span>
                                </div>
                            </div>
                            <div id="profit_margin" class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Profit Margin')}}</label>
                                    <input type="number" name="profit_margin" value="{{$lims_product_data->profit_margin}}" required class="form-control" {{ $lims_product_data->type === 'service' ? 'readonly' : '' }} step="0.01">
                                    <span class="validation-msg"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.product_price')}} <span class="text-danger">*</span></label>
                                    <input type="number" name="price" value="{{$lims_product_data->price}}" required class="form-control" step="any">
                                    <div class="alert alert-warning very-small-text d-none p-2 position-absolute" id="product-price-warning">
                                        Price must be higher than Cost to make Profit!
                                    </div>
                                    <span class="validation-msg"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Wholesale Price')}}</label>
                                    <input type="number" name="wholesale_price" class="form-control" value="{{$lims_product_data->wholesale_price}}" step="any">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <input type="hidden" name="tax" value="{{$lims_product_data->tax_id}}">
                                    <label>{{__('db.product')}} {{__('db.Tax')}}</label>
                                    <select name="tax_id" class="form-control selectpicker">
                                        <option value="">No Tax</option>
                                        @foreach($lims_tax_list as $tax)
                                            <option value="{{$tax->id}}">{{$tax->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <input type="hidden" name="tax_method_id" value="{{$lims_product_data->tax_method}}">
                                    <label>
                                        {{__('db.Tax Method')}}
                                        <i class="ti ti-info-circle" data-toggle="tooltip" title="{{__('db.Exclusive: Poduct price = Actual product price + Tax Inclusive: Actual product price = Product price - Tax')}}"></i>
                                    </label>
                                    <select name="tax_method" class="form-control selectpicker">
                                        <option value="1">{{__('db.Exclusive')}}</option>
                                        <option value="2">{{__('db.Inclusive')}}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Promotional pricing (collapsible) --}}
                        <div class="border-top pt-3 mt-1">
                            <label class="sp-collapse-trigger mb-2" style="border-bottom:none;">
                                <input type="hidden" name="promotion_hidden" value="{{$lims_product_data->promotion}}">
                                <input name="promotion" type="checkbox" id="promotion" value="1">
                                &nbsp; {{__('db.Add Promotional Price')}}
                            </label>
                            <div class="row mt-2">
                                <div class="col-md-4" id="promotion_price">
                                    <div class="form-group">
                                        <label>{{__('db.Promotional Price')}}</label>
                                        <input type="number" name="promotion_price" value="{{$lims_product_data->promotion_price}}" class="form-control" step="any" />
                                    </div>
                                </div>
                                <div id="start_date" class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Promotion Starts')}}</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text"><i class="ti ti-calendar"></i></div>
                                            </div>
                                            <input type="text" name="starting_date" value="{{$lims_product_data->starting_date}}" id="starting_date" class="form-control" />
                                        </div>
                                    </div>
                                </div>
                                <div id="last_date" class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Promotion Ends')}}</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text"><i class="ti ti-calendar"></i></div>
                                            </div>
                                            <input type="text" name="last_date" value="{{$lims_product_data->last_date}}" id="ending_date" class="form-control" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── 4. Units ── --}}
                <div id="unit" class="sp-card">
                    <div class="sp-card-header">
                        <i class="ti ti-ruler-2 section-icon"></i>
                        <h6>{{__('db.Units')}}</h6>
                    </div>
                    <div class="sp-card-body">
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Product Unit')}} <span class="text-danger">*</span></label>
                                <div class="input-group pos">
                                    <select required class="form-control selectpicker" data-live-search="true" data-live-search-style="begins" title="Select unit..." name="unit_id">
                                        @foreach($lims_unit_list as $unit)
                                            @if($unit->base_unit==null)
                                                <option value="{{$unit->id}}">{{$unit->unit_name}}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="unit" value="{{ $lims_product_data->unit_id}}">
                                </div>
                                <span class="validation-msg"></span>
                            </div>
                            <div class="col-md-4">
                                <label>{{__('db.Sale Unit')}}</label>
                                <div class="input-group pos">
                                    <select class="form-control selectpicker" name="sale_unit_id" id="sale-unit"></select>
                                    <input type="hidden" name="sale_unit" value="{{ $lims_product_data->sale_unit_id}}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Purchase Unit')}}</label>
                                    <div class="input-group pos">
                                        <select class="form-control selectpicker" name="purchase_unit_id"></select>
                                        <input type="hidden" name="purchase_unit" value="{{ $lims_product_data->purchase_unit_id}}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── 5. Variants ── --}}
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="ti ti-adjustments section-icon"></i>
                        <h6>{{__('db.Variants')}}</h6>
                    </div>
                    <div class="sp-card-body">
                        @if($lims_product_data->is_variant)
                        <div class="sp-collapse-trigger" id="variant-option">
                            <h5 class="d-none mb-0">
                                <input name="is_variant" type="checkbox" id="is-variant" value="1" checked>
                                &nbsp; {{__('db.This product has variant')}}
                            </h5>
                        </div>
                        @endif

                        <div id="variant-section">
                            @if($lims_product_data->variant_option)
                            <div id="variant-input-section">
                                @foreach($lims_product_data->variant_option as $key => $variant_option)
                                <?php $noOfVariantValue += count(explode(",", $lims_product_data->variant_value[$key])); ?>
                                <div class="row">
                                    <div class="col-sm-4 form-group mt-2">
                                        <label>{{__('db.Option')}} *</label>
                                        <input type="text" name="variant_option[]" class="form-control variant-field" value="{{$lims_product_data->variant_option[$key]}}">
                                    </div>
                                    <div class="col-sm-7 form-group mt-2">
                                        <label>{{__('db.Value')}} *</label>
                                        <input type="text" name="variant_value[]" class="type-variant form-control variant-field" value="{{$lims_product_data->variant_value[$key]}}">
                                    </div>
                                    <div class="col-sm-1 form-group mt-2" style="display:flex;flex-direction:column;align-items:center;justify-content:end;">
                                        <button type="button" class="delVarOption btn btn-danger btn-sm mr-3"><i class="ti ti-x"></i></button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <div id="variant-input-section">
                                <div class="row">
                                    <div class="col-md-4 form-group mt-2">
                                        <label>{{__('db.Option')}} *</label>
                                        <input type="text" name="variant_option[]" class="form-control variant-field" placeholder="{{__('db.Size, Color etc')}}">
                                    </div>
                                    <div class="col-md-7 form-group mt-2">
                                        <label>{{__('db.Value')}} *</label>
                                        <input type="text" name="variant_value[]" class="type-variant form-control variant-field">
                                    </div>
                                    <div class="col-sm-1 form-group mt-2" style="display:flex;flex-direction:column;align-items:center;justify-content:end;">
                                        <button type="button" class="delVarOption btn btn-danger btn-sm mr-3"><i class="ti ti-x"></i></button>
                                    </div>
                                </div>
                            </div>
                            @endif
                            <div class="col-md-12 form-group mt-2 px-0">
                                <button type="button" class="btn btn-info btn-sm add-more-variant">
                                    <i class="ti ti-plus"></i> {{__('db.Add More Variant')}}
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table id="variant-table" class="table table-hover variant-list">
                                    <thead>
                                        <tr>
                                            <th>{{__('db.name')}}</th>
                                            <th>{{__('db.Item Code')}}</th>
                                            <th>{{__('db.Additional Cost')}}</th>
                                            <th>{{__('db.Additional Price')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($lims_product_variant_data as $key => $variant)
                                        <tr>
                                            <td>{{$variant->name}}
                                                <input type="hidden" class="form-control variant-name" name="variant_name[]" value="{{$variant->name}}" />
                                            </td>
                                            <td><input type="text" class="form-control item-code" name="item_code[]" value="{{$variant->pivot['item_code']}}" /></td>
                                            <td><input type="number" class="form-control additional-cost" name="additional_cost[]" value="{{$variant->pivot['additional_cost']}}" step="any" /></td>
                                            <td><input type="number" class="form-control additional-price" name="additional_price[]" value="{{$variant->pivot['additional_price']}}" step="any" /></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── 6. Inventory ── --}}
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="ti ti-building-warehouse section-icon"></i>
                        <h6>{{__('db.inventory')}}</h6>
                    </div>
                    <div class="sp-card-body">
                        {{-- Different warehouse price --}}
                        <div class="sp-collapse-trigger" id="diffPrice-option">
                            @if($lims_product_data->is_diffPrice)
                                <input name="is_diffPrice" type="checkbox" id="is-diffPrice" value="1" checked>
                            @else
                                <input name="is_diffPrice" type="checkbox" id="is-diffPrice" value="1">
                            @endif
                            &nbsp; {{__('db.This product has different price for different warehouse')}}
                        </div>
                        <div id="diffPrice-section" style="margin-top:8px;">
                            <div class="table-responsive">
                                <table id="diffPrice-table" class="table table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>{{__('db.Warehouse')}}</th>
                                            <th>{{__('db.Price')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($lims_warehouse_list as $warehouse)
                                        <tr>
                                            <td>
                                                <input type="hidden" name="warehouse_id[]" value="{{$warehouse->id}}">
                                                {{$warehouse->name}}
                                            </td>
                                            <td>
                                                <?php
                                                    $product_warehouse = \App\Models\Product_Warehouse::FindProductWithoutVariant($lims_product_data->id, $warehouse->id)->first();
                                                ?>
                                                @if($product_warehouse)
                                                    <input type="number" name="diff_price[]" class="form-control form-control-sm" value="{{$product_warehouse->price}}">
                                                @else
                                                    <input type="number" name="diff_price[]" class="form-control form-control-sm">
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Batch --}}
                        <div class="sp-collapse-trigger mt-2" id="batch-option">
                            @if($lims_product_data->is_batch)
                                <input name="is_batch" type="checkbox" id="is-batch" value="1" checked>
                            @else
                                <input name="is_batch" type="checkbox" id="is-batch" value="1">
                            @endif
                            &nbsp; {{__('db.This product has batch and expired date')}}
                        </div>

                        {{-- IMEI --}}
                        <div class="sp-collapse-trigger mt-2" id="imei-option">
                            @if($lims_product_data->is_imei)
                                <input name="is_imei" type="checkbox" id="is-imei" value="1" checked>
                            @else
                                <input name="is_imei" type="checkbox" id="is-imei" value="1">
                            @endif
                            &nbsp; {{__('db.This product has IMEI or Serial numbers')}}
                        </div>
                    </div>
                </div>

                {{-- ── 7. Combo Products (shown only when type=combo) ── --}}
                <div id="combo" class="sp-card" style="display:none;">
                    <div class="sp-card-header">
                        <i class="ti ti-layers-linked section-icon"></i>
                        <h6>{{__('db.Combo Products')}}</h6>
                    </div>
                    <div class="sp-card-body">
                        <div class="form-group">
                            <label>{{__('db.add_product')}}</label>
                            <div class="search-box input-group mb-3">
                                <button class="btn btn-secondary" type="button"><i class="ti ti-barcode"></i></button>
                                <input type="text" name="product_code_name" id="lims_productcodeSearch"
                                    placeholder="{{__('db.Please type product code and select')}}" class="form-control" />
                            </div>
                        </div>
                        <label>{{__('db.Combo Products')}}</label>
                        <div class="table-responsive">
                            <table id="myTable" class="table table-hover order-list">
                                <thead>
                                    <tr>
                                        <th>{{__('db.product')}}</th>
                                        <th>{{__('db.Wastage Percent')}}</th>
                                        <th>{{__('db.Quantity')}}</th>
                                        <th>{{__('db.Unit Cost')}}</th>
                                        <th>{{__('db.Unit Price')}}</th>
                                        <th>{{__('db.Sub Total')}}</th>
                                        <th><i class="ti ti-trash"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($lims_product_data->type == 'combo')
                                    <?php
                                        $product_list = explode(",", $lims_product_data->product_list);
                                        $wastage_percent = explode(",", $lims_product_data->wastage_percent);
                                        $qty_list = explode(",", $lims_product_data->qty_list);
                                        $variant_list = explode(",", $lims_product_data->variant_list);
                                        $price_list = explode(",", $lims_product_data->price_list);
                                    ?>
                                    @foreach($product_list as $key=>$id)
                                    <tr>
                                        <?php
                                            $product = App\Models\Product::find($id);
                                            $combo_unit = App\Models\Unit::query()->where('id',$product->unit_id)->orWhere('base_unit',$product->unit_id)->get()->unique('id');
                                            if($lims_product_data->variant_list && $variant_list[$key]) {
                                                $product_variant_data = App\Models\ProductVariant::select('item_code')->FindExactProduct($id, $variant_list[$key])->first();
                                                $product->code = $product_variant_data->item_code;
                                            } else {
                                                $variant_list[$key] = "";
                                            }
                                        ?>
                                        <td>{{$product->name}} [{{$product->code}}]</td>
                                        <td>
                                            <div class="input-group">
                                                <input type="number" class="form-control wastage_percent" name="wastage_percent[]" value="{{@$wastage_percent[$key] ?? 0 }}" min="0" step="any"/>
                                                <div class="input-group-append">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="input-group" style="max-width: unset">
                                                <input type="number" class="form-control qty" min="0.000001" name="product_qty[]"
                                                    value="{{ $qty_list[$key] ?? 1 }}" step="any" placeholder="Qty" aria-label="Quantity">
                                                <div class="input-group-append">
                                                    <select name="combo_unit_id[]" style="width: 112px;"
                                                            class="btn btn-outline-secondary form-control combo_unit_id"
                                                            onchange="calculate_price()">
                                                        @foreach ($combo_unit as $row)
                                                            <option value="{{ $row->id }}"
                                                                    data-operation_value="{{ $row->operation_value }}"
                                                                    data-operator="{{ $row->operator }}"
                                                                    @if ($lims_product_data->unit_id == $row->id) 'selected' @endif>
                                                                {{ $row->unit_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </td>
                                        <td><input type="number" class="form-control unit_cost" name="product_unit_cost[]" value="{{$product->cost}}" step="any"/></td>
                                        <td><input type="number" class="form-control unit_price" name="unit_price[]" value="{{$price_list[$key]}}" step="any"/></td>
                                        <td><input type="number" class="form-control subtotal" name="subtotal[]" value="0.00" step="any"/></td>
                                        <td><button type="button" class="ibtnDel btn btn-danger btn-sm">X</button></td>
                                        <input type="hidden" class="product-id" name="product_id[]" value="{{$id}}"/>
                                        <input type="hidden" class="variant-id" name="variant_id[]" value="{{$variant_list[$key]}}"/>
                                        <input type="hidden" class="product_unit_cost" name="" value="{{$product->cost}}"/>
                                        <input type="hidden" class="product_unit_price" name="" value="{{$price_list[$key]}}"/>
                                    </tr>
                                    @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- ── 8. Online Store & SEO (ecommerce / restaurant only) ── --}}
                @if(in_array('ecommerce',explode(',',gen_setting()->modules)))
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="ti ti-world section-icon"></i>
                        <h6>{{__('db.Online Store & SEO')}}</h6>
                    </div>
                    <div class="sp-card-body">
                        <div class="form-group">
                            <label>{{__('db.Product Tags')}}</label>
                            <input type="text" name="tags" class="form-control" value="{{$lims_product_data->tags}}">
                            <span class="validation-msg" id="tags-error"></span>
                        </div>
                        <div class="form-group">
                            <label>{{ __('Meta Title') }}</label>
                            <input type="text" name="meta_title" class="form-control" value="{{$lims_product_data->meta_title}}">
                        </div>
                        <div class="form-group">
                            <label>{{ __('Meta Description') }}</label>
                            <input type="text" name="meta_description" class="form-control" value="{{$lims_product_data->meta_description}}">
                        </div>
                        <div class="form-group related-section">
                            <label>{{__('db.Related Products')}}</label>
                            <input type="text" id="search_products" class="form-control">
                            <div class="search_result mt-1"></div>
                            <h6 class="mt-3 mb-2">{{__('db.Selected Items')}}</h6>
                            @if(isset($related_products))
                                <div class="selected_items">
                                    @foreach($related_products as $product)
                                    @php $image = explode(',', $product->image); @endphp
                                    <div data-id="{{$product->id}}">
                                        <img src="{{asset('images/product/small/')}}/{{$image[0]}}">
                                        <h4>{{$product->name}}</h4>
                                        <span class="remove_item"><i class="ti ti-x"></i></span>
                                    </div>
                                    @endforeach
                                </div>
                                <textarea class="selected_ids hidden no-tiny" name="products">{{$related_products}},</textarea>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                {{-- ── 8. Online Store & SEO (ecommerce / restaurant only) ── --}}
                @if(is_dir(base_path('Modules/Restaurant')))
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="ti ti-world section-icon"></i>
                        <h6>{{__('db.restaurant_settings')}}</h6>
                    </div>
                    <div class="sp-card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group top-fields">
                                    <label>{{__('db.Kitchen')}}</label>
                                    <div class="input-group pos">
                                        <input type="hidden" name="kitchen" value="{{$lims_product_data->kitchen_id}}" />
                                        <select id="kitchen_id" name="kitchen_id" class="selectpicker form-control" title="Select kitchen...">
                                            @if(isset($kitchen_list) && count($kitchen_list) > 0)
                                            @foreach($kitchen_list as $kitchen)
                                            <option value="{{$kitchen->id}}" @if($lims_product_data->kitchen_id == $kitchen->id) selected @endif>{{$kitchen->name}}</option>
                                            @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group top-fields">
                                    <label>{{__('db.Menu Type')}}</label>
                                    @php $menu_type = explode(',', $lims_product_data->menu_type); @endphp
                                    @if(isset($menu_type_list) && count($menu_type_list) > 0)
                                    @foreach($menu_type_list as $menu)
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" name="menu_type[]" value="{{$menu->id}}" @checked(in_array($menu->id, $menu_type)) class="form-check-input" id="menu_{{$menu->id}}"/>
                                            <label class="form-check-label" for="menu_{{$menu->id}}">&nbsp;{{$menu->name}}</label>
                                        </div>
                                    @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                        <p class="text-muted mb-0">{{__('Modifier groups are managed from Restaurant > Modifier Group.')}}</p>
                    </div>
                </div>
                @endif

                {{-- ── 9. Custom Fields ── --}}
                @if(count($custom_fields) > 0)
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="ti ti-list-details section-icon"></i>
                        <h6>{{__('db.additional_fields')}}</h6>
                    </div>
                    <div class="sp-card-body">
                        <div class="row">
                            @foreach($custom_fields as $field)
                            <?php $field_name = str_replace(' ', '_', strtolower($field->name)); ?>
                            @if(!$field->is_admin || \Auth::user()->role_id == 1)
                                <div class="{{'col-md-'.$field->grid_value}}">
                                    <div class="form-group">
                                        <label>{{$field->name}}</label>
                                        @if($field->type == 'text')
                                            <input type="text" name="{{$field_name}}" value="{{$lims_product_data->$field_name}}" class="form-control" @if($field->is_required){{'required'}}@endif>
                                        @elseif($field->type == 'number')
                                            <input type="number" name="{{$field_name}}" value="{{$lims_product_data->$field_name}}" class="form-control" @if($field->is_required){{'required'}}@endif>
                                        @elseif($field->type == 'textarea')
                                            <textarea rows="5" name="{{$field_name}}" value="{{$lims_product_data->$field_name}}" class="form-control" @if($field->is_required){{'required'}}@endif></textarea>
                                        @elseif($field->type == 'checkbox')
                                            <br>
                                            <?php
                                            $option_values = explode(",", $field->option_value);
                                            $field_values =  explode(",", $lims_product_data->$field_name);
                                            ?>
                                            @foreach($option_values as $value)
                                                <label>
                                                    <input type="checkbox" name="{{$field_name}}[]" value="{{$value}}" @if(in_array($value, $field_values)) checked @endif @if($field->is_required){{'required'}}@endif> {{$value}}
                                                </label>&nbsp;
                                            @endforeach
                                        @elseif($field->type == 'radio_button')
                                            <br>
                                            <?php $option_values = explode(",", $field->option_value); ?>
                                            @foreach($option_values as $value)
                                                <label class="radio-inline">
                                                    <input type="radio" name="{{$field_name}}" value="{{$value}}" @if($value == $lims_product_data->$field_name){{'checked'}}@endif @if($field->is_required){{'required'}}@endif> {{$value}}
                                                </label>&nbsp;
                                            @endforeach
                                        @elseif($field->type == 'select')
                                            <?php $option_values = explode(",", $field->option_value); ?>
                                            <select class="form-control" name="{{$field_name}}" @if($field->is_required){{'required'}}@endif>
                                                @foreach($option_values as $value)
                                                    <option value="{{$value}}" @if($value == $lims_product_data->$field_name){{'selected'}}@endif>{{$value}}</option>
                                                @endforeach
                                            </select>
                                        @elseif($field->type == 'multi_select')
                                            <?php
                                            $option_values = explode(",", $field->option_value);
                                            $field_values  = explode(",", $lims_product_data->$field_name);
                                            ?>
                                            <select class="form-control" name="{{$field_name}}[]" @if($field->is_required){{'required'}}@endif multiple>
                                                @foreach($option_values as $value)
                                                    <option value="{{$value}}" @if(in_array($value, $field_values)) selected @endif>{{$value}}</option>
                                                @endforeach
                                            </select>
                                        @elseif($field->type == 'date_picker')
                                            <input type="date" name="{{$field_name}}" value="{{$lims_product_data->$field_name}}" class="form-control" @if($field->is_required){{'required'}}@endif>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                @if(\Module::has('SocialCommerce') && \Module::find('SocialCommerce')->isEnabled())
                @php
                    $scSetting = \Modules\SocialCommerce\Entities\SocialProductSetting::where('product_id', $lims_product_data->id)->first();
                @endphp
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="ti ti-share section-icon"></i>
                        <h6>{{ __('db.Social Commerce') }}</h6>
                    </div>
                    <div class="sp-card-body">
                        <div class="row">
                            <div class="col-md-12 form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="sc_is_published" name="sc_is_published" value="1" {{ $scSetting && $scSetting->is_published ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="sc_is_published">{{ __('db.Publish to Social Commerce') }}</label>
                                </div>
                            </div>
                            <div class="col-md-12 form-group">
                                <label>{{ __('db.Custom Social Title') }}</label>
                                <input type="text" name="sc_social_title" class="form-control" value="{{ $scSetting ? $scSetting->social_title : '' }}" placeholder="{{ __('db.Leave blank to use product name') }}">
                            </div>
                            <div class="col-md-12 form-group">
                                <label>{{ __('db.Custom Social Description') }}</label>
                                <textarea name="sc_social_description" class="form-control" rows="3" placeholder="{{ __('db.Leave blank to use product description') }}">{{ $scSetting ? $scSetting->social_description : '' }}</textarea>
                            </div>
                            <div class="col-md-12 form-group">
                                <label>{{ __('db.Custom Social Image') }}</label>
                                <input type="file" name="sc_social_image" class="form-control">
                                <small class="text-muted">{{ __('db.Leave blank to use standard product image') }}</small>
                                @if($scSetting && $scSetting->social_image)
                                    <div class="mt-2">
                                        <img src="{{ asset('images/product/social/'.$scSetting->social_image) }}" alt="Social Image" width="100">
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

            </div>{{-- /main col --}}

            {{-- ═══════════ SIDEBAR ═══════════ --}}
            <div class="col-lg-4">

                {{-- ── Status ── --}}
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="ti ti-toggle-right section-icon"></i>
                        <h6>{{__('db.Status')}}</h6>
                    </div>
                    <div class="sp-card-body p-0">
                        <ul class="sp-switch-list">
                            <li id="featured">
                                <div class="sp-switch-meta">
                                    <span class="switch-label">{{__('db.Featured')}}</span>
                                    <span class="switch-hint">{{__('db.Featured product will be displayed in POS')}}</span>
                                </div>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="switch-featured" name="featured" value="1" {{ $lims_product_data->featured ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="switch-featured"></label>
                                </div>
                            </li>
                            <li>
                                <div class="sp-switch-meta">
                                    <span class="switch-label">{{__('db.Embedded Barcode')}}</span>
                                    <span class="switch-hint">{{__('db.Check this if this product will be used in weight scale machine')}}</span>
                                </div>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="switch-embeded" name="is_embeded" value="1" {{ $lims_product_data->is_embeded ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="switch-embeded"></label>
                                </div>
                            </li>
                            @if(in_array('ecommerce',explode(',',gen_setting()->modules)))
                            <li>
                                <div class="sp-switch-meta">
                                    <span class="switch-label">{{__('db.Sell Online')}}</span>
                                </div>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="is_online" name="is_online" value="1" {{$lims_product_data->is_online==1 ? 'checked':''}}>
                                    <label class="custom-control-label" for="is_online"></label>
                                </div>
                            </li>
                            @endif
                            @if(in_array('ecommerce',explode(',',gen_setting()->modules)))
                            <li>
                                <div class="sp-switch-meta">
                                    <span class="switch-label">{{__('db.In Stock')}}</span>
                                </div>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="in_stock" name="in_stock" value="1" {{$lims_product_data->in_stock==1 ? 'checked':''}}>
                                    <label class="custom-control-label" for="in_stock"></label>
                                </div>
                            </li>
                            @endif

                            @if(\Schema::hasColumn('products', 'woocommerce_product_id'))
                            <li>
                                <div class="sp-switch-meta">
                                    <span class="switch-label">{{__('db.Disable Woocommerce Sync')}}</span>
                                </div>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="is_sync_disable" name="is_sync_disable" value="1" {{$lims_product_data->is_sync_disable==1 ? 'checked':''}}>
                                    <label class="custom-control-label" for="is_sync_disable"></label>
                                </div>
                            </li>
                            @endif
                        </ul>
                    </div>
                </div>

                {{-- ── Organization ── --}}
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="ti ti-folder section-icon"></i>
                        <h6>{{__('db.Organization')}}</h6>
                    </div>
                    <div class="sp-card-body">
                        <div class="form-group">
                            <label>{{__('db.Brand')}}</label>
                            <input type="hidden" name="brand" value="{{ $lims_product_data->brand_id}}">
                            <select name="brand_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Brand...">
                                @foreach($lims_brand_list as $brand)
                                    <option value="{{$brand->id}}">{{$brand->title}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <input type="hidden" name="category" value="{{$lims_product_data->category_id}}">
                            <label>{{__('db.category')}} <span class="text-danger">*</span></label>
                            <select name="category_id" required class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Category...">
                                @foreach (get_active_categories() as $category)
                                    <option value="{{$category->id}}">{{$category->name}}</option>
                                @endforeach
                            </select>
                            <span class="validation-msg"></span>
                        </div>
                    </div>
                </div>

                {{-- ── Warranty & Guarantee ── --}}
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="ti ti-shield-check section-icon"></i>
                        <h6>{{__('db.Warranty & Guarantee')}}</h6>
                    </div>
                    <div class="sp-card-body">
                        <div class="form-group">
                            <label>{{ __('db.Warranty') }}</label>
                            <div class="d-flex" style="gap:8px;">
                                <input type="number" name="warranty" min="1" class="form-control" placeholder="{{ __('db.eg: 1') }}" value="{{ $lims_product_data->warranty }}">
                                <select name="warranty_type" class="form-control selectpicker" style="width:110px;">
                                    <option value="days" {{ $lims_product_data->warranty_type == 'days' ? 'selected' : '' }}>{{ __('db.Days') }}</option>
                                    <option value="months" {{ $lims_product_data->warranty_type == 'months' ? 'selected' : '' }}>{{ __('db.Months') }}</option>
                                    <option value="years" {{ $lims_product_data->warranty_type == 'years' ? 'selected' : '' }}>{{ __('db.Years') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label>{{ __('db.Guarantee') }}</label>
                            <div class="d-flex" style="gap:8px;">
                                <input type="number" name="guarantee" min="1" class="form-control" placeholder="{{ __('db.eg: 1') }}" value="{{ $lims_product_data->guarantee }}">
                                <select name="guarantee_type" class="form-control selectpicker" style="width:110px;">
                                    <option value="days" {{ $lims_product_data->guarantee_type == 'days' ? 'selected' : '' }}>{{ __('db.Days') }}</option>
                                    <option value="months" {{ $lims_product_data->guarantee_type == 'months' ? 'selected' : '' }}>{{ __('db.Months') }}</option>
                                    <option value="years" {{ $lims_product_data->guarantee_type == 'years' ? 'selected' : '' }}>{{ __('db.Years') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Inventory Settings ── --}}
                <div id="alert-qty" class="sp-card">
                    <div class="sp-card-header">
                        <i class="ti ti-bell section-icon"></i>
                        <h6>{{__('db.Inventory Settings')}}</h6>
                    </div>
                    <div class="sp-card-body">
                        <div class="form-group">
                            <label>
                                {{__('db.Daily Sale Objective')}}
                                <i class="ti ti-info-circle" data-toggle="tooltip" title="{{__('db.Minimum qty which must be sold in a day If not, you will be notified on dashboard But you have to set up the cron job properly for that Follow the documentation in that regard')}}"></i>
                            </label>
                            <input type="number" name="daily_sale_objective" class="form-control" step="any" value="{{$lims_product_data->daily_sale_objective}}">
                        </div>
                        <div class="form-group mb-0">
                            <label>{{__('db.Alert Quantity')}}</label>
                            <input type="number" name="alert_quantity" value="{{$lims_product_data->alert_quantity}}" class="form-control" step="any">
                        </div>
                    </div>
                </div>

            </div>{{-- /sidebar --}}
        </div>{{-- /row --}}
        </form>
    </div>{{-- /container --}}

</section>

@push('scripts')
<script type="text/javascript" src="{{ asset($asset_prefix . 'js/dropzone.js') }}"></script>
<script>
    // ── Type pill selector: sync to hidden select + trigger existing JS change handler ──
    (function () {
        var currentType = "{{$lims_product_data->type}}";
        $('.type-pill[data-value="' + currentType + '"]').addClass('active');
        $('.type-pill').on('click', function () {
            $('.type-pill').removeClass('active');
            $(this).addClass('active');
            $('select[name="type"]').val($(this).data('value')).trigger('change');
        });
    })();
</script>
@endpush

@endsection

@push('scripts')
<script type="text/javascript">

    calculate_price();

    function showAlert(message, type) {
        const container = $('#alert-container');
        const messageSpan = $('#alert-message');
        
        container.removeClass('alert-success alert-danger alert-warning alert-info d-none');
        container.addClass(`alert-${type}`);
        messageSpan.text(message);
        
        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 'slow');
        
        // Ensure visible
        container.show();
        
        // Auto-fade after 8 seconds
        setTimeout(() => {
            container.fadeOut('slow');
        }, 8000);
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });


    @if(in_array('ecommerce',explode(',',gen_setting()->modules)))
    $('#search_products').on('input', function() {
        var item = $(this).val();
        $('.search_result').html('<div class="d-block text-center"><div class="spinner-border text-secondary" role="status"><span class="sr-only">Loading...</span></div></div>');

        if(item.length >= 3){
            $.ajax({
                type: "get",
                url: "{{url('search')}}/" + item,
                success: function(data) {
                    $('.search_result').html('').css('height','200px');
                    $.each(data,function(key, value){
                        var image = value.image.split(',');
                        $('.search_result').append('<div data-id="'+value.id+'"><img src="{{asset("images/product/small/")}}/'+image[0]+'"><h4>'+value.name+'</h4><i class="ti ti-checkmark d-none"></i></div>')
                    })
                }
            })
        } else if (item.length < 3) {
            $('.search_result').html('');
        }
    });

    $(document).on('click','.search_result div',function(){
        $(this).find('i').removeClass('d-none');
        var selected_item = '<div data-id="'+$(this).data('id')+'">'+$(this).html()+'<span class="remove_item"><i class="ti ti-x"></i></span></div>';
        if ($('.selected_ids').html().indexOf($(this).data('id')) === -1){
            $('.selected_items').prepend(selected_item);
            $('.selected_ids').append($(this).data('id')+',');
            $('.selected_items .ti ti-checkmark').addClass('d-none');
        }
    });

    $(document).on('click','.remove_item',function(){
        var item = $(this).parent().remove();
        var remove_id = $(this).parent().data('id');
        var selected_ids = $('.selected_ids').html().replace(remove_id+',','');
        $('.selected_ids').html(selected_ids);

    });
    @endif

    @if(in_array('restaurant',explode(',',gen_setting()->modules)))
    var kitchen = $("input[name='kitchen']").val();
    $('select[name=kitchen_id]').val(kitchen);

    @endif

    $("ul#product").siblings('a').attr('aria-expanded','true');
    $("ul#product").addClass("show");
    var product_id = <?php echo json_encode($lims_product_data->id) ?>;
    var is_batch = <?php echo json_encode($lims_product_data->is_batch) ?>;
    var is_variant = <?php echo json_encode($lims_product_data->is_variant) ?>;
    var redirectUrl = <?php echo json_encode(url('products')); ?>;
    var variantPlaceholder = <?php echo json_encode(__('db.Enter variant value seperated by comma')); ?>;
    var variantIds = [];
    var combinations = [];
    var oldCombinations = [];
    var step;
    var count = 1;
    var customizedVariantCode = 1;
    var noOfVariantValue = <?php echo json_encode($noOfVariantValue); ?>;

    $('[data-toggle="tooltip"]').tooltip();

    $(".remove-img").on("click", function () {
        $(this).closest("tr").remove();
    });

    $("#digital").hide();
    $("#combo").hide();
    $("select[name='type']").val($("input[name='type_hidden']").val());
    variantShowHide();
    diffPriceShowHide();
    if(is_batch)
        $("#variant-option").hide();
    if(is_variant) {
        var customizedVariantCode = 0;
        $("#batch-option").hide();
    }

    if($("input[name='type_hidden']").val() == "digital"){
        $("input[name='cost']").prop('required',false);
        $("select[name='unit_id']").prop('required',false);
        hide();
        $("#digital").show();
    }
    else if($("input[name='type_hidden']").val() == "service"){
        $("input[name='cost']").prop('required',false);
        $("select[name='unit_id']").prop('required',false);
        hide();
        $("#variant-section, #variant-option").hide();
    }
    else if($("input[name='type_hidden']").val() == "combo"){
        hide();
        $("#cost").show();
        $("#unit").show();
        $("#combo").show();
    }

    var promotion = $("input[name='promotion_hidden']").val();
    if(promotion){
        $("input[name='promotion']").prop('checked', true);
        $("#promotion_price").show(300);
        $("#start_date").show(300);
        $("#last_date").show(300);
    }
    else {
        $("#promotion_price").hide(300);
        $("#start_date").hide(300);
        $("#last_date").hide(300);
    }

    $('#genbutton').on("click", function(){
      $.get('../gencode', function(data){
        $("input[name='code']").val(data);
      });
    });

    $('.selectpicker').selectpicker({
      style: 'btn-link',
    });

    $('.add-more-variant').on("click", function() {
        var htmlText = '<div class="row"><div class="col-md-4 form-group mt-2"><label>Option *</label><input type="text" name="variant_option[]" class="form-control variant-field" placeholder="Size, Color etc..."></div><div class="col-md-7 form-group mt-2"><label>Value *</label><input type="text" name="variant_value[]" class="type-variant form-control variant-field"></div><div class="col-sm-1 form-group mt-2" style="display:flex;flex-direction:column;align-items:center;justify-content:end;"><button type="button" class="delVarOption btn btn-danger btn-sm mr-3"><i class="ti ti-x"></i></button></div></div>';
        $("#variant-input-section").append(htmlText);
        $('.type-variant').tagsInput();
    });

    $(document).on("click", '.delVarOption', function() {
        $(this).parent().parent().remove();
        $('.type-variant').tagsInput();
    });

    //start variant related js
    window.isLoadingVariants = true;

    $(function() {
        $('.type-variant').tagsInput();
        window.isLoadingVariants = false;
    });

    (function($) {
        var delimiter = [];
        var inputSettings = [];
        var callbacks = [];

        $.fn.addTag = function(value, options) {
            if(count == noOfVariantValue)
                customizedVariantCode = 1;
            options = jQuery.extend({
                focus: false,
                callback: true
            }, options);

            this.each(function() {
                var id = $(this).attr('id');
                var tagslist = $(this).val().split(_getDelimiter(delimiter[id]));
                if (tagslist[0] === '') tagslist = [];

                value = jQuery.trim(value);

                if ((inputSettings[id].unique && $(this).tagExist(value)) || !_validateTag(value, inputSettings[id], tagslist, delimiter[id])) {
                    $('#' + id + '_tag').addClass('error');
                    return false;
                }

                $('<span>', {class: 'tag'}).append(
                    $('<span>', {class: 'tag-text'}).text(value),
                    $('<button>', {class: 'tag-remove'}).click(function() {
                        return $('#' + id).removeTag(encodeURI(value));
                    })
                ).insertBefore('#' + id + '_addTag');

                tagslist.push(value);

                $('#' + id + '_tag').val('');
                if (options.focus) {
                    $('#' + id + '_tag').focus();
                } else {
                    $('#' + id + '_tag').blur();
                }

                $.fn.tagsInput.updateTagsField(this, tagslist);

                if (options.callback && callbacks[id] && callbacks[id]['onAddTag']) {
                    var f = callbacks[id]['onAddTag'];
                    f.call(this, this, value);
                }

                if (callbacks[id] && callbacks[id]['onChange']) {
                    var i = tagslist.length;
                    var f = callbacks[id]['onChange'];
                    f.call(this, this, value);
                }

                $(".type-variant").each(function(index) {
                    variantIds.splice(index, 1, $(this).attr('id'));
                });
                count++;
                // prevent running on page load
                if (customizedVariantCode && !window.isLoadingVariants) {
                    first_variant_values = $('#'+variantIds[0]).val().split(_getDelimiter(delimiter[variantIds[0] ]));
                    combinations = first_variant_values;
                    step = 1;
                    while (step < variantIds.length) {
                        var newCombinations = [];
                        for (var i = 0; i < combinations.length; i++) {
                            new_variant_values = $('#'+variantIds[step]).val().split(_getDelimiter(delimiter[variantIds[step]]));
                            for (var j = 0; j < new_variant_values.length; j++) {
                                newCombinations.push(combinations[i] + '/' + new_variant_values[j]);
                            }
                        }
                        combinations = newCombinations;
                        step++;
                    }

                    var rownumber = $('table.variant-list tbody tr:last').index();
                    if (rownumber > -1) {
                        oldCombinations = [];
                        oldAdditionalCost = [];
                        oldAdditionalPrice = [];
                        oldProductVariantId = [];
                        $(".variant-name").each(function(i) {
                            oldCombinations.push($(this).val());
                            oldProductVariantId.push($('table.variant-list tbody tr:nth-child(' + (i + 1) + ')').find('.product-variant-id').val());
                            oldAdditionalCost.push($('table.variant-list tbody tr:nth-child(' + (i + 1) + ')').find('.additional-cost').val());
                            oldAdditionalPrice.push($('table.variant-list tbody tr:nth-child(' + (i + 1) + ')').find('.additional-price').val());
                        });
                    }

                    // Collect existing data BEFORE removing the tbody
                    var oldData = [];
                    $('table.variant-list tbody tr').each(function() {
                        oldData.push({
                            name: $(this).find('.variant-name').val(),
                            code: $(this).find('.item-code').val(),
                            cost: $(this).find('.additional-cost').val(),
                            price: $(this).find('.additional-price').val(),
                        });
                    });

                    $("table.variant-list tbody").remove();
                    var newBody = $("<tbody>");
                    for (var i = 0; i < combinations.length; i++) {
                        var variant_name = combinations[i];
                        var newRow = $("<tr>");
                        var cols = '';

                        // Match existing variant (if any)
                        var existing = oldData.find(function(item) {
                            return item.name === variant_name;
                        });

                        // Keep variant name
                        cols += '<td>' + variant_name +
                            '<input type="hidden" class="variant-name" name="variant_name[]" value="' + variant_name + '" /></td>';

                        // Preserve or auto-generate item_code
                        var item_code = existing ? existing.code : (variant_name + '-' + $("#code").val());
                        cols += '<td><input type="text" class="form-control item-code" name="item_code[]" value="' + (item_code ?? '') + '" /></td>';

                        // Preserve or initialize cost/price
                        var cost = existing ? existing.cost : '';
                        var price = existing ? existing.price : '';
                        cols += '<td><input type="number" class="form-control additional-cost" name="additional_cost[]" value="' + (cost ?? '') + '" step="any" /></td>';
                        cols += '<td><input type="number" class="form-control additional-price" name="additional_price[]" value="' + (price ?? '') + '" step="any" /></td>';

                        newRow.append(cols);
                        newBody.append(newRow);
                    }

                    $("table.variant-list").append(newBody);
                }
            });

            return false;
        };

        $.fn.removeTag = function(value) {
            value = decodeURI(value);

            this.each(function() {
                var id = $(this).attr('id');

                var old = $(this).val().split(_getDelimiter(delimiter[id]));

                $('#' + id + '_tagsinput .tag').remove();

                var str = '';
                for (i = 0; i < old.length; ++i) {
                    if (old[i] != value) {
                        str = str + _getDelimiter(delimiter[id]) + old[i];
                    }
                }

                $.fn.tagsInput.importTags(this, str);

                if (callbacks[id] && callbacks[id]['onRemoveTag']) {
                    var f = callbacks[id]['onRemoveTag'];
                    f.call(this, this, value);
                }
            });

            return false;
        };

        $.fn.tagExist = function(val) {
            var id = $(this).attr('id');
            var tagslist = $(this).val().split(_getDelimiter(delimiter[id]));
            return (jQuery.inArray(val, tagslist) >= 0);
        };

        $.fn.importTags = function(str) {
            var id = $(this).attr('id');
            $('#' + id + '_tagsinput .tag').remove();
            $.fn.tagsInput.importTags(this, str);
        };

        $.fn.tagsInput = function(options) {
            var settings = jQuery.extend({
                interactive: true,
                placeholder: variantPlaceholder,
                minChars: 0,
                maxChars: null,
                limit: null,
                validationPattern: null,
                width: 'auto',
                height: 'auto',
                autocomplete: null,
                hide: true,
                delimiter: ',',
                unique: true,
                removeWithBackspace: true
            }, options);

            var uniqueIdCounter = 0;

            this.each(function() {
                if (typeof $(this).data('tagsinput-init') !== 'undefined') return;

                $(this).data('tagsinput-init', true);

                if (settings.hide) $(this).hide();

                var id = $(this).attr('id');
                if (!id || _getDelimiter(delimiter[$(this).attr('id')])) {
                    id = $(this).attr('id', 'tags' + new Date().getTime() + (++uniqueIdCounter)).attr('id');
                }

                var data = jQuery.extend({
                    pid: id,
                    real_input: '#' + id,
                    holder: '#' + id + '_tagsinput',
                    input_wrapper: '#' + id + '_addTag',
                    fake_input: '#' + id + '_tag'
                }, settings);

                delimiter[id] = data.delimiter;
                inputSettings[id] = {
                    minChars: settings.minChars,
                    maxChars: settings.maxChars,
                    limit: settings.limit,
                    validationPattern: settings.validationPattern,
                    unique: settings.unique
                };

                if (settings.onAddTag || settings.onRemoveTag || settings.onChange) {
                    callbacks[id] = [];
                    callbacks[id]['onAddTag'] = settings.onAddTag;
                    callbacks[id]['onRemoveTag'] = settings.onRemoveTag;
                    callbacks[id]['onChange'] = settings.onChange;
                }

                var markup = $('<div>', {id: id + '_tagsinput', class: 'tagsinput'}).append(
                    $('<div>', {id: id + '_addTag'}).append(
                        settings.interactive ? $('<input>', {id: id + '_tag', class: 'tag-input', value: '', placeholder: settings.placeholder}) : null
                    )
                );

                $(markup).insertAfter(this);

                $(data.holder).css('width', settings.width);
                $(data.holder).css('min-height', settings.height);
                $(data.holder).css('height', settings.height);

                if ($(data.real_input).val() !== '') {
                    $.fn.tagsInput.importTags($(data.real_input), $(data.real_input).val());
                }

                if (!settings.interactive) return;

                $(data.fake_input).val('');
                $(data.fake_input).data('pasted', false);

                $(data.fake_input).on('focus', data, function(event) {
                    $(data.holder).addClass('focus');
                    if ($(this).val() === '') {
                        $(this).removeClass('error');
                    }
                });

                $(data.fake_input).on('blur', data, function(event) {
                    $(data.holder).removeClass('focus');
                });

                if (settings.autocomplete !== null && jQuery.ui.autocomplete !== undefined) {
                    $(data.fake_input).autocomplete(settings.autocomplete);
                    $(data.fake_input).on('autocompleteselect', data, function(event, ui) {
                        $(event.data.real_input).addTag(ui.item.value, {
                            focus: true,
                            unique: settings.unique
                        });
                        return false;
                    });
                    $(data.fake_input).on('keypress', data, function(event) {
                        if (_checkDelimiter(event)) {
                            $(this).autocomplete("close");
                        }
                    });
                } else {
                    $(data.fake_input).on('blur', data, function(event) {
                        $(event.data.real_input).addTag($(event.data.fake_input).val(), {
                            focus: true,
                            unique: settings.unique
                        });
                        return false;
                    });
                }

                $(data.fake_input).on('keypress', data, function(event) {
                    if (_checkDelimiter(event)) {
                        event.preventDefault();
                        $(event.data.real_input).addTag($(event.data.fake_input).val(), {
                            focus: true,
                            unique: settings.unique
                        });
                        return false;
                    }
                });

                $(data.fake_input).on('paste', function () {
                    $(this).data('pasted', true);
                });

                $(data.fake_input).on('input', data, function(event) {
                    if (!$(this).data('pasted')) return;
                    $(this).data('pasted', false);
                    var value = $(event.data.fake_input).val();
                    value = value.replace(/\n/g, '');
                    value = value.replace(/\s/g, '');
                    var tags = _splitIntoTags(event.data.delimiter, value);
                    if (tags.length > 1) {
                        for (var i = 0; i < tags.length; ++i) {
                            $(event.data.real_input).addTag(tags[i], {
                                focus: true,
                                unique: settings.unique
                            });
                        }
                        return false;
                    }
                });

                data.removeWithBackspace && $(data.fake_input).on('keydown', function(event) {
                    if (event.keyCode == 8 && $(this).val() === '') {
                         event.preventDefault();
                         var lastTag = $(this).closest('.tagsinput').find('.tag:last > span').text();
                         var id = $(this).attr('id').replace(/_tag$/, '');
                         $('#' + id).removeTag(encodeURI(lastTag));
                         $(this).trigger('focus');
                    }
                });

                $(data.fake_input).keydown(function(event) {
                    if (jQuery.inArray(event.keyCode, [13, 37, 38, 39, 40, 27, 16, 17, 18, 225]) === -1) {
                        $(this).removeClass('error');
                    }
                });
            });

            return this;
        };

        $.fn.tagsInput.updateTagsField = function(obj, tagslist) {
            var id = $(obj).attr('id');
            $(obj).val(tagslist.join(_getDelimiter(delimiter[id])));
        };

        $.fn.tagsInput.importTags = function(obj, val) {
            $(obj).val('');
            var id = $(obj).attr('id');
            var tags = _splitIntoTags(delimiter[id], val);
            for (i = 0; i < tags.length; ++i) {
                $(obj).addTag(tags[i], {
                    focus: false,
                    callback: false
                });
            }
            if (callbacks[id] && callbacks[id]['onChange']) {
                var f = callbacks[id]['onChange'];
                f.call(obj, obj, tags);
            }
        };

        var _getDelimiter = function(delimiter) {
            if (typeof delimiter === 'undefined') {
                return delimiter;
            } else if (typeof delimiter === 'string') {
                return delimiter;
            } else {
                return delimiter[0];
            }
        };

        var _validateTag = function(value, inputSettings, tagslist, delimiter) {
            var result = true;
            if (value === '') result = false;
            if (value.length < inputSettings.minChars) result = false;
            if (inputSettings.maxChars !== null && value.length > inputSettings.maxChars) result = false;
            if (inputSettings.limit !== null && tagslist.length >= inputSettings.limit) result = false;
            if (inputSettings.validationPattern !== null && !inputSettings.validationPattern.test(value)) result = false;
            if (typeof delimiter === 'string') {
                if (value.indexOf(delimiter) > -1) result = false;
            } else {
                $.each(delimiter, function(index, _delimiter) {
                    if (value.indexOf(_delimiter) > -1) result = false;
                    return false;
                });
            }
            return result;
        };

        var _checkDelimiter = function(event) {
            var found = false;
            if (event.which === 13) { return true; }
            if (typeof event.data.delimiter === 'string') {
                if (event.which === event.data.delimiter.charCodeAt(0)) { found = true; }
            } else {
                $.each(event.data.delimiter, function(index, delimiter) {
                    if (event.which === delimiter.charCodeAt(0)) { found = true; }
                });
            }
            return found;
         };

         var _splitIntoTags = function(delimiter, value) {
             if (value === '') return [];
             if (typeof delimiter === 'string') {
                 return value.split(delimiter);
             } else {
                 var tmpDelimiter = '∞';
                 var text = value;
                 $.each(delimiter, function(index, _delimiter) {
                     text = text.split(_delimiter).join(tmpDelimiter);
                 });
                 return text.split(tmpDelimiter);
             }
             return [];
         };
    })(jQuery);
    //end of variant related js

    tinymce.init({
      selector: 'textarea:not(.no-tiny)',
      height: 130,
      plugins: [
        'advlist autolink lists link image charmap print preview anchor textcolor',
        'searchreplace visualblocks code fullscreen',
        'insertdatetime media table contextmenu paste code wordcount'
      ],
      toolbar: 'insert | undo redo |  formatselect | bold italic backcolor  | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat',
      branding:false
    });

    var barcode_symbology = $("input[name='barcode_symbology_hidden']").val();
    $('select[name=barcode_symbology]').val(barcode_symbology);

    var brand = $("input[name='brand']").val();
    $('select[name=brand_id]').val(brand);

    var cat = $("input[name='category']").val();
    $('select[name=category_id]').val(cat);

    if($("input[name='unit']").val()) {
        $('select[name=unit_id]').val($("input[name='unit']").val());
        populate_unit($("input[name='unit']").val());
    }

    var tax = $("input[name='tax']").val();
    if(tax)
        $('select[name=tax_id]').val(tax);

    var tax_method = $("input[name='tax_method_id']").val();
    $('select[name=tax_method]').val(tax_method);
    $('.selectpicker').selectpicker('refresh');

    $('select[name="type"]').on('change', function() {
        if($(this).val() == 'combo'){
            $("input[name='cost']").prop('required',false);
            $("select[name='unit_id']").prop('required',false);
            hide();
            $("#cost").show(300);
            $("#unit").show(300);
            $("#digital").hide();
            $("#variant-section, #variant-option, #diffPrice-option, #diffPrice-section").hide(300);
            $("#combo").show();
            $("input[name='price']").prop('disabled',true);
        }
        else if($(this).val() == 'digital'){
            $("input[name='cost']").prop('required',false);
            $("select[name='unit_id']").prop('required',false);
            $("input[name='file']").prop('required',true);
            hide();
            $("#combo").hide();
            $("#digital").show();
            $("#variant-section, #variant-option, #diffPrice-option, #diffPrice-section").hide(300);
            $("input[name='price']").prop('disabled',false);
        }
        else if($(this).val() == 'service') {
            $("input[name='cost']").prop('required',false);
            $("select[name='unit_id']").prop('required',false);
            $("input[name='file']").prop('required',true);
            hide();
            $("#combo").hide(300);
            $("#digital").hide(300);
            $("input[name='price']").prop('disabled',false);
            $("input[name='is_variant']").prop("checked", false);
            $("#variant-section, #variant-option").hide(300);
        }
        else if($(this).val() == 'standard'){
            $("input[name='cost']").prop('required',true);
            $("select[name='unit_id']").prop('required',true);
            $("input[name='file']").prop('required',false);
            $("#cost").show();
            $("#unit").show();
            $("#alert-qty").show();
            $("#variant-option").show(300);
            $("#diffPrice-option").show(300);
            $("#digital").hide();
            $("#combo").hide();
            $("input[name='price']").prop('disabled',false);
        }
    });

    $('select[name="unit_id"]').on('change', function() {
        unitID = $(this).val();
        if(unitID) {
            populate_unit_second(unitID);
        }else{
            $('select[name="sale_unit_id"]').empty();
            $('select[name="purchase_unit_id"]').empty();
        }
    });

    <?php $productArray = []; ?>
    var lims_product_code = [
        @foreach($lims_product_list_without_variant as $product)
        <?php
            $productArray[] = htmlspecialchars($product->code) . '(' . preg_replace('/[\n\r]/', "<br>", htmlspecialchars($product->name)). ')';
        ?>
        @endforeach
        @foreach($lims_product_list_with_variant as $product)
            <?php
                $productArray[] = htmlspecialchars($product->item_code) . '|' . preg_replace('/[\n\r]/', "<br>", htmlspecialchars($product->name));
            ?>
        @endforeach
            <?php
                echo  '"'.implode('","', $productArray).'"';
            ?> ];

    var lims_productcodeSearch = $('#lims_productcodeSearch');

    lims_productcodeSearch.autocomplete({
        source: function(request, response) {
            var matcher = new RegExp(".?" + $.ui.autocomplete.escapeRegex(request.term), "i");
            response($.grep(lims_product_code, function(item) {
                return matcher.test(item);
            }));
        },
        select: function(event, ui) {
            var data = ui.item.value;
            $.ajax({
                type: 'GET',
                url: '../lims_product_search',
                data: {
                    data: data
                },
                success: function(responseData) {
                    data = responseData[0];
                    var flag = 1;
                    $(".product-id").each(function() {
                        if ($(this).val() == data[8]) {
                            showAlert('Duplicate input is not allowed!', 'danger');
                            flag = 0;
                        }
                    });
                    $("input[name='product_code_name']").val('');
                    if(flag){
                        var newRow = $("<tr>");
                        var cols = '';
                        cols += '<td>' + data[0] +' [' + data[1] + ']</td>';
                        cols += `<td>
                                <div class="input-group">
                                    <input type="number" name="wastage_percent[]" class="form-control wastage_percent" value="0"/>
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </td>`;
                        cols += `<td>
                                    <div class="input-group" style="max-width: unset">
                                        <input type="number"
                                            class="form-control qty"
                                            min="0.000001"
                                            name="product_qty[]"
                                            value="1"
                                            step="any"
                                            placeholder="Qty"
                                            aria-label="Quantity">
                                        <div class="input-group-append">
                                            `+data[13]+`
                                        </div>
                                    </div>
                                </td>`;

                        cols += '<td><input type="number" class="form-control unit_cost" name="product_unit_cost[]" value="' + data[10] + '"/></td>';
                        cols += '<td><input type="number" class="form-control unit_price" name="unit_price[]" value="' + data[2] + '" step="any"/></td>';
                         cols += '<td><input type="number" class="form-control subtotal" name="subtotal[]" value="' + data[2] + '" step="any"/></td>';
                        cols += '<td><button type="button" class="ibtnDel btn btn-sm btn-danger">X</button></td>';
                        cols += '<input type="hidden" class="product-id" name="product_id[]" value="' + data[8] + '"/>';
                        cols += '<input type="hidden" class="" name="variant_id[]" value="' + data[9] + '"/>';
                        cols += '<input type="hidden" class="product_unit_cost" name="" value="' + data[10] + '"/>';
                        cols += '<input type="hidden" class="product_unit_price" name="" value="' + data[2] + '"/>';

                        newRow.append(cols);
                        $("table.order-list tbody").append(newRow);
                        calculate_price();
                    }
                }
            });
        }
    });

    //Change quantity or unit price
    $("#myTable").on('input', '.qty , .unit_cost, .unit_price', function() {
        calculate_price();
    });

    //Delete product
    $("table.order-list tbody").on("click", ".ibtnDel", function(event) {
        $(this).closest("tr").remove();
        calculate_price();
    });

    function calculate_price() {
        var price = 0;
        var cost = 0
        $(".qty").each(function() {
            rowindex = $(this).closest('tr').index();
            quantity =  $(this).val();
            unit_price = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product_unit_price').val();
            product_unit_cost = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product_unit_cost').val();
            unit_cost = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .unit_cost').val();
            cost += quantity * unit_cost;

            let $row = $(this).closest('tr');
            let qty = parseFloat($(this).val()) || 0;

            let $selectedOption = $row.find('.combo_unit_id option:selected');
            let operator = $selectedOption.data('operator');
            let operationValue = parseFloat($selectedOption.data('operation_value')) || 1;

            let convertedQty = quantity;
            if (operator === '*') {
                convertedQty = quantity * operationValue;
            } else if (operator === '/') {
                convertedQty = quantity / operationValue;
            }

            let subtotal = convertedQty * unit_price;
            cost += convertedQty * unit_cost;
            price += subtotal;
            $row.find('.subtotal').val(subtotal.toFixed(2));
            $row.find('.unit_price').val(subtotal.toFixed(2));
            $row.find('.unit_cost').val((convertedQty * product_unit_cost).toFixed(2));
        });
        if (price > 0)
            $('input[name="price"]').val(price.toFixed(2));

        let total_cost = 0;
        $('input[name="product_unit_cost[]"]').each(function() {
            let value = parseFloat($(this).val()) || 0;
            total_cost += value;
        });
        if (total_cost > 0)
            $('input[name="cost"]').val(total_cost.toFixed(2));

    }

    function hide() {
        $("#cost").hide();
        $("#unit").hide();
        $("#alert-qty").hide();
    }

    function populate_unit(unitID){
        $.ajax({
            url: '../saleunit/'+unitID,
            type: "GET",
            dataType: "json",

            success:function(data) {
                  $('select[name="sale_unit_id"]').empty();
                  $('select[name="purchase_unit_id"]').empty();
                  $.each(data, function(key, value) {
                      $('select[name="sale_unit_id"]').append('<option value="'+ key +'">'+ value +'</option>');
                      $('select[name="purchase_unit_id"]').append('<option value="'+ key +'">'+ value +'</option>');
                  });
                  $('.selectpicker').selectpicker('refresh');
                  var sale_unit = $("input[name='sale_unit']").val();
                  var purchase_unit = $("input[name='purchase_unit']").val();
                $('#sale-unit').val(sale_unit);
                $('select[name=purchase_unit_id]').val(purchase_unit);
                $('.selectpicker').selectpicker('refresh');
            },
        });
    }

    function populate_unit_second(unitID){
        $.ajax({
            url: '../saleunit/'+unitID,
            type: "GET",
            dataType: "json",
            success:function(data) {
                  $('select[name="sale_unit_id"]').empty();
                  $('select[name="purchase_unit_id"]').empty();
                  $.each(data, function(key, value) {
                      $('select[name="sale_unit_id"]').append('<option value="'+ key +'">'+ value +'</option>');
                      $('select[name="purchase_unit_id"]').append('<option value="'+ key +'">'+ value +'</option>');
                  });
                  $('.selectpicker').selectpicker('refresh');
            },
        });
    };

    let marginType = $('select[name="profit_margin_type"]').val();

    // When margin type changes
    $('select[name="profit_margin_type"]').on("change", function () {
        marginType = $(this).val();

        // Update placeholder dynamically
        $('input[name="profit_margin"]').attr(
            "placeholder",
            marginType === "percentage" ? "% value" : "Flat amount"
        );

        recalcPrice();
    }).trigger("change");

    // Recalculate price based on cost & margin
    function recalcPrice() {
        if ($('select[name="type"]').val() === 'service') return;
        let cost = parseFloat($('input[name="cost"]').val()) || 0;
        let margin = parseFloat($('input[name="profit_margin"]').val()) || 0;

        let price =
            marginType === "percentage"
                ? cost + (cost * margin / 100)
                : cost + margin;

        $('input[name="price"]').val(price.toFixed(2)).trigger("change");
    }

    // Recalculate margin when price changes manually
    function recalcMargin() {
        let cost = parseFloat($('input[name="cost"]').val()) || 0;
        let price = parseFloat($('input[name="price"]').val()) || 0;

        let margin =
            marginType === "percentage"
                ? ((price - cost) / cost * 100).toFixed(2)
                : (price - cost).toFixed(2);

        $('input[name="profit_margin"]').val(margin);
    }

    // When cost changes
    $('input[name="cost"]').on("input", function () {
        recalcPrice();
    });

    // When profit margin changes
    $('input[name="profit_margin"]').on("input", function () {
        recalcPrice();
    });

    // When price changes → update margin
    $('input[name="price"]').on("input", function () {
        recalcMargin();
    });

    // Warning UI for cost/price
    $('input[name="price"], input[name="cost"]').on("change keyup", function () {
        let curCost = parseFloat($('input[name="cost"]').val()) || 0;
        let curPrice = parseFloat($('input[name="price"]').val()) || 0;

        if (curCost <= 0) {
            $('#product-cost-warning').removeClass('d-none');
        } else {
            $('#product-cost-warning').addClass('d-none');
        }

        if (curPrice <= curCost) {
            $('#product-price-warning').removeClass('d-none');
        } else {
            $('#product-price-warning').addClass('d-none');
        }
    });

    $("input[name='is_batch']").on("change", function () {
        if ($(this).is(':checked')) {
            $("#variant-option").hide(300);
        }
        else
            $("#variant-option").show(300);
    });

    $("input[name='is_variant']").on("change", function () {
        variantShowHide();
    });

    $("input[name='is_diffPrice']").on("change", function () {
        diffPriceShowHide();
    });

    function variantShowHide() {
         if ($("#is-variant").is(':checked')) {
            $("#variant-section").show(300);
            $("#batch-option").hide(300);
            $(".variant-field").prop("required", true);
        }
        else {
            $("#variant-section").hide(300);
            $("#batch-option").show(300);
            $(".variant-field").prop("required", false);
        }
    };

    function diffPriceShowHide() {
         if ($("#is-diffPrice").is(':checked')) {
            $("#diffPrice-section").show(300);
        }
        else {
            $("#diffPrice-section").hide(300);
        }
    };

    $( "#promotion" ).on( "change", function() {
        if ($(this).is(':checked')) {
            $("#promotion_price").show();
            $("#start_date").show();
            $("#last_date").show();
        }
        else {
            $("#promotion_price").hide();
            $("#start_date").hide();
            $("#last_date").hide();
        }
    });

    var starting_date = $('#starting_date');
    starting_date.datepicker({
     format: "dd-mm-yyyy",
     startDate: "<?php echo date('d-m-Y'); ?>",
     autoclose: true,
     todayHighlight: true
     });

    var ending_date = $('#ending_date');
    ending_date.datepicker({
     format: "dd-mm-yyyy",
     startDate: "<?php echo date('d-m-Y'); ?>",
     autoclose: true,
     todayHighlight: true
     });

    //dropzone portion
    Dropzone.autoDiscover = false;

    jQuery.validator.setDefaults({
        errorPlacement: function (error, element) {
            if(error.html() == 'Select Category...')
                error.html('This field is required.');
            $(element).closest('div.form-group').find('.validation-msg').html(error.html());
        },
        highlight: function (element) {
            $(element).closest('div.form-group').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).closest('div.form-group').removeClass('has-error').addClass('has-success');
            $(element).closest('div.form-group').find('.validation-msg').html('');
        }
    });

    function validate() {
        var product_code = $("input[name='code']").val();
        var barcode_symbology = $('select[name="barcode_symbology"]').val();
        var exp = /^\d+$/;

        if(!(product_code.match(exp)) && (barcode_symbology == 'UPCA' || barcode_symbology == 'UPCE' || barcode_symbology == 'EAN8' || barcode_symbology == 'EAN13') ) {
            showAlert('Product code must be numeric.', 'danger');
            return false;
        }
        else if(product_code.match(exp)) {
            if(barcode_symbology == 'UPCA' && product_code.length > 11){
                showAlert('Product code length must be less than 12', 'danger');
                return false;
            }
            else if(barcode_symbology == 'EAN8' && product_code.length > 7){
                showAlert('Product code length must be less than 8', 'danger');
                return false;
            }
        }

        if( $("#type").val() == 'combo' ) {
            var rownumber = $('table.order-list tbody tr:last').index();
            if (rownumber < 0) {
                showAlert("Please insert product to table!", 'danger');
                return false;
            }
        }
        $("input[name='price']").prop('disabled',false);
        return true;
    }

    $(".dropzone").sortable({
        items:'.dz-preview',
        cursor: 'grab',
        opacity: 0.5,
        containment: '.dropzone',
        distance: 20,
        tolerance: 'pointer',
        stop: function () {
          var queue = myDropzone.getAcceptedFiles();
          newQueue = [];
          $('#imageUpload .dz-preview .dz-filename [data-dz-name]').each(function (count, el) {
                var name = el.innerHTML;
                queue.forEach(function(file) {
                    if (file.name === name) {
                        newQueue.push(file);
                    }
                });
          });
          myDropzone.files = newQueue;
        }
    });

    myDropzone = new Dropzone('div#imageUpload', {
        addRemoveLinks: true,
        autoProcessQueue: false,
        uploadMultiple: true,
        parallelUploads: 100,
        maxFilesize: 12,
        paramName: 'image',
        clickable: true,
        method: 'POST',
        url:'../update',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        renameFile: function(file) {
            var dt = new Date();
            var time = dt.getTime();
            return time + file.name;
        },
        acceptedFiles: ".jpeg,.jpg,.png,.gif",
        init: function () {
            var myDropzone = this;
            $('#submit-btn').on("click", function (e) {
                e.preventDefault();
                if ( $("#product-form").valid() && validate() ) {
                    tinyMCE.triggerSave();
                    $(this).attr('disabled','true').html('<span class="spinner-border text-light" role="status"></span> {{__("db.Saving")}}...');
                    if(myDropzone.getAcceptedFiles().length) {
                        myDropzone.processQueue();
                    }
                    else {
                        var formData = new FormData();
                        var data = $("#product-form").serializeArray();
                        $.each(data, function (key, el) {
                            formData.append(el.name, el.value);
                        });
                        var file = $('#file')[0].files;
                        if(file.length > 0)
                            formData.append('file',file[0]);
                        $.ajax({
                            type:'POST',
                            url:'../update',
                            data: formData,
                            contentType: false,
                            processData: false,
                            success:function(response) {
                                showAlert(response.message, 'success');
                                $('#submit-btn').removeAttr('disabled').html("{{__('db.submit')}}");
                            },
                            error:function(response) {
                              if(response.responseJSON.errors.name) {
                                  $("#name-error").text(response.responseJSON.errors.name);
                              }
                              else if(response.responseJSON.errors.code) {
                                  $("#code-error").text(response.responseJSON.errors.code);
                              }
                            },
                        });
                    }
                }
            });

            this.on('sending', function (file, xhr, formData) {
                var data = $("#product-form").serializeArray();
                $.each(data, function (key, el) {
                    formData.append(el.name, el.value);
                });
                var file = $('#file')[0].files;
                if(file.length > 0)
                    formData.append('file',file[0]);
            });
        },
        error: function (file, response) {
            console.log(response);
        },
        successmultiple: function (file, response) {
            showAlert(response.message, 'success');
            $('#submit-btn').removeAttr('disabled').html("{{__('db.submit')}}");
        },
        completemultiple: function (file, response) {
            console.log(file, response, "completemultiple");
        },
        reset: function () {
            console.log("resetFiles");
            this.removeAllFiles(true);
        }
    });

</script>
@endpush
