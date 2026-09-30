@php
    $db_str = '';
    $isLandlord = 0;
    if (!config('database.connections.saleprosaas_landlord')) {
        $layout = 'backend.layout.main';
        $db_str = 'db.';
    }
    else {
        $isLandlord = 1;
        $layout = 'landlord.layout.main';
    }
@endphp

@extends($layout)
@section('content')

@push('css')
<style>
.table td {
    background: #FFF;
}
</style>
@endpush

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <div class="card-header mt-2">
            <h3 class="text-center">{{__($db_str.'Addon List')}}</h3>
        </div>
    </div>
    <div class="table-responsive container-fluid mt-5">
        <table id="department-table" class="table">
            <thead>
                <tr>
                    <th>{{__($db_str.'name')}}</th>
                    <th style="width:65%">{{__($db_str.'Description')}}</th>
                    <th style="width:200px" class="not-exported">{{__($db_str.'action')}}</th>
                </tr>
            </thead>
            <tbody>
                @if (!config('database.connections.saleprosaas_landlord'))
                <tr>
                    <td>SaleProSaaS</td>
                    <td>It's a standalone application to start subscription business with SalePro. It is a multi tenant system and each client will have their separate database. This application comes with free landing page, unlimited custom pages, blog, payment gateway and lots more.</td>
                    <td>
                        <div class="btn-group">
                            <a target="_blank" href="https://lion-coders.com/software/salepro-saas-pos-inventory-saas-php-script" class="btn btn-primary btn-sm" title="SalePro Saas"><i class="ti ti-basket"></i> Buy Now</a>&nbsp;&nbsp;
                            <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#installSaasModal">
                                <i class="ti ti-download"></i> Install
                            </button>
                        </div>
                    </td>
                </tr>
                @endif
                <tr>
                    <td>SalePro{{$isLandlord ? 'SaaS' : ''}} eCommerce</td>
                    <td>Start an eCommerce store and manage all aspects of your eCommerce site from within SalePro{{$isLandlord ? 'SaaS' : ''}}. From inventories, customers, deliveries to CMS website, SEO and everything in between!</td>
                    <td>
                        <div class="btn-group">
                        @php
                        $ecommerceInstalled = $isLandlord
                            ? file_exists(base_path('Modules/Ecommerce'))
                            : in_array('ecommerce', explode(',', gen_setting()->modules));

                        $buyNowUrl = $isLandlord
                            ? 'https://lion-coders.com/software/ecommerce-addon-for-salepro-pos-saas'
                            : 'https://lion-coders.com/software/ecommerce-addon-for-salepro-pos-inventory-management-app';
                        @endphp

                        @if (!$ecommerceInstalled)
                            <a target="_blank" href="{{ $buyNowUrl }}" class="btn btn-primary btn-sm" title="SalePro eCommerce">
                                <i class="ti ti-basket"></i> Buy Now
                            </a>&nbsp;&nbsp;
                            <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#installeCommerceModal">
                                <i class="ti ti-download"></i> Install
                            </button>
                        @else
                            <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#installeCommerceModal">
                                <i class="ti ti-download"></i> Update
                            </button>
                        @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>SalePro{{$isLandlord ? 'SaaS' : ''}} Mobile App</td>
                    <td>SalePro{{$isLandlord ? 'SaaS' : ''}} Mobile App - All-in-one mobile POS, inventory, HRM & accounting management app.</td>
                    <td>
                        <div class="btn-group">
                            @php
                            $apiInstalled = $isLandlord
                                ? file_exists(base_path('app/Http/Controllers/Api'))
                                : in_array('api', explode(',', gen_setting()->modules));

                            $buyNowUrl = $isLandlord
                                ? 'https://lion-coders.com/software/saleprosaas-mobile-app-for-tenant-with-source-code'
                                : 'https://lion-coders.com/software/salepro-mobile-app-with-source-code';
                            @endphp

                            @if (!$apiInstalled)
                                <a target="_blank" href="{{ $buyNowUrl }}" class="btn btn-primary btn-sm" title="Mobile App - All-in-one mobile POS">
                                    <i class="ti ti-basket"></i> Buy Now
                                </a>&nbsp;&nbsp;
                                <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#installApiModal">
                                    <i class="ti ti-download"></i> Install
                                </button>
                            @else
                                <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#installApiModal">
                                    <i class="ti ti-download"></i> Update
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>SalePro{{$isLandlord ? 'SaaS' : ''}} WooCommerce</td>
                    <td>An addon to integrate SalePro{{$isLandlord ? 'SaaS' : ''}} with your existing WooCommerce website.</td>
                    <td>
                        <div class="btn-group">
                            @php
                            $woocommerceInstalled = $isLandlord
                                ? file_exists(base_path('Modules/Woocommerce'))
                                : in_array('woocommerce', explode(',', gen_setting()->modules));

                            $buyNowUrl = $isLandlord
                                ? 'https://lion-coders.com/software/woocommerce-addon-for-salepro-saas'
                                : 'https://lion-coders.com/software/salepro-woocommerce-addon';
                            @endphp

                            @if (!$woocommerceInstalled)
                                <a target="_blank" href="{{ $buyNowUrl }}" class="btn btn-primary btn-sm" title="Point of sale WooCommerce add-on">
                                    <i class="ti ti-basket"></i> Buy Now
                                </a>&nbsp;&nbsp;
                                <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#installWooCommerceModal">
                                    <i class="ti ti-download"></i> Install
                                </button>
                            @else
                                <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#installWooCommerceModal">
                                    <i class="ti ti-download"></i> Update
                                </button>
                            @endif

                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<div id="installSaasModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('saas.install') }}" method="POST">
                @csrf
            <div class="modal-header">
                <h5 class="modal-title">Install SaleProSaaS</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <p class="italic"><small>{{__($db_str.'The field labels marked with are required input fields')}}.</small></p>
                <form>
                    <div class="form-group">
                        <label>Purchase Code *</label>
                        <input type="text" name="purchase_code" required class="form-control" placeholder="{{ __($db_str.'Type purchase code') }}">
                    </div>
                    <div class="form-group">
                        <input type="submit" value="{{__($db_str.'submit')}}" class="btn btn-primary">
                    </div>
                </form>
            </div>
            </form>
        </div>
    </div>
</div>

<div id="installeCommerceModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <form action="{{ $isLandlord ? route('saas.ecommerce.install') : route('ecommerce.install') }}" method="POST">
                @csrf
            <div class="modal-header">
                <h5 class="modal-title">Install eCommerce Add-on</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <p class="italic"><small>{{__($db_str.'The field labels marked with are required input fields')}}.</small></p>
                <form>
                    <div class="form-group">
                        <label>Purchase Code *</label>
                        <input type="text" name="purchase_code" required class="form-control" placeholder="{{ __($db_str.'Type purchase code') }}">
                    </div>
                    <div class="form-group">
                        <input type="submit" value="{{__($db_str.'submit')}}" class="btn btn-primary">
                    </div>
                </form>
            </div>
            </form>
        </div>
    </div>
</div>

<div id="installApiModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <form action="{{ $isLandlord ? route('saas.api.install') : route('api.install') }}" method="POST">
                @csrf
            <div class="modal-header">
                <h5 class="modal-title">Install API Add-on</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <p class="italic"><small>{{__($db_str.'The field labels marked with are required input fields')}}.</small></p>
                <form>
                    <div class="form-group">
                        <label>Purchase Code *</label>
                        <input type="text" name="purchase_code" required class="form-control" placeholder="{{ __($db_str.'Type purchase code') }}">
                    </div>
                    <div class="form-group">
                        <input type="submit" value="{{__($db_str.'submit')}}" class="btn btn-primary">
                    </div>
                </form>
            </div>
            </form>
        </div>
    </div>
</div>

<div id="installWooCommerceModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <form action="{{ $isLandlord ? route('saas.woocommerce.install') : route('woocommerce.install') }}" method="POST">
                @csrf
            <div class="modal-header">
                <h5 class="modal-title">Install WooCommerce Add-on</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <p class="italic"><small>{{__($db_str.'The field labels marked with are required input fields')}}.</small></p>
                <form>
                    <div class="form-group">
                        <label>Purchase Code *</label>
                        <input type="text" name="purchase_code" required class="form-control" placeholder="{{ __($db_str.'Type purchase code') }}">
                    </div>
                    <div class="form-group">
                        <input type="submit" value="{{__($db_str.'submit')}}" class="btn btn-primary">
                    </div>
                </form>
            </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="text/javascript">
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endpush
