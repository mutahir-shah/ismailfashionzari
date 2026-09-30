@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{gen_setting()->site_title}}</title>
    @if(!config('database.connections.saleprosaas_landlord'))
    <link rel="icon" type="image/png" href="{{url('logo', gen_setting()->site_logo)}}" />
    <!-- Bootstrap CSS-->
    <link rel="stylesheet" href="<?php echo asset($asset_prefix . 'vendor/bootstrap/css/bootstrap.min.css') ?>" type="text/css">
    @else
    <link rel="icon" type="image/png" href="{{url('../../logo', gen_setting()->site_logo)}}" />
    <!-- Bootstrap CSS-->
    <link rel="stylesheet" href="<?php echo asset($asset_prefix . 'vendor/bootstrap/css/bootstrap.min.css') ?>" type="text/css">
    @endif

    <!-- Google fonts -->
    @if(gen_setting()->font_css)
      {!! gen_setting()->font_css !!}
    @else
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,100..900&display=swap" rel="stylesheet">
    @endif

    <!-- Custom CSS from general settings -->
    {!! gen_setting()->auth_css !!}

    <style>
        body { font-size: 14px;font-family: 'Inter', sans-serif;}
        .vh-100 { min-height: 100vh; }
        a{color: #7c5cc4;}
        
        /* Left Side Styles */
        .login-container { padding: 3% 0; }
        .login-container form { max-width: 400px; margin: auto; }
        .form-control { height: 38px; border-radius: .25rem; border: 1px solid #ddd; }
        .btn-primary { background-color: #7c5cc4; border: none; height: 40px; border-radius: .25rem; font-weight: 600; }
        .btn-primary:hover { background-color: #6a4bb3; }
        .btn-outline-light { border: 1px solid #ddd; color: #333; height: 38px; border-radius: .25rem; font-weight: 500; }
        .btn-outline-light img { width: 20px; margin-right: 8px; }
        
        /* Right Side Styles */
        .promo-side {
            background-image: url('{{asset('public/css/promo-bg.svg')}}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: white;
            padding: 10% 8%;
            border-radius: 24px;
            margin: 15px;
            overflow: hidden;
            z-index: 1; 
            position: relative;
        }
        .promo-side div {
            height: calc(100vh - 60px);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .dashboard-preview {
            margin-top: 38px;
            box-shadow: 0 20px 38px rgba(0,0,0,0.2);
            border-radius: 12px;
            width: 120%; /* Creates the "peek" effect */
        }
        .footer-text { font-size: 0.85rem; color: #888; }

        /* Dark Mode Variables */
        :root {
            --bg-dark: #0f172a;           /* Deep Navy background */
            --card-dark: #1e293b;         /* Slightly lighter slate for inputs/cards */
            --text-main: #f8fafc;         /* Off-white text */
            --text-muted: #94a3b8;        /* Slate gray for secondary text */
            --input-border: #334155;      /* Border for dark inputs */
        }

        body.dark-mode {
            background-color: var(--bg-dark);
            color: var(--text-main);
        }

        /* Left Side Adjustments */
        .dark-mode .form-control {
            background-color: var(--card-dark);
            border-color: var(--input-border);
            color: var(--text-main);
        }

        .dark-mode .form-control:focus {
            background-color: var(--card-dark);
            color: #fff;
            border-color: #7c5cc4;
        }

        .dark-mode .input-group-text {
            background-color: var(--card-dark) !important;
            border-color: var(--input-border) !important;
            color: var(--text-muted);
        }

        .dark-mode .text-muted {
            color: var(--text-muted) !important;
        }

        .dark-mode .btn-outline-light {
            border-color: var(--input-border);
            color: var(--text-main);
        }

        .dark-mode .btn-outline-light:hover {
            background-color: var(--input-border);
        }

        /* Horizontal Rule with "Or Login With" */
        .dark-mode hr {
            border-top: 1px solid var(--input-border);
        }

        .dark-mode .bg-white {
            background-color: var(--bg-dark) !important; /* Matches body bg */
        }

        .dark-mode .promo-side {
            opacity:0.9;
        }

        /* Footer Link Adjustments */
        .dark-mode .footer-text a {
            color: var(--text-muted) !important;
        }
    </style>
</head>
<body>
  <div class="container-fluid">
    <div class="row vh-100">
        <div class="col-lg-6 d-flex align-items-center position-relative">
            <div class="login-container w-100">
                <div class="mb-5" style="margin: auto; text-align: center;">
                    @if(gen_setting()->site_logo)
                    <img src="{{url('logo', gen_setting()->site_logo)}}" width="120">
                    @else
                    <span>{{gen_setting()->site_title}}</span>
                    @endif
                </div>

                @if(session()->has('delete_message'))
                <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('delete_message') }}</div>
                @endif
                @if(session()->has('message'))
                  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{!! session()->get('message') !!}</div>
                @endif
                @if(session()->has('not_permitted'))
                  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
                @endif
                <form method="POST" action="{{ route('password.email') }}" class="" id="login-form">
                    @csrf

                    <div class="form-group-material">
                        <label class="font-weight-600">{{__('db.email')}}</label>
                        <input id="email" type="email" name="email" required class="input-material form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" value="{{ old('email') }}">
                        @if ($errors->has('email'))
                            <span class="invalid-feedback">
                                <strong>{{ $errors->first('email') }}</strong>
                            </span>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-primary btn-block shadow-sm mt-3 mb-2">{{ __('db.submit') }}</button>
                </form>
            </div>
        </div>

        <div class="col-lg-6 d-none d-lg-flex">
            <div class="promo-side w-100">
                <div>
                    <h1 class="font-weight-bold">Lets get back</h1>
                    <p class="mb-2">Enter your email address to reset your password.</p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
