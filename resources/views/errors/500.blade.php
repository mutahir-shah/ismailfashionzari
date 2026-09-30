<!DOCTYPE html>
<html dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 | {{ __('db.Oh server just snapped!') }}</title>
    <style>
        :root{--bg:#f8f7ff;--card:#ffffffef;--text:#211d35;--muted:#6f6884;--accent:#7c3aed;--accent2:#6d28d9;--ring:#ddd6fe}
        *{box-sizing:border-box}html,body{min-height:100%}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(circle at 12% 18%,#7c3aed26,transparent 28%),radial-gradient(circle at 88% 82%,#6366f11f,transparent 30%),var(--bg);color:var(--text);font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
        .card{position:relative;width:min(100%,720px);overflow:hidden;padding:clamp(32px,6vw,64px);text-align:center;background:var(--card);border:1px solid #4c1d951a;border-radius:28px;box-shadow:0 24px 70px #4c1d9521;backdrop-filter:blur(10px)}.card:before{content:"500";position:absolute;inset-inline-end:-18px;top:-42px;color:#7c3aed0f;font-size:clamp(120px,25vw,220px);font-weight:800;line-height:1;letter-spacing:-.08em;pointer-events:none}
        .icon{position:relative;z-index:1;width:88px;height:88px;display:grid;place-items:center;margin:0 auto 28px;color:var(--accent);background:#7c3aed1a;border:1px solid #7c3aed29;border-radius:24px}.icon svg{width:46px;height:46px}.code{position:relative;z-index:1;margin-bottom:10px;color:var(--accent);font-size:14px;font-weight:800;letter-spacing:.18em}h1{position:relative;z-index:1;margin:0;font-size:clamp(32px,6vw,52px);line-height:1.12;letter-spacing:-.04em}p{position:relative;z-index:1;max-width:570px;margin:20px auto 32px;color:var(--muted);font-size:clamp(16px,2.2vw,18px);line-height:1.7}
        .btn{position:relative;z-index:1;min-height:48px;display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:0 20px;color:#fff;background:var(--accent);border:1px solid var(--accent);border-radius:14px;font-size:15px;font-weight:700;text-decoration:none;box-shadow:0 10px 24px #7c3aed3d;transition:.18s ease}.btn:hover{transform:translateY(-2px);background:var(--accent2);box-shadow:0 14px 28px #7c3aed47}.btn:focus-visible{outline:3px solid var(--ring);outline-offset:3px}.btn svg{width:20px;height:20px}@media(max-width:520px){body{padding:14px}.card{border-radius:22px}.btn{width:100%}}@media(prefers-reduced-motion:reduce){.btn{transition:none}}
    </style>
</head>
<body>
<main class="card" role="main" aria-labelledby="error-title">
    <div class="icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l9 5v8l-9 5l-9 -5v-8z"/><path d="M12 8v4"/><path d="M12 16h.01"/><path d="M3.5 8.5l8.5 4.5l8.5 -4.5"/></svg></div>
    <div class="code">500</div>
    <h1 id="error-title">{{ __('db.Oh server just snapped!') }}</h1>
    <p>{{ __('db.An error occured due to server not being to able to handle your request') }}</p>
    <a class="btn" href="{{ url()->previous() ?: url('/') }}"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 14l-4 -4l4 -4"/><path d="M5 10h11a4 4 0 1 1 0 8h-1"/></svg>{{ __('db.go_back') }}</a>
</main>
</body>
</html>
