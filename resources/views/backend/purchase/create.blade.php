@extends('backend.layout.main') @section('content')

<x-error-message key="not_permitted" />
<x-validation-error fieldName="product_code" />
<x-validation-error fieldName="qty" />

<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h4>{{__('db.Add Purchase')}}</h4>
                <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>

                <form action="{{ route('purchases.store') }}" method="POST" enctype="multipart/form-data" id="purchase-form">
                    @csrf
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.date')}}</label>
                                        <input type="text" name="created_at" class="form-control date" placeholder="{{__('db.Choose date')}}" value="{{date(gen_setting()->date_format,strtotime('now'))}}"/>
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
                                        <label>{{__('db.Warehouse')}} *</label>
                                        <div class="input-group pos">
                                            <select name="warehouse_id" class="selectpicker form-control" data-live-search="true" title="Select warehouse..." required>
                                                @foreach($lims_warehouse_list as $warehouse)
                                                <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#addWarehouse"><i class="ti ti-plus"></i></button>
                                        </div>
                                        <x-validation-error fieldName="warehouse_id" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Supplier')}}</label>
                                        <div class="input-group pos">
                                            <select name="supplier_id" id="supplier-select" class="selectpicker form-control" data-live-search="true" title="Select supplier...">
                                                @foreach($lims_supplier_list as $supplier)
                                                <option value="{{$supplier->id}}"
                                                        data-pay-term-no="{{$supplier->pay_term_no ?? ''}}"
                                                        data-pay-term-period="{{$supplier->pay_term_period ?? 'days'}}">
                                                    {{$supplier->name .' ('. $supplier->company_name .')'}}
                                                </option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#addSupplier"><i class="ti ti-plus"></i></button>
                                        </div>
                                    </div>
                                </div>
                                {{-- Payment Terms --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.payment_term') }}</label>
                                        <div class="d-flex">
                                            <input type="number"
                                                id="pay_term_no"
                                                name="pay_term_no"
                                                class="form-control"
                                                placeholder="e.g. 30">
                                            <select name="pay_term_period" id="pay_term_period" class="form-control ml-2">
                                                <option value="days">{{ __('db.Days') }}</option>
                                                <option value="months">{{ __('db.Months') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                {{-- Due Date --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.due_date') }}</label>
                                        <input type="hidden" name="due_date" id="due_date_hidden">
                                        <input type="text"
                                            id="due_date"
                                            class="form-control date"
                                            placeholder="{{ __('db.Choose date') }}"
                                            readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Purchase Status')}}</label>
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
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>{{__('db.Currency')}} *</label>
                                        <select name="currency_id" id="currency-id" class="form-control selectpicker" data-toggle="tooltip" title="">
                                            @foreach($currency_list as $currency_data)
                                            <option value="{{$currency_data->id}}" data-rate="{{$currency_data->exchange_rate}}" @if($currency_data->exchange_rate == 1){{'checked'}}@endif>{{$currency_data->code}}</option>
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
                                        <input class="form-control" type="text" id="exchange_rate" name="exchange_rate" value="{{$currency->exchange_rate}}">
                                        <div class="input-group-append">
                                            <span class="input-group-text" data-toggle="tooltip" title="" data-original-title="currency exchange rate">i</span>
                                        </div>
                                        <x-validation-error fieldName="exchange_rate" />
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
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mt-3">
                                    <label>{{__('db.Select Product')}}</label>
                                    <div class="search-box input-group">
                                        <button class="btn btn-secondary"><i class="ti ti-barcode"></i></button>
                                        <input type="text" name="product_code_name" id="lims_productcodeSearch" placeholder="{{__('db.Please type product code and select')}}" class="form-control" />

                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <h5>{{__('db.Order Table')}} *</h5>
                                    <div class="table-responsive mt-3">
                                        <table id="myTable" class="table table-hover order-list">
                                            <thead>
                                                <tr>
                                                    <th style="min-width: 30%">{{__('db.product')}}</th>
                                                    <th>{{__('db.Quantity')}}</th>
                                                    <th class="recieved-product-qty d-none">{{__('db.Recieved')}}</th>
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
                                            </tbody>
                                            <tfoot class="tfoot active">
                                                <th>{{__('db.Total')}}</th>
                                                <th id="total-qty">0</th>
                                                <th class="recieved-product-qty d-none"></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th id="total-discount">{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
                                                <th id="total-tax">{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
                                                <th id="total">{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
                                                <th><i class="ti ti-trash"></i></th>
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
                                        <input type="hidden" name="total_cost" />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <input type="hidden" name="profit_margin" />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <input type="hidden" name="profit_margin_type" />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <input type="hidden" name="product_price" />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <input type="hidden" name="item" />
                                        <input type="hidden" name="order_tax" />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <input type="hidden" name="grand_total" />
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
                                        <input type="number" name="order_discount" class="form-control" step="any" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Shipping Cost')}}</label>
                                        <input type="number" name="shipping_cost" class="form-control" step="any" />
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Payment Status')}} *</label>
                                        <select name="payment_status" id="payment_status" class="form-control">
                                            <option value="1">{{__('db.Due')}}</option>
                                            <option value="3">{{__('db.Partial')}}</option>
                                            <option value="4">{{__('db.Paid')}}</option>
                                        </select>
                                        <x-validation-error fieldName="payment_status" />
                                    </div>
                                </div>
                                <?php
                                    $accountSelection = $role_has_permissions_list->where('name', 'account-selection')->first();
                                    $defaultAccount = $lims_account_list->where('is_default', true)->first();
                                ?>
                                <!-- New Account Selection Field -->
                                <div id="account-list" class="col-md-3 col-6" hidden>
                                    <div class="form-group top-fields">
                                        <label>{{__('db.Account')}}</label>
                                        <select required name="account_id" id="account_id" class="selectpicker form-control" data-live-search="true">
                                            @if ($accountSelection)
                                                @foreach($lims_account_list as $account)
                                                    @if($account->is_default)
                                                    <option selected value="{{$account->id}}" data-total_balance="{{$account->total_balance}}">{{$account->name}} [{{$account->account_no}}]</option>
                                                    @else
                                                    <option value="{{$account->id}}" data-total_balance="{{$account->total_balance}}">{{$account->name}} [{{$account->account_no}}]</option>
                                                    @endif
                                                @endforeach
                                            @else
                                                <option selected value="{{$defaultAccount->id}}" data-total_balance="{{$defaultAccount->total_balance}}">{{$defaultAccount->name}} [{{$defaultAccount->account_no}}]</option>
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div id="payment">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Paid By')}}</label>
                                            <select name="paid_by_id[]" class="form-control">
                                                <option value="1">{{ __('db.Cash') }}</option>
                                                <option value="3">{{ __('db.Credit Card') }}</option>
                                                <option value="4">{{ __('db.Cheque') }}</option>
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
                                            <input type="number" name="amount[]" class="form-control" id="paid-amount" step="0.01"/>
                                            <input type="hidden" name="paid_amount" value="0" />
                                        </div>
                                        <div class="alert alert-danger d-none p-2 position-absolute" id="paying-amount-error">
                                            Paying amount must be greater than 0
                                        </div>
                                        <div class="alert alert-danger d-none p-2 position-absolute" id="paying-amount-error-2">

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
                                            <p id="change" class="ml-2">{{ number_format(0, gen_setting()->decimal, '.', '') }}</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Credit card fields --}}
                                <div class="row mt-2" id="card-element-row" style="display:none;">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <div class="card-element form-control"></div>
                                            <div class="card-errors text-danger mt-2" role="alert"></div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Cheque fields --}}
                                <div class="row" id="cheque-row" style="display:none;">
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
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{{__('db.note')}}</label>
                                        <textarea rows="5" class="form-control" name="note"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary" id="submit-btn">{{__('db.submit')}}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card" style="margin-top:0;">
            <div class="card-body">
                <div class="card-title">
                    {{ __('db.Order Summary') }}
                </div>
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
    </div>
    <div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="modal-header" class="modal-title"></h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row modal-element">
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Quantity')}}</label>
                                <input type="number" name="edit_qty" class="form-control" min="1"
                                 step="0.01">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Unit Discount')}}</label>
                                <input type="number" name="edit_discount" class="form-control" step="0.01">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Unit Cost')}}</label>
                                <input type="number" name="edit_unit_cost" class="form-control" step="0.01">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.profit_margin_type')}}</label>
                                <select name="edit_profit_margin_type" class="form-control" required>
                                    <option value="percentage">Percentage (%)</option>
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

@include('backend.warehouse.add-warehouse')

<div id="addSupplier" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Supplier')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('supplier.store') }}" method="POST" id="supplier-form">
                    @csrf

                    <div class="row">

                        {{-- Name --}}
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{__('db.name')}} *</label>
                                <input type="text" name="name" required class="form-control">
                            </div>
                        </div>

                        {{-- Company Name --}}
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{__('db.Company Name')}} *</label>
                                <input type="text" name="company_name" required class="form-control">
                            </div>
                        </div>

                        {{-- Opening Balance --}}
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{__('db.Opening balance')}} ({{__('db.Due')}})</label>
                                <input type="number" name="opening_balance"
                                    class="form-control"
                                    value="0" step="any" min="0">
                            </div>
                        </div>

                        {{-- Email --}}
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{__('db.email')}} *</label>
                                <input type="email"
                                    name="email"
                                    required
                                    class="form-control">
                            </div>
                        </div>

                        {{-- Phone --}}
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{__('db.Phone Number')}} *</label>
                                <input type="text"
                                    name="phone_number"
                                    required
                                    class="form-control">
                            </div>
                        </div>

                        {{-- WhatsApp --}}
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{__('db.WhatsApp Number')}}</label>
                                <input type="text"
                                    name="wa_number"
                                    class="form-control">
                            </div>
                        </div>

                        {{-- Address --}}
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{__('db.Address')}} *</label>
                                <input type="text"
                                    name="address"
                                    required
                                    class="form-control">
                            </div>
                        </div>

                        {{-- City --}}
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{__('db.City')}} *</label>
                                <input type="text"
                                    name="city"
                                    required
                                    class="form-control">
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="col-md-12">
                            <div class="form-group mt-4">
                                <input type="submit"
                                    value="{{__('db.submit')}}"
                                    class="btn btn-primary supplier-submit-btn">
                            </div>
                        </div>

                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="text/javascript">

    $("ul#purchase").siblings('a').attr('aria-expanded','true');
    $("ul#purchase").addClass("show");
    $("ul#purchase #purchase-create-menu").addClass("active");

    $("#payment").hide();
    $("#cheque").hide();

    // array data depend on warehouse
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
    var row_profit_margin_type;
    var row_product_price = 0;
    var currency = <?php echo json_encode($currency) ?>;
    var exchangeRate = 1;
    var currencyChange = false;

    $(document).ready(function(){
        $("#submit-btn").on("click", function(e){
            e.preventDefault(); // stop normal submit
            var payment_status = $('#payment_status').val();

            if(payment_status == 3 || payment_status == 4){
                var balance = $('#account_id option:selected').data('total_balance');
                var currency_rate = $('#currency-id option:selected').data('rate');
                let grandTotal = parseFloat($('#grand_total').text()) || 0;
                balance = currency_rate * balance;
                if(balance < grandTotal){
                    if(confirm("Are you sure you want to submit this form without balance?")) {
                        $(this).closest("form").submit();
                    }
                }else{
                    $(this).closest("form").submit();
                }
            }else{
                 $(this).closest("form").submit();
            }
        });

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

        $('#purchase-form').on('submit', function (e) {
            /* ===============================
            0. Warehouse must be selected
            =============================== */
            if ($('select[name="warehouse_id"]').val() == '') {
                alert("Please select warehouse!");
                e.preventDefault();
                return false;
            }

            /* ===============================
            1. Order table must have rows
            =============================== */
            var rownumber = $('table.order-list tbody tr:last').index();
            if (rownumber < 0) {
                alert("Please insert product to order table!");
                e.preventDefault();
                return false;
            }

            /* ===============================
            2. Batch & Expiry validation
            (dynamic inputs safe)
            =============================== */
            let batchError = false;

            $('.batch-no, .expired-date').each(function () {
                if ($(this).is(':visible') && !$(this).val()) {
                    batchError = true;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (batchError) {
                alert('Batch No and Expired Date are required');
                e.preventDefault();
                return false;
            }

            /* ===============================
            3. Purchase status logic
            =============================== */
            if ($('select[name="status"]').val() != 1) {

                let flag = 0;

                $(".qty").each(function () {
                    let rowindex = $(this).closest('tr').index();
                    let quantity = $(this).val();
                    let recieved = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')')
                                    .find('.recieved').val();

                    if (quantity != recieved) {
                        flag = 1;
                        return false;
                    }
                });

                if (!flag) {
                    alert('Quantity and Recieved value is same! Please Change Purchase Status or Recieved value');
                    e.preventDefault();
                    return false;
                }
            }

            /* ===============================
            4. Enable batch inputs & lock submit
            =============================== */
            $(".batch-no, .expired-date").prop('disabled', false);
            $("#submit-btn").prop('disabled', true);
        });
    });

    $('#currency-id').val(currency['id']);
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

    $('select[name="status"]').on('change', function() {
        if($('select[name="status"]').val() == 2){
            $(".recieved-product-qty").removeClass("d-none");
            $(".qty").each(function() {
                rowindex = $(this).closest('tr').index();
                $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.recieved').val($(this).val());
            });

        }
        else if(($('select[name="status"]').val() == 3) || ($('select[name="status"]').val() == 4)) {
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

    <?php $productArray = []; ?>
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
        $(this).closest("tr").remove();
        calculateTotal();
    });

    //Edit product
    $("table.order-list").on("click", ".edit-product", function() {
        rowindex = $(this).closest('tr').index();
        $(".imei-section").remove();
        $('input[name="edit_qty"]').removeAttr('disabled');
        if(is_imei[rowindex]) {
            $('input[name="edit_qty"]').attr('disabled',true)
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
            // htmlText = '<div class="col-md-12 form-group imei-section"><label>IMEI or Serial Numbers</label><input type="text" name="imei_numbers" value="'+imeiNumbers+'" class="form-control imei_number" placeholder="Type imei or serial numbers and separate them by comma. Example:1001,2001" step="any"></div>';
            $("#editModal .modal-element").append(htmlText);

        }

        var row_product_name = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(1)').text();
        var row_product_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)').text();
        $('#modal-header').text(row_product_name + '(' + row_product_code + ')');

        var qty = $(this).closest('tr').find('.qty').val();
        $('input[name="edit_qty"]').val(qty);

        $('input[name="edit_discount"]').val(parseFloat(product_discount[rowindex]).toFixed({{gen_setting()->decimal}}));

        unitConversion();
        $('input[name="edit_unit_cost"]').val(row_product_cost.toFixed({{gen_setting()->decimal}}));
        $('input[name="edit_profit_margin"]').val(row_profit_margin.toFixed({{gen_setting()->decimal}}));
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
        profit_margin_type[rowindex] = $('select[name="edit_profit_margin_type"]').val();
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
            url: 'lims_product_search',
            data: {
                data: data
            },
            success: function(data) {
                // console.log(data);
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

                    var batch_expiry = '';

                    if(data[10]) {
                        batch_expiry += '<input type="text" class="form-control batch-no mb-1" name="batch_no[]" placeholder="{{__("db.Batch No")}}" required/>';
                        batch_expiry += '<input type="text" class="form-control expired-date" name="expired_date[]" placeholder="{{__("db.Expired Date")}}" required/>';
                    }
                    cols += '<td>' + data[0] + ' (' + data[1] + ') <button type="button" class="edit-product btn btn-link" data-toggle="modal" data-target="#editModal"> <i class="ti ti-edit"></i></button><br>'+batch_expiry+'</td>';
                    cols += '<td><input type="number" class="form-control qty" name="qty[]" min="0.01" step="0.01" value="1" required style="font-size:13px;max-width:50px;padding: 0 0;text-align:center"/></td>';
                    if($('select[name="status"]').val() == 1)
                        cols += '<td class="recieved-product-qty d-none"><input type="text" class="form-control recieved" name="recieved[]" value="1" /></td>';
                    else if($('select[name="status"]').val() == 2)
                        cols += '<td class="recieved-product-qty"><input type="text" class="form-control recieved" name="recieved[]" value="1" /></td>';
                    else
                        cols += '<td class="recieved-product-qty d-none"><input type="text" class="form-control recieved" name="recieved[]" value="0" /></td>';

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
                    cols += `<input type="hidden"
                                    class="original-profit-margin-type"
                                    value="${data['profit_margin_type'] || 'percentage'}">`
                    cols += '<input type="hidden" class="original-price" value="'+data['product_price']+'" />';

                    newRow.append(cols);
                    $("table.order-list tbody").prepend(newRow);

                    rowindex = newRow.index();
                    product_cost.splice(rowindex,0, parseFloat(data[2]));
                    profit_margin.splice(rowindex,0, parseFloat(data['profit_margin']));
                    profit_margin_type.splice(rowindex,0, data['profit_margin_type'] || 'percentage');
                    product_price.splice(rowindex,0, parseFloat(data['product_price']));
                    product_discount.splice(rowindex,0, "{{number_format(0, gen_setting()->decimal, '.', '')}}");
                    tax_rate.splice(rowindex,0, parseFloat(data[3]));
                    tax_name.splice(rowindex,0, data[4]);
                    tax_method.splice(rowindex,0, data[5]);
                    unit_name.splice(rowindex,0, data[6]);
                    unit_operator.splice(rowindex,0, data[7]);
                    unit_operation_value.splice(rowindex,0, data[8]);
                    is_imei.splice(rowindex, 0, data[11]);
                    checkQuantity(1, true);
                    if(data[11]) {
                        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.edit-product').click();
                    }
                }
            }
        });
    }

    function checkQuantity(purchase_qty, flag) {
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
            profit_margin_type[rowindex] = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')')
                .find('.original-profit-margin-type')
                .val() || 'percentage';
            product_price[rowindex] = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.original-price').val();
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
            // console.log(row_profit_margin_type);
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
            // console.log(row_profit_margin_type);
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
        row_profit_margin = parseFloat(profit_margin[rowindex]);
        row_profit_margin_type = profit_margin_type[rowindex] || 'percentage';
        profit_margin_type[rowindex] = row_profit_margin_type;
        // console.log(profit_margin_type[rowindex]);
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
        payment_amount();
    }

    function calculateGrandTotal() {
        var item = $('table.order-list tbody tr:last').index();
        var total_qty = parseFloat($('#total-qty').text());
        var subtotal = parseFloat($('#total').text());
        var order_tax = parseFloat($('select[name="order_tax_rate"]').val());
        if($('input[name="order_discount"]').val()) {
            if(!currencyChange)
                var order_discount = parseFloat($('input[name="order_discount"]').val());
            else
                var order_discount = parseFloat($('input[name="order_discount"]').val()) * exchangeRate;
        }
        else
            var order_discount = {{number_format(0, gen_setting()->decimal, '.', '')}};

        if($('input[name="shipping_cost"]').val()) {
            if(!currencyChange)
                var shipping_cost = parseFloat($('input[name="shipping_cost"]').val());
            else
                var shipping_cost = parseFloat($('input[name="shipping_cost"]').val()) * exchangeRate;
        }
        else
            var shipping_cost = {{number_format(0, gen_setting()->decimal, '.', '')}};

        item = ++item + '(' + total_qty + ')';
        order_tax = (subtotal - order_discount) * (order_tax / 100);
        var grand_total = (subtotal + order_tax + shipping_cost) - order_discount;

        $('#item').text(item);
        $('input[name="item"]').val($('table.order-list tbody tr:last').index() + 1);
        $('#subtotal').text(subtotal.toFixed({{gen_setting()->decimal}}));
        $('#order_tax').text(order_tax.toFixed({{gen_setting()->decimal}}));
        $('input[name="order_tax"]').val(order_tax.toFixed({{gen_setting()->decimal}}));
        $('#order_discount').text(order_discount.toFixed({{gen_setting()->decimal}}));
        $('input[name="order_discount"]').val(order_discount);
        $('#shipping_cost').text(shipping_cost.toFixed({{gen_setting()->decimal}}));
        $('input[name="shipping_cost"]').val(shipping_cost);
        $('#grand_total').text(grand_total.toFixed({{gen_setting()->decimal}}));
        $('input[name="grand_total"]').val(grand_total.toFixed({{gen_setting()->decimal}}));
        currencyChange = false;
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

    $('select[name="payment_status"]').on("change", function() {
        var payment_status = $(this).val();
        payment_amount()
    });

    function payment_amount(){
        var payment_status = $('#payment_status').val();
        if (payment_status == '3' || payment_status == '4') {
            $("#paid-amount").prop('readonly', false);
            $("#payment").show();
            $("#paying-amount").prop('required', true);
            $("#paid-amount").prop('required', true);

            if (payment_status == '4') {
                $("#paid-amount").prop('readonly', true);
                $('input[name="paying_amount[]"]').val($('input[name="grand_total"]').val());
                $('input[name="amount[]"]').val($('input[name="grand_total"]').val());
                $('input[name="paid_amount"]').val($('input[name="grand_total"]').val());
            }
            $("#account-list").attr("hidden", false);
        } else {
            $("#paying-amount").prop('required', false);
            $("#paid-amount").prop('required', false);
            $('input[name="paying_amount[]"]').val('');
            $('input[name="amount[]"]').val('');
            $("#payment").hide();
            $("#account-list").attr("hidden", true);
        }
    }
    $('select[name="paid_by_id[]"]').on('change', function () {
        var paidBy = $(this).val();
        if (paidBy == 3) {
            $('#card-element-row').show();
            $('#cheque-row').hide();
        } else if (paidBy == 4) {
            $('#cheque-row').show();
            $('#card-element-row').hide();
        } else {
            $('#card-element-row').hide();
            $('#cheque-row').hide();
        }
    });

    $('input[name="amount[]"]').on("input", function() {
        let payingAmount = parseFloat($('input[name="paying_amount[]"]').val()) || 0;
        let paidAmount   = parseFloat($(this).val()) || 0;
        let grandTotal   = parseFloat($('#grand_total').text()) || 0;
        $('#paying-amount-error-2').addClass('d-none')

        if (paidAmount > payingAmount) {
            $('#paying-amount-error').addClass('d-none');
            $('#paying-amount-error-2')
                .removeClass('d-none')
                .text('Paying amount cannot be bigger than received amount');
            $("#submit-btn").prop('disabled', true).css('cursor', 'default');
        }
        else if (paidAmount > grandTotal) {
            $('#paying-amount-error').addClass('d-none');
            $('#paying-amount-error-2')
                .removeClass('d-none')
                .text('Paying amount cannot be bigger than grand total');
            $("#submit-btn").prop('disabled', true).css('cursor', 'default');
        }
        else if (paidAmount <= 0) {
            $("#submit-btn").prop('disabled', true).css('cursor', 'default');
            $('#paying-amount-error').removeClass('d-none');
        }
        else {
            $('#paying-amount-error').addClass('d-none');
            $("#submit-btn").prop('disabled', false).css('cursor', 'pointer');
        }

        // Update change
        $("#change").text((payingAmount - paidAmount).toFixed({{gen_setting()->decimal}}));
    });

    $('input[name="paying_amount[]"]').on("input", function() {
        let payingAmount = parseFloat($(this).val()) || 0;
        let paidAmount   = parseFloat($('input[name="amount[]"]').val()) || 0;

        $("#change").text((payingAmount - paidAmount).toFixed({{gen_setting()->decimal}}));
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

    $('.supplier-submit-btn').on("click", function(e) {
        e.preventDefault();
        $.ajax({
            type:'POST',
            url:'{{route('supplier.store')}}',
            data: $("#supplier-form").serialize(),
            success:function(response) {
                key = response['id'];
                value = response['name']+' ('+response['company_name']+')';
                $('select[name="supplier_id"]').append('<option value="'+ key +'" data-pay-term-no="'+response['pay_term_no']+'" data-pay-term-period="'+response['pay_term_period']+'">'+ value +'</option>');
                $('select[name="supplier_id"]').val(key).trigger('change');
                $('.selectpicker').selectpicker('refresh');
                $("#addSupplier").modal('hide');
            }
        });
    });

    $('.warehouse-submit-btn').on("click", function(e) {
        e.preventDefault();
        $.ajax({
            type:'POST',
            url:'{{route('warehouse.store')}}',
            data: $("#warehouse-form").serialize(),
            success:function(response) {
                key = response['id'];
                value = response['name'];
                $('select[name="warehouse_id"]').append('<option value="'+ key +'">'+ value +'</option>');
                $('select[name="warehouse_id"]').val(key).trigger('change');
                $('.selectpicker').selectpicker('refresh');
                $("#addWarehouse").modal('hide');
            }
        });
    });

    // selectpicker change event
    $('#supplier-select').on('changed.bs.select', function () {
        
        var selected = $(this).find('option:selected');
        var termNo     = selected.data('pay-term-no');
        var termPeriod = selected.data('pay-term-period');

        $('#pay_term_no').val(termNo || '');
        $('#pay_term_period').val(termPeriod || 'days');

        calculateDueDate();
    });

    // PHP date format টা JS variable এ রাখুন
    var dateFormat = "{{ gen_setting()->date_format }}";

    function formatDateByFormat(date, format) {
        var y = date.getFullYear();
        var m = String(date.getMonth() + 1).padStart(2, '0');
        var d = String(date.getDate()).padStart(2, '0');

        return format
            .replace('Y', y)
            .replace('m', m)
            .replace('d', d);
    }

    function calculateDueDate() {
        var purchaseDate = $('input[name="created_at"]').val();
        var termNo       = parseInt($('#pay_term_no').val());
        var termPeriod   = $('#pay_term_period').val();

        if (!purchaseDate || !termNo) return;

        // Purchase date parse করুন একই format অনুযায়ী
        var parts = purchaseDate.split(/[-\/]/);
        var date;

        if (dateFormat.startsWith('Y')) {
            // Y-m-d or Y/m/d
            date = new Date(parts[0], parts[1] - 1, parts[2]);
        } else if (dateFormat.startsWith('m')) {
            // m/d/Y or m-d-Y
            date = new Date(parts[2], parts[0] - 1, parts[1]);
        } else {
            // d/m/Y or d-m-Y
            date = new Date(parts[2], parts[1] - 1, parts[0]);
        }

        if (termPeriod === 'days') {
            date.setDate(date.getDate() + termNo);
        } else {
            date.setMonth(date.getMonth() + termNo);
        }

        $('#due_date').val(formatDateByFormat(date, dateFormat));
        $('#due_date_hidden').val(formatDateByFormat(date, 'Y-m-d'));
    }

    $('#pay_term_no, #pay_term_period').on('change input', function () {
        calculateDueDate();
    });

    $('input[name="created_at"]').on('change', function () {
        calculateDueDate();
    });
</script>

<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
@endpush
