@extends('backend.layout.main')

@push('css')
<style>
    #vehicle-section, #device-section { display: none; }
    .timeline { border-left: 3px solid #e4e6fc; margin-left: 15px; padding-left: 20px; }
    .timeline-item { position: relative; margin-bottom: 15px; }
    .timeline-item::before { content: ''; width: 12px; height: 12px; border-radius: 50%; background: #6777ef; position: absolute; left: -27px; top: 5px; }
</style>
@endpush

@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{ __('db.add_service_job') }}</h4>
                    </div>
                    <div class="card-body">

                        <form id="service-form" method="POST" action="{{ route('repair.service.store') }}" novalidate>
                            @csrf

                            {{-- ════ SECTION 1: Basic Info ════ --}}
                            <h3 class="mb-3 border-bottom pb-2">{{ __('db.basic_information') }}</h3>
                            <div class="row">

                                {{-- Customer + Plus --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.customer') }} *</label>
                                        <div class="input-group pos">
                                            <select required name="customer_id" id="customer_id"
                                                class="selectpicker form-control"
                                                data-live-search="true"
                                                title="{{ __('db.select_customer') }}">
                                                @foreach ($lims_customer_list as $customer)
                                                    <option value="{{ $customer->id }}">
                                                        {{ $customer->name }}
                                                        @if($customer->phone_number) — {{ $customer->phone_number }} @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="btn btn-default btn-sm"
                                                data-toggle="modal" data-target="#addCustomerModal">
                                                <i class="ti ti-plus"></i>
                                            </button>
                                        </div>
                                        @error('customer_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Warehouse') }} *</label>
                                        <select required name="warehouse_id" class="selectpicker form-control"
                                            data-live-search="true" title="{{ __('db.select_warehouse') }}">
                                            @foreach ($lims_warehouse_list as $wh)
                                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('warehouse_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                {{-- Service Type — triggers section show/hide --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.service_type') }} *</label>
                                        <select required id="service_type" name="service_type" class="form-control">
                                            <option value="">{{ __('db.select_type') }}</option>
                                            <option value="device">{{ __('db.device') }}</option>
                                            <option value="vehicle">{{ __('db.vehicle') }}</option>
                                        </select>
                                        @error('service_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>{{ __('db.job_title') }} *</label>
                                        <input type="text" required name="title" class="form-control" value="{{ old('title') }}"/>
                                        @error('title')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.expected_delivery_date') }}</label>
                                        <input type="text" name="expected_delivery_date"
                                            class="form-control date" value="{{ date('d-m-Y', strtotime('+3 days')) }}" />
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.priority') }}</label>
                                        <select name="priority" class="form-control">
                                            <option value="low">🟢 {{ __('db.low') }}</option>
                                            <option value="medium" selected>🟡 {{ __('db.medium') }}</option>
                                            <option value="high">🔴 {{ __('db.high') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.status') }}</label>
                                        <select name="status" class="form-control">
                                            <option value="pending" selected>{{ __('db.Pending') }}</option>
                                            <option value="diagnosed">{{ __('db.diagnosed') }}</option>
                                            <option value="in_progress">{{ __('db.in_progress') }}</option>
                                            <option value="completed">{{ __('db.Completed') }}</option>
                                            <option value="delivered">{{ __('db.Delivered') }}</option>
                                            <option value="cancelled">{{ __('db.Cancel') }}</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Technician + Plus --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.assign_technician') }}</label>
                                        <div class="input-group pos">
                                            <select name="assigned_to" id="assigned_to"
                                                class="selectpicker form-control"
                                                data-live-search="true"
                                                title="{{ __('db.select_technician') }}">
                                                @foreach ($lims_technician_list as $tech)
                                                    <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                                                @endforeach
                                            </select>
                                            {{-- <button type="button" class="btn btn-default btn-sm"
                                                data-toggle="modal" data-target="#addTechnicianModal">
                                                <i class="ti ti-plus"></i>
                                            </button> --}}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{{ __('db.Description') }}</label>
                                        <textarea name="description" rows="2" class="form-control no-tiny"></textarea>
                                    </div>
                                </div>
                            </div>

                            {{-- ════ SECTION 2: Device Details ════ --}}
                            <div id="device-section">
                                <h3 class="mb-3 mt-3 border-bottom pb-2">📱 {{ __('db.device_details') }}</h3>
                                <div class="row">

                                    {{-- Device Type from DB + Plus --}}
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{ __('db.device_type') }}</label>
                                            <div class="input-group pos">
                                                <select name="device_type_id" id="device_type_select" required
                                                    class="selectpicker form-control"
                                                    data-live-search="true"
                                                    title="{{ __('db.Please select') }}">
                                                    @foreach ($device_types as $type)
                                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="button" class="btn btn-default btn-sm"
                                                    data-toggle="modal"
                                                    data-target="#addDeviceTypeModal"
                                                    data-category="device">
                                                    <i class="ti ti-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Brand + Plus --}}
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{ __('db.Brand') }}</label>
                                            <div class="input-group pos">
                                                <select name="device_brand" id="device_brand_select"
                                                    class="selectpicker form-control"
                                                    data-live-search="true"
                                                    title="{{ __('db.select_brand') }}">
                                                    @foreach ($lims_brand_list as $brand)
                                                        <option value="{{ $brand->title }}">{{ $brand->title }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="button" class="btn btn-default btn-sm"
                                                    data-toggle="modal" data-target="#addBrandModal">
                                                    <i class="ti ti-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{ __('db.model') }}</label>
                                            <input type="text" name="device_model" class="form-control"/>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{ __('db.serial_number') }}</label>
                                            <input type="text" name="serial_number" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{ __('db.imei') }}</label>
                                            <input type="text" name="imei" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{ __('db.password_unlock_hint') }}</label>
                                            <input type="text" name="password_hint" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{ __('db.accessories_received') }}</label>
                                            <input type="text" name="accessories" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{ __('db.issue_reported_by_customer') }}</label>
                                            <textarea name="device_issue_reported" rows="2" class="form-control no-tiny"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{ __('db.condition_on_arrival') }}</label>
                                            <textarea name="device_condition_notes" rows="2" class="form-control no-tiny"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ════ SECTION 3: Vehicle Details ════ --}}
                            <div id="vehicle-section">
                                <h3 class="mb-3 mt-3 border-bottom pb-2">🚗 {{ __('db.vehicle_details') }}</h3>
                                <div class="row">

                                    {{-- Vehicle Type from DB + Plus --}}
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{ __('db.vehicle_type') }}</label>
                                            <div class="input-group pos">
                                                <select name="vehicle_type_id" id="vehicle_type_select"
                                                    class="selectpicker form-control"
                                                    data-live-search="true"
                                                    title="{{ __('db.Please select') }}">
                                                    @foreach ($vehicle_types as $type)
                                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="button" class="btn btn-default btn-sm"
                                                    data-toggle="modal"
                                                    data-target="#addDeviceTypeModal"
                                                    data-category="vehicle">
                                                    <i class="ti ti-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Vehicle Brand + Plus --}}
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{ __('db.Brand') }}</label>
                                            <div class="input-group pos">
                                                <select name="vehicle_brand" id="vehicle_brand_select"
                                                    class="selectpicker form-control"
                                                    data-live-search="true"
                                                    title="{{ __('db.select_brand') }}">
                                                    @foreach ($lims_brand_list as $brand)
                                                        <option value="{{ $brand->title }}">{{ $brand->title }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="button" class="btn btn-default btn-sm"
                                                    data-toggle="modal" data-target="#addBrandModal">
                                                    <i class="ti ti-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{ __('db.model') }}</label>
                                            <input type="text" name="vehicle_model" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{ __('db.year') }}</label>
                                            <input type="number" name="vehicle_year" class="form-control" min="1990" max="{{ date('Y') }}" />
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{ __('db.fuel_level') }}</label>
                                            <select name="fuel_level" class="form-control">
                                                <option value="">{{ __('db.Please select') }}</option>
                                                <option value="empty">Empty</option>
                                                <option value="quarter">1/4</option>
                                                <option value="half">1/2</option>
                                                <option value="three_quarter">3/4</option>
                                                <option value="full">{{ __('db.Full') }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{ __('db.registration_no') }}</label>
                                            <input type="text" name="registration_no" class="form-control"/>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{ __('db.engine_no') }}</label>
                                            <input type="text" name="engine_no" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{ __('db.chassis_no') }}</label>
                                            <input type="text" name="chassis_no" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{ __('db.mileage') }} (km)</label>
                                            <input type="number" name="mileage" class="form-control" />
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{ __('db.issue_reported_by_customer') }}</label>
                                            <textarea name="vehicle_issue_reported" rows="2" class="form-control no-tiny"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{ __('db.condition_on_arrival') }}</label>
                                            <textarea name="vehicle_condition_notes" rows="2" class="form-control no-tiny"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ════ Note & Submit ════ --}}
                            <div class="col-md-12 px-0 mt-3">
                                <div class="form-group">
                                    <label>{{ __('db.note_remarks') }}</label>
                                    <textarea name="note" rows="3" class="form-control no-tiny"></textarea>
                                </div>
                                {{-- Hidden input - কোন button press হলো track করতে --}}
<input type="hidden" name="submit_action" id="submit_action" value="save">

<div class="form-group mt-3 d-flex gap-2">

    {{-- Button 1: Save and Go to Parts & Billing --}}
    <button type="submit" class="btn btn-primary"
        onclick="document.getElementById('submit_action').value='parts'">
        <i class="ti ti-checkmark"></i> {{ __('Save & Go to Parts and Billing') }}
    </button>

    {{-- Button 2: Just Save --}}
    <button type="submit" class="btn btn-success ml-2"
        onclick="document.getElementById('submit_action').value='save'">
        <i class="ti ti-archive"></i> {{ __('Save') }}
    </button>

    {{-- Button 3: Cancel --}}
    <a href="{{ route('repair.service.index') }}" class="btn btn-secondary ml-2">
        <i class="ti ti-x"></i> {{ __('db.Cancel') }}
    </a>

</div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


{{-- ══════════════════════════════════════════
     MODAL 1: Add Customer
══════════════════════════════════════════ --}}
<div id="addCustomerModal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('db.Add Customer') }}</h5>
                <button type="button" data-dismiss="modal" class="close">
                    <span aria-hidden="true"><i class="ti ti-x"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <form id="customer-modal-form">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('db.Customer Group') }} *</label>
                                <select required class="form-control selectpicker" name="customer_group_id">
                                    @foreach($lims_customer_group_all ?? [] as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('db.name') }} *</label>
                                <input type="text" name="customer_name" required class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('db.email') }}</label>
                                <input type="email" name="email" placeholder="example@example.com" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('db.Phone Number') }} *</label>
                                <input type="text" name="phone_number" required class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('db.Address') }}</label>
                                <input type="text" name="address" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('db.City') }}</label>
                                <input type="text" name="city" class="form-control">
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="pos" value="1">
                    <button type="button" class="btn btn-primary" id="customer-modal-submit-btn">
                        {{ __('db.submit') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>


{{-- ══════════════════════════════════════════
     MODAL 2: Add Brand
══════════════════════════════════════════ --}}
<div id="addBrandModal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('db.Add Brand') }}</h5>
                <button type="button" data-dismiss="modal" class="close">
                    <span aria-hidden="true"><i class="ti ti-x"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <form id="brand-modal-form">
                    @csrf
                    <div class="form-group">
                        <label>{{ __('db.Title') }} *</label>
                        <input type="text" name="title" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>{{ __('db.Image') }}</label>
                        <input type="file" name="image" class="form-control">
                    </div>
                    <input type="hidden" name="ajax" value="1">
                    <button type="button" class="btn btn-primary" id="brand-modal-submit-btn">
                        {{ __('db.submit') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>


{{-- ══════════════════════════════════════════
     MODAL 3: Add Device / Vehicle Type (shared modal)
     The trigger button passes data-category="device"|"vehicle"
══════════════════════════════════════════ --}}
<div id="addDeviceTypeModal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deviceTypeModalTitle">Add Type</h5>
                <button type="button" data-dismiss="modal" class="close">
                    <span aria-hidden="true"><i class="ti ti-x"></i></span>
                </button>
            </div>
            <div class="modal-body">
                {{-- Stores which category triggered the modal --}}
                <input type="hidden" id="device_type_category" value="">

                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" id="new_type_name" class="form-control"
                        placeholder="e.g. Smart Watch / Motorcycle">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" id="new_type_description" class="form-control">
                </div>
                <button type="button" class="btn btn-primary btn-block mt-2"
                    id="device-type-modal-submit-btn">
                    {{ __('db.submit') }}
                </button>
            </div>
        </div>
    </div>
</div>


{{-- ══════════════════════════════════════════
     MODAL 4: Add Technician
══════════════════════════════════════════ --}}
<div id="addTechnicianModal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('db.add_technician') }}</h5>
                <button type="button" data-dismiss="modal" class="close">
                    <span aria-hidden="true"><i class="ti ti-x"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <form id="technician-modal-form">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('db.name') }} *</label>
                                <input type="text" name="name" required class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('db.email') }} *</label>
                                <input type="email" name="email" required class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('db.Phone Number') }}</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('db.Password') }} *</label>
                                <input type="password" name="password" required class="form-control">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" id="technician-modal-submit-btn">
                        {{ __('db.submit') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // ── Sidebar ──
    $("ul#repair").siblings('a').attr('aria-expanded', 'true');
    $("ul#repair").addClass("show");
    $("ul#repair #service-create-menu").addClass("active");

    // ── Date picker ──
    $('.date').datepicker({ format: 'dd-mm-yyyy', autoclose: true, todayHighlight: true });

    // ══════════════════════════════════════════
    // Service Type → show device or vehicle section
    // ══════════════════════════════════════════
    $('#service_type').on('change', function () {
        $('#device-section').hide();
        $('#vehicle-section').hide();

        // Reset required
        $('#device_type_select, #device_brand_select, #vehicle_type_select, #vehicle_brand_select').prop('required', false);

        if ($(this).val() === 'device') {
            $('#device-section').slideDown(200);
            $('#device_type_select, #device_brand_select').prop('required', true);
        }
        if ($(this).val() === 'vehicle') {
            $('#vehicle-section').slideDown(200);
            $('#vehicle_type_select, #vehicle_brand_select').prop('required', true);
        }
    });

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


    // ══════════════════════════════════════════
    // MODAL 1: Customer
    // ══════════════════════════════════════════
    $('#customer-modal-submit-btn').on('click', function () {
        $.ajax({
            type: 'POST',
            url: '{{ route("customer.store") }}',
            data: $('#customer-modal-form').serialize(),
            success: function (res) {
                var label = res.name + (res.phone_number ? ' — ' + res.phone_number : '');
                $('#customer_id')
                    .append(new Option(label, res.id, true, true))
                    .trigger('change');
                $('.selectpicker').selectpicker('refresh');
                $('#addCustomerModal').modal('hide');
                $('#customer-modal-form')[0].reset();
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message ?? 'Error creating customer.');
            }
        });
    });


    // ══════════════════════════════════════════
    // MODAL 2: Brand
    // ══════════════════════════════════════════
    $('#brand-modal-submit-btn').on('click', function () {
        var formData = new FormData($('#brand-modal-form')[0]);
        $.ajax({
            type: 'POST',
            url: '{{ route("brand.store") }}',
            data: formData,
            contentType: false,
            processData: false,
            success: function (res) {
                var opt = '<option value="' + res.title + '" selected>' + res.title + '</option>';
                // Append to both device & vehicle brand selects
                $('#device_brand_select, #vehicle_brand_select').append(opt);
                $('.selectpicker').selectpicker('refresh');
                $('#addBrandModal').modal('hide');
                $('#brand-modal-form')[0].reset();
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message ?? 'Error creating brand.');
            }
        });
    });


    // ══════════════════════════════════════════
    // MODAL 3: Device / Vehicle Type
    // Reads data-category from the trigger button
    // ══════════════════════════════════════════
    $('#addDeviceTypeModal').on('show.bs.modal', function (e) {
        var category = $(e.relatedTarget).data('category'); // "device" or "vehicle"
        $('#device_type_category').val(category);
        $('#deviceTypeModalTitle').text(
            category === 'vehicle' ? 'Add Vehicle Type' : 'Add Device Type'
        );
        $('#new_type_name, #new_type_description').val('');
    });

    $('#device-type-modal-submit-btn').on('click', function () {
        var name        = $('#new_type_name').val().trim();
        var description = $('#new_type_description').val().trim();
        var category    = $('#device_type_category').val(); // "device" | "vehicle"

        if (!name) {
            alert('Name is required.');
            return;
        }

        if (!category) {
            alert('Category is missing. Please close and re-open from the correct button.');
            return;
        }

        $.ajax({
            type: 'POST',
            url: '{{ route("repair.device-types.store") }}',
            data: {
                _token: '{{ csrf_token() }}',
                name: name,
                description: description,
                category: category,
                is_active: 1,
                ajax: 1       // controller checks: if (isset($request->ajax)) return $deviceType;
            },
            success: function (res) {
                // Controller returns the full DeviceType model as JSON
                // res = { id: X, name: "...", category: "device|vehicle", ... }
                if (!res || typeof res.id === 'undefined') {
                    alert('Unexpected response. Please refresh and try again.');
                    return;
                }

                var $opt = $('<option>', { value: res.id, text: res.name });

                if (category === 'device') {
                    $('#device_type_select').append($opt.prop('selected', true));
                    $('#device_type_select').selectpicker('refresh');
                } else {
                    $('#vehicle_type_select').append($opt.prop('selected', true));
                    $('#vehicle_type_select').selectpicker('refresh');
                }

                $('#addDeviceTypeModal').modal('hide');
                $('#new_type_name, #new_type_description').val('');
            },
            error: function (xhr) {
                var errors = xhr.responseJSON?.errors;
                if (errors) {
                    // Show first validation error
                    var firstError = Object.values(errors)[0];
                    alert(Array.isArray(firstError) ? firstError[0] : firstError);
                } else {
                    alert(xhr.responseJSON?.message ?? 'Error saving type.');
                }
            }
        });
    });


    // ══════════════════════════════════════════
    // MODAL 4: Technician
    // ══════════════════════════════════════════
    $('#technician-modal-submit-btn').on('click', function () {
        $.ajax({
            type: 'POST',
            url: '{{ route("user.store") }}',   // adjust route if needed
            data: $('#technician-modal-form').serialize(),
            success: function (res) {
                $('#assigned_to')
                    .append(new Option(res.name, res.id, true, true))
                    .trigger('change');
                $('.selectpicker').selectpicker('refresh');
                $('#addTechnicianModal').modal('hide');
                $('#technician-modal-form')[0].reset();
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message ?? 'Error creating technician.');
            }
        });
    });
</script>
@endpush
