@extends('backend.layout.main') @section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.Mail Setting')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                        <form action="{{ route('setting.mailStore') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Mail Driver')}} *</label>
                                <input type="text" name="driver" class="form-control" value="@if($mail_setting_data){{$mail_setting_data->driver}}@endif" required />
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Mail Host')}} *</label>
                                <input type="text" name="host" class="form-control" value="@if($mail_setting_data){{$mail_setting_data->host}}@endif" required />
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Mail Port')}} *</label>
                                <input type="text" name="port" class="form-control" value="@if($mail_setting_data){{$mail_setting_data->port}}@endif" required />
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Mail Address')}} *</label>
                                <input type="text" name="from_address" class="form-control" value="@if($mail_setting_data){{$mail_setting_data->from_address}}@endif" required />
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Mail From Name')}} *</label>
                                <input type="text" name="from_name" class="form-control" value="@if($mail_setting_data){{$mail_setting_data->from_name}}@endif" required />
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.username')}} *</label>
                                <input type="text" name="username" class="form-control" value="@if($mail_setting_data){{$mail_setting_data->username}}@endif" required />
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Password')}} *</label>
                                <input type="password" name="password" class="form-control" value="@if($mail_setting_data){{$mail_setting_data->password}}@endif" required />
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Encryption')}} *</label>
                                <input type="text" name="encryption" class="form-control" value="@if($mail_setting_data){{$mail_setting_data->encryption}}@endif" required />
                            </div>
                            <div class="col-md-12 form-group">
                                <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
                            </div>
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
    $("ul#setting").siblings('a').attr('aria-expanded','true');
    $("ul#setting").addClass("show");
    $("ul#setting #mail-setting-menu").addClass("active");
</script>
@endpush
