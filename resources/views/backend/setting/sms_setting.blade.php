@extends('backend.layout.main') @section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.SMS Setting')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                        <form action="{{ route('setting.smsStore') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <input type="hidden" name="type" value="sms">
                                        <input type="hidden" id="smsId" name="sms_id">
                                        @if($revesms)<input type="hidden" id="revesmsId" value="{{ $revesms['sms_id'] }}">@endif
                                        @if($bdbulksms)<input type="hidden" id="bdbulksmsId" value="{{ $revesms['sms_id'] }}">@endif
                                        @if($tonkra)<input type="hidden" id="tonkraId" value="{{ $tonkra['sms_id'] }}">@endif
                                        @if($twilio)<input type="hidden" id="twilioId" value="{{ $twilio['sms_id'] }}">@endif
                                        @if($clickatell)<input type="hidden" id="clickatellId" value="{{ $clickatell['sms_id'] ?? '' }}">@endif
                                        @if($zircon)<input type="hidden" id="zirconId" value="{{ $zircon['sms_id'] ?? '' }}">@endif
                                        @if($custom_http)<input type="hidden" id="customHttpId" value="{{ $custom_http['sms_id'] ?? '' }}">@endif
                                        <input type="hidden" name="gateway_hidden" value="">
                                        <label>{{__('db.Gateway')}} *</label>
                                        <select class="form-control" name="gateway">
                                            <option selected disabled>{{__('db.Select SMS gateway')}}</option>
                                            @if($revesms)<option value="revesms" data-active="{{ $revesms['active'] }}" {{ $revesms['active'] == true ? 'selected' : '' }}>revesms</option>@endif
                                            @if($bdbulksms)<option value="bdbulksms" data-active="{{ $bdbulksms['active'] }}" {{ $bdbulksms['active'] == true ? 'selected' : '' }}>bdbulksms</option>@endif
                                            @if($tonkra)<option value="tonkra" data-active="{{ $tonkra['active'] }}" {{ $tonkra['active'] == true ? 'selected' : '' }}>Tonkra</option>@endif
                                            @if($twilio)<option value="twilio" data-active="{{ $twilio['active'] }}" {{ $twilio['active'] == true ? 'selected' : '' }}>Twilio</option>@endif
                                            @if($clickatell)<option value="clickatell" data-active="{{ $clickatell['active'] }}" {{ $clickatell['active'] == true ? 'selected' : '' }}>Clickatell</option>@endif
                                            @if($zircon)<option value="zircon" data-active="{{ $zircon['active'] }}" {{ $zircon['active'] == true ? 'selected' : '' }}>Zircon</option>@endif
                                            @if($custom_http)<option value="custom_http" data-active="{{ $custom_http['active'] }}" {{ $custom_http['active'] == true ? 'selected' : '' }}>Custom HTTP Gateway</option>@endif
                                        </select>
                                    </div>
                                    <div class="form-group bdbulksms">
                                        <label>Token *</label>
                                        <input type="text" name="token" class="form-control bdbulksms-option" value="{{ $bdbulksms['token'] }}" />
                                    </div>
                                    <div class="form-group revesms">
                                        <label>API Key *</label>
                                        <input type="text" name="apikey" class="form-control revesms-option" value="{{ $revesms['apikey'] }}" />
                                    </div>
                                    <div class="form-group revesms">
                                        <label>Secret Key *</label>
                                        <input type="text" name="secretkey" class="form-control revesms-option" value="{{ $revesms['secretkey'] }}" />
                                    </div>
                                    <div class="form-group revesms">
                                        <label>Caller ID *</label>
                                        <input type="text" name="callerID" class="form-control revesms-option" value="{{ $revesms['callerID'] }}" />
                                    </div>
                                    <div class="form-group tonkra">
                                        <label>API Token *</label>
                                        <input type="text" name="api_token" class="form-control tonkra-option" value="{{ $tonkra['api_token'] }}" />
                                    </div>
                                    <div class="form-group tonkra">
                                        <label>Sender ID *</label>
                                        <input type="text" name="sender_id" class="form-control tonkra-option" value="{{ $tonkra['sender_id']  }}" />
                                    </div>
                                    <div class="form-group twilio">
                                        <label>ACCOUNT SID *</label>
                                        <input type="text" name="account_sid" class="form-control twilio-option" value="{{ $twilio['account_sid'] ?? '' }}" />
                                    </div>
                                    <div class="form-group twilio">
                                        <label>AUTH TOKEN *</label>
                                        <input type="text" name="auth_token" class="form-control twilio-option" value="{{  $twilio['auth_token'] ?? '' }}" />
                                    </div>
                                    <div class="form-group twilio">
                                        <label>Twilio Number *</label>
                                        <input type="text" name="twilio_number" class="form-control twilio-option" value="{{  $twilio['twilio_number'] ?? '' }}" />
                                    </div>
                                    <div class="form-group clickatell">
                                        <label>API Key *</label>
                                        <input type="text" name="api_key" class="form-control clickatell-option" value="{{  $clickatell['api_key'] ?? '' }}" />
                                    </div>
                                    <div class="form-group zircon">
                                        <label>User ID *</label>
                                        <input type="text" name="user_id" class="form-control zircon-option" value="{{ $zircon['user_id'] ?? '' }}" />
                                    </div>
                                    <div class="form-group zircon">
                                        <label>API Key *</label>
                                        <input type="text" name="api_key" class="form-control zircon-option" value="{{ $zircon['api_key'] ?? '' }}" />
                                    </div>
                                    <div class="form-group zircon">
                                        <label>Sender ID *</label>
                                        <input type="text" name="sender_id" class="form-control zircon-option" value="{{ $zircon['sender_id'] ?? '' }}" />
                                    </div>
                                    <div class="form-group custom_http">
                                        <label>Request Method *</label>
                                        <select name="method" class="form-control custom_http-option">
                                            <option value="POST" {{ ($custom_http['method'] ?? 'POST') == 'POST' ? 'selected' : '' }}>POST</option>
                                            <option value="GET" {{ ($custom_http['method'] ?? '') == 'GET' ? 'selected' : '' }}>GET</option>
                                        </select>
                                    </div>
                                    <div class="form-group custom_http">
                                        <label>API URL *</label>
                                        <input type="text" name="api_url" class="form-control custom_http-option" value="{{ $custom_http['api_url'] ?? '' }}" />
                                    </div>
                                    <div class="form-group custom_http">
                                        <label>Headers (JSON)</label>
                                        <textarea name="headers" class="form-control" rows="4" placeholder='{
    "Authorization": "Bearer YOUR_API_KEY",
    "Content-Type": "application/json"
}'>{{ $custom_http['headers'] ?? '' }}</textarea>
                                    </div>
                                    <div class="form-group custom_http">
                                        <label>Body Template (JSON / Query String) *</label>
                                        <textarea name="body_template" class="form-control custom_http-option" rows="5" placeholder='{
    "to": "{phone}",
    "message": "{message}",
    "sender_id": "SALEPRO"
}'>{{ $custom_http['body_template'] ?? '' }}</textarea>
                                        <small>Placeholders: {phone}, {message}</small>
                                    </div>
                                    <div class="form-group">
                                        <input class="mt-2 default" type="checkbox" name="active" value="1">
                                        <label class="mt-2"><strong>{{__('db.Default')}}</strong></label>
                                    </div>
                                    <div class="form-group">
                                        <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
                                        <button type="button" class="btn btn-info custom_http" id="test-custom-http-btn">Test SMS</button>
                                        <a href="https://sms.tonkra.com/account/top-up" type="button" target="_blank" class="btn btn-secondary tonkra">{{ __('db.Top Up') }}</a>
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
<script type="text/javascript">
    $("ul#setting").siblings('a').attr('aria-expanded', 'true');
    $("ul#setting").addClass("show");
    $("ul#setting #sms-setting-menu").addClass("active");

    $(document).ready(function() {
        var selectedOption = $(this).find(':selected').val();
        if (selectedOption == 'twilio') {
            $('select[name="gateway"]').val('twilio');
            $('.clickatell').hide();
            $('.tonkra').hide();
            $('.revesms').hide();
            $('.bdbulksms').hide();
            $('.zircon').hide();
            $('.custom_http').hide();
            var dataAtive = $(this).find(':selected').data('active');
            dataAtive == true ? $(".default").prop("checked", true) : $(".default").prop("checked", false);
        }
        if (selectedOption == 'revesms') {
            $('select[name="gateway"]').val('revesms');
            $('.clickatell').hide();
            $('.tonkra').hide();
            $('.bdbulksms').hide();
            $('.twilio').hide();
            $('.zircon').hide();
            $('.custom_http').hide();
            var dataAtive = $(this).find(':selected').data('active');
            dataAtive == true ? $(".default").prop("checked", true) : $(".default").prop("checked", false);
        } else if (selectedOption == 'clickatell') {
            $('select[name="gateway"]').val('clickatell');
            $('.twilio').hide();
            $('.tonkra').hide();
            $('.revesms').hide();
            $('.bdbulksms').hide();
            $('.zircon').hide();
            $('.custom_http').hide();
            var dataAtive = $(this).find(':selected').data('active');
            dataAtive == true ? $(".default").prop("checked", true) : $(".default").prop("checked", false);
        } else if (selectedOption == 'tonkra') {
            $('select[name="gateway"]').val('tonkra');
            $('.twilio').hide();
            $('.clickatell').hide();
            $('.revesms').hide();
            $('.bdbulksms').hide();
            $('.zircon').hide();
            $('.custom_http').hide();
            var dataAtive = $(this).find(':selected').data('active');
            dataAtive == true ? $(".default").prop("checked", true) : $(".default").prop("checked", false);
        } else if (selectedOption == 'bdbulksms') {
            $('select[name="gateway"]').val('bdbulksms');
            $('.twilio').hide();
            $('.clickatell').hide();
            $('.revesms').hide();
            $('.tonkra').hide();
            $('.zircon').hide();
            $('.custom_http').hide();
            var dataAtive = $(this).find(':selected').data('active');
            dataAtive == true ? $(".default").prop("checked", true) : $(".default").prop("checked", false);
        } else if (selectedOption == 'zircon') {
            $('select[name="gateway"]').val('zircon');
            $('.twilio').hide();
            $('.clickatell').hide();
            $('.revesms').hide();
            $('.tonkra').hide();
            $('.bdbulksms').hide();
            $('.custom_http').hide();
            var dataAtive = $(this).find(':selected').data('active');
            dataAtive == true ? $(".default").prop("checked", true) : $(".default").prop("checked", false);
        } else if (selectedOption == 'custom_http') {
            $('select[name="gateway"]').val('custom_http');
            $('.twilio').hide();
            $('.clickatell').hide();
            $('.revesms').hide();
            $('.tonkra').hide();
            $('.bdbulksms').hide();
            $('.zircon').hide();
            $('.custom_http').show(500);
            var dataAtive = $(this).find(':selected').data('active');
            dataAtive == true ? $(".default").prop("checked", true) : $(".default").prop("checked", false);
        } else {
            $('.clickatell').hide();
            $('.twilio').hide();
            $('.tonkra').hide();
            $('.revesms').hide();
            $('.bdbulksms').hide();
            $('.zircon').hide();
            $('.custom_http').hide();
        }
    });

    $('select[name="gateway"]').on('change', function() {
        if ($(this).val() == 'twilio') {
            $('.clickatell').hide();
            $('.tonkra').hide();
            $('.revesms').hide();
            $('.bdbulksms').hide();
            $('.zircon').hide();
            $('.custom_http').hide();
            $('.twilio').show(500);
            $('.twilio-option').prop('required', true);
            $('.clickatell-option').prop('required', false);
            $('.tonkra-option').prop('required', false);
            $('.revesms-option').prop('required', false);
            $('.bdbulksms-option').prop('required', false);
            $('.zircon-option').prop('required', false);
            $('.custom_http-option').prop('required', false);
            $('#smsId').val($('#twilioId').val());
            var selectedOption = $(this).find(':selected');
            var dataId = selectedOption.data('active');
            dataId == true ? $(".default").prop("checked", true) : $(".default").prop("checked", false);
        } else if ($(this).val() == 'clickatell') {
            $('.twilio').hide();
            $('.tonkra').hide();
            $('.revesms').hide();
            $('.clickatell').show(500);
            $('.bdbulksms').hide();
            $('.zircon').hide();
            $('.custom_http').hide();
            $('.bdbulksms-option').prop('required', false);
            $('.twilio-option').prop('required', false);
            $('.revesms-option').prop('required', false);
            $('.tonkra-option').prop('required', false);
            $('.clickatell-option').prop('required', true);
            $('.zircon-option').prop('required', false);
            $('.custom_http-option').prop('required', false);
            $('#smsId').val($('#clickatellId').val());
            var selectedOption = $(this).find(':selected');
            var dataId = selectedOption.data('active');
            dataId == true ? $(".default").prop("checked", true) : $(".default").prop("checked", false);
        } else if ($(this).val() == 'tonkra') {
            $('.twilio').hide();
            $('.clickatell').hide();
            $('.revesms').hide();
            $('.tonkra').show(500);
            $('.bdbulksms').hide();
            $('.zircon').hide();
            $('.custom_http').hide();
            $('.bdbulksms-option').prop('required', false);
            $('.tonkra-option').prop('required', true);
            $('.twilio-option').prop('required', false);
            $('.clickatell-option').prop('required', false);
            $('.revesms-option').prop('required', false);
            $('.zircon-option').prop('required', false);
            $('.custom_http-option').prop('required', false);
            $('#smsId').val($('#tonkraId').val());
            var selectedOption = $(this).find(':selected');
            var dataId = selectedOption.data('active');
            dataId == true ? $(".default").prop("checked", true) : $(".default").prop("checked", false);
        } else if ($(this).val() == 'revesms') {
            $('.twilio').hide();
            $('.clickatell').hide();
            $('.tonkra').hide();
            $('.revesms').show(500);
            $('.bdbulksms').hide();
            $('.zircon').hide();
            $('.custom_http').hide();
            $('.bdbulksms-option').prop('required', false);
            $('.revesms-option').prop('required', true);
            $('.twilio-option').prop('required', false);
            $('.clickatell-option').prop('required', false);
            $('.tonkra-option').prop('required', false);
            $('.zircon-option').prop('required', false);
            $('.custom_http-option').prop('required', false);
            $('#smsId').val($('#revesmsId').val());
            var selectedOption = $(this).find(':selected');
            var dataId = selectedOption.data('active');
            dataId == true ? $(".default").prop("checked", true) : $(".default").prop("checked", false);
        } else if ($(this).val() == 'bdbulksms') {
            $('.twilio').hide();
            $('.clickatell').hide();
            $('.tonkra').hide();
            $('.revesms').hide();
            $('.bdbulksms').show(500);
            $('.zircon').hide();
            $('.custom_http').hide();
            $('.bdbulksms-option').prop('required', true);
            $('.revesms-option').prop('required', false);
            $('.twilio-option').prop('required', false);
            $('.clickatell-option').prop('required', false);
            $('.tonkra-option').prop('required', false);
            $('.zircon-option').prop('required', false);
            $('.custom_http-option').prop('required', false);
            $('#smsId').val($('#bdbulksmsId').val());
            var selectedOption = $(this).find(':selected');
            var dataId = selectedOption.data('active');
            dataId == true ? $(".default").prop("checked", true) : $(".default").prop("checked", false);
        } else if ($(this).val() == 'zircon') {
            $('.twilio').hide();
            $('.clickatell').hide();
            $('.tonkra').hide();
            $('.revesms').hide();
            $('.bdbulksms').hide();
            $('.zircon').show(500);
            $('.custom_http').hide();
            $('.zircon-option').prop('required', true);
            $('.bdbulksms-option').prop('required', false);
            $('.revesms-option').prop('required', false);
            $('.twilio-option').prop('required', false);
            $('.clickatell-option').prop('required', false);
            $('.tonkra-option').prop('required', false);
            $('.custom_http-option').prop('required', false);
            $('#smsId').val($('#zirconId').val());
            var selectedOption = $(this).find(':selected');
            var dataId = selectedOption.data('active');
            dataId == true ? $(".default").prop("checked", true) : $(".default").prop("checked", false);
        } else if ($(this).val() == 'custom_http') {
            $('.twilio').hide();
            $('.clickatell').hide();
            $('.tonkra').hide();
            $('.revesms').hide();
            $('.bdbulksms').hide();
            $('.zircon').hide();
            $('.custom_http').show(500);
            $('.custom_http-option').prop('required', true);
            $('.zircon-option').prop('required', false);
            $('.bdbulksms-option').prop('required', false);
            $('.revesms-option').prop('required', false);
            $('.twilio-option').prop('required', false);
            $('.clickatell-option').prop('required', false);
            $('.tonkra-option').prop('required', false);
            $('#smsId').val($('#customHttpId').val());
            var selectedOption = $(this).find(':selected');
            var dataId = selectedOption.data('active');
            dataId == true ? $(".default").prop("checked", true) : $(".default").prop("checked", false);
        }
    });


    $('#test-custom-http-btn').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('Testing...');

        var method = $('select[name="method"]').val();
        var api_url = $('input[name="api_url"]').val();
        var headers = $('textarea[name="headers"]').val();
        var body_template = $('textarea[name="body_template"]').val();

        var testPhone = prompt("Enter a test phone number (with country code):");
        if (!testPhone) {
            btn.prop('disabled', false).text('Test SMS');
            return;
        }

        $.ajax({
            type: 'POST',
            url: '{{route("setting.testCustomHttpSms")}}',
            data: {
                _token: $('input[name="_token"]').val(),
                phone: testPhone,
                method: method,
                api_url: api_url,
                headers: headers,
                body_template: body_template
            },
            success: function(response) {
                btn.prop('disabled', false).text('Test SMS');
                if (response.success) {
                    alert('SMS test request sent successfully!\n\nResponse:\n' + JSON.stringify(response.response, null, 2));
                } else {
                    alert('SMS test failed.\n\nError:\n' + response.error);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).text('Test SMS');
                alert('Test request failed due to server error. Check console.');
                console.log(xhr.responseText);
            }
        });
    });
</script>

@endpush