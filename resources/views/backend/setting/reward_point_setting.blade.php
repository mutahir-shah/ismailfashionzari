@extends('backend.layout.main')
@section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">

                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{ __("db.Reward Point Setting") }}</h4>
                    </div>

                    <div class="card-body">

                        <p class="italic">
                            <small>{{ __("db.The field labels marked with are required input fields") }}.</small>
                        </p>

                        <form action="{{ route('setting.rewardPointStore') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                        <div class="row">

                            {{-- ACTIVE --}}
                            <div class="col-md-3 mt-3">
                                <div class="form-group">
                                    <input type="checkbox" name="is_active" value="1"
                                        {{ ($lims_reward_point_setting_data && $lims_reward_point_setting_data->is_active) ? 'checked' : '' }}>
                                    &nbsp;
                                    <label>{{ __("db.Active reward point") }}
                                        <x-info title="Check this box to activate reward points feature." />
                                    </label>
                                </div>
                            </div>

                            {{-- PER POINT AMOUNT --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>
                                        {{ __("db.Sold amount per point") }} *
                                        <x-info title="This means how much point customer will get according to sold amount. For example, if you put 100 then for every 100 spent customer will get one point as reward." />
                                    </label>
                                    <input type="number" name="per_point_amount" class="form-control"
                                        value="{{ $lims_reward_point_setting_data->per_point_amount ?? '' }}" required>
                                </div>
                            </div>

                            {{-- MINIMUM AMOUNT --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>
                                        {{ __("db.Minimum sold amount to get point") }} *
                                        <x-info title="Customer will only get points if order total reaches this amount." />
                                    </label>
                                    <input type="number" name="minimum_amount" class="form-control"
                                        value="{{ $lims_reward_point_setting_data->minimum_amount ?? '' }}" required>
                                </div>
                            </div>

                            {{-- EXPIRY DURATION --}}
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>
                                        {{ __("db.Point Expiry Duration") }}
                                        <x-info title="Duration after which the earned reward points will expire." />
                                    </label>
                                    <input type="number" name="duration" class="form-control"
                                        value="{{ $lims_reward_point_setting_data->duration ?? '' }}">
                                </div>
                            </div>

                            {{-- EXPIRY TYPE --}}
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>
                                        {{ __("db.Duration Type") }}
                                        <x-info title="Select whether the expiry duration is in days, months, or years." />
                                    </label>
                                    <select name="type" class="form-control">
                                        <option value="days" {{ @$lims_reward_point_setting_data->type == 'days' ? 'selected' : '' }}>Days</option>
                                        <option value="months" {{ @$lims_reward_point_setting_data->type == 'months' ? 'selected' : '' }}>Months</option>
                                        <option value="years" {{ @$lims_reward_point_setting_data->type == 'years' ? 'selected' : '' }}>Years</option>
                                    </select>
                                </div>
                            </div>

                        </div> {{-- row end --}}

                        <hr>

                        {{-- ========================================= --}}
                        {{--          REDEEM POINTS SETTINGS            --}}
                        {{-- ========================================= --}}

                        <div class="row well mt-4">

                            <div class="col-sm-12">
                                <h4>{{ __("db.Redeem Points Settings") }}:</h4>
                            </div>

                            {{-- Redeem amount per unit point --}}
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>
                                        {{ __("db.Redeem amount per unit point") }}
                                        <x-info title="How much monetary value each reward point can be redeemed for." />
                                    </label>
                                    <input class="form-control input_number" placeholder="{{ __('db.Redeem amount per unit point') }}"
                                        name="redeem_amount_per_unit_rp" type="text"
                                        value="{{ $lims_reward_point_setting_data->redeem_amount_per_unit_rp ?? '' }}">
                                </div>
                            </div>

                            {{-- Minimum order total --}}
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>
                                        {{ __("db.Minimum order total to redeem points") }}
                                        <x-info title="Customer can only redeem points if order total reaches this minimum amount." />
                                    </label>
                                    <input class="form-control input_number" placeholder="{{ __('db.Minimum order total to redeem points') }}"
                                        name="min_order_total_for_redeem" type="text"
                                        value="{{ $lims_reward_point_setting_data->min_order_total_for_redeem ?? '' }}">
                                </div>
                            </div>

                            {{-- Minimum redeem point --}}
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>
                                        {{ __("db.Minimum redeem point") }}
                                        <x-info title="Minimum number of points that must be available to redeem." />
                                    </label>
                                    <input class="form-control" placeholder="{{ __('db.Minimum redeem point') }}"
                                        name="min_redeem_point" type="number"
                                        value="{{ $lims_reward_point_setting_data->min_redeem_point ?? '' }}">
                                </div>
                            </div>

                            {{-- Maximum redeem point --}}
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>
                                        {{ __("db.Maximum redeem point per order") }}
                                        <x-info title="If a maximum redeem limit is set, you cannot redeem more than the allowed maximum points. If the limit is set to 0, then unlimited points can be redeemed." />
                                    </label>

                                    <input class="form-control" placeholder="{{ __('db.Maximum redeem point per order') }}"
                                        name="max_redeem_point" type="number"
                                        value="{{ $lims_reward_point_setting_data->max_redeem_point ?? '' }}">
                                </div>
                            </div>

                        </div> {{-- redeem row end --}}

                        <div class="col-md-12 form-group mt-4">
                            <button type="submit" class="btn btn-primary">{{ __("db.submit") }}</button>
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
    $("ul#setting").siblings('a').attr('aria-expanded','true');
    $("ul#setting").addClass("show");
    $("ul#setting #reward-point-setting-menu").addClass("active");
    $('[data-toggle="tooltip"]').tooltip();
</script>
@endpush
