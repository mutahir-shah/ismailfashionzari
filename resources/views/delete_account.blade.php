@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{gen_setting()->site_title}} - Delete Account</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="all,follow">
    

    <link rel="icon" type="image/png" href="{{url($asset_prefix . 'logo', gen_setting()->site_logo)}}" />
    <!-- Bootstrap CSS-->
    <link rel="stylesheet" href="<?php echo asset($asset_prefix . 'vendor/bootstrap/css/bootstrap.min.css') ?>" type="text/css">
    <!-- Font Awesome CSS-->
    <link rel="preload" href="<?php echo asset($asset_prefix . 'vendor/font-awesome/css/font-awesome.min.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset($asset_prefix . 'vendor/font-awesome/css/font-awesome.min.css') ?>" rel="stylesheet"></noscript>
    <!-- login stylesheet-->
    <link rel="stylesheet" href="<?php echo asset($asset_prefix . 'css/auth.css') ?>" id="theme-stylesheet" type="text/css">

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
  </head>
  <body>
    <div class="page login-page">
      <div class="container">
        <div class="form-outer text-center d-flex align-items-center">
          <div class="form-inner">
            <div class="logo">
                @if(gen_setting()->site_logo)
                <img src="{{url('logo', gen_setting()->site_logo)}}" width="110">
                @else
                <span>{{gen_setting()->site_title}}</span>
                @endif
            </div>
            
            <h4 class="mt-4 mb-4">Send Request for Deleting Account</h4>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible text-center">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('delete-account.submit') }}" method="POST">
                @csrf
                <div class="form-group-material">
                    <input id="email" type="email" name="email" required class="input-material" value="{{ old('email') }}">
                    <label for="email" class="label-material">Email Address</label>
                    @error('email')
                        <p class="text-danger mt-1"><small>{{ $message }}</small></p>
                    @enderror
                </div>

                <div class="form-group-material">
                    <input id="reason" name="reason" class="input-material" rows="3"/>
                    <label for="reason" class="label-material">Reason for deletion</label>
                     @error('reason')
                        <p class="text-danger mt-1"><small>{{ $message }}</small></p>
                    @enderror
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Submit Request</button>
            </form>
            
            <div class="mt-3">
                 <a href="{{ url('login') }}" class="forgot-pass">Back to Login</a>
            </div>

          </div>
          <div class="copyrights text-center">
            <p>{{__('db.Developed By')}} <span class="external">{{gen_setting()->developed_by}}</span></p>
          </div>
        </div>
      </div>
    </div>
    

<script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/jquery/jquery.min.js') ?>"></script>
<script>
    $("div.alert").delay(4000).slideUp(800);

    //switch theme code
    var theme = <?php echo json_encode($theme); ?>;
    if(theme == 'dark') {
        $('body').addClass('dark-mode');
    }
    else {
        $('body').removeClass('dark-mode');
    }

    // ------------------------------------------------------- //
    // Material Inputs
    // ------------------------------------------------------ //

    var materialInputs = $('input.input-material, textarea.input-material');

    // activate labels for prefilled values
    materialInputs.filter(function() { return $(this).val() !== ""; }).siblings('.label-material').addClass('active');

    // move label on focus
    materialInputs.on('focus', function () {
        $(this).siblings('.label-material').addClass('active');
    });

    // remove/keep label on blur
    materialInputs.on('blur', function () {
        $(this).siblings('.label-material').removeClass('active');

        if ($(this).val() !== '') {
            $(this).siblings('.label-material').addClass('active');
        } else {
            $(this).siblings('.label-material').removeClass('active');
        }
    });
</script>
</body>
</html>
