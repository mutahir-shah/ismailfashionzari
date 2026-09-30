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
</style>
@endpush

<x-error-message key="not_permitted" />
<x-error-message key="error" />

<?php $authUser = Auth::user()->role_id; ?>

@php
    $restaurantModuleEnabled = (bool) ($restaurantModuleEnabled ?? false);
    $selectedWarehouseId = $selectedWarehouseId ?? $lims_sale_data->warehouse_id;
    $selectedWarehouse = collect($lims_warehouse_list)->first(function ($warehouse) use ($selectedWarehouseId) {
        return (string) $warehouse->id === (string) $selectedWarehouseId;
    });
    $isRestaurantWarehouse = (bool) ($isRestaurantWarehouse ?? false);
    $selectedServiceId = (string) ($lims_sale_data->service_id ?? '1');
@endphp

<section id="pos-layout" class="forms hidden-print">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.Update Sale')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                        <form action="{{ route('sales.update', $lims_sale_data->id) }}" method="POST" enctype="multipart/form-data" id="payment-form">
                            @csrf
                            @method('PUT')

                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.date')}}</label>
                                            @can('change_sale_date')
                                                <input type="text" name="created_at" class="form-control date" value="{{date(gen_setting()->date_format, strtotime($lims_sale_data->created_at->toDateString()))}}" />
                                            @else
                                                <input type="text" name="created_at" class="form-control date" value="{{date(gen_setting()->date_format, strtotime($lims_sale_data->created_at->toDateString()))}}" readonly/>
                                            @endcan
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.reference')}}</label>
                                            <p><strong>{{ $lims_sale_data->reference_no }}</strong></p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.customer')}} *</label>
                                            <input type="hidden" name="customer_id_hidden" value="{{ $lims_sale_data->customer_id }}" />
                                            <select required name="customer_id" class="selectpicker form-control" data-live-search="true" id="customer_id" title="Select customer...">
                                                @foreach($lims_customer_list as $customer)
                                                <option value="{{$customer->id}}" data-credit-limit="{{ $customer->credit_limit }}">{{$customer->name . ' (' . $customer->phone_number . ')'}}</option>
                                                @endforeach
                                            </select>
                                            <x-validation-error fieldName="customer_id" />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Warehouse')}} *</label>
                                            <input type="hidden" name="warehouse_id_hidden" value="{{$lims_sale_data->warehouse_id}}" />
                                            <select required id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select warehouse...">
                                                @foreach($lims_warehouse_list as $warehouse)
                                                <option value="{{$warehouse->id}}" data-pos-type="{{ $warehouse->pos_type ?? 'regular' }}">{{$warehouse->name}}</option>
                                                @endforeach
                                            </select>
                                            <x-validation-error fieldName="warehouse_id" />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Biller')}} *</label>
                                            <input type="hidden" name="biller_id_hidden" value="{{$lims_sale_data->biller_id}}" />
                                            <select required name="biller_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Biller...">
                                                @foreach($lims_biller_list as $biller)
                                                <option value="{{$biller->id}}">{{$biller->name . ' (' . $biller->company_name . ')'}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    @if($restaurantModuleEnabled)
                                    <div class="col-md-4 restaurant-sale-field" @if(!$isRestaurantWarehouse) style="display:none;" @endif>
                                        <div class="form-group">
                                            <label>{{__('db.Service')}}</label>
                                            <select id="service_id" name="service_id" class="selectpicker form-control" title="Select service..."
                                                @if($isRestaurantWarehouse) required @else disabled @endif>
                                                @foreach($service_list as $service)
                                                <option value="{{$service->id}}" @if((string) $service->id === $selectedServiceId) selected @endif>{{$service->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4 restaurant-sale-field" @if(!$isRestaurantWarehouse) style="display:none;" @endif>
                                        <div class="form-group">
                                            <label>{{__('db.table')}}</label>
                                            <select id="table_id" name="table_id" class="selectpicker form-control" data-live-search="true" title="Select table...">
                                                @foreach($lims_table_list as $table)
                                                <option value="{{$table->id}}" data-warehouse-id="{{$table->warehouse_id}}" @if((string) $table->id === (string) $lims_sale_data->table_id) selected @endif>
                                                    {{$table->name}} at {{$table->floor}} ({{$table->number_of_person}})
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4 restaurant-sale-field" @if(!$isRestaurantWarehouse) style="display:none;" @endif>
                                        <div class="form-group">
                                            <label>{{__('db.Waiter')}}</label>
                                            <select id="waiter_id" name="waiter_id" class="selectpicker form-control" title="Select waiter...">
                                                @foreach($waiter_list as $waiter)
                                                <option value="{{$waiter->id}}" @if((string) $waiter->id === (string) $lims_sale_data->waiter_id) selected @endif>{{$waiter->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    @endif
                                </div>
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
                                                        <th>{{__('db.name')}}</th>
                                                        <th>{{__('db.Quantity')}}</th>
                                                        <th>{{__('db.Net Unit Price')}}</th>
                                                        <th>{{__('db.Discount')}}</th>
                                                        <th>{{__('db.Tax')}}</th>
                                                        <th>{{__('db.Subtotal')}}</th>
                                                        <th><i class="ti ti-trash"></i></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $temp_unit_name = [];
                                                    $temp_unit_operator = [];
                                                    $temp_unit_operation_value = [];
                                                    ?>
                                                    @foreach($lims_product_sale_data as $product_sale)
                                                    <tr class="old">
                                                    <?php
                                                        $product_data = DB::table('products')->find($product_sale->product_id);
                                                        if($product_sale->variant_id){
                                                            $product_variant_data = \App\Models\ProductVariant::select('id', 'item_code')->FindExactProduct($product_data->id, $product_sale->variant_id)->first();
                                                            $product_variant_id = $product_variant_data->id ?? null;
                                                            $product_data->code = $product_variant_data->item_code ?? $product_data->code;
                                                            $variant_name = isset($variantMap) && $variantMap->has($product_sale->variant_id) ? $variantMap->get($product_sale->variant_id)->name : null;
                                                        }
                                                        else {
                                                            $product_variant_id = null;
                                                            $variant_name = null;
                                                        }
                                                        $product_price = $product_sale->net_unit_price;
                                                        if($product_data && $product_data->tax_method == 1){
                                                            $product_price = $product_sale->net_unit_price + ($product_sale->discount / $product_sale->qty);
                                                        }
                                                        elseif ($product_data && $product_data->tax_method == 2) {
                                                            $product_price =($product_sale->total / $product_sale->qty) + ($product_sale->discount / $product_sale->qty);
                                                        }

                                                        $tax = DB::table('taxes')->where('rate',$product_sale->tax_rate)->first();
                                                        $unit_name = array();
                                                        $unit_operator = array();
                                                        $unit_operation_value = array();
                                                        if($product_data->type == 'standard' || $product_data->type == 'combo') {
                                                            $units = DB::table('units')->where('base_unit', $product_data->unit_id)->orWhere('id', $product_data->unit_id)->get();

                                                            foreach($units as $unit) {
                                                                if($product_sale->sale_unit_id == $unit->id) {
                                                                    array_unshift($unit_name, $unit->unit_name);
                                                                    array_unshift($unit_operator, $unit->operator);
                                                                    array_unshift($unit_operation_value, $unit->operation_value);
                                                                }
                                                                else {
                                                                    $unit_name[]  = $unit->unit_name;
                                                                    $unit_operator[] = $unit->operator;
                                                                    $unit_operation_value[] = $unit->operation_value;
                                                                }
                                                            }
                                                            if($unit_operator[0] == '*'){
                                                                $product_price = $product_price / $unit_operation_value[0];
                                                            }
                                                            elseif($unit_operator[0] == '/'){
                                                                $product_price = $product_price * $unit_operation_value[0];
                                                            }
                                                        }
                                                        else {
                                                            $unit_name[] = 'n/a'. ',';
                                                            $unit_operator[] = 'n/a'. ',';
                                                            $unit_operation_value[] = 'n/a'. ',';
                                                        }
                                                        $temp_unit_name = $unit_name = implode(",",$unit_name) . ',';

                                                        $temp_unit_operator = $unit_operator = implode(",",$unit_operator) .',';

                                                        $temp_unit_operation_value = $unit_operation_value =  implode(",",$unit_operation_value) . ',';

                                                        $product_batch_data = \App\Models\ProductBatch::select('batch_no', 'expired_date')->find($product_sale->product_batch_id);
                                                    ?>
                                                        <td><strong class="edit-product btn btn-link pl-0 pr-0" data-toggle="modal" data-target="#editModal">{{$product_data->name}} <i class="ti ti-edit"></i></strong>
                                                        @if(!empty($variant_name))
                                                            <br><span class="product-variant font-weight-bold" style="font-size: 0.85em; color: #555;">{{ __('db.Variant') ?? 'Variant' }}: {{ $variant_name }}</span>
                                                        @endif
                                                        <br>
                                                        <span>{{$product_data->code}}</span>

                                                        @if(auth()->user()->role_id == 1 || auth()->user()->role_id == 2)
                                                        @php
                                                            if ($product_data->type == 'combo') {
                                                                $product_list = explode(",", $product_data->product_list); // child products
                                                                $qty_list = explode(",", $product_data->qty_list); // required qty for combo
                                                                $comboQtys = [];
                                                                foreach ($product_list as $index => $child_id) {
                                                                    $requiredQty = $qty_list[$index];
                                                                    $childStock = \App\Models\Product_Warehouse::where('product_id', $child_id)
                                                                        ->where('warehouse_id', $lims_sale_data->warehouse_id)
                                                                        ->value('qty') ?? 0;
                                                                    if($requiredQty > 0){
                                                                        $comboQtys[] = intdiv($childStock, $requiredQty);
                                                                    } else {
                                                                        $comboQtys[] = 0;
                                                                    }
                                                                }
                                                                $product_data->qty = min($comboQtys);
                                                            }
                                                        @endphp

                                                         | {{ __('db.In Stock') }} {{$product_data->qty}} <input type="hidden" class="product-type" value="{{$product_data->type}}" />
                                                        @endif

                                                        <br>
                                                        @if($product_batch_data)
                                                            <br>
                                                            <input type="hidden" class="product-batch-id" name="product_batch_id[]" value="{{$product_sale->product_batch_id}}">
                                                            <input type="text" class="form-control batch-no" name="batch_no[]" value="{{$product_batch_data->batch_no}}" required/>
                                                        @endif
                                                        @if($restaurantModuleEnabled)
                                                        @php
                                                            $toppings = json_decode($product_sale->topping_id, true);
                                                            $toppingTotal = collect($toppings)->sum('price');
                                                        @endphp

                                                        @if(!empty($toppings))
                                                            <span class="restaurant-topping-display" @if(!$isRestaurantWarehouse) style="display:none;" @endif>
                                                                Includes: {{ collect($toppings)->pluck('name')->implode(', ') }}
                                                            </span>
                                                        @endif
                                                        @endif
                                                        </td>
                                                        <td>
                                                            <div class="input-group"><span class="input-group-btn">
                                                            @if($product_data->is_imei != 1)
                                                            <button type="button" class="btn btn-default minus mr-1" style="padding:5px 8px"><i class="ti ti-minus"></i></button></span>
                                                            @endif

                                                            <input type="text" class="form-control qty numkey input-number" name="qty[]" value="{{$product_sale->qty}}" style="font-size:13px;max-width:50px;padding: 0 0;text-align:center" step="any" min="0.000001" max="{{($product_sale->qty+$product_data->qty)}}" required/><span class="input-group-btn">

                                                            @if($product_data->is_imei != 1)
                                                            <button type="button" class="btn btn-default plus ml-1" style="padding:5px 8px"><i class="ti ti-plus"></i></button>
                                                            @endif
                                                            </span></div>
                                                        </td>

                                                        <td class="net_unit_price">{{ number_format((float)$product_sale->net_unit_price, gen_setting()->decimal, '.', '')}}</td>
                                                        <td class="discount">{{ number_format((float)$product_sale->discount, gen_setting()->decimal, '.', '')}}</td>
                                                        <td class="tax">{{ number_format((float)$product_sale->tax, gen_setting()->decimal, '.', '')}}</td>
                                                        <td class="sub-total">{{ number_format((float)$product_sale->total, gen_setting()->decimal, '.', '')}}</td>
                                                        <td><button type="button" class="ibtnDel btn btn-sm btn-danger"><i class="ti ti-trash"></i></button></td>
                                                        <input type="hidden" class="product-code" name="product_code[]" value="{{$product_data->code}}"/>
                                                        <input type="hidden" class="product-id" name="product_id[]" value="{{$product_data->id}}"/>
                                                        <input type="hidden" class="product_type" name="product_type[]" value="{{$product_data->type}}"/>
                                                        <input type="hidden" name="product_variant_id[]" value="{{$product_variant_id}}"/>
                                                        <input type="hidden" class="product-price" name="product_price[]" value="{{$product_price}}"/>
                                                        <input type="hidden" class="sale-unit" name="sale_unit[]" value="{{$unit_name}}"/>
                                                        <input type="hidden" class="sale-unit-operator" value="{{$unit_operator}}"/>
                                                        <input type="hidden" class="sale-unit-operation-value" value="{{$unit_operation_value}}"/>
                                                        <input type="hidden" class="net_unit_price" name="net_unit_price[]" value="{{$product_sale->net_unit_price}}" />
                                                        <input type="hidden" class="discount-value" name="discount[]" value="{{$product_sale->discount}}" />
                                                        <input type="hidden" class="tax-rate" name="tax_rate[]" value="{{$product_sale->tax_rate}}"/>
                                                        @if($tax)
                                                        <input type="hidden" class="tax-name" value="{{$tax->name}}" />
                                                        @else
                                                        <input type="hidden" class="tax-name" value="No Tax" />
                                                        @endif
                                                        <input type="hidden" class="tax-method" value="{{$product_data->tax_method}}"/>
                                                        <input type="hidden" class="tax-value" name="tax[]" value="{{$product_sale->tax}}" />
                                                        <input type="hidden" class="subtotal-value" name="subtotal[]" value="{{$product_sale->total}}" />
                                                        <input type="hidden" class="imei-number" name="imei_number[]"  value="{{$product_sale->imei_number}}" />
                                                        <input type="hidden" class="is-imei"  value="{{$product_data->is_imei}}" />

                                                        @if($restaurantModuleEnabled)
                                                        <input type="hidden" class="topping_product" name="topping_product[]" value="{{$product_sale->topping_id}}"
                                                            @if(!$isRestaurantWarehouse) disabled @endif />
                                                        @endif
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="tfoot active">
                                                    <th>{{__('db.Total')}}</th>
                                                    <th id="total-qty">{{$lims_sale_data->total_qty}}</th>
                                                    <th></th>
                                                    <th id="total-discount">{{ number_format((float)$lims_sale_data->total_discount, gen_setting()->decimal, '.', '')}}</th>
                                                    <th id="total-tax">{{ number_format((float)$lims_sale_data->total_tax, gen_setting()->decimal, '.', '')}}</th>
                                                    <th id="total">{{ number_format((float)$lims_sale_data->total_price, gen_setting()->decimal, '.', '')}}</th>
                                                    <th><i class="ti ti-trash"></i></th>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_qty" value="{{$lims_sale_data->total_qty}}" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_discount" value="{{$lims_sale_data->total_discount}}" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_tax" value="{{$lims_sale_data->total_tax}}" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_price" value="{{$lims_sale_data->total_price}}" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="item" value="{{$lims_sale_data->item}}" />
                                            <input type="hidden" name="order_tax" value="{{$lims_sale_data->order_tax}}"/>
                                        </div>
                                        <x-validation-error fieldName="item" />
                                    </div>
                                    <div class="col-md-2">
                                        @if($lims_sale_data->coupon_id)
                                            @php
                                                $coupon_data = DB::table('coupons')->find($lims_sale_data->coupon_id);
                                            @endphp
                                            <input type="hidden" name="coupon_active" value="1" />
                                            <input type="hidden" name="coupon_type" value="{{$coupon_data->type}}" />
                                            <input type="hidden" name="coupon_amount" value="{{$coupon_data->amount}}" />
                                            <input type="hidden" name="coupon_minimum_amount" value="{{$coupon_data->minimum_amount}}" />
                                            <input type="hidden" name="coupon_discount" value="{{$lims_sale_data->coupon_discount}}">

                                        @else
                                            <input type="hidden" name="coupon_active" value="0" />
                                        @endif
                                        <div class="form-group">
                                            <input type="hidden" name="grand_total" value="{{$lims_sale_data->grand_total}}" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <input type="hidden" name="order_tax_rate_hidden" value="{{$lims_sale_data->order_tax_rate}}">
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
                                            <select class="form-control" name="order_discount_type">
                                                @if($lims_sale_data->order_discount_type == 'Percentage')
                                                <option value="Percentage">Percentage</option>
                                                <option value="Flat">Flat</option>
                                                @else
                                                <option value="Flat">Flat</option>
                                                <option value="Percentage">Percentage</option>
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>
                                                {{__('db.Order Discount Value')}}
                                            </label>
                                            <input type="number" name="order_discount_value" class="form-control" value="@if($lims_sale_data->order_discount_value){{$lims_sale_data->order_discount_value}}@else{{$lims_sale_data->order_discount}}@endif" step="any" />
                                            <input type="hidden" name="order_discount" value="{{$lims_sale_data->order_discount}}" />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>
                                                {{__('db.Shipping Cost')}}
                                            </label>
                                            <input type="number" name="shipping_cost" class="form-control" value="{{$lims_sale_data->shipping_cost}}" step="any" />
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
                                        <?php $field_name = str_replace(' ', '_', strtolower($field->name)); ?>
                                        @if(!$field->is_admin || \Auth::user()->role_id == 1)
                                            <div class="{{'col-md-'.$field->grid_value}}">
                                                <div class="form-group">
                                                    <label>{{$field->name}}</label>
                                                    @if($field->type == 'text')
                                                        <input type="text" name="{{$field_name}}" value="{{$lims_sale_data->$field_name}}" class="form-control" @if($field->is_required){{'required'}}@endif>
                                                    @elseif($field->type == 'number')
                                                        <input type="number" name="{{$field_name}}" value="{{$lims_sale_data->$field_name}}" class="form-control" @if($field->is_required){{'required'}}@endif>
                                                    @elseif($field->type == 'textarea')
                                                        <textarea rows="5" name="{{$field_name}}" value="{{$lims_sale_data->$field_name}}" class="form-control" @if($field->is_required){{'required'}}@endif></textarea>
                                                    @elseif($field->type == 'checkbox')
                                                        <br>
                                                        <?php
                                                        $option_values = explode(",", $field->option_value);
                                                        $field_values =  explode(",", $lims_sale_data->$field_name);
                                                        ?>
                                                        @foreach($option_values as $value)
                                                            <label>
                                                                <input type="checkbox" name="{{$field_name}}[]" value="{{$value}}" @if(in_array($value, $field_values)) checked @endif @if($field->is_required){{'required'}}@endif> {{$value}}
                                                            </label>
                                                            &nbsp;
                                                        @endforeach
                                                    @elseif($field->type == 'radio_button')
                                                        <br>
                                                        <?php
                                                        $option_values = explode(",", $field->option_value);
                                                        ?>
                                                        @foreach($option_values as $value)
                                                            <label class="radio-inline">
                                                                <input type="radio" name="{{$field_name}}" value="{{$value}}" @if($value == $lims_sale_data->$field_name){{'checked'}}@endif @if($field->is_required){{'required'}}@endif> {{$value}}
                                                            </label>
                                                            &nbsp;
                                                        @endforeach
                                                    @elseif($field->type == 'select')
                                                        <?php $option_values = explode(",", $field->option_value); ?>
                                                        <select class="form-control" name="{{$field_name}}" @if($field->is_required){{'required'}}@endif>
                                                            @foreach($option_values as $value)
                                                                <option value="{{$value}}" @if($value == $lims_sale_data->$field_name){{'selected'}}@endif>{{$value}}</option>
                                                            @endforeach
                                                        </select>
                                                    @elseif($field->type == 'multi_select')
                                                        <?php
                                                        $option_values = explode(",", $field->option_value);
                                                        $field_values =  explode(",", $lims_sale_data->$field_name);
                                                        ?>
                                                        <select class="form-control" name="{{$field_name}}[]" @if($field->is_required){{'required'}}@endif multiple>
                                                            @foreach($option_values as $value)
                                                                <option value="{{$value}}" @if(in_array($value, $field_values)) selected @endif>{{$value}}</option>
                                                            @endforeach
                                                        </select>
                                                    @elseif($field->type == 'date_picker')
                                                        <input type="text" name="{{$field_name}}" value="{{$lims_sale_data->$field_name}}" class="form-control date" @if($field->is_required){{'required'}}@endif>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Sale Status')}} *</label>
                                            <input type="hidden" name="sale_status_hidden" value="{{$lims_sale_data->sale_status}}" />
                                            <select name="sale_status" class="form-control">
                                                <option value="1">{{__('db.Completed')}}</option>
                                                <option value="2">{{__('db.Pending')}}</option>
                                                @if($restaurantModuleEnabled)
                                                <option value="5" class="restaurant-sale-status-option" @if(!$isRestaurantWarehouse) disabled hidden @endif>{{__('db.Processing')}}</option>
                                                @endif
                                            </select>
                                            <x-validation-error fieldName="sale_status" />
                                        </div>
                                    </div>
                                    @if($lims_sale_data->coupon_id)
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>
                                                <strong>{{__('db.Coupon Discount')}}</strong>
                                            </label>
                                            <p class="mt-2 pl-2"><strong id="coupon-text">{{ number_format((float)$lims_sale_data->coupon_discount, gen_setting()->decimal, '.', '')}}</strong></p>
                                        </div>
                                    </div>
                                    @endif
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{__('db.Sale Note')}}</label>
                                            <textarea rows="5" class="form-control" name="sale_note" >{{ $lims_sale_data->sale_note }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{__('db.Staff Note')}}</label>
                                            <textarea rows="5" class="form-control" name="staff_note">{{ $lims_sale_data->staff_note }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <input type="hidden" name="payment_status" value="{{$lims_sale_data->payment_status}}" />
                                            <input type="hidden" name="paid_amount" value="{{$lims_sale_data->paid_amount}}" />
                                        </div>
                                        <x-validation-error fieldName="payment_status" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <input type="hidden" name="draft" value="0" />
                                    @include('backend.sale.credit_limit_checker')
                                    <button id="submit-button" type="submit" class="btn btn-primary sale-submit-btn">{{__('db.submit')}}</button>
                                </div>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <table class="table table-bordered table-condensed totals">
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
                                <input type="number" name="edit_unit_price" class="form-control numkey" step="any">
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
                        </div>
                        <button type="button" name="update_btn" class="btn btn-primary">{{__('db.update')}}</button>
                    </form>
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

    const doneTypingInterval = 300;
    const $input = $('#product-search-input');
    const $results = $('#product-results-container');
    const $noResults = $('#no-results-message');

    function clearResults() {
        $results.empty().css('padding', '0');
        $noResults.hide();
    }

    $(document).ready(function() {

        calculateTotal();

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
        @if(isset($numberOfInvoice))
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

        var posType = $('#warehouse_id').find(':selected').data('pos-type');
        return String(posType || '').toLowerCase() === 'restaurant';
    }

    function filterEditRestaurantTablesByWarehouse() {
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

    function syncEditRestaurantServiceRequirements() {
        var isRestaurant = isSelectedRestaurantWarehouse();
        var isDineIn = isRestaurant && String($('#service_id').val() || '') === '1';

        $('#table_id, #waiter_id')
            .prop('disabled', !isDineIn)
            .prop('required', isDineIn);

        if (!isDineIn) {
            $('#table_id, #waiter_id').val('');
        }
    }

    function syncEditRestaurantWarehouseUi() {
        var isRestaurant = isSelectedRestaurantWarehouse();
        var $processingOption = $('select[name="sale_status"] option[value="5"]');

        $('.restaurant-sale-field').toggle(isRestaurant);
        $('#service_id')
            .prop('disabled', !isRestaurant)
            .prop('required', isRestaurant);

        if (isRestaurant) {
            filterEditRestaurantTablesByWarehouse();
            syncEditRestaurantServiceRequirements();
        } else {
            $('#table_id, #waiter_id').prop('disabled', true).prop('required', false);
        }

        $processingOption
            .prop('disabled', !isRestaurant)
            .prop('hidden', !isRestaurant);

        if (!isRestaurant && String($('select[name="sale_status"]').val() || '') === '5') {
            $('select[name="sale_status"]').val('1');
        }

        $('.restaurant-topping-display').toggle(isRestaurant);
        $('.topping_product').prop('disabled', !isRestaurant);
        $('.selectpicker').selectpicker('refresh');
    }

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

    $('select[name="customer_id"]').val($('input[name="customer_id_hidden"]').val());
    $('select[name="warehouse_id"]').val($('input[name="warehouse_id_hidden"]').val());
    $('select[name="biller_id"]').val($('input[name="biller_id_hidden"]').val());
    $('select[name="sale_status"]').val($('input[name="sale_status_hidden"]').val());
    $('select[name="order_tax_rate"]').val($('input[name="order_tax_rate_hidden"]').val());
    $('.selectpicker').selectpicker('refresh');
    syncEditRestaurantWarehouseUi();

    $('#warehouse_id').on('change', function () {
        syncEditRestaurantWarehouseUi();
    });

    $(document).on('change', '#service_id', function () {
        syncEditRestaurantServiceRequirements();
        $('.selectpicker').selectpicker('refresh');
    });

$(window).on('load', async function () {

    var customer_id = $('#customer_id').val();
    setCustomerGroupRate(customer_id);
});

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

var exist_type = [];
var exist_code = [];
var exist_qty = [];
var rowindex;
var customer_group_rate;
var row_product_price;
var pos;
var role_id = <?php echo json_encode(Auth::user()->role_id)?>;

var warehouse_id = $('#warehouse_id').val();

var rownumber = $('table.order-list tbody tr:last').index();

for(rowindex  =0; rowindex <= rownumber; rowindex++){
    product_price.push(parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-price').val()));
    exist_code.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)').text());
    exist_type.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-type').val());
    var total_discount = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.discount').text());
    var quantity = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val());
    exist_qty.push(quantity);
    product_discount.push((total_discount / quantity).toFixed({{gen_setting()->decimal}}));
    tax_rate.push(parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-rate').val()));
    tax_name.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-name').val());
    tax_method.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-method').val());
    temp_unit_name = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit').val().split(',');
    unit_name.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit').val());
    unit_operator.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit-operator').val());
    unit_operation_value.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit-operation-value').val());
    if( !$('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val().includes(null) )
        is_imei.push(1);
    else
        is_imei.push(0);
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit').val(temp_unit_name[0]);
}

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
    else if(!isNaN(max_qty) && parseFloat(qty) >= parseFloat(max_qty) && without_stock == 'no') {
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

$("select[name=price_option]").on("change", function () {
    $("#editModal input[name=edit_unit_price]").val($(this).val());
});

$("#myTable").on("change", ".batch-no", function () {
    rowindex = $(this).closest('tr').index();
    var product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-id').val();
    var warehouse_id = $('#warehouse_id').val();
    $.get('{{url("/check-batch-availability")}}/' + product_id + '/' + $(this).val() + '/' + warehouse_id, function(data) {
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

    // Restaurant-only UI is governed by the selected warehouse's canonical POS type.
    // Edit Sale has no table/waiter/service controls in this Blade, so do not read
    // non-existent restaurant fields merely because the Restaurant module is installed.

    var data = $(this).data();
    productSearch(data);
});

async function productSearch(data) {
    if(data.embedded == 1) {
        alert('{{ __("db.product_added_using_scale_weight")}}');
        return;
    }
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

                if(isSelectedRestaurantWarehouse() && data.extras) {
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
    cols += '<input type="text" name="qty[]" class="form-control qty numkey input-number" style="font-size:13px;max-width:50px;padding: 0 0;text-align:center" step="any" min="0.000001" value="'+data.qty+'" max="'+data.warehouse_qty+'" required><span class="input-group-btn">';

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
    if(isSelectedRestaurantWarehouse() && data.extras){
        cols += '<input type="hidden" class="topping_product" name="topping_product[]" value="" />';
        cols += '<input type="hidden" class="topping-price" name="topping-price" value="" />';
    }

    newRow.append(cols);

    $("table.order-list tbody").prepend(newRow);

    rowindex = newRow.index();

    // ── Price Arrays ──────────────────────────────────────────────────────────
    // NOTE: edit.blade.php does not apply currency exchange rate on new adds
    // (existing rows loaded from DB already have their currency baked in).
    const _cgRate    = customer_group_rate;
    const _retail    = parseFloat(data.price) + parseFloat(data.price * _cgRate);
    const _wholesale = data.wholesale_price
        ? parseFloat(data.wholesale_price) + parseFloat(data.wholesale_price * _cgRate)
        : 0;

    product_price.splice(rowindex, 0, _retail);
    wholesale_price.splice(rowindex, 0, _wholesale || '{{number_format(0, gen_setting()->decimal, '.', '')}}');
    cost.splice(rowindex, 0, parseFloat(data.cost));

    // ── Discount from server response (no second AJAX needed) ─────────────────
    const _discountPerUnit = parseFloat(data.discount || 0);
    product_discount.splice(rowindex, 0, _discountPerUnit);

    tax_rate.splice(rowindex, 0, parseFloat(data.tax_rate));
    tax_name.splice(rowindex, 0, data.tax_name);
    tax_method.splice(rowindex, 0, data.tax_method);
    unit_name.splice(rowindex, 0, data.unit_name);
    unit_operator.splice(rowindex, 0, data.unit_operator);
    unit_operation_value.splice(rowindex, 0, data.unit_operation_value);
    is_imei.splice(rowindex, 0, data.is_imei);
    is_variant.splice(rowindex, 0, data.is_variant);

    // Cache row reference once
    const $newRow = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')');
    $newRow.find('.product_price').val(product_price[rowindex]);

    // ── Single synchronous calculation — no AJAX, no redundant DOM scans ──
    checkQuantity(data.qty, true);

    if(data.wholesale_price) {
        populatePriceOption();
        $newRow.find('.edit-product').click();
    }

    if (isSelectedRestaurantWarehouse() && data.extras && Array.isArray(data.extras) && data.extras.length > 0) {
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

                let toppingNames = toppings.map(t => t.name).join(", ");
                let totalToppingPrice = toppings.reduce((sum, t) => sum + parseFloat(t.price), 0);

                newRow.find('.product-title').append(`<br><small>Includes: ${toppingNames}</small>`);
                newRow.find('.topping_product').val(matchedProduct.topping_id);
                newRow.find('.topping-price').val(totalToppingPrice.toFixed({{gen_setting()->decimal}}));

                const currentPrice = parseFloat(newRow.find('.product-price').text()) || 0;
                const newPrice = currentPrice + totalToppingPrice;
                newPrice -= product_discount[rowindex];
                newRow.find('.product-price').text(newPrice.toFixed({{gen_setting()->decimal}}));
                newRow.find('.sub-total').text(newPrice.toFixed({{gen_setting()->decimal}}));

                // Remove used item from array
                productSale.splice(matchedIndex, 1);

                calculateTotal();
            }

        }else{
            openToppingsModal(data, [], rowindex);

            function openToppingsModal(data, selectedToppings = [], rowIndex = null) {
                let modalContent = '<form id="product-selection-form">';
                data.extras.forEach(product => {
                    const selected = selectedToppings.find(t => t.id == product.id);
                    const isChecked = selected ? 'checked' : '';
                    const qty = selected ? selected.qty : 1;

                    modalContent += `
                        <div class="form-check d-flex align-items-center mb-1">
                            <div>
                                <input class="form-check-input" type="checkbox" name="productOption" id="product_${product.id}" value="${product.id}" data-name="${product.name}" data-price="${product.price}" ${isChecked}>
                                <label class="form-check-label" for="product_${product.id}">
                                    ${product.name} (${product.code}) - ${product.price}
                                </label>
                            </div>
                            <input type="number" name="quantity_${product.id}" id="quantity_${product.id}" class="form-control form-control-sm" style="width: 80px;" min="1" value="${qty}">
                        </div>`;
                });
                modalContent += '</form>';

                const modalHTML = `
                    <div class="modal fade" id="productSelectionModal" tabindex="-1" role="dialog" aria-labelledby="productSelectionModalLabel" aria-hidden="true" data-rowindex="${rowIndex}">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="productSelectionModalLabel">{{__('db.select_additional_products')}}</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">${modalContent}</div>
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
            $("#confirmSelection").on('click', function () {
                let selectedToppings = [];
                let totalAdditionalPrice = 0;

                if (product_discount[rowindex] < 1) {
                    cur_product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product-id').val();
                    @if (isset($draft_product_discount))
                        if (product_discount[rowindex] < 1) {
                            draft_discounts = @json($draft_product_discount['discount']);
                            product_discount[rowindex] = draft_discounts[cur_product_id];
                        }
                    @endif
                }

                $("input[name='productOption']:checked").each(function () {
                    const qty = parseFloat($(`#quantity_${$(this).val()}`).val() || 1); // define qty first

                    const topping = {
                        id: $(this).val(),
                        name: $(this).data('name'),
                        qty: qty,
                        price: parseFloat($(this).data('price')) * qty
                    };

                    selectedToppings.push(topping);
                    totalAdditionalPrice += topping.price;
                });

                if (selectedToppings.length > 0) {
                    // Convert the selected toppings array to JSON
                    const selectedToppingsJson = JSON.stringify(selectedToppings);

                    // Append toppings to the main product row
                    const selectedProductNames = selectedToppings.map(t => `${t.name} (${t.qty})`).join(', ');

                    newRow.find('.product-title').append(`<br><small>Includes: ${selectedProductNames}</small>`);

                    newRow.find('.topping_product').val(selectedToppingsJson); // Store JSON in hidden field

                    // Update the total price
                    const currentPrice = parseFloat(newRow.find('.product-price').text()) || 0;
                    let newPrice = currentPrice + totalAdditionalPrice;
                    newPrice -= product_discount[rowindex];
                    newRow.find('.product-price').text(newPrice.toFixed({{gen_setting()->decimal}}));
                    newRow.find('.sub-total').text(newPrice.toFixed({{gen_setting()->decimal}}));
                    newRow.find('.topping-price').val(totalAdditionalPrice.toFixed({{gen_setting()->decimal}}));
                }

                $("#productSelectionModal").modal('hide');
                $(".modal-backdrop").remove();
                $("#productSelectionModal").remove();
                calculateTotal();
            });

            // Stop further processing until the modal is resolved
            return;
        }
    }
}

function populatePriceOption() {
    $('#editModal select[name=price_option]').empty();
    $('#editModal select[name=price_option]').append('<option value="'+ product_price[rowindex] +'">'+ product_price[rowindex] +'</option>');
    if(wholesale_price[rowindex] > 0)
        $('#editModal select[name=price_option]').append('<option value="'+ wholesale_price[rowindex] +'">'+ wholesale_price[rowindex] +'</option>');
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
    const rowclass     = $row.attr('class');

    $.ajax({
        type: 'GET',
        async: true, // ✅ Never block the UI thread
        url: '{{url("/")}}/sales/check-discount?qty=' + qty + '&customer_id=' + customer_id + '&product_id=' + product_id + '&warehouse_id=' + warehouse_id,
        success: function(data) {
            rowindex = capturedRowindex;

            if (String(product_price[rowindex]).length === 0) {
                product_price[rowindex] = $row.find('.product_price').val();
            }
            // Preserve the old-row price calculation (already stored with customer_group_rate)
            if (price > 0) {
                if (rowclass === 'old') {
                    product_price[rowindex] = parseFloat(price) + parseFloat(price * customer_group_rate);
                } else {
                    product_price[rowindex] = parseFloat(price * currency['exchange_rate'])
                        + parseFloat(price * currency['exchange_rate'] * customer_group_rate);
                }
            }

            // Update stored discount then recalculate
            product_discount[rowindex] = parseFloat(data[2] || 0);
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

let editSaleSubmitting = false;

$('#payment-form').on('submit', function(e) {
    e.preventDefault();

    if (editSaleSubmitting) {
        return false;
    }

    if (window.SaleRequestFeedback) {
        window.SaleRequestFeedback.clear();
    }

    let invalidQtyInput = null;
    $('table.order-list tbody .qty').each(function () {
        const rawVal = String($(this).val() === null ? '' : $(this).val()).trim();
        const numVal = Number(rawVal);
        $(this).removeClass('is-invalid');

        if (rawVal === '' || !Number.isFinite(numVal) || numVal <= 0) {
            invalidQtyInput = $(this);
            return false;
        }
    });

    if (invalidQtyInput) {
        invalidQtyInput.addClass('is-invalid').focus();
        alert('Quantity must be greater than zero!');
        return false;
    }

    const rownumber = $('table.order-list tbody tr:last').index();
    if (rownumber < 0) {
        alert('Please insert product to order table!');
        return false;
    }

    if (parseFloat($('input[name="total_qty"]').val()) <= 0) {
        alert('Product quantity is 0');
        return false;
    }

    const form = this;

    const submitSale = function () {
        editSaleSubmitting = true;
        $('#submit-button').prop('disabled', true);
        $('.batch-no').prop('disabled', false);

        // Native submit avoids re-entering this async validation handler.
        HTMLFormElement.prototype.submit.call(form);
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

$(document).on('input change', 'table.order-list .qty', function() {
    const rawVal = String($(this).val() === null ? '' : $(this).val()).trim();
    const numVal = Number(rawVal);
    if (rawVal !== '' && Number.isFinite(numVal) && numVal > 0) {
        $(this).removeClass('is-invalid');
    }
});

</script>
<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
@endpush
