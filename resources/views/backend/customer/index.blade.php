@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')

<x-success-message key="create_message" />
<x-success-message key="edit_message" />
<x-success-message key="import_message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        @can('customers-add')
            <a href="{{route('customer.create')}}" class="btn btn-info"><i class="ti ti-plus"></i> {{__('db.Add Customer')}}</a>&nbsp;
        @endcan
        @can('customers-import')
            <a href="#" data-toggle="modal" data-target="#importCustomer" class="btn btn-primary"><i class="ti ti-copy"></i> {{__('db.Import Customer')}}</a>
        @endcan
    </div>
    <div class="table-responsive">
        <table id="customer-table" class="table customer-list" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.Customer Group')}}</th>
                    <th>{{__('db.Customer Details')}}</th>
                    <th>{{__('db.Discount Plan')}}</th>
                    <th>{{__('db.Reward Points')}}</th>
                    <th>{{__('db.Deposited Balance')}}</th>
                    <th>{{__('db.Total Due')}}</th>
                    @foreach($custom_fields as $fieldName)
                    <th>{{$fieldName}}</th>
                    @endforeach
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
        </table>
    </div>
</section>

<div id="importCustomer" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">


        <form route ='{{route("customer.import")}}' method ='post' enctype="multipart/form-data">
            @csrf
        <div class="modal-header">
          <h5 id="exampleModalLabel" class="modal-title">{{__('db.Import Customer')}}</h5>
          <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
        </div>
        <div class="modal-body">
          <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
           <p>{{__('db.The correct column order is')}} (customer_group*, name*, company_name, email, phone_number*, address*, city*, state, postal_code, country, deposit) {{__('db.and you must follow this')}}.</p>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{__('db.Upload CSV File')}} *</label>
                        <input type="file" name="file" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label> {{__('db.Sample File')}}</label>
                        <a href="sample_file/sample_customer.csv" class="btn btn-info btn-block btn-md"><i class="ti ti-download"></i>  {{__('db.Download')}}</a>
                    </div>
                </div>
            </div>
            <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary" id="submit-button">
        </div>
        </form>
      </div>
    </div>
</div>

<div id="clearDueModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('customer.clearDue') }}" method="POST" class="clear-due-form">
        @csrf
        <div class="modal-header">
          <h5 id="exampleModalLabel" class="modal-title">{{__('db.Clear Due')}}</h5>
          <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
        </div>
        <div class="modal-body">
            <p class="italic">
                <small>{{__('db.The field labels marked with are required input fields')}}.</small>
            </p>
            <div class="row">
                <input type="hidden" name="customer_id">
                <input type="hidden" name="balance">
                <div class="col-md-6">
                    <label>{{__('db.Recieved Amount')}} *</label>
                    <input type="text" name="paying_amount" class="form-control numkey" step="any" required>
                </div>
                <div class="col-md-6">
                    <label>{{__('db.Paying Amount')}} *</label>
                    <input type="text" id="p_amount" name="amount" class="form-control"  step="any" required>
                </div>
                <div class="col-md-6 mt-1">
                    <label>{{__('db.Change')}} : </label>
                    <p class="change ml-2">{{number_format(0, gen_setting()->decimal, '.', '')}}</p>
                </div>
                <div class="col-md-6 mt-1">
                    <label>{{__('db.Paid By')}}</label>
                    <select name="paid_by_id" class="form-control">
                        @if(in_array("cash",$options))
                        <option value="1">Cash</option>
                        @endif
                        @if(in_array("gift_card",$options))
                        <option value="2">Gift Card</option>
                        @endif
                        @if(in_array("card",$options))
                        <option value="3">Credit Card</option>
                        @endif
                        @if(in_array("cheque",$options))
                        <option value="4">Cheque</option>
                        @endif
                        @if(in_array("paypal",$options) && (strlen($lims_pos_setting_data->paypal_live_api_username)>0) && (strlen($lims_pos_setting_data->paypal_live_api_password)>0) && (strlen($lims_pos_setting_data->paypal_live_api_secret)>0))
                        <option value="5">Paypal</option>
                        @endif
                        @if(in_array("deposit",$options))
                        <option value="6">Deposit</option>
                        @endif
                        @if($lims_reward_point_setting_data && $lims_reward_point_setting_data->is_active)
                        <option value="7">Points</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-6">
                    <label>{{__('db.Payment Receiver')}}</label>
                    <input type="text" name="payment_receiver" class="form-control">
                </div>
            </div>
            <div class="gift-card form-group">
                <label> {{__('db.Gift Card')}} *</label>
                <select id="gift_card_id" name="gift_card_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Gift Card...">
                    @php
                        $balance = [];
                        $expired_date = [];
                    @endphp
                    @foreach($lims_gift_card_list as $gift_card)
                    <?php
                        $balance[$gift_card->id] = $gift_card->amount - $gift_card->expense;
                        $expired_date[$gift_card->id] = $gift_card->expired_date;
                    ?>
                        <option value="{{$gift_card->id}}">{{$gift_card->card_no}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mt-2">
                <div class="card-element" class="form-control">
                </div>
                <div class="card-errors" role="alert"></div>
            </div>
            <div id="cheque">
                <div class="form-group">
                    <label>{{__('db.Cheque Number')}} *</label>
                    <input type="text" name="cheque_no" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label> {{__('db.Account')}}</label>
                <select class="form-control selectpicker" name="account_id">
                @foreach($lims_account_list as $account)
                    @if($account->is_default)
                    <option selected value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                    @else
                    <option value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                    @endif
                @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>{{__('db.Payment Note')}}</label>
                <textarea rows="3" class="form-control" name="payment_note"></textarea>
            </div>
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="clear_due_print_receipt"
                        name="print_receipt" value="1">
                    <label class="custom-control-label" for="clear_due_print_receipt">
                        <i class="ti ti-printer"></i> {{__('db.Print Payment Receipt')}}
                    </label>
                </div>
            </div>
            <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary" id="submit-button">
        </div>
        </form>
      </div>
    </div>
</div>

<div id="depositModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('customer.addDeposit') }}" method="POST">
          @csrf
        <div class="modal-header">
          <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Deposit')}}</h5>
          <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
        </div>
        <div class="modal-body">
          <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
            <div class="form-group">
                <input type="hidden" name="customer_id">
                <label>{{__('db.Amount')}} *</label>
                <input type="number" name="amount" step="any" class="form-control" required>
            </div>
            <div class="form-group">
                <label>{{__('db.Account')}}</label>
                <select class="form-control" name="account_id">
                    @foreach($lims_account_list as $account)
                        @if($account->is_default)
                        <option selected value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                        @else
                        <option value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>{{__('db.note')}}</label>
                <textarea name="note" rows="4" class="form-control"></textarea>
            </div>
            <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary" id="submit-button">
        </div>
        </form>
      </div>
    </div>
</div>

<div id="pointModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('customer.addPoint') }}" method="POST">
          @csrf
        <div class="modal-header">
          <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Point')}}</h5>
          <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
        </div>
        <div class="modal-body">
          <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
            <div class="form-group">
                <input type="hidden" name="customer_id">
                <label>{{__('db.Points')}} *</label>
                <input type="number" name="points" step="any" class="form-control" required>
                <label>{{__('db.note')}}</label>
                <textarea name="note" id="" cols="30" rows="3" class="form-control"></textarea>
            </div>
            <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary" id="submit-button">
        </div>
        </form>
      </div>
    </div>
</div>

<div id="view-points" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Points')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <table class="table table-hover points-list">
                    <thead>
                        <tr>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.Earn Points')}}</th>
                            <th>{{__('db.Redeem Points')}}</th>
                            <th>{{__('db.note')}}</th>
                            <th>{{__('db.Created By')}}</th>
                            <th>{{__('db.Type')}}</th>
                            <th>{{__('db.action')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="edit-point" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.update_point')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('customer.updatePoint') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label>{{__('db.Points')}} *</label>
                        <input type="number" name="points" step="any" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>{{__('db.note')}}</label>
                        <textarea name="note" rows="4" class="form-control"></textarea>
                    </div>
                    <input type="hidden" name="point_id">
                    <button type="submit" class="btn btn-primary">{{__('db.update')}}</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="view-deposit" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.All Deposit')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <table class="table table-hover deposit-list">
                    <thead>
                        <tr>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.Amount')}}</th>
                            <th>{{__('db.note')}}</th>
                            <th>{{__('db.Created By')}}</th>
                            <th>{{__('db.action')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="edit-deposit" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Update Deposit')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('customer.updateDeposit') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label>{{__('db.Amount')}} *</label>
                        <input type="number" name="amount" step="any" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>{{__('db.Account')}}</label>
                        <select class="form-control" name="account_id">
                            @foreach($lims_account_list as $account)
                                @if($account->is_default)
                                <option selected value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                                @else
                                <option value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{__('db.note')}}</label>
                        <textarea name="note" rows="4" class="form-control"></textarea>
                    </div>
                    <input type="hidden" name="deposit_id">
                    <button type="submit" class="btn btn-primary">{{__('db.update')}}</button>
                </form>
            </div>
        </div>
    </div>
</div>


@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">
    $("ul#people").siblings('a').attr('aria-expanded','true');
    $("ul#people").addClass("show");
    $("ul#people #customer-list-menu").addClass("active");

    function confirmDelete() {
      if (confirm("Are you sure want to delete?")) {
          return true;
      }
      return false;
    }

    var customer_id = [];
    var user_verified = <?php echo json_encode(config('app.user_verified'))?>;
    var all_permission = <?php echo json_encode($all_permission) ?>;
    @if($lims_reward_point_setting_data)
        var reward_point_setting = <?php echo json_encode($lims_reward_point_setting_data) ?>;
    @endif

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $(document).on("click", ".deposit", function() {
        var id = $(this).data('id').toString();
        $("#depositModal input[name='customer_id']").val(id);
    });

    $(document).on("click", ".point", function() {
        var id = $(this).data('id').toString();
        $("#pointModal input[name='customer_id']").val(id);
    });

    $(document).on("click", ".clear-due", function() {
        var id = $(this).data('id').toString();
        // console.log(id);
        $("#clearDueModal input[name='customer_id']").val(id);
        $("#cheque").hide();
        $(".gift-card").hide();
        $(".card-element").hide();
        $('select[name="paid_by_id"]').val(1);
        $('.selectpicker').selectpicker('refresh');
        rowindex = $(this).closest('tr').index();
        // deposit = $('table.customer-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.deposit').text();
        deposit = $('table.customer-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(6)').text();
        var sale_id = $(this).data('id').toString();
        var balance = $('table.customer-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(7)').text();
        balance = parseFloat(balance.replace(/,/g, ''));
        $('input[name="paying_amount"]').val(balance);
        $('#clearDueModal input[name="balance"]').val(balance);
        $('#p_amount').val(balance);
    });

    $('select[name="paid_by_id"]').on("change", function() {
        var id = $(this).val();

        // Hide received amount and change if paid_by_id is not 1 (Cash)
        if (id != 1) {
            $('input[name="paying_amount"]').val($('#p_amount').val());
            $('input[name="paying_amount"]').closest('.col-md-6').hide();
            $('.change').closest('.col-md-6').hide();
        } else {
            $('input[name="paying_amount"]').closest('.col-md-6').show();
            $('.change').closest('.col-md-6').show();
        }

        $('input[name="cheque_no"]').attr('required', false);
        $('#clearDueModal select[name="gift_card_id"]').attr('required', false);
        $(".clear-due-form").off("submit");
        // console.log($('select[name="paid_by_id"]').val(), id);
        if(id == 2){
            $(".gift-card").show();
            $(".card-element").hide();
            $("#cheque").hide();
            $('#clearDueModal select[name="gift_card_id"]').attr('required', true);
        }
        else if (id == 3) {
            @if($lims_pos_setting_data && (strlen($lims_pos_setting_data->stripe_public_key)>0) && (strlen($lims_pos_setting_data->stripe_secret_key )>0))
                $.getScript( "vendor/stripe/checkout.js" );
                $(".card-element").show();
            @endif
            $(".gift-card").hide();
            $("#cheque").hide();
        } else if (id == 4) {
            $("#cheque").show();
            $(".gift-card").hide();
            $(".card-element").hide();
            $('input[name="cheque_no"]').attr('required', true);
        } else if (id == 5) {
            $(".card-element").hide();
            $(".gift-card").hide();
            $("#cheque").hide();
        } else {
            $(".card-element").hide();
            $(".gift-card").hide();
            $("#cheque").hide();
            if(id == 6){
                if($('#p_amount').val() > parseFloat(deposit))
                    alert('Amount exceeds customer deposit! Customer deposit : ' + deposit);
            }
            else if(id==7) {
                pointCalculation($('#p_amount').val());
            }
        }
    });

    $(document).ready(function() {
        $('select[name="paid_by_id"]').trigger("change");
    });

    $('input#p_amount').on("input", function () {
        var paidBy = $('select[name="paid_by_id"]').val(); // Get the selected payment method
        if (paidBy != 1) { // Check if paid_by_id is NOT 1 (Cash)
            $('input[name="paying_amount"]').val($(this).val());
        }
    });



    $('#clearDueModal select[name="gift_card_id"]').on("change", function() {
        var id = $(this).val();
        if(expired_date[id] < current_date)
            alert('This card is expired!');
        else if($('#clearDueModal input[name="amount"]').val() > balance[id]){
            alert('Amount exceeds card balance! Gift Card balance: '+ balance[id]);
        }
    });

    $('input[name="paying_amount"]').on("focusout", function() {
        if($('#p_amount').val() > $(this).val()) {
            $('#p_amount').val(parseFloat($(this).val()));
        }else{
            $(".change").text(parseFloat( $(this).val() - $('input[name="amount"]').val() ).toFixed({{gen_setting()->decimal}}));
        }
    });

    $('#p_amount').on("input", function() {
        if( $(this).val() > parseFloat($('input[name="paying_amount"]').val()) ) {
            alert('Paying amount cannot be bigger than recieved amount');
            $(this).val('');
        }
        else if( $(this).val() > parseFloat($('input[name="balance"]').val()) ) {
            alert('Paying amount cannot be bigger than due amount');
            $(this).val('');
        }
        $(".change").text(parseFloat($('input[name="paying_amount"]').val() - $(this).val()).toFixed({{gen_setting()->decimal}}));
        var id = $('#clearDueModal select[name="paid_by_id"]').val();
        var amount = $(this).val();
        if(id == 2){
            id = $('#clearDueModal select[name="gift_card_id"]').val();
            if(amount > balance[id])
                alert('Amount exceeds card balance! Gift Card balance: '+ balance[id]);
        }
        else if(id == 6){
            if(amount > parseFloat(deposit))
                alert('Amount exceeds customer deposit! Customer deposit : ' + deposit);
        }
        else if(id==7) {
            pointCalculation(amount);
        }
    });

    function pointCalculation(amount) {
        availablePoints = $('table.customer-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.points').val();
        required_point = Math.ceil(amount / reward_point_setting['per_point_amount']);
        if(required_point > availablePoints) {
          alert('Customer does not have sufficient points. Available points: '+availablePoints+'. Required points: '+required_point);
        }
    }

    $(document).on('submit', '.clear-due-form', function(e) {
        if( $('input[name="paying_amount"]').val() < parseFloat($('#amount').val()) ) {
            alert('Paying amount cannot be bigger than recieved amount');
            $('#p_amount').val('');
            $(".change").text(parseFloat( $('input[name="paying_amount"]').val() - $('#p_amount').val() ).toFixed({{gen_setting()->decimal}}));
            e.preventDefault();
        }
        else if( $('input[name="edit_paying_amount"]').val() < parseFloat($('input[name="edit_amount"]').val()) ) {
            alert('Paying amount cannot be bigger than recieved amount');
            $('input[name="edit_amount"]').val('');
            $(".change").text(parseFloat( $('input[name="edit_paying_amount"]').val() - $('input[name="edit_amount"]').val() ).toFixed({{gen_setting()->decimal}}));
            e.preventDefault();
        }

        $('#edit-payment select[name="edit_paid_by_id"]').prop('disabled', false);
    });

  $(document).on("click", ".getDeposit", function() {
        var id = $(this).data('id').toString();
        $.get('customer/getDeposit/' + id, function(data) {
            $(".deposit-list tbody").remove();
            var newBody = $("<tbody>");
            $.each(data[0], function(index){
                var newRow = $("<tr>");
                var cols = '';

                cols += '<td>' + data[1][index] + '</td>';
                cols += '<td>' + data[2][index] + '</td>';
                if(data[3][index])
                    cols += '<td>' + data[3][index] + '</td>';
                else
                    cols += '<td>N/A</td>';
                cols += '<td>' + data[4][index] + '<br>' + data[5][index] + '</td>';
                cols += '<td>' +
                            '<div class="btn-group">' +
                                '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{__("db.action")}}<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>' +
                                '<ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">' +
                                    '<li>' +
                                        '<button type="button" class="btn btn-link edit-btn" data-id="' + data[0][index] + '" data-toggle="modal" data-target="#edit-deposit"><i class="ti ti-edit"></i> {{__("db.edit")}}</button>' +
                                    '</li>' +
                                    '<li class="divider"></li>' +
                                    '<form action="{{ route("customer.deleteDeposit") }}" method="POST">' +
                                        '@csrf' +
                                        '<li>' +
                                            '<input type="hidden" name="id" value="' + data[0][index] + '" />' + 
                                            '<button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="ti ti-trash"></i> {{__("db.delete")}}</button>' +
                                        '</li>' +
                                    '</form>' +
                                '</ul>' +
                            '</div>' +
                        '</td>';
                newRow.append(cols);
                newBody.append(newRow);
                $("table.deposit-list").append(newBody);
            });
            $("#view-deposit").modal('show');
        });
  });

  $(document).on("click", ".getPoints", function() {
        var id = $(this).data('id').toString();
        $.get('customer/getPoints/' + id, function(data) {
            $(".points-list tbody").remove();
            var newBody = $("<tbody>");
            $.each(data[0], function(index){
                var newRow = $("<tr>");
                var cols = '';

                cols += '<td>' + data[1][index] + '</td>';
                cols += '<td>' + data[2][index] + '</td>';
                cols += '<td>' + data[7][index] + '</td>';
                if(data[3][index])
                    cols += '<td>' + data[3][index] + '</td>';
                else
                    cols += '<td>N/A</td>';
                cols += '<td>' + data[4][index] + '<br>' + data[5][index] + '</td>';
                cols += '<td>' + data[6][index] + '</td>';

                cols += '<td>' +
                            '<div class="btn-group">' +
                                '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{__("db.action")}}<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>' +
                                '<ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">' +
                                    '<li>' +
                                        '<button type="button" class="btn btn-link edit-btn" data-id="' + data[0][index] +'" data-toggle="modal" data-target="#edit-point"><i class="ti ti-edit"></i> {{__("db.edit")}}</button>' +
                                    '</li>' +
                                    '<li class="divider"></li>' +
                                    '<form action="{{ route("customer.deletePoints") }}" method="POST">' +
                                        '@csrf' + 
                                        '<li>' +
                                            '<input type="hidden" name="id" value="' + data[0][index] + '" />' +
                                            '<button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="ti ti-trash"></i> {{__("db.delete")}}</button>' +
                                        '</li>' +
                                    '</form>' +
                                '</ul>' +
                            '</div>' +
                        '</td>';
                newRow.append(cols);
                newBody.append(newRow);
                $("table.points-list").append(newBody);
            });
            $("#view-points").modal('show');
        });
  });

  $(document).on("click", "table.deposit-list .edit-btn", function(event) {
        var id = $(this).data('id');
        var rowindex = $(this).closest('tr').index();
        var amount = $('table.deposit-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)').text();
        var note = $('table.deposit-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(3)').text();
        if(note == 'N/A')
            note = '';

        $('#edit-deposit input[name="deposit_id"]').val(id);
        $('#edit-deposit input[name="amount"]').val(amount);
        $('#edit-deposit textarea[name="note"]').val(note);
        $('#view-deposit').modal('hide');
    });

     $(document).on("click", "table.points-list .edit-btn", function(event) {
        var id = $(this).data('id');
        var rowindex = $(this).closest('tr').index();
        var amount = $('table.points-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)').text();
        var note = $('table.points-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(3)').text();
        if(note == 'N/A')
            note = '';

        $('#edit-point input[name="point_id"]').val(id);
        $('#edit-point input[name="points"]').val(amount);
        $('#edit-point textarea[name="note"]').val(note);
        $('#view-points').modal('hide');
    });

    var columns = [{"data": "key"}, {"data": "customer_group"}, {"data": "customer_details"}, {"data": "discount_plan"}, {"data": "reward_point"}, {"data": "deposited_balance"}, {"data": "total_due"}];
    var field_name = <?php echo json_encode($field_name) ?>;
    for(i = 0; i < field_name.length; i++) {
        columns.push({"data": field_name[i]});
    }
    columns.push({"data": "options"});

    let buttons = [];

    @can('customer_export')
        buttons.push([
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                }
            },
            {
                extend: 'excel',
                text: '<i title="export to excel" class="ti ti-file-type-xls"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                }
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                }
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                }
            },
        ]);
    @endcan

    buttons.push([
        {
            text: '<i title="delete" class="ti ti-x"></i>',
            className: 'buttons-delete',
            action: function ( e, dt, node, config ) {
                if(user_verified == '1') {
                    customer_id.length = 0;
                    $(':checkbox:checked').each(function(i){
                        if(i){
                            customer_id[i-1] = $(this).closest('tr').data('customer');
                        }
                    });
                    if(customer_id.length && confirm("Are you sure want to delete?")) {
                        $.ajax({
                            type:'POST',
                            url:'customer/deletebyselection',
                            data:{
                                customerIdArray: customer_id
                            },
                            success:function(data){
                                alert(data);
                            }
                        });
                        dt.rows({ page: 'current', selected: true }).remove().draw(false);
                    }
                    else if(!customer_id.length)
                        alert('No customer is selected!');
                }
                else
                    alert('This feature is disable for demo!');
            }
        },
        {
            extend: 'colvis',
            text: '<i title="column visibility" class="ti ti-eye"></i>',
            columns: ':gt(0)'
        },
    ]);

    $('#customer-table').DataTable( {
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"{{ url('customers/customer-data')}}",
            data:{
                all_permission: all_permission,
            },
            dataType: "json",
            type:"post"
        },
         "createdRow": function( row, data, dataIndex ) {
             $(row).attr('data-customer', data['id']);
            //  console.log(data);
        },
        "columns": columns,
        'language': {

            'lengthMenu': '_MENU_ {{__("db.records per page")}}',
             "info":      '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search":  '{{__("db.Search")}}',
            'paginate': {
                    'previous': '<i class="ti ti-chevron-left"></i>',
                    'next': '<i class="ti ti-chevron-right"></i>'
            }
        },
        order:[['1', 'desc']],
        'columnDefs': [
            {
                "orderable": false,
                'targets': [0, 2, 3, 4, 5, 6, 7 ]
            },
            {
                'render': function(data, type, row, meta){
                    if(type === 'display'){
                        data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                    }

                   return data;
                },
                'checkboxes': {
                   'selectRow': true,
                   'selectAllRender': '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                },
                'targets': [0]
            }
        ],
        'select': { style: 'multi',  selector: 'td:first-child'},
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        rowId: 'ObjectID',
        buttons: buttons
    } );

  $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

  if(all_permission.indexOf("customers-delete") == -1)
        $('.buttons-delete').addClass('d-none');
</script>
@endpush
