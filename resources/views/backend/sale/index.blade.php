
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
        @can('sales-add')
            <a href="{{route('sales.create')}}" class="btn btn-info add-sale-btn btn-icon"><i class="ti ti-plus"></i> {{__('db.Add Sale')}}</a>&nbsp;
        @endcan
        @can('sales-import')
            <a href="{{url('sales/sale_by_csv')}}" class="btn btn-primary add-sale-btn btn-icon"><i class="ti ti-copy"></i> {{__('db.Import Sale')}}</a>
        @endcan
        @can('sales-delete')
            <a href="{{ url('sales/deleted_data') }}" class="btn btn-secondary add-sale-btn btn-icon">
                <i class="ti ti-trash"></i>
                 {{ __('Deleted Sales') }}
            </a>
        @endcan
        <button type="button" class="btn btn-warning btn-icon" id="toggle-filter">
            <i class="ti ti-filter"></i> {{ __('db.Filter Sales') }}
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
                            <label>{{__('db.Sale Status')}}</label>
                            <select id="sale-status" class="form-control" name="sale_status">
                                <option value="0">{{__('db.All')}}</option>
                                <option value="1">{{__('db.Completed')}}</option>
                                <option value="2">{{__('db.Pending')}}</option>
                                <option value="4">{{__('db.Returned')}}</option>
                                <option value="5">{{__('db.Processing')}}</option>
                                @if(in_array('restaurant', explode(',', gen_setting()->modules ?? '')))
                                <option value="6">{{__('db.cooked')}}</option>
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group top-fields">
                            <label>{{__('db.Payment Status')}}</label>
                            <select id="payment-status" class="form-control" name="payment_status">
                                <option value="0">{{__('db.All')}}</option>
                                <option value="1">{{__('db.Pending')}}</option>
                                <option value="2">{{__('db.Due')}}</option>
                                <option value="3">{{__('db.Partial')}}</option>
                                <option value="4">{{__('db.Paid')}}</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group top-fields">
                            <label>{{__('db.Payment Method')}}</label>
                            <select id="payment-method" class="form-control" name="payment_method">
                                <option value="0">All</option>
                                <option value="Cash">Cash</option>
                                <option value="Gift Card">Gift Card</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Deposit">Deposit</option>
                                <option value="Points">Points</option>
                                <option value="Pesapal">Pesapal</option>
                                @foreach($options as $option)
                                    @if($option !== 'cash' && $option !== 'card' && $option !== 'card' && $option !== 'cheque' && $option !== 'gift_card' && $option !== 'deposit' && $option !== 'paypal' && $option !== 'pesapal')
                                        <option value="{{$option}}">{{$option}}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group top-fields">
                            <label>Installment</label>
                            <select id="installment-filter" class="form-control" name="installment">
                                <option value="0">All</option>
                                <option value="1">Installment Sale</option>
                                <option value="2">Non-Installment Sale</option>
                            </select>
                        </div>
                    </div>

                    @php
                        $modules = explode(',', gen_setting()->modules ?? '');
                        $showSaleType = in_array('ecommerce', $modules) || in_array('tailoring', $modules);
                    @endphp
                    <div class="col-md-3 @if(!$showSaleType) d-none @endif">
                        <div class="form-group top-fields">
                            <label>{{__('db.Sale Type')}}</label>
                            <select id="sale-type" class="form-control" name="sale_type">
                                <option value="0">{{__('db.All')}}</option>
                                <option value="pos">{{__('db.POS')}}</option>
                                @if(in_array('ecommerce', $modules))
                                <option value="online">{{__('db.eCommerce')}}</option>
                                @endif
                                @if(in_array('tailoring', $modules))
                                <option value="tailoring">{{__('db.tailoring')}}</option>
                                @endif
                                <option value="social_commerce">{{ __('db.Social Commerce') }}</option>
                            </select>
                        </div>
                    </div>
                    @if(Module::has('SocialCommerce') && Module::find('SocialCommerce')->isEnabled() && (\Modules\SocialCommerce\Entities\SocialCommerceSetting::first()->is_active ?? false))
                    <div class="col-md-3">
                        <div class="form-group top-fields">
                            <label>{{ __('db.Social Channel') }}</label>
                            <select id="social-channel" class="form-control" name="social_channel">
                                <option value="0">{{ __('db.All Channels') }}</option>
                                <option value="whatsapp">{{ __('db.WhatsApp') }}</option>
                                <option value="facebook">{{ __('db.Facebook') }}</option>
                                <option value="instagram">{{ __('db.Instagram') }}</option>
                                <option value="telegram">{{ __('db.Telegram') }}</option>
                                <option value="qr">{{ __('db.QR Code') }}</option>
                                <option value="direct">{{ __('db.Direct') }}</option>
                            </select>
                        </div>
                    </div>
                    @endif
                    <div id="filter-loading" class="col-12 text-center my-2" style="display:none;">
                        <span class="spinner-border text-primary spinner-border-sm" role="status"></span>
                        <span>Loading results...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table id="sale-table" class="table sale-list" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th class="not-exported">{{__('db.action')}}</th>
                    <th>{{__('db.date')}}</th>
                    <th>{{__('db.reference')}}</th>
                    <th>{{__('db.Created By')}}</th>
                    <th>{{__('db.customer')}}</th>
                    <th>{{__('db.Warehouse')}}</th>
                    @if (gen_setting()->show_products_details_in_sales_table)
                        <th>{{__('db.Products')}}</th>
                        <th>{{__('db.Quantity')}}</th>
                    @endif
                    <th>{{__('db.Sale Status')}}</th>
                    <th>{{__('db.Payment Status')}}</th>
                    <th>{{__('db.Payment Method')}}({{ config('currency') }})</th>
                    <th>{{__('db.Currency')}}/{{__('db.Exchange Rate')}}</th>
                    <th>{{__('db.Delivery Status')}}</th>
                    <th>{{__('db.Tax')}} ({{ config('currency') }})</th>
                    <th>{{__('db.Order Tax')}} ({{ config('currency') }})</th>
                    <th>{{__('db.grand total')}} ({{ config('currency') }})</th>
                    <th>{{__('db.Returned Amount')}} ({{ config('currency') }})</th>
                    <th>{{__('db.Paid')}} ({{ config('currency') }})</th>
                    <th>{{__('db.Due')}} ({{ config('currency') }})</th>
                    @foreach($custom_fields as $fieldName)
                    <th>{{$fieldName}}</th>
                    @endforeach

                </tr>
            </thead>

            <tfoot class="tfoot active">
                <th></th>
                <th>{{__('db.Total')}}</th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                @if (gen_setting()->show_products_details_in_sales_table)
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
                <th></th>
                <th></th>
                <th></th>
                @foreach($custom_fields as $fieldName)
                <th></th>
                @endforeach
            </tfoot>
        </table>
    </div>
</section>

@include('backend.sale.sale_details_modal')

{{-- Load JsBarcode + QRCode libraries --}}
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<!-- Packing Slip modal -->
<div id="packing-slip-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">Create Packing Slip</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body">
                <form action="{{route('packingSlip.store')}}" method="POST" class="packing-slip-form">
                    @csrf
                  <div class="row">
                        <input type="hidden" name="sale_id">
                        <input type="hidden" name="amount">
                        <div class="col-md-12 form-group">
                            <h5>Product List</h5>
                            <table class="table table-bordered table-hover product-list mt-3">
                                <thead>
                                    <tr>
                                        <th>{{ __('db.name') }}</th>
                                        <th>{{ __('db.Code') }}</th>
                                        <th>Qty</th>
                                        <th>{{ __('db.Unit Price') }}</th>
                                        <th>{{ __('db.Total Price') }}</th>
                                        <th>{{ __('db.Packed') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                  </div>
                  <div class="form-group">
                      <button type="submit" class="btn btn-primary packing-slip-submit-btn">Submit</button>
                  </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="view-payment" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.All')}} {{__('db.Payment')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <table class="table table-hover payment-list">
                    <thead>
                        <tr>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}}</th>
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

@include('backend.partials.add_payment_modal')
<div id="edit-payment" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Update Payment')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('sale.update-payment') }}" method="POST" class="payment-form">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <label>{{__('db.Recieved Amount')}} *</label>
                            <input type="text" name="edit_paying_amount" class="form-control numkey"  step="any" required>
                        </div>
                        <div class="col-md-4">
                            <label>{{__('db.Paying Amount')}} *</label>
                            <input type="text" name="edit_amount" class="form-control"  step="any" required>
                        </div>
                        <div class="col-md-4">
                            <label>{{__('db.Change')}} : </label>
                            <p class="change ml-2">{{number_format(0, gen_setting()->decimal, '.', '')}}</p>
                        </div>
                        <?php
                            $payment_methods = explode(',', $lims_pos_setting_data->payment_options);
                        ?>
                        <div class="col-md-4">
                            <label>{{__('db.Paid By')}}</label>
                            <select name="edit_paid_by_id" class="form-control selectpicker">
                                @if(in_array("cash",$options))
                                <option value="1">Cash</option>
                                @endif
                                @if(in_array("gift_card",$options))
                                <option value="2">Gift Card</option>
                                @endif
                                @if(in_array("card",$options))
                                <option value="3">Credit Card</option>
                                @endif
                                @if(in_array("cheque",$options))
                                <option value="4">Cheque</option>
                                @endif
                                @if(in_array("paypal",$options) && (strlen($lims_pos_setting_data->paypal_live_api_username)>0) && (strlen($lims_pos_setting_data->paypal_live_api_password)>0) && (strlen($lims_pos_setting_data->paypal_live_api_secret)>0))
                                <option value="5">Paypal</option>
                                @endif
                                @if(in_array("deposit",$options))
                                <option value="6">Deposit</option>
                                @endif
                                @if($lims_reward_point_setting_data && $lims_reward_point_setting_data->is_active)
                                <option value="7">Points</option>
                                @endif
                                @foreach($options as $option)
                                    @if($option !== 'cash' && $option !== 'card' && $option !== 'cheque' && $option !== 'gift_card' && $option !== 'deposit' && $option !== 'paypal' && $option !== 'pesapal' && $option !== 'points')
                                        <option value="{{$option}}">{{ucfirst($option)}}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>{{__('db.Payment Receiver')}}</label>
                            <input type="text" name="payment_receiver" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label>{{ __('db.Payment Date') }}</label>
                            <input type="text" name="payment_at" id="edit_payment_at" class="form-control"
                                value="" required>
                        </div>
                        <div class="col-md-12 mt-2">
                            <label>{{__('db.Document')}}</label>
                            <input type="file" name="document" class="form-control">
                        </div>
                    </div>
                    <div class="gift-card form-group">
                        <label> {{__('db.Gift Card')}} *</label>
                        <select id="gift_card_id" name="gift_card_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Gift Card...">
                            @foreach($lims_gift_card_list as $gift_card)
                                <option value="{{$gift_card->id}}">{{$gift_card->card_no}}</option>
                            @endforeach
                        </select>
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
                    <div class="form-group">
                        <label> {{__('db.Account')}}</label>
                        <select class="form-control selectpicker" name="account_id">
                        @foreach($lims_account_list as $account)
                            <option value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                        @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{__('db.Payment Note')}}</label>
                        <textarea rows="3" class="form-control" name="edit_payment_note"></textarea>
                    </div>

                    <input type="hidden" name="payment_id">
                    <input type="hidden" name="installment_id">

                    <button type="submit" class="btn btn-primary">{{__('db.update')}}</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="add-delivery" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Delivery')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('delivery.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>{{__('db.Delivery Reference')}}</label>
                            <p id="dr"></p>
                        </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.Sale Reference')}}</label>
                        <p id="sr"></p>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>{{__('db.status')}} *</label>
                        <select name="status" required class="form-control selectpicker">
                            <option value="1">{{__('db.Packing')}}</option>
                            <option value="2">{{__('db.Delivering')}}</option>
                            <option value="3">{{__('db.Delivered')}}</option>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.Courier')}}</label>
                        <select name="courier_id" id="courier_id" class="selectpicker form-control" data-live-search="true" title="Select courier...">
                            @foreach($lims_courier_list as $courier)
                            <option value="{{$courier->id}}">{{$courier->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mt-2 form-group">
                        <label>{{__('db.Delivered By')}}</label>
                        <input type="text" name="delivered_by" class="form-control">
                    </div>
                    <div class="col-md-6 mt-2 form-group">
                        <label>{{__('db.Recieved By')}} </label>
                        <input type="text" name="recieved_by" class="form-control">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.customer')}} *</label>
                        <p id="customer"></p>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.Attach File')}}</label>
                        <input type="file" name="file" class="form-control">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.Address')}} *</label>
                        <textarea rows="3" name="address" class="form-control" required></textarea>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.note')}}</label>
                        <textarea rows="3" name="note" class="form-control"></textarea>
                    </div>
                </div>
                <input type="hidden" name="reference_no">
                <input type="hidden" name="sale_id">
                <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="steadfast-delivery" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.steadfast_delivery')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="steadfastForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>{{__('db.Invoice')}}</label>
                            <p id="invoice"></p>
                        </div>
                    <div class="col-md-6 mt-2 form-group">
                        <label>{{__('db.name')}} *</label>
                        <input type="text" name="recipient_name" class="form-control">
                    </div>
                    <div class="col-md-6 mt-2 form-group">
                        <label>{{__('db.Phone')}} *</label>
                        <input type="text" name="recipient_phone" class="form-control">
                    </div>
                    <div class="col-md-6 mt-2 form-group">
                        <label>{{__('db.alternative_phone')}}</label>
                        <input type="text" name="alternative_phone" class="form-control">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.email')}}</label>
                        <input type="email" name="recipient_email" class="form-control">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.Address')}} *</label>
                        <textarea rows="3" name="recipient_address" class="form-control" required></textarea>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.Amount')}} *</label>
                        <input type="number" name="cod_amount" class="form-control" min="0.01" step="0.01">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.Item Description')}}</label>
                        <textarea rows="3" name="item_description" class="form-control"></textarea>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.note')}}</label>
                        <textarea rows="3" name="note" class="form-control"></textarea>
                    </div>
                </div>
                <!-- <input type="hidden" name="reference_no"> -->
                <input type="hidden" name="sale_id">
                <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="track-steadfast-delivery" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.steadfast_delivery')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>{{__('db.Delivery Status')}}</label>
                        <p id="delivery-status" class="font-weight-bold text-primary">...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="send-sms" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Send SMS')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('sale.sendsms') }}" method="post">
                    @csrf
                    <div class="row">
                        <input type="hidden" name="customer_id">
                        <input type="hidden" name="reference_no">
                        <input type="hidden" name="sale_status">
                        <input type="hidden" name="payment_status">
                        <div class="col-md-6 mt-1">
                            <label>{{__('db.SMS Template')}}</label>
                            <select name="template_id" class="form-control">
                                <option value="">Select Template</option>
                                @foreach($smsTemplates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">{{__('db.submit')}}</button>
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

    $("ul#sale").siblings('a').attr('aria-expanded','true');
    $("ul#sale").addClass("show");
    $("ul#sale #sale-list-menu").addClass("active");

    @if(config('database.connections.saleprosaas_landlord'))
        if(localStorage.getItem("message")) {
            alert(localStorage.getItem("message"));
            localStorage.removeItem("message");
        }

        numberOfInvoice = <?php echo json_encode($numberOfInvoice)?>;
        $.ajax({
            type: 'GET',
            async: false,
            url: '{{route("package.fetchData", gen_setting()->package_id)}}',
            success: function(data) {
                if(data['number_of_invoice'] > 0 && data['number_of_product'] <= numberOfInvoice) {
                    $("a.add-sale-btn").addClass('d-none');
                }
            }
        });
    @endif

    var show_products_details = <?php echo json_encode(gen_setting()->show_products_details_in_sales_table) ?>;
    let columns = [
        { "data": "key" },
        { "data": "options" },
        { "data": "date" },
        { "data": "reference_no" },
            {"data": "created_by"},
        { "data": "customer" },
        { "data": "warehouse_name" }
    ];

    // Insert product details columns if needed
    if (show_products_details == 1) {
        columns.push(
            { "data": "products" },
            { "data": "qty" }
        );
    }

    // Add the rest of the columns
    columns.push(
        { "data": "sale_status" },
        { "data": "payment_status" },
        { "data": "payment_method" },
        { "data": "currency" },
        { "data": "delivery_status" },
        { "data": "total_tax" },
        { "data": "order_tax" },
        { "data": "grand_total" },
        { "data": "returned_amount" },
        { "data": "paid_amount" },
        { "data": "due" }
    );

    var field_name = <?php echo json_encode($field_name) ?>;
    for(i = 0; i < field_name.length; i++) {
        columns.push({"data": field_name[i]});
    }
    // columns.splice(2, 0, { "data": "options" });
    // columns.push({"data": "options"});

    @if($lims_pos_setting_data)
        var public_key = <?php echo json_encode($lims_pos_setting_data->stripe_public_key) ?>;
    @endif
    var all_permission = <?php echo json_encode($all_permission) ?>;
    @if($lims_reward_point_setting_data)
        var reward_point_setting = <?php echo json_encode($lims_reward_point_setting_data) ?>;
    @endif
    var sale_id = [];
    var user_verified = <?php echo json_encode(config('app.user_verified')) ?>;
    var starting_date = <?php echo json_encode($starting_date); ?>;
    var ending_date = <?php echo json_encode($ending_date); ?>;
    var warehouse_id = <?php echo json_encode($warehouse_id); ?>;
    var sale_status = <?php echo json_encode($sale_status); ?>;
    var payment_status = <?php echo json_encode($payment_status); ?>;
    var sale_type = <?php echo json_encode($sale_type); ?>;
    var payment_method = <?php echo json_encode($payment_method); ?>;
    var balance = {};
    var expired_date = {};
    @foreach($lims_gift_card_list as $gift_card)
        balance[{{ $gift_card->id }}]      = {{ $gift_card->amount - $gift_card->expense }};
        expired_date[{{ $gift_card->id }}] = "{{ $gift_card->expired_date }}";
    @endforeach
    var current_date = <?php echo json_encode(date("Y-m-d")) ?>;
    var payment_date = [];
    var payment_reference = [];
    var paid_amount = [];
    var paying_method = [];
    var payment_id = [];
    var payment_note = [];
    var account = [];
    var deposit;
    var without_stock = <?php echo json_encode(gen_setting()->without_stock) ?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $("#warehouse_id").val(warehouse_id);
    $("#sale-status").val(sale_status);
    $("#payment-status").val(payment_status);
    $("#sale-type").val(sale_type);
    $("#payment-method").val(payment_method);
    $("#installment-filter").val(0);

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

    $(".gift-card").hide();
    $(".card-element").hide();
    $("#cheque").hide();
    $('#view-payment').modal('hide');

    $('.selectpicker').selectpicker('refresh');

    $(document).on("click", "tr.sale-link td:not(:first-child, :nth-child(2), :last-child)", function() {
        var tr   = $(this).closest('tr');
        var sale_id = tr.data('sale');          // DataTable row array — used only to get the ID

        $.ajax({
            url: '{{url("sales/get-sale")}}/' + sale_id,
            type: 'GET',
            success: function(sale) {
                saleDetails(sale, tr);
            },
            error: function(xhr, status, error) {
                alert('Error loading sale details: ' + error);
            }
        });
    });

    $(document).on("click", ".view", function(){
        var tr   = $(this).closest('tr');
        var sale_id = tr.data('sale');          // DataTable row array — used only to get the ID

        $.ajax({
            url: '{{url("sales/get-sale")}}/' + sale_id,
            type: 'GET',
            success: function(sale) {
                saleDetails(sale, tr);
            },
            error: function(xhr, status, error) {
                alert('Error loading sale details: ' + error);
            }
        });
    });

    $(document).on("click", ".gen-invoice", function(e){
        e.preventDefault();
        let link = $(this).attr('href');
        $.ajax({
            url: link,
            type: 'GET',
            success: function(data) {
                location.href = link;
            },
            error: function(xhr, status, error) {
                console.error("Error loading invoice:", error);
            }
        });
    });

    $(document).on("click", ".create-packing-slip-btn", function (e) {
        e.preventDefault();
        id = $(this).data('id');
        $("#packing-slip-modal input[name=sale_id]").val(id);
        $.get('sales/get-sold-items/'+id, function (data) {
            if(data == 'All the items of this sale has already been packed') {
                alert(data);
                $("#packing-slip-modal").modal('hide');
            }
            else {
                $("table.product-list tbody").remove();
                var newBody = $("<tbody>");
                total_amount = 0.0;
                $.each(data, function(index){
                    if(index != 'amount') {
                        var newRow = $("<tr>");
                        var cols = '';
                        cols += '<td>' + data[index]['name'] + '</td>';
                        cols += '<td>' + data[index]['code'] + '</td>';
                        cols += '<td>' + data[index]['sold_qty'] + '</td>';
                        cols += '<td>' + data[index]['unit_price'] + '</td>';
                        cols += '<td class="total-price">' + data[index]['total_price'] + '</td>';
                        if( data[index]['type'] == 'standard' && without_stock == 'no' && (data[index]['qty'] > data[index]['stock']) ) {
                            cols += '<td>In stock: '+data[index]['stock']+'</td>';
                        }
                        else if( data[index]['type'] == 'combo' && without_stock == 'no' && !data[index]['combo_in_stock'] ) {
                            cols += '<td>'+data[index]['child_info']+'</td>';
                        }
                        else if(data[index]['is_packing']) {
                            cols += '<td><input type="checkbox" class="is-packing" name="is_packing[]" value="'+data[index]['product_id']+'" checked disabled /></td>';
                        }
                        else {
                            cols += '<td><input type="checkbox" class="is-packing" name="is_packing[]" value="'+data[index]['product_id']+'" checked style="pointer-events: none;" /></td>';

                            total_amount += parseFloat(data[index]['unit_price']);
                        }

                        newRow.append(cols);
                        newBody.append(newRow);
                        $("table.product-list").append(newBody);
                    }
                });
                $("#packing-slip-modal input[name=amount]").val(total_amount);
                $("#packing-slip-modal").modal();
            }
        });
    });

    $(document).on('submit', '.packing-slip-form', function(e) {
        $(".packing-slip-submit-btn").prop("disabled", true);
    });

    $(document).on('submit', '.packing-slip-submit-btn', function(e) {
        $("input[name='is_packing[]']").prop('checked', true);
    });

    $(document).on("click", "#print-btn", function() {
        var divContents = document.getElementById("sale-details").innerHTML;
        var styleContents = document.getElementById("invoice-style").innerHTML;
        var a = window.open('');
        a.document.write('<html><head>');
        a.document.write('<style>' + styleContents + '</style>');
        a.document.write('<style>body{font-family: sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact;} .d-print-none{display:none;} table{width:100%; border-collapse:collapse;} </style>');
        a.document.write('</head><body style="padding: 20px;">');
        a.document.write(divContents);
        a.document.write('</body></html>');
        a.document.close();
        setTimeout(function(){
            a.print();
            setTimeout(function(){a.close();}, 100);
        }, 200);
    });

    $(document).on("click", "table.sale-list tbody .add-payment, #inv-action-bar .add-payment", function() {
        $("#cheque").hide();
        $(".gift-card").hide();
        $(".card-element").hide();
        $('select[name="paid_by_id"]').val(1);
        $('.selectpicker').selectpicker('refresh');

        const sale_id       = String($(this).data('id'));
        const balance       = parseFloat(String($(this).data('due')).replace(/,/g, '') * $(this).data('exchange_rate')) || 0;
        const currency_id   = $(this).data('currency_id');
        const currency_name = $(this).data('currency_name');
        const exchange_rate = parseFloat($(this).data('exchange_rate')) || 1;

        var tr = $(this).closest('tr');
        if(tr.length) {
            rowindex = tr.index();
            deposit = tr.find('.deposit').val();
        } else {
            deposit = $(this).attr('data-deposit');
        }


        $('input[name="paying_amount"]').val(balance);
        $('#add-payment input[name="balance"]').val(balance);
        $('input[name="amount"]').val(balance);
        $('input[name="sale_id"]').val(sale_id);
        // Fill readonly currency info
        $('#currency_display').text(currency_name);
        $('#exchange_rate_display').text(exchange_rate.toFixed(2));

        // Hidden inputs for backend
        $('#currency_id').val(currency_id);
        $('#exchange_rate').val(exchange_rate);
    });

    $(document).on("click", "table.sale-list tbody .get-payment, #inv-action-bar .get-payment", function(event) {
        var tr = $(this).closest('tr');
        if(tr.length) {
            rowindex = tr.index();
            deposit = tr.find('.deposit').val();
        } else {
            deposit = $(this).attr('data-deposit');
        }
        var id = $(this).data('id').toString();
        $.get('sales/getpayment/' + id, function(data) {
            $(".payment-list tbody").remove();
            var newBody = $("<tbody>");
            payment_date  = data[0];
            payment_reference = data[1];
            paid_amount = data[2];
            paying_method = data[3];
            payment_id = data[4];
            payment_note = data[5];
            cheque_no = data[6];
            gift_card_id = data[7];
            change = data[8];
            paying_amount = data[9];
            account_name = data[10];
            account_id = data[11];
            payment_receiver = data[12];
            if(data[13])
                payment_proof = data[13];
            payment_document = data[14];
            payment_at = data[15];
            installment_id = data[16];

            $.each(payment_date, function(index) {
                var newRow = $("<tr>");
                var cols = '';

                cols += '<td>' + payment_date[index] + '</td>';
                cols += '<td>' + payment_reference[index] + '</td>';
                cols += '<td>' + account_name[index] + '</td>';
                cols += '<td>' + paid_amount[index] + '</td>';
                cols += '<td>' + paying_method[index] + '</td>';
                cols += '<td>' + payment_at[index] + '</td>';
                cols += '<td><div class="btn-group"><button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{__("db.action")}}<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button><ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">';
                cols += '<li><a href="{{ url("sales/payment-receipt")}}/' + payment_id[index] + '" target="_blank"><button type="button" class="btn btn-link"><i class="ti ti-file-type-xls"></i> {{__("db.payment_receipt")}}</button></a></li> ';
                if(paying_method[index] == 'QR Code' && all_permission.indexOf("sale-payment-edit") != -1)
                    cols += '<li><a href="{{url("frontend/images/payment-proof")}}/'+payment_proof+'" target="_blank"><button type="button" class="btn btn-link"><i class="ti ti-eye"></i> {{__("db.payment_proof")}}</button></a></li> ';
                if(payment_document[index])
                    cols += '<li><a href="{{url("documents/add-payment")}}/'+payment_document[index]+'" target="_blank"><button type="button" class="btn btn-link"><i class="ti ti-file"></i> {{__("db.View Document")}}</button></a></li> ';
                if(paying_method[index] != 'Paypal' && all_permission.indexOf("sale-payment-edit") != -1)
                    cols += '<li><button type="button" class="btn btn-link edit-btn" data-id="' + payment_id[index] +'" data-installment_id="' + installment_id[index] + '" data-clicked=false data-toggle="modal" data-target="#edit-payment"><i class="ti ti-edit"></i> {{__("db.edit")}}</button></li> ';
                if(all_permission.indexOf("sale-payment-delete") != -1)
                    cols += '<form action="{{ route("sale.delete-payment") }}" method="POST">'+'@csrf'+'<li><input type="hidden" name="id" value="' + payment_id[index] + '" /> <input type="hidden" name="installment_id" value="' + installment_id[index] + '" /> <button type="submit" class="btn btn-link" onclick="return confirmPaymentDelete()"><i class="ti ti-trash"></i> {{__("db.delete")}}</button></li></form>';
                cols += '</ul></div></td>';
                newRow.append(cols);
                newBody.append(newRow);
                $("table.payment-list").append(newBody);
            });
            $('#view-payment').modal('show');
        });
    });

    $("table.payment-list").on("click", ".edit-btn", function(event) {
        $(".edit-btn").attr('data-clicked', true);
        $(".card-element").hide();
        $("#edit-cheque").hide();
        $('.gift-card').hide();
        $('#edit-payment select[name="edit_paid_by_id"]').prop('disabled', false);
        var id = $(this).data('id').toString();
        $.each(payment_id, function(index){
            if(payment_id[index] == parseFloat(id)){
                $('input[name="payment_id"]').val(payment_id[index]);
                $('#edit-payment select[name="account_id"]').val(account_id[index]);
                if(paying_method[index] == 'Cash')
                    $('select[name="edit_paid_by_id"]').val(1);
                else if(paying_method[index] == 'Gift Card'){
                    $('select[name="edit_paid_by_id"]').val(2);
                    $('#edit-payment select[name="gift_card_id"]').val(gift_card_id[index]);
                    $('.gift-card').show();
                    $('#edit-payment select[name="edit_paid_by_id"]').prop('disabled', true);
                }
                else if(paying_method[index] == 'Credit Card'){
                    $('select[name="edit_paid_by_id"]').val(3);
                    @if($lims_pos_setting_data && (strlen($lims_pos_setting_data->stripe_public_key)>0) && (strlen($lims_pos_setting_data->stripe_secret_key )>0))
                        $.getScript( "vendor/stripe/checkout.js" );
                        $(".card-element").show();
                    @endif
                    $('#edit-payment select[name="edit_paid_by_id"]').prop('disabled', true);
                }
                else if(paying_method[index] == 'Cheque'){
                    $('select[name="edit_paid_by_id"]').val(4);
                    $("#edit-cheque").show();
                    $('input[name="edit_cheque_no"]').val(cheque_no[index]);
                    $('input[name="edit_cheque_no"]').attr('required', true);
                }
                else if(paying_method[index] == 'Deposit')
                    $('select[name="edit_paid_by_id"]').val(6);
                else if(paying_method[index] == 'Points'){
                    $('select[name="edit_paid_by_id"]').val(7);
                }

                $('.selectpicker').selectpicker('refresh');
                $("#payment_reference").html(payment_reference[index]);
                $('input[name="edit_paying_amount"]').val(paying_amount[index]);
                $('#edit-payment .change').text(change[index]);
                $('input[name="edit_amount"]').val(paid_amount[index]);
                $('textarea[name="edit_payment_note"]').val(payment_note[index]);
                $('input[name="payment_receiver"]').val(payment_receiver[index]);
                $('input[name="payment_at"]').val(payment_at[index]);
                $('input[name="installment_id"]').val(installment_id[index]);
                if (installment_id[index] > 0) {
                    $('input[name="edit_amount"]').attr('readonly', true);
                    $('input[name="edit_paying_amount"]').attr('readonly', true);
                }
                return false;
            }
        });
        $('#view-payment').modal('hide');
    });

    $('select[name="paid_by_id"]').on("change", function() {
        var id = $(this).val();
        $('input[name="cheque_no"]').attr('required', false);
        $('#add-payment select[name="gift_card_id"]').attr('required', false);
        $(".payment-form").off("submit");
        if(id == 2){
            $(".gift-card").show();
            $(".card-element").hide();
            $("#cheque").hide();
            $('#add-payment select[name="gift_card_id"]').attr('required', true);
        }
        else if (id == 3) {
            @if($lims_pos_setting_data && (strlen($lims_pos_setting_data->stripe_public_key)>0) && (strlen($lims_pos_setting_data->stripe_secret_key )>0))
                $.getScript( "vendor/stripe/checkout.js" );
                $(".card-element").show();
            @endif
            $(".gift-card").hide();
            $("#cheque").hide();
        } else if (id == 4) {
            $("#cheque").show();
            $(".gift-card").hide();
            $(".card-element").hide();
            $('input[name="cheque_no"]').attr('required', true);
        } else if (id == 5) {
            $(".card-element").hide();
            $(".gift-card").hide();
            $("#cheque").hide();
        } else {
            $(".card-element").hide();
            $(".gift-card").hide();
            $("#cheque").hide();
            if(id == 6){
                // console.log('paid_by_id', deposit, $('#add-payment input[name="amount"]').val());
                if($('#add-payment input[name="amount"]').val() > parseFloat(deposit))
                    alert('Amount exceeds customer deposit! Customer deposit : ' + deposit);
            }
            else if(id==7) {
                pointCalculation($('#add-payment input[name="amount"]').val());
            }
        }
    });

    $('#add-payment select[name="gift_card_id"]').on("change", function() {
        var id = $(this).val();
        if(expired_date[id] < current_date)
            alert('This card is expired!');
        else if($('#add-payment input[name="amount"]').val() > balance[id]){
            alert('Amount exceeds card balance! Gift Card balance: '+ balance[id]);
        }
    });

    $('input[name="paying_amount"]').on("focusout", function() {
        if( $(this).val() < parseFloat($('input[name="amount"]').val()) ) {
            $('input[name="amount"]').val($(this).val());
        } else{
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
        var id = $('#add-payment select[name="paid_by_id"]').val();
        var amount = $(this).val();
        if(id == 2){
            id = $('#add-payment select[name="gift_card_id"]').val();
            if(amount > balance[id])
                alert('Amount exceeds card balance! Gift Card balance: '+ balance[id]);
        }
        else if(id == 6){
            if(amount > parseFloat(deposit))
                alert('Amount exceeds customer deposit! Customer deposit : ' + deposit);
        }
        else if(id==7) {
            pointCalculation(amount);
        }
    });

    $('select[name="edit_paid_by_id"]').on("change", function() {
        var id = $(this).val();
        $('input[name="edit_cheque_no"]').attr('required', false);
        $('#edit-payment select[name="gift_card_id"]').attr('required', false);
        $(".payment-form").off("submit");
        if(id == 2){
            $(".card-element").hide();
            $("#edit-cheque").hide();
            $('.gift-card').show();
            $('#edit-payment select[name="gift_card_id"]').attr('required', true);
        }
        else if (id == 3) {
            $(".edit-btn").attr('data-clicked', true);
            @if($lims_pos_setting_data && (strlen($lims_pos_setting_data->stripe_public_key)>0) && (strlen($lims_pos_setting_data->stripe_secret_key )>0))
                $.getScript( "vendor/stripe/checkout.js" );
                $(".card-element").show();
            @endif
            $("#edit-cheque").hide();
            $('.gift-card').hide();
        } else if (id == 4) {
            $("#edit-cheque").show();
            $(".card-element").hide();
            $('.gift-card').hide();
            $('input[name="edit_cheque_no"]').attr('required', true);
        } else {
            $(".card-element").hide();
            $("#edit-cheque").hide();
            $('.gift-card').hide();
            if(id == 6) {
                if($('input[name="edit_amount"]').val() > parseFloat(deposit))
                    alert('Amount exceeds customer deposit! Customer deposit : ' + deposit);
            }
            else if(id==7) {
                pointCalculation($('input[name="edit_amount"]').val());
            }
        }
    });

    $('#edit-payment select[name="gift_card_id"]').on("change", function() {
        var id = $(this).val();
        if(expired_date[id] < current_date)
            alert('This card is expired!');
        else if($('#edit-payment input[name="edit_amount"]').val() > balance[id])
            alert('Amount exceeds card balance! Gift Card balance: '+ balance[id]);
    });

    $('input[name="edit_paying_amount"]').on("input", function() {
        $(".change").text(parseFloat( $(this).val() - $('input[name="edit_amount"]').val() ).toFixed({{gen_setting()->decimal}}));
    });

    $('input[name="edit_amount"]').on("input", function() {
        if( $(this).val() > parseFloat($('input[name="edit_paying_amount"]').val()) ) {
            alert('Paying amount cannot be bigger than recieved amount');
            $(this).val('');
        }
        $(".change").text(parseFloat($('input[name="edit_paying_amount"]').val() - $(this).val()).toFixed({{gen_setting()->decimal}}));
        var amount = $(this).val();
        var id = $('#edit-payment select[name="gift_card_id"]').val();
        if(amount > balance[id]){
            alert('Amount exceeds card balance! Gift Card balance: '+ balance[id]);
        }
        var id = $('#edit-payment select[name="edit_paid_by_id"]').val();
        if(id == 6){
            if(amount > parseFloat(deposit))
                alert('Amount exceeds customer deposit! Customer deposit : ' + deposit);
        }
        else if(id==7) {
            pointCalculation(amount);
        }
    });

    $(document).on("click", "table.sale-list tbody .add-delivery, #inv-action-bar .add-delivery", function(event) {
        var id = $(this).data('id').toString();
        $.get('delivery/create/'+id, function(data) {
            $('#dr').text(data[0]);
            $('#sr').text(data[1]);

            $('select[name="status"]').val(data[2]);
            $('.selectpicker').selectpicker('refresh');
            $('input[name="delivered_by"]').val(data[3]);
            $('input[name="recieved_by"]').val(data[4]);
            $('#customer').text(data[5]);
            $('textarea[name="address"]').val(data[6]);
            $('textarea[name="note"]').val(data[7]);
            $('select[name="courier_id"]').val(data[8]);
            $('.selectpicker').selectpicker('refresh');
            $('input[name="reference_no"]').val(data[0]);
            $('input[name="sale_id"]').val(id);
            $('#add-delivery').modal('show');
        });
    });

    $(document).on("click", "table.sale-list tbody .steadfast-delivery", function(event) {
        var id = $(this).data('id').toString();
        $.get('delivery/steadfast/'+id, function(data) {
            $('#invoice').text(data['invoice']);
            $('input[name="sale_id"]').val(id);
            $('input[name="recipient_name"]').val(data['recipient_name']);
            $('input[name="recipient_email"]').val(data['recipient_email']);
            $('input[name="recipient_phone"]').val(data['recipient_phone']);
            $('textarea[name="recipient_address"]').val(data['recipient_address']);
            let amount = parseFloat(data['cod_amount']);
            $('input[name="cod_amount"]').val(isNaN(amount) ? '' : amount.toFixed(2));
            $('#steadfast-delivery').modal('show');
        });
    });

    $(document).on('submit', '#steadfastForm', function(event) {
        event.preventDefault();

        let form = $(this);

        let requestData = {
            invoice: $('#invoice').text().trim(),
            sale_id: form.find('input[name="sale_id"]').val(),
            recipient_name: form.find('input[name="recipient_name"]').val(),
            recipient_phone: form.find('input[name="recipient_phone"]').val(),
            alternative_phone: form.find('input[name="alternative_phone"]').val(),
            recipient_email: form.find('input[name="recipient_email"]').val(),
            recipient_address: form.find('textarea[name="recipient_address"]').val(),
            cod_amount: parseFloat(form.find('input[name="cod_amount"]').val()).toFixed(2),
            item_description: form.find('textarea[name="item_description"]').val(),
            note: form.find('textarea[name="note"]').val(),
            // optional values if needed
            // total_lot: 1,
            // delivery_type: 0
        };

        $.ajax({
            url: '/steadfast/create-order',
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            data: JSON.stringify(requestData),
            success: function(response) {
                if (response.status === 'success') {
                    $('#sale-table').DataTable().ajax.reload();
                    $('#steadfast-delivery').modal('hide');
                } else {
                    alert(response.message || "Failed to place order.");
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    for (let field in errors) {
                        alert(errors[field][0]);
                    }
                } else if (xhr.status === 401) {
                    alert("Unauthorized Access!");
                } else {
                    alert("Something went wrong!");
                }
            }
        });
    });

    $(document).on('click', '.track-order', function () {
        const invoice = $(this).data('invoice');
        const $statusDisplay = $('#delivery-status');

        $statusDisplay.text('Loading...');

        $.ajax({
            url: `/steadfast/${invoice}`,
            method: 'GET',
            success: function (response) {
                console.log(response.status);
                if (response.status === 200) {
                    let status = response.delivery_status;
                    let statusLabel = getReadableStatus(status);
                    $statusDisplay.text(statusLabel);
                } else {
                    $statusDisplay.text('Unable to fetch status');
                }

                $('#track-steadfast-delivery').modal('show');
            },
            error: function () {
                $statusDisplay.text('Error fetching status');
                $('#track-steadfast-delivery').modal('show');
            }
        });
    });

    function getReadableStatus(apiStatus) {
        const statuses = {
            pending: "Pending",
            delivered_approval_pending: "Delivered (Approval Pending)",
            partial_delivered_approval_pending: "Partially Delivered (Approval Pending)",
            cancelled_approval_pending: "Cancelled (Approval Pending)",
            unknown_approval_pending: "Unknown (Approval Pending)",
            delivered: "Delivered",
            partial_delivered: "Partially Delivered",
            cancelled: "Cancelled",
            hold: "On Hold",
            in_review: "In Review",
            unknown: "Unknown",
        };

        return statuses[apiStatus] || "Unknown Status";
    }

    function pointCalculation(amount) {
        availablePoints = $('table.sale-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.points').val();
        required_point = Math.ceil(amount / reward_point_setting['per_point_amount']);
        if(required_point > availablePoints) {
          alert('Customer does not have sufficient points. Available points: '+availablePoints+'. Required points: '+required_point);
        }
    }

    let buttons = [];
    @can('sale_export')
        buttons.push([
            {
                extend: "pdf",
                text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                exportOptions: {
                    columns: ":visible:Not(.not-exported)",
                    rows: ":visible",
                },
                action: function (e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(
                        this,
                        e,
                        dt,
                        button,
                        config
                    );
                    datatable_sum(dt, false);
                },
                footer: true,
            },
            {
                extend: "excel",
                text: '<i title="export to excel" class="ti ti-file-type-xls"></i>',
                exportOptions: {
                    columns: ":visible:Not(.not-exported)",
                    rows: ":visible",
                },
                action: function (e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.excelHtml5.action.call(
                        this,
                        e,
                        dt,
                        button,
                        config
                    );
                    datatable_sum(dt, false);
                },
                footer: true,
            },
            {
                extend: "csv",
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ":visible:Not(.not-exported)",
                    rows: ":visible",
                },
                action: function (e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(
                        this,
                        e,
                        dt,
                        button,
                        config
                    );
                    datatable_sum(dt, false);
                },
                footer: true,
            },
            {
                extend: "print",
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ":visible:Not(.not-exported)",
                    rows: ":visible",
                },
                action: function (e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(
                        this,
                        e,
                        dt,
                        button,
                        config
                    );
                    datatable_sum(dt, false);
                },
                footer: true,
            },
        ]);
    @endcan

    buttons.push([
        {
            text: '<i title="delete" class="ti ti-x"></i>',
            className: 'buttons-delete',
            action: function ( e, dt, node, config ) {
                if(user_verified == '1') {
                    sale_id.length = 0;
                    $(':checkbox:checked').each(function(i){
                        if(i){
                            var sale = $(this).closest('tr').data('sale');
                            if(sale)
                                sale_id[i-1] = sale;
                        }
                    });
                    if(sale_id.length && confirm("Are you sure want to delete?")) {
                        $.ajax({
                            type:'POST',
                            url:'sales/deletebyselection',
                            data:{
                                saleIdArray: sale_id
                            },
                            success:function(data){
                                alert(data);
                                //dt.rows({ page: 'current', selected: true }).deselect();
                                dt.rows({ page: 'current', selected: true }).remove().draw(false);
                            }
                        });
                    }
                    else if(!sale_id.length)
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

    var saleTable = $('#sale-table').DataTable( {
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"{{url('sales/sale-data')}}",
            data: function (d) {
                d.all_permission   = all_permission;
                d.starting_date    = $('input[name=starting_date]').val();
                d.ending_date      = $('input[name=ending_date]').val();
                d.warehouse_id     = $('#warehouse_id').val();
                d.sale_status      = $('#sale-status').val();
                d.sale_type        = $('#sale-type').val();
                d.payment_status   = $('#payment-status').val();
                d.payment_method   = $('#payment-method').val();
                d.installment      = $('#installment-filter').val();
                if ($('#social-channel').length) {
                    d.social_channel = $('#social-channel').val();
                }
            },
            dataType: "json",
            type:"post"
            // dataSrc: function(json) {
            //     console.log(json);
            // }
        },
        /*rowId: function(data) {
              return 'row_'+data['id'];
        },*/
        "createdRow": function( row, data, dataIndex ) {
            $(row).addClass('sale-link');
            $(row).attr('data-sale', data['sale']);
        },
        "columns": columns,
        'language': {

            'lengthMenu': '_MENU_ {{__("db.records per page")}}',
             "info":      '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search":  '{{__("db.Search")}}',
            'paginate': {
                    'previous': '<i class="ti ti-chevron-left"></i>',
                    'next': '<i class="ti ti-chevron-right"></i>'
            }
        },
        order:[['2', 'desc']],
        "footerCallback": function(row, data, start, end, display) {
            var api = this.api();

            var paymentTotals = {};

            data.forEach(function(row) {
                var payments = row['payment_method'];
                if (payments) {
                    payments.split(', ').forEach(function(payment) {
                        var match = payment.match(/(.+)\(([\d.,]+)\)/);
                        if (match) {
                            var method = match[1];
                            var amount = parseFloat(match[2].replace(/,/g, '')) || 0;

                            paymentTotals[method] = (paymentTotals[method] || 0) + amount;
                        }
                    });
                }
            });

            // Generate footer content
            var footerContent = '';
            Object.keys(paymentTotals).forEach(function(method) {
                footerContent += `<div>${method}: ${paymentTotals[method].toFixed(2)}</div>`;
            });

            if(show_products_details == 1){
                $(api.column(11).footer()).html(footerContent);
            }
            else{
                $(api.column(9).footer()).html(footerContent);
            }
        },
        'columnDefs': [
            {
                "orderable": false,
                'targets': show_products_details == 1
                    ? [0, 1, 4, 11, 12, 16, 17]
                    : [0, 1, 4, 9, 10, 14, 15]
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
        rowId: 'ObjectID',
        buttons: buttons,
        drawCallback: function () {
            var api = this.api();
            datatable_sum(api, false);
        }
    });

    $('#warehouse_id, #sale-status, #sale-type, #payment-status, #payment-method, #installment-filter').on('change', function () {
        saleTable.ajax.reload();
    });

    // Show loader on request
    saleTable.on('preXhr.dt', function () {
        $('#filter-loading').show();
    });

    // Hide loader after draw
    saleTable.on('xhr.dt', function () {
        $('#filter-loading').hide();
    });

    function datatable_sum(dt_selector, is_calling_first) {

        var rows;

        if (dt_selector.rows('.selected').any() && is_calling_first) {
            rows = dt_selector.rows('.selected').indexes();
        } else {
            rows = dt_selector.rows({ page: 'current' }).indexes();
        }

        if (show_products_details == 1) {
            // Total Tax
            $(dt_selector.column(14).footer()).html(
                formatCurrency(dt_selector.cells(rows, 14).data().sum())
            );

            // Order Tax
            $(dt_selector.column(15).footer()).html(
                formatCurrency(dt_selector.cells(rows, 15).data().sum())
            );

            // Grand Total
            $(dt_selector.column(16).footer()).html(
                formatCurrency(dt_selector.cells(rows, 16).data().sum())
            );

            // Returned
            $(dt_selector.column(17).footer()).html(
                formatCurrency(dt_selector.cells(rows, 17).data().sum())
            );

            // Paid
            $(dt_selector.column(18).footer()).html(
                formatCurrency(dt_selector.cells(rows, 18).data().sum())
            );

            // ✅ Due
            $(dt_selector.column(19).footer()).html(
                formatCurrency(dt_selector.cells(rows, 19).data().sum())
            );

        } else {
            // Total Tax
            $(dt_selector.column(12).footer()).html(
                formatCurrency(dt_selector.cells(rows, 12).data().sum())
            );

            // Order Tax
            $(dt_selector.column(13).footer()).html(
                formatCurrency(dt_selector.cells(rows, 13).data().sum())
            );

            // Grand Total
            $(dt_selector.column(14).footer()).html(
                formatCurrency(dt_selector.cells(rows, 14).data().sum())
            );

            // Returned
            $(dt_selector.column(15).footer()).html(
                formatCurrency(dt_selector.cells(rows, 15).data().sum())
            );

            // Paid
            $(dt_selector.column(16).footer()).html(
                formatCurrency(dt_selector.cells(rows, 16).data().sum())
            );

            // ✅ Due
            $(dt_selector.column(17).footer()).html(
                formatCurrency(dt_selector.cells(rows, 17).data().sum())
            );
        }
    }

    $('.daterangepicker-field').on('apply.daterangepicker', function(ev, picker) {
        $('input[name=starting_date]').val(picker.startDate.format('YYYY-MM-DD'));
        $('input[name=ending_date]').val(picker.endDate.format('YYYY-MM-DD'));
        saleTable.ajax.reload();
    });

    @include('backend.sale.sale_details_function');

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

    if(all_permission.indexOf("sales-delete") == -1)
        $('.buttons-delete').addClass('d-none');

        function confirmDelete() {
            if (confirm("Are you sure want to delete?")) {
                return true;
            }
            return false;
        }

    function confirmPaymentDelete() {
        if (confirm("Are you sure want to delete? If you delete this money will be refunded.")) {
            return true;
        }
        return false;
    }

    $(document).ready(function() {
        $(document).on('click', '.send-sms', function(){
            $("#send-sms input[name='customer_id']").val($(this).data('customer_id'));
            $("#send-sms input[name='reference_no']").val($(this).data('reference_no'));
            $("#send-sms input[name='sale_status']").val($(this).data('sale_status'));
            $("#send-sms input[name='payment_status']").val($(this).data('payment_status'));
        });
    });


        $('#add-payment-form').on('submit', function() {
            var $submitButton = $('#add-payment-submit-btn');
            if ($submitButton.is(':disabled')) {
                return false;
            }
            $submitButton.attr('disabled', 'disabled').text('Submitting...');
            return true;
        });
</script>
<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
@endpush
