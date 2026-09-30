@extends('backend.layout.main')
@section('content')
    <x-error-message key="not_permitted" />

    <section class="forms">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h4>{{ __('db.Add Sale Agent') }}</h4>
                        </div>
                        <div class="card-body">
                            <p class="italic">
                                <small>{{ __('db.The field labels marked with are required input fields') }}.</small></p>
                            <form action="{{ route('sale-agents.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.name') }} *</label>
                                        <input type="text" name="name" required class="form-control">
                                    </div>
                                </div>
                                <input type="hidden" name="department_id" value="0">

                                <div class="form-group">
                                    <label>{{ __('db.Image') }}</label>
                                    <input type="file" name="image" class="form-control">
                                    @if ($errors->has('image'))
                                        <span><strong>{{ $errors->first('image') }}</strong></span>
                                    @endif
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.email') }} *</label>
                                        <input type="email" name="email" required class="form-control">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Phone Number') }} *</label>
                                        <input type="text" name="phone_number" required class="form-control">
                                    </div>
                                </div>

                                {{-- <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.WhatsApp Number') }}</label>
                                        <input type="text" name="wa_number" class="form-control">
                                    </div>
                                </div> --}}

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Address') }} *</label>
                                        <input type="text" name="address" required class="form-control">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.City') }} *</label>
                                        <input type="text" name="city" required class="form-control">
                                    </div>
                                </div>

                                {{-- <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.State') }}</label>
                                        <input type="text" name="state" class="form-control">
                                    </div>
                                </div> --}}

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Country') }}</label>
                                        <input type="text" name="country" class="form-control">
                                    </div>
                                </div>

                                {{-- Sale Agent Commission Section --}}
                                <div class="col-md-12 mt-4">
                                    <div class="form-group d-none">
                                        <label>
                                            <input type="checkbox" name="is_sale_agent" id="is_sale_agent" value="1">
                                            {{ __('db.Is Sale Agent?') }}
                                        </label>
                                    </div>

                                    <div id="sale-agent-section" class="border p-3 rounded" >
                                        <h5>{{ __('db.Sales Target') }}</h5>
                                        <div id="commission-wrapper">
                                            <div class="commission-row row mb-2">
                                                <div class="col-md-3">
                                                    <input type="number" name="sales_target[0][sales_from]"
                                                        class="form-control" placeholder="{{ __("db.Total Sales Amount From") }}">
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="number" name="sales_target[0][sales_to]"
                                                        class="form-control" placeholder="{{ __('db.Total Sales Amount To') }}">
                                                    <small class="text-danger error-message"></small>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="number" step="0.01" name="sales_target[0][percent]"
                                                        class="form-control" placeholder="{{ __('db.Commission Percent') }}" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <button type="button" class="btn btn-danger remove-row">{{ __("db.Cancel") }}</button>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-success mt-2"
                                            id="add-more">{{ __('db.Add More') }}</button>
                                    </div>
                                </div>

                                {{-- User Account Section --}}
                                <div class="col-md-3 mt-4">
                                    <div class="form-group">
                                        <input type="checkbox" name="user" id="add-user" value="1" />&nbsp;
                                        <label for="add-user">{{ __('db.Add User') }}
                                            <x-info
                                                title="If checked, sale agent will be able to login with username and password you set"
                                                type="info" />
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-3 user-input">
                                    <div class="form-group">
                                        <label>{{ __('db.username') }} *</label>
                                        <input type="text" name="username" class="form-control">
                                    </div>
                                </div>

                                <div class="col-md-3 user-input">
                                    <div class="form-group">
                                        <label>{{ __('db.Password') }} *</label>
                                        <input type="password" name="password" class="form-control">
                                    </div>
                                </div>
                                {{-- Role Select --}}
                                <div class="col-md-3 user-input">
                                    <div class="form-group">
                                        <label>{{ __('db.Role') }} *</label>
                                        <select name="role_id" class="selectpicker form-control">
                                            @foreach ($lims_role_list as $role)
                                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 user-input">
                                    <div class="form-group" id="warehouse">
                                        <label>{{ __('db.Warehouse') }} *</label>
                                        <select name="warehouse_id" class="selectpicker form-control" data-live-search="true"
                                            data-live-search-style="begins" title="Select Warehouse...">
                                            @foreach ($lims_warehouse_list as $warehouse)
                                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 user-input">
                                    <div class="form-group" id="biller">
                                        <label>{{ __('db.Biller') }} *</label>
                                        <select name="biller_id" class="selectpicker form-control" data-live-search="true"
                                            data-live-search-style="begins" title="Select Biller...">
                                            @foreach ($lims_biller_list as $biller)
                                                <option value="{{ $biller->id }}">{{ $biller->name }}
                                                    ({{ $biller->company_name }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mt-4">
                                <input type="submit" value="{{ __('db.submit') }}" class="btn btn-primary">
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


        // Toggle user account inputs
        $('.user-input').hide();
        $('#warehouse').hide();
        $('#biller').hide();
        $('.user-role').hide();
        $('input[name="user"]').on('change', function() {
            let isChecked = $(this).is(':checked');
            $('.user-input').toggle(isChecked);
            $('.user-role').toggle(isChecked);
            $('input[name="username"]').prop('required', isChecked);
            $('input[name="password"]').prop('required', isChecked);
        });

        // Sale agent commission toggle
        $('#is_sale_agent').on('change', function() {
            $('#sale-agent-section').toggle(this.checked);
        });

        // Add more commission rows
        let rowIndex = 1;
        $('#add-more').on('click', function() {
            $('#commission-wrapper').append(`
            <div class="commission-row row mb-2">
                <div class="col-md-3">
                    <input type="number" name="sales_target[${rowIndex}][sales_from]" class="form-control" placeholder="Total Sales Amount From">
                </div>
                <div class="col-md-3">
                    <input type="number" name="sales_target[${rowIndex}][sales_to]" class="form-control" placeholder="Total Sales Amount To">
                    <small class="text-danger error-message"></small>
                </div>
                <div class="col-md-3">
                    <input type="number" step="0.01" name="sales_target[${rowIndex}][percent]" class="form-control" placeholder="Commission Percent" required>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-danger remove-row">Cancel</button>
                </div>
            </div>
        `);
            rowIndex++;
        });

        // Remove row
        $(document).on('click', '.remove-row', function() {
            $(this).closest('.commission-row').remove();
            validateSalesTargets();
        });

        // Inline validation on input
        $(document).on("input", "#commission-wrapper input", function() {
            validateSalesTargets();
        });

        function validateSalesTargets() {
            let rows = $("#commission-wrapper .commission-row");
            let prevTo = null;
            let isValid = true;

            rows.each(function(index) {
                let fromInput = $(this).find("input[name*='[sales_from]']");
                let toInput = $(this).find("input[name*='[sales_to]']");
                let errorMsg = $(this).find(".error-message");

                let fromVal = parseFloat(fromInput.val()) || 0;
                let toVal = parseFloat(toInput.val()) || 0;

                // reset
                errorMsg.text("");
                fromInput.removeClass("is-invalid");
                toInput.removeClass("is-invalid");

                // Rule 1: from < to
                if (fromVal >= toVal && toVal !== 0) {
                    errorMsg.text("From must be less than To");
                    fromInput.addClass("is-invalid");
                    toInput.addClass("is-invalid");
                    isValid = false;
                }

                // Rule 2: previous "to" < current "from"
                if (prevTo !== null && fromVal <= prevTo && fromVal !== 0) {
                    errorMsg.text("This 'From' must be greater than previous row's 'To'");
                    fromInput.addClass("is-invalid");
                    isValid = false;
                }

                prevTo = toVal;
            });

            return isValid;
        }

        // Prevent invalid submit
        $("form").on("submit", function(e) {
            if (!validateSalesTargets()) {
                e.preventDefault();
            }
        });

        @if (!in_array('project', explode(',', gen_setting()->modules)))
            $('select[name="role_id"]').on('change', function() {
                if ($(this).val() > 2) {
                    $('#warehouse').show(400);
                    $('#biller').show(400);
                    $('select[name="warehouse_id"]').prop('required', true);
                    $('select[name="biller_id"]').prop('required', true);
                } else {
                    $('#warehouse').hide(400);
                    $('#biller').hide(400);
                    $('select[name="warehouse_id"]').prop('required', false);
                    $('select[name="biller_id"]').prop('required', false);
                }
            });
        @endif
    </script>

    <style>
        .is-invalid {
            border: 2px solid red !important;
            background-color: #ffe6e6;
        }

        .error-message {
            font-size: 12px;
        }
    </style>
@endpush
