{{--
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <title>SalePro Installer | Step-3</title>
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

                @include('includes.session_message')
	        </header>
	        <hr>
		    <div class="content">
		        <?php
		        if (isset($_GET['_error'])) {
		        	if ($_GET['_error'] != '') {
		        		echo '<h4 class="text-danger">'.$_GET['_error'].'</h4>';
		        	}
		        }
		        ?>
		        <form action="{{ route('install-process') }}" method="post">
                    @csrf
		            <fieldset>
						<label>Purchase Code</label>
		                <input type='text' class="form-control" name="purchasecode" placeholder="Ex: 123456789XXXXXXXX" required>
		                <label>Database Host</label>
		                <input type='text' class="form-control" name="db_host" placeholder="Ex: localhost" required>
		                <label>Database Username</label>
		                <input type='text' class="form-control" name="db_username" placeholder="Ex: salepro2023" required>
		                <label>Database Password</label>
		                <input type='password' class="form-control" name="db_password" placeholder="Ex: PXsfdf1542" required>
		                <label>Database Name</label>
		                <input type='text' class="form-control" name="db_name" placeholder="Ex: salepro_db" required>
		                <button type='submit' class='btn btn-primary btn-block'>Submit</button>
		            </fieldset>
		        </form>
		    </div>
		    <hr>
		    <footer>Copyright &copy; LionCoders. All Rights Reserved.</footer>
		</div>
	</div>


	<script src="{{ asset($asset_prefix . 'install-assets/js/jquery.min.js')}}"></script>
	<script src="{{ asset($asset_prefix . 'install-assets/js/bootstrap.min.js')}}"></script>


</body>
</html>
--}}
@extends('install.layout', ['currentStep' => 3])
@section('title', 'Database Setup | SalePro Installer')
@section('content')
<div class="installer-heading"><div class="installer-kicker">Configuration</div><h1 id="installer-title">Connect your database</h1><p>Enter the credentials supplied by your hosting provider.</p></div>
@include('includes.session_message')
@if(request()->filled('_error'))<div class="installer-alert danger" role="alert">{{ request()->query('_error') }}</div>@endif
<form action="{{ route('install-process') }}" method="post" class="installer-form">@csrf
@foreach([['purchasecode','Purchase code','text','e.g. 123456789XXXXXXXX'],['db_host','Database host','text','localhost'],['db_username','Database username','text','Database user'],['db_password','Database password','password','Database password'],['db_name','Database name','text','salepro_db']] as [$name,$label,$type,$placeholder])
<div class="field-group"><label for="{{ $name }}">{{ $label }}</label><div class="field-wrap">
@switch($name)
    @case('purchasecode')
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5v2m0 4v2m0 4v2"/><path d="M5 5h14a2 2 0 012 2v3a2 2 0 000 4v3a2 2 0 01-2 2H5a2 2 0 01-2-2v-3a2 2 0 000-4V7a2 2 0 012-2z"/></svg>
        @break
    @case('db_host')
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 010 18M12 3a15 15 0 000 18"/></svg>
        @break
    @case('db_username')
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="7" r="4"/><path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg>
        @break
    @case('db_password')
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>
        @break
    @case('db_name')
        <svg viewBox="0 0 24 24" aria-hidden="true"><ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v6c0 4 16 4 16 0V6M4 12v6c0 4 16 4 16 0v-6"/></svg>
        @break
@endswitch
<input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" class="form-control" value="{{ $type==='password'?'':old($name) }}" placeholder="{{ $placeholder }}" required></div></div>
@endforeach
<button class="btn-installer" type="submit">Install SalePro <svg viewBox="0 0 24 24"><path d="M12 3v12m-4-4 4 4 4-4M5 21h14"/></svg></button></form>
@endsection
