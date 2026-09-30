@extends('backend.layout.main')
@push('css')
@endpush

@section('content')


    <x-error-message key="not_permitted" />
    <x-success-message key="customMessage" />

    <section class="forms">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h4>{{ __('db.Edit Invoice Setting') }}</h4>
                        </div>
                        <div class="card-body">
                            <p class="italic">
                                <small>{{ __('db.The field labels marked with are required input fields') }}.</small>
                            </p>

                            <form action="{{ route('settings.invoice.update', $invoice->id) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <div class="row">
                                    <div class="col-md-4 form-group">

                                        <label>{{ __('db.Invoice Type') }}</label>

                                        <select name="size" disabled class="form-control">
                                            <option value="a4" {{ $invoice->size == 'a4' ? 'selected' : '' }}>A4
                                            </option>
                                            <option value="a4_bilingual" {{ $invoice->size == 'a4_bilingual' ? 'selected' : '' }}>A4 (Bilingual / ثنائي اللغة)
                                            </option>
                                            <option value="58mm" {{ $invoice->size == '58mm' ? 'selected' : '' }}>58mm
                                                (Thermal
                                                receipt)</option>
                                            <option value="80mm" {{ $invoice->size == '80mm' ? 'selected' : '' }}>80mm
                                                (Thermal
                                                receipt)</option>
                                            <option value="80mm_bilingual" {{ $invoice->size == '80mm_bilingual' ? 'selected' : '' }}>80mm (Bilingual / ثنائي اللغة)
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label>{{ __('db.Template Name') }} *</label>
                                        <input type="text" name="template_name"
                                            value="{{ old('template_name', $invoice->template_name) }}" required
                                            class="form-control">
                                        @error('template_name')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="col-md-4 form-group">
                                        <label>{{ __('db.Prefix') }} *</label>

                                        <input type="text" id="prefix" name="prefix" class="form-control" required
                                            minlength="2" maxlength="11" value="{{ old('prefix', $invoice->prefix) }}">
                                        <small id="prefix-message" style="display: block; margin-top: 5px;"></small>

                                        @error('prefix')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="col-md-4 form-group">
                                        <label>{{ __('db.Numbering Type') }} *</label>
                                        <select name="numbering_type" class="form-control numberingType" required>
                                            <option value="sequential"
                                                {{ $invoice->numbering_type === 'sequential' ? 'selected' : '' }}>
                                                {{ __('db.Sequential') }}</option>
                                            <option value="random"
                                                {{ $invoice->numbering_type === 'random' ? 'selected' : '' }}>
                                                {{ __('db.Random') }}</option>
                                            <option value="datewise"
                                                {{ $invoice->numbering_type === 'datewise' ? 'selected' : '' }}>
                                                {{ __('db.Date Wise') }}</option>

                                        </select>
                                    </div>

                                    <div class="col-md-4 form-group">
                                        <label>{{ __('db.Company Logo') }}</label>
                                        <input type="file" name="company_logo" class="form-control">
                                        @if ($invoice->company_logo)
                                            <img src="{{ url('invoices', $invoice->company_logo) }}" height="50" class="mt-2">
                                        @endif
                                    </div>

                                    <div class="col-md-4 form-group">
                                        <label>{{ __('db.Logo Height') }}</label>
                                        <input type="number" name="logo_height"
                                            value="{{ old('logo_height', $invoice->logo_height) }}" class="form-control">
                                    </div>

                                    <div class="col-md-4 form-group">
                                        <label>{{ __('db.Logo Width') }}</label>
                                        <input type="number" name="logo_width"
                                            value="{{ old('logo_width', $invoice->logo_width) }}" class="form-control">
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label
                                                for="invoice_date_format">{{ __('db.Invoice Date Format') }}</label>
                                            <select name="invoice_date_format" id="invoice_date_format"
                                                class="form-control">
                                                <option value="d.m.y h:i A"
                                                    {{ $invoice->invoice_date_format == 'd.m.y h:i A' ? 'selected' : '' }}>
                                                    d.m.y h:i A</option>
                                                <option value="m.d.y h:i A"
                                                    {{ $invoice->invoice_date_format == 'm.d.y h:i A' ? 'selected' : '' }}>
                                                    m.d.y h:i A</option>
                                                <option value="y.m.d h:i A"
                                                    {{ $invoice->invoice_date_format == 'y.m.d h:i A' ? 'selected' : '' }}>
                                                    y.m.d h:i A</option>
                                                <option value="d-m-y h:i A"
                                                    {{ $invoice->invoice_date_format == 'd-m-y h:i A' ? 'selected' : '' }}>
                                                    d-m-y h:i A</option>
                                                <option value="y-m-d h:i A"
                                                    {{ $invoice->invoice_date_format == 'y-m-d h:i A' ? 'selected' : '' }}>
                                                    y-m-dd h:i A</option>
                                                <option value="d/m/y h:i A"
                                                    {{ $invoice->invoice_date_format == 'd/m/y h:i A' ? 'selected' : '' }}>
                                                    d/m/y h:i A</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-4 form-group digit_number">
                                        <label>{{ __('db.number_of_digits') }} *</label>
                                        <input type="number" name="number_of_digit"
                                            value="{{ old('number_of_digit', $invoice->number_of_digit) }}" required
                                            class="form-control">
                                        @error('number_of_digit')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>


                                    <div class="col-md-4 form-group start_number">
                                        <label>{{ __('db.Start Number') }}</label>
                                        <input type="text" name="start_number"
                                            value="{{ old('start_number', $invoice->start_number) }}" class="form-control">
                                    </div>

                                    <div class="col-md-4 form-group">
                                        <label>{{ __('db.Header Text') }}</label>
                                        <textarea class="form-control validate-input" name="header_text"
                                                id="header_text" data-min="2"
                                                data-target="#header-text-message"
                                                rows="3">{{ old('header_text', $invoice->header_text) }}</textarea>
                                        <small id="header-text-message" style="display: block; margin-top: 5px;"></small>
                                    </div>

                                    @if($invoice->size == 'a4' || $invoice->size == 'a4_bilingual')
                                    <div class="col-md-4 form-group">
                                        <label>{{ __('db.Primary Color') }}</label>
                                        <input type="color" name="primary_color" id="colorPicker"
                                            value="{{ old('primary_color', $invoice->primary_color) }}"
                                            class="form-control">
                                    </div>
                                    @endif


                                    <div class="col-md-12 form-group">
                                        <label>{{ __('db.Footer Text') }}</label>
                                        <textarea class="form-control validate-input" name="footer_text"
                                                id="footer_text" data-min="2"
                                                data-target="#footer-text-message"
                                                rows="3">{{ old('footer_text', $invoice->footer_text) }}</textarea>
                                        <small id="footer-text-message" style="display: block; margin-top: 5px;"></small>
                                    </div>
                                </div>

                                <div class="row">
                                    @php

                                        if($invoice->size == '58mm' || $invoice->size == '80mm' || $invoice->size == '80mm_bilingual'){;
                                            $checkboxes = [
                                                'active_logo_height_width' => 'db.Active Logo Height Width',
                                                'show_ref_number' => 'db.Show Reference No',
                                                'active_generat_settings' => 'db.Auto Generate Numbering Type',
                                                'active_date_format' => 'db.Active Date Format',
                                                'show_warehouse_info' => 'db.Show Warehouse Info',
                                                'show_description' => 'db.Show Description',
                                                'show_paid_info' => 'db.Show Paid Info',
                                                'hide_total_due' => 'db.Hide Total Due',
                                                'show_in_words' => 'db.Show Amount In Words',
                                                'show_biller_info' => 'db.Served By',
                                                'show_footer_text' => 'db.Show Footer Text',
                                                'show_barcode' => 'db.Show Barcode',
                                                'show_qr_code' => 'db.Show QR Code',
                                                'show_vat_registration_number' => 'db.Show Vat Registration Number',
                                                'show_sale_note' => 'db.Show Sale Note',
                                                'show_customer_name' => 'db.show_customer_name',
                                            ];
                                        }else{
                                            $checkboxes = [
                                                'active_logo_height_width' => 'db.Active Logo Height Width',
                                                'show_ref_number' => 'db.Show Reference No',
                                                'active_generat_settings' => 'db.Auto Generate Numbering Type',
                                                'active_date_format' => 'db.Active Date Format',
                                                'show_warehouse_info' => 'db.Show Warehouse Info',
                                                'show_bill_to_info' => 'db.Show Bill To Info',
                                                'show_biller_info' => 'db.Served By',
                                                'show_payment_note' => 'db.Show Payment Note',
                                                'hide_total_due' => 'db.Hide Total Due',
                                                'show_in_words' => 'db.Show Amount In Words',
                                                'show_footer_text' => 'db.Show Footer Text',
                                                'show_barcode' => 'db.Show Barcode',
                                                'show_qr_code' => 'db.Show QR Code',
                                                'active_primary_color' => 'db.Active Primary Color',
                                                'show_vat_registration_number' => 'db.Show Vat Registration Number',
                                                'show_sale_note' => 'db.Show Sale Note',
                                                'show_customer_name' => 'db.show_customer_name',
                                            ];
                                        }

                                        // Decode JSON if it's stored as string in DB
                                        $show_column = old('show_column', $invoice->show_column ?? []);
                                        if (is_string($show_column)) {
                                            $show_column = json_decode($show_column, true);
                                        }
                                    @endphp
                                    <hr>
                                    <div class="col-md-12 mb-2">
                                        <label class="custom-checkbox">
                                            <input type="checkbox" id="select-all">
                                            {{ __('db.Select All') }}
                                        </label>
                                    </div>

                                    @foreach ($checkboxes as $field => $label)
                                        <div class="col-md-4">
                                            <div class="checkbox-item">
                                                <label class="custom-checkbox">
                                                    <input type="checkbox" name="show_column[{{ $field }}]"
                                                        value="1"
                                                        {{ isset($show_column[$field]) && $show_column[$field] ? 'checked' : '' }}>
                                                    {{-- <span class="checkmark"></span> --}}
                                                    {{ __($label) }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach

                                </div>


                                <div class="form-group mt-3">
                                    <button type="submit" class="btn btn-primary">{{ __('db.update') }}</button>
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
    <script>
        tinymce.init({
            selector: 'textarea:not(.no-tiny)',
            height: 130,
            plugins: [
            'advlist autolink lists link image charmap print preview anchor textcolor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table contextmenu paste code wordcount'
            ],
            toolbar: 'insert | undo redo |  formatselect | bold italic backcolor  | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat',
            branding:false
        });
        $('.numberingType').on('change', function() {
            var type = $(this).val();

            if (type == 'sequential') {
                $('.digit_number').hide();
                $('.start_number').show();
            } else if (type == 'random') {
                $('.digit_number').show();
                $('.start_number').hide();
            } else {
                $('.digit_number').hide();
                $('.start_number').hide();
            }
            // console.log(type);
        });

        $('.numberingType').trigger('change');

        $('input[name="number_of_digit"]').on('input', function() {
            var value = parseInt($(this).val(), 10);
            if (value < 6 || value > 12) {
                $(this).css('border', '2px solid red');
            } else {
                $(this).css('border', '2px solid green');
            }
        });
        $('#select-all').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('input[name^="show_column"]').prop('checked', isChecked);
        });


        $('#prefix').on('input', function() {
            var length = $(this).val().length;

            if (length == 0) {
                $('#prefix-message').text('').css('color', '');
            } else if (length < 2) {
                $('#prefix-message').text('Minimum 2 characters required.').css('color', 'red');
            } else if (length > 10) {
                $('#prefix-message').text('Maximum 10 characters allowed.').css('color', 'red');
            } else {
                $('#prefix-message').text('Good input ✅').css('color', 'green');
            }
        });

        $('.validate-input').on('input', function() {
            let $this = $(this);
            let min = parseInt($this.data('min'));
            let max = parseInt($this.data('max'));
            let target = $this.data('target');
            let value = $this.val();
            let message = '';
            let color = 'red';

            // If user enters more than max, block it and show error
            if (value.length > max) {
                $this.val(value.substring(0, max));
                message = `Maximum ${max} characters allowed.`;
            } else if (value.length < min) {
                message = `Must be at least ${min} characters.`;
            } else {
                message = 'Looks good!';
                color = 'green';
            }

            $(target).text(message).css('color', color);
        });
    </script>
@endpush
