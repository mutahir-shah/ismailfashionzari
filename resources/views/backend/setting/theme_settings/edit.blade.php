@php
if (config('database.connections.saleprosaas_landlord') && !tenant()) {
    $layout = 'landlord.layout.main';
    $routePrefix = 'superadminSetting.';
} else {
    $layout = 'backend.layout.main';
    $routePrefix = 'setting.';
}
@endphp


@extends($layout)
@section('content')

<x-success-message key="message" />
<x-error-message key="name" />
<x-error-message key="not_permitted" />

<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4>Edit Theme Setting</h4>
                        <a href="{{ route($routePrefix . 'themeSettings.index') }}" class="btn btn-default btn-sm">Back</a>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>The field labels marked with are required input fields</small></p>

                        <form action="{{ route($routePrefix . 'themeSettings.update', $themeSetting->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            @include('backend.setting.theme_settings._form', ['theme' => $themeSetting])
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
