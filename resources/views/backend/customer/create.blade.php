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
                        <h4>{{__('db.Add Customer')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                        <form action="{{ route('customer.store') }}" method="POST" enctype="multipart/form-data" id="customer_form">
                            @csrf
                        <div class="row">
                            <div class="col-md-4 mt-4">
                                <div class="form-group">
                                    <input type="checkbox" name="both" value="1" />&nbsp;
                                    <label>{{__('db.Both Customer and Supplier')}}</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Customer Group')}} *</strong> </label>
                                    <select required class="form-control selectpicker" id="customer-group-id" name="customer_group_id">
                                        @foreach($lims_customer_group_all as $customer_group)
                                            <option value="{{$customer_group->id}}">{{$customer_group->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.name')}} *</strong> </label>
                                    <input type="text" id="name" name="customer_name" required class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Company Name')}} <span class="asterisk">*</span></label>
                                    <input type="text" name="company_name" class="form-control">
                                    @if($errors->has('company_name'))
                                   <span>
                                       <strong>{{ $errors->first('company_name') }}</strong>
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.email')}} <span class="asterisk">*</span></label>
                                    <input type="email" name="email" placeholder="example@example.com" class="form-control">
                                    @if($errors->has('email'))
                                   <span>
                                       <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('db.Type') }}</label>
                                    <select name="type" class="form-control">
                                        @foreach(\App\Enums\CustomerTypeEnum::cases() as $case)
                                            <option value="{{ $case->value }}" {{ old('type', 'regular') === $case->value ? 'selected' : '' }}>
                                                {{ $case->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Phone Number')}} *</label>
                                    <input type="text" name="phone_number" required class="form-control">
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
                                        <input type="tel" id="wa_number" class="form-control">
                                        <input type="hidden" id="full_phone" name="wa_number">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Tax Number')}}</label>
                                    <input type="text" name="tax_no" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Address')}} <span class="asterisk">*</span></label>
                                    <input type="text" name="address" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.City')}} <span class="asterisk">*</span></label>
                                    <input type="text" name="city" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.State')}}</label>
                                    <input type="text" name="state" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Postal Code')}}</label>
                                    <input type="text" name="postal_code" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Country')}}</label>
                                    <input type="text" name="country" class="form-control">
                                </div>
                            </div>
                            @if(\Illuminate\Support\Facades\Schema::hasColumn('customers', 'zatca_registration_scheme'))
                            <div class="col-12"><hr><h5 class="mb-1">ZATCA Phase 2 buyer details</h5><p class="text-muted">Complete these fields for Saudi business customers (standard B2B invoices). Leave them empty for retail customers.</p></div>
                            <div class="col-md-4"><div class="form-group"><label>Buyer ID type</label><select name="zatca_registration_scheme" class="form-control"><option value="">Select</option>@foreach(['CRN' => 'Commercial Registration', '700' => 'Unified 700 Number', 'NAT' => 'National ID', 'IQA' => 'Iqama', 'TIN' => 'Tax ID', 'PAS' => 'Passport', 'GCC' => 'GCC ID', 'OTH' => 'Other'] as $code => $label)<option value="{{ $code }}" {{ old('zatca_registration_scheme') === $code ? 'selected' : '' }}>{{ $label }} ({{ $code }})</option>@endforeach</select></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Buyer ID number</label><input type="text" name="zatca_registration_number" value="{{ old('zatca_registration_number') }}" maxlength="20" class="form-control"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Building number</label><input type="text" name="zatca_building_number" value="{{ old('zatca_building_number') }}" pattern="[0-9]{4}" maxlength="4" class="form-control" placeholder="4 digits"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>Additional number</label><input type="text" name="zatca_additional_number" value="{{ old('zatca_additional_number') }}" pattern="[0-9]{4}" maxlength="4" class="form-control" placeholder="4 digits"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>District</label><input type="text" name="zatca_district" value="{{ old('zatca_district') }}" class="form-control"></div></div>
                            @endif
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Opening balance')}} ({{__('db.Due')}}) <x-info title="Add Customer's old due amount here. If customer has new dues, they will be added to this amount and total dues will show on customer list" type="info" /></label>
                                    <input type="number" name="opening_balance" class="form-control" value="0" step="any" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Initial Deposit')}}  <x-info title="Customer can pay with deposit amount you set here" type="info" /></label>
                                    <input type="number" name="deposit" value="0" class="form-control" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-4 customer-credit-field">
                                <div class="form-group">
                                    <label>{{__('db.Credit Limit')}} <x-info title="Leave it blank for unlimited credit" type="info" /></label>
                                    <input type="number" name="credit_limit" class="form-control" value="0" step="any" min="0">
                                </div>
                            </div>
                            <div class="col-md-4 customer-credit-field">
                                <div class="form-group">
                                    <label>{{ __('db.payment_term') }}</label>
                                    <div class="d-flex">
                                        <input type="number" name="pay_term_no" class="form-control" placeholder="e.g. 30" value="{{ old('pay_term_no') }}">
                                        <select name="pay_term_period" class="form-control ml-2">
                                            <option value="days">{{ __('db.Days') }}</option>
                                            <option value="months">{{ __('db.Months') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
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
                        <div class="row">
                            <div class="col-md-4 mt-4">
                                <div class="form-group">
                                    <input type="checkbox" name="user" value="1" />&nbsp;
                                    <label>{{__('db.Add User')}}  <x-info title="If checked, customer will be able to login with username and password you set" type="info" /></label>
                                </div>
                            </div>
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
                        </div>
                        <div class="form-group">
                            <input type="hidden" name="pos" value="0">
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
    $("ul#people #customer-create-menu").addClass("active");

    $('.asterisk').hide();
    $(".user-input").hide();

    $('input[name="both"]').on('change', function() {
        if ($(this).is(':checked')) {
            $('.asterisk').show();
            $('input[name="company_name"]').prop('required',true);
            $('input[name="email"]').prop('required',true);
            $('input[name="address"]').prop('required',true);
            $('input[name="city"]').prop('required',true);
        }
        else{
            $('.asterisk').hide();
            $('input[name="company_name"]').prop('required',false);
            $('input[name="email"]').prop('required',false);
            $('input[name="address"]').prop('required',false);
            $('input[name="city"]').prop('required',false);
        }
    });

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
