{{--
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>SalePro Installer | Step-4</title>
        <link rel="shortcut icon" type="image/x-icon" href="{{ asset($asset_prefix . 'install-assets/images/favicon.ico') }}">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="{{ asset($asset_prefix . 'install-assets/css/bootstrap.min.css') }}" rel="stylesheet">
        <link href="{{ asset($asset_prefix . 'install-assets/css/style.css') }}" rel="stylesheet">
    </head>
<body>
    <div class="col-md-6 offset-md-3">
        <div class='wrapper'>
            <header>
                <img src="{{ asset($asset_prefix . 'install-assets/images/logo.png')}}" alt="Logo" style="max-width: 120px;"/>
                <h1 class="text-center">SalePro Auto Installer</h1>
            </header>
            <hr>
            <div class="content pad-top-bot-50">
                <h3 class="text-center"><strong class="theme-color">Congratulations!</strong></h3><br>
                <h5 class="text-center">You have successfully installed SalepPro.</h5><br>
                <hr>
                <br>
                <p>Access admin login page - <strong><a href="{{ url('/login') }}" target="__blank">Click here</a></strong></p>
                <p>
                    Username: <strong>admin</strong>
                    <br>
                    Password: <strong>admin</strong>
                </p>
            </div>
            <hr>
            <footer>Copyright &copy; LionCoders. All Rights Reserved.</footer>
        </div>
    </div>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'install-assets/js/jquery.min.js')}}"></script>
</body>
</html>
--}}
@extends('install.layout', ['currentStep' => 4])
@section('title', 'Installation Complete | SalePro')
@section('content')
<div class="installer-heading"><span class="hero-icon success"><svg viewBox="0 0 24 24"><path d="M5 12l5 5 10-10"/></svg></span><div class="installer-kicker">Installation complete</div><h1 id="installer-title">SalePro is ready</h1><p>Everything was installed successfully. Use the temporary credentials below.</p></div>
<div class="credentials-card"><div><span>Username</span><strong>admin</strong></div><div><span>Password</span><strong>admin</strong></div></div>
<p class="security-note">For security, change this password immediately after your first sign-in.</p>
<div class="action-center"><a href="{{ url('/login') }}" class="btn-installer">Open admin login <svg viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></a></div>
@endsection
