@extends('backend.layout.main') @section('content')

@push('css')
<style>
    .change-theme-color {
        align-items: center;
        cursor: pointer;
        display: flex;
        line-height:2
    }
    .change-theme-color span { 
        border-radius: 3px;
        height:15px;
        margin-right: 10px;
        width:15px;
    }
</style>
@endpush

@if(session()->has('message'))
<div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
@endif
@if(session()->has('not_permitted'))
<div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.restaurant_settings')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{__('db.The field labels marked with * are required input fields')}}.</small></p>
                        <form action="{{ route('setting.restaurant.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                        <div class="row">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    @if(isset($settings->logo))
                                    <div>
                                        <img style="max-width: 100px;height:auto;margin-right:25px" src="{{ url('frontend/images') }}/{{$settings->logo}}" />
                                    </div>
                                    @endif
                                    <div class="form-group">
                                        <label>{{__('db.Site Logo')}} *</label>
                                        <input type="file" name="logo" class="form-control" value="" />
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    @if(isset($settings->logo))
                                    <div>
                                        <img style="max-width: 50px;height:auto;margin-right:25px" src="{{ url('frontend/images') }}/{{$settings->favicon}}" />
                                    </div>
                                    @endif
                                    <div class="form-group">
                                        <label>{{__('db.Favicon')}} *</label>
                                        <input type="file" name="favicon" class="form-control" value="" />
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Site Title')}} *</label>
                                    <input type="text" name="site_title" class="form-control" value="@if($settings){{$settings->site_title}}@endif" required />
                                </div>
                            </div>
                            <!-- <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Theme')}}</label>
                                    <select name="theme" class="form-control">
                                        <option @if(isset($settings->theme) && $settings->theme == 'default') selected @endif value="default">{{__('db.Default')}}</option>
                                        <option @if(isset($settings->theme) && $settings->theme == 'fashion') selected @endif value="fashion">{{__('db.Fashion')}}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Font')}}</label>
                                    <select name="theme_font" class="form-control">
                                        <option @if(isset($settings->theme_font) && $settings->theme_font == 'Inter') selected @endif value="Inter">Inter</option>
                                        <option @if(isset($settings->theme_font) && $settings->theme_font == 'Josefin Sans') selected @endif value="Josefin Sans">Josefin Sans</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{__('db.Theme Color')}} *</label>
                                    <div class="dropdown">
                                        <button class="btn btn-default btn-block dropdown-toggle" type="button" data-toggle="dropdown"><span id="def_color"><span style="background-color:@if($settings){{$settings->theme_color}}@endif;width:15px;height:15px"></span> @if($settings){{$settings->theme_color}}@endif</span>
                                            <span class="caret"></span></button>
                                        <ul class="dropdown-menu">
                                            <li class="change-theme-color" data-color="#0071df"> <span style="background-color:#0071df;"></span> #0071df</li>
                                            <li class="change-theme-color" data-color="#f51e46"> <span style="background-color:#f51e46;"></span> #f51e46</li>
                                            <li class="change-theme-color" data-color="#fa9928"> <span style="background-color:#fa9928;"></span> #fa9928</li>
                                            <li class="change-theme-color" data-color="#fd6602"> <span style="background-color:#fd6602;"></span> #fd6602</li>
                                            <li class="change-theme-color" data-color="#59b210"> <span style="background-color:#59b210;"></span> #59b210</li>
                                            <li class="change-theme-color" data-color="#ff749f"> <span style="background-color:#ff749f;"></span> #ff749f</li>
                                            <li class="change-theme-color" data-color="#f8008c"> <span style="background-color:#f8008c;"></span> #f8008c</li>
                                            <li class="change-theme-color" data-color="#6453f7"> <span style="background-color:#6453f7;"></span> #6453f7</li>
                                        </ul>
                                    </div>
                                    <input type="hidden" name="theme_color" class="form-control" value="@if($settings){{$settings->theme_color}}@endif" required />
                                </div>
                            </div> -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{__('db.rtl_layout')}}</label>
                                    <select name="is_rtl" class="form-control">
                                        <option @if(isset($settings->is_rtl) && $settings->is_rtl == 0) selected @endif value="0">{{__('db.LTR')}}</option>
                                        <option @if(isset($settings->is_rtl) && $settings->is_rtl == 1) selected @endif value="1">{{__('db.RTL')}}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{__('db.Home Page')}}</label>
                                    <select name="home_page" class="form-control">
                                        <option></option>
                                        @if($pages)
                                        @foreach($pages as $page)
                                        <option @if(isset($settings->home_page) && $settings->home_page == $page->id) selected @endif value="{{$page->id}}">{{$page->page_name}}</option>
                                        @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{__('db.Store Phone')}}</label>
                                    <input type="text" name="store_phone" class="form-control" value="@if($settings){{$settings->store_phone ?? ''}}@endif" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{__('db.Store Email')}}</label>
                                    <input type="text" name="store_email" class="form-control" value="@if($settings){{$settings->store_email ?? ''}}@endif" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{__('db.Store Address')}}</label>
                                    <input type="text" name="store_address" class="form-control" value="@if($settings){{$settings->store_address ?? ''}}@endif" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{__('db.Contact From Email')}}</label>
                                    <input type="text" name="contact_form_email" class="form-control" value="@if($settings){{$settings->contact_form_email ?? ''}}@endif" />
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{__('db.Min amount for free shipping')}}</label>
                                    <input type="text" name="free_shipping_from" class="form-control" value="@if($settings){{$settings->free_shipping_from ?? ''}}@endif" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{__('db.Flat Rate Shipping')}}</label>
                                    <input type="text" name="flat_rate_shipping" class="form-control" value="@if($settings){{$settings->flat_rate_shipping ?? ''}}@endif" />
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{__('db.Default Warehouse')}}</label>
                                    <select required name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select warehouse...">
                                        @foreach($warehouse_list as $warehouse)
                                        <option @if(isset($settings) && ($settings->warehouse_id == $warehouse->id)) selected @endif value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{__('db.Default Biller')}}</label>
                                    <select required name="biller_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Biller...">
                                        @foreach($biller_list as $biller)
                                        <option @if(isset($settings) && ($settings->biller_id == $biller->id)) selected @endif value="{{$biller->id}}">{{$biller->name . ' (' . $biller->company_name . ')'}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>{{__('db.Pages to read & accept before checkout')}}</label>
                                    <select name="checkout_pages[]" class="selectpicker form-control" multiple>
                                        @foreach($pages as $page)
                                        <option value="{{$page->id}}" @if(isset($settings) && isset($settings->checkout_pages) && in_array($page->id,json_decode($settings->checkout_pages))) selected @endif>{{$page->page_name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>{{__('db.Custom CSS')}}</label>
                                    <textarea name="custom_css" class="form-control">@if($settings){{$settings->custom_css}}@endif</textarea>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>{{__('db.Custom JS')}}</label>
                                    <textarea name="custom_js" class="form-control">@if($settings){{$settings->custom_js}}@endif</textarea>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>{{__('db.Chat Code')}}</label>
                                    <textarea name="chat_code" class="form-control">@if($settings){{$settings->chat_code}}@endif</textarea>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>{{__('db.Google Analytics Code')}}</label>
                                    <textarea name="analytics_code" class="form-control">@if($settings){{$settings->analytics_code}}@endif</textarea>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>{{__('db.Facebook Pixel')}}</label>
                                    <textarea name="fb_pixel_code" class="form-control">@if($settings){{$settings->fb_pixel_code}}@endif</textarea>
                                </div>
                            </div>

                        </div>
                        <div class="form-group">
                            <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
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
    $(document).on('click', '.change-theme-color', function(){
        var color = $(this).data('color');
        var def_color = '<span style="background-color:'+color+';width:15px;height:15px"></span> '+color;
        $('input[name=theme_color]').val(color);
        $('#def_color').html(def_color)
    })
</script>
@endpush