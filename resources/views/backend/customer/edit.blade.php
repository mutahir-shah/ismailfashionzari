@extends('backend.layout.main') @section('content')

@push('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.9/css/intlTelInput.css"/>
<style>
.country-phone-group .bootstrap-select{
    display: none !important
}
</style>
@endpush

<x-error-message key="not_permitted" />

<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.Update Customer')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                        <form action="{{ route('customer.update', $lims_customer_data->id) }}" method="POST" enctype="multipart/form-data" id="customer_form">
                            @csrf
                            @method('PUT')
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <input type="hidden" name="customer_group" value="{{$lims_customer_data->customer_group_id}}">
                                    <label>{{__('db.Customer Group')}} *</strong> </label>
                                    <select required class="form-control selectpicker" name="customer_group_id">
                                        @foreach($lims_customer_group_all as $customer_group)
                                            <option value="{{$customer_group->id}}">{{$customer_group->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.name')}} *</strong> </label>
                                    <input type="text" name="customer_name" value="{{$lims_customer_data->name}}" required class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Company Name')}} </label>
                                    <input type="text" name="company_name" value="{{$lims_customer_data->company_name}}" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.email')}}</label>
                                    <input type="email" name="email" value="{{$lims_customer_data->email}}" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('db.Type') }}</label>
                                    <select name="type" class="form-control">
                                        @foreach(\App\Enums\CustomerTypeEnum::cases() as $case)
                                            <option value="{{ $case->value }}" {{ $lims_customer_data->type === $case->value ? 'selected' : '' }}>
                                                {{ $case->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Phone Number')}} *</label>
                                    <input type="text" name="phone_number" required value="{{$lims_customer_data->phone_number}}" class="form-control">
                                    @if($errors->has('phone_number'))
                                   <span>
                                       <strong>{{ $errors->first('phone_number') }}</strong>
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="country-phone-group form-group">
                                    <label>{{__('db.WhatsApp Number')}}</label>
                                    <div class="d-flex">
                                        <select id="country_code" name="country_code" class="form-control w-auto me-2">
                                        </select>
                                        <input type="tel" id="wa_number" class="form-control" value="{{$lims_customer_data->phone_number}}">
                                        <input type="hidden" id="full_phone" name="wa_number"  value="{{$lims_customer_data->phone_number}}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Tax Number')}}</label>
                                    <input type="text" name="tax_no" class="form-control" value="{{$lims_customer_data->tax_no}}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Address')}}</label>
                                    <input type="text" name="address" value="{{$lims_customer_data->address}}" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.City')}}</label>
                                    <input type="text" name="city" value="{{$lims_customer_data->city}}" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.State')}}</label>
                                    <input type="text" name="state" value="{{$lims_customer_data->state}}" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Postal Code')}}</label>
                                    <input type="text" name="postal_code" value="{{$lims_customer_data->postal_code}}" class="form-control">
                                </div>
                            </div>
                            @if(!$lims_customer_data->user_id)
                            <div class="col-md-4 mt-3">
                                <div class="form-group">
                                    <label>{{__('db.Add User')}}</label>&nbsp;
                                    <input type="checkbox" name="user" value="1" />
                                </div>
                            </div>
                            @endif
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Country')}}</label>
                                    <input type="text" name="country" value="{{$lims_customer_data->country}}" class="form-control">
                                </div>
                            </div>
                            @if(\Illuminate\Support\Facades\Schema::hasColumn('customers', 'zatca_registration_scheme'))
                            <div class="col-12"><hr><h5 class="mb-1">ZATCA Phase 2 buyer details</h5><p class="text-muted">Complete these fields for Saudi business customers (standard B2B invoices). Leave them empty for retail customers.</p></div>
                            <div class="col-md-4"><div class="form-group"><label>Buyer ID type</label><select name="zatca_registration_scheme" class="form-control"><option value="">Select</option>@foreach(['CRN' => 'Commercial Registration', '700' => 'Unified 700 Number', 'NAT' => 'National ID', 'IQA' => 'Iqama', 'TIN' => 'Tax ID', 'PAS' => 'Passport', 'GCC' => 'GCC ID', 'OTH' => 'Other'] as $code => $label)<option value="{{ $code }}" {{ old('zatca_registration_scheme', $lims_customer_data->zatca_registration_scheme) === $code ? 'selected' : '' }}>{{ $label }} ({{ $code }})</option>@endforeach</select></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Buyer ID number</label><input type="text" name="zatca_registration_number" value="{{ old('zatca_registration_number', $lims_customer_data->zatca_registration_number) }}" maxlength="20" class="form-control"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Building number</label><input type="text" name="zatca_building_number" value="{{ old('zatca_building_number', $lims_customer_data->zatca_building_number) }}" pattern="[0-9]{4}" maxlength="4" class="form-control" placeholder="4 digits"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Additional number</label><input type="text" name="zatca_additional_number" value="{{ old('zatca_additional_number', $lims_customer_data->zatca_additional_number) }}" pattern="[0-9]{4}" maxlength="4" class="form-control" placeholder="4 digits"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>District</label><input type="text" name="zatca_district" value="{{ old('zatca_district', $lims_customer_data->zatca_district) }}" class="form-control"></div></div>
                            @endif
                            <div class="col-md-4 customer-credit-field">
                                <div class="form-group">
                                    <label>{{__('db.Credit Limit')}} <x-info title="Leave it blank for unlimited credit" type="info" /></label>
                                    <input type="number" name="credit_limit" class="form-control" value="{{$lims_customer_data->credit_limit}}" step="any" min="0">
                                </div>
                            </div>
                            <div class="col-md-4 customer-credit-field">
                                <div class="form-group">
                                    <label>{{ __('db.payment_term') }}</label>
                                    <div class="d-flex">
                                        <input type="number" name="pay_term_no" class="form-control" placeholder="e.g. 30" value="{{ $lims_customer_data->pay_term_no }}">
                                        <select name="pay_term_period" class="form-control ml-2">
                                            <option value="days" {{ $lims_customer_data->pay_term_period == 'days' ? 'selected' : '' }}>{{ __('db.Days') }}</option>
                                            <option value="months"  {{ $lims_customer_data->pay_term_period == 'months' ? 'selected' : '' }}>{{ __('db.Months') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            @foreach($custom_fields as $field)
                                <?php $field_name = str_replace(' ', '_', strtolower($field->name)); ?>
                                @if(!$field->is_admin || \Auth::user()->role_id == 1)
                                    <div class="{{'col-md-'.$field->grid_value}}">
                                        <div class="form-group">
                                            <label>{{$field->name}}</label>
                                            @if($field->type == 'text')
                                                <input type="text" name="{{$field_name}}" value="{{$lims_customer_data->$field_name}}" class="form-control" @if($field->is_required){{'required'}}@endif>
                                            @elseif($field->type == 'number')
                                                <input type="number" name="{{$field_name}}" value="{{$lims_customer_data->$field_name}}" class="form-control" @if($field->is_required){{'required'}}@endif>
                                            @elseif($field->type == 'textarea')
                                                <textarea rows="5" name="{{$field_name}}" value="{{$lims_customer_data->$field_name}}" class="form-control" @if($field->is_required){{'required'}}@endif></textarea>
                                            @elseif($field->type == 'checkbox')
                                                <br>
                                                <?php
                                                $option_values = explode(",", $field->option_value);
                                                $field_values =  explode(",", $lims_customer_data->$field_name);
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
                                                        <input type="radio" name="{{$field_name}}" value="{{$value}}" @if($value == $lims_customer_data->$field_name){{'checked'}}@endif @if($field->is_required){{'required'}}@endif> {{$value}}
                                                    </label>
                                                    &nbsp;
                                                @endforeach
                                            @elseif($field->type == 'select')
                                                <?php $option_values = explode(",", $field->option_value); ?>
                                                <select class="form-control" name="{{$field_name}}" @if($field->is_required){{'required'}}@endif>
                                                    @foreach($option_values as $value)
                                                        <option value="{{$value}}" @if($value == $lims_customer_data->$field_name){{'selected'}}@endif>{{$value}}</option>
                                                    @endforeach
                                                </select>
                                            @elseif($field->type == 'multi_select')
                                                <?php
                                                $option_values = explode(",", $field->option_value);
                                                $field_values =  explode(",", $lims_customer_data->$field_name);
                                                ?>
                                                <select class="form-control" name="{{$field_name}}[]" @if($field->is_required){{'required'}}@endif multiple>
                                                    @foreach($option_values as $value)
                                                        <option value="{{$value}}" @if(in_array($value, $field_values)) selected @endif>{{$value}}</option>
                                                    @endforeach
                                                </select>
                                            @elseif($field->type == 'date_picker')
                                                <input type="text" name="{{$field_name}}" value="{{$lims_customer_data->$field_name}}" class="form-control date" @if($field->is_required){{'required'}}@endif>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                            <div class="col-md-4 user-input">
                                <div class="form-group">
                                    <label>{{__('db.username')}} *</label>
                                    <input type="text" name="name" class="form-control">
                                    @if($errors->has('name'))
                                   <span>
                                       <strong>{{ $errors->first('name') }}</strong>
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4 user-input">
                                <div class="form-group">
                                    <label>{{__('db.Password')}} *</label>
                                    <input type="password" name="password" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group mt-3">
                                    <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
                                </div>
                            </div>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
<script>
    const input = document.querySelector("#wa_number");
    window.intlTelInput(input, {
        initialCountry: "auto",
        geoIpLookup: function (callback) {
        fetch("https://ipapi.co/json")
            .then((res) => res.json())
            .then((data) => callback(data.country_code))
            .catch(() => callback("us"));
        },
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js"
    });

    $("#customer_form").submit(function(e) {
        e.preventDefault();
        var iti = window.intlTelInputGlobals.getInstance(input);
        var full_number = iti.getNumber();
        $('#full_phone').val(full_number);
        $(this).off('submit').submit();
    });
</script>

<script type="text/javascript">

    $("ul#people").siblings('a').attr('aria-expanded','true');
    $("ul#people").addClass("show");

    $(".user-input").hide();

    $('input[name="user"]').on('change', function() {
        if ($(this).is(':checked')) {
            $('.user-input').show(300);
            $('input[name="name"]').prop('required',true);
            $('input[name="password"]').prop('required',true);
        }
        else{
            $('.user-input').hide(300);
            $('input[name="name"]').prop('required',false);
            $('input[name="password"]').prop('required',false);
        }
    });

    var customer_group = $("input[name='customer_group']").val();
    $('select[name=customer_group_id]').val(customer_group);

    function toggleCreditFields() {
        var customerType = $('select[name="type"]').val();
        if (customerType === 'walkin') {
            $('.customer-credit-field').hide();
            $('input[name="credit_limit"]').val('0');
            $('input[name="pay_term_no"]').val('');
            $('select[name="pay_term_period"]').val('days');
        } else {
            $('.customer-credit-field').show();
        }
    }

    $('select[name="type"]').on('change', function() {
        toggleCreditFields();
    });

    toggleCreditFields();
</script>
@endpush
