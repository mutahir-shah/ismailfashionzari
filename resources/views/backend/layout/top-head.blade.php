<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{gen_setting()->site_title}}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="all,follow">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
    @endphp

    <link rel="icon" type="image/png" href="{{url('logo', gen_setting()->favicon ?? gen_setting()->site_logo)}}" />

    <!-- Bootstrap CSS-->
    <link rel="stylesheet" href="{{ asset($asset_prefix . 'vendor/bootstrap/css/bootstrap.min.css') }}" type="text/css">

    <link rel="preload" href="{{ asset($asset_prefix . 'vendor/bootstrap/css/bootstrap-datepicker.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="{{ asset($asset_prefix . 'vendor/bootstrap/css/bootstrap-datepicker.min.css') }}" rel="stylesheet"></noscript>

    <link rel="preload" href="{{ asset($asset_prefix . 'vendor/bootstrap/css/bootstrap-select.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="{{ asset($asset_prefix . 'vendor/bootstrap/css/bootstrap-select.min.css') }}" rel="stylesheet"></noscript>

    <!-- virtual keybord stylesheet-->
    <link rel="preload" href="{{ asset($asset_prefix . 'vendor/keyboard/css/keyboard.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="{{ asset($asset_prefix . 'vendor/keyboard/css/keyboard.css') }}" rel="stylesheet"></noscript>

    @if( Config::get('app.locale') == 'ar' || gen_setting()->is_rtl)
      <!-- RTL css -->
      <link rel="stylesheet" href="{{ asset($asset_prefix . 'vendor/bootstrap/css/bootstrap-rtl.min.css') }}" type="text/css">
      <link rel="stylesheet" href="{{ asset($asset_prefix . 'css/custom-rtl.css') }}" type="text/css" id="custom-style">
    @endif

    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdn.datatables.net" crossorigin>
    <!-- Google fonts -->
    @if(gen_setting()->font_css)
      {!! gen_setting()->font_css !!}
    @else
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,100..900&display=swap" rel="stylesheet">
    @endif

    @stack('css')

    <!-- Custom CSS from general settings -->
    {!! gen_setting()->pos_css !!}
  </head>
  <body class="pos-page">
      <div id="content">
          @yield('content')
      </div>

    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/jquery/jquery.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/jquery/jquery-ui.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/jquery/bootstrap-datepicker.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/popper.js/umd/popper.min.js') }}">
    </script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/bootstrap/js/bootstrap.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/bootstrap/js/bootstrap-select.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/keyboard/js/jquery.keyboard.js') }}"></script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/keyboard/js/jquery.keyboard.extension-autocomplete.js') }}"></script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/jquery.cookie/jquery.cookie.js') }}">
    </script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/jquery-validation/jquery.validate.min.js') }}"></script>
    @if( Config::get('app.locale') == 'ar' || gen_setting()->is_rtl)
      <script type="text/javascript" src="{{ asset($asset_prefix . 'js/front_rtl.js') }}"></script>
    @else
      <script type="text/javascript" src="{{ asset($asset_prefix . 'js/front.js') }}"></script>
    @endif

    @stack('scripts')
        <script>
        window.SaleproConfig = {
            theme: '{{ $theme }}',
            alertProduct: typeof alert_product !== 'undefined' ? alert_product : 0,
            switchThemeDarkUrl: '{{ route("switchTheme", "dark") }}',
            switchThemeLightUrl: '{{ route("switchTheme", "light") }}',
            currency: "{{ config('currency') }}",
            currency_position: "{{ config('currency_position') }}",
            decimal: {{ config('decimal') }},
            isRtl: {{ (Config::get('app.locale') == 'ar' || gen_setting()->is_rtl) ? 'true' : 'false' }}
        };
    </script>
    @include('backend.layout.theme_customizer')
  </body>
</html>
