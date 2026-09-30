@php
    $assetPrefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
    $currentStep = $currentStep ?? 1;
    $steps = ['Welcome', 'Requirements', 'Database', 'Complete'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#6d4aff">
    <title>@yield('title', 'SalePro Installer')</title>
    <link rel="shortcut icon" href="{{ asset($assetPrefix . 'install-assets/images/favicon.ico') }}">
    <link href="{{ asset($assetPrefix . 'install-assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset($assetPrefix . 'install-assets/css/style.css') }}" rel="stylesheet">
    <style>
        :root{--p:#6d4aff;--pd:#5635db;--text:#172033;--muted:#667085;--line:#e4e7ec;--ok:#079455;--bad:#d92d20}
        *{box-sizing:border-box}body{min-height:100vh;margin:0;color:var(--text);background:radial-gradient(circle at 10% 5%,rgba(109,74,255,.16),transparent 30rem),radial-gradient(circle at 90% 90%,rgba(49,181,255,.12),transparent 28rem),#f7f8fc;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.installer-page{padding:48px 20px}.installer-shell{max-width:760px;margin:auto}.installer-brand{display:flex;justify-content:center;margin-bottom:24px}.installer-brand img{max-width:132px}.installer-card{overflow:hidden;border:1px solid rgba(228,231,236,.9);border-radius:24px;background:rgba(255,255,255,.97);box-shadow:0 24px 64px rgba(30,38,60,.1)}.installer-progress{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;padding:24px 32px;border-bottom:1px solid var(--line);background:#fbfbfe}.progress-step{display:flex;align-items:center;gap:8px;color:#98a2b3}.progress-dot{display:flex;width:30px;height:30px;flex:0 0 30px;align-items:center;justify-content:center;border:1px solid #d0d5dd;border-radius:50%;background:#fff;font-size:12px;font-weight:700}.progress-dot svg{width:17px}.progress-label{overflow:hidden;font-size:12px;font-weight:700;text-overflow:ellipsis;white-space:nowrap}.is-active{color:var(--p)}.is-active .progress-dot{color:#fff;border-color:var(--p);background:var(--p);box-shadow:0 0 0 4px rgba(109,74,255,.12)}.is-complete{color:var(--ok)}.is-complete .progress-dot{color:#fff;border-color:var(--ok);background:var(--ok)}.installer-content{padding:44px 48px 48px}.installer-heading{max-width:590px;margin:0 auto 30px;text-align:center}.installer-kicker{margin-bottom:8px;color:var(--p);font-size:12px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.installer-heading h1{margin:0 0 12px;font-size:32px;font-weight:750;letter-spacing:-.03em}.installer-heading p{margin:0;color:var(--muted);font-size:16px;line-height:1.6}.hero-icon{display:inline-flex;width:64px;height:64px;margin-bottom:20px;align-items:center;justify-content:center;color:var(--p);border-radius:18px;background:rgba(109,74,255,.1)}svg{fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}.hero-icon svg{width:32px}.hero-icon.success{color:var(--ok);background:rgba(7,148,85,.1)}.welcome-points,.credentials-card{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:8px 0 30px}.welcome-points div,.credentials-card div{padding:16px;border:1px solid var(--line);border-radius:14px;background:#fcfcfd;text-align:center;font-size:13px;font-weight:700}.credentials-card{grid-template-columns:repeat(2,1fr);max-width:460px;margin:0 auto 20px}.credentials-card span{display:block;color:var(--muted);font-size:12px}.credentials-card strong{display:block;margin-top:6px;font-size:17px}.btn-installer{display:inline-flex;min-height:48px;padding:11px 22px;align-items:center;justify-content:center;gap:9px;color:#fff!important;border:0;border-radius:12px;background:var(--p);box-shadow:0 8px 18px rgba(109,74,255,.22);font-weight:700}.btn-installer:hover{background:var(--pd);transform:translateY(-1px)}.btn-installer svg{width:20px}.action-center{text-align:center}.requirement-list{display:grid;gap:10px;margin-bottom:24px}.requirement-item{display:flex;padding:14px 16px;align-items:center;gap:13px;border:1px solid var(--line);border-radius:13px}.requirement-icon{display:flex;width:34px;height:34px;align-items:center;justify-content:center;border-radius:10px}.requirement-icon svg{width:20px}.passed .requirement-icon{color:var(--ok);background:#ecfdf3}.failed .requirement-icon{color:var(--bad);background:#fef3f2}.requirement-copy{display:flex;min-width:0;flex:1;flex-direction:column}.requirement-copy small{color:var(--muted)}.status-label{color:var(--ok);font-size:11px;font-weight:800;text-transform:uppercase}.failed .status-label{color:var(--bad)}.installer-alert{margin-bottom:22px;padding:14px 16px;border-radius:12px;font-size:14px}.installer-alert.success{color:#05603a;border:1px solid #abefc6;background:#ecfdf3}.installer-alert.danger{color:#912018;border:1px solid #fecdca;background:#fef3f2}.is-disabled{color:#98a2b3!important;background:#f2f4f7;box-shadow:none}.installer-form{display:grid;grid-template-columns:1fr 1fr;gap:18px}.field-group:first-of-type,.field-group:nth-of-type(2),.field-group:last-of-type,.installer-form button{grid-column:1/-1}.field-group label{display:block;margin:0 0 7px;font-size:14px;font-weight:700}.field-wrap{position:relative}.field-wrap svg{position:absolute;top:15px;left:14px;width:20px;color:#98a2b3}.field-wrap .form-control{height:50px;padding-left:46px;border:1px solid #d0d5dd;border-radius:11px}.field-wrap .form-control:focus{border-color:var(--p);box-shadow:0 0 0 4px rgba(109,74,255,.12)}.security-note{color:var(--muted);text-align:center;font-size:13px}.installer-footer{padding:20px;color:var(--muted);border-top:1px solid var(--line);background:#fbfbfe;text-align:center;font-size:12px}.installer-footer p{margin:3px}
        @media(max-width:680px){.installer-page{padding:20px 12px}.installer-card{border-radius:18px}.installer-progress{padding:18px 16px}.progress-step{justify-content:center}.progress-label{display:none}.installer-content{padding:34px 22px 38px}.installer-heading h1{font-size:27px}.welcome-points,.credentials-card,.installer-form{grid-template-columns:1fr}.field-group,.installer-form button{grid-column:1!important}.status-label{display:none}}
    </style>
</head>
<body>
<main class="installer-page">
    <div class="installer-shell">
        <a class="installer-brand" href="{{ route('install-step-1') }}" aria-label="SalePro installer home">
            <img src="{{ asset($assetPrefix . 'install-assets/images/logo.png') }}" alt="SalePro">
        </a>
        <section class="installer-card" aria-labelledby="installer-title">
            <nav class="installer-progress" aria-label="Installation progress">
                @foreach ($steps as $index => $label)
                    @php $step = $index + 1; @endphp
                    <div class="progress-step {{ $step < $currentStep ? 'is-complete' : '' }} {{ $step === $currentStep ? 'is-active' : '' }}" @if ($step === $currentStep) aria-current="step" @endif>
                        <span class="progress-dot" aria-hidden="true">
                            @if ($step < $currentStep)
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12l5 5l10-10"/></svg>
                            @else
                                {{ $step }}
                            @endif
                        </span>
                        <span class="progress-label">{{ $label }}</span>
                    </div>
                @endforeach
            </nav>
            <div class="installer-content">@yield('content')</div>
            <footer class="installer-footer">
                <p>Need help? <a href="https://lion-coders.com/support" target="_blank" rel="noopener noreferrer">Contact LionCoders support</a></p>
                <p>&copy; {{ date('Y') }} LionCoders. All rights reserved.</p>
            </footer>
        </section>
    </div>
</main>
</body>
</html>
