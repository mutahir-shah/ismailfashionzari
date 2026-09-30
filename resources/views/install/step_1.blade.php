{{--
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <title>SalePro Installer | Step-1</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset($asset_prefix . 'install-assets/images/favicon.ico') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="{{ asset($asset_prefix . 'install-assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset($asset_prefix . 'install-assets/css/style.css') }}" rel="stylesheet">
</head>
<body>
	<div class="col-md-6 offset-md-3">
		<div class="wrapper">
	        <header>
	            <img src="{{ asset($asset_prefix . 'install-assets/images/logo.png') }}" alt="Logo" style="max-width: 120px;"/>
	            <h1 class="text-center">SalePro Auto Installer</h1>
	        </header>
            <hr>
            <div class="content text-center">
                <a href="{{ route('install-step-2') }}" class="btn btn-primary">Let's Start</a>
                <hr class="mt-lg-5">
                <h6>If you need any help with installation, <br>
                    Please contact through support <a target="_blank" href="https://lion-coders.com/support">Contact Support</a></h6>
            </div>
            <hr>
            <footer>Copyright &copy; LionCoders. All Rights Reserved.</footer>
		</div>
	</div>
</body>
</html>
--}}
@extends('install.layout', ['currentStep' => 1])
@section('title', 'Welcome | SalePro Installer')
@section('content')
<div class="installer-heading"><span class="hero-icon"><svg viewBox="0 0 24 24"><path d="M4 13a8 8 0 017 7 6 6 0 003-5 9 9 0 006-8 3 3 0 00-3-3 9 9 0 00-8 6 6 6 0 00-5 3"/><path d="M7 14a6 6 0 00-3 6 6 6 0 006-3"/><circle cx="15" cy="9" r="1"/></svg></span><div class="installer-kicker">Setup wizard</div><h1 id="installer-title">Let’s get SalePro ready</h1><p>This guided installer will check your server, connect your database, and finish setup in a few minutes.</p></div>
<div class="welcome-points"><div>Server check</div><div>Database setup</div><div>Ready to use</div></div>
<div class="action-center"><a href="{{ route('install-step-2') }}" class="btn-installer">Begin installation <svg viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></a></div>
@endsection
