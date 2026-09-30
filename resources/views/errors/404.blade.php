<!DOCTYPE html>
<html dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 | {{ __('db.Oh snap! We are lost') }}</title>
    <style>
        :root{--bg:#f4f7fb;--card:#ffffffee;--text:#172033;--muted:#667085;--accent:#3b82f6;--accent2:#2563eb;--ring:#bfdbfe}
        *{box-sizing:border-box}html,body{min-height:100%}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(circle at 12% 18%,#3b82f629,transparent 28%),radial-gradient(circle at 88% 82%,#0ea5e91f,transparent 30%),var(--bg);color:var(--text);font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
        .card{position:relative;width:min(100%,720px);overflow:hidden;padding:clamp(32px,6vw,64px);text-align:center;background:var(--card);border:1px solid #0f172a14;border-radius:28px;box-shadow:0 24px 70px #0f172a1f;backdrop-filter:blur(10px)}
        .card:before{content:"404";position:absolute;inset-inline-end:-18px;top:-42px;color:#3b82f60f;font-size:clamp(120px,25vw,220px);font-weight:800;line-height:1;letter-spacing:-.08em;pointer-events:none}
        .icon{position:relative;z-index:1;width:88px;height:88px;display:grid;place-items:center;margin:0 auto 28px;color:var(--accent);background:#3b82f61a;border:1px solid #3b82f629;border-radius:24px}.icon svg{width:46px;height:46px}
        .code{position:relative;z-index:1;margin-bottom:10px;color:var(--accent);font-size:14px;font-weight:800;letter-spacing:.18em}h1{position:relative;z-index:1;margin:0;font-size:clamp(32px,6vw,52px);line-height:1.12;letter-spacing:-.04em}p{position:relative;z-index:1;max-width:560px;margin:20px auto 32px;color:var(--muted);font-size:clamp(16px,2.2vw,18px);line-height:1.7}
        .btn{position:relative;z-index:1;min-height:48px;display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:0 20px;color:#fff;background:var(--accent);border:1px solid var(--accent);border-radius:14px;font-size:15px;font-weight:700;text-decoration:none;box-shadow:0 10px 24px #3b82f63d;transition:.18s ease}.btn:hover{transform:translateY(-2px);background:var(--accent2);box-shadow:0 14px 28px #3b82f647}.btn:focus-visible{outline:3px solid var(--ring);outline-offset:3px}.btn svg{width:20px;height:20px}
        @media(max-width:520px){body{padding:14px}.card{border-radius:22px}.btn{width:100%}}@media(prefers-reduced-motion:reduce){.btn{transition:none}}
    </style>
</head>
<body>
<main class="card" role="main" aria-labelledby="error-title">
    <div class="icon" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 16a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M14 16a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M16.346 9.17l-.729 -1.261c-.16 -.248 -1.056 -.203 -1.117 .091l-.177 1.38"/><path d="M19.761 14.813l-2.84 -5.133c-.189 -.31 -.592 -.68 -1.421 -.68c-.828 0 -1.5 .448 -1.5 1v6"/><path d="M7.654 9.17l.729 -1.261c.16 -.249 1.056 -.203 1.117 .091l.177 1.38"/><path d="M4.239 14.813l2.84 -5.133c.189 -.31 .592 -.68 1.421 -.68c.828 0 1.5 .448 1.5 1v6"/><path d="M10 12h4v2h-4z"/></svg>
    </div>
    <div class="code">404</div>
    <h1 id="error-title">{{ __('db.Oh snap! We are lost') }}</h1>
    <p>{{ __('db.It seems we can not find what you are looking for Perhaps searching can help or go back to') }}</p>
    <a class="btn" href="{{ url('/') }}"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12l9 -9l9 9"/><path d="M5 10v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1 -1v-10"/><path d="M9 21v-6a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v6"/></svg>{{ __('db.Home') }}</a>
</main>
</body>
</html>
