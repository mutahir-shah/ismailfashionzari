@extends('backend.layout.main')
@push('css')
@endpush
@section('content')

    <x-error-message key="not_permitted" />

    <section class="forms">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h4>{{ __('db.Add Invoice Setting') }}</h4>
                        </div>
                        <div class="card-body">
                            <p class="italic">
                                <small>{{ __('db.The field labels marked with are required input fields') }}.</small>
                            </p>
                            <form action="{{ route('settings.invoice.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Invoice Type') }}</label>
                                        <select name="size" id="" class="form-control">
                                            <option value="a4">A4</option>
                                            <option value="a4_bilingual">A4 (Bilingual / ثنائي اللغة)</option>
                                            <option value="58mm">58mm(Thermal receipt)</option>
                                            <option value="80mm">80mm(Thermal receipt)</option>
                                            <option value="80mm_bilingual">80mm (Bilingual / ثنائي اللغة)</option>
                                        </select>
                                        {{-- <input type="text" name="size" class="form-control"> --}}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Template Name') }} * </label>
                                        <input type="text" name="template_name" required class="form-control">
                                        @if ($errors->has('template_name'))
                                            <small>
                                                {{ $errors->first('nametemplate_name') }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>{{ __('db.Prefix') }} *</label>

                                    <input type="text" id="prefix" name="prefix" class="form-control" required
                                        minlength="2" maxlength="11" value="{{ old('prefix') }}">
                                    <small id="prefix-message" style="display: block; margin-top: 5px;"></small>

                                    @error('prefix')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Numbering Type') }} *</label>
                                        <select type="text" name="numbering_type" placeholder="4"
                                            class="form-control numberingType">
                                            <option value="sequential">{{ __('db.Sequential') }}</option>
                                            <option value="random">{{ __('db.Random') }}</option>
                                            <option value="datewise">{{ __('db.Date Wise') }}</option>
                                        </select>
                                        @if ($errors->has('numbering_type'))
                                            <small>
                                                <strong
                                                    class="text-danger">{{ $errors->first('number_of_digit') }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4 digit_number">
                                    <div class="form-group">
                                        <label>{{ __('db.validation_digit_range', ['min' => 6, 'max' => 12]) }}*</label>
                                        <input type="number" name="number_of_digit" placeholder="6" required min="6"
                                            value="6" max="12" class="form-control">


                                    </div>
                                </div>

                                <div class="col-md-4 start_number">
                                    <div class="form-group">
                                        <label>{{ __('db.Start Number') }} *</label>
                                        <input type="number" required name="start_number"
                                            class="form-control customer-input">
                                    </div>
                                </div>


                                <div class="col-md-4 form-group">
                                    <label>{{ __('db.Header Text') }}</label>
                                    <input type="text" class="form-control validate-input" name="header_text"
                                        id="header_text" data-min="2" data-max="100"
                                        data-target="#header-text-message"
                                        value="{{ old('header_text') }}">
                                    <small id="header-text-message" style="display: block; margin-top: 5px;"></small>
                                </div>

                                <div class="col-md-4 form-group">
                                    <label>{{ __('db.Footer Text') }}</label>
                                    <input type="text" class="form-control validate-input" name="footer_text"
                                        id="footer_text" data-min="2" data-max="100"
                                        data-target="#footer-text-message"
                                        value="{{ old('footer_text') }}">
                                    <small id="footer-text-message" style="display: block; margin-top: 5px;"></small>
                                </div>

                                {{-- <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.footer_title') }}</label>
                                        <input type="text" name="footer_title" class="form-control">
                                    </div>
                                </div> --}}

                                <div class="col-md-4 form-group">
                                    <label>{{ __('db.Company Logo') }}</label>
                                    <input type="file" name="company_logo" class="form-control">

                                </div>

                                <div class="col-md-4 form-group">
                                    <label>{{ __('db.Logo Height') }}</label>
                                    <input type="number" name="logo_height" value="{{ old('logo_height',80) }}"
                                        class="form-control">
                                </div>

                                <div class="col-md-4 form-group">
                                    <label>{{ __('db.Logo Width') }}</label>
                                    <input type="number" name="logo_width" value="{{ old('logo_width',120) }}"
                                        class="form-control">
                                </div>


                                <div class="col-md-4 form-group">
                                    <label>{{ __('db.Primary Color') }}</label>
                                    <input type="color" name="primary_color" id="colorPicker"
                                        value="{{ old('primary_color', '#0036B3') }}" class="form-control">
                                </div>


                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label
                                            for="invoice_date_format">{{ __('db.Invoice Date Format') }}</label>
                                        <select name="invoice_date_format" id="invoice_date_format" class="form-control">
                                            <option value="d.m.y h:i A">d.m.y h:i A</option>
                                            <option value="m.d.y h:i A">m.d.y h:i A</option>
                                            <option value="y.m.d h:i A">y.m.d h:i A</option>
                                            <option value="d-m-y h:i A">d-m-y h:i A</option>
                                            <option value="y-m-d h:i A">y-m-dd h:i A</option>
                                            <option value="d/m/y h:i A">d/m/y h:i A</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.preview_image') }}</label>
                                        <input type="file" name="preview_invoice" class="form-control">
                                    </div>
                                </div> --}}
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-grou" style="margin-left: -15px;">
                                        <input class="mt-2" type="checkbox" name="is_default" value="1">
                                        <label class="mt-2">{{ __('db.Default') }}</label>
                                    </div>
                                </div>

                                @php
                                    $checkboxes = [
                                        'show_barcode' => 'db.Show Barcode',
                                        'show_qr_code' => 'db.Show QR Code',
                                        'show_description' => 'db.Show Description[58mm,80mm]',
                                        'show_in_words' => 'db.Show Amount In Words',
                                        'active_primary_color' => 'db.Active Primary Color',
                                        'show_warehouse_info' => 'db.Show Warehouse Info',
                                        'show_bill_to_info' => 'db.Show Bill To Info',
                                        'show_biller_info' => 'db.Served By',
                                        'show_paid_info' => 'db.Show Paid Info',
                                        'show_footer_text' => 'db.Show Footer Text',
                                        'show_payment_note' => 'db.Show Payment Note',
                                        'show_ref_number' => 'db.Show Reference No',
                                        'active_date_format' => 'db.Active Date Format',
                                        'active_generat_settings' => 'db.Auto Generate Numbering Type',
                                        'active_logo_height_width' => 'db.Active Logo Height Width',
                                        'hide_total_due' => 'db.Hide Total Due',
                                        'show_vat_registration_number' => 'db.Show Vat Registration Number',
                                        'show_sale_note' => 'db.Show Sale Note',
                                        'show_customer_name' => 'db.show_customer_name',
                                    ];

                                    $show_column = old('show_column', $invoice->show_column ?? []);
                                    if (is_string($show_column)) {
                                        $show_column = json_decode($show_column, true);
                                    }
                                @endphp



                                <div class="col-md-12 mb-2" style="margin-left: -15px">
                                    <label class="form-grou">
                                        <input type="checkbox" id="select-all">
                                        {{ __('db.Select All') }}
                                    </label>
                                </div>


                                <div class="row">
                                    @foreach ($checkboxes as $field => $label)
                                        <div class="col-md-4 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox"
                                                    name="show_column[{{ $field }}]" value="1"
                                                    id="checkbox_{{ $field }}">
                                                <label class="form-check-label" for="checkbox_{{ $field }}">
                                                    {{ __($label) }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                            </div>

                            <div class="form-group">
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


        // prefix validation
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
