@extends('backend.layout.main') @section('content')

@push('css')
    <style>
        .sale-section {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            padding: 20px 20px 10px 20px;
            margin-bottom: 20px;
            background: #fff;
        }
        body.dark-mode .sale-section {
            background: #283046;
            border-color: #3b4253;
            box-shadow: none;
        }
    </style>
@endpush

<x-success-message key="message" />
<x-error-message key="not_permitted" />
<x-validation-error fieldName="product_code" />
<x-validation-error fieldName="qty" />

<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card-header d-flex align-items-center">
                    <h4>{{__('db.Update Purchase')}}</h4>
                </div>
                <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                <form action="{{ route('purchases.update', $lims_purchase_data->id) }}" method="POST" enctype="multipart/form-data" id="purchase-form">
                    @csrf
                    @method('PUT')
                    <div class="sale-section">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.date')}}</label>
                                    <input type="text" name="created_at" class="form-control date" value="{{date(gen_setting()->date_format, strtotime($lims_purchase_data->created_at->toDateString()))}}" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Reference No')}}</label>
                                    <p><strong>{{ $lims_purchase_data->reference_no }}</strong> </p>
                                </div>
                                <x-validation-error fieldName="reference_no" />
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Warehouse')}} *</label>
                                    <input type="hidden" name="warehouse_id_hidden" value="{{$lims_purchase_data->warehouse_id}}" />
                                    <select required name="warehouse_id" class="selectpicker form-control" data-live-search="true" title="Select warehouse..." required>
                                        @foreach($lims_warehouse_list as $warehouse)
                                        <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                        @endforeach
                                    </select>
                                    <x-validation-error fieldName="warehouse_id" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Supplier')}}</label>
                                    <input type="hidden" name="supplier_id_hidden" value="{{ $lims_purchase_data->supplier_id }}" />
                                    <select name="supplier_id" class="selectpicker form-control" data-live-search="true" id="supplier-id" title="Select supplier...">
                                        @foreach($lims_supplier_list as $supplier)
                                        <option value="{{$supplier->id}}">{{$supplier->name .' ('. $supplier->company_name .')'}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Purchase Status')}}</label>
                                    <input type="hidden" name="status_hidden" value="{{$lims_purchase_data->status}}">
                                    <select name="status" class="form-control">
                                        <option value="1">{{__('db.Recieved')}}</option>
                                        <option value="2">{{__('db.Partial')}}</option>
                                        <option value="3">{{__('db.Pending')}}</option>
                                        <option value="4">{{__('db.Ordered')}}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Attach Document')}}</label> <i class="ti ti-info-circle" data-toggle="tooltip" title="Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported"></i>
                                    <input type="file" name="document" class="form-control" >
                                    @if($errors->has('extension'))
                                        <span>
                                            <strong>{{ $errors->first('extension') }}</strong>
                                        </span>
                                    @endif
                                    <x-validation-error fieldName="document" />
                                </div>
                            </div>
                            @if ($lims_purchase_data->currency_id && $lims_purchase_data->exchange_rate)
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>{{__('db.Currency')}} *</label>
                                        <select name="currency_id" id="currency-id" class="form-control selectpicker" data-toggle="tooltip" title="">
                                            @foreach($currency_list as $currency_data)
                                                @if ($currency_data->id == $lims_purchase_data->currency_id)
                                                    <option value="{{$currency_data->id}}" data-rate="{{$currency_data->exchange_rate}}" @if($currency_data->exchange_rate == 1){{'checked'}}@endif>{{$currency_data->code}}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <x-validation-error fieldName="currency_id" />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-0">
                                        <label>{{__('db.Exchange Rate')}} *</label>
                                    </div>
                                    <div class="form-group d-flex">
                                        <input class="form-control" type="text" id="exchange_rate" name="exchange_rate" value="{{$lims_purchase_data->exchange_rate}}" readonly>
                                        <div class="input-group-append">
                                            <span class="input-group-text" data-toggle="tooltip" title="" data-original-title="currency exchange rate">i</span>
                                        </div>
                                        <x-validation-error fieldName="exchange_rate" />
                                    </div>
                                </div>
                            @endif
                            @foreach($custom_fields as $field)
                                <?php $field_name = str_replace(' ', '_', strtolower($field->name)); ?>
                                @if(!$field->is_admin || \Auth::user()->role_id == 1)
                                    <div class="{{'col-md-'.$field->grid_value}}">
                                        <div class="form-group">
                                            <label>{{$field->name}}</label>
                                            @if($field->type == 'text')
                                                <input type="text" name="{{$field_name}}" value="{{$lims_purchase_data->$field_name}}" class="form-control" @if($field->is_required){{'required'}}@endif>
                                            @elseif($field->type == 'number')
                                                <input type="number" name="{{$field_name}}" value="{{$lims_purchase_data->$field_name}}" class="form-control" @if($field->is_required){{'required'}}@endif>
                                            @elseif($field->type == 'textarea')
                                                <textarea rows="5" name="{{$field_name}}" value="{{$lims_purchase_data->$field_name}}" class="form-control" @if($field->is_required){{'required'}}@endif></textarea>
                                            @elseif($field->type == 'checkbox')
                                                <br>
                                                <?php
                                                $option_values = explode(",", $field->option_value);
                                                $field_values =  explode(",", $lims_purchase_data->$field_name);
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
                                                        <input type="radio" name="{{$field_name}}" value="{{$value}}" @if($value == $lims_purchase_data->$field_name){{'checked'}}@endif @if($field->is_required){{'required'}}@endif> {{$value}}
                                                    </label>
                                                    &nbsp;
                                                @endforeach
                                            @elseif($field->type == 'select')
                                                <?php $option_values = explode(",", $field->option_value); ?>
                                                <select class="form-control" name="{{$field_name}}" @if($field->is_required){{'required'}}@endif>
                                                    @foreach($option_values as $value)
                                                        <option value="{{$value}}" @if($value == $lims_purchase_data->$field_name){{'selected'}}@endif>{{$value}}</option>
                                                    @endforeach
                                                </select>
                                            @elseif($field->type == 'multi_select')
                                                <?php
                                                $option_values = explode(",", $field->option_value);
                                                $field_values =  explode(",", $lims_purchase_data->$field_name);
                                                ?>
                                                <select class="form-control" name="{{$field_name}}[]" @if($field->is_required){{'required'}}@endif multiple>
                                                    @foreach($option_values as $value)
                                                        <option value="{{$value}}" @if(in_array($value, $field_values)) selected @endif>{{$value}}</option>
                                                    @endforeach
                                                </select>
                                            @elseif($field->type == 'date_picker')
                                                <input type="text" name="{{$field_name}}" value="{{$lims_purchase_data->$field_name}}" class="form-control date" @if($field->is_required){{'required'}}@endif>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="sale-section">
                        <div class="col-md-12 mt-3">
                            <label>{{__('db.Select Product')}}</label>
                            <div class="search-box input-group">
                                <button type="button" class="btn btn-secondary"><i class="ti ti-barcode"></i></button>
                                <input type="text" name="product_code_name" id="lims_productcodeSearch" placeholder="{{__('db.Please type product code and select')}}" class="form-control" />
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
                                                <th>{{__('db.Code')}}</th>
                                                <th>{{__('db.Quantity')}}</th>
                                                <th class="recieved-product-qty d-none">{{__('db.Recieved')}}</th>
                                                <th>{{__('db.Batch No')}}</th>
                                                <th>{{__('db.Expired Date')}}</th>
                                                <th>{{__('db.Net Unit Cost')}}</th>
                                                <th>{{__('db.Profit Margin')}}</th>
                                                <th>{{__('db.profit_margin_type')}}</th>
                                                <th>{{__('db.product_price')}}</th>
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
                                            @foreach($lims_product_purchase_data as $product_purchase)
                                            <tr>
                                            <?php
                                                $product_data = DB::table('products')->find($product_purchase->product_id);
                                                if($product_purchase->variant_id) {
                                                    $product_variant_data = \App\Models\ProductVariant::FindExactProduct($product_data->id, $product_purchase->variant_id)->select('item_code')->first();
                                                    if($product_variant_data)
                                                        $product_data->code = $product_variant_data->item_code;
                                                }

                                                $tax = DB::table('taxes')->where('rate', $product_purchase->tax_rate)->first();

                                                $units = DB::table('units')->where('base_unit', $product_data->unit_id)->orWhere('id', $product_data->unit_id)->get();

                                                $unit_name = array();
                                                $unit_operator = array();
                                                $unit_operation_value = array();

                                                foreach($units as $unit) {
                                                    if($product_purchase->purchase_unit_id == $unit->id) {
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
                                                if($product_data->tax_method == 1){
                                                    $product_cost = ($product_purchase->net_unit_cost + ($product_purchase->discount / $product_purchase->qty)) / $unit_operation_value[0];
                                                }
                                                else{
                                                    $product_cost = (($product_purchase->total + ($product_purchase->discount / $product_purchase->qty)) / $product_purchase->qty) / $unit_operation_value[0];
                                                }


                                                $temp_unit_name = $unit_name = implode(",",$unit_name) . ',';

                                                $temp_unit_operator = $unit_operator = implode(",",$unit_operator) .',';

                                                $temp_unit_operation_value = $unit_operation_value =  implode(",",$unit_operation_value) . ',';

                                                $product_batch_data = \App\Models\ProductBatch::select('batch_no', 'expired_date')->find($product_purchase->product_batch_id);
                                            ?>
                                                <td>{{$product_data->name}} <button type="button" class="edit-product btn btn-link" data-toggle="modal" data-target="#editModal"> <i class="ti ti-edit"></i></button> </td>
                                                <td>{{$product_data->code}}</td>
                                                <td><input type="text" class="form-control qty" name="qty[]" value="{{$product_purchase->qty}}" required step="0.01" /></td>
                                                <td class="recieved-product-qty d-none"><input type="number" class="form-control recieved" name="recieved[]" value="{{$product_purchase->recieved}}" step="any"/></td>
                                                @if($product_purchase->product_batch_id)
                                                <td>
                                                    <input type="hidden" name="product_batch_id[]" value="{{$product_purchase->product_batch_id}}">
                                                    <input type="text" class="form-control batch-no" name="batch_no[]" value="{{$product_batch_data->batch_no}}" required/>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control expired-date" name="expired_date[]" value="{{$product_batch_data->expired_date}}" required/>
                                                </td>
                                                @else
                                                <td>
                                                    <input type="hidden" name="product_batch_id[]">
                                                    <input type="text" class="form-control batch-no" name="batch_no[]" disabled />
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control expired-date" name="expired_date[]" disabled />
                                                </td>
                                                @endif
                                                <td class="net_unit_cost">{{ number_format((float)$product_purchase->net_unit_cost, gen_setting()->decimal, '.', '')}} </td>
                                                <td class="net_unit_margin">{{ number_format(
                                                    (float)($product_purchase->net_unit_margin > 0 
                                                        ? $product_purchase->net_unit_margin 
                                                        : $product_data->profit_margin),
                                                    gen_setting()->decimal, '.', ''
                                                ) }} </td>
                                                <td class="net_unit_margin_type">{{$product_purchase->net_unit_margin_type}}</td>
                                                <td class="net_unit_price">{{ number_format(
                                                    (float)($product_purchase->net_unit_price > 0 
                                                        ? $product_purchase->net_unit_price 
                                                        : $product_data->price),
                                                    gen_setting()->decimal, '.', ''
                                                ) }} </td>
                                                <td class="discount">{{ number_format((float)$product_purchase->discount, gen_setting()->decimal, '.', '')}}</td>
                                                <td class="tax">{{ number_format((float)$product_purchase->tax, gen_setting()->decimal, '.', '')}}</td>
                                                <td class="sub-total">{{ number_format((float)$product_purchase->total, gen_setting()->decimal, '.', '')}}</td>
                                                <td><button type="button" class="ibtnDel btn btn-md btn-danger"><i class="ti ti-trash"></i></button></td>
                                                <input type="hidden" class="product-id" name="product_id[]" value="{{$product_data->id}}"/>
                                                <input type="hidden" class="product-code" name="product_code[]" value="{{$product_data->code}}"/>
                                                <input type="hidden" class="product-cost" name="product_cost[]" value="{{ $product_cost}}"/>
                                                <input type="hidden" class="profit-margin" name="profit_margin[]" value="{{         $product_purchase->net_unit_margin > 0 
                                                        ? $product_purchase->net_unit_margin 
                                                        : $product_data->profit_margin }}"/>
                                                <input type="hidden"
                                                    class="profit-margin-type"
                                                    name="profit_margin_type[]"
                                                    value="{{ $product_purchase->net_unit_margin_type ?? 'percentage' }}">
                                                <input type="hidden" class="product-price" name="product_price[]" value="{{         $product_purchase->net_unit_price > 0 
                                                            ? $product_purchase->net_unit_price 
                                                            : $product_data->price }}"/>
                                                <input type="hidden" class="purchase-unit" name="purchase_unit[]" value="{{$unit_name}}"/>
                                                <input type="hidden" class="purchase-unit-operator" value="{{$unit_operator}}"/>
                                                <input type="hidden" class="purchase-unit-operation-value" value="{{$unit_operation_value}}"/>
                                                <input type="hidden" class="unit_cost" name="unit_cost[]" value="{{ $product_cost}}" />
                                                <input type="hidden" class="net_unit_cost" name="net_unit_cost[]" value="{{$product_purchase->net_unit_cost}}" />
                                                <input type="hidden" class="net_unit_margin" name="net_unit_margin[]" value="{{$product_purchase->net_unit_margin}}" />
                                                <input type="hidden" class="net_unit_margin_type" name="net_unit_margin_type[]" value="{{$product_purchase->net_unit_margin_type}}" />
                                                <input type="hidden" class="net_unit_price" name="net_unit_price[]" value="{{$product_purchase->net_unit_price}}" />
                                                <input type="hidden" class="discount-value" name="discount[]" value="{{$product_purchase->discount}}" />
                                                <input type="hidden" class="tax-rate" name="tax_rate[]" value="{{$product_purchase->tax_rate}}"/>
                                                @if($tax)
                                                <input type="hidden" class="tax-name" value="{{$tax->name}}" />
                                                @else
                                                <input type="hidden" class="tax-name" value="No Tax" />
                                                @endif
                                                <input type="hidden" class="tax-method" value="{{$product_data->tax_method}}"/>
                                                <input type="hidden" class="tax-value" name="tax[]" value="{{$product_purchase->tax}}" />
                                                <input type="hidden" class="subtotal-value" name="subtotal[]" value="{{$product_purchase->total}}" />
                                                <input type="hidden" class="is-imei" value="{{$product_data->is_imei}}" />
                                                <input type="hidden" class="imei-number" name="imei_number[]"  value="{{$product_purchase->imei_number}}" />
                                                <input type="hidden" class="original-cost"  value="{{$product_data->cost}}" />
                                                <input type="hidden" class="original-profit-margin"  value="{{$product_data->profit_margin}}" />
                                                <input type="hidden" class="original-profit-margin-type"  value="{{$product_data->profit_margin_type}}" />
                                                <input type="hidden" class="original-price"  value="{{$product_data->price}}" />
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="tfoot active">
                                            <th colspan="2">{{__('db.Total')}}</th>
                                            <th id="total-qty">{{$lims_purchase_data->total_qty}}</th>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                            <th class="recieved-product-qty d-none"></th>
                                            <th id="total-discount">{{ number_format((float)$lims_purchase_data->total_discount, gen_setting()->decimal, '.', '')}}</th>
                                            <th id="total-tax">{{ number_format((float)$lims_purchase_data->total_tax, gen_setting()->decimal, '.', '')}}</th>
                                            <th id="total">{{ number_format((float)$lims_purchase_data->total_cost, gen_setting()->decimal, '.', '')}}</th>
                                            <th><i class="ti ti-trash"></i></th>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <input type="hidden" name="total_qty" value="{{$lims_purchase_data->total_qty}}" />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <input type="hidden" name="total_discount" value="{{$lims_purchase_data->total_discount}}" />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <input type="hidden" name="total_tax" value="{{$lims_purchase_data->total_tax}}" />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <input type="hidden" name="total_cost" value="{{$lims_purchase_data->total_cost}}" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <input type="hidden" name="item" value="{{$lims_purchase_data->item}}" />
                                    <input type="hidden" name="order_tax" value="{{$lims_purchase_data->order_tax}}"/>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <input type="hidden" name="grand_total" value="{{$lims_purchase_data->grand_total}}" />
                                    <input type="hidden" name="paid_amount" value="{{$lims_purchase_data->paid_amount}}" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sale-section">
                        <div class="row mt-5">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Order Tax')}}</label>
                                    <input type="hidden" name="order_tax_rate_hidden" value="{{$lims_purchase_data->order_tax_rate}}">
                                    <select class="form-control" name="order_tax_rate">
                                        <option value="0">{{__('db.No Tax')}}</option>
                                        @foreach($lims_tax_list as $tax)
                                        <option value="{{$tax->rate}}">{{$tax->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Discount')}}</label>
                                    <input type="number" name="order_discount" class="form-control" value="{{$lims_purchase_data->order_discount}}" step="any" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Shipping Cost')}}</label>
                                    <input type="number" name="shipping_cost" class="form-control" value="{{$lims_purchase_data->shipping_cost}}" step="any" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="sale-section">  
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>{{__('db.note')}}</label>
                                    <textarea rows="5" class="form-control" name="note" >{{ $lims_purchase_data->note }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary" id="submit-button">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="sale-section">
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
                                <input type="number" name="edit_qty" class="form-control" min="1" step="0.01">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Unit Discount')}}</label>
                                <input type="number" name="edit_discount" class="form-control" step="any">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Unit Cost')}}</label>
                                <input type="number" name="edit_unit_cost" class="form-control" step="any">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{ __('db.profit_margin_type') }}</label>
                                <select name="edit_profit_margin_type" class="form-control">
                                    <option value="percentage">Percentage</option>
                                    <option value="flat">Flat</option>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Profit Margin')}}</label>
                                <input type="number" name="edit_profit_margin" class="form-control" step="0.01">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.product_price')}}</label>
                                <input type="number" name="edit_product_price" class="form-control" step="0.01">
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
                            <div class="col-md-4 form-group">
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


@endsection

@push('scripts')
<script type="text/javascript">

    $("ul#purchase").siblings('a').addClass("active");
    $("ul#purchase").addClass("show");

// array data depend on warehouse
var lims_product_array = [];
var product_code = [];
var product_name = [];
var product_qty = [];

// array data with selection
var product_cost = [];
var profit_margin = [];
var profit_margin_type = [];
var product_price = [];
var product_discount = [];
var tax_rate = [];
var tax_name = [];
var tax_method = [];
var unit_name = [];
var unit_operator = [];
var unit_operation_value = [];
var is_imei = [];

// temporary array
var temp_unit_name = [];
var temp_unit_operator = [];
var temp_unit_operation_value = [];

var rowindex;
var customer_group_rate;
var row_product_cost;
var row_profit_margin = 0;
var row_profit_margin_type = 'percentage';
var row_product_price = 0;
var currency = <?php echo json_encode($currency) ?>;
var exchangeRate = 1;
var currencyChange = false;

var rownumber = $('table.order-list tbody tr:last').index();
for(rowindex = 0; rowindex <= rownumber; rowindex++){
    product_cost.push(parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-cost').val()));
    profit_margin.push(parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.profit-margin').val()));
    profit_margin_type.push(
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.profit-margin-type').val() || 'percentage');
    product_price.push(parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-price').val()));
    // console.log(profit_margin, product_price);
    var total_discount = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.discount').text());
    var quantity = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val());
    product_discount.push((total_discount / quantity).toFixed({{gen_setting()->decimal}}));
    tax_rate.push(parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-rate').val()));
    tax_name.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-name').val());
    tax_method.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-method').val());
    temp_unit_name = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit').val().split(',');
    unit_name.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit').val());
    unit_operator.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit-operator').val());
    unit_operation_value.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit-operation-value').val());
    is_imei.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.is-imei').val());
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit').val(temp_unit_name[0]);
}

$('.selectpicker').selectpicker({
    style: 'btn-link',
});

$('.selectpicker').selectpicker('refresh');

$('#currency-id').change(function(){
    var rate = $(this).find(':selected').data('rate');
    var currency_id = $(this).val();
    $('#exchange_rate').val(rate);
    exchangeRate = rate;
    currencyChange = true;
    $("table.order-list tbody .qty").each(function(index) {
        rowindex = index;
        checkQuantity($(this).val(), true);
    });
});

$('[data-toggle="tooltip"]').tooltip();

//assigning value
$('select[name="supplier_id"]').val($('input[name="supplier_id_hidden"]').val());
$('select[name="warehouse_id"]').val($('input[name="warehouse_id_hidden"]').val());
$('select[name="status"]').val($('input[name="status_hidden"]').val());
$('select[name="order_tax_rate"]').val($('input[name="order_tax_rate_hidden"]').val());
$('.selectpicker').selectpicker('refresh');

$('#item').text($('input[name="item"]').val() + '(' + $('input[name="total_qty"]').val() + ')');
$('#subtotal').text(parseFloat($('input[name="total_cost"]').val()).toFixed({{gen_setting()->decimal}}));
$('#order_tax').text(parseFloat($('input[name="order_tax"]').val()).toFixed({{gen_setting()->decimal}}));
if($('select[name="status"]').val() == 2){
    $(".recieved-product-qty").removeClass("d-none");

}
if(!$('input[name="order_discount"]').val())
    $('input[name="order_discount"]').val('{{number_format(0, gen_setting()->decimal, '.', '')}}');
$('#order_discount').text(parseFloat($('input[name="order_discount"]').val()).toFixed({{gen_setting()->decimal}}));
if(!$('input[name="shipping_cost"]').val())
    $('input[name="shipping_cost"]').val('{{number_format(0, gen_setting()->decimal, '.', '')}}');
$('#shipping_cost').text(parseFloat($('input[name="shipping_cost"]').val()).toFixed({{gen_setting()->decimal}}));
$('#grand_total').text(parseFloat($('input[name="grand_total"]').val()).toFixed({{gen_setting()->decimal}}));

$('select[name="status"]').on('change', function() {
    if($('select[name="status"]').val() == 2){
        $(".recieved-product-qty").removeClass("d-none");
        $(".qty").each(function() {
            rowindex = $(this).closest('tr').index();
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.recieved').val($(this).val());
        });

    }
    else if(($('select[name="status"]').val() == 3) || ($('select[name="status"]').val() == 4)){
        $(".recieved-product-qty").addClass("d-none");
        $(".recieved").each(function() {
            $(this).val(0);
        });
    }
    else {
        $(".recieved-product-qty").addClass("d-none");
        $(".qty").each(function() {
            rowindex = $(this).closest('tr').index();
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.recieved').val($(this).val());
        });
    }
});


var lims_product_code = [
    @foreach($lims_product_list_without_variant as $product)
        <?php
            $productArray[] = htmlspecialchars($product->code) . '|' . preg_replace('/[\n\r]/', "<br>", htmlspecialchars($product->name));
        ?>
    @endforeach
    @foreach($lims_product_list_with_variant as $product)
        <?php
            $productArray[] = htmlspecialchars($product->item_code) . '|' . preg_replace('/[\n\r]/', "<br>", htmlspecialchars($product->name));
        ?>
    @endforeach
    <?php
        echo  '"'.implode('","', $productArray).'"';
    ?>
];

    var lims_productcodeSearch = $('#lims_productcodeSearch');

    lims_productcodeSearch.autocomplete({
    source: function(request, response) {
        var matcher = new RegExp(".?" + $.ui.autocomplete.escapeRegex(request.term), "i");
        response($.grep(lims_product_code, function(item) {
            return matcher.test(item);
        }));
    },
    response: function(event, ui) {
        if (ui.content.length == 1) {
            var data = ui.content[0].value;
            $(this).autocomplete( "close" );
            productSearch(data);
        };
    },
    select: function(event, ui) {
        var data = ui.item.value;
        productSearch(data);
    }
});

$('body').on('focus',".expired-date", function() {
    $(this).datepicker({
        format: "yyyy-mm-dd",
        startDate: "<?php echo date("Y-m-d", strtotime('+ 1 days')) ?>",
        autoclose: true,
        todayHighlight: true
    });
});

//Change quantity
$("#myTable").on('input', '.qty', function() {
    rowindex = $(this).closest('tr').index();
    if($(this).val() < 0 && $(this).val() != '') {
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(1);
        alert("Quantity can't be less than 0");
    }
    checkQuantity($(this).val(), true);
});

//Delete product
$("table.order-list tbody").on("click", ".ibtnDel", function(event) {
    rowindex = $(this).closest('tr').index();
    product_cost.splice(rowindex, 1);
    profit_margin.splice(rowindex, 1);
    profit_margin_type.splice(rowindex, 1);
    product_price.splice(rowindex, 1);
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
    $(".imei-section").remove();
    $('input[name="edit_qty"]').removeAttr('disabled');
    if(is_imei[rowindex]) {
        $('input[name="edit_qty"]').attr('disabled',true);
        var imeiNumbers = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val();
        if(!imeiNumbers.length) {
            htmlText = `<div class="col-md-8 form-group imei-section">
                        <label>IMEI or Serial Numbers</label>
                        <div class="table-responsive ml-2">
                            <table id="imei-table" class="table table-hover">
                                <tbody>
                                    <tr>
                                        <td>
                                            <input type="text" class="form-control imei-numbers" name="imei_numbers[]" />
                                        </td>
                                        <td>
                                            <button type="button" class="imei-del btn btn-sm btn-danger">X</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-info btn-sm ml-2 mb-3" id="imei-add-more"><i class="ion-plus"></i> Add More</button>
                    </div>`;
        }
        else {
            imeiArrays = imeiNumbers.split(",");
            htmlText = `<div class="col-md-8 form-group imei-section">
                        <label>IMEI or Serial Numbers</label>
                        <div class="table-responsive ml-2">
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
                        <button type="button" class="btn btn-info btn-sm ml-2 mb-3" id="imei-add-more"><i class="ion-plus"></i> Add More</button>
                    </div>`;
        }
        $("#editModal .modal-element").append(htmlText);
    }
    var row_product_name = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(1)').text();
    var row_product_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)').text();
    $('#modal_header').text(row_product_name + '(' + row_product_code + ')');

    var qty = $(this).closest('tr').find('.qty').val();
    $('input[name="edit_qty"]').val(qty);

    $('input[name="edit_discount"]').val(parseFloat(product_discount[rowindex]).toFixed({{gen_setting()->decimal}}));

    unitConversion();
    $('input[name="edit_unit_cost"]').val(row_product_cost.toFixed({{gen_setting()->decimal}}));
    $('input[name="edit_profit_margin"]').val(row_profit_margin.toFixed({{gen_setting()->decimal}}));
    $('input[name="edit_product_price"]').val(row_product_price.toFixed({{gen_setting()->decimal}}));
    row_profit_margin_type = profit_margin_type[rowindex] || 'percentage';
        $('select[name="edit_profit_margin_type"]')
            .val(row_profit_margin_type)
            .selectpicker('refresh')
            .trigger('change');

    var tax_name_all = <?php echo json_encode($tax_name_all) ?>;
    var pos = tax_name_all.indexOf(tax_name[rowindex]);
    $('select[name="edit_tax_rate"]').val(pos);

    temp_unit_name = (unit_name[rowindex]).split(',');
    temp_unit_name.pop();
    temp_unit_operator = (unit_operator[rowindex]).split(',');
    temp_unit_operator.pop();
    temp_unit_operation_value = (unit_operation_value[rowindex]).split(',');
    temp_unit_operation_value.pop();
    $('select[name="edit_unit"]').empty();
    $.each(temp_unit_name, function(key, value) {
        $('select[name="edit_unit"]').append('<option value="' + key + '">' + value + '</option>');
    });
    $('.selectpicker').selectpicker('refresh');
});

//add imei
$(document).on("click", "#imei-add-more", function() {
    var newRow = $("<tr>");
    var cols = '';
    cols += '<td><input type="text" class="form-control imei-numbers" name="imei_numbers[]" /></td>';
    cols += '<td><button type="button" class="imei-del btn btn-sm btn-danger">X</button></td>';

    newRow.append(cols);
    $("table#imei-table tbody").append(newRow);
    //increasing qty
    var edit_qty = parseFloat($('input[name="edit_qty"]').val());
    $('input[name="edit_qty"]').val(edit_qty+1);
});

//Delete imei
$(document).on("click", "table#imei-table tbody .imei-del", function() {
    $(this).closest("tr").remove();
    //decreaing qty
    var edit_qty = parseFloat($('input[name="edit_qty"]').val());
    $('input[name="edit_qty"]').val(edit_qty-1);
});

$(document).ready(function () {
    let $cost  = $('input[name="edit_unit_cost"]');
    let $margin = $('input[name="edit_profit_margin"]');
    let $price = $('input[name="edit_product_price"]');
    let $marginType = $('select[name="edit_profit_margin_type"]');

    function recalcPrice() {
        let cost = parseFloat($cost.val()) || 0;
        let margin = parseFloat($margin.val()) || 0;
        let type = profit_margin_type[rowindex] || 'percentage';
        let price = 0;

        if (type === 'percentage') {
            price = cost + (cost * margin / 100);
        } else {
            price = cost + margin;
        }

        $price.val(price ? price.toFixed(2) : 0);
    }

    function recalcMargin() {
        let cost = parseFloat($cost.val()) || 0;
        let price = parseFloat($price.val()) || 0;
        let type = profit_margin_type[rowindex] || 'percentage';
        let margin = 0;

        if (cost <= 0) {
            $margin.val(0);
            return;
        }

        if (type === 'percentage') {
            margin = ((price - cost) / cost) * 100;
        } else {
            margin = price - cost;
        }

        $margin.val(margin ? margin.toFixed(2) : 0);
    }

    // Cost change → recalc price
    $cost.on("input", function () {
        if ($margin.val()) recalcPrice();
    });

    // Margin change → recalc price
    $margin.on("input", function () {
        if ($cost.val()) recalcPrice();
    });

    // Price change → recalc margin
    $price.on("input", function () {
        if ($cost.val()) recalcMargin();
    });

    // Margin type change
    $marginType.on("change", function () {
        if ($(this).val() === 'percentage') {
            $margin.attr('placeholder', '% value');
        } else {
            $margin.attr('placeholder', 'Flat amount');
        }

        recalcPrice();
    }).trigger("change");

    $('select[name="edit_profit_margin_type"]').on("change", function () {
        var type = $(this).val();
        var margin = parseFloat($('input[name="edit_profit_margin"]').val()) || 0;

        // Update per-row array
        profit_margin_type[rowindex] = type;

        // Recalculate price
        recalcPrice();
    });
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
    var edit_unit_cost = $('input[name="edit_unit_cost"]').val();

    if (parseFloat(edit_discount) > parseFloat(edit_unit_cost)) {
        alert('Invalid Discount Input!');
        return;
    }
    var edit_profit_margin = $('input[name="edit_profit_margin"]').val();
    var edit_profit_margin_type = $('select[name="edit_profit_margin_type"]').val();
    var edit_product_price = $('input[name="edit_product_price"]').val();

    if(edit_qty < 0) {
        $('input[name="edit_qty"]').val(1);
        edit_qty = 1;
        alert("Quantity can't be less than 0");
    }

    var row_unit_operator = unit_operator[rowindex].slice(0, unit_operator[rowindex].indexOf(","));
    var row_unit_operation_value = unit_operation_value[rowindex].slice(0, unit_operation_value[rowindex].indexOf(","));
    row_unit_operation_value = parseFloat(row_unit_operation_value);
    var tax_rate_all = <?php echo json_encode($tax_rate_all) ?>;


    tax_rate[rowindex] = parseFloat(tax_rate_all[$('select[name="edit_tax_rate"]').val()]);
    tax_name[rowindex] = $('select[name="edit_tax_rate"] option:selected').text();


    if (row_unit_operator == '*') {
        product_cost[rowindex] = $('input[name="edit_unit_cost"]').val() / row_unit_operation_value;
    } else {
        product_cost[rowindex] = $('input[name="edit_unit_cost"]').val() * row_unit_operation_value;
    }
    profit_margin[rowindex] = $('input[name="edit_profit_margin"]').val();
    profit_margin_type [rowindex] = $('select[name="edit_profit_margin_type"]').val();
    product_price[rowindex] = $('input[name="edit_product_price"]').val();

    product_discount[rowindex] = $('input[name="edit_discount"]').val();
    var position = $('select[name="edit_unit"]').val();
    var temp_operator = temp_unit_operator[position];
    var temp_operation_value = temp_unit_operation_value[position];
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit').val(temp_unit_name[position]);
    temp_unit_name.splice(position, 1);
    temp_unit_operator.splice(position, 1);
    temp_unit_operation_value.splice(position, 1);

    temp_unit_name.unshift($('select[name="edit_unit"] option:selected').text());
    temp_unit_operator.unshift(temp_operator);
    temp_unit_operation_value.unshift(temp_operation_value);

    unit_name[rowindex] = temp_unit_name.toString() + ',';
    unit_operator[rowindex] = temp_unit_operator.toString() + ',';
    unit_operation_value[rowindex] = temp_unit_operation_value.toString() + ',';
    checkQuantity(edit_qty, false);
});

function productSearch(data) {
    $.ajax({
        type: 'GET',
        url: '../lims_product_search',
        data: {
            data: data
        },
        success: function(data) {
            var flag = 1;
            $(".product-code").each(function(i) {
                if ($(this).val() == data[1]) {
                    rowindex = i;
                    var qty = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val()) + 1;
                    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(qty);
                    if($('select[name="status"]').val() == 1 || $('select[name="status"]').val() == 1) {
                        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .recieved').val(qty);
                    }
                    calculateRowProductData(qty);
                    flag = 0;
                }
            });
            $("input[name='product_code_name']").val('');
            if(flag){
                var newRow = $("<tr>");
                var cols = '';
                temp_unit_name = (data[6]).split(',');
                cols += '<td>' + data[0] + '<button type="button" class="edit-product btn btn-link" data-toggle="modal" data-target="#editModal"> <i class="ti ti-edit"></i></button></td>';
                cols += '<td>' + data[1] + '</td>';
                cols += '<td><input type="text" class="form-control qty" name="qty[]" value="1" required step="0.01" /></td>';
                if($('select[name="status"]').val() == 1)
                    cols += '<td class="recieved-product-qty d-none"><input type="text" class="form-control recieved" name="recieved[]" value="1"  /></td>';
                else if($('select[name="status"]').val() == 2)
                    cols += '<td class="recieved-product-qty"><input type="text" class="form-control recieved" name="recieved[]" value="1" /></td>';
                else
                    cols += '<td class="recieved-product-qty d-none"><input type="text" class="form-control recieved" name="recieved[]" value="0" /></td>';
                if(data[10]) {
                    cols += '<td><input type="text" class="form-control batch-no" name="batch_no[]" required/></td>';
                    cols += '<td><input type="text" class="form-control expired-date" name="expired_date[]" required/></td>';
                }
                else {
                    cols += '<td><input type="text" class="form-control batch-no" name="batch_no[]" disabled/></td>';
                    cols += '<td><input type="text" class="form-control expired-date" name="expired_date[]" disabled/></td>';
                }
                cols += '<td class="net_unit_cost"></td>';
                cols += '<td class="net_unit_margin"></td>';
                cols += '<td class="net_unit_margin_type"></td>';
                cols += '<td class="net_unit_price"></td>';
                cols += '<td class="discount">{{number_format(0, gen_setting()->decimal, '.', '')}}</td>';
                cols += '<td class="tax"></td>';
                cols += '<td class="sub-total"></td>';
                cols += '<td><button type="button" class="ibtnDel btn btn-md btn-danger"><i class="ti ti-trash"></i></button></td>';
                cols += '<input type="hidden" class="product-code" name="product_code[]" value="' + data[1] + '"/>';
                cols += '<input type="hidden" class="product-id" name="product_id[]" value="' + data[9] + '"/>';
                cols += '<input type="hidden" class="purchase-unit" name="purchase_unit[]" value="' + temp_unit_name[0] + '"/>';
                cols += '<input type="hidden" class="unit_cost" name="unit_cost[]" />';
                cols += '<input type="hidden" class="net_unit_cost" name="net_unit_cost[]" />';
                cols += '<input type="hidden" class="net_unit_margin" name="net_unit_margin[]" />';
                cols += '<input type="hidden" class="net_unit_margin_type" name="net_unit_margin_type[]" />';
                cols += '<input type="hidden" class="net_unit_price" name="net_unit_price[]" />';
                cols += '<input type="hidden" class="discount-value" name="discount[]" />';
                cols += '<input type="hidden" class="tax-rate" name="tax_rate[]" value="' + data[3] + '"/>';
                cols += '<input type="hidden" class="tax-value" name="tax[]" />';
                cols += '<input type="hidden" class="subtotal-value" name="subtotal[]" />';
                cols += '<input type="hidden" class="imei-number" name="imei_number[]" />';
                cols += '<input type="hidden" class="original-cost" value="'+data[2]+'" />';
                cols += '<input type="hidden" class="original-profit-margin" value="'+data['profit_margin']+'" />';
                cols += '<input type="hidden" class="original-profit-margin-type" value="'+data['profit_margin_type']+'" />';
                cols += '<input type="hidden" class="original-price" value="'+data['product_price']+'" />';

                newRow.append(cols);
                $("table.order-list tbody").prepend(newRow);

                rowindex = newRow.index();
                product_cost.splice(rowindex, 0, parseFloat(data[2]));
                profit_margin.splice(rowindex,0, parseFloat(data['profit_margin']));
                profit_margin_type.splice(rowindex, 0, data['profit_margin_type'] ?? 'percentage');
                product_price.splice(rowindex,0, parseFloat(data['product_price']));
                product_discount.splice(rowindex, 0, '{{number_format(0, gen_setting()->decimal, '.', '')}}');
                tax_rate.splice(rowindex, 0, parseFloat(data[3]));
                tax_name.splice(rowindex, 0, data[4]);
                tax_method.splice(rowindex, 0, data[5]);
                unit_name.splice(rowindex, 0, data[6]);
                unit_operator.splice(rowindex, 0, data[7]);
                unit_operation_value.splice(rowindex, 0, data[8]);
                is_imei.splice(rowindex, 0, data[11]);
                calculateRowProductData(1);
                if(data[11]) {
                    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.edit-product').click();
                }
            }
        }
    });
}
function checkQuantity(purchase_qty, flag) {
    var row_product_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)').text();
    var pos = product_code.indexOf(row_product_code);
    var operator = unit_operator[rowindex].split(',');
    var operation_value = unit_operation_value[rowindex].split(',');
    if(operator[0] == '*')
    	total_qty = purchase_qty * operation_value[0];
    else if(operator[0] == '/')
    	total_qty = purchase_qty / operation_value[0];

    $('#editModal').modal('hide');
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(purchase_qty);
    var status = $('select[name="status"]').val();
    if(status == '1' || status == '2' )
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.recieved').val(purchase_qty);
    else
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.recieved').val(0);

    if(flag) {
        product_cost[rowindex] = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.original-cost').val() * exchangeRate;
        profit_margin[rowindex] = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.original-profit-margin').val();
        profit_margin_type[rowindex] = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.original-profit-margin-type').val();
        product_price[rowindex] = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.original-price').val();
        // console.log(product_cost, profit_margin, product_price);
    }
    calculateRowProductData(purchase_qty);
}

function calculateRowProductData(quantity) {
    //product_cost[rowindex] = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.original-cost').val() * exchangeRate;
    unitConversion();
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.discount').text((product_discount[rowindex] * quantity).toFixed({{gen_setting()->decimal}}));
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.discount-value').val((product_discount[rowindex] * quantity).toFixed({{gen_setting()->decimal}}));
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-rate').val(tax_rate[rowindex].toFixed({{gen_setting()->decimal}}));

    if (tax_method[rowindex] == 1) {
        var net_unit_cost = row_product_cost - product_discount[rowindex];
        var net_unit_margin = row_profit_margin;
        var net_unit_margin_type = row_profit_margin_type;
        var net_unit_price = row_product_price;
        var tax = net_unit_cost * quantity * (tax_rate[rowindex] / 100);
        var unit_cost = net_unit_cost + (net_unit_cost * tax_rate[rowindex] / 100);
        var sub_total = (net_unit_cost * quantity) + tax;

        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_cost').text(net_unit_cost.toFixed({{gen_setting()->decimal}}));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_cost').val(net_unit_cost.toFixed({{gen_setting()->decimal}}));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.unit_cost').val(unit_cost.toFixed({{gen_setting()->decimal}}));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_margin').text(net_unit_margin.toFixed({{gen_setting()->decimal}}));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_margin').val(net_unit_margin.toFixed({{gen_setting()->decimal}}));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_margin_type').text(net_unit_margin_type);
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_margin_type').val(net_unit_margin_type);
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_price').text(net_unit_price.toFixed({{gen_setting()->decimal}}));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_price').val(net_unit_price.toFixed({{gen_setting()->decimal}}));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax').text(tax.toFixed({{gen_setting()->decimal}}));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-value').val(tax.toFixed({{gen_setting()->decimal}}));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sub-total').text(sub_total.toFixed({{gen_setting()->decimal}}));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.subtotal-value').val(sub_total.toFixed({{gen_setting()->decimal}}));
    } else {
        var sub_total_unit = row_product_cost - product_discount[rowindex];
        var unit_cost = sub_total_unit;
        var net_unit_cost = (100 / (100 + tax_rate[rowindex])) * sub_total_unit;
        var net_unit_margin = row_profit_margin;
        var net_unit_margin_type = row_profit_margin_type;
        var net_unit_price = row_product_price;
        var tax = (sub_total_unit - net_unit_cost) * quantity;
        var sub_total = sub_total_unit * quantity;

        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_cost').text(net_unit_cost.toFixed({{gen_setting()->decimal}}));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_cost').val(net_unit_cost.toFixed({{gen_setting()->decimal}}));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.unit_cost').val(unit_cost.toFixed({{gen_setting()->decimal}}));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_margin').text(net_unit_margin.toFixed({{gen_setting()->decimal}}));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_margin').val(net_unit_margin.toFixed({{gen_setting()->decimal}}));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_margin_type').text(net_unit_margin_type);
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_margin_type').val(net_unit_margin_type);
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_price').text(net_unit_price.toFixed({{gen_setting()->decimal}}));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_price').val(net_unit_price.toFixed({{gen_setting()->decimal}}));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax').text(tax.toFixed({{gen_setting()->decimal}}));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-value').val(tax.toFixed({{gen_setting()->decimal}}));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sub-total').text(sub_total.toFixed({{gen_setting()->decimal}}));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.subtotal-value').val(sub_total.toFixed({{gen_setting()->decimal}}));
    }

    calculateTotal();
}

function unitConversion() {
    var row_unit_operator = unit_operator[rowindex].slice(0, unit_operator[rowindex].indexOf(","));
    var row_unit_operation_value = unit_operation_value[rowindex].slice(0, unit_operation_value[rowindex].indexOf(","));
    row_unit_operation_value = parseFloat(row_unit_operation_value);
    if (row_unit_operator == '*') {
        row_product_cost = product_cost[rowindex] * row_unit_operation_value;
    } else {
        row_product_cost = product_cost[rowindex] / row_unit_operation_value;
    }
    // console.log(profit_margin[rowindex], product_price[rowindex]);
    row_profit_margin = parseFloat(profit_margin[rowindex]);
    row_profit_margin_type = profit_margin_type[rowindex];
    row_product_price = parseFloat(product_price[rowindex]);
}

function calculateTotal() {
    //Sum of quantity
    var total_qty = 0;
    $(".qty").each(function() {

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
    $(".discount").each(function() {
        total_discount += parseFloat($(this).text());
    });
    $("#total-discount").text(total_discount.toFixed({{gen_setting()->decimal}}));
    $('input[name="total_discount"]').val(total_discount.toFixed({{gen_setting()->decimal}}));

    //Sum of tax
    var total_tax = 0;
    $(".tax").each(function() {
        total_tax += parseFloat($(this).text());
    });
    $("#total-tax").text(total_tax.toFixed({{gen_setting()->decimal}}));
    $('input[name="total_tax"]').val(total_tax.toFixed({{gen_setting()->decimal}}));

    //Sum of subtotal
    var total = 0;
    $(".sub-total").each(function() {
        total += parseFloat($(this).text());
    });
    $("#total").text(total.toFixed({{gen_setting()->decimal}}));
    $('input[name="total_cost"]').val(total.toFixed({{gen_setting()->decimal}}));

    calculateGrandTotal();
}

function calculateGrandTotal() {

    var item = $('table.order-list tbody tr:last').index();

    var total_qty = parseFloat($('#total-qty').text());
    var subtotal = parseFloat($('#total').text());
    var order_tax = parseFloat($('select[name="order_tax_rate"]').val());
    var order_discount = parseFloat($('input[name="order_discount"]').val());
    var shipping_cost = parseFloat($('input[name="shipping_cost"]').val());

    if (!order_discount)
        order_discount = {{number_format(0, gen_setting()->decimal, '.', '')}};
    if (!shipping_cost)
        shipping_cost = {{number_format(0, gen_setting()->decimal, '.', '')}};

    item = ++item + '(' + total_qty + ')';
    order_tax = (subtotal - order_discount) * (order_tax / 100);
    var grand_total = (subtotal + order_tax + shipping_cost) - order_discount;

    $('#item').text(item);
    $('input[name="item"]').val($('table.order-list tbody tr:last').index() + 1);
    $('#subtotal').text(subtotal.toFixed({{gen_setting()->decimal}}));
    $('#order_tax').text(order_tax.toFixed({{gen_setting()->decimal}}));
    $('input[name="order_tax"]').val(order_tax.toFixed({{gen_setting()->decimal}}));
    $('#order_discount').text(order_discount.toFixed({{gen_setting()->decimal}}));
    $('#shipping_cost').text(shipping_cost.toFixed({{gen_setting()->decimal}}));
    $('#grand_total').text(grand_total.toFixed({{gen_setting()->decimal}}));
    $('input[name="grand_total"]').val(grand_total.toFixed({{gen_setting()->decimal}}));
}

$('input[name="order_discount"]').on("blur", function() {
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

$('#purchase-form').on('submit',function(e){
    var rownumber = $('table.order-list tbody tr:last').index();
    if (rownumber < 0) {
        alert("Please insert product to order table!")
        e.preventDefault();
    }

    else if($('select[name="status"]').val() != 1)
    {
        flag = 0;
        $(".qty").each(function() {
            rowindex = $(this).closest('tr').index();
            quantity =  $(this).val();
            recieved = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.recieved').val();

            if(quantity != recieved){
                flag = 1;
                return false;
            }
        });
        if(!flag){
            alert('Quantity and Recieved value is same! Please Change Purchase Status or Recieved value');
            e.preventDefault();
        }
        else
            $(".batch-no, .expired-date").prop('disabled', false);
    }
    else {
        $("#submit-button").prop('disabled', true);
        $(".batch-no, .expired-date").prop('disabled', false);
    }
});
</script>
@endpush
