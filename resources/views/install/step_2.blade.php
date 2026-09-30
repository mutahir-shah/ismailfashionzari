{{--
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <title>SalePro Installer | Step-2</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset($asset_prefix . 'install-assets/images/favicon.ico') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="{{ asset($asset_prefix . 'install-assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset($asset_prefix . 'install-assets/css/style.css') }}" rel="stylesheet">
</head>
<body>
    <div class="col-md-6 offset-md-3">
        <div class='wrapper'>
            <header>
	            <img src="{{ asset($asset_prefix . 'install-assets/images/logo.png') }}" alt="Logo" style="max-width: 120px;"/>
                <h1 class="text-center">SalePro Auto Installer</h1>
            </header>
            <hr>
            <div class="content">
                <?php

                $passed = '';
                $ltext = '';
                if (version_compare(PHP_VERSION, '8.3') >= 0) {
                    $ltext .= '<i class="ti ti-check"></i>Your PHP Version is: ' . PHP_VERSION . '<br/>';
                    $passed .= '1';

                } else {
                    $ltext .= '<i class="ti ti-close"></i>SalePro needs at least PHP version  8.3, Your PHP Version is: ' . PHP_VERSION . '<br/>';
                    $passed .= '0';
                }

                if (extension_loaded('PDO')) {
                    $ltext .= '<i class="ti ti-check"></i>PDO is installed on your server.' . '<br/>';
                    $passed .= '1';
                } else {
                    $ltext .= '<i class="ti ti-close"></i>PDO is not installed on your server.' . '<br/>';
                    $passed .= '0';
                }

                if (extension_loaded('pdo_mysql')) {
                    $ltext .= '<i class="ti ti-check"></i>PDO MySQL driver is enabled on your server.' . '<br/>';
                    $passed .= '1';
                } else {
                    $ltext .= '<i class="ti ti-close"></i>PDO MySQL driver is not enabled on your server.' . '<br/>';
                    $passed .= '0';
                }

                if (extension_loaded('curl')) {
                    $ltext .= '<i class="ti ti-check"></i>php-curl extension is enabled on your server.' . '<br/>';
                    $passed .= '1';
                } else {
                    $ltext .= '<i class="ti ti-close"></i>php-curl extension is not enabled on your server.' . '<br/>';
                    $passed .= '0';
                }

                if (extension_loaded('fileinfo')) {
                    $ltext .= '<i class="ti ti-check"></i>fileinfo extension is installed on your server.' . '<br/>';
                    $passed .= '1';
                } else {
                    $ltext .= '<i class="ti ti-close"></i>fileinfo extension is not installed on your server.' . '<br/>';
                    $passed .= '0';
                }

                if (extension_loaded('gd')) {
                    $ltext .= '<i class="ti ti-check"></i>gd extension is installed on your server.' . '<br/>';
                    $passed .= '1';
                } else {
                    $ltext .= '<i class="ti ti-close"></i>gd extension is not installed on your server.' . '<br/>';
                    $passed .= '0';
                }

                if (extension_loaded('zip')) {
                    $ltext .= '<i class="ti ti-check"></i>zip extension is installed on your server.' . '<br/>';
                    $passed .= '1';
                } else {
                    $ltext .= '<i class="ti ti-close"></i>zip extension is not installed on your server.' . '<br/>';
                    $passed .= '0';
                }

                if (extension_loaded('calendar')) {
                    $ltext .= '<i class="ti ti-check"></i>calendar extension is installed on your server.' . '<br/>';
                    $passed .= '1';
                } else {
                    $ltext .= '<i class="ti ti-close"></i>calendar extension is not installed on your server.' . '<br/>';
                    $passed .= '0';
                }

                if (ini_get('allow_url_fopen')) {
                    $ltext .= '<i class="ti ti-check"></i>allow_url_fopen is enabled on your server.' . '<br/>';
                    $passed .= '1';
                } else {
                    $ltext .= '<i class="ti ti-close"></i>allow_url_fopen is disabled on your server.' . '<br/>';
                    $passed .= '0';
                }

                ?>

                <?php if (substr_count($passed, '0') == 0): ?>
                    <br/><?php echo $ltext; ?><br/>
                    <h5>Great! System Test Completed. You can run SalepPro on your server. Click Continue For Next Step.</h5>
                    <a href="{{ route('install-step-3') }}" class="btn btn-primary">Continue</a>

                <?php else: ?>

                <br/><?php echo $ltext; ?><br/>Sorry. The requirements of SalePro is not available on your server. Please create <a href="https://lion-coders.com/support" target="_blank">support ticket</a> Or contact with your server administrator.<br><br>
                <a href="#" class="btn btn-primary disabled">Correct The Problem To Continue</a>

                <?php endif ?>

            </div>
            <hr>
            <footer>Copyright &copy; LionCoders. All Rights Reserved.</footer>
        </div>
    </div>
</body>
</html>
--}}
@php
$requirements = [
 ['PHP 8.3 or newer','Installed: '.PHP_VERSION,version_compare(PHP_VERSION,'8.3','>=')],
 ['PDO extension','Database access',extension_loaded('PDO')],
 ['PDO MySQL driver','MySQL connections',extension_loaded('pdo_mysql')],
 ['cURL extension','Remote requests',extension_loaded('curl')],
 ['Fileinfo extension','File detection',extension_loaded('fileinfo')],
 ['GD extension','Image processing',extension_loaded('gd')],
 ['ZIP extension','Archive handling',extension_loaded('zip')],
 ['Calendar extension','Calendar functions',extension_loaded('calendar')],
 ['Remote file access','allow_url_fopen enabled',(bool)ini_get('allow_url_fopen')]];
$allPassed=collect($requirements)->every(fn($item)=>$item[2]);
@endphp
@extends('install.layout', ['currentStep' => 2])
@section('title', 'Server Requirements | SalePro Installer')
@section('content')
<div class="installer-heading"><div class="installer-kicker">Environment check</div><h1 id="installer-title">Server requirements</h1><p>We’re checking that this server has everything SalePro needs.</p></div>
<div class="requirement-list">@foreach($requirements as [$name,$detail,$passed])<div class="requirement-item {{ $passed?'passed':'failed' }}"><span class="requirement-icon">@if($passed)<svg viewBox="0 0 24 24"><path d="M5 12l5 5 10-10"/></svg>@else<svg viewBox="0 0 24 24"><path d="M12 9v4m0 4v.01M3 20h18L12 4z"/></svg>@endif</span><span class="requirement-copy"><strong>{{ $name }}</strong><small>{{ $detail }}</small></span><span class="status-label">{{ $passed?'Ready':'Action needed' }}</span></div>@endforeach</div>
@if($allPassed)<div class="installer-alert success">All checks passed. Your server is ready.</div><div class="action-center"><a href="{{ route('install-step-3') }}" class="btn-installer">Continue to database</a></div>@else<div class="installer-alert danger">Some requirements need attention. Contact your server administrator, then refresh.</div><div class="action-center"><span class="btn-installer is-disabled">Resolve requirements to continue</span></div>@endif
@endsection
