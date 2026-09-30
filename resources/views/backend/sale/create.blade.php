@extends('backend.layout.main') @section('content')
@push('css')
<style>
    @media print {
        .hidden-print {
            display: none !important;
        }
    }
    #product-results-container{background:#f5f6f7;position: absolute;overflow: hidden;max-height: 300px;overflow-y: auto;padding-top: 10px;top:40px;width:100%;z-index:999}
    #product-results-container .product-img{border-radius: 3px; color: #7c5cc4;font-size:13px;padding-top:7px;padding-bottom:7px;text-align:left}
    #product-results-container .product-img:hover{background-color: #7c5cc4;color: #FFF}

    .sale-section {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        padding: 20px 20px 10px 20px;
        margin-bottom: 20px;
        background: #fff;
    }

</style>
@endpush

<x-error-message key="not_permitted" />
<x-error-message key="error" />

<?php $authUser = Auth::user()->role_id; ?>

@php
    $restaurantModuleEnabled = (bool) ($restaurantModuleEnabled ?? false);
    $firstWarehouse = collect($lims_warehouse_list)->first();
    $selectedWarehouseId = $selectedWarehouseId
        ?? auth()->user()->warehouse_id
        ?? ($lims_pos_setting_data->warehouse_id ?? ($firstWarehouse->id ?? null));
    $selectedWarehouse = collect($lims_warehouse_list)->first(function ($warehouse) use ($selectedWarehouseId) {
        return (string) $warehouse->id === (string) $selectedWarehouseId;
    });
    $isRestaurantWarehouse = (bool) ($isRestaurantWarehouse ?? false);
    $selectedServiceId = isset($service_id) && $service_id ? (string) $service_id : '1';
    $isDineIn = $isRestaurantWarehouse && $selectedServiceId === '1';
@endphp

<section id="pos-layout" class="forms hidden-print">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h4>{{__('db.Add Sale')}}</h4>
                <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                <form action="{{ route('sales.store') }}" method="post" enctype="multipart/form-data" class="payment-form">
                @csrf
                    <div class="card">
                        <div class="card-body">                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.date')}}</label>
                                        @can('change_sale_date')
                                            <input type="text" name="created_at" class="form-control date" placeholder="{{ __('db.Choose date') }}" value="{{date(gen_setting()->date_format,strtotime('now'))}}" />
                                        @else
                                            <input type="text" name="created_at" class="form-control date" placeholder="{{ __('db.Choose date') }}" value="{{date(gen_setting()->date_format,strtotime('now'))}}" readonly/>
                                        @endcan
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>
                                            {{__('db.Reference No')}}
                                        </label>
                                        <input type="text" name="reference_no" class="form-control" />
                                    </div>
                                    <x-validation-error fieldName="reference_no" />
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.customer')}} *</label>
                                        <div class="input-group pos">
                                            @php
                                            $deposit = [];
                                            $points = [];
                                            $customer_active = DB::table('permissions')
                                            ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                                            ->where([
                                                ['permissions.name', 'customers-add'],
                                                ['role_id', \Auth::user()->role_id] ])->first();

                                                if($lims_pos_setting_data) {
                                                    $customer_id = $lims_pos_setting_data->customer_id;
                                                }
                                                else{
                                                    $customer_id = $lims_customer_list[0]->id;
                                                }
                                            @endphp
                                            @if($customer_active)
                                            <select required name="customer_id" id="customer_id" class="selectpicker form-control" data-live-search="true" title="Select customer..." style="width: 100px">
                                            @foreach($lims_customer_list as $customer)
                                                @php
                                                $deposit[$customer->id] = $customer->deposit - $customer->expense;

                                                $points[$customer->id] = $customer->points;
                                                @endphp
                                                <option value="{{$customer->id}}" data-type="{{$customer->type}}" data-credit-limit="{{ $customer->credit_limit }}" data-pay_term_no="{{ $customer->pay_term_no }}" data-pay_term_period="{{ $customer->pay_term_period }}"  @if($customer->id == $customer_id) selected @endif>{{$customer->name}} @if($customer->wa_number)({{$customer->wa_number}})@endif</option>
                                            @endforeach
                                            </select>
                                            <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#addCustomer"><i class="ti ti-plus"></i></button>
                                            @else
                                            <select required name="customer_id" id="customer_id" class="selectpicker form-control" data-live-search="true" title="Select customer...">
                                            @foreach($lims_customer_list as $customer)
                                                @php
                                                $deposit[$customer->id] = $customer->deposit - $customer->expense;

                                                $points[$customer->id] = $customer->points;
                                                @endphp
                                                <option value="{{$customer->id}}" data-type="{{$customer->type}}" data-credit-limit="{{ $customer->credit_limit }}" data-pay_term_no="{{ $customer->pay_term_no }}" data-pay_term_period="{{ $customer->pay_term_period }}"  @if($customer->id == $customer_id) selected @endif>{{$customer->name . ' (' . $customer->phone_number . ')'}}</option>
                                            @endforeach
                                            </select>
                                            @endif
                                            <x-validation-error fieldName="customer_id" />
                                        </div>
                                    </div>
                                </div>
                                @if(isset(auth()->user()->warehouse_id))
                                <input type="hidden" name="warehouse_id" id="warehouse_id"
                                    value="{{auth()->user()->warehouse_id}}"
                                    data-pos-type="{{ $selectedWarehouse->pos_type ?? 'regular' }}" />
                                @else
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Warehouse')}} *</label>
                                        <select required name="warehouse_id" id="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select warehouse...">
                                            @foreach($lims_warehouse_list as $warehouse)
                                            <option value="{{$warehouse->id}}"
                                                data-pos-type="{{ $warehouse->pos_type ?? 'regular' }}"
                                                @if((string) $warehouse->id === (string) $selectedWarehouseId) selected @endif>{{$warehouse->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                @endif
                                <x-validation-error fieldName="warehouse_id" />
                                @if(isset(auth()->user()->biller_id))
                                <input type="hidden" name="biller_id" id="biller_id" value="{{auth()->user()->biller_id}}" />
                                @else
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Biller')}} *</label>
                                        @php
                                        if($lims_pos_setting_data) {
                                            $biller_id = $lims_pos_setting_data->biller_id;
                                        }
                                        else{
                                            $biller_id = $lims_biller_list[0]->id;
                                        }
                                        @endphp
                                        <select required id="biller_id" name="biller_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Biller...">
                                            @foreach($lims_biller_list as $biller)
                                            <option value="{{$biller->id}}" @if($biller->id == $biller_id) selected @endif>{{$biller->name . ' (' . $biller->company_name . ')'}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                @endif
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>{{__('db.Currency')}} *</label>
                                        <select name="currency_id" id="currency" class="form-control selectpicker" data-toggle="tooltip" title="" data-original-title="Sale currency">
                                            @foreach($currency_list as $currency_data)
                                            <option value="{{$currency_data->id}}" data-rate="{{$currency_data->exchange_rate}}">{{$currency_data->code}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-0">
                                        <label>{{__('db.Exchange Rate')}} *</label>
                                    </div>
                                    <div class="form-group d-flex">
                                        <input class="form-control" type="text" id="exchange_rate" name="exchange_rate" value="{{$currency->exchange_rate}}">
                                        <div class="input-group-append">
                                            <span class="input-group-text" data-toggle="tooltip" title="" data-original-title="currency exchange rate">i</span>
                                        </div>
                                    </div>
                                </div>
                                @if($restaurantModuleEnabled)
                                <div class="col-md-3 col-6 restaurant-sale-field" @if(!$isRestaurantWarehouse) style="display:none;" @endif>
                                    <div class="form-group top-fields">
                                        <label>{{__('db.Service')}}</label>
                                        <div class="input-group pos">
                                            <select id="service_id" name="service_id" class="selectpicker form-control" title="Select service..."
                                                @if($isRestaurantWarehouse) required @else disabled @endif>
                                                <option value="1" @if($selectedServiceId === '1') selected @endif>{{__('db.Dine In')}}</option>
                                                <option value="2" @if($selectedServiceId === '2') selected @endif>{{__('db.Take Away')}}</option>
                                                <option value="3" @if($selectedServiceId === '3') selected @endif>{{__('db.Delivery')}}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 restaurant-sale-field" @if(!$isRestaurantWarehouse) style="display:none;" @endif>
                                    <div class="form-group top-fields">
                                        <label>{{__('db.table')}}</label>
                                        <div class="input-group pos">
                                            <select id="table_id" name="table_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select table..."
                                                @if($isDineIn) required @else disabled @endif>
                                                @foreach($lims_table_list as $table)
                                                <option value="{{$table->id}}" data-warehouse-id="{{$table->warehouse_id}}" @if(!empty($table_id) && $table->id == $table_id) selected @endif>
                                                    {{$table->name}} at {{$table->floor}} ( ðŸ‘¤ {{$table->number_of_person}})
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-6 restaurant-sale-field" @if(!$isRestaurantWarehouse) style="display:none;" @endif>
                                    <div class="form-group top-fields">
                                        <label>{{__('db.Waiter')}}</label>
                                        <div class="input-group pos">
                                            <select id="waiter_id" name="waiter_id" class="selectpicker form-control" title="Select waiter..."
                                                @if($isDineIn) required @else disabled @endif>
                                                @if(auth()->user()->service_staff == 1)
                                                <option value="{{auth()->user()->id}}" selected >{{auth()->user()->name}}</option>
                                                @else
                                                    @foreach($waiter_list as $waiter)
                                                    <option value="{{$waiter->id}}" @if(!empty($waiter_id) && $waiter->id == $waiter_id) selected @endif>
                                                        {{$waiter->name}}
                                                    </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <label>{{__('db.Select Product')}}</label>
                                    <div class="search-box form-group mb-2" style="position:relative">
                                        <div class="input-group pos">
                                            <input style="border: 1px solid #7c5cc4;" type="text" name="product_code_name" id="product-search-input" placeholder="Scan/Search product by name/code/IMEI" class="form-control" autofocus />
                                            <button type="button" class="btn btn-primary" onclick="barcode()"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-upc" viewBox="0 0 16 16"><path d="M3 4.5a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0z"/></svg></button>
                                        </div>
                                        <div id="product-results-container">

                                        </div>
                                        <div id="no-results-message" style="background-color: #f5f6f7;color: #666; margin-top: 5px;padding: 3px 5px; display: none;">No results found</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-5">
                                <div class="col-md-12">
                                    <h5>{{__('db.Order Table')}} *</h5>
                                    <div class="table-responsive mt-3">
                                        <table id="myTable" class="table table-hover order-list">
                                            <thead>
                                                <tr>
                                                    <th>{{__('db.product')}}</th>
                                                    <th>{{__('db.Quantity')}}</th>
                                                    <th>{{__('db.Net Unit Price')}}</th>
                                                    <th>{{__('db.Discount')}}</th>
                                                    <th>{{__('db.Tax')}}</th>
                                                    <th>{{__('db.Subtotal')}}</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody-id">
                                            </tbody>
                                            <tfoot class="tfoot active">
                                                <th>{{__('db.Total')}}</th>
                                                <th id="total-qty" class="text-center">0</th>
                                                <th></th>
                                                <th id="total-discount">{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
                                                <th id="total-tax">{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
                                                <th id="total">{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
                                                <th></th>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <input type="hidden" name="total_qty" />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <input type="hidden" name="total_discount" />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <input type="hidden" name="total_tax" />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <input type="hidden" name="total_price" />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <input type="hidden" name="item" />
                                        <input type="hidden" name="order_tax" />
                                    </div>
                                    <x-validation-error fieldName="item" />
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <input type="hidden" name="grand_total" />
                                        <input type="hidden" name="redeem_point" />
                                        <input type="hidden" name="coupon_active" value="0" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Order Tax')}}</label>
                                        <select class="form-control" name="order_tax_rate">
                                            <option value="0">No Tax</option>
                                            @foreach($lims_tax_list as $tax)
                                            <option value="{{$tax->rate}}">{{$tax->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Order Discount Type')}}</label>
                                        <select id="order-discount-type" name="order_discount_type" class="form-control">
                                            <option value="Flat">{{__('db.Flat')}}</option>
                                            <option value="Percentage">{{__('db.Percentage')}}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Order Discount Value') }}</label>
                                        <input type="number"
                                            name="order_discount_value"
                                            id="order-discount-val"
                                            class="form-control numkey"
                                            value="0.00"
                                            step="0.01" />
                                        <input type="hidden" name="order_discount" id="order-discount">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>
                                            {{__('db.Shipping Cost')}}
                                        </label>
                                        <input type="number" name="shipping_cost" value="0" class="form-control" step="any"/>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Attach Document')}}</label> <i class="ti ti-info-circle" data-toggle="tooltip" title="Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported"></i>
                                        <input type="file" name="document" class="form-control" />
                                        <x-validation-error fieldName="document" />
                                        <x-validation-error fieldName="extension" />
                                    </div>
                                </div>
                                @foreach($custom_fields as $field)
                                    @if(!$field->is_admin || \Auth::user()->role_id == 1)
                                        <div class="{{'col-md-'.$field->grid_value}}">
                                            <div class="form-group">
                                                <label>{{$field->name}}</label>
                                                @if($field->type == 'text')
                                                    <input type="text" name="{{str_replace(' ', '_', strtolower($field->name))}}" value="{{$field->default_value}}" class="form-control" @if($field->is_required){{'required'}}@endif>
                                                @elseif($field->type == 'number')
                                                    <input type="number" name="{{str_replace(' ', '_', strtolower($field->name))}}" value="{{$field->default_value}}" class="form-control" @if($field->is_required){{'required'}}@endif>
                                                @elseif($field->type == 'textarea')
                                                    <textarea rows="5" name="{{str_replace(' ', '_', strtolower($field->name))}}" value="{{$field->default_value}}" class="form-control" @if($field->is_required){{'required'}}@endif></textarea>
                                                @elseif($field->type == 'checkbox')
                                                    <br>
                                                    <?php $option_values = explode(",", $field->option_value); ?>
                                                    @foreach($option_values as $value)
                                                        <label>
                                                        <input type="checkbox" name="{{str_replace(' ', '_', strtolower($field->name))}}[]" value="{{$value}}" @if($value == $field->default_value){{'checked'}}@endif @if($field->is_required){{'required'}}@endif> {{$value}}
                                                            </label>
                                                            &nbsp;
                                                    @endforeach
                                                @elseif($field->type == 'radio_button')
                                                        <br>
                                                        <?php $option_values = explode(",", $field->option_value); ?>
                                                    @foreach($option_values as $value)
                                                        <label class="radio-inline">
                                                            <input type="radio" name="{{str_replace(' ', '_', strtolower($field->name))}}" value="{{$value}}" @if($value == $field->default_value){{'checked'}}@endif @if($field->is_required){{'required'}}@endif> {{$value}}
                                                        </label>
                                                        &nbsp;
                                                    @endforeach
                                                    @elseif($field->type == 'select')
                                                        <?php $option_values = explode(",", $field->option_value); ?>
                                                        <select class="form-control" name="{{str_replace(' ', '_', strtolower($field->name))}}" @if($field->is_required){{'required'}}@endif>
                                                            @foreach($option_values as $value)
                                                                <option value="{{$value}}" @if($value == $field->default_value){{'selected'}}@endif>{{$value}}</option>
                                                            @endforeach
                                                        </select>
                                                    @elseif($field->type == 'multi_select')
                                                        <?php $option_values = explode(",", $field->option_value); ?>
                                                        <select class="form-control" name="{{str_replace(' ', '_', strtolower($field->name))}}[]" @if($field->is_required){{'required'}}@endif multiple>
                                                            @foreach($option_values as $value)
                                                                <option value="{{$value}}" @if($value == $field->default_value){{'selected'}}@endif>{{$value}}</option>
                                                            @endforeach
                                                        </select>
                                                    @elseif($field->type == 'date_picker')
                                                        <input type="text" name="{{str_replace(' ', '_', strtolower($field->name))}}" value="{{$field->default_value}}" class="form-control date" @if($field->is_required){{'required'}}@endif>
                                                    @endif
                                                </div>
                                        </div>
                                    @endif
                                @endforeach
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Sale Status')}} *</label>
                                        <select name="sale_status" class="form-control">
                                            <option value="1" @if(!$isRestaurantWarehouse) selected @endif>{{__('db.Completed')}}</option>
                                            <option value="2">{{__('db.Pending')}}</option>
                                            @if($restaurantModuleEnabled)
                                            <option value="5" class="restaurant-sale-status-option"
                                                @if($isRestaurantWarehouse) selected @else disabled hidden @endif>{{__('db.Processing')}}</option>
                                            @endif
                                        </select>
                                        <x-validation-error fieldName="sale_status" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Payment Status')}} *</label>
                                            <select name="payment_status" id="payment_status" class="form-control">
                                                <option value="1">{{__('db.Pending')}}</option>
                                                <option value="2">{{__('db.Due')}}</option>
                                                <option value="3">{{__('db.Partial')}}</option>
                                                <option value="4">{{__('db.Paid')}}</option>
                                        </select>
                                        <x-validation-error fieldName="payment_status" />
                                    </div>
                                </div>
                                <?php
                                    $accountSelection = $role_has_permissions_list->where('name', 'account-selection')->first();
                                ?>
                                @if ($accountSelection)
                                    <!-- New Account Selection Field -->
                                    <div id="account-list" class="col-md-3 col-6" hidden>
                                        <div class="form-group top-fields">
                                            <label>{{__('db.Account')}}</label>
                                            <select required name="account_id" id="account_id" class="selectpicker form-control" data-live-search="true">
                                                <option value="0" style="color: #A7B49E;">Select an Account</option>
                                                @if(isset($lims_account_list))
                                                @foreach($lims_account_list as $account)
                                                    <option value="{{ $account->id }}"
                                                        @if(auth()->user()->account_id == $account->id)
                                                            selected
                                                        @elseif($account->is_default == 1)
                                                            selected
                                                        @endif
                                                    >
                                                        {{ $account->name }}
                                                    </option>
                                                @endforeach
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div id="payment" class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Paid By')}}</label>
                                        <select name="paid_by_id[]" class="form-control">
                                            @if(in_array("cash",$options))
                                            <option value="1">{{ __('db.Cash') }}</option>
                                            @endif
                                            @if(in_array("gift_card",$options))
                                            <option value="2">{{ __('db.Gift Card') }}</option>
                                            @endif
                                            @if(in_array("card",$options))
                                            <option value="3">{{ __('db.Credit Card') }}</option>
                                            @endif
                                            @if(in_array("cheque",$options))
                                            <option value="4">{{ __('db.Cheque') }}</option>
                                            @endif
                                            @if(in_array("paypal",$options) && (strlen($lims_pos_setting_data->paypal_live_api_username)>0) && (strlen($lims_pos_setting_data->paypal_live_api_password)>0) && (strlen($lims_pos_setting_data->paypal_live_api_secret)>0))
                                            <option value="5">{{ __('db.paypal') }}</option>
                                            @endif
                                            @if(in_array("deposit",$options))
                                            <option value="6">{{ __('db.Deposit') }}</option>
                                            @endif
                                            @if($lims_reward_point_setting_data && $lims_reward_point_setting_data->is_active)
                                            <option value="7">{{ __('db.Points') }}</option>
                                            @endif
                                            @if(in_array("pesapal",$options))
                                            <option value="8">{{ __('db.Pesapal') }}</option>
                                            @endif
                                            @foreach($options as $option)
                                                @if(!in_array($option, ['cash', 'card', 'cheque', 'gift_card', 'deposit', 'paypal', 'pesapal', 'points', 'credit']))
                                                    <option value="{{$option}}">{{ucfirst($option)}}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Recieved Amount')}} *</label>
                                        <input type="number" name="paying_amount[]" class="form-control" id="paying-amount" step="any" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Paying Amount')}} *</label>
                                        <input type="number" name="paid_amount[]" class="form-control" id="paid-amount" step="any"/>
                                    </div>
                                    <div class="alert alert-danger d-none p-2 position-absolute" id="paying-amount-error">
                                        Paying amount must be greater than 0
                                    </div>

                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Payment Receiver')}}</label>
                                        <input type="text" name="payment_receiver" class="form-control" id="payment-receiver"/>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Change')}}</label>
                                        <p id="change" class="ml-2">{{number_format(0, gen_setting()->decimal, '.', '')}}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div id="pay_term_div" class="mt-2" style="display:none;">
                                        <div class="form-group">
                                            <label>{{ __('Pay Term') }}</label>
                                            <div class="input-group">
                                                <input type="number"
                                                    name="pay_term_no"
                                                    id="pay_term_no"
                                                    class="form-control"
                                                    min="1"
                                                    placeholder="e.g. 30">

                                                <div class="input-group-append">
                                                    <select name="pay_term_period" id="pay_term_period" class="form-control">
                                                        <option value="days">Days</option>
                                                        <option value="months">Months</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="card-element" class="form-control">
                                        </div>
                                        <div class="card-errors" role="alert"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row" id="gift-card">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label> {{__('db.Gift Card')}} *</label>
                                        <select id="gift_card_id" name="gift_card_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Gift Card..."></select>
                                    </div>
                                </div>
                            </div>
                            <div class="row" id="cheque">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{{__('db.Cheque Number')}} *</label>
                                        <input type="text" name="cheque_no" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <label>{{__('db.Payment Note')}}</label>
                                    <textarea rows="3" class="form-control" name="payment_note"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__('db.Sale Note')}}</label>
                                        <textarea rows="5" class="form-control" name="sale_note"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__('db.Staff Note')}}</label>
                                        <textarea rows="5" class="form-control" name="staff_note"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <input type="hidden" name="draft" value="0" />
                                        @include('backend.sale.credit_limit_checker')
                                        <button id="submit-button" type="button" class="btn btn-primary sale-submit-btn">{{__('db.submit')}}</button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group text-right">
                                        <button type="button" class="btn btn-warning" disabled="true" id="installmentPlanBtn">
                                            <i class="bi bi-credit-card"></i> Instalment Plan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered table-condensed totals mt-3">
                    <td><strong>{{__('db.Items')}}</strong>
                        <span class="pull-right" id="item">{{number_format(0, gen_setting()->decimal, '.', '')}}</span>
                    </td>
                    <td><strong>{{__('db.Total')}}</strong>
                        <span class="pull-right" id="subtotal">{{number_format(0, gen_setting()->decimal, '.', '')}}</span>
                    </td>
                    <td><strong>{{__('db.Order Tax')}}</strong>
                        <span class="pull-right" id="order_tax">{{number_format(0, gen_setting()->decimal, '.', '')}}</span>
                    </td>
                    <td><strong>{{__('db.Order Discount')}}</strong>
                        <span class="pull-right" id="order_discount">{{number_format(0, gen_setting()->decimal, '.', '')}}</span>
                    </td>
                    <td><strong>{{__('db.Shipping Cost')}}</strong>
                        <span class="pull-right" id="shipping_cost">{{number_format(0, gen_setting()->decimal, '.', '')}}</span>
                    </td>
                    <td><strong>{{__('db.grand total')}}</strong>
                        <span class="pull-right" id="grand_total">{{number_format(0, gen_setting()->decimal, '.', '')}}</span>
                    </td>
                </table>
            </div>
        </div>
    </div>

    <div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="modal_header" class="modal-title"></h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row modal-element">
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Quantity')}}</label>
                                <input type="number" step="any" name="edit_qty" class="form-control numkey">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Unit Discount')}}</label>
                                <input type="number" name="edit_discount" class="form-control numkey">
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Price Option')}}</strong> </label>
                                    <div class="input-group">
                                      <select class="form-control selectpicker" name="price_option" class="price-option">
                                      </select>
                                  </div>
                                </div>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Unit Price')}}</label>
                                @can('price_edit_in_sale')
                                    <input type="number" name="edit_unit_price" class="form-control numkey" step="any">
                                @else
                                    <input type="number" name="edit_unit_price" class="form-control numkey" step="any" readonly>
                                @endcan
                            </div>
                            <?php
                                $tax_name_all[] = 'No Tax';
                                $tax_rate_all[] = 0;
                                foreach($lims_tax_list as $tax) {
                                    $tax_name_all[] = $tax->name;
                                    $tax_rate_all[] = $tax->rate;
                                }
                            ?>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Tax Rate')}}</label>
                                <select name="edit_tax_rate" class="form-control selectpicker">
                                    @foreach($tax_name_all as $key => $name)
                                    <option value="{{$key}}">{{$name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div id="edit_unit" class="col-md-4 form-group">
                                <label>{{__('db.Product Unit')}}</label>
                                <select name="edit_unit" class="form-control selectpicker">
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Cost')}}</label>
                                <p id="product-cost"></p>
                            </div>
                        </div>
                        <button type="button" name="update_btn" class="btn btn-primary">{{__('db.update')}}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @include('backend.customer.add_modal')

<!-- ✅ Instalment Plan Modal -->
    <div class="modal fade" id="installmentPlanModal" tabindex="-1" aria-labelledby="installmentPlanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Instalment Plan</h5>
                    <button type="button" id="close-installment-modal-x" data-bs-dismiss="modal" aria-label="Close">X</button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="enable_installment" name="enable_installment" value="0">
                    <div id="installmentFields">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Plan Name</label>
                                    <input type="text" class="form-control" name="installment_plan[name]" value="12 Months" placeholder="e.g., 6 Month Plan">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Price</label>
                                    <input type="number" step="0.01" class="form-control" name="installment_plan[price]" id="installment_price" readonly>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Additional Amount</label>
                                    <input id="additional_amount" type="number" step="0.01" class="form-control" name="installment_plan[additional_amount]" value="0">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Total Amount</label>
                                    <input type="number" step="0.01" class="form-control" name="installment_plan[total_amount]" id="installment_total" readonly>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Down Payment</label>
                                    <input type="number" step="0.01" class="form-control" id="down_payment_id" name="installment_plan[down_payment]" value="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Months</label>
                                    <input type="number" step="1" class="form-control" name="installment_plan[months]" id="installment_months" min="1" value="12">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div id="paymentFields" style="display: none;">
                                    <div class="mb-3">
                                        <label class="form-label">Payment Method</label>
                                        <select name="installment_plan[paid_by_id]" class="form-control selectpicker">
                                            @if(in_array("cash",$options))
                                            <option value="1">{{ __('db.Cash') }}</option>
                                            @endif
                                            @if(in_array("gift_card",$options))
                                            <option value="2">{{ __('db.Gift Card') }}</option>
                                            @endif
                                            @if(in_array("card",$options))
                                            <option value="3">{{ __('db.Credit Card') }}</option>
                                            @endif
                                            @if(in_array("cheque",$options))
                                            <option value="4">{{ __('db.Cheque') }}</option>
                                            @endif
                                            @if(in_array("deposit",$options))
                                            <option value="6">{{ __('db.Deposit') }}</option>
                                            @endif
                                            @if($lims_reward_point_setting_data && $lims_reward_point_setting_data->is_active)
                                            <option value="7">{{ __('db.Points') }}</option>
                                            @endif
                                            @foreach($options as $option)
                                                @if(!in_array($option, ['cash', 'card', 'cheque', 'gift_card', 'deposit', 'paypal', 'pesapal', 'points', 'credit']))
                                                    <option value="{{$option}}">{{ucfirst($option)}}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Account</label>
                                        <select name="installment_plan[account_id]" class="form-control selectpicker">
                                            @if(isset($lims_account_list))
                                            @foreach($lims_account_list as $account)
                                            <option value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                                            @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Payment Note</label>
                                        <textarea name="installment_plan[payment_note]" class="form-control"></textarea>
                                    </div>
                                </div>
                                <div id="schedulePreview" class="mt-2">
                                    <h6>Schedule Preview</h6>
                                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                        <table class="table table-sm table-bordered" id="installmentScheduleTable">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Date</th>
                                                    <th>Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="installment_plan[reference_type]" value="sale">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="close-installment-modal" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="done-installment-modal">Create Instalment</button>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="print-layout">
</section>

<div style="width:100%;max-width:350px;position:fixed;top:5%;left:50%;transform:translateX(-50%);z-index:999">
    <button type="button" class="btn btn-danger" id="closeScannerBtn" style="display:none"> X </button>
    <div id="reader" style="width:100%;"></div>
</div>

@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode"></script>
<script>

    const doneTypingInterval = 200;
    const $input = $('#product-search-input');
    const $results = $('#product-results-container');
    const $noResults = $('#no-results-message');

    function clearResults() {
        $results.empty().css('padding', '0');
        $noResults.hide();
    }

    $(document).ready(function() {

        $('#product-search-input').focus();

        let typingTimer;

        function searchProducts(search) {
            $results.css('padding', '0 10px 15px');
            $results.html('<div class="loader " title="4" style="border:none;min-height:300px"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="30px" viewBox="0 0 24 30" style="enable-background:new 0 0 50 50;" xml:space="preserve"><rect x="0" y="0" width="4" height="10" fill="#333"><animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0" dur="0.6s" repeatCount="indefinite"></animateTransform></rect><rect x="10" y="0" width="4" height="10" fill="#333"><animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0.2s" dur="0.6s" repeatCount="indefinite"></animateTransform></rect><rect x="20" y="0" width="4" height="10" fill="#333"><animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0.4s" dur="0.6s" repeatCount="indefinite"></animateTransform></rect></svg></div>');
            $noResults.hide();

            $.ajax({
                url: '{{ url("/sales/search") }}',
                type: 'GET',
                data: {
                    warehouse_id: warehouse_id,
                    search: search
                },
                success: function (data) {
                    $results.empty();
                    if (data.length > 0) {
                        $noResults.hide();
                        data.forEach(function (product) {
                            let productHtml = '';
                            let displayStock = '';

                            if(authUser > 2) {
                                displayStock = '';
                            } else {
                                displayStock = ` | ${product.qty} {{ __('db.In Stock') }} `;
                            }

                            var batch_id = product.product_batch_id ? product.product_batch_id : '';

                            if (product.is_imei == '1' || product.is_imei === 1 || product.is_imei === true) {

                                // Check if IMEI already exists in the selected products
                                let imeiNumbersArray = [];
                                let exists = false;
                                $('.imei-number').each(function () {
                                    let val = $(this).val();
                                    imeiNumbersArray = val.split(",");
                                    if(imeiNumbersArray.includes(product.imei_number)) {
                                        exists = true;
                                        return;
                                    }
                                });

                                if((exists == false) && (product.imei_number !== null && $.trim(product.imei_number) !== '')){
                                    productHtml = `
                                        <div class="product-img" data-code="${product.code}"
                                                                data-qty="${product.qty}"
                                                                data-imei="${product.imei_number}"
                                                                data-embedded="${product.is_embeded}"
                                                                data-batch="${batch_id}"
                                                                data-price="${product.price}">
                                            ${product.name} (${product.code}) | ${product.price} | IMEI: ${product.imei_number}
                                        </div>
                                    `;
                                }else{
                                    $noResults.show();
                                }
                            } else if (product.product_batch_id != null) {
                                if(parseInt(product.qty) > 0){
                                    if(product.expired_date == 0) {
                                        product.expired_date = "{{__('db.expired')}}";
                                        var expired = "expired";
                                    }
                                    productHtml = `
                                        <div class="product-img ${expired}" data-code="${product.code}"
                                                                            data-qty="${product.qty}"
                                                                            data-imei="${product.is_imei}"
                                                                            data-embedded="${product.is_embeded}"
                                                                            data-batch="${batch_id}"
                                                                            data-price="${product.price}">
                                            ${product.name} (${product.code}) - ${product.expired_date} | ${product.price} ${displayStock}
                                        </div>
                                    `;
                                }
                            } else if (product.type == 'service' || product.type == 'digital') {
                                productHtml = `
                                    <div class="product-img" data-code="${product.code}"
                                                                        data-qty="N/A"
                                                                        data-imei="${product.is_imei}"
                                                                        data-embedded="${product.is_embeded}"
                                                                        data-batch="${batch_id}"
                                                                        data-price="${product.price}">
                                        ${product.name} (${product.code}) | ${product.price} ${displayStock}
                                    </div>
                                `;
                            } else {
                                if(parseInt(product.qty) > 0 || without_stock == 'yes'){
                                    productHtml = `
                                        <div class="product-img" data-code="${product.code}"
                                                                data-qty="${product.qty}"
                                                                data-imei="${product.is_imei}"
                                                                data-embedded="${product.is_embeded}"
                                                                data-batch="${batch_id}"
                                                                data-price="${product.price}">
                                            ${product.name} (${product.code}) | ${product.price} ${displayStock}
                                        </div>
                                    `;
                                }
                            }

                            $results.append(productHtml);
                        });

                        $('.product-img').on('click', function () {
                            clearResults();
                        });

                        // Auto-click if only one result
                        if (data.length === 1) {

                            //let product = data.name; // ✅ define it properly

                            if (click === 0) {
                                $('#product-results-container .product-img').first().trigger('click');
                            }

                            clearResults();
                            click = 1;
                        }

                    } else {
                        clearResults();
                        $noResults.show();
                    }
                },
                error: function () {
                    $noResults.text("Error searching products.").show();
                }
            });
        }

        var click = 0;

        // Trigger on input
        $input.on('input', function () {
            const value = $(this).val().trim();
            if (value.length >= 3) {
                click = 0;
                clearTimeout(typingTimer);
                typingTimer = setTimeout(() => searchProducts(value), doneTypingInterval);
            } else {
                clearResults();
            }
        });

        // Trigger on paste
        $input.on('paste', function (e) {
            const pastedData = (e.originalEvent || e).clipboardData.getData('text');
            if (pastedData.length >= 3) {
                click = 0;
                searchProducts(pastedData.trim());
            }
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#product-results-container, #product-search-input').length) {
                clearResults();
            }
        });

        // ✅ Instalment Logic
        $('#installmentPlanBtn').on('click', function() {
            var customer_type = $('#customer_id option:selected').data('type');
            if(customer_type == 'walkin') {
                alert('Instalment Plan is not available for walk-in customer');
                return;
            }
            let baseTotal = parseFloat($('#total').text().replace(/,/g, '')) || 0;
            let additionalAmount = parseFloat($('#additional_amount').val()) || 0;
            let installment_total_price = baseTotal + additionalAmount;

            $('#installment_price').val(baseTotal.toFixed(2));
            $('#installment_total').val(installment_total_price.toFixed(2));

            updateSchedulePreview();
            $('#installmentPlanModal').modal('show');
        });

        function updateSchedulePreview() {
            let total = parseFloat($('#installment_total').val()) || 0;
            let downPayment = parseFloat($('#down_payment_id').val()) || 0;
            let months = parseInt($('#installment_months').val()) || 1;
            let remaining = total - downPayment;
            let installmentAmount = (months > 0) ? (remaining / months).toFixed(2) : 0;

            let tbody = $('#installmentScheduleTable tbody');
            tbody.empty();

            let date = new Date();
            for (let i = 1; i <= months; i++) {
                let nextDate = new Date(date);
                nextDate.setMonth(date.getMonth() + i);
                let dateString = nextDate.toISOString().split('T')[0];
                tbody.append(`<tr><td>${i}</td><td>${dateString}</td><td>${installmentAmount}</td></tr>`);
            }

            if (downPayment > 0) {
                $('#paymentFields').slideDown();
                $('#done-installment-modal').text('Create Instalment & Pay');
            } else {
                $('#paymentFields').slideUp();
                $('#done-installment-modal').text('Create Instalment');
            }
        }

        $('#additional_amount, #down_payment_id, #installment_months').on('input', function() {
            let baseTotal = parseFloat($('#installment_price').val()) || 0;
            let additional_amount = parseFloat($('#additional_amount').val()) || 0;
            let installment_total_price = baseTotal + additional_amount;

            $('#installment_total').val(installment_total_price.toFixed(2));
            updateSchedulePreview();
        });

        // ✅ When Done button clicked - Validate and Submit main form
        $('#done-installment-modal').on('click', function() {
            let months = parseInt($('#installment_months').val()) || 0;
            if (months < 1) {
                alert('Please enter valid number of months.');
                return;
            }

            // Sync totals to main form
            let total = $('#installment_total').val();
            $('input[name="grand_total"]').val(total);
            $('#grand_total').text(parseFloat(total).toFixed(2));

            let downPayment = parseFloat($('#down_payment_id').val()) || 0;
            if (downPayment > 0) {
                $('#payment_status').val('3').trigger('change'); // Partial
                $('#paying-amount').val(downPayment);
                $('#paid-amount').val(downPayment);

                // Sync payment method and account from modal to main form
                let method = $('#installmentPlanModal select[name="installment_plan[paid_by_id]"]').val();
                let account = $('#installmentPlanModal select[name="installment_plan[account_id]"]').val();
                let note = $('#installmentPlanModal textarea[name="installment_plan[payment_note]"]').val();

                $('select[name="paid_by_id[]"]').val(method).trigger('change');
                $('select[name="account_id"]').val(account).trigger('change');
                $('textarea[name="payment_note"]').val(note);
            } else {
                $('#payment_status').val('1').trigger('change'); // Pending
                $('#paying-amount').val(0);
                $('#paid-amount').val(0);
            }

            // Enable installments and Trigger the main form submission
            $('.payment-form').find('input[name^="installment_plan"], input[name="enable_installment"]').remove();
            $('.payment-form').append('<input type="hidden" name="enable_installment" value="1">');
            $('#installmentPlanModal').find('input, select, textarea').each(function() {
                if($(this).attr('name') && $(this).attr('name').startsWith('installment_plan')) {
                    $('.payment-form').append('<input type="hidden" name="' + $(this).attr('name') + '" value="' + $(this).val() + '">');
                }
            });

            $('.payment-form').submit();
        });

        $('#close-installment-modal, #close-installment-modal-x').on('click', function() {
            $('#installmentPlanModal').modal('hide');
        });

        // ✅ Reset modal fields every time it's closed
        $('#installmentPlanModal').on('hidden.bs.modal', function() {
            $(this).find('input[name="installment_plan[name]"]').val('12 Months');
            $(this).find('input[name="installment_plan[additional_amount]"]').val('0');
            $(this).find('input[name="installment_plan[down_payment]"]').val('0');
            $(this).find('input[name="installment_plan[months]"]').val('12');
            $(this).find('textarea[name="installment_plan[payment_note]"]').val('');
            $(this).find('select[name="installment_plan[paid_by_id]"]').val('1');
            $('#paymentFields').hide();
            $('#done-installment-modal').text('Create Instalment');
            $('#installmentScheduleTable tbody').empty();
        });

    });
</script>


<script>
    const closeScannerBtn = document.getElementById("closeScannerBtn");
    const scanner = document.getElementById("reader");
    const html5Qrcode = new Html5Qrcode('reader');

    function barcode() {
        const qrCodeSuccessCallback = (decodedText, decodedResult) => {
            if (decodedText) {
                document.getElementById('lims_productcodeSearch').value = decodedText;
                html5Qrcode.stop();
                closeScannerBtn.style.display = "none";
            }
        };

        const config = {
            fps: 30,
            qrbox: { width: 300, height: 100 },
            // ðŸ‘‡ Add this line to support Code128
            // formatsToSupport: [ Html5QrcodeSupportedFormats.CODE_128 ]
        };

        html5Qrcode.start({ facingMode: "environment" }, config, qrCodeSuccessCallback);
        closeScannerBtn.style.display = "inline-block";
    }

    closeScannerBtn.addEventListener("click", function () {
        closeScannerBtn.style.display = "none";
        html5Qrcode.stop();
    });
</script>
<script type="text/javascript">

    $("ul#sale").siblings('a').attr('aria-expanded','true');
    $("ul#sale").addClass("show");
    $("ul#sale #sale-create-menu").addClass("active");

    @if(config('database.connections.saleprosaas_landlord'))
        numberOfInvoice = <?php echo json_encode($numberOfInvoice)?>;
        $.ajax({
            type: 'GET',
            async: false,
            url: '{{route("package.fetchData", gen_setting()->package_id)}}',
            success: function(data) {
                if(data['number_of_invoice'] > 0 && data['number_of_invoice'] <= numberOfInvoice) {
                    localStorage.setItem("message", "You don't have permission to create another invoice as you already exceed the limit! Subscribe to another package if you wants more!");
                    location.href = "{{route('sales.index')}}";
                }
            }
        });
    @endif

    @if($lims_pos_setting_data)
        var public_key = <?php echo json_encode($lims_pos_setting_data->stripe_public_key) ?>;
    @endif
    var currency = <?php echo json_encode($currency) ?>;
    var currencyChange = false;
    var without_stock = <?php echo json_encode(gen_setting()->without_stock) ?>;
    var authUser = <?php echo json_encode($authUser) ?>;
    var all_permission = '<?php echo json_encode($all_permission) ?>';
    var restaurantModuleEnabled = @json($restaurantModuleEnabled);

    function isSelectedRestaurantWarehouse() {
        if (!restaurantModuleEnabled) {
            return false;
        }

        var $warehouse = $('#warehouse_id');
        var posType = $warehouse.is('select')
            ? $warehouse.find(':selected').data('pos-type')
            : $warehouse.data('pos-type');

        return String(posType || '').toLowerCase() === 'restaurant';
    }

    function filterRestaurantTablesByWarehouse() {
        if (!$('#table_id').length) {
            return;
        }

        var warehouseId = String($('#warehouse_id').val() || '');
        var currentTableId = String($('#table_id').val() || '');
        var currentTableIsAvailable = false;

        $('#table_id option').each(function () {
            var belongsToWarehouse = String($(this).data('warehouse-id') || '') === warehouseId;
            $(this).prop('disabled', !belongsToWarehouse).prop('hidden', !belongsToWarehouse);
            if (belongsToWarehouse && String($(this).val()) === currentTableId) {
                currentTableIsAvailable = true;
            }
        });

        if (!currentTableIsAvailable) {
            $('#table_id').val('');
        }
    }

    function syncRestaurantServiceRequirements() {
        if (!isSelectedRestaurantWarehouse() || !$('#service_id').length) {
            return;
        }

        var isDineIn = String($('#service_id').val() || '') === '1';
        $('#table_id, #waiter_id')
            .prop('disabled', !isDineIn)
            .prop('required', isDineIn);

        if (!isDineIn) {
            $('#table_id, #waiter_id').val('');
        }

        $('.selectpicker').selectpicker('refresh');
    }

    function syncRestaurantSaleUi() {
        var isRestaurant = isSelectedRestaurantWarehouse();

        $('.restaurant-sale-field').toggle(isRestaurant);
        $('#service_id')
            .prop('disabled', !isRestaurant)
            .prop('required', isRestaurant);

        if (isRestaurant) {
            filterRestaurantTablesByWarehouse();
            syncRestaurantServiceRequirements();
        } else {
            $('#service_id, #table_id, #waiter_id')
                .prop('disabled', true)
                .prop('required', false);
        }

        var $processingOption = $('select[name="sale_status"] option[value="5"]');
        $processingOption
            .prop('disabled', !isRestaurant)
            .prop('hidden', !isRestaurant);

        if (!isRestaurant && String($('select[name="sale_status"]').val() || '') === '5') {
            $('select[name="sale_status"]').val('1');
        }

        $('.selectpicker').selectpicker('refresh');
    }

    $(function () {
        syncRestaurantSaleUi();
    });

    $(document).on('change', '#service_id', function () {
        syncRestaurantServiceRequirements();
    });

    $('#currency').val(currency['id']);

    $('#currency').change(function(){
        var rate = $(this).find(':selected').data('rate');
        var prev_rate = currency['exchange_rate'];
        var currency_id = $(this).val();
        $('#exchange_rate').val(rate);
        //$('input[name="currency_id"]').val(currency_id);
        currency['exchange_rate'] = rate;
        $("table.order-list tbody .product-id").each(function(index) {
            rowindex = index;
            currencyChange = true;
            cur_product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product-id').val();
            var qty = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val();
            var price = (parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_price').val()) + parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-value').val()));

            price = (price / prev_rate);
            checkDiscount(qty, true, price);
            couponDiscount();
        });
    });



    function setCustomerGroupRate(id) {
        $.get('{{ url("sales/getcustomergroup") }}/' + id, function(data) {
            customer_group_rate = (data / 100);
        });
    }

$(window).on('load', async function () {

    var customer_id = $('#customer_id').val();
    setCustomerGroupRate(customer_id);
});

$("#payment").hide();
$(".card-element").hide();
$("#gift-card").hide();
$("#cheque").hide();

// array data depend on warehouse
var lims_product_array = [];
var product_code = [];
var product_name = [];
var product_qty = [];
var product_type = [];
var product_id = [];
var product_list = [];
var variant_list = [];
var qty_list = [];

// array data with selection
var product_price = [];
var wholesale_price = [];
var cost = [];
var product_discount = [];
var tax_rate = [];
var tax_name = [];
var tax_method = [];
var unit_name = [];
var unit_operator = [];
var unit_operation_value = [];
var is_imei = [];
var is_variant = [];
var gift_card_amount = [];
var gift_card_expense = [];
// temporary array
var temp_unit_name = [];
var temp_unit_operator = [];
var temp_unit_operation_value = [];

var deposit = <?php echo json_encode($deposit) ?>;
var points = <?php echo json_encode($points) ?>;

var reward_point_setting = <?php echo json_encode($lims_reward_point_setting_data ?? []) ?>;
if (reward_point_setting === null) reward_point_setting = {};

var rowindex;
var customer_group_rate;
var row_product_price;
var pos;
var role_id = <?php echo json_encode(Auth::user()->role_id)?>;

var warehouse_id = $('#warehouse_id').val();

$('.selectpicker').selectpicker({
    style: 'btn-link',
});

$('[data-toggle="tooltip"]').tooltip();

$('select[name="customer_id"]').on('change', function() {
    setCustomerGroupRate($(this).val());
});

let previousqty = '';

$("#myTable").on('focus', '.qty', function() {
    previousqty = $(this).val();
});

//Change quantity
$("#myTable").on('focusout', '.qty', function () {

        let $input  = $(this);
        let value   = $.trim($input.val());
        let max     = parseFloat($input.attr('max'));
        rowindex = $input.closest('tr').index();

        // --- 1) Empty or non-numeric check
        if (value === "" || isNaN(value)) {
            $input.val(1);
            alert("Quantity must be a number.");
            return;
        }

        value = parseFloat(value);

        // --- 2) Must be greater than 0
        if (value <= 0) {
            $input.val(1);
            alert("Quantity must be greater than 0.");
            return;
        }

        // --- 3) Max attribute validation
        if (!isNaN(max) && value > max && without_stock == 'no') {
            $input.val(max);
            alert("Quantity cannot exceed available stock (" + max + ").");
            return;
        }

        // --- 4) Safe to continue with valid value
        $input.val(value);

        if(is_variant[rowindex]){
            checkQuantity(value, true);
        } else {
            checkDiscount(value, 'input');
        }
    });

//Delete product
$("table.order-list tbody").on("click", ".ibtnDel", function(event) {
    rowindex = $(this).closest('tr').index();
    product_price.splice(rowindex, 1);
    wholesale_price.splice(rowindex, 1);
    product_discount.splice(rowindex, 1);
    tax_rate.splice(rowindex, 1);
    tax_name.splice(rowindex, 1);
    tax_method.splice(rowindex, 1);
    unit_name.splice(rowindex, 1);
    unit_operator.splice(rowindex, 1);
    unit_operation_value.splice(rowindex, 1);
    is_imei.splice(rowindex, 1);
    $(this).closest("tr").remove();
    calculateTotal();
    if ($('#tbody-id tr').length < 1) {
        $('#installmentPlanBtn').attr('disabled', true);
    }
});

//Edit product
$("table.order-list").on("click", ".edit-product", function() {
    rowindex = $(this).closest('tr').index();
    edit();
});

//Update product
$('button[name="update_btn"]').on("click", function() {
    if(is_imei[rowindex]) {
        var imeiNumbers = '';
        $("#editModal .imei-numbers").each(function(i) {
            if (i)
                imeiNumbers += ','+ $(this).val();
            else
                imeiNumbers = $(this).val();
        });
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val(imeiNumbers);
    }

    var edit_discount = $('input[name="edit_discount"]').val();
    var edit_qty = $('input[name="edit_qty"]').val();
    var edit_unit_price = $('input[name="edit_unit_price"]').val();

    if (parseFloat(edit_discount) > parseFloat(edit_unit_price)) {
        alert('Invalid Discount Input!');
        return;
    }

    if(edit_qty < 0) {
        $('input[name="edit_qty"]').val(1);
        edit_qty = 1;
        alert("Quantity can't be less than 0");
    }

    var tax_rate_all = <?php echo json_encode($tax_rate_all) ?>;
    tax_rate[rowindex]  = parseFloat(tax_rate_all[$('select[name="edit_tax_rate"]').val()]);
    tax_name[rowindex]  = $('select[name="edit_tax_rate"] option:selected').text();

    var product_type = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product_type').val();

    product_discount[rowindex] = $('input[name="edit_discount"]').val();
    if(product_type == 'standard'){

        row_unit_operator= $('#edit_unit select').find(':selected').data('operator');
        row_unit_operation_value = $('#edit_unit select').find(':selected').data('operation-value');

        if (row_unit_operator == '*') {
            product_price[rowindex] = $('input[name="edit_unit_price"]').val() * row_unit_operation_value;
        } else {
            product_price[rowindex] = $('input[name="edit_unit_price"]').val() / row_unit_operation_value;
        }
        var position = $('select[name="edit_unit"]').val();
        var temp_operator = temp_unit_operator[position];
        var temp_operation_value = temp_unit_operation_value[position];
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit').val(temp_unit_name[position]);
        temp_unit_name.splice(position, 1);
        temp_unit_operator.splice(position, 1);
        temp_unit_operation_value.splice(position, 1);

        temp_unit_name.unshift($('select[name="edit_unit"] option:selected').text());
        temp_unit_operator.unshift(temp_operator);
        temp_unit_operation_value.unshift(temp_operation_value);

        unit_name[rowindex] = temp_unit_name.toString() + ',';
        unit_operator[rowindex] = temp_unit_operator.toString() + ',';
        unit_operation_value[rowindex] = temp_unit_operation_value.toString() + ',';
    }
    else {
        product_price[rowindex] = $('input[name="edit_unit_price"]').val();
    }
    product_discount[rowindex] = $('input[name="edit_discount"]').val();
    checkDiscount(edit_qty, false);
    //checkQuantity(edit_qty, false);
    $('#editModal').modal('hide');
});

$("#myTable").on('click', '.plus', function() {
    var row = $(this).closest('tr');
    var qtyInput = row.find('.qty'); 
    
    var qty = parseFloat(qtyInput.val()) || 0;
    var max_qty = parseFloat(qtyInput.attr('max'));

    if(!qty)
        qty = 1;
    else if(!isNaN(max_qty) && qty >= max_qty && without_stock == 'no') {
        alert("Quantity cannot exceed available stock (" + max_qty + ").");
        return;
    }
    else
        qty = parseFloat(qty) + 1;
    if(is_variant[rowindex]){
        checkQuantity(String(qty), true);
    }else{
        checkDiscount(qty, true);
    }
});

$("#myTable").on('click', '.minus', function() {
    rowindex = $(this).closest('tr').index();
    var qty = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val()) - 1;
    if (qty > 0) {
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(qty);

        if(is_variant[rowindex])
            checkQuantity(String(qty), true);
        else
            checkDiscount(qty, '3');
    }
    else {
        qty = 1;
    }

});

$('#warehouse_id').on('change', function() {
        warehouse_id = $(this).val();
        syncRestaurantSaleUi();
        // getProduct(warehouse_id);
        $('#featured-filter').trigger('click');
    });

    $('#customer_id').on('change', function() {
        var customer_id = $(this).val();
        $.get('{{url("sales/getcustomergroup")}}/' + customer_id, function(data) {
            customer_group_rate = (data / 100);
        });

        if ($('#tbody-id tr').length > 0) {
            $('#installmentPlanBtn').removeAttr('disabled');
        }
    });

$("select[name=price_option]").on("change", function () {
    $("#editModal input[name=edit_unit_price]").val($(this).val());
});

$("#myTable").on("change", ".batch-no", function () {
    rowindex = $(this).closest('tr').index();
    var product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-id').val();
    var warehouse_id = $('#warehouse_id').val();
    $.get('../check-batch-availability/' + product_id + '/' + $(this).val() + '/' + warehouse_id, function(data) {
        if(data['message'] != 'ok') {
            alert(data['message']);
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.batch-no').val('');
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-batch-id').val('');
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.expired-date').text('');
        }
        else {
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-batch-id').val(data['product_batch_id']);
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.expired-date').text(data['expired_date']);
            code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-code').val();
            pos = product_code.indexOf(code);
            product_qty[pos] = data['qty'];
        }
    });
});

$(document).on('click', '.product-img', function() {

    clearResults();

    var customer_id = $('#customer_id').val();
    var warehouse_id = $('#warehouse_id').val();
    var biller_id = $('#biller_id').val();

    var isRestaurantWarehouse = isSelectedRestaurantWarehouse();
    var table_id = isRestaurantWarehouse ? $('#table_id').val() : null;
    var waiter_id = isRestaurantWarehouse ? $('#waiter_id').val() : null;
    var service_id = isRestaurantWarehouse ? $('#service_id').val() : null;

    if(!customer_id)
        alert('Please select Customer!');
    else if(!warehouse_id)
        alert('Please select Warehouse!');
    else if(!biller_id)
        alert('Please select Biller!');
    else if(isRestaurantWarehouse && !service_id){
        alert('Please select Service!');
    }
    else if(isRestaurantWarehouse && service_id == 1 && !table_id){
        alert('Please select Table!');
    }
    else if(isRestaurantWarehouse && service_id == 1 && !waiter_id){
        alert('Please select Waiter!');
    }
    else{
        var data = $(this).data();
        productSearch(data);
    }
});

async function productSearch(data) {
    // if(data.embedded == 1) {
    //     alert('{{ __("db.product_added_using_scale_weight")}}');
    //     return;
    // }
    var item_code = data.code;
    var pre_qty = 0;
    var flag = true;
    $(".product-code").each(function(i) {
        if ($(this).val().trim() == item_code) {
            rowindex = i;
            if(data.imei != 'null' && data.imei != '') {
                imeiNumbers = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .imei-number').val();
                imeiNumbersArray = imeiNumbers.split(",");

                if(imeiNumbersArray.includes(data.imei)) {
                    alert('Same imei or serial number is not allowed!');
                    flag = false;
                    $('#product-search-input').val('');
                    return;
                }
            }
            pre_qty = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val();
        }
    });
    if(flag)
    {
        let product = {
            code: data.code,
            qty: data.qty,
            pre_qty: (parseFloat(pre_qty) + 1),
            imei: data.imei,
            embedded: data.embedded,
            batch: data.batch,
            price: data.price,
            customer_id: $('#customer_id').val()
        };
        await $.ajax({
            type: 'GET',
            url: '{{url("sales/lims_product_search")}}',
            data: {
                data: product
            },
            success: function(data) {
                // Capture rowindex immediately — it's a shared global that can
                // change if the user clicks another product before this callback fires.
                const capturedRowindex = rowindex;

                if(isSelectedRestaurantWarehouse() && data.modifiers && data.modifiers.length) {
                    data.qty = 1;
                    pre_qty = 0;
                }
                if(pre_qty > 0 && data.batch_id) {
                    const $existingRow = $('table.order-list tbody tr:nth-child(' + (capturedRowindex + 1) + ')');
                    const old_batch = $existingRow.find('.batch-no').val();
                    if(old_batch && old_batch != data.batch_no) {
                        pre_qty = 0;
                        data.qty = 1;
                    }
                }

                var flag = 1;
                if (pre_qty > 0) {
                    const qty = data.qty;
                    rowindex = capturedRowindex;
                    const $existingRow = $('table.order-list tbody tr:nth-child(' + (capturedRowindex + 1) + ')');
                    $existingRow.find('.qty').val(qty);

                    product_price[capturedRowindex] = parseFloat(data.price * currency['exchange_rate']) + parseFloat(data.price * currency['exchange_rate'] * customer_group_rate);

                    // Update discount from fresh server response (qty may affect discount tier)
                    if (data.discount !== undefined) {
                        product_discount[capturedRowindex] = parseFloat(data.discount) * currency['exchange_rate'];
                    }

                    rowindex = capturedRowindex;
                    checkQuantity(String(qty), true);
                    flag = 0;
                }
                $("input[name='product_code_name']").val('');

                if(flag){
                    rowindex = capturedRowindex;
                    addNewProduct(data);
                }
                else if(data.imei_number != 'null' && data.imei_number != '') {
                    const $existingRow = $('table.order-list tbody tr:nth-child(' + (capturedRowindex + 1) + ')');
                    let imeiNumbers = $existingRow.find('.imei-number').val();
                    imeiNumbers = imeiNumbers ? imeiNumbers + ',' + data.imei_number : data.imei_number;
                    $existingRow.find('.imei-number').val(imeiNumbers);
                }
            }
        });
    }

}

function addNewProduct(data){
    $('.payment-btn').removeAttr('disabled');
    $('#installmentPlanBtn').removeAttr('disabled');
    var newRow = $('<tr id='+ data.code +'>');
    var cols = '';
    temp_unit_name = (data.unit_name).split(',');
    pos = product_code.indexOf(data.code);

    let stockDisplay = '';
    var variantDisplay = '';
    if (data.variant_name && data.variant_name.length > 0) {
        variantDisplay = '<br><span class="product-variant font-weight-bold" style="font-size: 0.85em; color: #555;">' + "{{trans('file.Variant') == 'file.Variant' ? 'Variant' : trans('file.Variant')}}" + ': ' + data.variant_name + '</span>';
    }

    if (all_permission.includes("cart-product-update")) {
        if(data.type.trim() == 'standard' || data.type.trim() == 'combo'){
            if (!data.imei_number || data.imei_number == 'null') {
                stockDisplay = ` | {{ __('db.In Stock') }} : <span class="in-stock">` + data.warehouse_qty + `</span>`;
            }
        }
        cols += '<td class="product-title"><strong class="edit-product btn btn-link pl-0 pr-0" data-toggle="modal" data-target="#editModal">' + data.name + ' <i class="ti ti-edit"></i></strong>' + variantDisplay + '<br><span>' + data.code + '</span>' + stockDisplay + ' <strong class="product-price d-md-none"></strong>';
    } else {
        cols += '<td class="product-title"><strong>' + data.name + '</strong>' + variantDisplay + '<br><span>' + data.code + '</span>' + stockDisplay + ' <strong class="product-price d-md-none"></strong>';
    }

    if(data.is_batch) {
        cols += '<br><input style="font-size:13px;padding:3px 25px 3px 10px;height:30px !important" type="text" class="form-control batch-no" value="'+data.batch_no+'" required/> <input type="hidden" class="product-batch-id" name="product_batch_id[]" value="'+data.batch_id+'"/>';
    }
    else {
        cols += '<input type="text" class="form-control batch-no d-none" disabled/> <input type="hidden" class="product-batch-id" name="product_batch_id[]"/>';
    }

    cols += '</td>';
    cols += '<td><div class="input-group"><span class="input-group-btn">';

    // If no IMEI, show minus button
    if (!data.imei_number || data.imei_number == 'null') {
        cols += '<button type="button" class="btn btn-default minus mr-1" style="padding:5px 8px"><i class="ti ti-minus"></i></button></span>';
    }

    // Input field
    cols += '<input type="text" name="qty[]" class="form-control qty numkey input-number" style="font-size:13px;max-width:50px;padding: 0 0;text-align:center" step="any" value="'+data.qty+'" max="'+data.warehouse_qty+'" required><span class="input-group-btn">';

    // If no IMEI, show plus button
    if (!data.imei_number || data.imei_number == 'null') {
        cols += '<button type="button" class="btn btn-default plus ml-1" style="padding:5px 8px"><i class="ti ti-plus"></i></button>';
    }

    cols += '</span></div></td>';

    cols += '<td class="product-price"></td>';
    cols += '<td class="discount">0.00</td>';
    cols += '<td class="tax">0.00</td>';

    cols += '<td class="sub-total"></td>';
    // Always show delete button
    cols += '<td><button type="button" class="ibtnDel btn btn-danger btn-sm mr-2"><i class="ti ti-trash"></i></button></td>';

    cols += '<input type="hidden" class="product-code" name="product_code[]" value="' + data.code + '"/>';
    cols += '<input type="hidden" class="product-id" name="product_id[]" value="' + data.id + '"/>';
    cols += '<input type="hidden" class="product_type" name="product_type[]" value="' + data.type + '"/>';
    cols += '<input type="hidden" class="product_price" />';
    cols += '<input type="hidden" class="sale-unit" name="sale_unit[]" value="' + temp_unit_name[0] + '"/>';
    cols += '<input type="hidden" class="net_unit_price" name="net_unit_price[]" />';
    cols += '<input type="hidden" class="discount-value" name="discount[]" />';
    cols += '<input type="hidden" class="tax-rate" name="tax_rate[]" value="' + data.tax_rate + '"/>';
    cols += '<input type="hidden" class="tax-value" name="tax[]" />';
    cols += '<input type="hidden" class="tax-name" value="'+data.tax_name+'" />';
    cols += '<input type="hidden" class="tax-method" value="'+data.tax_method+'" />';
    cols += '<input type="hidden" class="sale-unit-operator" value="'+data.unit_operator+'" />';
    cols += '<input type="hidden" class="sale-unit-operation-value" value="'+data.unit_operation_value+'" />';
    cols += '<input type="hidden" class="subtotal-value" name="subtotal[]" />';
    if(data.imei_number != 'null' && data.imei_number != '')
        cols += '<input type="hidden" class="imei-number" name="imei_number[]" value="'+data.imei_number+'" />';
    else
        cols += '<input type="hidden" class="imei-number" name="imei_number[]" value="" />';
    if(isSelectedRestaurantWarehouse() && data.modifiers && data.modifiers.length){
        cols += '<input type="hidden" class="topping_product" name="topping_product[]" value="" />';
        cols += '<input type="hidden" class="topping-price" name="topping-price" value="" />';
    }

    newRow.append(cols);

    $("table.order-list tbody").prepend(newRow);

    rowindex = newRow.index();

    // ── Price Arrays ──────────────────────────────────────────────────────────
    const _exRate    = currency['exchange_rate'];
    const _cgRate    = customer_group_rate;
    const _retail    = parseFloat(data.price * _exRate) + parseFloat(data.price * _exRate * _cgRate);
    const _wholesale = data.wholesale_price
        ? parseFloat(data.wholesale_price * _exRate) + parseFloat(data.wholesale_price * _exRate * _cgRate)
        : 0;

    product_price.splice(rowindex, 0, _retail);
    wholesale_price.splice(rowindex, 0, _wholesale || '{{number_format(0, gen_setting()->decimal, '.', '')}}');
    cost.splice(rowindex, 0, parseFloat(data.cost * _exRate));

    // ── Discount from server response (no second AJAX needed) ─────────────────
    const _discountPerUnit = parseFloat(data.discount || 0) * _exRate;
    product_discount.splice(rowindex, 0, _discountPerUnit);

    tax_rate.splice(rowindex, 0, parseFloat(data.tax_rate));
    tax_name.splice(rowindex, 0, data.tax_name);
    tax_method.splice(rowindex, 0, data.tax_method);
    unit_name.splice(rowindex, 0, data.unit_name);
    unit_operator.splice(rowindex, 0, data.unit_operator);
    unit_operation_value.splice(rowindex, 0, data.unit_operation_value);
    is_imei.splice(rowindex, 0, data.is_imei);
    is_variant.splice(rowindex, 0, data.is_variant);

    // Cache row reference once — reused below (avoids repeated nth-child scans)
    const $newRow = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')');
    $newRow.find('.product_price').val(product_price[rowindex]);

    // ── Single synchronous calculation — no AJAX, no redundant DOM scans ──
    checkQuantity(data.qty, true);

    if(data.wholesale_price) {
        populatePriceOption();
        $newRow.find('.edit-product').click();
    }

    if (isSelectedRestaurantWarehouse() && data.modifiers && Array.isArray(data.modifiers) && data.modifiers.length > 0) {
        if(productSale && productSale.length > 0) {

            if (product_discount[rowindex] < 1) {
                cur_product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product-id').val();
                @if (isset($draft_product_discount))
                    if (product_discount[rowindex] < 1) {
                        draft_discounts = @json($draft_product_discount['discount']);
                        product_discount[rowindex] = draft_discounts[cur_product_id];
                    }
                @endif
            }

            // Find a match for current data.id (product_id)
            let matchedIndex = productSale.findIndex(p => parseInt(p.product_id) === parseInt(data.id));

            if (matchedIndex !== -1) {
                let matchedProduct = productSale[matchedIndex];

                // Parse toppings
                let toppings = JSON.parse(matchedProduct.topping_id || '[]');

                let toppingNames = toppings.map(t => `${t.group_name || ''}: ${t.name}`).join(" | ");
                let totalToppingPrice = toppings.reduce((sum, t) => sum + parseFloat(t.price_adjustment || t.price || 0), 0);

                newRow.find('.product-title').append(`<br><small>Includes: ${toppingNames}</small>`);
                newRow.find('.topping_product').val(matchedProduct.topping_id || JSON.stringify(toppings));
                newRow.find('.topping-price').val(totalToppingPrice.toFixed({{gen_setting()->decimal}}));

                const currentPrice = parseFloat(newRow.find('.net_unit_price').val()) || 0;
                let newPrice = currentPrice + totalToppingPrice;
                
                newRow.find('.net_unit_price').val(newPrice.toFixed({{gen_setting()->decimal}}));
                product_price[rowindex] = newPrice;
                
                var qty = newRow.find('.qty').val();
                checkDiscount(qty, true, newPrice);

                // Remove used item from array
                productSale.splice(matchedIndex, 1);
            }

        }else{
            openToppingsModal(data, [], rowindex);

            function openToppingsModal(data, selectedToppings = [], rowIndex = null) {
                let modalContent = '<form id="product-selection-form">';
                data.modifiers.forEach(group => {
                    let requiredLabel = group.is_required ? '<span class="text-danger">*</span>' : '';
                    let reqText = [];
                    if (group.is_required && group.min_selection > 0) reqText.push(`Min: ${group.min_selection}`);
                    if (group.max_selection > 0) reqText.push(`Max: ${group.max_selection}`);
                    let smallText = reqText.length ? `<small class="text-muted ml-2">(${reqText.join(', ')})</small>` : '';

                    modalContent += `
                        <div class="modifier-group mb-3" data-id="${group.id}" data-name="${group.name}" data-min="${group.min_selection}" data-max="${group.max_selection}" data-required="${group.is_required ? 1 : 0}">
                            <h6 class="border-bottom pb-1 mb-2">${group.name} ${requiredLabel} ${smallText}</h6>
                            <div class="modifier-options">`;
                    
                    group.modifiers.forEach(mod => {
                        const selected = selectedToppings.find(t => t.id == mod.id && t.group_id == group.id);
                        const isChecked = selected ? 'checked' : '';
                        const inputType = group.selection_type === 'single' ? 'radio' : 'checkbox';
                        const inputName = group.selection_type === 'single' ? `group_${group.id}` : `modifier_${mod.id}`;
                        const priceLabel = parseFloat(mod.price_adjustment) > 0 ? ` (+${parseFloat(mod.price_adjustment).toFixed(2)})` : '';
                        
                        modalContent += `
                            <div class="form-check d-flex align-items-center mb-1">
                                <div>
                                    <input class="form-check-input mod-input" type="${inputType}" name="${inputName}" id="mod_${mod.id}" value="${mod.id}" data-group-id="${group.id}" data-group-name="${group.name}" data-name="${mod.name}" data-price="${mod.price_adjustment}" ${isChecked}>
                                    <label class="form-check-label" for="mod_${mod.id}">
                                        ${mod.name}${priceLabel}
                                    </label>
                                </div>
                            </div>`;
                    });
                    modalContent += `</div></div>`;
                });
                modalContent += '</form>';

                const modalHTML = `
                    <div class="modal fade" id="productSelectionModal" tabindex="-1" role="dialog" aria-labelledby="productSelectionModalLabel" aria-hidden="true" data-rowindex="${rowIndex}" data-backdrop="static">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="productSelectionModalLabel">{{__('db.select_modifiers')}}</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                                    <div id="modifier-error" class="alert alert-danger d-none p-2 mb-2"></div>
                                    ${modalContent}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="confirmSelection">Confirm</button>
                                </div>
                            </div>
                        </div>
                    </div>`;

                // Remove existing modal if any, then append and show new
                $("#productSelectionModal").remove();
                $("body").append(modalHTML);
                $("#productSelectionModal").modal('show');
            }


            // Handle selection confirmation
            $("body").off("click", "#confirmSelection").on("click", "#confirmSelection", function () {
                let selectedToppings = [];
                let totalAdditionalPrice = 0;
                let validationError = null;

                if (product_discount[rowindex] < 1) {
                    cur_product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product-id').val();
                    @if (isset($draft_product_discount))
                        if (product_discount[rowindex] < 1) {
                            draft_discounts = @json($draft_product_discount['discount']);
                            product_discount[rowindex] = draft_discounts[cur_product_id];
                        }
                    @endif
                }

                // Validate groups
                $('.modifier-group').each(function() {
                    let $group = $(this);
                    let min = parseInt($group.data('min')) || 0;
                    let max = parseInt($group.data('max')) || 0;
                    let req = parseInt($group.data('required'));
                    let name = $group.data('name');
                    let checkedCount = $group.find('.mod-input:checked').length;

                    if (req === 1 && checkedCount < min) {
                        validationError = `Please select at least ${min} option(s) for ${name}.`;
                        return false;
                    }
                    if (max > 0 && checkedCount > max) {
                        validationError = `You can only select up to ${max} option(s) for ${name}.`;
                        return false;
                    }
                });

                if (validationError) {
                    $('#modifier-error').text(validationError).removeClass('d-none');
                    return;
                }

                $('#modifier-error').addClass('d-none');

                $(".mod-input:checked").each(function () {
                    const topping = {
                        group_id: $(this).data('group-id'),
                        id: $(this).val(),
                        group_name: $(this).data('group-name'),
                        name: $(this).data('name'),
                        price: parseFloat($(this).data('price')) || 0,
                        qty: 1
                    };

                    selectedToppings.push(topping);
                    totalAdditionalPrice += topping.price;
                });

                if (selectedToppings.length > 0) {
                    const selectedToppingsJson = JSON.stringify(selectedToppings);
                    const selectedProductNames = selectedToppings.map(t => `${t.group_name}: ${t.name}`).join(' | ');

                    newRow.find('.product-title').append(`<br><small class="text-muted">Incl: ${selectedProductNames}</small>`);
                    newRow.find('.topping_product').val(selectedToppingsJson); 
                    newRow.find('.topping-price').val(totalAdditionalPrice.toFixed({{gen_setting()->decimal}}));

                    let baseUnitPrice = parseFloat(newRow.find('.net_unit_price').val()) || 0;
                    baseUnitPrice += totalAdditionalPrice;
                    newRow.find('.net_unit_price').val(baseUnitPrice.toFixed({{gen_setting()->decimal}}));

                    product_price[rowindex] = baseUnitPrice;
                    
                    var qty = newRow.find('.qty').val();
                    checkDiscount(qty, true, baseUnitPrice);
                }

                $("#productSelectionModal").modal('hide');
                $(".modal-backdrop").remove();
                $("#productSelectionModal").remove();
            });

            // Stop further processing until the modal is resolved
            return;
        }
    }
}

function populatePriceOption() {
        var product_price = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product_price').val()).toFixed({{gen_setting()->decimal}});
        var ws_price = parseFloat(wholesale_price[rowindex]).toFixed({{gen_setting()->decimal}});

        $('#editModal select[name=price_option]').empty();
        if(ws_price > 0)
            $('#editModal select[name=price_option]').append('<option value="'+ wholesale_price +'">'+ ws_price +'</option>');
        $('#editModal select[name=price_option]').append('<option selected value="'+ product_price +'">'+ product_price +'</option>');

        $('.selectpicker').selectpicker('refresh');
    }

function edit(){
    $(".imei-section").remove();
    if(is_imei[rowindex]) {

        var imeiNumbers = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val();

        if(imeiNumbers.length) {
            imeiArrays = [...new Set(imeiNumbers.split(","))];
            htmlText = `<div class="col-md-8 form-group imei-section">
                        <label>IMEI or Serial Numbers</label>
                        <div class="table-responsive">
                            <table id="imei-table" class="table table-hover">
                                <tbody>`;
            for (var i = 0; i < imeiArrays.length; i++) {
                htmlText += `<tr>
                                <td>
                                    <input type="text" class="form-control imei-numbers" name="imei_numbers[]" value="`+imeiArrays[i]+`" />
                                </td>
                                <td>
                                    <button type="button" class="imei-del btn btn-sm btn-danger">X</button>
                                </td>
                            </tr>`;
            }
            htmlText += `</tbody>
                            </table>
                        </div>
                    </div>`;
            $("#editModal .modal-element").append(htmlText);
        }
    }
    populatePriceOption();
    $("#product-cost").text(cost[rowindex]);
    var row_product_name_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(1) > strong:nth-child(1)').text();
    $('#modal_header').text(row_product_name_code);

    var qty = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val();
    $('input[name="edit_qty"]').val(qty);

    cur_product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product-id').val();
    @if (isset($draft_product_discount))
        if (product_discount[rowindex] < 1) {
            draft_discounts = @json($draft_product_discount['discount']);
            product_discount[rowindex] = draft_discounts[cur_product_id];
        }
    @endif

    $('input[name="edit_discount"]').val(parseFloat(product_discount[rowindex]).toFixed({{gen_setting()->decimal}}));

    var tax_name_all = <?php echo json_encode($tax_name_all) ?>;
    pos = tax_name_all.indexOf(tax_name[rowindex]);
    $('select[name="edit_tax_rate"]').val(pos);

    var row_product_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-code').val();
    var product_type = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product_type').val();
    if(product_type == 'standard'){
        unitConversion();
        temp_unit_name = (unit_name[rowindex]).split(',');
        temp_unit_name.pop();
        temp_unit_operator = (unit_operator[rowindex]).split(',');
        temp_unit_operator.pop();
        temp_unit_operation_value = (unit_operation_value[rowindex]).split(',');
        temp_unit_operation_value.pop();
        $('select[name="edit_unit"]').empty();
        $.each(temp_unit_name, function(key, value) {
            $('select[name="edit_unit"]').append('<option data-operator="'+temp_unit_operator[key]+'" data-operation-value="'+temp_unit_operation_value[key]+'" value="' + key + '">' + value + '</option>');
        });
        $("#edit_unit").show();
    }
    else{
        row_product_price = product_price[rowindex];
        $("#edit_unit").hide();
    }
    $('input[name="edit_unit_price"]').val(row_product_price.toFixed({{gen_setting()->decimal}}));
    $('.selectpicker').selectpicker('refresh');
}

//Delete imei
$(document).on("click", "table#imei-table tbody .imei-del", function() {
    // Decrease qty
    var edit_qty = parseFloat($('input[name="edit_qty"]').val());
    edit_qty = (edit_qty - 1);
    $('input[name="edit_qty"]').val(edit_qty);

    // Check number of remaining IMEI for the same product
    let imeis = $('#tbody-id tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val();

    let target = $(this).closest("tr").find('.imei-numbers').val();

    // Remove the row
    $(this).closest("tr").remove();

    // 1. Convert to array (remove spaces just in case)
    let arr = imeis.split(',').map(s => s.trim());

    // 2. Filter out the target IMEI
    arr = arr.filter(i => i !== target);

    // 3. Convert back to string
    let updated = arr.join(',');

    // Set the updated value back
    $('#tbody-id tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val(updated);

    if (edit_qty == 0) {
        $('#editModal').modal('hide');
        $('#tbody-id tr:eq('+rowindex+')').remove();
    }

    $('#tbody-id tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(edit_qty);
    checkDiscount(edit_qty,false);
    calculateTotal();
});

/**
 * checkDiscount — used only from the EDIT MODAL / qty-change path.
 * NOT called during initial product add (discount now comes in server response).
 * Converted from async:false to async:true to prevent UI thread blocking.
 */
function checkDiscount(qty, flag, price = 0) {
    const capturedRowindex = rowindex; // capture before async gap
    const customer_id  = $('#customer_id').val();
    const warehouse_id = $('#warehouse_id').val();
    const $row         = $('table.order-list tbody tr:nth-child(' + (capturedRowindex + 1) + ')');
    const product_id   = $row.find('.product-id').val();

    $.ajax({
        type: 'GET',
        async: true, // ✅ Never block the UI thread
        url: '{{url("/")}}/sales/check-discount?qty=' + qty + '&customer_id=' + customer_id + '&product_id=' + product_id + '&warehouse_id=' + warehouse_id,
        success: function(data) {
            rowindex = capturedRowindex;

            if (String(product_price[rowindex]).length === 0) {
                product_price[rowindex] = $row.find('.product_price').val();
            }
            if (price > 0) {
                product_price[rowindex] = parseFloat(price * currency['exchange_rate'])
                    + parseFloat(price * currency['exchange_rate'] * customer_group_rate);
            }

            // Update stored discount then recalculate
            product_discount[rowindex] = parseFloat(data[2] || 0) * currency['exchange_rate'];
            $row.find('.qty').val(qty);
            checkQuantity(String(qty), true);
        }
    });
}

function checkQuantity(sale_qty, flag) {
    // Cache row reference once — avoid repeated nth-child DOM traversals
    const $row        = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')');
    const maxQty      = parseFloat($row.find('.qty').attr('max'));
    const productType = $row.find('.product_type').val();
    const imeiNumbers = $row.find('.imei-number').val();
    sale_qty          = parseFloat(sale_qty);

    if (without_stock == 'no') {
        if (productType && (productType.trim() == 'standard' || productType.trim() == 'combo')) {
            const operator        = unit_operator[rowindex].split(',');
            const operation_value = unit_operation_value[rowindex].split(',');

            let total_stock_qty = sale_qty;
            if (operator[0] == '*')
                total_stock_qty = sale_qty * operation_value[0];
            else if (operator[0] == '/')
                total_stock_qty = sale_qty / operation_value[0];

            // ✅ Iterative clamp — replaces recursive call that risked stack overflow
            if (total_stock_qty > maxQty && !isNaN(maxQty)) {
                if (!imeiNumbers || !imeiNumbers.length) {
                    alert('Quantity exceeds stock quantity!');
                    if (flag) {
                        if (operator[0] == '*')
                            sale_qty = Math.floor(maxQty / operation_value[0]);
                        else if (operator[0] == '/')
                            sale_qty = Math.floor(maxQty * operation_value[0]);
                        else
                            sale_qty = maxQty;
                        sale_qty = Math.max(0, sale_qty);
                    } else {
                        edit();
                        return;
                    }
                }

                if (sale_qty === 0) {
                    $row.remove();
                    calculateTotal();
                    return;
                }
            }
        }
    }

    $row.find('.qty').val(sale_qty);

    if (!flag) {
        $('#editModal').modal('hide');
    }
    calculateRowProductData(sale_qty);
}

function unitConversion() {
    var row_unit_operator = unit_operator[rowindex].slice(0, unit_operator[rowindex].indexOf(","));
    var row_unit_operation_value = unit_operation_value[rowindex].slice(0, unit_operation_value[rowindex].indexOf(","));

    if (row_unit_operator == '*') {
        row_product_price = product_price[rowindex] * row_unit_operation_value;
    } else {
        row_product_price = product_price[rowindex] / row_unit_operation_value;
    }
}

function calculateRowProductData(quantity) {
    quantity = parseFloat(quantity);

    // Draft discount resolution (only applies when restoring a draft sale)
    if (product_discount[rowindex] < 1) {
        const cur_product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product-id').val();
        @if (isset($draft_product_discount))
            if (product_discount[rowindex] < 1) {
                const draft_discounts = @json($draft_product_discount['discount']);
                product_discount[rowindex] = draft_discounts[cur_product_id];
            }
        @endif
    }

    // Cache row reference once — reused for all reads/writes below
    const $row = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')');
    const productTypeVal = $row.find('.product_type').val();
    if (productTypeVal && productTypeVal.trim() == 'standard')
        unitConversion();
    else
        row_product_price = product_price[rowindex];

    let net_unit_price, tax, sub_total, sub_total_unit;

    if (tax_method[rowindex] == 1) {
        net_unit_price = row_product_price - product_discount[rowindex];
        tax            = net_unit_price * quantity * (tax_rate[rowindex] / 100);
        sub_total      = net_unit_price * quantity + tax;
        sub_total_unit = quantity ? sub_total / quantity : sub_total;
    } else {
        sub_total_unit = row_product_price - product_discount[rowindex];
        net_unit_price = (100 / (100 + tax_rate[rowindex])) * sub_total_unit;
        tax            = (sub_total_unit - net_unit_price) * quantity;
        sub_total      = sub_total_unit * quantity;
    }

    const dp = {{gen_setting()->decimal}};
    $row.find('.discount-value').val((product_discount[rowindex] * quantity).toFixed(dp));
    $row.find('.tax-rate').val(tax_rate[rowindex].toFixed(dp));
    $row.find('.net_unit_price').val(net_unit_price.toFixed(dp));
    $row.find('.tax-value').val(tax.toFixed(dp));
    $row.find('.product-price').text(sub_total_unit.toFixed(dp));
    $row.find('.sub-total').text(sub_total.toFixed(dp));
    $row.find('.subtotal-value').val(sub_total.toFixed(dp));

    calculateTotal();
}

function calculateTotal() {
    //Sum of quantity
    var total_qty = 0;
    $("table.order-list tbody .qty").each(function(index) {
        if ($(this).val() == '') {
            total_qty += 0;
        } else {
            total_qty += parseFloat($(this).val());
        }
    });
    $("#total-qty").text(total_qty);
    $('input[name="total_qty"]').val(total_qty);

    //Sum of discount
    var total_discount = 0;
    $("table.order-list tbody .discount-value").each(function() {
        total_discount += parseFloat($(this).val());
    });
    $("#total-discount").text(total_discount.toFixed({{gen_setting()->decimal}}));
    $('input[name="total_discount"]').val(total_discount.toFixed({{gen_setting()->decimal}}));

    //Sum of tax
    var total_tax = 0;
    $(".tax-value").each(function() {
        total_tax += parseFloat($(this).val());
    });
    $("#total-tax").text(total_tax.toFixed({{gen_setting()->decimal}}));
    $('input[name="total_tax"]').val(total_tax.toFixed({{gen_setting()->decimal}}));

    //Sum of subtotal
    var total = 0;
    $(".sub-total").each(function() {
        total += parseFloat($(this).text());
    });
    $("#total").text(total.toFixed({{gen_setting()->decimal}}));
    $('input[name="total_price"]').val(total.toFixed({{gen_setting()->decimal}}));

    calculateGrandTotal();
    payment_amount();
}

function calculateGrandTotal() {
    var item = $('table.order-list tbody tr:last').index();
    if (item == -1) {
        $('#order-discount-val').val(0);
    }
    var total_qty = parseFloat($('input[name="total_qty"]').val());
    var subtotal = parseFloat($('input[name="total_price"]').val());
    var order_tax = parseFloat($('select[name="order_tax_rate"]').val());
    var order_discount_type = $('select[name="order_discount_type"]').val();
    var order_discount_value = parseFloat($('input[name="order_discount_value"]').val());

    if (!order_discount_value)
        order_discount_value = {{number_format(0, gen_setting()->decimal, '.', '')}};

    if(order_discount_type == 'Flat') {
        if(!currencyChange) {
            var order_discount = parseFloat(order_discount_value);
        }
        else
            var order_discount = parseFloat(order_discount_value*currency['exchange_rate']);
    }
    else
        var order_discount = parseFloat(subtotal * (order_discount_value / 100));

    $("#discount").text(order_discount_value.toFixed({{gen_setting()->decimal}}));
    $('input[name="order_discount"]').val(order_discount);
    $('#order-discount-val').val(order_discount_value);
    $('input[name="order_discount_type"]').val(order_discount_type);
    if(!currencyChange)
        var shipping_cost = parseFloat($('input[name="shipping_cost"]').val());
    else
        var shipping_cost = parseFloat($('input[name="shipping_cost"]').val() * currency['exchange_rate']);
    if (shipping_cost.length < 1)
        shipping_cost = {{number_format(0, gen_setting()->decimal, '.', '')}};

    item = ++item + '(' + total_qty + ')';
    order_tax = (subtotal - order_discount) * (order_tax / 100);
    var grand_total = (subtotal + order_tax + shipping_cost) - order_discount;
    $('input[name="grand_total"]').val(grand_total.toFixed({{gen_setting()->decimal}}));

    if(!currencyChange)
        var coupon_discount = parseFloat($('input[name="coupon_discount"]').val());
    else
        var coupon_discount = parseFloat($('input[name="coupon_discount"]').val() * currency['exchange_rate']);
    if (!coupon_discount)
        coupon_discount = {{number_format(0, gen_setting()->decimal, '.', '')}};
    grand_total -= coupon_discount;

    $('#item').text(item);
    $('input[name="item"]').val($('table.order-list tbody tr:last').index() + 1);
    $('#subtotal').text(subtotal.toFixed({{gen_setting()->decimal}}));
    $('#order_tax').text(order_tax.toFixed({{gen_setting()->decimal}}));
    $('#tax').text(order_tax.toFixed({{gen_setting()->decimal}}));
    $('input[name="order_tax"]').val(order_tax.toFixed({{gen_setting()->decimal}}));
    $('#order_discount').text(order_discount.toFixed({{gen_setting()->decimal}}));
    $('#shipping_cost').text(shipping_cost.toFixed({{gen_setting()->decimal}}));
    $('input[name="shipping_cost"]').val(shipping_cost);
    $('#grand_total').text(grand_total.toFixed({{gen_setting()->decimal}}));
    $('input[name="grand_total"]').val(grand_total.toFixed({{gen_setting()->decimal}}));
    currencyChange = false;
    $(document).trigger('credit_limit_check_required');
}

function cancel(rownumber) {
    while(rownumber >= 0) {
        product_price.pop();
        wholesale_price.pop();
        product_discount.pop();
        tax_rate.pop();
        tax_name.pop();
        tax_method.pop();
        unit_name.pop();
        unit_operator.pop();
        unit_operation_value.pop();
        $('table.order-list tbody tr:last').remove();
        rownumber--;
    }
    $('input[name="shipping_cost"]').val('');
    $('input[name="order_discount_value"]').val('');
    $('select[name="order_tax_rate"]').val(0);
    calculateTotal();
}

$('select[name="order_discount_type"]').on("change", function() {
    calculateGrandTotal();
});

$('input[name="order_discount_value"]').on("blur", function() {
    calculateGrandTotal();
});

$('input[name="shipping_cost"]').on("blur", function() {
    calculateGrandTotal();
});

$('select[name="order_tax_rate"]').on("change", function() {
    calculateGrandTotal();
});

$('select[name="payment_status"]').on("change", function() {
    payment_amount()
});

function payment_amount(){
    var payment_status = $('#payment_status').val();
    if (payment_status == 3 || payment_status == 4) {
        $("#paid-amount").prop('disabled',false);
        $("#payment").show();
        $("#paying-amount").prop('required',true);
        $("#paid-amount").prop('required',true);
        if(payment_status == 4){
            $("#paid-amount").prop('disabled',true);
            $('input[name="paying_amount[]"]').val($('input[name="grand_total"]').val());
            $('input[name="paid_amount[]"]').val($('input[name="grand_total"]').val());
        }
        $("#account-list").attr("hidden", false);
        $(document).trigger('credit_limit_check_required');
    }
    else{
        $("#paying-amount").prop('required',false);
        $("#paid-amount").prop('required',false);
        $('input[name="paying_amount[]"]').val('');
        $('input[name="paid_amount[]"]').val('');
        $("#payment").hide();
        $("#account-list").attr("hidden", true);
        $(document).trigger('credit_limit_check_required');
    }
}

$('select[name="paid_by_id[]"]').on("change", function() {
    var id = $(this).val();
    $(".payment-form").off("submit");
    $('input[name="cheque_no"]').attr('required', false);
    $('select[name="gift_card_id"]').attr('required', false);

    // ✅ Pay Term show/hide
    if (id == 'credit') {
        var selected = $('#customer_id option:selected');
        $('#pay_term_no').val(selected.data('pay_term_no') || '');
        $('#pay_term_period').val(selected.data('pay_term_period') || 'days');
        $('#pay_term_div').show();
    } else {
        $('#pay_term_div').hide();
        $('#pay_term_no').val('');
        $('#pay_term_period').val('days');
    }

    if(id == 2) {
        $("#gift-card").show();
        $.ajax({
            url: 'get_gift_card',
            type: "GET",
            dataType: "json",
            success:function(data) {
                $('select[name="gift_card_id"]').empty();
                $.each(data, function(index) {
                    gift_card_amount[data[index]['id']] = data[index]['amount'];
                    gift_card_expense[data[index]['id']] = data[index]['expense'];
                    $('select[name="gift_card_id"]').append('<option value="'+ data[index]['id'] +'">'+ data[index]['card_no'] +'</option>');
                });
                $('.selectpicker').selectpicker('refresh');
            }
        });
        $(".card-element").hide();
        $("#cheque").hide();
        $('select[name="gift_card_id"]').attr('required', true);
    }
    else if (id == 3) {
        @if($lims_pos_setting_data && (strlen($lims_pos_setting_data->stripe_public_key)>0) && (strlen($lims_pos_setting_data->stripe_secret_key )>0))
            $.getScript( "../vendor/stripe/checkout.js" );
            $(".card-element").show();
            $(".card-errors").show();
        @endif
        $("#gift-card").hide();
        $("#cheque").hide();
    }
    else if (id == 4) {
        $("#cheque").show();
        $("#gift-card").hide();
        $(".card-element").hide();
        $('input[name="cheque_no"]').attr('required', true);
    }
    else {
        $("#gift-card").hide();
        $(".card-element").hide();
        $("#cheque").hide();
        if (id == 6) {
            if($('input[name="paid_amount[]"]').val() > deposit[$('#customer_id').val()]){
                alert('Amount exceeds customer deposit! Customer deposit : '+ deposit[$('#customer_id').val()]);
            }
        }
        else if (id == 7) {
            var pointAlreadyExists = false;
            var $currentSelect = $(this);
            $('select[name="paid_by_id[]"]').each(function() {
                if (!$(this).is($currentSelect) && $(this).val() == '7') {
                    pointAlreadyExists = true;
                    return false;
                }
            });

            if (pointAlreadyExists) {
                alert('Points Payment has already been added. Multiple points payment is not allowed.');
                $(this).val('1').selectpicker('refresh');
                return;
            }

            let isValid = pointCalculation();
            if (!isValid) {
                $(this).val('1').selectpicker('refresh');
                return;
            }
        }
    }
});

function pointCalculation() {
    let paid_amount = parseFloat($('input[name="paid_amount[]"]').val()) || 0;
    let customerPoints = points[$('#customer_id').val()] || 0;
    
    let perPoint = reward_point_setting['redeem_amount_per_unit_rp'] || 1;
    let minPoints = reward_point_setting['min_redeem_point'] || 0;
    let maxPoints = reward_point_setting['max_redeem_point'] || 0;
    let minOrderTotal = reward_point_setting['min_order_total_for_redeem'] || 0;
    let total_bill = parseFloat($('input[name="grand_total"]').val()) || 0;

    let required_point = Math.ceil(paid_amount / perPoint);

    if (minPoints > 0 && required_point < minPoints) {
        required_point = minPoints;
    }

    if (maxPoints > 0 && required_point > maxPoints) {
        required_point = maxPoints;
    }

    if (paid_amount <= 0) {
        alert('⚠️ Paid amount is required.');
        return false;
    }
    
    if (minOrderTotal > 0 && total_bill < minOrderTotal) {
        alert('⚠️ Order total must be at least ' + minOrderTotal + ' to redeem points.');
        return false;
    }

    if (required_point > customerPoints) {
        alert(
            '⚠️ Not enough points\n' +
            'Required: ' + required_point +
            '\nAvailable: ' + customerPoints
        );
        return false;
    }

    $("input[name='redeem_point']").val(required_point);
    return true;
}

$('select[name="gift_card_id"]').on("change", function() {
    var balance = gift_card_amount[$(this).val()] - gift_card_expense[$(this).val()];
    if($('input[name="paid_amount[]"]').val() > balance){
        alert('Amount exceeds card balance! Gift Card balance: '+ balance);
    }
});

$('input[name="paid_amount[]"]').on("input", function() {
    if( $(this).val() > parseFloat($('input[name="paying_amount[]"]').val()) ) {
        $('#paying-amount-error').addClass('d-none');
        $("#submit-button").prop('disabled', true).css('cursor', 'default');
        alert('Paying amount cannot be bigger than recieved amount');
        // $(this).val('');
    }
    else if( $(this).val() > parseFloat($('#grand_total').text()) ){
        $('#paying-amount-error').addClass('d-none');
        $("#submit-button").prop('disabled', true).css('cursor', 'default');
        alert('Paying amount cannot be bigger than grand total');
        // $(this).val('');
    } else if ($(this).val() <= 0) {
        $("#submit-button").prop('disabled', true).css('cursor', 'default');
        $('#paying-amount-error').removeClass('d-none');
    } else {
        $('#paying-amount-error').addClass('d-none');
        $("#submit-button").prop('disabled', false).css('cursor', 'pointer');
    }

    $("#change").text( parseFloat($("#paying-amount").val() - $(this).val()).toFixed({{gen_setting()->decimal}}) );
    var id = $('select[name="paid_by_id[]"]').val();
    if(id == 2){
        var balance = gift_card_amount[$("#gift_card_id").val()] - gift_card_expense[$("#gift_card_id").val()];
        if($(this).val() > balance)
            alert('Amount exceeds card balance! Gift Card balance: '+ balance);
    }
    else if(id == 6){
        if( $('input[name="paid_amount[]"]').val() > deposit[$('#customer_id').val()] )
            alert('Amount exceeds customer deposit! Customer deposit : '+ deposit[$('#customer_id').val()]);
    }
    else if (id == 7) {
        let paid_amount = parseFloat($(this).val()) || 0;
        let customerPoints = points[$('#customer_id').val()] || 0;
        let perPoint = reward_point_setting['redeem_amount_per_unit_rp'] || 1;
        let maxPoints = reward_point_setting['max_redeem_point'] || 0;
        
        let required_point = Math.ceil(paid_amount / perPoint);
        
        if (required_point > customerPoints) {
            alert('⚠️ Not enough points. You only have ' + customerPoints + ' points available.');
            $(this).val(0);
        } else if (maxPoints > 0 && required_point > maxPoints) {
            alert('⚠️ You can redeem a maximum of ' + maxPoints + ' points.');
            $(this).val(0);
        }
    }
});

$('input[name="paying_amount[]"]').on("input", function() {
    $("#change").text( parseFloat( $(this).val() - $("#paid-amount").val()).toFixed({{gen_setting()->decimal}}));
});

$(window).keydown(function(e){
    if (e.which == 13) {
        var $targ = $(e.target);
        if (!$targ.is("textarea") && !$targ.is(":button,:submit")) {
            var focusNext = false;
            $(this).find(":input:visible:not([disabled],[readonly]), a").each(function(){
                if (this === e.target) {
                    focusNext = true;
                }
                else if (focusNext){
                    $(this).focus();
                    return false;
                }
            });
            return false;
        }
    }
});

$("#submit-button").on("click", function (e) {
    e.preventDefault(); // prevent normal form submit
    const form = $(".payment-form");

    // 🧹 Remove any previously appended hidden inputs (avoid duplicates)
    form.find('input[name^="installment_plan["], input[name="enable_installment"]').remove();

    // ✅ Gather installment data if enabled
    if ($("#enable_installment").is(":checked")) {
        const installmentData = {
            enabled: true,
            name: $('input[name="installment_plan[name]"]').val(),
            price: $('input[name="installment_plan[price]"]').val(),
            additional_amount: $('input[name="installment_plan[additional_amount]"]').val(),
            total_amount: $('input[name="installment_plan[total_amount]"]').val(),
            down_payment: $('input[name="installment_plan[down_payment]"]').val(),
            months: $('input[name="installment_plan[months]"]').val(),
            reference_type: $('input[name="installment_plan[reference_type]"]').val()
        };

        // 🟢 Append all installment fields as hidden inputs
        $.each(installmentData, function (key, value) {
            if (value !== undefined && value !== null && value !== "") {
                $('<input>', {
                    type: "hidden",
                    name: "installment_plan[" + key + "]",
                    value: value
                }).appendTo(form);
            }
        });

        // Add enable flag
        $('<input>', {
            type: "hidden",
            name: "enable_installment",
            value: "1"
        }).appendTo(form);

    } else {
        $('<input>', {
            type: "hidden",
            name: "enable_installment",
            value: "0"
        }).appendTo(form);
    }

    // 🚀 Submit after all fields added
    form.trigger("submit");
});

let addSaleSubmitting = false;

$(document).on('submit', '.payment-form', function(e) {
    e.preventDefault();

    if (addSaleSubmitting) {
        return false;
    }

    if (window.SaleRequestFeedback) {
        window.SaleRequestFeedback.clear();
    }

    let hasError = false;

    $('table.order-list tbody tr').each(function () {
        let batchInput = $(this).find('.batch-no');
        let batchId = $(this).find('.product-batch-id').val();
        let productName = $(this).find('.product-name').text() || 'This product';

        if (batchInput.length && !batchInput.hasClass('d-none')) {
            if (!batchInput.val()) {
                alert(productName + ': Batch number is required!');
                hasError = true;
                return false;
            }

            if (!batchId) {
                alert(productName + ': Invalid batch selected!');
                hasError = true;
                return false;
            }
        }
    });

    if (hasError) {
        return false;
    }

    let customerType = $('#customer_id option:selected').data('type');
    let currentPaymentStatus = parseInt($('select[name="payment_status"]').val());
    let rownumber = $('table.order-list tbody tr:last').index();
    let hasEmptyQty = false;

    $('table.order-list tbody .qty').each(function () {
        if ($(this).val() === '') {
            hasEmptyQty = true;
            return false;
        }
    });

    if (hasEmptyQty) {
        alert('One of products has no quantity!');
        return false;
    }

    if (customerType === 'walkin' && currentPaymentStatus !== 4) {
        alert('Payment Status should be Paid for Walk in Customer!');
        return false;
    }

    if (rownumber < 0) {
        alert('Please insert product to order table!');
        return false;
    }

    if (parseFloat($('input[name="total_qty"]').val()) <= 0) {
        alert('Product quantity is 0');
        return false;
    }

    if (parseFloat($('#paying-amount').val()) < parseFloat($('#paid-amount').val())) {
        alert('Paying amount cannot be bigger than recieved amount');
        return false;
    }

    if (
        $('select[name="payment_status"]').val() == 3 &&
        parseFloat($('#paid-amount').val()) == parseFloat($('input[name="grand_total"]').val())
    ) {
        alert('Paying amount equals to grand total! Please change payment status.');
        return false;
    }

    if (!$('#biller_id').val()) {
        alert('Please select a biller');
        return false;
    }

    const form = $(this);

    const submitSale = function () {
        addSaleSubmitting = true;
        $('#submit-button').prop('disabled', true);
        $('#paid-amount').prop('disabled', false);
        $('.batch-no').prop('disabled', false);

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: form.serialize(),
            success: function(response) {

                if (response.payment_method === 'pesapal' && response.redirect_url) {
                    // Redirect to the URL returned for Pesapal payment method
                    location.href = response.redirect_url;
                }else if(response.payment_method === 'moneipoint'){
                }else if ($('select[name="sale_status"]').val() == 1 && response !== 'pesapal') {
                    let link = "{{ url('sales/gen_invoice') }}/" + response + "?is_print=true";
                    $.ajax({
                        url: link,
                        type: 'GET',
                        success: function(data) {
                            if (data.trim() === 'receipt_printer') {
                                alert("{{ __('db.The receipt has been successfully printed') }}");
                                location.href = "{{route('sales.index')}}";
                            } else if (data.trim() === 'invoice_settings_error') {
                                alert("{{ __('db.Please select either the 58mm or 80mm template as the default in Invoice Settings') }}");
                                location.href = "{{route('sales.index')}}";
                            } else {
                                location.href = link;
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("Error loading invoice:", error);
                        }
                    });
                }
                else if($('select[name="sale_status"]').val() != 1){
                    localStorage.clear();
                    location.href = "{{route('sales.index')}}";
                }
                else {
                    localStorage.clear();
                    location.href = response;
                }
            },
            error: function(xhr) {
                addSaleSubmitting = false;

                if (window.SaleRequestFeedback) {
                    window.SaleRequestFeedback.showXhrError(xhr, true);
                } else {
                    const response = xhr.responseJSON || {};
                    alert(response.message || response.error || @json(__('db.generic_error_please_try_again')));
                }

                $('#submit-button').prop('disabled', false);
            }
        });
    };

    if (window.SaleCreditLimit) {
        window.SaleCreditLimit.validate({ focusError: true }).done(function (allowed) {
            if (allowed) {
                submitSale();
            }
        });
    } else {
        submitSale();
    }

    return false;
});
</script>
<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
@endpush
