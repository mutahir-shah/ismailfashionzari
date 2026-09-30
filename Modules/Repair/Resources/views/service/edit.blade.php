@extends('backend.layout.main')

@push('css')
    <style>
        #vehicle-section,
        #device-section {
            display: none;
        }
    </style>
@endpush

@section('content')
    <section class="forms">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h4>{{ __('db.edit_service_job') }} — <small class="text-muted">{{ $job->reference_no }}</small>
                            </h4>
                            <a href="{{ route('repair.service.show', $job->id) }}" class="btn btn-secondary btn-sm">
                                <i class="ti ti-arrow-left"></i> {{ __('db.back_to_job') }}
                            </a>
                        </div>
                        <div class="card-body">

                            <form id="service-form" method="POST" action="{{ route('repair.service.update', $job->id) }}" novalidate>
                                @csrf
                                @method('PUT')

                                {{-- ════ SECTION 1: Basic Info ════ --}}
                                <h3 class="mb-3 border-bottom pb-2">{{ __('db.basic_information') }}</h3>
                                <div class="row">

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('db.customer') }}</label>
                                            <select name="customer_id" class="selectpicker form-control"
                                                data-live-search="true">
                                                @foreach ($lims_customer_list as $customer)
                                                    <option value="{{ $customer->id }}"
                                                        {{ $job->customer_id == $customer->id ? 'selected' : '' }}>
                                                        {{ $customer->name }}
                                                        @if($customer->phone_number) — {{ $customer->phone_number }} @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('customer_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('db.Warehouse') }} *</label>
                                            <select required name="warehouse_id" class="selectpicker form-control"
                                                data-live-search="true">
                                                @foreach ($lims_warehouse_list as $wh)
                                                    <option value="{{ $wh->id }}"
                                                        {{ $job->warehouse_id == $wh->id ? 'selected' : '' }}>
                                                        {{ $wh->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('warehouse_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('db.service_type') }}</label>
                                            <input type="text" class="form-control"
                                                value="{{ ucfirst($job->service_type) }}" readonly />
                                        </div>
                                    </div>

                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label>{{ __('db.job_title') }} *</label>
                                            <input type="text" required name="title" class="form-control"
                                                value="{{ old('title', $job->title) }}" />
                                            @error('title')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('db.expected_delivery_date') }}</label>
                                            <input type="text" name="expected_delivery_date" class="form-control date"
                                                value="{{ $job->expected_delivery_date ? $job->expected_delivery_date->format('d-m-Y') : '' }}" />
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{ __('db.status') }}</label>
                                            <select name="status" class="form-control">
                                                @foreach (['pending', 'diagnosed', 'in_progress', 'completed', 'delivered', 'cancelled'] as $s)
                                                    <option value="{{ $s }}"
                                                        {{ $job->status === $s ? 'selected' : '' }}>
                                                        {{ ucfirst(str_replace('_', ' ', $s)) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{ __('db.priority') }}</label>
                                            <select name="priority" class="form-control">
                                                <option value="low" {{ $job->priority === 'low' ? 'selected' : '' }}>
                                                    {{ __('db.low') }}</option>
                                                <option value="medium" {{ $job->priority === 'medium' ? 'selected' : '' }}>
                                                    {{ __('db.medium') }}</option>
                                                <option value="high" {{ $job->priority === 'high' ? 'selected' : '' }}>
                                                    {{ __('db.high') }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{ __('db.technician') }}</label>
                                            <select name="assigned_to" class="selectpicker form-control"
                                                data-live-search="true" title="{{ __('db.select_technician') }}">
                                                @foreach ($lims_technician_list as $tech)
                                                    <option value="{{ $tech->id }}"
                                                        {{ $job->assigned_to == $tech->id ? 'selected' : '' }}>
                                                        {{ $tech->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{ __('db.amount_paid') }}</label>
                                            <input type="number" name="paid_amount" class="form-control"
                                                value="{{ $job->paid_amount }}" step="any" min="0" />
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>{{ __('db.Description') }}</label>
                                            <textarea name="description" rows="2" class="form-control no-tiny">{{ $job->description }}</textarea>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>{{ __('db.status_update_note') }}</label>
                                            <input type="text" name="status_note" class="form-control"
                                                value="{{ $job->status_note }}" />
                                        </div>
                                    </div>

                                </div>

                                {{-- ════ SECTION 2: Device Details ════ --}}
                                @if ($job->service_type === 'device' && $job->device)
                                    <div id="device-section" style="display:block">
                                        <h5 class="mb-3 mt-3 text-info border-bottom pb-2">📱 {{ __('db.device_details') }}
                                        </h5>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>{{ __('db.device_type') }}</label>
                                                    <select required name="device_type_id" class="selectpicker form-control" data-live-search="true" title="{{ __('db.Please select') }}">
                                                        @foreach ($device_types as $type)
                                                            <option value="{{ $type->id }}"
                                                                {{ $job->device->device_type == $type->id ? 'selected' : '' }}>
                                                                {{ $type->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>{{ __('db.Brand') }}</label>
                                                    <select required name="device_brand" class="selectpicker form-control"
                                                        data-live-search="true" title="{{ __('db.select_brand') }}">
                                                        @foreach ($lims_brand_list as $brand)
                                                            <option value="{{ $brand->title }}"
                                                                {{ $job->device->brand == $brand->title ? 'selected' : '' }}>
                                                                {{ $brand->title }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>{{ __('db.model') }}</label>
                                                    <input type="text" name="device_model" class="form-control"
                                                        value="{{ $job->device->model }}" />
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>{{ __('db.serial_number') }}</label>
                                                    <input type="text" name="serial_number" class="form-control"
                                                        value="{{ $job->device->serial_number }}" />
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>{{ __('db.imei') }}</label>
                                                    <input type="text" name="imei" class="form-control"
                                                        value="{{ $job->device->imei }}" />
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>{{ __('db.password_hint') }}</label>
                                                    <input type="text" name="password_hint" class="form-control"
                                                        value="{{ $job->device->password_hint }}" />
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>{{ __('db.accessories') }}</label>
                                                    <input type="text" name="accessories" class="form-control"
                                                        value="{{ $job->device->accessories }}" />
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>{{ __('db.issue_reported_by_customer') }}</label>
                                                    <textarea name="device_issue_reported" rows="2" class="form-control no-tiny">{{ $job->device->issue_reported }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>{{ __('db.condition_on_arrival') }}</label>
                                                    <textarea name="device_condition_notes" rows="2" class="form-control no-tiny">{{ $job->device->condition_notes }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- ════ SECTION 3: Vehicle Details ════ --}}
                                @if ($job->service_type === 'vehicle' && $job->vehicle)
                                    <div id="vehicle-section" style="display:block">
                                        <h5 class="mb-3 mt-3 text-warning border-bottom pb-2">🚗
                                            {{ __('db.vehicle_details') }}</h5>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>{{ __('db.vehicle_type') }}</label>
                                                    <select required name="vehicle_type_id" class="selectpicker form-control" data-live-search="true" title="{{ __('db.Please select') }}">
                                                        @foreach ($vehicle_types as $type)
                                                            <option value="{{ $type->id }}"
                                                                {{ $job->vehicle->vehicle_type == $type->id ? 'selected' : '' }}>
                                                                {{ $type->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>{{ __('db.Brand') }}</label>
                                                    <select required name="vehicle_brand" class="selectpicker form-control"
                                                        data-live-search="true" title="{{ __('db.select_brand') }}">
                                                        @foreach ($lims_brand_list as $brand)
                                                            <option value="{{ $brand->title }}"
                                                                {{ $job->vehicle?->brand == $brand->title ? 'selected' : '' }}>
                                                                {{ $brand->title }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>{{ __('db.model') }}</label>
                                                    <input type="text" name="vehicle_model" class="form-control"
                                                        value="{{ $job->vehicle->model }}" />
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>{{ __('db.year') }}</label>
                                                    <input type="number" name="vehicle_year" class="form-control"
                                                        value="{{ $job->vehicle->year }}" />
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>{{ __('db.fuel_level') }}</label>
                                                    <select name="fuel_level" class="form-control">
                                                        @foreach (['empty', 'quarter', 'half', 'three_quarter', 'full'] as $fl)
                                                            <option value="{{ $fl }}"
                                                                {{ $job->vehicle->fuel_level === $fl ? 'selected' : '' }}>
                                                                {{ ucfirst(str_replace('_', ' ', $fl)) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>{{ __('db.registration_no') }}</label>
                                                    <input type="text" name="registration_no" class="form-control"
                                                        value="{{ $job->vehicle->registration_no }}" />
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>{{ __('db.engine_no') }}</label>
                                                    <input type="text" name="engine_no" class="form-control"
                                                        value="{{ $job->vehicle->engine_no }}" />
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>{{ __('db.chassis_no') }}</label>
                                                    <input type="text" name="chassis_no" class="form-control"
                                                        value="{{ $job->vehicle->chassis_no }}" />
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>{{ __('db.mileage') }}</label>
                                                    <input type="number" name="mileage" class="form-control"
                                                        value="{{ $job->vehicle->mileage }}" />
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>{{ __('db.issue_reported_by_customer') }}</label>
                                                    <textarea name="vehicle_issue_reported" rows="2" class="form-control no-tiny">{{ $job->vehicle->issue_reported ?? '' }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>{{ __('db.condition_on_arrival') }}</label>
                                                    <textarea name="vehicle_condition_notes" rows="2" class="form-control no-tiny">{{ $job->vehicle->condition_notes }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="col-md-12 px-0 mt-3">
                                    <div class="form-group">
                                        <label>{{ __('db.note') }}</label>
                                        <textarea name="note" rows="2" class="form-control no-tiny">{{ $job->note }}</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-checkmark"></i> {{ __('db.save_changes') }}
                                    </button>
                                    <a href="{{ route('repair.service.show', $job->id) }}"
                                        class="btn btn-secondary ml-2">{{ __('db.Cancel') }}</a>
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
        $('.date').datepicker({
            format: 'dd-mm-yyyy',
            autoclose: true,
            todayHighlight: true
        });
        $("ul#repair").siblings('a').attr('aria-expanded', 'true');
        $("ul#repair").addClass("show");
        $("ul#repair #service-list-menu").addClass("active");

        // ══════════════════════════════════════════
        // Real-time JS Validation
        // ══════════════════════════════════════════
        function validateField($field) {
            if (!$field.prop('required') || !$field.closest('.form-group').is(':visible')) {
                $field.removeClass('is-invalid');
                if ($field.hasClass('selectpicker')) {
                    $field.parent().css({'border': '', 'border-radius': ''});
                }
                $field.closest('.form-group').find('.custom-error-msg').remove();
                return true;
            }

            var val = $field.val();
            var isValid = val !== '' && val !== null;
            if (!isValid) {
                $field.addClass('is-invalid');
                if ($field.hasClass('selectpicker')) {
                    $field.parent().css({'border': '1px solid #dc3545', 'border-radius': '4px'});
                }
                if ($field.closest('.form-group').find('.custom-error-msg').length === 0) {
                    $field.closest('.form-group').append('<div class="custom-error-msg text-danger" style="font-size: 12px; margin-top: 4px;">This field is required</div>');
                }
            } else {
                $field.removeClass('is-invalid');
                if ($field.hasClass('selectpicker')) {
                    $field.parent().css({'border': '', 'border-radius': ''});
                }
                $field.closest('.form-group').find('.custom-error-msg').remove();
            }
            return isValid;
        }

        $(document).on('input change', 'input[required], select[required], textarea[required]', function() {
            validateField($(this));
        });

        $('#service-form').on('submit', function(e) {
            let isFormValid = true;
            $(this).find('input, select, textarea').each(function() {
                if ($(this).prop('required')) {
                    if (!validateField($(this))) {
                        isFormValid = false;
                    }
                }
            });

            if (!isFormValid) {
                e.preventDefault();
            }
        });
    </script>
@endpush
