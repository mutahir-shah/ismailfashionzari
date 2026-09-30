@extends('backend.layout.main')
@section('content')
    <x-success-message key="message" />

    <section>
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h4>{{ __('db.whatsapp_settings') }}</h4>
                </div>
                <div class="card-body">
                    {{-- Form Start --}}
                    <form method="POST" action="{{ route('whatsapp.settings.update') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label>{{ __('db.permanent_access_token') }}</label>
                                <input type="text" name="permanent_access_token" class="form-control"
                                    value="{{ old('permanent_access_token', $settings->permanent_access_token ?? '') }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('db.phone_number_id') }}</label>
                                <input type="text" name="phone_number_id" class="form-control"
                                    value="{{ old('phone_number_id', $settings->phone_number_id ?? '') }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('db.business_account_id') }}</label>
                                <input type="text" name="business_account_id" class="form-control"
                                    value="{{ old('business_account_id', $settings->business_account_id ?? '') }}">
                            </div>

                            <div class="col-md-12 mt-3">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('db.submit') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection


@push('scripts')
    <script type="text/javascript">

    </script>
@endpush
