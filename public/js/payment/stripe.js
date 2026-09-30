$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
});

    var $form = $(".require-validation");
    $('form').bind('submit', function(e) {
    // $("#stripePaymentForm").on("submit",function(e){
        e.preventDefault();
        const stripePublishableKey = $('#stripeKey').val();
        $('#payNowBtn').text('Processing...');
        if (!$form.data('cc-on-file')) {
            Stripe.setPublishableKey(stripePublishableKey);
            Stripe.createToken({
                number: $('.card-number').val(),
                cvc: $('.card-cvc').val(),
                exp_month: $('.card-expiry-month').val(),
                exp_year: $('.card-expiry-year').val()
            }, stripeResponseHandler);
        }
    });

    function stripeResponseHandler(status, response) {
        /*console.log(response.error);
        console.log(response.stripeToken);*/
        if (response.error) {
                //console.log(response.error);
                let errorCode = response.error.code;
                let errorMessage = errorCode.charAt(0).toUpperCase() + errorCode.slice(1).replace(/_/g, " ");
                let html =
                    `<div class="alert alert-danger alert-dismissible fade show" role="alert">${errorMessage}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>`;
                $('#errorMessage').html(html);
                $('#payNowBtn').text('Pay Now');
        }
        else {
            //console.log('asad');
            //console.log(redirectURL);
            // token contains id, last4, and card type
            var token = response['id'];
            $('input[name="stripeToken"]').val(token);
            $.ajax({
                url: targetURL,
                method: "POST",
                data: new FormData(document.getElementById("stripePaymentForm")),
                contentType: false,
                cache: false,
                processData: false,
                dataType: "json",
                success: function (response) {
                    $('#payNowBtn').text('Payment Succeeded...Please wait');
                    window.location.href = redirectURL;
                }
            })
        }
    }


    $("#payCancelBtn").click(function(){
        if (confirm('Are you sure to cancel ?')) {
            $.ajax({
                url: cancelURL,
                type: 'POST',
                data: {},
                dataType: 'JSON',
                success: function (data) {
                    window.location.href = redirectURLAfterCancel;
                }
            });
        }
    });