@php
    $layout = config('database.connections.saleprosaas_landlord') ? 'landlord.layout.main' : 'backend.layout.main';
@endphp

@extends($layout)

@section('title','Admin | New Release Version')
@section('content')

    @if (session()->has('message'))
    <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close"
            data-dismiss="alert" aria-label="Close"><span
                aria-hidden="true">&times;</span></button>{!! session()->get('message') !!}</div>
    @endif
    @if (session()->has('not_permitted'))
    <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close"
            data-dismiss="alert" aria-label="Close"><span
                aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
    @endif

    @if (isset($versionUpgradeData['alert_version_upgrade_enable']) &&
            $versionUpgradeData['alert_version_upgrade_enable'] == true)
        @if (!empty($versionUpgradeData['advertise_info']))
        <section id="adSection" class="container text-center">
            <div class="card">
                <div class="card-body">
                    {!! $versionUpgradeData['advertise_info'] !!}
                </div>
            </div>
        </section>
        @endif
        <!-- For New Version -->
        <section id="newVersionSection" class="container text-center">
            <div class="card">
                <div class="card-body">
                    <h4 class="text-center text-success"> A new version <b>{{ $versionUpgradeData['demo_version'] }}</b> has
                        been released.</h4>
                    <p>Before upgrading, we highly recomended you to keep a backup of your current script and database.</p>
                    <p><a target="_blank" href="{{ config('database.connections.saleprosaas_landlord') ? 'https://lion-coders.com/software/salepro-saas-pos-inventory-saas-php-script' : 'https://lion-coders.com/software/salepro-inventory-management-system-with-pos-hrm-accounting' }}">Change Log</a></p>
                </div>
            </div>

            <div class="d-flex justify-content-center mt-3 mb-3">
                <div id="spinner" class="d-none spinner-border text-success" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
            <form action="{{ config('database.connections.saleprosaas_landlord') ? route('saas-version-upgrade') : route('version-upgrade') }}" method="post">
                @csrf
                <label>Purchase Code</label>
		        <input type='text' placeholder="Ex: 123456789XXXXXXXX" required class="form-control" name="purchasecode">
                <button type="submit" class="mt-5 mb-5 btn btn-primary btn-lg">Upgrade</button>
            </form>
        </section>
    @else
        <!-- Cuurent Version -->
        <section id="oldVersionSection" class="container mt-5 text-center">
            <div class="card">
                <div class="card-body">
                    <h4 class="text-center text-info">Your current version is <span>{{ env('VERSION') }}</span></h4>
                    <p>Please wait for upcoming version</p>
                </div>
            </div>
        </section>
    @endif
@endsection

@push('scripts')
    <script>
        $("#upgrade-form").on("submit", function() {
            $(".upgrade-btn").prop("disabled", true);
        });
    </script>
@endpush
