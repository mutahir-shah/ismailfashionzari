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
</style>

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        @can('purchases-add')
            <a href="{{route('purchases.create')}}" class="btn btn-info btn-icon"><i class="ti ti-plus"></i> {{__('db.Add Purchase')}}</a>&nbsp;
        @endcan
        @can('purchases-import')
            <a href="{{url('purchases/purchase_by_csv')}}" class="btn btn-primary btn-icon"><i class="ti ti-copy"></i> {{__('db.Import Purchase')}}</a>
        @endcan
        @if (auth()->user()->role_id <= 2)
            <a href="{{url('purchases/deleted_data')}}" class="btn btn-secondary btn-icon"><i class="ti ti-trash"></i> {{__('Deleted Purchases')}}</a>
        @endif
        <button type="button" class="btn btn-warning btn-icon" id="toggle-filter">
            <i class="ti ti-filter"></i> {{ __('db.Filter Purchases') }}
        </button>
        <div class="card mt-3 mb-2">
            <div class="card-body" id="filter-card" style="display: none;">
                <div class="row mt-2">
                    <div class="col-md-3">
                        <div class="form-group top-fields">
                            <label>{{__('db.date')}}</label>
                            <input type="text" class="daterangepicker-field form-control" value="{{$starting_date}} To {{$ending_date}}" required />
                            <input type="hidden" name="starting_date" value="{{$starting_date}}" />
                            <input type="hidden" name="ending_date" value="{{$ending_date}}" />
                        </div>
                    </div>
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
                            <label>{{__('db.Purchase Status')}}</label>
                            <select id="purchase-status" class="form-control" name="purchase_status">
                                <option value="0">{{__('db.All')}}</option>
                                <option value="1">{{__('db.Recieved')}}</option>
                                <option value="2">{{__('db.Partial')}}</option>
                                <option value="3">{{__('db.Pending')}}</option>
                                <option value="4">{{__('db.Ordered')}}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group top-fields">
                            <label>{{__('db.Payment Status')}}</label>
                            <select id="payment-status" class="form-control" name="payment_status">
                                <option value="0">{{__('db.All')}}</option>
                                <option value="1">{{__('db.Due')}}</option>
                                <option value="2">{{__('db.Paid')}}</option>
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
        <table id="purchase-table" class="table purchase-list mt-0" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.date')}}</th>
                    <th>{{__('db.reference')}}</th>
                    <th>{{__('db.Created By')}}</th>
                    <th>{{__('db.Supplier')}}</th>
                    @if (gen_setting()->show_products_details_in_purchase_table)
                        <th>{{ __('db.Products') }}</th>
                        <th>{{ __('db.Quantity') }}</th>
                    @endif
                    <th>{{__('db.Purchase Status')}}</th>
                    <th>{{__('db.grand total')}} ({{ config('currency') }})</th>
                    <th>{{__('db.Returned Amount')}} ({{ config('currency') }})</th>
                    <th>{{__('db.Paid')}} ({{ config('currency') }})</th>
                    <th>{{__('db.Due')}} ({{ config('currency') }})</th>
                    <th>{{__('db.Currency')}}/{{__('db.Exchange Rate')}}</th>
                    <th>{{__('db.payment_term')}}</th>
                    <th>{{__('db.due_date')}}</th>
                    <th>{{__('db.Payment Status')}}</th>
                    @foreach($custom_fields as $fieldName)
                    <th>{{$fieldName}}</th>
                    @endforeach
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>

            <tfoot class="tfoot active">
                <th></th>
                <th>{{__('db.Total')}}</th>
                <th></th>
                <th></th>
                <th></th>
                @if (gen_setting()->show_products_details_in_purchase_table)
                    <th></th>
                    <th></th>
                @endif
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                @foreach($custom_fields as $fieldName)
                <th></th>
                @endforeach
                <th></th>
            </tfoot>
        </table>
    </div>
</section>

{{-- ====================== INVOICE-STYLE PURCHASE DETAILS MODAL ====================== --}}
<style>
/* ===== Action Dropdown Fix ===== */
.purchase-list .dropdown-menu.edit-options {
    min-width: 250px !important;
}
.purchase-list .dropdown-menu.edit-options > li > a,
.purchase-list .dropdown-menu.edit-options > li > button {
    white-space: nowrap !important;
}

/* ===== Invoice Modal Styles ===== */
#purchase-details .modal-dialog { max-width: 880px; width: 96%; }
#purchase-details .modal-content {
    border-radius: 0; border: none;
    font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #222;
}

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

/* ---- INVOICE WRAPPER ---- */
#invoice-wrapper { padding: 22px 30px 18px 30px; background: #fff; }

/* ---- 3-COLUMN HEADER (Logo | Labels | Values+Title) ---- */
#invoice-header {
    display: grid;
    grid-template-columns: 150px 1fr 1fr;
    align-items: center;
    gap: 0;
    margin-bottom: 12px;
    min-height: 90px;
}
#invoice-logo-area img {
    max-width: 140px; max-height: 72px; object-fit: contain; display: block;
}
#invoice-meta-labels {
    text-align: right;
    padding-right: 10px;
    line-height: 2;
}
#invoice-meta-labels span {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #555;
}
#invoice-meta-right {
    text-align: right;
    padding-left: 10px;
    border-left: 2px solid #e0e7f3;
}
#invoice-title-text {
    font-size: 22px; font-weight: 800;
    color: #1a3a6b; letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 4px;
    line-height: 1.1;
}
#invoice-ref-badge {
    display: inline-block;
    background: #e8edf5; color: #1a3a6b;
    font-weight: 700; font-size: 13px;
    padding: 2px 12px; border-radius: 4px;
    margin-bottom: 4px;
}
#invoice-meta-right .meta-val-row {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #222;
    line-height: 2;
    text-align: right;
}

/* ---- BADGES (lighter / softer) ---- */
.inv-badge {
    display: inline-block; padding: 2px 6px; border-radius: 4px;
    font-size: 10px; font-weight: 600; letter-spacing: 0.1px; text-transform: uppercase;
}
.inv-badge-completed  { background: #e8f8ee; color: #2e7d52; border: 1px solid #b7e4c7; }
.inv-badge-pending    { background: #fffbea; color: #9a6e00; border: 1px solid #ffe48c; }
.inv-badge-returned   { background: #fdf0f0; color: #a94442; border: 1px solid #f5baba; }
.inv-badge-processing { background: #eaf4ff; color: #1a6eb5; border: 1px solid #b3d7f5; }
.inv-badge-paid       { background: #e8f8ee; color: #2e7d52; border: 1px solid #b7e4c7; }
.inv-badge-due        { background: #fdf0f0; color: #a94442; border: 1px solid #f5baba; }
.inv-badge-partial    { background: #fffbea; color: #9a6e00; border: 1px solid #ffe48c; }
.inv-badge-default    { background: #f2f3f5; color: #555;    border: 1px solid #dde0e6; }

/* ---- DIVIDER ---- */
.inv-divider { border: none; border-top: 2px solid #1a3a6b; margin: 10px 0; }

/* ---- ADDRESS BOXES ---- */
.inv-address-row { display: flex; gap: 14px; margin-bottom: 13px; }
.inv-address-box { flex: 1; border: 1px solid #dde3ee; border-radius: 4px; overflow: hidden; }
.inv-address-header { background: #1a3a6b; color: #fff; font-weight: 700; font-size: 12px; padding: 5px 12px; letter-spacing: 0.5px; text-transform: uppercase; }
.inv-address-body { padding: 9px 13px; font-size: 13px; line-height: 1.7; }
.inv-address-body strong { display: block; font-size: 14px; font-weight: 700; margin-bottom: 2px; }
.inv-address-body .addr-row { display: flex; gap: 4px; }
.inv-address-body .addr-lbl { color: #666; min-width: 65px; font-weight: 600; }

/* ---- PRODUCT TABLE ---- */
.inv-product-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
.inv-product-table thead tr { background: #1a3a6b; color: #fff; }
.inv-product-table thead th { padding: 8px 10px; font-size: 12px; font-weight: 700; letter-spacing: 0.4px; text-transform: uppercase; border: none; }
.inv-product-table tbody tr { border-bottom: 1px solid #e8edf5; }
.inv-product-table tbody tr:nth-child(even) { background: #f7f9fc; }
.inv-product-table tbody td { padding: 7px 10px; font-size: 13px; border: none; vertical-align: top; }
.inv-product-table td.disc-red { color: #c0392b; font-weight: 600; }

/* ---- TOTALS ---- */
.inv-totals-row { display: flex; justify-content: space-between; align-items: flex-end; gap: 14px; margin-top: 10px; }
.inv-inwords-box {
    flex: 1; background: #eef2fa; border: 1px solid #d0d9ee; border-radius: 4px; padding: 10px 14px;
    display: flex; flex-direction: column; justify-content: center; min-height: 50px;
}
.inv-inwords-box .inwords-title { font-weight: 700; font-size: 13px; margin-bottom: 4px; color: #1a3a6b; }
.inv-inwords-box .inwords-text { font-size: 12px; color: #333; }
.inv-totals-box { width: 340px; }
.inv-totals-table { width: 100%; border-collapse: collapse; }
.inv-totals-table td { padding: 5px 10px; font-size: 13px; border: 1px solid #e2e8f0; }
.inv-totals-table .tlbl { color: #444; width: 50%; }
.inv-totals-table .tval { text-align: right; font-weight: 600; }
.inv-totals-table .tval.disc { color: #c0392b; }
.inv-grand-row td { background: #1a3a6b; color: #fff; font-size: 14px; font-weight: 700; }
.inv-paid-row td { background: #eafaf1; color: #155724; font-weight: 700; }
.inv-due-row td { background: #fffde7; color: #856404; font-weight: 700; }

/* ---- BARCODE + QR (centered) ---- */
.inv-codes-row { text-align: center; margin-top: 16px; padding-top: 12px; border-top: 1px solid #e2e8f0; }
.inv-codes-row .barcode-wrap { margin-bottom: 10px; }
.inv-codes-row svg, .inv-codes-row canvas { display: inline-block; }
#inv-qrcode { display: flex; justify-content: center; margin-top: 4px; }
#inv-qrcode canvas, #inv-qrcode img { display: block; margin: 0 auto; }

/* ---- THANK YOU ---- */
.inv-thankyou { text-align: left; color: #555; font-size: 12px; margin-top: 10px; font-style: italic; }

@media print {
    #inv-action-bar { display: none !important; }
    #purchase-details .modal-dialog { max-width: 100%; }
    #invoice-wrapper { padding: 10px; }
}
</style>

<div id="purchase-details" tabindex="-1" role="dialog" aria-labelledby="purchaseDetailsLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">

            {{-- Action Bar — icon-only toolbar (hidden on print) --}}
            <div id="inv-action-bar" class="d-print-none">
                <div class="inv-btn-group">
                    {{-- Fixed Buttons --}}
                    <button id="print-btn" type="button" class="inv-btn" title="{{__('db.print')}}">
                        <i class="ti ti-printer"></i>
                    </button>
                    
                    {{-- Dynamic Buttons (shown via JS based on row options) --}}
                    <a href="#" id="inv-edit-btn" class="inv-btn" title="{{__('db.edit')}}" style="display:none;"><i class="ti ti-edit"></i></a>
                    <button type="button" id="inv-get-payment-btn" class="inv-btn get-payment" title="{{__('db.View Payment')}}" style="display:none;"><i class="ti ti-cash-banknote"></i></button>
                    <button type="button" id="inv-add-payment-btn" class="inv-btn add-payment" title="{{__('db.Add Payment')}}" style="display:none;" data-toggle="modal" data-target="#add-payment"><i class="ti ti-plus"></i></button>
                    <form action="" method="POST" id="inv-delete-form" style="margin:0; display:none;">
                        @csrf
                        @method("DELETE")
                        <button type="submit" class="inv-btn" title="{{__('db.delete')}}" onclick="return confirmDelete()"><i class="ti ti-trash"></i></button>
                    </form>
                </div>
                {{-- Close --}}
                <button type="button" id="close-btn" data-dismiss="modal" aria-label="Close" class="inv-close">
                    <i class="ti ti-x"></i>
                </button>
            </div>

            {{-- Invoice Body --}}
            <div id="invoice-wrapper">

                {{-- === 3-COLUMN HEADER: Logo | Labels | Values+Title === --}}
                <div id="invoice-header">

                    {{-- COL 1: Logo (left) --}}
                    <div id="invoice-logo-area">
                        <img src="{{url('logo', gen_setting()->site_logo)}}" alt="Logo">
                    </div>

                    {{-- COL 2: Meta Labels (center, right-aligned) --}}
                    <div id="invoice-meta-labels">
                        <span>&nbsp;</span>{{-- spacer for title row --}}
                        <span>Date:</span>
                        <span>Reference #:</span>
                        <span>Status:</span>
                        <span>Payment:</span>
                        <span>Currency / Rate:</span>
                    </div>

                    {{-- COL 3: PURCHASE INVOICE title + Meta Values (right) --}}
                    <div id="invoice-meta-right">
                        <div id="invoice-title-text">PURCHASE DETAILS</div>
                        <span class="meta-val-row" id="inv-date">—</span>
                        <span class="meta-val-row" id="inv-ref">—</span>
                        <span class="meta-val-row" id="inv-status">—</span>
                        <span class="meta-val-row" id="inv-payment">—</span>
                        <span class="meta-val-row" id="inv-currency-rate">—</span>
                    </div>

                </div>

                <hr class="inv-divider">

                {{-- === BILL TO (Warehouse) / FROM (Supplier) === --}}
                <div class="inv-address-row">
                    <div class="inv-address-box">
                        <div class="inv-address-header">From (Supplier)</div>
                        <div class="inv-address-body" id="inv-from">—</div>
                    </div>
                    <div class="inv-address-box">
                        <div class="inv-address-header">To (Warehouse)</div>
                        <div class="inv-address-body" id="inv-bill-to">—</div>
                    </div>
                </div>

                {{-- === PRODUCT TABLE === --}}
                <div class="table-responsive">
                    <table class="inv-product-table product-purchase-list">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Batch No</th>
                                <th>Qty</th>
                                <th>Returned</th>
                                <th>Unit Cost</th>
                                <th>Tax</th>
                                <th>Discount</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                {{-- === TOTALS === --}}
                <div class="inv-totals-row">
                    <div class="inv-inwords-box">
                        <div class="inwords-title">In Words</div>
                        <div class="inwords-text" id="inv-inwords">—</div>
                    </div>
                    <div class="inv-totals-box">
                        <table class="inv-totals-table">
                            <tbody>
                                <tr><td class="tlbl">Total:</td><td class="tval" id="inv-total">—</td></tr>
                                <tr><td class="tlbl">Order Tax:</td><td class="tval" id="inv-order-tax">—</td></tr>
                                <tr><td class="tlbl">Order Discount:</td><td class="tval disc" id="inv-discount">—</td></tr>
                                <tr><td class="tlbl">Shipping Cost:</td><td class="tval" id="inv-shipping">—</td></tr>
                                <tr class="inv-grand-row"><td class="tlbl">Grand Total:</td><td class="tval" id="inv-grand-total">—</td></tr>
                                <tr class="inv-paid-row"><td class="tlbl">Paid Amount:</td><td class="tval" id="inv-paid">—</td></tr>
                                <tr id="inv-returned-row" style="display:none;"><td class="tlbl" style="color:#a94442;">Return Amount:</td><td class="tval disc" id="inv-returned">—</td></tr>
                                <tr class="inv-due-row"><td class="tlbl">Due:</td><td class="tval" id="inv-due">—</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- === BARCODE + QR CODE === --}}
                <div class="inv-codes-row">
                    <div class="barcode-wrap">
                        <svg id="inv-barcode"></svg>
                    </div>
                    <div id="inv-qrcode"></div>
                </div>

                {{-- === NOTES === --}}
                <div id="purchase-content" style="display:none;"></div>
                <div id="purchase-footer" style="margin-top:8px; font-size:12px; color:#555;"></div>

            </div>{{-- end invoice-wrapper --}}
        </div>
    </div>
</div>
{{-- Load JsBarcode + QRCode libraries --}}
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<div id="view-payment" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.All Payment')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <table class="table table-hover payment-list">
                    <thead>
                        <tr>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.Reference No')}}</th>
                            <th>{{__('db.Account')}}</th>
                            <th>{{__('db.Amount')}}</th>
                            <th>{{__('db.Paid By')}}</th>
                            <th>{{__('db.Payment Date')}}</th>
                            <th>{{__('db.action')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="add-payment" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Payment')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('purchase.add-payment') }}" method="POST" class="payment-form" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <input type="hidden" name="balance">
                        <div class="col-md-6">
                            <label>{{__('db.Recieved Amount')}} *</label>
                            <input type="text" name="paying_amount" class="form-control numkey"  step="any" required>
                        </div>
                        <div class="col-md-6">
                            <label>{{__('db.Paying Amount')}} *</label>
                            <input type="text" id="amount" name="amount" class="form-control"  step="any" required>
                        </div>
                        <div class="col-md-6 mt-1">
                            <label>{{__('db.Change')}} : </label>
                            <p class="change ml-2">{{number_format(0, gen_setting()->decimal, '.', '')}}</p>
                        </div>
                        <div class="col-md-6 mt-1">
                            <label>{{__('db.Paid By')}}</label>
                            <select name="paid_by_id" class="form-control">
                                <option value="1">{{ __('db.Cash') }}</option>
                                <option value="3">{{ __('db.Credit Card') }}</option>
                                <option value="4">{{ __('db.Cheque') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group mt-2">
                        <div class="card-element" class="form-control">
                        </div>
                        <div class="card-errors" role="alert"></div>
                    </div>
                    <div id="cheque">
                        <div class="form-group">
                            <label>{{__('db.Cheque Number')}} *</label>
                            <input type="text" name="cheque_no" class="form-control">
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label> {{__('db.Account')}}</label>
                            <select class="form-control selectpicker" name="account_id">
                                @foreach($lims_account_list as $account)
                                    @if($account->is_default)
                                    <option selected value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                                    @else
                                    <option value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label>{{ __('db.Payment Date') }}</label>
                            <input type="text" name="payment_at" id="payment_at" class="form-control"
                                value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label>{{__('db.Currency')}} & {{__('db.Exchange Rate')}}</label>
                            <div class="form-group d-flex align-items-center">
                                <p id="currency_display" class="form-control-plaintext mb-0 font-weight-bold mr-3"></p>
                                <p id="exchange_rate_display" class="form-control-plaintext mb-0 font-weight-bold"></p>
                            </div>

                            <!-- Hidden fields for backend -->
                            <input type="hidden" name="currency_id" id="currency_id">
                            <input type="hidden" name="exchange_rate" id="exchange_rate">
                        </div>
                        {{-- Attach Document --}}
                        <div class="col-md-4 mt-1">
                            <div class="form-group">
                                <label>{{__('db.Attach Document')}}</label>
                                <x-info title="Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported" type="info" />
                                <input type="file" name="document" class="form-control" />
                                @if($errors->has('extension'))
                                    <span><strong>{{ $errors->first('extension') }}</strong></span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>{{__('db.Payment Note')}}</label>
                        <textarea rows="3" class="form-control" name="payment_note"></textarea>
                    </div>

                    <input type="hidden" name="purchase_id">

                    <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="edit-payment" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Update Payment')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('purchase.update-payment') }}" method="POST" class="payment-form" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <label>{{__('db.Recieved Amount')}} *</label>
                            <input type="text" name="edit_paying_amount" class="form-control numkey"  step="any" required>
                        </div>
                        <div class="col-md-6">
                            <label>{{__('db.Paying Amount')}} *</label>
                            <input type="text" name="edit_amount" class="form-control"  step="any" required>
                        </div>
                        <div class="col-md-6 mt-1">
                            <label>{{__('db.Change')}} : </label>
                            <p class="change ml-2">{{number_format(0, gen_setting()->decimal, '.', '')}}</p>
                        </div>
                        <div class="col-md-6 mt-1">
                            <label>{{__('db.Paid By')}}</label>
                            <select name="edit_paid_by_id" class="form-control selectpicker">
                                <option value="1">{{ __('db.Cash') }}</option>
                                <option value="3">{{ __('db.Credit Card') }}</option>
                                <option value="4">{{ __('db.Cheque') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group mt-2">
                        <div class="card-element" class="form-control">
                        </div>
                        <div class="card-errors" role="alert"></div>
                    </div>
                    <div id="edit-cheque">
                        <div class="form-group">
                            <label>{{__('db.Cheque Number')}} *</label>
                            <input type="text" name="edit_cheque_no" class="form-control">
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label> {{__('db.Account')}}</label>
                            <select class="form-control selectpicker" name="account_id">
                            @foreach($lims_account_list as $account)
                                <option value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                            @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mt-1">
                            <div class="form-group">
                                <label>{{__('db.Attach Document')}}</label>
                                <x-info title="Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported" type="info" />
                                <input type="file" name="edit_document" class="form-control" />
                                <small id="current_edit_document_link" class="text-muted"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label>{{ __('db.Payment Date') }}</label>
                            <input type="text" name="payment_at" id="edit_payment_at" class="form-control"
                                value="" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>{{__('db.Payment Note')}}</label>
                        <textarea rows="3" class="form-control" name="edit_payment_note"></textarea>
                    </div>

                    <input type="hidden" name="payment_id">

                    <button type="submit" class="btn btn-primary">{{__('db.update')}}</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">

    $('#toggle-filter').on('click', function() {
        $('#filter-card').slideToggle('slow');
    });

    $(function () {
        $('#payment_at').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        }).datepicker("setDate", new Date());
        $('#edit_payment_at').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });
    });

    $("ul#purchase").siblings('a').attr('aria-expanded','true');
    $("ul#purchase").addClass("show");
    $("ul#purchase #purchase-list-menu").addClass("active");

    @if($lims_pos_setting_data)
        var public_key = <?php echo json_encode($lims_pos_setting_data->stripe_public_key) ?>;
    @endif
    var all_permission = <?php echo json_encode($all_permission) ?>;

    var purchase_id = [];
    var user_verified = <?php echo json_encode(config('app.user_verified'))?>;
    var starting_date = <?php echo json_encode($starting_date); ?>;
    var ending_date = <?php echo json_encode($ending_date); ?>;
    var warehouse_id = <?php echo json_encode($warehouse_id); ?>;
    var purchase_status = <?php echo json_encode($purchase_status); ?>;
    var payment_status = <?php echo json_encode($payment_status); ?>;

    var show_purchase_product_details = <?php echo json_encode(gen_setting()->show_products_details_in_purchase_table) ?>;
    if(show_purchase_product_details == 1){
        var columns = [
            {"data": "key"},
            {"data": "date"},
            {"data": "reference_no"},
            {"data": "created_by"},
            {"data": "supplier"},
            {"data": "products"},
            {"data": "products_qty"},
            {"data": "purchase_status"},
            {"data": "grand_total"},
            {"data": "returned_amount"},
            {"data": "paid_amount"},
            {"data": "due"},
            {"data": "currency"},
            {"data": "pay_term"},
            {"data": "due_date"},
            {"data": "payment_status"}
        ];
    }else{
        var columns = [
            {"data": "key"},
            {"data": "date"},
            {"data": "reference_no"},
            {"data": "created_by"},
            {"data": "supplier"},
            {"data": "purchase_status"},
            {"data": "grand_total"},
            {"data": "returned_amount"},
            {"data": "paid_amount"},
            {"data": "due"},
            {"data": "currency"},
            {"data": "pay_term"},
            {"data": "due_date"},
            {"data": "payment_status"}
        ];
    }

    var field_name = <?php echo json_encode($field_name) ?>;
    for(i = 0; i < field_name.length; i++) {
        columns.push({"data": field_name[i]});
    }
    columns.push({"data": "options"});

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $("#warehouse_id").val(warehouse_id);
    $("#purchase-status").val(purchase_status);
    $("#payment-status").val(payment_status);

    $('.selectpicker').selectpicker('refresh');

    function confirmDelete() {
        if (confirm("Are you sure want to delete?")) {
            return true;
        }
        return false;
    }

    function confirmDeletePayment() {
        if (confirm("Are you sure want to delete? If you delete this money will be refunded")) {
            return true;
        }
        return false;
    }

    $(document).on("click", "tr.purchase-link td:not(:first-child, :nth-child(2), :last-child)", function(){
        var tr = $(this).closest('tr');
        var purchase = tr.data('purchase');
        purchaseDetails(purchase, tr);
    });

    $(document).on("click", ".view", function(){
        var tr = $(this).closest('tr');
        var purchase = tr.data('purchase');
        purchaseDetails(purchase, tr);
    });

    $("#print-btn").on("click", function(){
        var purchase = currentPurchase;

        var a = window.open('');
        a.document.write('<html><head>');
        a.document.write('<style>');
        a.document.write('body{font-family:sans-serif;line-height:1.5;font-size:13px;}');
        a.document.write('h3{text-align:center;margin-bottom:2px;}');
        a.document.write('table{width:100%;border-collapse:collapse;margin-top:15px;}');
        a.document.write('th,td{border:1px solid #000;padding:7px;text-align:left;}');
        a.document.write('.no-border td{border:none;padding:0;vertical-align:top;}');
        a.document.write('</style>');
        a.document.write('</head><body>');

        // Title
        a.document.write('<h3>{{gen_setting()->site_title}}</h3>');
        a.document.write('<p style="text-align:center"><i>{{__("db.Purchase Details")}}</i></p>');

        // বাম: Date Info | ডান: From
        a.document.write('<table class="no-border" style="margin-top:5px;">');
        a.document.write('<tr>');

        // বাম
        a.document.write('<td style="width:50%;padding-left:0;vertical-align:top;">');
        a.document.write('<span>{{__("db.date")}}:</span> ' + purchase[0] + '<br>');
        a.document.write('<span>{{__("db.reference")}}:</span> ' + purchase[1] + '<br>');
        a.document.write('<span>{{__("db.Purchase Status")}}:</span> ' + purchase[2] + '<br>');
        a.document.write('<span>{{__("db.Currency")}}:</span> ' + purchase[26] + '<br>');
        a.document.write('<span>{{__("db.Exchange Rate")}}:</span> ' + (purchase[27] ? purchase[27] : 'N/A') + '<br>');
        if(purchase[28])
            a.document.write('<span>Payment Term:</span> ' + purchase[28] + ' ' + purchase[29] + '<br>');
        if(purchase[30])
            a.document.write('<span>Due Date:</span> ' + purchase[30] + '<br>');
        if(purchase[25])
            a.document.write('<span>{{__("db.Attach Document")}}:</span> ' + purchase[25] + '<br>');
        a.document.write('</td>');

        // ডান: From
        a.document.write('<td style="width:50%;padding-right:0;vertical-align:top;text-align:right;">');
        a.document.write('<strong>{{__("db.From")}}:</strong><br>');
        a.document.write((purchase[7]  ? purchase[7]  : '') + '<br>');
        a.document.write((purchase[8]  ? purchase[8]  : '') + '<br>');
        a.document.write((purchase[9]  ? purchase[9]  : '') + '<br>');
        a.document.write((purchase[10] ? purchase[10] : '') + '<br>');
        a.document.write((purchase[11] ? purchase[11] : '') + '<br>');
        a.document.write((purchase[12] ? purchase[12] : '') + '<br>');
        a.document.write('</td>');

        a.document.write('</tr></table>');

        // To: Warehouse
        a.document.write('<p style="margin:5px 5px 10px 0;">');
        a.document.write('<strong>{{__("db.To")}}:</strong> ');
        a.document.write((purchase[4] ? purchase[4] : '') + ' &nbsp;|&nbsp; ');
        a.document.write((purchase[5] ? purchase[5] : '') + ' &nbsp;|&nbsp; ');
        a.document.write((purchase[6] ? purchase[6] : ''));
        a.document.write('</p>');

        // Table — modal থেকে হুবহু (colspan rowspan সহ)
        var tableHTML = document.querySelector("table.product-purchase-list").outerHTML;
        a.document.write(tableHTML);

        // Footer
        a.document.write('<br><p><strong>{{__("db.note")}}:</strong> ' + purchase[22] + '</p>');
        a.document.write('<p><strong>{{__("db.Created By")}}:</strong> ' + purchase[23] + ' | ' + purchase[24] + '</p>');

        a.document.write('</body></html>');
        a.document.close();
        a.focus();
        setTimeout(function(){ a.print(); a.close(); }, 500);
    });

    $(document).on("click", "table.purchase-list tbody .add-payment, #inv-action-bar .add-payment", function(event) {
        $("#cheque").hide();
        $(".card-element").hide();
        $('select[name="paid_by_id"]').val(1);
        $('.selectpicker').selectpicker('refresh');
        const purchase_id = String($(this).data('id'));
        const balance = parseFloat(String($(this).data('due')).replace(/,/g, '') * $(this).data('exchange_rate')) || 0;
        const currency_name = $(this).data('currency_name');
        const currency_id = $(this).data('currency_id');
        const exchange_rate = parseFloat($(this).data('exchange_rate')) || 1;
        
        $('input[name="amount"]').val(balance);
        $('input[name="balance"]').val(balance);
        $('input[name="paying_amount"]').val(balance);
        $('input[name="purchase_id"]').val(purchase_id);
        // Fill readonly currency info
        $('#currency_display').text(currency_name);
        $('#exchange_rate_display').text(exchange_rate.toFixed(2));

        // Hidden inputs for backend
        $('#currency_id').val(currency_id);
        $('#exchange_rate').val(exchange_rate);
    });

    $(document).on("click", "table.purchase-list tbody .get-payment, #inv-action-bar .get-payment", function(event) {
        var id = $(this).data('id').toString();
        $.get('purchases/getpayment/' + id, function(data) {
            $(".payment-list tbody").remove();
            var newBody = $("<tbody>");
            payment_date  = data[0];
            payment_reference = data[1];
            paid_amount = data[2];
            paying_method = data[3];
            payment_id = data[4];
            payment_note = data[5];
            cheque_no = data[6];
            change = data[7];
            paying_amount = data[8];
            account_name = data[9];
            account_id = data[10];
            payment_at = data[11];
            payment_document  = data[12];

            $.each(payment_date, function(index){
                var newRow = $("<tr>");
                var cols = '';

                cols += '<td>' + payment_date[index] + '</td>';
                cols += '<td>' + payment_reference[index] + '</td>';
                cols += '<td>' + account_name[index] + '</td>';
                cols += '<td>' + paid_amount[index] + '</td>';
                cols += '<td>' + paying_method[index] + '</td>';
                cols += '<td>' + payment_at[index] + '</td>';
                cols += '<td><div class="btn-group"><button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button><ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">';
                if(payment_document[index])
                    cols += '<li><a href="{{url("documents/add-payment")}}/'+payment_document[index]+'" target="_blank" class="btn btn-link"><i class="ti ti-file"></i> View Document</a></li><li class="divider"></li>';

                if(all_permission.indexOf("purchase-payment-edit") != -1)
                    cols += '<li><button type="button" class="btn btn-link edit-btn" data-id="' + payment_id[index] +'" data-clicked=false data-toggle="modal" data-target="#edit-payment"><i class="ti ti-edit"></i> Edit</button></li><li class="divider"></li>';
                if(all_permission.indexOf("purchase-payment-delete") != -1)
                    cols += '<form action="{{ route('purchase.delete-payment') }}" method="POST">'+'@csrf'+'<li><input type="hidden" name="id" value="' + payment_id[index] + '" /> <button type="submit" class="btn btn-link" onclick="return confirmDeletePayment()"><i class="ti ti-trash"></i> Delete</button></li></form>';
                cols += '</ul></div></td>';
                newRow.append(cols);
                newBody.append(newRow);
                $("table.payment-list").append(newBody);
            });
            $('#view-payment').modal('show');
        });
    });

    $(document).on("click", "table.payment-list .edit-btn", function(event) {
        $(".edit-btn").attr('data-clicked', true);
        $(".card-element").hide();
        $("#edit-cheque").hide();
        $('#edit-payment select[name="edit_paid_by_id"]').prop('disabled', false);
        var id = $(this).data('id').toString();
        $.each(payment_id, function(index){
            if(payment_id[index] == parseFloat(id)){
                $('input[name="payment_id"]').val(payment_id[index]);
                $('#edit-payment select[name="account_id"]').val(account_id[index]);
                if(paying_method[index] == 'Cash')
                    $('select[name="edit_paid_by_id"]').val(1);
                else if(paying_method[index] == 'Credit Card'){
                    $('select[name="edit_paid_by_id"]').val(3);
                    @if($lims_pos_setting_data && (strlen($lims_pos_setting_data->stripe_public_key)>0) && (strlen($lims_pos_setting_data->stripe_secret_key )>0))
                        $.getScript( "vendor/stripe/checkout.js" );
                        $(".card-element").show();
                    @endif
                    $("#edit-cheque").hide();
                    $('#edit-payment select[name="edit_paid_by_id"]').prop('disabled', true);
                }
                else{
                    $('select[name="edit_paid_by_id"]').val(4);
                    $("#edit-cheque").show();
                    $('input[name="edit_cheque_no"]').val(cheque_no[index]);
                    $('input[name="edit_cheque_no"]').attr('required', true);
                }
                $('input[name="edit_date"]').val(payment_date[index]);
                $("#payment_reference").html(payment_reference[index]);
                $('input[name="edit_amount"]').val(paid_amount[index]);
                $('input[name="edit_paying_amount"]').val(paying_amount[index]);
                $('.change').text(change[index]);
                $('textarea[name="edit_payment_note"]').val(payment_note[index]);
                $('input[name="payment_at"]').val(payment_at[index]);
                return false;
            }
        });
        $('.selectpicker').selectpicker('refresh');
        $('#view-payment').modal('hide');
    });

    $('select[name="paid_by_id"]').on("change", function() {
        var id = $('select[name="paid_by_id"]').val();
        $('input[name="cheque_no"]').attr('required', false);
        $(".payment-form").off("submit");
        if (id == 3) {
            $.getScript( "vendor/stripe/checkout.js" );
            $(".card-element").show();
            $("#cheque").hide();
        } else if (id == 4) {
            $("#cheque").show();
            $(".card-element").hide();
            $('input[name="cheque_no"]').attr('required', true);
        } else {
            $(".card-element").hide();
            $("#cheque").hide();
        }
    });

    $('input[name="paying_amount"]').on("focusout", function() {
        if( $(this).val() < $('input[name="amount"]').val() ) {
            $('input[name="amount"]').val($(this).val());
        } else {
            $(".change").text(parseFloat( $(this).val() - $('input[name="amount"]').val() ).toFixed({{gen_setting()->decimal}}));
    }
    });

    $('input[name="amount"]').on("input", function() {
        if( $(this).val() > parseFloat($('input[name="paying_amount"]').val()) ) {
            alert('Paying amount cannot be bigger than recieved amount');
            $(this).val('');
        }
        else if( $(this).val() > parseFloat($('input[name="balance"]').val()) ) {
            alert('Paying amount cannot be bigger than due amount');
            $(this).val('');
        }
        $(".change").text(parseFloat($('input[name="paying_amount"]').val() - $(this).val()).toFixed({{gen_setting()->decimal}}));
    });

    $('select[name="edit_paid_by_id"]').on("change", function() {
        var id = $('select[name="edit_paid_by_id"]').val();
        $('input[name="edit_cheque_no"]').attr('required', false);
        $(".payment-form").off("submit");
        if (id == 3) {
            $(".edit-btn").attr('data-clicked', true);
            $.getScript( "vendor/stripe/checkout.js" );
            $(".card-element").show();
            $("#edit-cheque").hide();
        } else if (id == 4) {
            $("#edit-cheque").show();
            $(".card-element").hide();
            $('input[name="edit_cheque_no"]').attr('required', true);
        } else {
            $(".card-element").hide();
            $("#edit-cheque").hide();
        }
    });

    $('input[name="edit_amount"]').on("input", function() {
        if( $(this).val() > parseFloat($('input[name="edit_paying_amount"]').val()) ) {
            alert('Paying amount cannot be bigger than recieved amount');
            $(this).val('');
        }
        $(".change").text(parseFloat($('input[name="edit_paying_amount"]').val() - $(this).val()).toFixed({{gen_setting()->decimal}}));
    });

    $('input[name="edit_paying_amount"]').on("input", function() {
        $(".change").text(parseFloat( $(this).val() - $('input[name="edit_amount"]').val() ).toFixed({{gen_setting()->decimal}}));
    });

    let targets = [];

    if (show_purchase_product_details == 1) {
        targets = [0, 3, 4, 5, 6, 7, 9, 11, 12, 13, 14];
    } else {
        targets = [0, 3, 4, 5, 7, 9, 10, 11, 12];
    }

    let buttons = [];
    @can('purchase_export')
        buttons.push([
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'excel',
                text: '<i title="export to excel" class="ti ti-file-type-xls"></i>',
                exportOptions: {
                    columns: ':visible:not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.excelHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':visible:not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
        ]);
    @endcan

    buttons.push([
        {
            text: '<i title="delete" class="ti ti-x"></i>',
            className: 'buttons-delete',
            action: function ( e, dt, node, config ) {
                if(user_verified == '1') {
                    purchase_id.length = 0;
                    $(':checkbox:checked').each(function(i){
                        if(i){
                            var purchase = $(this).closest('tr').data('purchase');
                            if(purchase)
                                purchase_id[i-1] = purchase[3];
                        }
                    });
                    if(purchase_id.length && confirm("Are you sure want to delete?")) {
                        $.ajax({
                            type:'POST',
                            url:'purchases/deletebyselection',
                            data:{
                                purchaseIdArray: purchase_id
                            },
                            success:function(res) {
                                if (!res || !Array.isArray(res.deleted)) {
                                    alert(res.message || 'Unexpected server response');
                                    return;
                                }
                                res.deleted.forEach(function(id){
                                    var row = $('#purchase-table tbody tr[data-id="'+id+'"]');
                                    if (row.length) dt.row(row).remove();
                                });
                                dt.draw(false);
                                alert(res.message || 'Deleted');
                                //dt.rows({ page: 'current', selected: true }).deselect();
                                //dt.rows({ page: 'current', selected: true }).remove().draw(false);
                            },
                            error: function(xhr) {
                                // if the server responded with HTML (redirect/login), show a friendly message
                                var contentType = xhr.getResponseHeader('Content-Type') || '';
                                if (contentType.indexOf('text/html') !== -1) {
                                    console.warn('Server returned HTML — likely a redirect to login.', xhr.responseText);
                                    alert('Session expired or not authenticated. Please login again.');
                                    // Optional: redirect user to login page
                                    window.location = '/login';
                                    return;
                                }
                                let json = null;
                                try { json = xhr.responseJSON; } catch(e){}
                                alert((json && json.message) ? json.message : 'Delete failed: ' + xhr.status);
                            }
                        });
                    }
                    else if(!purchase_id.length)
                        alert('Nothing is selected!');
                }
                else
                    alert('This feature is disable for demo!');
            }
        },
        {
            extend: 'colvis',
            text: '<i title="column visibility" class="ti ti-eye"></i>',
            columns: ':gt(0)'
        },
    ]);

    var purchaseTable = $('#purchase-table').DataTable( {
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"{{url('purchases/purchase-data')}}",
            data: function (d) {
                d.all_permission   = all_permission;
                d.starting_date    = $('input[name=starting_date]').val();
                d.ending_date      = $('input[name=ending_date]').val();
                d.warehouse_id     = $('#warehouse_id').val();
                d.purchase_status  = $('#purchase-status').val();
                d.payment_status   = $('#payment-status').val();
            },
            dataType: "json",
            type:"post",
            /*success:function(data){
                console.log(data);
            }*/
        },
        "createdRow": function( row, data, dataIndex ) {
            $(row).addClass('purchase-link');
            $(row).attr('data-purchase', data['purchase']);
        },
        "columns": columns,
        'language': {
            /*'searchPlaceholder': "{{__('db.Type date or purchase reference')}}",*/
            'lengthMenu': '_MENU_ {{__("db.records per page")}}',
             "info":      '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search":  '{{__("db.Search")}}',
            'paginate': {
                    'previous': '<i class="ti ti-chevron-left"></i>',
                    'next': '<i class="ti ti-chevron-right"></i>'
            }
        },
        order:[['1', 'desc']],
        'columnDefs': [
            {
                "orderable": false,
                'targets': targets
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
        buttons: buttons,
        drawCallback: function () {
            var api = this.api();
            datatable_sum(api, false);
        }
    });

    function datatable_sum(dt_selector, is_calling_first) {

        if(show_purchase_product_details == 1){
             if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
                var rows = dt_selector.rows( '.selected' ).indexes();
                $( dt_selector.column( 8 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 8, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 9 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 9, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 10 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 10, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 11 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 11, { page: 'current' } ).data().sum()));
            }
            else {
                $( dt_selector.column( 8 ).footer() ).html(formatCurrency(dt_selector.column( 8, {page:'current'} ).data().sum()));
                $( dt_selector.column( 9 ).footer() ).html(formatCurrency(dt_selector.column( 9, {page:'current'} ).data().sum()));
                $( dt_selector.column( 10 ).footer() ).html(formatCurrency(dt_selector.column( 10, {page:'current'} ).data().sum()));
                $( dt_selector.column( 11 ).footer() ).html(formatCurrency(dt_selector.column( 11, {page:'current'} ).data().sum()));
            }
        }else{
            if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
                var rows = dt_selector.rows( '.selected' ).indexes();
                $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 6, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 8 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 8, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 9 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 9, { page: 'current' } ).data().sum()));
            }
            else {
                $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.column( 6, {page:'current'} ).data().sum()));
                $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.column( 7, {page:'current'} ).data().sum()));
                $( dt_selector.column( 8 ).footer() ).html(formatCurrency(dt_selector.column( 8, {page:'current'} ).data().sum()));
                $( dt_selector.column( 9 ).footer() ).html(formatCurrency(dt_selector.column( 9, {page:'current'} ).data().sum()));
            }
        }

    }

    $('#warehouse_id, #purchase-status, #payment-status').on('change', function () {
        purchaseTable.ajax.reload();
    });

    // for date range picker
    $('.daterangepicker-field').on('apply.daterangepicker', function(ev, picker) {
        $('input[name=starting_date]').val(picker.startDate.format('YYYY-MM-DD'));
        $('input[name=ending_date]').val(picker.endDate.format('YYYY-MM-DD'));
        purchaseTable.ajax.reload();
    });

    // Show loader on request
    purchaseTable.on('preXhr.dt', function () {
        $('#filter-loading').show();
    });

    // Hide loader after draw
    purchaseTable.on('xhr.dt', function () {
        $('#filter-loading').hide();
    });

    // ========= NUMBER TO WORDS HELPER =========
    function numberToWords(num) {
        if (num === undefined || num === null || isNaN(num)) return '';
        var n = parseFloat(num);
        var ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
                    'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
                    'Seventeen','Eighteen','Nineteen'];
        var tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        function words(n) {
            if (n < 20) return ones[n];
            if (n < 100) return tens[Math.floor(n/10)] + (n%10 ? ' '+ones[n%10] : '');
            if (n < 1000) return ones[Math.floor(n/100)] + ' Hundred' + (n%100 ? ' '+words(n%100) : '');
            if (n < 1000000) return words(Math.floor(n/1000)) + ' Thousand' + (n%1000 ? ' '+words(n%1000) : '');
            if (n < 1000000000) return words(Math.floor(n/1000000)) + ' Million' + (n%1000000 ? ' '+words(n%1000000) : '');
            return words(Math.floor(n/1000000000)) + ' Billion' + (n%1000000000 ? ' '+words(n%1000000000) : '');
        }
        var intPart = Math.floor(n);
        var decPart = Math.round((n - intPart) * 100);
        var result = (intPart === 0 ? 'Zero' : words(intPart)) + ' only';
        return result;
    }

    // ========= STATUS BADGE HELPER =========
    function purchaseStatusBadge(statusText) {
        var cls = 'inv-badge-default';
        var t = (statusText || '').toLowerCase();
        if (t.indexOf('recieved') !== -1 || t.indexOf('received') !== -1) cls = 'inv-badge-completed';
        else if (t.indexOf('partial') !== -1) cls = 'inv-badge-partial';
        else if (t.indexOf('pending') !== -1) cls = 'inv-badge-pending';
        else if (t.indexOf('ordered') !== -1) cls = 'inv-badge-processing';
        return '<span class="inv-badge ' + cls + '">' + (statusText || '—') + '</span>';
    }
    function paymentStatusBadge(statusText) {
        var cls = 'inv-badge-default';
        var t = (statusText || '').toLowerCase();
        if (t.indexOf('paid') !== -1) cls = 'inv-badge-paid';
        else if (t.indexOf('due') !== -1) cls = 'inv-badge-due';
        else if (t.indexOf('partial') !== -1) cls = 'inv-badge-partial';
        else if (t.indexOf('pending') !== -1) cls = 'inv-badge-pending';
        return '<span class="inv-badge ' + cls + '">' + (statusText || '—') + '</span>';
    }

    // ========= MAIN PURCHASE DETAILS FUNCTION =========
    function purchaseDetails(purchase, trElement = null){
        currentPurchase = purchase;

        // ---- Populate dynamic action buttons from TR options ----
        if(trElement) {
            var optionsHtml = trElement.find('.edit-options');

            // Edit
            var editBtn = optionsHtml.find('.ti ti-edit').parent('a');
            if(editBtn.length) { $('#inv-edit-btn').show().attr('href', editBtn.attr('href')); } else { $('#inv-edit-btn').hide(); }
            
            // View Payment
            var getPaymentBtn = optionsHtml.find('.get-payment');
            if(getPaymentBtn.length) { 
                $('#inv-get-payment-btn').show().attr('data-id', getPaymentBtn.attr('data-id')); 
            } else { $('#inv-get-payment-btn').hide(); }
            
            // Add Payment
            var addPaymentBtn = optionsHtml.find('button.add-payment');
            var p_grandTotal = parseFloat(purchase[20] || 0);
            var p_returnedAmount = parseFloat(purchase[31] || 0);
            var p_paidAmount = parseFloat(purchase[21] || 0);
            var p_dueAmount = p_grandTotal - p_returnedAmount - p_paidAmount;
            
            if(addPaymentBtn.length && p_dueAmount > 0.01) {
                $('#inv-add-payment-btn').show()
                    .attr('data-id', addPaymentBtn.attr('data-id'))
                    .attr('data-due', addPaymentBtn.attr('data-due'))
                    .attr('data-currency_id', addPaymentBtn.attr('data-currency_id'))
                    .attr('data-currency_name', addPaymentBtn.attr('data-currency_name'))
                    .attr('data-exchange_rate', addPaymentBtn.attr('data-exchange_rate'));
            } else { $('#inv-add-payment-btn').hide(); }
            
            // Delete
            var deleteBtn = optionsHtml.find('.ti ti-trash').closest('form');
            if(deleteBtn.length) { $('#inv-delete-form').show().attr('action', deleteBtn.attr('action')); } else { $('#inv-delete-form').hide(); }
        } else {
            // Hide all dynamic if no tr context
            $('#inv-action-bar .inv-btn').not('#print-btn').not('#close-btn').hide();
        }

        // ---- Header: date, reference#, status, payment ----
        var refNo = purchase[1] || '—';
        $('#inv-date').text(purchase[0] || '—');
        $('#inv-ref').text(refNo);

        var purchaseStatusText  = typeof purchase[2]  === 'string' ? purchase[2]  : (purchase[2]  || '—');
        // We don't have payment_status mapped to an exact index in `purchase` array in purchaseData,
        // Wait, payment_status is actually inside trElement, but let's check due amount instead.
        var dueAmountCheck = parseFloat(purchase[20]) - parseFloat(purchase[31] || 0) - parseFloat(purchase[21]);
        var payStatusText = (dueAmountCheck > 1) ? 'Due' : 'Paid';

        purchaseStatusText = $('<div>').html(purchaseStatusText).text().trim();
        $('#inv-status').html(purchaseStatusBadge(purchaseStatusText));
        $('#inv-payment').html(paymentStatusBadge(payStatusText));
        
        var currencyStr = purchase[26] || '{{gen_setting()->currency ?? "USD"}}';
        var exchangeRateVal = parseFloat(purchase[27] || 1).toFixed(2);
        $('#inv-currency-rate').text(currencyStr + ' / ' + exchangeRateVal);

        // ---- FROM (Supplier) ----
        var fromHtml = '<strong>' + (purchase[7] || '—') + '</strong>'; // supplier name
        if (purchase[8]) fromHtml += '<div class="addr-row"><span class="addr-lbl">Company:</span><span>' + purchase[8] + '</span></div>';
        if (purchase[10]) fromHtml += '<div class="addr-row"><span class="addr-lbl">Phone:</span><span>' + purchase[10] + '</span></div>';
        if (purchase[11]) fromHtml += '<div class="addr-row"><span class="addr-lbl">Address:</span><span>' + purchase[11] + '</span></div>';
        if (purchase[9]) fromHtml += '<div class="addr-row"><span class="addr-lbl">Email:</span><span>'  + purchase[9] + '</span></div>';
        if (purchase[12]) fromHtml += '<div class="addr-row"><span class="addr-lbl">City:</span><span>'  + purchase[12] + '</span></div>';
        $('#inv-from').html(fromHtml);

        // ---- TO (Warehouse) ----
        var billHtml = '<strong>' + (purchase[4] || '—') + '</strong>'; // warehouse name
        if (purchase[5]) billHtml += '<div class="addr-row"><span class="addr-lbl">Phone:</span><span>'       + purchase[5] + '</span></div>';
        if (purchase[6]) billHtml += '<div class="addr-row"><span class="addr-lbl">Address:</span><span>'     + purchase[6] + '</span></div>';
        $('#inv-bill-to').html(billHtml);

        // ---- Product rows ----
        var loaderHtml = '<tbody id="inv-loader-tbody"><tr><td colspan="9" class="text-center"><div class="loader" title="4" style="border:none;min-height:150px;display:flex;align-items:center;justify-content:center;"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="30px" viewBox="0 0 24 30" style="enable-background:new 0 0 50 50;" xml:space="preserve"><rect x="0" y="0" width="4" height="10" fill="#333"><animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0" dur="0.6s" repeatCount="indefinite"></animateTransform></rect><rect x="10" y="0" width="4" height="10" fill="#333"><animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0.2s" dur="0.6s" repeatCount="indefinite"></animateTransform></rect><rect x="20" y="0" width="4" height="10" fill="#333"><animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0.4s" dur="0.6s" repeatCount="indefinite"></animateTransform></rect></svg></div></td></tr></tbody>';
        $(".product-purchase-list tbody").remove();
        $(".product-purchase-list").append(loaderHtml);

        $.get('purchases/product_purchase/' + purchase[3], function(data) {
            $(".product-purchase-list tbody").remove();
            if(data == 'Something is wrong!') {
                var newBody = $("<tbody>");
                var newRow = $("<tr>");
                cols = '<td colspan="9">Something is wrong!</td>';
                newRow.append(cols);
                newBody.append(newRow);
                $("table.product-purchase-list").append(newBody);
            }
            else {
                var name_code = data[0];
                var qty = data[1];
                var unit_code = data[2];
                var tax = data[3];
                var tax_rate = data[4];
                var discount = data[5];
                var subtotal = data[6];
                var batch_no = data[7];
                var returned = data[8];
                var newBody = $("<tbody>");

                $.each(name_code, function(index) {
                    var newRow = $("<tr>");
                    var cols = '';
                    cols += '<td>' + (index+1) + '</td>';
                    cols += '<td><strong>' + name_code[index] + '</strong></td>';
                    cols += '<td>' + (batch_no[index] || '—') + '</td>';
                    cols += '<td>' + qty[index] + ' ' + (unit_code[index] || '') + '</td>';
                    cols += '<td>' + (returned[index] || '0') + '</td>';
                    cols += '<td>' + formatCurrency(subtotal[index] / qty[index]) + '</td>';
                    cols += '<td>' + formatCurrency(tax[index]) + ' (' + tax_rate[index] + '%)</td>';
                    var discHtml = discount[index] > 0 ? '<span class="disc-red">' + formatCurrency(discount[index]) + '</span>' : formatCurrency(discount[index]);
                    cols += '<td>' + discHtml + '</td>';
                    cols += '<td><strong>' + formatCurrency(subtotal[index]) + '</strong></td>';
                    newRow.append(cols);
                    newBody.append(newRow);
                });
                $("table.product-purchase-list").append(newBody);

                // ---- Totals ----
                var totalCost   = parseFloat(purchase[15] || 0);
                var orderTax    = parseFloat(purchase[16] || 0);
                var discountAmt = parseFloat(purchase[18] || 0);
                var shipping    = parseFloat(purchase[19] || 0);
                var grandTotal  = parseFloat(purchase[20] || 0);
                var paidAmount  = parseFloat(purchase[21] || 0);
                var returnedAmount = parseFloat(purchase[31] || 0);
                var dueAmount   = grandTotal - returnedAmount - paidAmount;
                
                $('#inv-total').text(formatCurrency(totalCost));
                $('#inv-order-tax').text('+ ' + formatCurrency(orderTax));
                $('#inv-discount').text('- ' + formatCurrency(discountAmt));
                $('#inv-shipping').text('+ ' + formatCurrency(shipping));
                $('#inv-grand-total').text(formatCurrency(grandTotal));
                $('#inv-paid').text(formatCurrency(paidAmount));
                
                if(returnedAmount > 0) {
                    $('#inv-returned-row').show();
                    $('#inv-returned').text(formatCurrency(returnedAmount));
                } else {
                    $('#inv-returned-row').hide();
                }
                
                $('#inv-due').text(formatCurrency(dueAmount < 0 ? 0 : dueAmount));
                
                // Set In Words
                $('#inv-inwords').text(numberToWords(grandTotal));

                // ---- Barcode ----
                try {
                    var barcodeVal = refNo.replace(/[^A-Za-z0-9\-\.\$\%\/\+\s]/g, '');
                    if (barcodeVal.length < 1) barcodeVal = '000000';
                    JsBarcode('#inv-barcode', barcodeVal, {
                        format: 'CODE128',
                        lineColor: '#000',
                        width: 2,
                        height: 55,
                        displayValue: true,
                        fontSize: 12,
                        margin: 4
                    });
                    $('#inv-barcode').show();
                } catch(e) {
                    $('#inv-barcode').hide();
                    console.warn('Barcode error', e);
                }

                // ---- QR Code ----
                $('#inv-qrcode').empty();
                try {
                    var qrContent = 'Purchase: ' + refNo +
                        '\nDate: ' + (purchase[0] || '') +
                        '\nSupplier: ' + ($('<div>').html(purchase[7] || '').text()) +
                        '\nTotal: ' + formatCurrency(grandTotal) +
                        '\nPaid: '  + formatCurrency(paidAmount);
                    new QRCode(document.getElementById('inv-qrcode'), {
                        text: qrContent,
                        width: 80,
                        height: 80,
                        colorDark: '#000',
                        colorLight: '#fff',
                        correctLevel: QRCode.CorrectLevel.M
                    });
                } catch(e) {
                    console.warn('QR Code error', e);
                }
            }
        });

        var htmlfooter = '<div class="row mt-4" style="font-size:13px; color:#444; border-top: 1px solid #e2e8f0; padding-top:15px;">';
        
        // Column 1: Additional Info
        htmlfooter += '<div class="col-md-6">';
        if (purchase[27] && purchase[27].trim() !== 'N/A' && purchase[27].trim() !== '' && purchase[27].trim() !== 'null') {
            htmlfooter += '<p class="mb-1"><strong>{{__("db.Exchange Rate")}}:</strong> ' + purchase[27] + '</p>';
        }
        if (purchase[28] && purchase[28].trim() !== 'N/A' && purchase[28].trim() !== '' && purchase[28].trim() !== 'null') {
            htmlfooter += '<p class="mb-1"><strong>Payment Term:</strong> ' + purchase[28] + ' ' + (purchase[29] || '') + '</p>';
        }
        if (purchase[30] && purchase[30].trim() !== 'N/A' && purchase[30].trim() !== '' && purchase[30].trim() !== 'null') {
            htmlfooter += '<p class="mb-1"><strong>Due Date:</strong> ' + purchase[30] + '</p>';
        }
        if (purchase[25] && purchase[25].trim() !== 'N/A' && purchase[25].trim() !== '' && purchase[25].trim() !== 'null') {
            htmlfooter += '<p class="mb-1"><strong>{{__("db.Attach Document")}}:</strong> <a href="documents/purchase/'+purchase[25]+'" target="_blank" class="text-primary" style="text-decoration:underline;">Download</a></p>';
        }
        htmlfooter += '</div>';

        // Column 2: Notes & Creator
        htmlfooter += '<div class="col-md-6">';
        if (purchase[22] && purchase[22].trim() !== 'N/A' && purchase[22].trim() !== '' && purchase[22].trim() !== 'null') {
            htmlfooter += '<p class="mb-1"><strong>{{__("db.note")}}:</strong> ' + purchase[22] + '</p>';
        }
        if (purchase[23]) {
            htmlfooter += '<p class="mb-1"><strong>{{__("db.Created By")}}:</strong> ' + purchase[23] + ' (' + (purchase[24] || '') + ')</p>';
        }
        htmlfooter += '</div>';

        htmlfooter += '</div>';

        $('#purchase-footer').html(htmlfooter);

        $('#purchase-details').modal('show');
    }

    $(document).on('submit', '.payment-form', function(e) {
        if( $('input[name="paying_amount"]').val() < parseFloat($('#amount').val()) ) {
            alert('Paying amount cannot be bigger than recieved amount');
            $('input[name="amount"]').val('');
            $(".change").text(parseFloat( $('input[name="paying_amount"]').val() - $('#amount').val() ).toFixed({{gen_setting()->decimal}}));
            e.preventDefault();
        }
        else if( $('input[name="edit_paying_amount"]').val() < parseFloat($('input[name="edit_amount"]').val()) ) {
            alert('Paying amount cannot be bigger than recieved amount');
            $('input[name="edit_amount"]').val('');
            $(".change").text(parseFloat( $('input[name="edit_paying_amount"]').val() - $('input[name="edit_amount"]').val() ).toFixed({{gen_setting()->decimal}}));
            e.preventDefault();
        }

        $('#edit-payment select[name="edit_paid_by_id"]').prop('disabled', false);
    });

    if(all_permission.indexOf("purchases-delete") == -1)
        $('.buttons-delete').addClass('d-none');


</script>
<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
@endpush
