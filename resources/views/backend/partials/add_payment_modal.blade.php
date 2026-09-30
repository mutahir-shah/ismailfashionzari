{{-- ============================================================
     Partial: Add Payment Modal
     Include with: @include('backend.partials._add_payment_modal')
     ============================================================ --}}

<div id="add-payment" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Payment')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                    <span aria-hidden="true"><i class="ti ti-x"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('sale.add-payment') }}" method="post" enctype="multipart/form-data" class="payment-form" id="add-payment-form">
                    @csrf
                    <div class="row">
                        <input type="hidden" name="balance">

                        {{-- Received Amount --}}
                        <div class="col-md-4">
                            <label>{{__('db.Recieved Amount')}} *</label>
                            <input type="text" name="paying_amount" class="form-control numkey" step="any" required>
                        </div>

                        {{-- Paying Amount --}}
                        <div class="col-md-4">
                            <label>{{__('db.Paying Amount')}} *</label>
                            <input type="text" id="amount" name="amount" class="form-control" step="any" required>
                        </div>

                        {{-- Change --}}
                        <div class="col-md-4 mt-1">
                            <label>{{__('db.Change')}} : </label>
                            <p class="change ml-2">{{number_format(0, gen_setting()->decimal, '.', '')}}</p>
                        </div>

                        {{-- Paid By --}}
                        <div class="col-md-4">
                            <label>{{__('db.Paid By')}}</label>
                            <select name="paid_by_id" class="form-control">
                                @if(in_array("cash", $options))
                                    <option value="1">{{ __('db.Cash') }}</option>
                                @endif
                                @if(in_array("gift_card", $options))
                                    <option value="2">{{ __('db.Gift Card') }}</option>
                                @endif
                                @if(in_array("card", $options))
                                    <option value="3">{{ __('db.Credit Card') }}</option>
                                @endif
                                @if(in_array("cheque", $options))
                                    <option value="4">{{ __('db.Cheque') }}</option>
                                @endif
                                @if(
                                    in_array("paypal", $options) &&
                                    strlen($lims_pos_setting_data->paypal_live_api_username) > 0 &&
                                    strlen($lims_pos_setting_data->paypal_live_api_password) > 0 &&
                                    strlen($lims_pos_setting_data->paypal_live_api_secret) > 0
                                )
                                    <option value="5">{{ __('db.paypal') }}</option>
                                @endif
                                @if(in_array("deposit", $options))
                                    <option value="6">{{ __('db.Deposit') }}</option>
                                @endif
                                @if($lims_reward_point_setting_data && $lims_reward_point_setting_data->is_active)
                                    <option value="7">{{ __('db.Points') }}</option>
                                @endif
                                @foreach($options as $option)
                                    @if(!in_array($option, ['cash', 'card', 'cheque', 'gift_card', 'deposit', 'paypal', 'pesapal','points']))
                                        <option value="{{$option}}">{{ucfirst($option)}}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        {{-- Payment Receiver --}}
                        <div class="col-md-4">
                            <label>{{__('db.Payment Receiver')}}</label>
                            <input type="text" name="payment_receiver" class="form-control">
                        </div>

                        {{-- Payment Date --}}
                        <div class="col-md-4">
                            <label>{{ __('db.Payment Date') }}</label>
                            <input type="text" name="payment_at" id="payment_at" class="form-control"
                                value="{{ date('Y-m-d') }}" required>
                        </div>

                        {{-- Currency & Exchange Rate --}}
                        <div class="col-md-4">
                            <label>{{__('db.Currency')}} & {{__('db.Exchange Rate')}}</label>
                            <div class="form-group d-flex align-items-center">
                                <p id="currency_display" class="form-control-plaintext mb-0 font-weight-bold mr-3"></p>
                                <p id="exchange_rate_display" class="form-control-plaintext mb-0 font-weight-bold"></p>
                            </div>
                            <input type="hidden" name="currency_id" id="currency_id">
                            <input type="hidden" name="exchange_rate" id="exchange_rate">
                        </div>

                        {{-- Account --}}
                        <div class="col-md-4">
                            <label>{{__('db.Account')}}</label>
                            <select class="form-control selectpicker" name="account_id">
                                @foreach($lims_account_list as $account)
                                    @if(auth()->user()->account_id === $account->id)
                                        <option selected value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                                    @elseif($account->is_default)
                                        <option selected value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                                    @else
                                        <option value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        {{-- Attach Document --}}
                        <div class="col-md-12 mt-1">
                            <div class="form-group">
                                <label>{{__('db.Attach Document')}}</label>
                                <x-info title="Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported" type="info" />
                                <input type="file" name="document" class="form-control" />
                                @if($errors->has('extension'))
                                    <span><strong>{{ $errors->first('extension') }}</strong></span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Gift Card --}}
                    <div class="gift-card form-group">
                        <label>{{__('db.Gift Card')}} *</label>
                        <select id="gift_card_id" name="gift_card_id" class="selectpicker form-control"
                            data-live-search="true" data-live-search-style="begins" title="Select Gift Card...">
                            @foreach($lims_gift_card_list as $gift_card)
                                <option value="{{$gift_card->id}}">{{$gift_card->card_no}}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Stripe Card Element --}}
                    <div class="form-group mt-2">
                        <div class="card-element" class="form-control"></div>
                        <div class="card-errors" role="alert"></div>
                    </div>

                    {{-- Cheque --}}
                    <div id="cheque">
                        <div class="form-group">
                            <label>{{__('db.Cheque Number')}} *</label>
                            <input type="text" name="cheque_no" class="form-control">
                        </div>
                    </div>

                    {{-- Payment Note --}}
                    <div class="form-group">
                        <label>{{__('db.Payment Note')}}</label>
                        <textarea rows="3" class="form-control" name="payment_note"></textarea>
                    </div>

                    {{-- Print Receipt Checkbox --}}
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="print_receipt"
                                name="print_receipt" value="1">
                            <label class="custom-control-label" for="print_receipt">
                                <i class="ti ti-printer"></i> {{__('db.Print Payment Receipt')}}
                            </label>
                        </div>
                    </div>

                    <input type="hidden" name="sale_id">

                    <button type="submit" class="btn btn-primary" id="add-payment-submit-btn">
                        {{__('db.submit')}}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- <script type="text/javascript">

    // ─── Gift Card: Balance & Expiry Maps (built from PHP) ───────────────────────
    var balance = {};
    var expired_date = {};
    @foreach($lims_gift_card_list as $gift_card)
        balance[{{ $gift_card->id }}]      = {{ $gift_card->amount - $gift_card->expense }};
        expired_date[{{ $gift_card->id }}] = "{{ $gift_card->expired_date }}";
    @endforeach

    // ─── Datepicker Init ──────────────────────────────────────────────────────────
    $(function () {
        $('#payment_at').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        }).datepicker("setDate", new Date());
    });

    // ─── Default Hidden State ─────────────────────────────────────────────────────
    $(".gift-card").hide();
    $(".card-element").hide();
    $("#cheque").hide();

    // ─── Open Add Payment Modal ───────────────────────────────────────────────────
    $(document).on("click", "table.sale-list tbody .add-payment", function () {
        // Reset conditional sections
        $("#cheque").hide();
        $(".gift-card").hide();
        $(".card-element").hide();
        $('select[name="paid_by_id"]').val(1);
        $('.selectpicker').selectpicker('refresh');

        const sale_id       = String($(this).data('id'));
        const balance       = parseFloat($(this).data('due').replace(/,/g, '')) || 0;
        const currency_id   = $(this).data('currency_id');
        const currency_name = $(this).data('currency_name');
        const exchange_rate = parseFloat($(this).data('exchange_rate')) || 1;

        $('input[name="paying_amount"]').val(balance);
        $('#add-payment input[name="balance"]').val(balance);
        $('input[name="amount"]').val(balance);
        $('input[name="sale_id"]').val(sale_id);

        // Currency display (readonly info)
        $('#currency_display').text(currency_name);
        $('#exchange_rate_display').text(exchange_rate.toFixed(2));

        // Hidden inputs for backend
        $('#currency_id').val(currency_id);
        $('#exchange_rate').val(exchange_rate);
    });

    // ─── Paid By: Change Handler ──────────────────────────────────────────────────
    $('select[name="paid_by_id"]').on("change", function () {
        var id = $(this).val();

        $('input[name="cheque_no"]').attr('required', false);
        $('#add-payment select[name="gift_card_id"]').attr('required', false);
        $(".payment-form").off("submit");

        if (id == 2) {
            // Gift Card
            $(".gift-card").show();
            $(".card-element").hide();
            $("#cheque").hide();
            $('#add-payment select[name="gift_card_id"]').attr('required', true);

        } else if (id == 3) {
            // Credit Card (Stripe)
            @if($lims_pos_setting_data && (strlen($lims_pos_setting_data->stripe_public_key)>0) && (strlen($lims_pos_setting_data->stripe_secret_key)>0))
                $.getScript("vendor/stripe/checkout.js");
                $(".card-element").show();
            @endif
            $(".gift-card").hide();
            $("#cheque").hide();

        } else if (id == 4) {
            // Cheque
            $("#cheque").show();
            $(".gift-card").hide();
            $(".card-element").hide();
            $('input[name="cheque_no"]').attr('required', true);

        } else if (id == 5) {
            // Paypal
            $(".card-element").hide();
            $(".gift-card").hide();
            $("#cheque").hide();

        } else {
            $(".card-element").hide();
            $(".gift-card").hide();
            $("#cheque").hide();

            if (id == 6) {
                // Deposit
                if ($('#add-payment input[name="amount"]').val() > parseFloat(deposit))
                    alert('Amount exceeds customer deposit! Customer deposit : ' + deposit);
            } else if (id == 7) {
                // Points
                pointCalculation($('#add-payment input[name="amount"]').val());
            }
        }
    });

    // ─── Gift Card: Expiry & Balance Check ───────────────────────────────────────
    $('#add-payment select[name="gift_card_id"]').on("change", function () {
        var id = $(this).val();
        if (expired_date[id] < current_date)
            alert('This card is expired!');
        else if ($('#add-payment input[name="amount"]').val() > balance[id])
            alert('Amount exceeds card balance! Gift Card balance: ' + balance[id]);
    });

    // ─── Received Amount: Live Change Calculation ─────────────────────────────────
    $('input[name="paying_amount"]').on("input", function () {
        $(".change").text(
            parseFloat($(this).val() - $('input[name="amount"]').val())
                .toFixed({{gen_setting()->decimal}})
        );
    });

    // ─── Paying Amount: Validation & Change Calculation ───────────────────────────
    $('input[name="amount"]').on("input", function () {
        var val     = parseFloat($(this).val());
        var received = parseFloat($('input[name="paying_amount"]').val());
        var due     = parseFloat($('input[name="balance"]').val());

        if (val > received) {
            alert('Paying amount cannot be bigger than recieved amount');
            $(this).val('');
        } else if (val > due) {
            alert('Paying amount cannot be bigger than due amount');
            $(this).val('');
        }

        $(".change").text(
            parseFloat(received - $(this).val()).toFixed({{gen_setting()->decimal}})
        );

        var id     = $('#add-payment select[name="paid_by_id"]').val();
        var amount = $(this).val();

        if (id == 2) {
            var giftId = $('#add-payment select[name="gift_card_id"]').val();
            if (amount > balance[giftId])
                alert('Amount exceeds card balance! Gift Card balance: ' + balance[giftId]);
        } else if (id == 6) {
            if (amount > parseFloat(deposit))
                alert('Amount exceeds customer deposit! Customer deposit : ' + deposit);
        } else if (id == 7) {
            pointCalculation(amount);
        }
    });

    // ─── Form Submit: Double-submit Guard ─────────────────────────────────────────
    $('#add-payment-form').on('submit', function () {
        var $btn = $('#add-payment-submit-btn');
        if ($btn.is(':disabled')) return false;
        $btn.attr('disabled', 'disabled').text('Submitting...');
        return true;
    });

    // ─── Form Submit: Global Paying Amount Validation ─────────────────────────────
    $(document).on('submit', '.payment-form', function (e) {
        if ($('input[name="paying_amount"]').val() < parseFloat($('#amount').val())) {
            alert('Paying amount cannot be bigger than recieved amount');
            $('input[name="amount"]').val('');
            $(".change").text(
                parseFloat($('input[name="paying_amount"]').val() - $('#amount').val())
                    .toFixed({{gen_setting()->decimal}})
            );
            e.preventDefault();
        }
    });

</script> -->
