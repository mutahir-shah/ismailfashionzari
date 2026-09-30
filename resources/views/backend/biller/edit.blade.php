@extends('backend.layout.main') @section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.Update Biller')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                        <form action="{{ route('biller.update', $lims_biller_data->id) }}" method="post" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__('db.name')}} *</strong> </label>
                                        <input type="text" name="name" value="{{$lims_biller_data->name}}" required class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__('db.Image')}}</label>
                                        <input type="file" name="image" class="form-control">
                                        <x-validation-error fieldName="image" />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__('db.Company Name')}} *</label>
                                        <input type="text" name="company_name" value="{{$lims_biller_data->company_name}}" required class="form-control">
                                        <x-validation-error fieldName="company_name" />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__('db.VAT Number')}}</label>
                                        <input type="text" name="vat_number" value="{{$lims_biller_data->vat_number}}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__('db.email')}} *</label>
                                        <input type="email" name="email" value="{{$lims_biller_data->email}}" required class="form-control">
                                        <x-validation-error fieldName="email" />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__('db.Phone Number')}} *</label>
                                        <input type="text" name="phone_number" value="{{$lims_biller_data->phone_number}}" required class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__('db.Address')}} *</label>
                                        <input type="text" name="address" value="{{$lims_biller_data->address}}" required class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__('db.City')}} *</label>
                                        <input type="text" name="city"  value="{{$lims_biller_data->city}}" required class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__('db.State')}}</label>
                                        <input type="text" name="state" value="{{$lims_biller_data->state}}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__('db.Postal Code')}}</label>
                                        <input type="text" name="postal_code" value="{{$lims_biller_data->postal_code}}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__('db.Country')}}</label>
                                        <input type="text" name="country" value="{{$lims_biller_data->country}}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group mt-3">
                                        <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
                                    </div>
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
    $("ul#people").siblings('a').attr('aria-expanded','true');
    $("ul#people").addClass("show");
</script>
@endpush
