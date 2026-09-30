@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                @if (!in_array('api', explode(',', $lims_general_setting_data->modules)))
                    @if(config('database.connections.saleprosaas_landlord') && !tenant())
                    <div class="alert alert-danger mb-2">
                        {{__('db.mobile_app_saas_addon_prompt')}} <a href="https://lion-coders.com/software/saleprosaas-mobile-app-for-tenant-with-source-code" target="_blank">{{__('db.click_here_buy')}}</a>.
                    </div>
                    @else
                    <div class="alert alert-danger mb-2">
                        {{__('db.mobile_app_addon_prompt')}} <a href="https://lion-coders.com/software/salepro-mobile-app-with-source-code" target="_blank">{{__('db.click_here_buy')}}</a>.
                    </div>
                    @endif
                @endif
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.App Setting')}}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mt-2">
                                <h5 class="mb-4">{{__('db.Manual Process for connecting the Mobile App')}}</h5>
                                <div class="form-group mb-3">
                                    <label for="server_url">Server URL <x-info title="It is your server url for connecting with the app" type="info" /></label>
                                    <div class="input-group">
                                        <input type="text" id="server_url" name="server_url" class="form-control" value="{{ $installUrl }}" readonly>
                                    </div>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="app_key">App Key <x-info title="It is your mobile app key for connecting with the app" type="info" /></label>
                                    <div class="input-group">
                                        <input type="text" id="app_key" name="app_key" class="form-control" value="{{ $lims_general_setting_data->app_key }}" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5 class="mb-4">{{__('db.QR Code for Connecting the Mobile App')}}</h5>
                                <div id="qrcode"></div>
                            </div>
                        </div>  
                        <div class="table-responsive">
                            <h4 class="my-4">{{__('db.Active Devices')}}</h4>
                            <table id="connected-device" class="table transfer-list" style="width: 100%">
                                <thead>
                                    <tr>
                                        <th class="not-exported"></th>
                                        <th>{{__('db.name')}}</th>
                                        <th>{{__('db.IP')}}</th>
                                        <th>{{__('db.Last Active')}}</th>
                                        <th class="not-exported">{{__('db.action')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($mobile_tokens as $token)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $token->name ?? 'Device ' . $loop->iteration }}</td>
                                        <td>{{ $token->ip ?? '-' }}</td>
                                        <td>
                                            @if($token->last_active)
                                                {{ \Carbon\Carbon::parse($token->last_active)->format('d M Y, h:i A') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if(config('app.user_verified'))
                                                <form action="{{ route('setting.tokenDelete', $token->id) }}" method="POST" onsubmit="return confirm('Are you sure?')" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">{{ __('db.Delete') }}</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script type="text/javascript">
    @if(!empty($lims_general_setting_data->app_key))
    var installUrl = "{{ $installUrl }}?app_key={{ $lims_general_setting_data->app_key }}";
    var qrcode = new QRCode(document.getElementById("qrcode"), {
        text: installUrl,
        width: 256,
        height: 256,
    });
    @endif

    var connectedDeviceTable = $('#connected-device').DataTable({
        "processing": true,
        "serverSide": false,
        'language': {
            'lengthMenu': '_MENU_ {{__("db.records per page")}}',
            "info": '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search": '{{__("db.Search")}}',
            'paginate': {
                'previous': '<i class="ti ti-chevron-left"></i>',
                'next': '<i class="ti ti-chevron-right"></i>'
            }
        },
        order: [[1, 'asc']],
        'columnDefs': [
            {
                "orderable": false,
                'targets': [0, 4]
            }
        ],
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lf>rtip'
    });
</script>

@endpush
