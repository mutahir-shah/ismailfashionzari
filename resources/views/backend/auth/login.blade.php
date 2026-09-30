<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{gen_setting()->site_title}}</title>
    @if(!config('database.connections.saleprosaas_landlord'))
    <link rel="icon" type="image/png" href="{{url('logo', gen_setting()->site_logo)}}" />
    <!-- Bootstrap CSS-->
    <link rel="stylesheet" href="<?php echo asset('vendor/bootstrap/css/bootstrap.min.css') ?>" type="text/css">
    @else
    <link rel="icon" type="image/png" href="{{url('../../logo', gen_setting()->site_logo)}}" />
    <!-- Bootstrap CSS-->
    <link rel="stylesheet" href="<?php echo asset('../../vendor/bootstrap/css/bootstrap.min.css') ?>" type="text/css">
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
            background-image: url('{{ !config('database.connections.saleprosaas_landlord') ? asset('css/promo-bg.svg') : asset('../../css/promo-bg.svg') }}');
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
        .promo-side > div {
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
        
        button.dropdown-item {display:flex;}
        
        button svg {margin:0 10px; width:20px}

        /* Demo showcase */
        .demo-showcase {
            position: relative;
            width: 100%;
            margin-top: 28px;
            padding: 22px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 22px;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.16), rgba(255, 255, 255, 0.07));
            box-shadow: 0 24px 55px rgba(40, 19, 80, 0.2);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }

        .demo-showcase::before {
            content: '';
            position: absolute;
            top: -90px;
            right: -70px;
            width: 190px;
            height: 190px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.12);
            pointer-events: none;
        }

        .demo-showcase-header,
        .demo-grid,
        .demo-purchase-row {
            position: relative;
            z-index: 1;
        }

        .demo-showcase-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 16px;
        }

        .demo-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 7px;
            color: rgba(255, 255, 255, 0.78);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .demo-live-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #75f3b0;
            box-shadow: 0 0 0 5px rgba(117, 243, 176, 0.15);
            animation: demo-pulse 2s ease-in-out infinite;
        }

        @keyframes demo-pulse {
            0%, 100% { box-shadow: 0 0 0 4px rgba(117, 243, 176, 0.13); }
            50% { box-shadow: 0 0 0 7px rgba(117, 243, 176, 0.04); }
        }

        .demo-showcase-title {
            margin: 0 0 4px;
            color: #fff;
            font-size: 20px;
            font-weight: 750;
            letter-spacing: -0.35px;
        }

        .demo-showcase-copy {
            margin: 0;
            color: rgba(255, 255, 255, 0.66);
            font-size: 12px;
            line-height: 1.5;
        }

        .demo-count {
            flex: 0 0 auto;
            padding: 6px 10px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
        }

        .demo-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 11px;
        }

        .demo-card {
            display: flex;
            align-items: center;
            width: 100%;
            min-width: 0;
            min-height: 76px;
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, 0.13);
            border-radius: 15px;
            background: rgba(24, 14, 53, 0.2);
            color: #fff;
            text-align: left;
            cursor: pointer;
            transition: transform .22s ease, background-color .22s ease, border-color .22s ease, box-shadow .22s ease;
        }

        .demo-card:hover,
        .demo-card:focus {
            color: #fff;
            text-decoration: none;
            transform: translateY(-3px);
            border-color: rgba(255, 255, 255, 0.4);
            background: rgba(255, 255, 255, 0.16);
            box-shadow: 0 14px 25px rgba(31, 15, 66, 0.22);
            outline: none;
        }

        .demo-card:focus-visible {
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.28), 0 14px 25px rgba(31, 15, 66, 0.22);
        }

        .demo-card-icon {
            display: inline-flex;
            flex: 0 0 42px;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            margin-right: 11px;
            border-radius: 13px;
            background: linear-gradient(135deg, #ff9a62, #ff646f);
            box-shadow: 0 9px 18px rgba(59, 25, 91, 0.18);
        }

        .demo-card--admin .demo-card-icon { background: linear-gradient(135deg, #58c7ff, #5268f5); }
        .demo-card--woo .demo-card-icon { background: linear-gradient(135deg, #a789ff, #7954d8); }
        .demo-card--gym .demo-card-icon { background: linear-gradient(135deg, #5ee0bd, #159b8d); }

        .demo-card-icon svg,
        .demo-card-arrow svg {
            width: 21px;
            height: 21px;
            margin: 0;
        }

        .demo-card-content {
            display: block;
            min-width: 0;
            line-height: 1.2;
        }

        .demo-card-content strong,
        .demo-card-content small {
            display: block;
        }

        .demo-card-content strong {
            overflow: hidden;
            color: #fff;
            font-size: 12px;
            font-weight: 750;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .demo-card-content small {
            margin-top: 5px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 10px;
        }

        .demo-card-arrow {
            display: inline-flex;
            flex: 0 0 auto;
            margin-left: auto;
            padding-left: 7px;
            color: rgba(255, 255, 255, 0.55);
            transition: transform .22s ease, color .22s ease;
        }

        .demo-card:hover .demo-card-arrow,
        .demo-card:focus .demo-card-arrow {
            color: #fff;
            transform: translateX(3px);
        }

        .demo-purchase-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            color: rgba(255, 255, 255, 0.62);
            font-size: 10px;
        }

        .demo-purchase-link {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            gap: 7px;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
        }

        .demo-purchase-link:hover { color: #fff; }
        .demo-purchase-link svg { width: 15px; height: 15px; }

        @media (max-width: 1199.98px) {
            .demo-showcase { padding: 18px; }
            .demo-grid { grid-template-columns: 1fr; }
            .demo-card { min-height: 66px; }
            .demo-card-content strong { font-size: 11px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .demo-live-dot { animation: none; }
            .demo-card, .demo-card-arrow { transition: none; }
        }
    </style>
</head>
<body class="">

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
                <form method="POST" action="{{ route('login') }}" id="login-form">
                @csrf
                    <div class="form-group">
                        <label class="font-weight-600">{{__('db.username')}}</label>
                        <input type="name" name="name" class="form-control" placeholder="{{__('db.username')}}" @if(!config('app.user_verified')) value="admin" @endif required>
                        @if(session()->has('error'))
                            <p>
                                <strong>{{ session()->get('error') }}</strong>
                            </p>
                        @endif
                    </div>
                    <div class="form-group">
                        <label class="font-weight-600">{{__('db.Password')}}</label>
                        <div class="input-group">
                            <input type="password" name="password" class="form-control" placeholder="••••••••" @if(!config('app.user_verified')) value="admin" @endif  required>
                            <div class="input-group-append">
                                <span id="togglePassword" class="input-group-text bg-white border-left-0" style="cursor: pointer;">
                                    <svg id="icon-hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 20px; height: 20px;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>

                                    <svg id="icon-show" class="d-none" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 20px; height: 20px;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                        @if(session()->has('error'))
                            <p>
                                <strong>{{ session()->get('error') }}</strong>
                            </p>
                        @endif
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">

                        @if(gen_setting()->disable_forgot_password == 0)
                        <a href="{{ route('password.request') }}" class="small font-weight-bold">{{__('db.Forgot Password?')}}</a>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-primary btn-block shadow-sm mb-2">{{__('db.LogIn')}}</button>

                    @if(gen_setting()->disable_signup == 0)
                    <p class="text-center mt-5 text-muted">
                        {{__('db.Do not have an account?')}} <a href="{{url('register')}}" class="font-weight-bold">{{__('db.Register')}}</a>
                    </p>
                    @endif
                </form>

                <div class="footer-text w-100 d-flex justify-content-center mt-5">
                    <p>{{__('db.Developed By')}} <span class="external">{{gen_setting()->developed_by}}</span></p>
                </div>
            </div>
        </div>

        <div class="col-lg-6 d-none d-lg-flex">
            <div class="promo-side w-100">
                <div>
                    <h1 class="font-weight-bold">Welcome Back</h1>
                    <p>Enter your username and password to access your account.</p>
                    <!-- This section for demo only-->
                    @if(!config('app.user_verified') && !config('database.connections.saleprosaas_landlord'))
                        <div class="demo-showcase" aria-label="Available product demos">
                            <div class="demo-showcase-header">
                                <div>
                                    <span class="demo-eyebrow"><span class="demo-live-dot"></span>Live product demos</span>
                                    <h2 class="demo-showcase-title">Explore more with SalePro</h2>
                                    <p class="demo-showcase-copy">Choose an experience and get instant demo access.</p>
                                </div>
                                <span class="demo-count">4 demos</span>
                            </div>

                            <div class="demo-grid">
                                <button type="button" data-page="ecom_front" data-env=".env.ecom" class="demo-card demo-card--store demo-btn" aria-label="Open eCommerce storefront demo">
                                    <span class="demo-card-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" />
                                        </svg>
                                    </span>
                                    <span class="demo-card-content">
                                        <strong>eCommerce Store</strong>
                                        <small>Customer storefront</small>
                                    </span>
                                    <span class="demo-card-arrow">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                    </span>
                                </button>

                                <button type="button" data-page="back_admin" data-env=".env.ecom" class="demo-card demo-card--admin demo-btn" aria-label="Open eCommerce admin demo">
                                    <span class="demo-card-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25m-4.5-13.5h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0L6.75 21m3.75-10.5 2.25 2.25L16.5 9" />
                                        </svg>
                                    </span>
                                    <span class="demo-card-content">
                                        <strong>eCommerce Admin</strong>
                                        <small>Backend operations</small>
                                    </span>
                                    <span class="demo-card-arrow">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                    </span>
                                </button>

                                <button type="button" data-page="back_admin" data-env=".env.wcom" class="demo-card demo-card--woo demo-btn" aria-label="Open WooCommerce connector demo">
                                    <span class="demo-card-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.5 9h3M4 9h2.5M11 9l3 11 4-9M5.5 9 9 20l3-7M18 11c.177-.528 1-1.364 1-2.5C19 6.72 18.224 6 17.125 6 16.227 6 16 6.812 16 7.429c0 1.83 2 2.058 2 3.571"/><path d="M3 12a9 9 0 1 0 18 0 9 9 0 1 0-18 0"/></svg>
                                    </span>
                                    <span class="demo-card-content">
                                        <strong>WooCommerce</strong>
                                        <small>Store connector</small>
                                    </span>
                                    <span class="demo-card-arrow">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                    </span>
                                </button>

                                <button type="button" data-page="back_admin" data-env=".env.gym" class="demo-card demo-card--gym demo-btn" aria-label="Open gym management demo">
                                    <span class="demo-card-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12h1M6 8H4a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h2M6 7v10a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1ZM9 12h6M15 7v10a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1h-1a1 1 0 0 0-1 1ZM18 8h2a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1h-2M22 12h-1"/></svg>
                                    </span>
                                    <span class="demo-card-content">
                                        <strong>Gym Management</strong>
                                        <small>Membership suite</small>
                                    </span>
                                    <span class="demo-card-arrow">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                    </span>
                                </button>
                            </div>

                            <div class="demo-purchase-row">
                                <span>Ready to launch your own workspace?</span>
                                <a class="demo-purchase-link" target="_blank" rel="noopener noreferrer" href="https://lion-coders.com/software/salepro-saas-pos-inventory-saas-php-script">
                                    Explore SaaS
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // 1. Landlord Logic
        @if(config('database.connections.saleprosaas_landlord'))
            const storedMessage = localStorage.getItem("message");
            if(storedMessage) {
                alert(storedMessage);
                localStorage.removeItem("message");
            }

            const numberOfUserAccount = @json($numberOfUserAccount);
            
            // Replaces $.ajax
            fetch('{{route("package.fetchData", gen_setting()->package_id)}}')
                .then(response => response.json())
                .then(data => {
                    if(data['number_of_user_account'] > 0 && data['number_of_user_account'] <= numberOfUserAccount) {
                        const registerSection = document.querySelector(".register-section");
                        if(registerSection) registerSection.classList.add('d-none');
                    }
                })
                .catch(error => console.error('Error fetching package data:', error));
        @endif

        // 2. Alert slideUp Replacement
        const alerts = document.querySelectorAll("div.alert");
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = "all 0.8s ease";
                alert.style.opacity = "0";
                alert.style.height = "0";
                alert.style.padding = "0";
                alert.style.margin = "0";
                setTimeout(() => alert.remove(), 800);
            }, 4000);
        });

        // 3. Password Toggle Logic
        const toggleBtn = document.getElementById('togglePassword');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const passwordField = document.querySelector("input[name='password']");
                const iconHidden = document.getElementById("icon-hidden");
                const iconShow = document.getElementById("icon-show");

                if (passwordField.type === "password") {
                    passwordField.type = "text";
                    iconHidden.classList.add('d-none');
                    iconShow.classList.remove('d-none');
                } else {
                    passwordField.type = "password";
                    iconHidden.classList.remove('d-none');
                    iconShow.classList.add('d-none');
                }
            });
        }

        // 4. Theme Logic
        const theme = @json($theme);
        const body = document.body;
        const themeIcon = document.querySelector('#switch-theme i');

        if(theme === 'dark') {
            body.classList.add('dark-mode');
            if(themeIcon) themeIcon.classList.add('ti ti-brightness-down');
        } else {
            body.classList.remove('dark-mode');
            if(themeIcon) themeIcon.classList.add('ti ti-brightness-up');
        }

        // 5. Cookie Helper
        function setEnvCookie(cookieValue) {
            const date = new Date();
            date.setTime(date.getTime() + (1 * 24 * 60 * 60 * 1000)); // 1 day
            document.cookie = `env_name=${cookieValue}; expires=${date.toUTCString()}; path=/`;
        }

        // 6. Auto-trigger from /demo/{type} direct URL
        // যখন কেউ সরাসরি /demo/pos লিঙ্ক দিয়ে আসে, তখন index.php
        // ?demo=1&env=...&page=... সহ এই login page এ redirect করে।
        // এই block সেটা detect করে automatically login submit করে।
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('demo') === '1') {
            const env  = urlParams.get('env');
            const page = urlParams.get('page');

            if (env && page) {
                if (env === '.env.ecom' && page === 'ecom_front') {
                    // ecom frontend — নতুন tab এ খোলো, login দরকার নেই
                    window.open("{{ url('/') }}?demo=true", "_blank");
                } else {
                    const nameInput = document.querySelector("input[name='name']");
                    const passInput = document.querySelector("input[name='password']");

                    // page অনুযায়ী username ঠিক করো
                    let val = 'admin';
                    if (page === 'back_staff')    val = 'staff';
                    if (page === 'back_customer') val = 'james';

                    if (nameInput) nameInput.value = val;
                    if (passInput) passInput.value = val;

                    // 200ms পর form auto-submit
                    const form = document.getElementById('login-form');
                    if (form) setTimeout(() => form.submit(), 200);
                }
            }
        }

        // 7. Demo Button Logic (Event Delegation) — login page এর dropdown থেকে
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.demo-btn');
            if (!btn) return;

            e.preventDefault();
            const env = btn.getAttribute('data-env');
            const page = btn.getAttribute('data-page');
            const href = btn.getAttribute('href');

            setEnvCookie(env);

            if (env === '.env.ecom' && page === 'ecom_front') {
                window.open("{{ url('/') }}?demo=true", "_blank");
            } else {
                const nameInput = document.querySelector("input[name='name']");
                const passInput = document.querySelector("input[name='password']");
                
                let val = 'admin';
                if (page === 'back_staff') val = 'staff';
                else if (page === 'back_customer') val = 'james';

                if(nameInput) { nameInput.value = val; nameInput.focus(); }
                if(passInput) { passInput.value = val; passInput.focus(); }

                const form = document.getElementById('login-form');
                if(form) {
                    if(href) form.action = href;
                    form.submit();
                }
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        // 1. Toggle the dropdown when the button is clicked
        document.addEventListener('click', function(event) {
            const toggle = event.target.closest('[data-toggle="dropdown"]');
            
            if (toggle) {
                event.preventDefault();
                const parent = toggle.parentElement;
                const menu = parent.querySelector('.dropdown-menu');
                const isOpen = parent.classList.contains('show');

                // Close all other open dropdowns first
                closeAllDropdowns();

                // Toggle the current one
                if (!isOpen) {
                    parent.classList.add('show');
                    menu.classList.add('show');
                    toggle.setAttribute('aria-expanded', 'true');
                }
            } else if (!event.target.closest('.dropdown-menu')) {
                // 2. Close dropdowns if clicking outside the menu or toggle
                closeAllDropdowns();
            }
        });

        // Function to remove 'show' classes from all dropdown elements
        function closeAllDropdowns() {
            document.querySelectorAll('.dropdown, .dropup').forEach(container => {
                container.classList.remove('show');
                const menu = container.querySelector('.dropdown-menu');
                const toggle = container.querySelector('[data-toggle="dropdown"]');
                if (menu) menu.classList.remove('show');
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
            });
        }

        // 3. Handle 'Esc' key to close dropdowns for accessibility
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAllDropdowns();
            }
        });
    });
</script>

</body>
</html>
