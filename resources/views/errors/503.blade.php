@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
<!DOCTYPE html>
<html dir="ltr" lang="en-US">

<head>
    <!-- Metas -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Document Title -->
    <title>{{__("db.maintenance_mode")}}</title>

    @if(!config('database.connections.saleprosaas_landlord'))
    <!-- Plugins CSS -->
    <link href="{{ asset($asset_prefix . 'frontend/css/plugins.css') }}" rel="stylesheet" />
    <!-- style CSS -->
    <link rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'" href="{{ asset($asset_prefix . 'frontend/css/style.css') }}">
    <noscript>
        <link rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'" href="{{ asset($asset_prefix . 'frontend/css/style.css') }}">
    </noscript>
    @else
    <!-- Plugins CSS -->
    <link href="{{ asset($asset_prefix . 'frontend/css/plugins.css') }}" rel="stylesheet" />
    <!-- style CSS -->
    <link rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'" href="{{ asset($asset_prefix . 'frontend/css/style.css') }}">
    <noscript>
        <link rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'" href="{{ asset($asset_prefix . 'frontend/css/style.css') }}">
    </noscript>
    @endif
    <!-- google fonts-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0" rel="stylesheet" />

</head>
<body>
    <section class="error-section mt-5 mb-5">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <div class="error-icon">
                        <span style="font-size: 60px;" class="material-symbols-outlined">waving_hand</span>
                    </div>
                </div>
                <div class="col-md-6 offset-md-3  error-text text-center">
                    <i class="las la-binoculars"style="color:var(--theme-color);font-size: 90px;margin-bottom:30px"></i>
                    <h2 class="h1">{{__("db.maintenance_short_message")}}</h2>
                    <p class="lead">{{__("db.maintenance_long_message")}}</p>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
