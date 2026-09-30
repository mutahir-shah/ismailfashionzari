@extends('backend.layout.main') @section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.Create SMS')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.<strong>{{__('db.Add mobile numbers by selecting the customers')}}</strong></small></p>
                        <form action="{{ route('setting.sendSms') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mt-1">
                                        <label>{{__('db.SMS Template')}}</label>
                                        <select name="template_id" class="form-control selectpicker sms_template">                            
                                            <option value="">Select Template</option>
                                            @foreach($smsTemplates as $template)
                                            <option value="{{ $template->id }}" data-msg="{{ $template->content}}" >{{ $template->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="lims_customerSearch" id="lims_customerSearch" placeholder="{{ __('db.Please type customer name or mobile no and select') }}" />
                                    </div>
                                    <div class="form-group twilio">
                                        <label>{{__('db.Mobile')}} *</label>
                                        <input type="text" name="mobile" id="mobile" class="form-control" placeholder="+8801xxxxxxxxx, +8801xxxxxxxxx" required />
                                    </div>
                                    <div class="form-group twilio">
                                        <label>{{__('db.Message')}} *</label>
                                        <textarea name="message" class="form-control message" rows="3" required></textarea>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary"><i class="ti ti-paper-plane"></i> {{__('db.Send SMS')}}</button>
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
    $("ul#setting").siblings('a').attr('aria-expanded','true');
    $("ul#setting").addClass("show");
    $("ul#setting #create-sms-menu").addClass("active");

    <?php $customerArray = []; ?>
    var customer = [ @foreach($lims_customer_list as $customer)
        <?php
            $customerArray[] = $customer->name . ' [' . $customer->phone_number . ']';
        ?>
         @endforeach
            <?php
            echo  '"'.implode('","', $customerArray).'"';
            ?> ];

    var lims_customerSearch = $('#lims_customerSearch');

    lims_customerSearch.autocomplete({
        source: function(request, response) {
            var matcher = new RegExp(".?" + $.ui.autocomplete.escapeRegex(request.term), "i");
            response($.grep(customer, function(item) {
                return matcher.test(item);
            }));
        },
        response: function(event, ui) {
            if (ui.content.length == 1) {
                var data = ui.content[0].value;
                $(this).autocomplete( "close" );
                getNumber(data);
            };
        },
        select: function(event, ui) {
            var data = ui.item.value;
            event.preventDefault();
            getNumber(data);
        }
    });

    function getNumber(data) {
        mobile_no = data.substring(data.indexOf("[")+1, data.indexOf("]") );
        if( !$('#mobile').val().includes(mobile_no) ){
            if($('#mobile').val() == '')
                $('#mobile').val(mobile_no);
            else
                $('#mobile').val( $('#mobile').val()+','+mobile_no );
        }
        $('#lims_customerSearch').val('');
    }
    $(".sms_template").change(function(){
     
        var id = $(this).val();
        var msg = $(this).find(':selected').data('msg');
        $('.message').val(msg);
    });
</script>
@endpush
