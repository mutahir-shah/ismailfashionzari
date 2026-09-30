@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
<?php $general_setting = DB::table('general_settings')->find(1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{gen_setting()->site_title}}</title>

    <link rel="icon" type="image/png" href="{{url($asset_prefix . 'logo', gen_setting()->site_logo)}}" />
    <!-- Bootstrap CSS-->
    <link rel="stylesheet" href="{{ asset($asset_prefix . 'vendor/bootstrap/css/bootstrap.min.css') }}" type="text/css">

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
    <div class="row min-vh-100">
        <div class="col-lg-6 d-flex align-items-center position-relative py-5">
            <div class="login-container w-100 px-lg-5 px-3">
                <div class="mb-5" style="margin: auto; text-align: center;">
                    @if(gen_setting()->site_logo)
                    <img src="{{url('logo', gen_setting()->site_logo)}}" width="120">
                    @else
                    <span>{{gen_setting()->site_title}}</span>
                    @endif
                </div>

                <form method="POST" action="{{ route('register') }}" id="register-form">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small">{{__('db.username')}} *</label>
                            <input type="text" name="name" required class="form-control" value="{{old('name')}}">
                            @if ($errors->has('name')) <small class="text-danger">{{ $errors->first('name') }}</small> @endif
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small">{{__('db.email')}} *</label>
                            <input type="email" name="email" required class="form-control" value="{{old('email')}}">
                            @if ($errors->has('email')) <small class="text-danger">{{ $errors->first('email') }}</small> @endif
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small">{{__('db.Phone Number')}} *</label>
                            <input type="text" name="phone_number" required class="form-control">
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small">{{__('db.Company Name')}}</label>
                            <input type="text" name="company_name" class="form-control">
                        </div>

                        <div class="col-12 form-group">
                            <label class="font-weight-bold small">Role *</label>
                            <select required name="role_id" id="role-id" class="form-control custom-select">
                                <option value="">Select Role</option>
                                @foreach($lims_role_list as $role)
                                    @if($role->id != 1 && $role->id != 2)
                                        <option value="{{$role->id}}">{{$role->name}}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div id="customer-section" class="col-12 d-none">
                            <div class="card card-body bg-light-custom border-0 mb-3">
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label class="small font-weight-bold">{{__('db.name')}} *</label>
                                        <input type="text" name="customer_name" class="form-control customer-field">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label class="small font-weight-bold">Customer Group *</label>
                                        <select name="customer_group_id" class="form-control customer-field">
                                            <option value="">Select group</option>
                                            @foreach($lims_customer_group_list as $customer_group)
                                                <option value="{{$customer_group->id}}">{{$customer_group->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-12 form-group">
                                        <label class="small font-weight-bold">{{__('db.Address')}} *</label>
                                        <input type="text" name="address" class="form-control customer-field">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label class="small font-weight-bold">{{__('db.City')}} *</label>
                                        <input type="text" name="city" class="form-control customer-field">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label class="small font-weight-bold">{{__('db.Country')}}</label>
                                        <input type="text" name="country" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="biller-id" class="col-md-6 form-group d-none">
                            <label class="small font-weight-bold">Biller *</label>
                            <select name="biller_id" class="form-control">
                                <option value="">Select Biller</option>
                                @foreach($lims_biller_list as $biller)
                                    <option value="{{$biller->id}}">{{$biller->name}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="warehouse-id" class="col-md-6 form-group d-none">
                            <label class="small font-weight-bold">Warehouse *</label>
                            <select name="warehouse_id" class="form-control">
                                <option value="">Select Warehouse</option>
                                @foreach($lims_warehouse_list as $warehouse)
                                    <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small">{{__('db.Password')}} *</label>
                            <input id="password" type="password" name="password" required class="form-control">
                            @if ($errors->has('password')) <small class="text-danger">{{ $errors->first('password') }}</small> @endif
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small">{{__('db.Confirm Password')}} *</label>
                            <input id="password-confirm" type="password" name="password_confirmation" required class="form-control">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block shadow-sm mt-3">Register</button>
                    
                    <p class="text-center mt-4 text-muted">
                        {{__('db.Already have an account')}}? <a href="{{url('login')}}" class="text-primary font-weight-bold">{{__('db.LogIn')}}</a>
                    </p>
                </form>
            </div>
        </div>

        <div class="col-lg-6 d-none d-lg-flex">
            <div class="promo-side w-100">
              <div>
                <h1 class="font-weight-bold">Create Account</h1>
                <p class="lead">Fill in the details to register.</p>
              </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // 1. Landlord Limit Check (Fetch API replacement for $.ajax)
    @if(config('database.connections.saleprosaas_landlord'))
        const numberOfUserAccount = @json($numberOfUserAccount);
        fetch('{{route("package.fetchData", gen_setting()->package_id)}}')
            .then(response => response.json())
            .then(data => {
                if(data['number_of_user_account'] > 0 && data['number_of_user_account'] <= numberOfUserAccount) {
                    localStorage.setItem("message", "You have exceeded your user limit. Please upgrade your package.");
                    window.location.href = "{{route('user.index')}}";
                }
            })
            .catch(err => console.error("Error checking limits", err));
    @endif

    // 2. Dynamic Section Toggling
    const roleSelect = document.getElementById('role-id');
    const customerSection = document.getElementById('customer-section');
    const billerSection = document.getElementById('biller-id');
    const warehouseSection = document.getElementById('warehouse-id');

    const customerFields = document.querySelectorAll('.customer-field');
    const billerSelect = document.querySelector('select[name="biller_id"]');
    const warehouseSelect = document.querySelector('select[name="warehouse_id"]');

    roleSelect.addEventListener('change', function() {
        const roleId = this.value;

        // Reset all dynamic sections first
        customerSection.classList.add('d-none');
        billerSection.classList.add('d-none');
        warehouseSection.classList.add('d-none');
        
        // Remove required attributes
        customerFields.forEach(f => f.required = false);
        billerSelect.required = false;
        warehouseSelect.required = false;

        if (roleId === '5') {
            // Customer Role
            customerSection.classList.remove('d-none');
            customerFields.forEach(f => f.required = true);
        } 
        else if (roleId !== "" && parseInt(roleId) > 2) {
            // Staff/Other Roles
            billerSection.classList.remove('d-none');
            warehouseSection.classList.remove('d-none');
            billerSelect.required = true;
            warehouseSelect.required = true;
        }
    });

    // 3. Simple dark mode helper for background colors
    const body = document.body;
    if (body.classList.contains('dark-mode')) {
        // Any specific registration-only dark mode tweaks can go here
    }
});
</script>
</body>
</html>

