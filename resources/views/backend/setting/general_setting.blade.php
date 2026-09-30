@extends('backend.layout.main') @section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.General Setting')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                        <form action="{{ route('setting.generalStore') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.System Title')}} *</label>
                                        <input type="text" name="site_title" class="form-control" value="@if($lims_general_setting_data){{$lims_general_setting_data->site_title}}@endif" required />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.System Logo')}} * <x-info title="jpg, jpeg, png & gif" type="info" /></label>
                                        <input type="file" name="site_logo" class="form-control" value="" accept="image/png, image/jpeg, image/gif"/>
                                    </div>
                                    @if($errors->has('site_logo'))
                                   <span>
                                       <strong id="logo_error">{{ $errors->first('site_logo') }}</strong>
                                    </span>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('favicon')}} <x-info title="jpg, jpeg, png & gif" type="info" /></label>
                                        <input type="file" name="favicon" class="form-control" value="" accept="image/png, image/jpeg, image/gif"/>
                                    </div>
                                </div>
                                <div class="col-md-4 mt-4">
                                    <div class="form-group">
                                        @if($lims_general_setting_data->is_rtl)
                                        <input type="checkbox" name="is_rtl" value="1" checked>
                                        @else
                                        <input type="checkbox" name="is_rtl" value="1" />
                                        @endif
                                        &nbsp;
                                        <label>{{__('db.rtl_layout')}}</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mt-4">
                                    <div class="form-group">
                                        @if(\Illuminate\Support\Facades\Route::has('zatca.ksa.settings'))
                                        <label class="d-block">{{__('db.ZATCA QrCode')}}</label>
                                        <a href="{{ route('zatca.ksa.settings') }}" class="btn btn-sm btn-outline-primary">Manage Phase 1 / Phase 2</a>
                                        @elseif($lims_general_setting_data->is_zatca)
                                        <input type="checkbox" name="is_zatca" value="1" checked>
                                        @else
                                        <input type="checkbox" name="is_zatca" value="1" />
                                        @endif
                                        @unless(\Illuminate\Support\Facades\Route::has('zatca.ksa.settings'))
                                        &nbsp;
                                        <label>{{__('db.ZATCA QrCode')}}</label>
                                        @endunless
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Company Name')}}</label>
                                        <input type="text" name="company_name" class="form-control" value="@if($lims_general_setting_data){{$lims_general_setting_data->company_name}}@endif" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.VAT Registration Number')}}</label>
                                        <input type="text" name="vat_registration_number" class="form-control" value="@if($lims_general_setting_data){{$lims_general_setting_data->vat_registration_number}}@endif" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Time Zone')}}</label>
                                        @php
                                            // Use DB timezone if exists, otherwise fallback to Laravel default timezone
                                            $active_timezone = $lims_general_setting_data->timezone ?? config('app.timezone');
                                        @endphp

                                        <input type="hidden" name="timezone_hidden" value="{{ $active_timezone }}">

                                        <select name="timezone" class="selectpicker form-control" data-live-search="true" title="Select TimeZone...">
                                            @foreach($zones_array as $zone)
                                                <option value="{{ $zone['zone'] }}"
                                                    {{ old('timezone', $active_timezone) == $zone['zone'] ? 'selected' : '' }}>
                                                    {{ $zone['diff_from_GMT'] . ' - ' . $zone['zone'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Sale and Quotation without stock')}} *</label><br>
                                        @if($lims_general_setting_data->without_stock == 'yes')
                                        <label class="radio-inline">
                                            <input type="radio" name="without_stock" value="yes" checked> {{__('db.Yes')}}
                                        </label>
                                        <label class="radio-inline">
                                          <input type="radio" name="without_stock" value="no"> {{__('db.No')}}
                                        </label>
                                        @else
                                        <label class="radio-inline">
                                            <input type="radio" name="without_stock" value="yes"> {{__('db.Yes')}}
                                        </label>
                                        <label class="radio-inline">
                                          <input type="radio" name="without_stock" value="no" checked> {{__('db.No')}}
                                        </label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Packing Slip to manage orders/sales')}} *</label><br>
                                        @if($lims_general_setting_data->is_packing_slip)
                                        <label class="radio-inline">
                                            <input type="radio" name="is_packing_slip" value="1" checked> {{__('db.Enable')}}
                                        </label>
                                        <label class="radio-inline">
                                          <input type="radio" name="is_packing_slip" value="0"> {{__('db.Disable')}}
                                        </label>
                                        @else
                                        <label class="radio-inline">
                                            <input type="radio" name="is_packing_slip" value="1"> {{__('db.Enable')}}
                                        </label>
                                        <label class="radio-inline">
                                          <input type="radio" name="is_packing_slip" value="0" checked> {{__('db.Disable')}}
                                        </label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Currency')}} * <x-info title="You can add new currencies from the settings > currency. Exchange rate for default currency must be 1" /></label>
                                        <select name="currency" class="form-control" required>
                                            @foreach($lims_currency_list as $key => $currency)
                                                @if($lims_general_setting_data->currency == $currency->id)
                                                    <option value="{{$currency->id}}" selected>{{$currency->name}}</option>
                                                @else
                                                    <option value="{{$currency->id}}">{{$currency->name}}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Currency Position')}} *</label><br>
                                        @if($lims_general_setting_data->currency_position == 'prefix')
                                        <label class="radio-inline">
                                            <input type="radio" name="currency_position" value="prefix" checked> {{__('db.Prefix')}}
                                        </label>
                                        <label class="radio-inline">
                                          <input type="radio" name="currency_position" value="suffix"> {{__('db.Suffix')}}
                                        </label>
                                        @else
                                        <label class="radio-inline">
                                            <input type="radio" name="currency_position" value="prefix"> {{__('db.Prefix')}}
                                        </label>
                                        <label class="radio-inline">
                                          <input type="radio" name="currency_position" value="suffix" checked> {{__('db.Suffix')}}
                                        </label>
                                        @endif
                                    </div>
                                </div>

                                {{-- show Products Details in Purchase List --}}
                                 <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.show Products Details in Purchase List')}} *</label><br>
                                        @if(@$lims_general_setting_data->show_products_details_in_purchase_table == 1)
                                        <label class="radio-inline">
                                            <input type="radio" name="show_products_details_in_purchase_table" value="1" checked> {{__('db.show')}}
                                        </label>
                                        <label class="radio-inline">
                                          <input type="radio" name="show_products_details_in_purchase_table" value="0"> {{__('db.hide')}}
                                        </label>
                                        @else
                                        <label class="radio-inline">
                                            <input type="radio" name="show_products_details_in_purchase_table" value="1"> {{__('db.show')}}
                                        </label>
                                        <label class="radio-inline">
                                          <input type="radio" name="show_products_details_in_purchase_table" value="0" checked> {{__('db.hide')}}
                                        </label>
                                        @endif
                                    </div>
                                </div>


                                {{-- show Products Details in Sales List --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.show Products Details in Sales List')}} *</label><br>
                                        @if(@$lims_general_setting_data->show_products_details_in_sales_table == 1)
                                        <label class="radio-inline">
                                            <input type="radio" name="show_products_details_in_sales_table" value="1" checked> {{__('db.show')}}
                                        </label>
                                        <label class="radio-inline">
                                          <input type="radio" name="show_products_details_in_sales_table" value="0"> {{__('db.hide')}}
                                        </label>
                                        @else
                                        <label class="radio-inline">
                                            <input type="radio" name="show_products_details_in_sales_table" value="1"> {{__('db.show')}}
                                        </label>
                                        <label class="radio-inline">
                                          <input type="radio" name="show_products_details_in_sales_table" value="0" checked> {{__('db.hide')}}
                                        </label>
                                        @endif
                                    </div>
                                </div>


                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Digits after deciaml point')}}*</label>
                                        <input class="form-control" type="number" name="decimal" value="@if($lims_general_setting_data){{$lims_general_setting_data->decimal}}@endif" max="6" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4 d-none">
                                    <div class="form-group">
                                        <label>{{__('db.Theme')}} *</label>
                                        <div class="row ml-1">
                                            <div class="col-md-3 theme-option" data-color="default.css" style="background: #7c5cc4; min-height: 40px; max-width: 50px;" title="Purple"></div>&nbsp;&nbsp;
                                            <div class="col-md-3 theme-option" data-color="green.css" style="background: #1abc9c; min-height: 40px;max-width: 50px;" title="Green"></div>&nbsp;&nbsp;
                                            <div class="col-md-3 theme-option" data-color="blue.css" style="background: #3498db; min-height: 40px;max-width: 50px;" title="Blue"></div>&nbsp;&nbsp;
                                            <div class="col-md-3 theme-option" data-color="dark.css" style="background: #34495e; min-height: 40px;max-width: 50px;" title="Dark"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Staff Access')}} *</label>
                                        @if($lims_general_setting_data)
                                        <input type="hidden" name="staff_access_hidden" value="{{$lims_general_setting_data->staff_access}}">
                                        @endif
                                        <select name="staff_access" class="selectpicker form-control">
                                            <option value="all"> {{__('db.All Records')}}</option>
                                            <option value="own"> {{__('db.Own Records')}}</option>
                                            <option value="warehouse"> {{__('db.Warehouse Wise')}}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Invoice Format')}} *</label>
                                        @if($lims_general_setting_data)
                                        <input type="hidden" name="invoice_format_hidden" value="{{$lims_general_setting_data->invoice_format}}">
                                        @endif
                                        <select name="invoice_format" class="selectpicker form-control" required>
                                            <option value="standard">Standard</option>
                                            <option value="gst">Indian GST</option>
                                        </select>
                                    </div>
                                </div>
                                <div id="state" class="col-md-4 d-none">
                                    <div class="form-group">
                                        <label>{{__('db.State')}} *</label>
                                        @if($lims_general_setting_data)
                                        <input type="hidden" name="state_hidden" value="{{$lims_general_setting_data->state}}">
                                        @endif
                                        <select name="state" class="selectpicker form-control">
                                            <option value="1">Home State</option>
                                            <option value="2">Buyer State</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Date Format')}} *</label>
                                        @if($lims_general_setting_data)
                                        <input type="hidden" name="date_format_hidden" value="{{$lims_general_setting_data->date_format}}">
                                        @endif
                                        <select name="date_format" class="selectpicker form-control">
                                            <option value="d-m-Y"> dd-mm-yyy</option>
                                            <option value="d/m/Y"> dd/mm/yyy</option>
                                            <option value="d.m.Y"> dd.mm.yyy</option>
                                            <option value="m-d-Y"> mm-dd-yyy</option>
                                            <option value="m/d/Y"> mm/dd/yyy</option>
                                            <option value="m.d.Y"> mm.dd.yyy</option>
                                            <option value="Y-m-d"> yyy-mm-dd</option>
                                            <option value="Y/m/d"> yyy/mm/dd</option>
                                            <option value="Y.m.d"> yyy.mm.dd</option>
                                        </select>
                                    </div>
                                </div>


                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.show_expiry_alerts_before')}}
                                            <x-info title="Enter the number of days before a product's expiry to show the alert. For example, entering 7 will show alerts for products that will expire within the next 7 days." />
                                        </label>
                                        <input type="number" name="expiry_alert_days" class="form-control" value="{{$lims_general_setting_data->expiry_alert_days}}">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Developed By')}}</label>
                                        <input type="text" name="developed_by" class="form-control" value="{{$lims_general_setting_data->developed_by}}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.profit_margin_type')}}</label>
                                        <select name="margin_type" class="selectpicker form-control">
                                            <option value="0" {{ old('margin_type', $lims_general_setting_data->margin_type) == 0 ? 'selected' : '' }}>
                                                percentage
                                            </option>
                                            <option value="1" {{ old('margin_type', $lims_general_setting_data->margin_type) == 1 ? 'selected' : '' }}>
                                                flat
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Default Profit Margin Value')}} <x-info title="You can change it from add/edit product & purchase pages" type="info" /></label>
                                        <input type="number" class="form-control" name="default_margin_value" value="{{ $lims_general_setting_data->default_margin_value }}">
                                    </div>
                                </div>
                                <!-- <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Expiry Duration Type')}}</label>
                                        <select name="expiry_type" class="form-control">
                                                <option value="days" {{ $lims_general_setting_data->expiry_type == 'days' ? 'selected' : '' }}>Days</option>
                                                <option value="months" {{ $lims_general_setting_data->expiry_type == 'months' ? 'selected' : '' }}>Months</option>
                                                <option value="years" {{ $lims_general_setting_data->expiry_type == 'years' ? 'selected' : '' }}>Years</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Expiry Value')}}</label>
                                        <input type="number" class="form-control" name="expiry_value" value="{{ $lims_general_setting_data->expiry_value ?? '0' }}">
                                    </div>
                                </div> -->
                                <div class="col-md-4 mt-4">
                                    <div class="form-group mt-2">
                                        @if($lims_general_setting_data->disable_signup)
                                        <input type="checkbox" name="disable_signup" value="0" checked>
                                        @else
                                        <input type="checkbox" name="disable_signup" value="1" />
                                        @endif
                                        &nbsp;
                                        <label>{{__('db.Disable registration')}}</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mt-4">
                                    <div class="form-group mt-2">
                                        @if($lims_general_setting_data->disable_forgot_password)
                                        <input type="checkbox" name="disable_forgot_password" value="1" checked>
                                        @else
                                        <input type="checkbox" name="disable_forgot_password" value="1" />
                                        @endif
                                        &nbsp;
                                        <label>{{__('db.Disable password reset')}}</label>
                                    </div>
                                </div>

                                <div class="col-md-4 mt-4">
                                    <div class="form-group mt-2">
                                        <input type="checkbox" id="maintenance_mode" {{ $lims_general_setting_data->maintenance_allowed_ips ? 'checked' : '' }}>
                                        &nbsp;
                                        <label>{{__('db.maintenance_mode')}}</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mt-4 {{ $lims_general_setting_data->maintenance_allowed_ips ? '' : 'd-none' }}">
                                    <div class="form-group mt-2">
                                        <label>{{__('db.maintenance_allowed_ips')}}  <x-info title="you'll be able to access admin panel from these IPs in maintenance mode" type="info" /></label>
                                        <input type="text" id="maintenance_allowed_ips" name="maintenance_allowed_ips" class="form-control" value="{{ old('maintenance_allowed_ips', $lims_general_setting_data->maintenance_allowed_ips) }}" placeholder="127.0.0.1, 127.0.0.2">
                                    </div>
                                </div>

                                <div class="col-md-12 mt-2">
                                    <div class="form-group">
                                        <label>{{__('db.Font CSS')}} <x-info title="If you you want to change font, you have to put your font css files here" type="info" /></label>
                                        <textarea class="form-control" name="font_css" rows="4">{{ $lims_general_setting_data->font_css }}</textarea>
                                    </div>
                                </div>

                                <div class="col-md-12 mt-2">
                                    <div class="form-group">
                                        <label>{{__('db.CSS for auth pages (login/registration/forgot password/verification)')}} <x-info title="If you you want to change style on login/registration pages, you have to put your css files here" type="info" /></label>
                                        <textarea class="form-control" name="auth_css" rows="4">{{ $lims_general_setting_data->auth_css }}</textarea>
                                    </div>
                                </div>

                                <div class="col-md-12 mt-2">
                                    <div class="form-group">
                                        <label>{{__('db.POS page CSS')}} <x-info title="If you you want to change style on POS page, you have to put your css files here" type="info" /></label>
                                        <textarea class="form-control" name="pos_css" rows="4">{{ $lims_general_setting_data->pos_css }}</textarea>
                                    </div>
                                </div>

                                <div class="col-md-12 mt-2">
                                    <div class="form-group">
                                        <label>{{__('db.Custom CSS/Styles')}} <x-info title="If you you want to change style on pages except auth and POS page, you have to put your css files here" type="info" /></label>
                                        <textarea class="form-control" name="custom_css" rows="4">{{ $lims_general_setting_data->custom_css }}</textarea>
                                    </div>
                                </div>

                                @if(config('database.connections.saleprosaas_landlord'))
                                    <br>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{__('db.Subscription Type')}}</label>
                                            <p>{{$lims_general_setting_data->subscription_type}}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{__('db.Package Name')}}</label>
                                            <p id="package-name"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{__('db.Monthly Fee')}}</label>
                                            <p id="monthly-fee"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{__('db.Yearly Fee')}}</label>
                                            <p id="yearly-fee"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{__('db.Number of Warehouses')}}</label>
                                            <p id="number-of-warehouse"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{__('db.Number of Products')}}</label>
                                            <p id="number-of-product"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{__('db.Number of Invoices')}}</label>
                                            <p id="number-of-invoice"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{__('db.Number of User Account')}}</label>
                                            <p id="number-of-user-account"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{__('db.Number of Employees')}}</label>
                                            <p id="number-of-employee"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{__('db.Subscription Ends at')}}</label>
                                            <p>{{date($lims_general_setting_data->date_format, strtotime($lims_general_setting_data->expiry_date))}}</p>
                                        </div>
                                    </div>

                                @endif
                            </div>
                            <div class="form-group mt-3">
                                <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#setting").siblings('a').attr('aria-expanded','true');
    $("ul#setting").addClass("show");
    $("ul#setting #general-setting-menu").addClass("active");

    $("select[name=invoice_format]").on("change", function (argument) {
        if($(this).val() == 'standard') {
            $("#state").addClass('d-none');
            $("input[name=state]").prop("required", false);
        }
        else if($(this).val() == 'gst') {
            $("#state").removeClass('d-none');
            $("input[name=state]").prop("required", true);
        }
    })
    if($("input[name='timezone_hidden']").val()){
        $('select[name=timezone]').val($("input[name='timezone_hidden']").val());
        $('select[name=staff_access]').val($("input[name='staff_access_hidden']").val());
        $('select[name=date_format]').val($("input[name='date_format_hidden']").val());
        $('select[name=invoice_format]').val($("input[name='invoice_format_hidden']").val());
        if($("input[name='invoice_format_hidden']").val() == 'gst') {
            $('select[name=state]').val($("input[name='state_hidden']").val());
            $("#state").removeClass('d-none');
        }
        $('.selectpicker').selectpicker('refresh');
    }

    $('#maintenance_mode').on('change', function () {
        if ($(this).is(':checked')) {
            $('#maintenance_allowed_ips')
                .closest('.col-md-4')
                .removeClass('d-none');

            $('#maintenance_allowed_ips').prop('required', true);
        } else {
            $('#maintenance_allowed_ips')
                .closest('.col-md-4')
                .addClass('d-none');

            $('#maintenance_allowed_ips')
                .prop('required', false)
                .val('');
        }
    });

    $('.theme-option').on('click', function() {
        $.get('general_setting/change-theme/' + $(this).data('color'), function(data) {
        });
        var style_link= $('#custom-style').attr('href').replace(/([^-]*)$/, $(this).data('color') );
        $('#custom-style').attr('href', style_link);
    });

    @if(config('database.connections.saleprosaas_landlord'))
        $.ajax({
            type: 'GET',
            async: false,
            url: '{{route("package.fetchData", $lims_general_setting_data->package_id)}}',
            success: function(data) {
                $("#package-name").text(data['name']);
                $("#monthly-fee").text(data['monthly_fee']);
                $("#yearly-fee").text(data['yearly_fee']);
                $("#package-name").text(data['name']);

                if(data['number_of_warehouse'])
                    $("#number-of-warehouse").text(data['number_of_warehouse']);
                else
                    $("#number-of-warehouse").text('Unlimited');

                if(data['number_of_product'])
                    $("#number-of-product").text(data['number_of_product']);
                else
                    $("#number-of-product").text('Unlimited');

                if(data['number_of_invoice'])
                    $("#number-of-invoice").text(data['number_of_invoice']);
                else
                    $("#number-of-invoice").text('Unlimited');

                if(data['number_of_user_account'])
                    $("#number-of-user-account").text(data['number_of_user_account']);
                else
                    $("#number-of-user-account").text('Unlimited');

                if(data['number_of_employee'])
                    $("#number-of-employee").text(data['number_of_employee']);
                else
                    $("#number-of-employee").text('Unlimited');
            }
        });
    @endif
</script>

<script>
    document.getElementById('site_logo').addEventListener('change', function () {
        const file = this.files[0];
        const errorEl = document.getElementById('logo_error');
        errorEl.textContent = '';

        if (!file) return;

        // Allowed types
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        const maxSize = 5 * 1024 * 1024; // 5 MB

        if (!allowedTypes.includes(file.type)) {
            errorEl.textContent = 'Only JPG, PNG, or GIF images are allowed.';
            this.value = '';
            return;
        }

        if (file.size > maxSize) {
            errorEl.textContent = 'File size must be less than 5 MB.';
            this.value = '';
            return;
        }
    });
</script>
@endpush
