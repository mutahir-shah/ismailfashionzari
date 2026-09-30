@extends('backend.layout.main')
@section('content')
@if(session()->has('message'))
  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
@endif
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif
<style>
    .sc-settings-head h4 { margin: 0; font-size: 1.1rem; font-weight: 600; }
    .sc-settings-section { border: 1px solid #eef0f4; border-radius: 6px; padding: 18px; margin-bottom: 18px; background: #fff; }
    .sc-settings-section h5 { font-size: .95rem; font-weight: 600; margin-bottom: 16px; color: #34395e; }
    .sc-switch-line { display: flex; align-items: center; justify-content: space-between; gap: 12px; border: 1px solid #eef0f4; border-radius: 6px; padding: 12px 14px; min-height: 58px; }
    .sc-switch-line label { margin: 0; font-weight: 600; }
    .sc-template-grid textarea { min-height: 112px; resize: vertical; }
    .sc-save-row { display: flex; justify-content: flex-end; }
    @media (max-width: 767px) {
        .sc-switch-line { align-items: flex-start; flex-direction: column; }
        .sc-save-row .btn { width: 100%; }
    }
</style>
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center sc-settings-head">
                        <h4>{{ __('db.Social Commerce Settings') }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('socialcommerce.updateSettings') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="sc-settings-section">
                                <h5>{{ __('db.Module Status') }}</h5>
                                <div class="row">
                                    <div class="col-md-4 form-group">
                                        <div class="sc-switch-line">
                                            <label>{{ __('db.Enable Social Commerce') }} <x-info title="{{ __('db.Activate or deactivate the social commerce module globally.') }}" type="info" /></label>
                                            <input type="checkbox" name="is_active" value="1" {{ $settings->is_active ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <div class="sc-switch-line">
                                            <label>{{ __('db.Fallback Landing Page') }} <x-info title="{{ __('db.Allows customers to check out on a minimal landing page if no other integration is available.') }}" type="info" /></label>
                                            <input type="checkbox" name="allow_fallback" value="1" {{ $settings->allow_fallback ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label>{{ __('db.Preferred Link Target') }} * <x-info title="{{ __('db.Where should the Buy Now links point to in the catalog feed.') }}" type="info" /></label>
                                        <select name="link_target" class="form-control" required>
                                            <option value="fallback" {{ $settings->link_target == 'fallback' ? 'selected' : '' }}>{{ __('db.Fallback Landing Page') }}</option>
                                            <option value="ecommerce" {{ $settings->link_target == 'ecommerce' ? 'selected' : '' }}>{{ __('db.Paid eCommerce Add-on') }}</option>
                                            <option value="qr" {{ $settings->link_target == 'qr' ? 'selected' : '' }}>{{ __('db.QR Menu / WhatsApp Store') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="sc-settings-section">
                                <h5>{{ __('db.Sharing Defaults') }}</h5>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>{{ __('db.Default WhatsApp Number') }} <x-info title="{{ __('db.The business WhatsApp number for receiving orders and inquiries.') }}" type="info" /></label>
                                        <input type="text" name="whatsapp_number" class="form-control" value="{{ $settings->whatsapp_number }}">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>{{ __('db.Default Social Share Text Template') }}</label>
                                        <textarea name="share_template" class="form-control" rows="3">{{ $settings->share_template }}</textarea>
                                        <small class="text-muted">{{ __('db.Placeholders') }}: {product_name}, {product_link}</small>
                                    </div>
                                </div>
                            </div>

                            <div class="sc-settings-section">
                                <h5>{{ __('db.WhatsApp Templates') }}</h5>
                                <div class="row sc-template-grid">
                                    <div class="col-md-4 form-group">
                                        <label>{{ __('db.WhatsApp Order Confirmation Template') }}</label>
                                        <textarea name="wa_template_order_confirmation" class="form-control">{{ $settings->wa_template_order_confirmation }}</textarea>
                                        <small class="text-muted">{{ __('db.Placeholders') }}: {customer_name}, {reference_no}, {amount}, {order_link}</small>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label>{{ __('db.WhatsApp Payment Request Template') }}</label>
                                        <textarea name="wa_template_payment_request" class="form-control">{{ $settings->wa_template_payment_request }}</textarea>
                                        <small class="text-muted">{{ __('db.Placeholders') }}: {customer_name}, {reference_no}, {due_amount}, {payment_link}</small>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label>{{ __('db.WhatsApp Delivery Update Template') }}</label>
                                        <textarea name="wa_template_delivery_update" class="form-control">{{ $settings->wa_template_delivery_update }}</textarea>
                                        <small class="text-muted">{{ __('db.Placeholders') }}: {customer_name}, {reference_no}, {status}</small>
                                    </div>
                                </div>
                            </div>

                            <div class="sc-save-row">
                                <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
