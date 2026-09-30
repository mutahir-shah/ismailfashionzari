@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')

<style type="text/css">
    .btn-icon i{margin-right:5px}
    .top-fields{margin-top:10px;position: relative;}
    .top-fields label {font-size:11px;font-weight:600;margin-left:10px;padding:0 3px;position:absolute;top:-8px;z-index:9;}
    .top-fields input{font-size:13px;height:45px}

    /* ===== Premium Product Details Modal ===== */
    #product-details .modal-dialog {
        max-width: 900px;
        margin: 30px auto;
    }
    #product-details .modal-content {
        border: none;
        border-radius: 0;
        overflow: hidden;
        box-shadow: 0 30px 80px rgba(124,92,196,0.22);
    }
    .pd-modal-header {
        background: linear-gradient(135deg, #3b2066 0%, #5a3a9e 45%, #7c5cc4 100%);
        padding: 24px 28px 20px;
        position: relative;
        overflow: hidden;
    }
    .pd-modal-header::before {
        content: '';
        position: absolute;
        top: -50px; right: -50px;
        width: 200px; height: 200px;
        background: radial-gradient(circle, rgba(124,92,196,0.35) 0%, transparent 70%);
        border-radius: 50%;
    }
    .pd-modal-header::after {
        content: '';
        position: absolute;
        bottom: -30px; left: 30px;
        width: 120px; height: 120px;
        background: radial-gradient(circle, rgba(200,180,255,0.15) 0%, transparent 70%);
        border-radius: 50%;
    }
    .pd-modal-header h5 {
        color: #fff;
        font-size: 20px;
        font-weight: 700;
        margin: 0;
        letter-spacing: 0.3px;
        position: relative; z-index: 2;
    }
    .pd-modal-header .pd-subtitle {
        color: rgba(255,255,255,0.6);
        font-size: 12px;
        margin-top: 3px;
        position: relative; z-index: 2;
    }
    .pd-header-actions {
        position: relative; z-index: 2;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .pd-btn-print {
        background: rgba(255,255,255,0.13);
        border: 1px solid rgba(255,255,255,0.25);
        color: #fff;
        border-radius: 8px;
        padding: 6px 14px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        backdrop-filter: blur(10px);
    }
    .pd-btn-print:hover {
        background: rgba(255,255,255,0.25);
        color: #fff;
    }
    .pd-btn-close {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.18);
        color: rgba(255,255,255,0.75);
        border-radius: 8px;
        width: 32px; height: 32px;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 14px;
    }
    .pd-btn-close:hover {
        background: rgba(239,68,68,0.3);
        border-color: rgba(239,68,68,0.4);
        color: #fff;
    }
    /* Icon action buttons in header */
    .pd-icon-btn {
        background: rgba(255,255,255,0.10);
        border: 1px solid rgba(255,255,255,0.18);
        color: rgba(255,255,255,0.85);
        border-radius: 7px;
        width: 34px; height: 34px;
        display: inline-flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 14px;
        text-decoration: none;
        vertical-align: middle;
    }
    .pd-icon-btn:hover { color: #fff; text-decoration: none; }
    .pd-icon-btn.btn-edit:hover    { background: rgba(124,92,196,0.55); border-color: rgba(124,92,196,0.6); }
    .pd-icon-btn.btn-history:hover { background: rgba(20,184,166,0.45); border-color: rgba(20,184,166,0.5); }
    .pd-icon-btn.btn-barcode:hover { background: rgba(245,158,11,0.45); border-color: rgba(245,158,11,0.5); }
    .pd-icon-btn.btn-delete:hover  { background: rgba(239,68,68,0.4);  border-color: rgba(239,68,68,0.5);  }
    .pd-icon-btn.btn-print:hover   { background: rgba(255,255,255,0.22); }
    .pd-header-sep {
        width: 1px; height: 22px;
        background: rgba(255,255,255,0.18);
        display: inline-block;
        margin: 0 2px;
        vertical-align: middle;
    }
    .pd-modal-body {
        padding: 0;
        max-height: 80vh;
        overflow-y: auto;
    }
    .pd-modal-body::-webkit-scrollbar { width: 5px; }
    .pd-modal-body::-webkit-scrollbar-track { background: #f1eeff; }
    .pd-modal-body::-webkit-scrollbar-thumb { background: #b89fe0; border-radius: 10px; }
    /* Image section */
    #pd-image-section {
        background: linear-gradient(160deg, #ede8f9 0%, #e2d8f5 100%);
        border-right: 1px solid #d8cef0;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 280px;
        padding: 20px;
        position: relative;
    }
    #pd-image-section img {
        max-height: 240px;
        max-width: 100%;
        object-fit: contain;
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(124,92,196,0.15);
    }
    #pd-image-section .carousel {
        width: 100%;
    }
    /* Type badge */
    .pd-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 10px;
    }
    .pd-type-standard  { background: #ede8f9; color: #7c5cc4; }
    .pd-type-combo     { background: #fce7f3; color: #9d174d; }
    .pd-type-digital   { background: #d1fae5; color: #065f46; }
    .pd-type-service   { background: #fef3c7; color: #92400e; }
    /* Info section */
    #pd-info-section {
        padding: 22px 24px 18px;
    }
    .pd-product-name {
        font-size: 20px;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 4px;
        line-height: 1.3;
    }
    .pd-product-code {
        font-size: 12px;
        color: #7c5cc4;
        margin-bottom: 14px;
        font-family: 'Courier New', monospace;
        background: #ede8f9;
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
    }
    .pd-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 14px;
    }
    .pd-info-card {
        background: #fff;
        border: 1px solid #e8e0f5;
        border-radius: 12px;
        padding: 12px 14px;
        position: relative;
        overflow: hidden;
        transition: box-shadow 0.2s, transform 0.2s;
    }
    .pd-info-card:hover {
        box-shadow: 0 4px 16px rgba(124,92,196,0.13);
        transform: translateY(-1px);
    }
    /* Default accent = theme purple */
    .pd-info-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 3px;
        height: 100%;
        background: linear-gradient(180deg, #7c5cc4, #a07de0);
        border-radius: 0 2px 2px 0;
    }
    .pd-info-card.accent-green::before  { background: linear-gradient(180deg, #10b981, #059669); }
    .pd-info-card.accent-orange::before { background: linear-gradient(180deg, #f59e0b, #d97706); }
    .pd-info-card.accent-red::before    { background: linear-gradient(180deg, #ef4444, #dc2626); }
    .pd-info-card.accent-blue::before   { background: linear-gradient(180deg, #7c5cc4, #5a3a9e); }
    .pd-info-card.accent-teal::before   { background: linear-gradient(180deg, #14b8a6, #0d9488); }
    .pd-info-label {
        font-size: 10px;
        font-weight: 700;
        color: #9e8cc0;
        letter-spacing: 0.6px;
        text-transform: uppercase;
        margin-bottom: 2px;
    }
    .pd-info-value {
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
    }
    .pd-info-value.price-value {
        color: #059669;
        font-size: 17px;
    }
    .pd-info-value.cost-value {
        color: #dc2626;
    }
    /* Description */
    .pd-description-box {
        background: #fff;
        border: 1px solid #e8e0f5;
        border-left: 3px solid #7c5cc4;
        border-radius: 0 12px 12px 0;
        padding: 14px;
        margin-top: 10px;
    }
    .pd-description-box .pd-desc-label {
        font-size: 10px;
        font-weight: 700;
        color: #9e8cc0;
        letter-spacing: 0.6px;
        text-transform: uppercase;
        margin-bottom: 6px;
    }
    .pd-description-box .pd-desc-text {
        font-size: 13px;
        color: #475569;
        line-height: 1.6;
    }
    /* Section divider */
    .pd-section {
        border-top: 1px solid #ede8f9;
        padding: 20px 24px;
        background: #fff;
    }
    .pd-section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 700;
        color: #3b2066;
        margin-bottom: 14px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .pd-section-title .pd-section-icon {
        width: 28px; height: 28px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px;
        background: linear-gradient(135deg, #7c5cc4, #a07de0);
        color: #fff;
    }
    .pd-section-title .pd-section-icon.green  { background: linear-gradient(135deg, #10b981, #059669); }
    .pd-section-title .pd-section-icon.orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
    /* Tables inside modal */
    .pd-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 13px;
    }
    .pd-table thead tr th {
        background: linear-gradient(135deg, #ede8f9 0%, #e2d8f5 100%);
        color: #5a3a9e;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        padding: 10px 12px;
        border-bottom: 2px solid #c8b8e8;
        text-align: left;
    }
    .pd-table thead tr th:first-child { border-radius: 0; }
    .pd-table thead tr th:last-child  { border-radius: 0; }
    .pd-table tbody tr td {
        padding: 10px 12px;
        border-bottom: 1px solid #f3eeff;
        color: #334155;
        vertical-align: middle;
    }
    .pd-table tbody tr:hover td {
        background: #f9f7ff;
    }
    .pd-table tbody tr:last-child td {
        border-bottom: none;
    }
    .pd-qty-badge {
        background: linear-gradient(135deg, #ede8f9, #d8cef0);
        color: #5a3a9e;
        font-weight: 700;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 12px;
        display: inline-block;
    }
    /* Carousel styling inside modal */
    #pd-image-section .carousel-control-prev,
    #pd-image-section .carousel-control-next {
        background: rgba(124,92,196,0.55);
        width: 30px; height: 30px;
        border-radius: 50%;
        top: 50%; transform: translateY(-50%);
        opacity: 0.9;
    }
    #pd-image-section .carousel-control-prev { left: 6px; }
    #pd-image-section .carousel-control-next { right: 6px; }
    @keyframes pd-fade-in {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .pd-animate { animation: pd-fade-in 0.35s ease forwards; }

    /* ---- ACTION BAR (icon-only toolbar) ---- */
    #inv-action-bar {
        display: flex; align-items: center; justify-content: space-between;
        padding: 7px 14px;
        background: #f0f4ff;
        border-bottom: 1px solid #d5ddf0;
    }
    #inv-action-bar .inv-btn-group { display: flex; gap: 4px; align-items: center; }
    #inv-action-bar .inv-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 34px; height: 34px; border-radius: 6px; border: 1px solid #c8d3ea;
        background: #fff; color: #3a5090; font-size: 16px; cursor: pointer;
        transition: background 0.15s, color 0.15s;
        position: relative;
    }
    #inv-action-bar .inv-btn:hover { background: #3a5090; color: #fff; border-color: #3a5090; }
    #inv-action-bar .inv-btn[title]:hover::after {
        content: attr(title);
        position: absolute; bottom: -28px; left: 50%; transform: translateX(-50%);
        background: #333; color: #fff; font-size: 11px; padding: 2px 8px;
        border-radius: 4px; white-space: nowrap; pointer-events: none; z-index: 99;
    }
    #inv-action-bar .inv-close {
        font-size: 20px; color: #888; background: none; border: none;
        cursor: pointer; line-height: 1; padding: 2px 6px; border-radius: 4px;
    }
    #inv-action-bar .inv-close:hover { color: #c0392b; background: #fde8e8; }
</style>

<x-success-message key="create_message" />
<x-success-message key="import_message" />
<x-error-message key="not_permitted" />
<x-errors-message key="import_errors" />
<x-error-message key="message" />

<section>
    <div class="container-fluid">
        
        @can('products-add')
            <a href="{{route('products.create')}}" class="btn btn-info add-product-btn btn-icon"><i class="ti ti-plus"></i> {{__('db.add_product')}}</a>
        @endcan
        @can('products-import')
            <a href="#" data-toggle="modal" data-target="#importProduct" class="btn btn-primary add-product-btn btn-icon"><i class="ti ti-copy"></i> {{__('db.import_product')}}</a>
        @endcan

        @can('products-edit')
            @if(in_array('ecommerce',explode(',',gen_setting()->modules)) )
                <a href="{{route('product.allProductInStock')}}" class="btn btn-dark add-product-btn btn-icon"><i class="ti ti-stack"></i> {{__('db.All Product In Stock')}}</a>
                <a href="{{route('product.showAllProductOnline')}}" class="btn btn-dark add-product-btn btn-icon"><i class="ti ti-wifi"></i> {{__('db.Show All Product Online')}}</a>
            @endif
        @endcan
        <button type="button" class="btn btn-warning btn-icon" id="toggle-filter">
            <i class="ti ti-filter"></i> {{ __('db.Filter Products') }}
        </button>

        <div class="card mt-3 mb-2">
            <div class="card-body" id="filter-card" style="display: none;">
                <div class="row">
                    <div class="col-md-3 @if(\Auth::user()->role_id > 2){{'d-none'}}@endif">
                        <div class="form-group top-fields">
                            <label>{{__('db.Warehouse')}}</label>
                            <select id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" >
                                <option value="0">{{__('db.All Warehouse')}}</option>
                                @foreach($lims_warehouse_list as $warehouse)
                                    <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group top-fields">
                            <label>{{__('db.Product Type')}}</label>
                            <select name="product_type" required class="form-control selectpicker" id="product_type" data-live-search="true" data-live-search-style="begins">
                                <option value="all" selected>All Types</option>
                                <option value="standard">Standard</option>
                                <option value="combo">Combo</option>
                                <option value="digital">Digital</option>
                                <option value="service">Service</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group top-fields">
                            <label>{{__('db.Brand')}}</label>
                            <select name="brand_id" required class="form-control selectpicker" id="brand_id" data-live-search="true" data-live-search-style="begins">
                                <option value="0" selected>All Brands</option>
                                @foreach($lims_brand_list as $brand)
                                    <option value="{{$brand->id}}">{{$brand->title}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group top-fields">
                            <label>{{__('db.category')}}</label>
                            <select name="category_id" required class="form-control selectpicker" id="category_id" data-live-search="true" data-live-search-style="begins">
                                <option value="0" selected>All Categories</option>
                                @foreach (get_active_categories() as $category)
                                    <option value="{{$category->id}}">{{$category->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group top-fields">
                            <label>{{__('db.Unit')}}</label>
                            <select name="unit_id" required class="form-control selectpicker" id="unit_id" data-live-search="true" data-live-search-style="begins">
                                <option value="0" selected>All Unit</option>
                                @foreach($lims_unit_list as $unit)
                                    <option value="{{$unit->id}}">{{$unit->unit_name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group top-fields">
                            <label>{{__('db.Tax')}}</label>
                            <select name="tax_id" required class="form-control selectpicker" id="tax_id" data-live-search="true" data-live-search-style="begins">
                                <option value="0" selected>All Tax</option>
                                @foreach($lims_tax_list as $tax)
                                    <option value="{{$tax->id}}">{{$tax->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group top-fields">
                            <label>{{__('db.Product with')}}</label>
                            <select name="imeiorvariant" required class="form-control selectpicker" id="imeiorvariant">
                                <option value="0" selected>Select IMEI/Variant/Batch</option>
                                <option value="batch">Batch/Expiry</option>
                                <option value="imei">IMEI</option>
                                <option value="variant">Variant</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group top-fields">
                            <label>{{__('db.stock')}}</label>
                            <select name="stock_filter" required class="form-control selectpicker" id="stock_filter">
                                <option value="all" selected>All</option>
                                <option value="with">With Stock</option>
                                <option value="without">Without Stock</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group top-fields">
                            <label>{{__('db.Status')}}</label>
                            <select name="status" required class="form-control selectpicker" id="status_filter">
                                <option value="active" selected>{{__('db.Active')}}</option>
                                <option value="inactive">{{__('db.Inactive')}}</option>
                                <option value="all">{{__('db.All')}}</option>
                            </select>
                        </div>
                    </div>
                    <div id="filter-loading" class="col-12 text-center my-2" style="display:none;">
                        <span class="spinner-border text-primary spinner-border-sm" role="status"></span>
                        <span>Loading results...</span>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table id="product-data-table" class="table pt-0" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.product')}}</th>
                    <th class="d-none"></th>
                    <th>{{__('db.Code')}}</th>
                    <th>{{__('db.Brand')}}</th>
                    <th>{{__('db.category')}}</th>
                    <th>{{__('db.Quantity')}}</th>
                    <th>{{__('db.Unit')}}</th>
                    <th>{{__('db.Price')}} ({{ config('currency') }})</th>
                    @if($role_id <= 2)
                        <th>{{__('db.Cost')}} ({{ config('currency') }})</th>
                        <th>{{__('db.Stock Worth') . '(' . __('db.Price') . '/' . __('db.Cost') . ')'}}</th>
                    @endif
                    @foreach($custom_fields as $fieldName)
                        <th>{{$fieldName}}</th>
                    @endforeach
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
        </table>
    </div>
</section>

<div id="importProduct" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('product.import') }}" method="POST" enctype="multipart/form-data">
          @csrf
        <div class="modal-header">
          <h5 id="exampleModalLabel" class="modal-title">{{__('db.import_product')}} / {{__('db.bulk_update')}}</h5>
          <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
        </div>
        <div class="modal-body">
          <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
           <p>{{__('db.The correct column order is')}} (image, name*, code*, type*, brand, category*, unit_code*, cost*, profit_margin(%), price, wholesale price, product_details, variant_name, item_code, additional_price) {{__('db.and you must follow this')}}.</p>
           <p>If you provide profit_margin, then price will be calculated based on profit_margin: <strong>price = cost * (1 + profit_margin / 100)</strong></p>
           <p>{{__('db.To display Image it must be stored in')}} images/product {{__('db.directory')}}. {{__('db.Image name must be same as product name')}}</p>
           <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{__('db.Upload CSV File')}} *</label>
                        <input type="file" name="file" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label> {{__('db.Sample File')}}</label>
                        <a href="sample_file/sample_products.csv" class="btn btn-info btn-block btn-md"><i class="ti ti-download"></i>  {{__('db.Download')}}</a>
                    </div>
                </div>
           </div>
            <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
        </div>
        </form>
      </div>
    </div>
</div>

@if(session('image_import_token'))
<div id="product-image-import-progress" class="alert alert-info" data-token="{{ session('image_import_token') }}">
    <strong>{{ __('db.Product images') }}:</strong>
    <span class="status">{{ __('db.Pending') }}</span>
    <button type="button" class="btn btn-sm btn-outline-primary retry-images" style="display:none">{{ __('db.Retry') }}</button>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const panel = document.getElementById('product-image-import-progress');
    if (!panel) return;
    const status = panel.querySelector('.status');
    const retry = panel.querySelector('.retry-images');
    const processBatch = function () {
        retry.style.display = 'none';
        fetch(@json(route('product.import.images')), {
            method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json'},
            body: JSON.stringify({token: panel.dataset.token, limit: 10})
        }).then(response => response.ok ? response.json() : Promise.reject(new Error('Image processing request failed')))
          .then(result => {
              status.textContent = result.completed + ' completed, ' + result.pending + ' pending, ' + result.failed + ' failed';
              if (result.pending > 0) setTimeout(processBatch, 250);
              else if (result.failed > 0) retry.style.display = '';
              else panel.className = 'alert alert-success';
          }).catch(error => { status.textContent = error.message; retry.style.display = ''; });
    };
    retry.addEventListener('click', processBatch);
    processBatch();
});
</script>
@endif

{{-- ===== PREMIUM PRODUCT DETAILS MODAL ===== --}}
<div id="product-details" tabindex="-1" role="dialog" aria-labelledby="pd-modal-title" aria-hidden="true" class="modal fade">
    <div role="document" class="modal-dialog" style="max-width:900px;">
      <div class="modal-content" style="border:none;border-radius:0;overflow:hidden;box-shadow:0 30px 80px rgba(0,0,0,0.25);">

        <!-- Action Bar -->
        <div id="inv-action-bar">
            <div class="inv-btn-group">
                {{-- Print --}}
                <button id="print-btn" type="button" class="inv-btn btn-print" title="{{__('db.print')}}">
                    <i class="ti ti-printer"></i>
                </button>

                {{-- Edit --}}
                @can('products-edit')
                <a id="pd-edit-btn" href="#" class="inv-btn btn-edit" title="{{__('db.edit')}}">
                    <i class="ti ti-edit"></i>
                </a>
                @endcan

                {{-- History --}}
                @can('product_history')
                <form id="pd-history-form" action="{{route('products.history')}}" method="GET" style="margin:0; display:inline;">
                    <input type="hidden" name="product_id" id="pd-history-product-id" value="">
                    <button type="submit" class="inv-btn btn-history" title="{{__('db.Product History')}}">
                        <i class="ti ti-checklist"></i>
                    </button>
                </form>
                @endcan

                {{-- Print Barcode --}}
                @can('print_barcode')
                <form id="pd-barcode-form" action="{{route('product.printBarcode')}}" method="GET" target="_blank" style="margin:0; display:inline;">
                    <input type="hidden" name="data" id="pd-barcode-data" value="">
                    <button type="submit" class="inv-btn btn-barcode" title="{{__('db.print_barcode')}}">
                        <i class="ti ti-barcode"></i>
                    </button>
                </form>
                @endcan

                {{-- Delete --}}
                @can('products-delete')
                <form id="pd-delete-form" action="" method="POST" style="margin:0; display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inv-btn btn-delete" title="{{__('db.delete')}}" onclick="return confirmDelete()">
                        <i class="ti ti-trash"></i>
                    </button>
                </form>
                @endcan
            </div>
            
            {{-- Close --}}
            <button type="button" id="close-btn" data-dismiss="modal" aria-label="Close" class="inv-close">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <div class="pd-modal-body" style="padding: 20px 30px;">
          
          {{-- Top: image + info --}}
          <div class="d-flex flex-wrap">
            {{-- Image panel --}}
            <div id="pd-image-section" style="width:300px;min-width:260px;">
              <div id="slider-content"></div>
            </div>
            {{-- Info panel --}}
            <div id="pd-info-section" style="flex:1;min-width:260px;">
              <div id="product-content"></div>
            </div>
          </div>

          {{-- Warehouse section (admin only) --}}
          @if($role_id <= 2)
          <div id="product-warehouse-section" class="pd-section d-none">
            <div class="pd-section-title">
              <span class="pd-section-icon green"><i class="ti ti-building-store"></i></span>
              {{__('db.Warehouse Quantity')}}
            </div>
            <div class="table-responsive">
              <table class="pd-table product-warehouse-list">
                <thead></thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
          @endif

          {{-- Variant section --}}
          <div id="product-variant-section" class="pd-section d-none">
            <div class="pd-section-title">
              <span class="pd-section-icon"><i class="ti ti-tags"></i></span>
              {{__('db.Product Variant Information')}}
            </div>
            <div class="table-responsive">
              <table class="pd-table product-variant-list">
                <thead></thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

          {{-- Variant warehouse section (admin only) --}}
          @if($role_id <= 2)
          <div id="product-variant-warehouse-section" class="pd-section d-none">
            <div class="pd-section-title">
              <span class="pd-section-icon orange"><i class="ti ti-chart-pie"></i></span>
              {{__('db.Warehouse quantity of product variants')}}
            </div>
            <div class="table-responsive">
              <table class="pd-table product-variant-warehouse-list">
                <thead></thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
          @endif

          {{-- Combo products section --}}
          <div id="pd-combo-section" class="pd-section d-none">
            <div class="pd-section-title">
              <span class="pd-section-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="ti ti-stack"></i></span>
              <span id="combo-header"></span>
            </div>
            <div class="table-responsive">
              <table class="pd-table item-list">
                <thead></thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

        </div>{{-- end pd-modal-body --}}
      </div>
    </div>
</div>

@endsection
@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script>

    if(localStorage.getItem("message")) {
        alert(localStorage.getItem("message"));
        localStorage.removeItem("message");
    }

    @if(config('database.connections.saleprosaas_landlord'))
        numberOfProduct = <?php echo json_encode($numberOfProduct)?>;
        $.ajax({
            type: 'GET',
            async: false,
            url: `{{route("package.fetchData", gen_setting()->package_id)}}`,
            success: function(data) {
                if(data['number_of_product'] > 0 && data['number_of_product'] <= numberOfProduct) {
                    $("a.add-product-btn").addClass('d-none');
                }
            }
        });
    @endif

    function confirmDelete() {
        if (confirm("Are you sure want to delete?")) {
            return true;
        }
        return false;
    }

    var role_id = <?php echo json_encode($role_id) ?>;
    var columns = [{"data": "key"},{"data": "name"},{"data": "image_path"},{"data": "code"},{"data": "brand"},{"data": "category"},{"data": "qty"},{"data": "unit"},{"data": "price"}];
    if(role_id <= 2) {
        columns.push({"data": "cost"});
        columns.push({"data": "stock_worth"});
    }
    var field_name = <?php echo json_encode($field_name) ?>;
    for(i = 0; i < field_name.length; i++) {
        columns.push({"data": field_name[i]});
    }
    columns.push({"data": "options"});

    var warehouse = [];
    var variant = [];
    var qty = [];
    var htmltext;
    var slidertext;
    var product_id = [];
    var all_permission = <?php echo json_encode($all_permission) ?>;
    var user_verified = <?php echo json_encode(config('app.user_verified'))?>;
    var logoUrl = <?php echo json_encode(url('logo', gen_setting()->site_logo)) ?>;
    var warehouse_id = <?php echo json_encode($warehouse_id); ?>;
    var product_type = <?php echo json_encode($product_type); ?>;
    var brand_id = <?php echo json_encode($brand_id); ?>;
    var category_id = <?php echo json_encode($category_id); ?>;
    var unit_id = <?php echo json_encode($unit_id); ?>;
    var tax_id = <?php echo json_encode($tax_id); ?>;
    var imeiorvariant = <?php echo json_encode($imeiorvariant); ?>;
    var stock_filter = <?php echo json_encode($stock_filter); ?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $("#warehouse_id").val(warehouse_id);
    $("#product_type").val(product_type);
    $("#brand_id").val(brand_id);
    $("#category_id").val(category_id);
    $("#unit_id").val(unit_id);
    $("#tax_id").val(tax_id);
    $("#imeiorvariant").val(imeiorvariant);
    $("#stock_filter").val(stock_filter);

    $( "#select_all" ).on( "change", function() {
        if ($(this).is(':checked')) {
            $("tbody input[type='checkbox']").prop('checked', true);
        }
        else {
            $("tbody input[type='checkbox']").prop('checked', false);
        }
    });

    $(document).on("click", "tr.product-link td:not(:first-child, :last-child)", function() {
        productDetails( $(this).parent().data('product'), $(this).parent().data('imagedata') );
    });

    $(document).on("click", ".view", function(){
        var product = $(this).parent().parent().parent().parent().parent().data('product');
        var imagedata = $(this).parent().parent().parent().parent().parent().data('imagedata');
        // console.log(product);
        productDetails(product, imagedata);
    });

    $("#print-btn").on("click", function() {
        var imageSrc = $('#slider-content img').first().attr('src') || '';
        var newWin = window.open('', '', 'width=1100,height=700');
        newWin.document.open();

        newWin.document.write(`
            <html>
            <head>
                <title>Product Details</title>
                <link rel="stylesheet" href="{{ asset($asset_prefix . 'vendor/bootstrap/css/bootstrap.min.css') }}">
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        padding: 30px;
                        color: #333;
                    }

                    .print-header {
                        text-align: center;
                        margin-bottom: 30px;
                        border-bottom: 2px solid #000;
                        padding-bottom: 10px;
                    }

                    .product-top {
                        display: flex;
                        gap: 30px;
                        margin-bottom: 25px;
                    }

                    .product-image {
                        width: 250px;
                    }

                    .product-image img {
                        width: 100%;
                        border: 1px solid #ddd;
                        padding: 5px;
                        border-radius: 6px;
                    }

                    .product-info {
                        flex: 1;
                    }

                    .section-title {
                        margin-top: 30px;
                        margin-bottom: 10px;
                        font-weight: 600;
                        border-left: 4px solid #007bff;
                        padding-left: 10px;
                        font-size: 16px;
                    }

                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 20px;
                    }

                    table thead th {
                        background: #f2f2f2;
                        font-weight: 600;
                        border: 1px solid #ddd;
                        padding: 8px;
                        text-align: left;
                    }

                    table tbody td {
                        border: 1px solid #ddd;
                        padding: 8px;
                    }

                    .footer-note {
                        margin-top: 40px;
                        font-size: 12px;
                        text-align: right;
                        color: #777;
                    }

                    @media print {
                        body {
                            -webkit-print-color-adjust: exact;
                        }
                    }
                </style>
            </head>
            <body>

                <div class="print-header">
                    <h3>Product Details Report</h3>
                    <small>Generated on: ${new Date().toLocaleString()}</small>
                </div>

                <div class="product-top">
                    <div class="product-image">
                        ${imageSrc ? `<img src="${imageSrc}">` : ''}
                    </div>
                    <div class="product-info">
                        ${$('#product-content').html()}
                    </div>
                </div>

                ${$('#product-warehouse-section').html() || ''}
                ${$('#product-variant-section').html() || ''}
                ${$('#product-variant-warehouse-section').html() || ''}
                ${$('.item-list').prop('outerHTML') || ''}

                <div class="footer-note">
                    System Generated Report
                </div>

            </body>
            </html>
        `);

        newWin.document.close();
        newWin.onload = function() {
            newWin.focus();
            newWin.print();
            newWin.close();
        };
    });

    function productDetails(product, imagedata) {
        product[11] = product[11].replace(/@/g, '"');

        // ------- Type badge -------
        var typeName   = product[0] || 'standard';
        var typeCls    = 'pd-type-' + typeName;
        var typeIcon   = typeName === 'combo' ? 'ti ti-stack'
                       : typeName === 'digital' ? 'ti ti-cloud-upload'
                       : typeName === 'service' ? 'ti ti-settings'
                       : 'ti ti-box';

        // ------- Build info cards -------
        var infoCards = '';

        infoCards += '<div class="pd-info-card accent-blue"><div class="pd-info-label">{{__("db.Brand")}}</div><div class="pd-info-value">' + (product[3] || '—') + '</div></div>';
        infoCards += '<div class="pd-info-card accent-teal"><div class="pd-info-label">{{__("db.category")}}</div><div class="pd-info-value">' + (product[4] || '—') + '</div></div>';
        infoCards += '<div class="pd-info-card accent-green"><div class="pd-info-label">{{__("db.Quantity")}}</div><div class="pd-info-value"><span class="pd-qty-badge">' + (product[17] !== undefined ? product[17] : '—') + '</span> <small style="font-weight:500;color:#64748b;font-size:12px;">' + (product[5] || '') + '</small></div></div>';
        infoCards += '<div class="pd-info-card accent-green"><div class="pd-info-label">{{__("db.Price")}}</div><div class="pd-info-value price-value">' + (product[7] || '—') + '</div></div>';

        if(role_id < 3) {
            infoCards += '<div class="pd-info-card accent-red"><div class="pd-info-label">{{__("db.Cost")}}</div><div class="pd-info-value cost-value">' + (product[6] || '—') + '</div></div>';
        }

        infoCards += '<div class="pd-info-card"><div class="pd-info-label">{{__("db.Tax")}}</div><div class="pd-info-value">' + (product[8] || '0') + '%</div></div>';
        infoCards += '<div class="pd-info-card"><div class="pd-info-label">{{__("db.Tax Method")}}</div><div class="pd-info-value">' + (product[9] || '—') + '</div></div>';
        infoCards += '<div class="pd-info-card accent-orange"><div class="pd-info-label">{{__("db.Alert Quantity")}}</div><div class="pd-info-value">' + (product[10] || '0') + '</div></div>';

        var descriptionHtml = '';
        if(product[11] && product[11].trim() && product[11].trim() !== 'null') {
            descriptionHtml = '<div class="pd-description-box"><div class="pd-desc-label">{{__("db.Product Details")}}</div><div class="pd-desc-text">' + product[11] + '</div></div>';
        }

        var htmltext = '<div class="pd-product-name">' + (product[1] || '') + '</div>' +
            '<div style="display:flex; align-items:center; gap:10px; margin-bottom: 15px;">' +
                '<span class="pd-type-badge ' + typeCls + '" style="margin:0;"><i class="' + typeIcon + '"></i> ' + typeName.charAt(0).toUpperCase() + typeName.slice(1) + '</span>' +
                '<div class="pd-product-code" style="margin:0; color:#64748b; font-size:13px; font-weight:600;"><i class="ti ti-ticket" style="font-size:12px; margin-right:3px;"></i> ' + (product[2] || '') + '</div>' +
            '</div>' +
            '<div class="pd-info-grid">' + infoCards + '</div>' +
            descriptionHtml;

        // ------- Wire action buttons -------
        var productId   = product[12];
        var productCode = product[2];
        var productName = product[1];
        var editUrl     = '{{ url("products") }}/' + productId + '/edit';
        var deleteUrl   = '{{ url("products") }}/' + productId;

        $('#pd-edit-btn').attr('href', editUrl);
        $('#pd-history-product-id').val(productId);
        $('#pd-barcode-data').val(productCode + ' (' + productName + ')');
        $('#pd-delete-form').attr('action', deleteUrl);

        // ------- Build image slider -------
        var slidertext = '';
        if(product[18]) {
            var product_image = product[18].split(",");
            if(product_image.length > 1) {
                slidertext = '<div id="product-img-slider" class="carousel slide" data-ride="carousel"><div class="carousel-inner">';
                for (var i = 0; i < product_image.length; i++) {
                    slidertext += '<div class="carousel-item' + (!i ? ' active' : '') + '"><img src="images/product/'+product_image[i]+'" style="object-fit:contain;max-height:240px;width:100%;"></div>';
                }
                slidertext += '</div>';
                slidertext += '<a class="carousel-control-prev" href="#product-img-slider" data-slide="prev"><span class="carousel-control-prev-icon"></span></a>';
                slidertext += '<a class="carousel-control-next" href="#product-img-slider" data-slide="next"><span class="carousel-control-next-icon"></span></a>';
                slidertext += '</div>';
            } else {
                slidertext = '<img src="images/product/'+product[18]+'" style="object-fit:contain;max-height:240px;width:100%;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,0.12);">';
            }
        } else {
            slidertext = '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:220px;width:100%;background:linear-gradient(135deg,#e8edf8,#dfe6f5);border-radius:14px;border:2px dashed #c0cbdf;">' +
                '<svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-bottom:12px;opacity:0.5;">' +
                    '<rect x="4" y="12" width="56" height="42" rx="6" fill="#b0bdd4"/>' +
                    '<circle cx="32" cy="33" r="11" fill="#e8edf8"/>' +
                    '<circle cx="32" cy="33" r="7" fill="#c0cbdf"/>' +
                    '<rect x="22" y="14" width="10" height="5" rx="2" fill="#c8d0e0"/>' +
                    '<circle cx="50" cy="18" r="3" fill="#c8d0e0"/>' +
                '</svg>' +
                '<span style="font-size:12px;font-weight:600;color:#94a3b8;letter-spacing:0.5px;">No Image Available</span>' +
            '</div>';
        }

        // ------- Clear old table data -------
        $("#combo-header").text('');
        $("#pd-combo-section").addClass('d-none');
        $("table.item-list thead, table.item-list tbody").remove();
        $("table.product-warehouse-list thead, table.product-warehouse-list tbody").remove();
        $(".product-variant-list thead, .product-variant-list tbody").remove();
        $(".product-variant-warehouse-list thead, .product-variant-warehouse-list tbody").remove();
        $("#product-warehouse-section").addClass('d-none');
        $("#product-variant-section").addClass('d-none');
        $("#product-variant-warehouse-section").addClass('d-none');

        // ------- Combo products -------
        if(product[0] == 'combo') {
            $("#combo-header").text('{{__("db.Combo Products")}}');
            $("#pd-combo-section").removeClass('d-none');
            product_list    = product[13].split(",");
            variant_list    = product[14].split(",");
            qty_list        = product[15].split(",");
            price_list      = product[16].split(",");
            combo_unit      = product[20].split(",");
            wastage_percent = product[21].split(",");

            var newHead = $("<thead>");
            var newBody = $("<tbody>");
            var newRow = $("<tr>");
            newRow.append('<th>{{__("db.product")}}</th><th>{{__("db.Wastage Percent")}}</th><th>{{__("db.Quantity")}}</th><th>{{__("db.Price")}}</th>');
            newHead.append(newRow);
            $(product_list).each(function(i) {
                if(!variant_list[i]) variant_list[i] = 0;
                $.get('products/getdata/' + product_list[i] + '/' + variant_list[i], function(data) {
                    var newRow = $("<tr>");
                    var cols = '<td><strong>' + data['name'] + '</strong> <span style="color:#94a3b8;font-size:11px;">[' + data['code'] + ']</span></td>';
                    cols += '<td>' + (wastage_percent[i] || '0') + '%</td>';
                    cols += '<td><span class="pd-qty-badge">' + qty_list[i] + '</span> <small>' + (combo_unit[i] || '') + '</small></td>';
                    cols += '<td><strong style="color:#059669;">' + price_list[i] + '</strong></td>';
                    newRow.append(cols);
                    newBody.append(newRow);
                });
            });
            $("table.item-list").append(newHead).append(newBody);
        }

        // ------- Variants & Warehouse -------
        if(product[0] == 'standard' || product[0] == 'combo') {
            if(product[19]) {
                $.get('products/variant-data/' + product[12], function(variantData) {
                    var newHead = $("<thead>");
                    var newBody = $("<tbody>");
                    var newRow = $("<tr>");
                    newRow.append('<th>{{__("db.Variant")}}</th><th>{{__("db.Item Code")}}</th><th>{{__("db.Additional Cost")}}</th><th>{{__("db.Additional Price")}}</th><th>{{__("db.qty")}}</th>');
                    newHead.append(newRow);
                    $.each(variantData, function(i) {
                        var newRow = $("<tr>");
                        var cols = '<td><strong>' + variantData[i]['name'] + '</strong></td>';
                        cols += '<td style="font-family:monospace;font-size:12px;">' + variantData[i]['item_code'] + '</td>';
                        cols += '<td>' + (variantData[i]['additional_cost'] || '0') + '</td>';
                        cols += '<td><strong style="color:#059669;">' + (variantData[i]['additional_price'] || '0') + '</strong></td>';
                        cols += '<td><span class="pd-qty-badge">' + variantData[i]['qty'] + '</span></td>';
                        newRow.append(cols);
                        newBody.append(newRow);
                    });
                    $("table.product-variant-list").append(newHead).append(newBody);
                });
                $("#product-variant-section").removeClass('d-none');
            }

            if(role_id <= 2) {
                $.get('products/product_warehouse/' + product[12], function(data) {
                    if(data.product_warehouse[0].length != 0) {
                        var wh           = data.product_warehouse[0];
                        var whQty        = data.product_warehouse[1];
                        var whBatch      = data.product_warehouse[2];
                        var whExpDate    = data.product_warehouse[3];
                        var whImei       = data.product_warehouse[4];

                        var newHead = $("<thead>");
                        var newBody = $("<tbody>");
                        var newRow  = $("<tr>");
                        newRow.append('<th>{{__("db.Warehouse")}}</th><th>{{__("db.Batch No")}}</th><th>{{__("db.Expired Date")}}</th><th>{{__("db.Quantity")}}</th><th>{{__("db.IMEI or Serial Numbers")}}</th>');
                        newHead.append(newRow);

                        $.each(wh, function(index) {
                            var newRow = $("<tr>");
                            var imeiHtml = (whImei.length <= index) ? '<span style="color:#94a3b8;">N/A</span>' : whImei[index].split(',').join(",<br/>");
                            var cols = '<td><strong>' + wh[index] + '</strong></td>';
                            cols += '<td style="font-family:monospace;font-size:12px;">' + (whBatch[index] || '—') + '</td>';
                            cols += '<td>' + (whExpDate[index] || '—') + '</td>';
                            cols += '<td><span class="pd-qty-badge">' + whQty[index] + '</span></td>';
                            cols += '<td style="font-size:11px;max-width:140px;word-break:break-all;">' + imeiHtml + '</td>';
                            newRow.append(cols);
                            newBody.append(newRow);
                        });
                        $("table.product-warehouse-list").append(newHead).append(newBody);
                        $("#product-warehouse-section").removeClass('d-none');
                    }

                    if(data.product_variant_warehouse[0].length != 0) {
                        var pvWh  = data.product_variant_warehouse[0];
                        var pvVar = data.product_variant_warehouse[1];
                        var pvQty = data.product_variant_warehouse[2];

                        var newHead = $("<thead>");
                        var newBody = $("<tbody>");
                        var newRow  = $("<tr>");
                        newRow.append('<th>{{__("db.Warehouse")}}</th><th>{{__("db.Variant")}}</th><th>{{__("db.Quantity")}}</th>');
                        newHead.append(newRow);

                        $.each(pvWh, function(index) {
                            var newRow = $("<tr>");
                            var cols = '<td><strong>' + pvWh[index] + '</strong></td>';
                            cols += '<td>' + pvVar[index] + '</td>';
                            cols += '<td><span class="pd-qty-badge">' + pvQty[index] + '</span></td>';
                            newRow.append(cols);
                            newBody.append(newRow);
                        });
                        $("table.product-variant-warehouse-list").append(newHead).append(newBody);
                        $("#product-variant-warehouse-section").removeClass('d-none');
                    }
                });
            }
        }

        $('#product-content').html(htmltext);
        $('#slider-content').html(slidertext);
        $('#product-details').modal('show');
        $('#product-img-slider').carousel(0);
    }

    $('#toggle-filter').on('click', function() {
        $('#filter-card').slideToggle('slow');
    });

    let buttons = [];
    @can('product_export')
        buttons.push([
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                exportOptions: {
                    columns: ':visible:not(.not-exported)',
                    rows: ':visible',
                    stripHtml: false
                },
                customize: function(doc) {
                    for (var i = 1; i < doc.content[1].table.body.length; i++) {
                        if (doc.content[1].table.body[i][0].text.indexOf('<img src=') !== -1) {
                            var imagehtml = doc.content[1].table.body[i][0].text;
                            var regex = /<img.*?src=['"](.*?)['"]/;
                            var src = regex.exec(imagehtml)[1];
                            var tempImage = new Image();
                            tempImage.src = src;
                            var canvas = document.createElement("canvas");
                            canvas.width = tempImage.width;
                            canvas.height = tempImage.height;
                            var ctx = canvas.getContext("2d");
                            ctx.drawImage(tempImage, 0, 0);
                            var imagedata = canvas.toDataURL("image/png");
                            delete doc.content[1].table.body[i][0].text;
                            doc.content[1].table.body[i][0].image = imagedata;
                            doc.content[1].table.body[i][0].fit = [30, 30];
                        }
                    }
                },
            },
            {
                extend: 'excelHtml5',
                text: '<i title="export to excel" class="ti ti-file-type-xls"></i>',
                exportOptions: {
                    columns: ':not(.not-exported)',
                    format: {
                        body: function (data, row, column, node) {

                            if (column === 0) {
                                var $cell = $('<div>').html(data);
                                $cell.find('img').remove();
                                data = $.trim($cell.text());
                            }

                            return data;
                        }
                    }
                }
            },
            {
                extend: 'csvHtml5',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':not(.not-exported)',
                    format: {
                        body: function (data, row, column, node) {

                            if (column === 0) {
                                var $cell = $('<div>').html(data);
                                $cell.find('img').remove();
                                data = $.trim($cell.text());
                            }

                            return data;
                        }
                    }
                }
            },
            {
                extend: 'print',
                title: '',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:not(.not-exported)',
                    rows: ':visible',
                    stripHtml: false
                },
                repeatingHead: {
                    logo: logoUrl,
                    logoPosition: 'left',
                    logoStyle: '',
                    title: '<h3>Product List</h3>'
                }
                /*customize: function ( win ) {
                    $(win.document.body)
                        .prepend(
                            '<img src="http://datatables.net/media/images/logo-fade.png" style="margin:10px;" />'
                        );
                }*/
            },
        ]);
    @endcan

    @can('products-delete')
        buttons.push([
            {
                text: '<i title="delete" class="ti ti-x"></i>',
                className: 'buttons-delete',
                action: function ( e, dt, node, config ) {
                    if(user_verified == '1') {
                        product_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                var product_data = $(this).closest('tr').data('product');
                                if(product_data)
                                    product_id[i-1] = product_data[12];
                            }
                        });
                        if(product_id.length && confirmDelete()) {
                            $.ajax({
                                type:'POST',
                                url:'products/deletebyselection',
                                data:{
                                    productIdArray: product_id
                                },
                                success:function(data) {
                                    alert(data);
                                    //dt.rows({ page: 'current', selected: true }).deselect();
                                    dt.rows({ page: 'current', selected: true }).remove().draw(false);
                                }
                            });
                        }
                        else if(!product_id.length)
                            alert('No product is selected!');
                    }
                    else
                        alert('This feature is disable for demo!');
                }
            },
        ]);
    @endcan

    buttons.push([
        {
            extend: 'colvis',
            text: '<i title="column visibility" class="ti ti-eye"></i>',
            columns: ':gt(0)'
        },
    ]);

    $(document).ready(function() {
        var table = $('#product-data-table').DataTable( {
            responsive: false,
            fixedHeader: {
                header: true,
                footer: true
            },
            "processing": true,
            "serverSide": true,
            "ajax":{
                url:"products/product-data",
                data: function (d) {
                    d.all_permission = all_permission;
                    d.warehouse_id = $('#warehouse_id').val();
                    d.product_type = $('#product_type').val();
                    d.brand_id = $('#brand_id').val();
                    d.category_id = $('#category_id').val();
                    d.unit_id = $('#unit_id').val();
                    d.tax_id = $('#tax_id').val();
                    d.imeiorvariant = $('#imeiorvariant').val();
                    d.stock_filter = $('#stock_filter').val();
                    d.status = $('#status_filter').val();
                },
                type:"post"
            },
            "createdRow": function( row, data, dataIndex ) {
                $(row).addClass('product-link');
                $(row).attr('data-product', data['product']);
                $(row).attr('data-imagedata', data['imagedata']);
            },
            "columns": columns,
            'language': {
                /*'searchPlaceholder': "{{__('db.Type Product Name or Code')}}",*/
                'lengthMenu': '_MENU_ {{__("db.records per page")}}',
                 "info":      '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
                "search":  '{{__("db.Search")}}',
                'paginate': {
                        'previous': '<i class="ti ti-chevron-left"></i>',
                        'next': '<i class="ti ti-chevron-right"></i>'
                }
            },
            order:[['1', 'asc']],
            'columnDefs': [
                {
                    "orderable": false,
                    'targets': role_id <= 2 ? [0, 11] : [0, 9],
                },
                {
                    targets: 2, // export-only image path column
                    visible: false,
                    className: 'export-image-path'
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
            'select': { style: 'multi', selector: 'td:first-child'},
            'lengthMenu': [[10, 25, 50, 100, 250], [10, 25, 50, 100, 250]],
            dom: '<"row"lfB>rtip',
            buttons: buttons,
        } );

        let hasFilters = window.location.search.length > 0;
        if (hasFilters) {
            $('#filter-card').show();
        }

        $('#warehouse_id, #product_type, #brand_id, #category_id, #unit_id, #tax_id, #imeiorvariant, #stock_filter, #status_filter').on('change', function () {
            table.ajax.reload();
        });

        $(document).on('click', '.toggle-active', function (e) {
            e.preventDefault();
            var productId = $(this).data('id');
            var status = $(this).data('status');
            var confirmMsg = (status === 'deactivate')
                ? 'Deactivate this product?\n\nThe product will no longer be available for new transactions, but its existing stock and transaction history will be preserved.'
                : 'Activate this product?\n\nThe product will become available for new transactions.';

            if (confirm(confirmMsg)) {
                $.ajax({
                    type: 'POST',
                    url: '{{ url("products") }}/' + productId + '/toggle-active',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        if (response.success) {
                            alert(response.message);
                            table.ajax.reload(null, false);
                        } else {
                            alert(response.message || 'Something went wrong');
                        }
                    },
                    error: function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error updating product status';
                        alert(msg);
                    }
                });
            }
        });

        // Show loader on request
        table.on('preXhr.dt', function () {
            $('#filter-loading').show();
        });

        // Hide loader after draw
        table.on('xhr.dt', function () {
            $('#filter-loading').hide();
        });
    });

    $('select').selectpicker();

</script>
@endpush
