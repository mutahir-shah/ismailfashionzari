<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#0f172a" id="meta-theme-color">
    <title>{{ $warehouse->name }} – Menu</title>
    <meta name="description" content="Browse and order from {{ $warehouse->name }} easily via WhatsApp.">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        (function() {
            try {
                const savedTheme = localStorage.getItem('pm-theme');
                const prefersLight = window.matchMedia('(prefers-color-scheme: light)').matches;
                if (savedTheme === 'light' || (!savedTheme && prefersLight)) {
                    document.documentElement.setAttribute('data-theme', 'light');
                    document.getElementById('meta-theme-color').setAttribute('content', '#f8fafc');
                }
            } catch (e) {}
        })();
    </script>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ─── Design Tokens ─────────────────────────────────────── */
        :root {
            --bg:          #0f172a;
            --bg-surface:  #1e293b;
            --bg-card:     #263248;
            --bg-header:   rgba(15,23,42,.92);
            --accent:      #38bdf8;
            --accent-dark: #0284c7;
            --accent-alpha: rgba(56,189,248,.12);
            --accent-wa:   #25D366;
            --accent-wa-d: #128C7E;
            --text:        #f1f5f9;
            --text-muted:  #94a3b8;
            --text-faint:  #475569;
            --border:      #334155;
            --radius:      14px;
            --radius-sm:   8px;
            --shadow:      0 4px 24px rgba(0,0,0,.4);
            --btn-text:    #0f172a;
            --transition:  .2s cubic-bezier(.4,0,.2,1);
            --header-h:    64px;
            --cat-h:       50px;
        }

        :root[data-theme="light"] {
            --bg:          #f8fafc;
            --bg-surface:  #ffffff;
            --bg-card:     #ffffff;
            --bg-header:   rgba(248,250,252,.92);
            --accent:      #004eeb;
            --accent-dark: #0284c7;
            --accent-alpha: rgba(14,165,233,.12);
            --text:        #0f172a;
            --text-muted:  #475569;
            --text-faint:  #94a3b8;
            --border:      #e2e8f0;
            --shadow:      0 4px 24px rgba(0,0,0,.08);
            --btn-text:    #ffffff;
        }

        /* ─── Reset ─────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overscroll-behavior-y: contain;
        }
        img { display: block; max-width: 100%; }
        button { cursor: pointer; border: none; background: none; color: inherit; font: inherit; }
        a { text-decoration: none; color: inherit; }

        /* ─── Sticky Header ─────────────────────────────────────── */
        .pm-sticky-header {
            position: sticky;
            top: 0;
            z-index: 100;
            height: var(--header-h);
            background: var(--bg-header);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 16px;
        }
        .pm-header-brand {
            flex: 1;
            min-width: 0;
        }
        .pm-header-brand h1 {
            font-size: 1.3rem;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
        }
        .pm-header-context {
            font-size: .75rem;
            color: var(--accent);
            margin-top: 2px;
        }
        .pm-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        .pm-icon-btn {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            background: var(--bg-surface);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background var(--transition);
        }
        .pm-icon-btn:hover { background: var(--bg-card); }
        .pm-icon-btn svg { width: 20px; height: 20px; stroke: var(--text); fill: none; }
        
        #pm-icon-moon { display: none; }
        :root[data-theme="light"] #pm-icon-moon { display: block; }
        :root[data-theme="light"] #pm-icon-sun { display: none; }
        .pm-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 18px;
            height: 18px;
            padding: 0 4px;
            border-radius: 999px;
            background: var(--accent);
            color: var(--btn-text);
            font-size: .65rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .pm-badge-bounce {
            animation: badgeBounce .35s cubic-bezier(.36,.07,.19,.97);
        }
        @keyframes badgeBounce {
            0%,100% { transform: scale(1); }
            40%      { transform: scale(1.4); }
            60%      { transform: scale(.9); }
        }

        /* ─── Search ────────────────────────────────────────────── */
        .pm-search-wrap {
            position: sticky;
            top: var(--header-h);
            z-index: 90;
            background: var(--bg-header);
            backdrop-filter: blur(10px);
            padding: 10px 16px 8px;
        }
        .pm-search-inner {
            display: flex;
            align-items: center;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            overflow: hidden;
            transition: border-color var(--transition);
        }
        .pm-search-inner:focus-within { border-color: var(--accent); }
        .pm-search-inner svg {
            width: 16px;
            height: 16px;
            margin-left: 12px;
            flex-shrink: 0;
            stroke: var(--text-muted);
            fill: none;
        }
        #pm-search-input {
            flex: 1;
            background: none;
            border: none;
            outline: none;
            padding: 10px 12px;
            color: var(--text);
            font-size: .875rem;
            font-family: inherit;
        }
        #pm-search-input::placeholder { color: var(--text-faint); }

        /* ─── Category Nav ──────────────────────────────────────── */
        .pm-cat-nav {
            position: sticky;
            top: calc(var(--header-h) + 52px);
            z-index: 80;
            background: var(--bg-header);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            overflow-x: auto;
            white-space: nowrap;
            scrollbar-width: none;
            -ms-overflow-style: none;
            height: var(--cat-h);
            display: flex;
            align-items: center;
            padding: 0 24px;
            gap: 6px;
        }
        .pm-cat-nav::-webkit-scrollbar { display: none; }
        .pm-cat-tab {
            display: inline-flex;
            align-items: center;
            height: 32px;
            padding: 0 14px;
            border-radius: 999px;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            font-size: .8rem;
            font-weight: 500;
            white-space: nowrap;
            transition: background var(--transition), border-color var(--transition), color var(--transition);
            flex-shrink: 0;
        }
        .pm-cat-tab:hover { background: var(--bg-card); border-color: var(--accent); }
        .pm-cat-tab.active {
            background: var(--accent);
            border-color: var(--accent);
            color: var(--btn-text);
            font-weight: 700;
        }

        /* ─── Main Content ──────────────────────────────────────── */
        .pm-main { padding: 16px; padding-bottom: 100px; margin: 0 auto; }

        /* ─── Category Section ──────────────────────────────────── */
        .pm-category-section { margin-bottom: 28px; }
        .pm-category-title {
            font-size: .9rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--text);
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border);
        }
        .pm-items-grid {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding: 10px 0;
            scroll-behavior: smooth;
        }

        .pm-items-grid::-webkit-scrollbar {
            display: none; /* cleaner mobile look */
        }

        .pm-item-card {
            display: flex;
            flex-direction: column;   /* 🔥 KEY CHANGE */
            align-items: center;
            justify-content: space-between;

            width: 200px;
            min-width: 200px;         /* 🔥 ensures horizontal scroll */
            flex-shrink: 0;

            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;

            transition: transform var(--transition), box-shadow var(--transition);
        }

        .pm-item-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
        }

        .pm-item-img-wrap {
            width: 100%;
            height: 100px;
            background: var(--bg-surface);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pm-item-info {
            padding: 0 10px;
            text-align: center;
            width: 100%;
        }

        .pm-item-name {
            font-size: .85rem;
            font-weight: 600;
            line-height: 1.3;
            margin-bottom: 4px;

            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .pm-item-desc {
            display: none; 
        }

        .pm-item-price {
            font-size: .95rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 6px;
        }

        .pm-item-img-wrap img {
            width: auto;
            height: 100%;
            margin: 0 auto;
        }

        .pm-item-action {
            margin: 10px 0;
            width: 100%;
            display: flex;
            justify-content: center;
        }

        /* ─── Out of Stock Styling ──────────────────────────────── */
        .pm-out-of-stock {
            filter: grayscale(0.6);
            opacity: 0.8;
            pointer-events: none;
        }
        .pm-out-of-stock .pm-add-btn {
            background: #94a3b8;
            box-shadow: none;
            cursor: not-allowed;
        }
        .pm-out-of-stock .pm-item-price {
            color: var(--text-muted);
        }
        .pm-item-badge-oos {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ef4444;
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 999px;
            text-transform: uppercase;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
        }
        .pm-item-card { position: relative; }

        .pm-add-btn {
            width: 34px;
            height: 34px;
            border-radius: 50%;   /* 🔥 round button (more modern) */

            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: var(--btn-text);

            font-size: 1.2rem;
            font-weight: 700;

            display: flex;
            align-items: center;
            justify-content: center;

            transition: transform var(--transition), box-shadow var(--transition);
            box-shadow: 0 2px 8px rgba(56,189,248,.3);
        }

        .pm-add-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 16px rgba(56,189,248,.5);
        }

        .pm-add-btn:active {
            transform: scale(.95);
        }

        .pm-items-grid {
            scroll-snap-type: x mandatory;
        }

        .pm-item-card {
            scroll-snap-align: start;
        }

        /* ─── Qty Box ───────────────────────────────────────────── */
        .pm-qty-box {
            display: none;
            align-items: center;
            gap: 0;
            background: var(--bg-surface);
            border: 1px solid var(--accent);
            border-radius: var(--radius-sm);
            overflow: hidden;
        }
        .pm-qty-btn {
            width: 30px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 700;
            color: var(--accent);
            background: none;
            transition: background var(--transition);
        }
        .pm-qty-btn:hover { background: var(--accent-alpha); }
        .pm-qty-value {
            width: 28px;
            text-align: center;
            font-size: .875rem;
            font-weight: 700;
        }

        /* ─── Floating Cart Bar ─────────────────────────────────── */
        #pm-floating-bar {
            position: fixed;
            bottom: -80px;
            color: var(--btn-text);
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 32px);
            max-width: 600px;
            background: var(--accent);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            cursor: pointer;
            transition: bottom .35s cubic-bezier(.4,0,.2,1);
            z-index: 200;
        }
        #pm-floating-bar.pm-visible { bottom: 20px; }
        .pm-bar-left { display: flex; align-items: center; gap: 10px; }
        .pm-bar-icon { font-size: 1.2rem; }
        .pm-bar-count { font-size: .9rem; font-weight: 600; }
        .pm-bar-total { font-size: 1rem; font-weight: 700; }
        .pm-bar-cta {
            font-size: .85rem;
            font-weight: 700;
            opacity: .9;
        }

        /* ─── Cart Modal ────────────────────────────────────────── */
        #pm-cart-modal {
            position: fixed;
            inset: 0;
            z-index: 300;
            background: rgba(0,0,0,.7);
            display: flex;
            align-items: flex-end;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity var(--transition);
        }
        #pm-cart-modal.pm-open {
            opacity: 1;
            pointer-events: all;
        }
        .pm-cart-sheet {
            width: 100%;
            max-width: 640px;
            max-height: 85vh;
            background: var(--bg-surface);
            border-radius: var(--radius) var(--radius) 0 0;
            display: flex;
            flex-direction: column;
            transform: translateY(60px);
            transition: transform .3s cubic-bezier(.4,0,.2,1);
            overflow: hidden;
        }
        #pm-cart-modal.pm-open .pm-cart-sheet { transform: translateY(0); }
        .pm-cart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 20px 14px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .pm-cart-header h2 { font-size: 1.05rem; font-weight: 700; }
        #pm-cart-close {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            background: var(--bg-card);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: background var(--transition);
        }
        #pm-cart-close:hover { background: var(--border); }
        #pm-cart-list {
            flex: 1;
            overflow-y: auto;
            padding: 12px 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .pm-empty-cart {
            text-align: center;
            color: var(--text-muted);
            padding: 40px 0;
            font-size: .9rem;
        }

        /* ─── Cart Items ────────────────────────────────────────── */
        .pm-cart-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 10px 12px;
        }
        .pm-cart-item-info {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            min-width: 0;
        }
        .pm-cart-img {
            width: 44px;
            height: 44px;
            border-radius: 6px;
            object-fit: cover;
            flex-shrink: 0;
        }
        .pm-cart-img-placeholder {
            width: 44px;
            height: 44px;
            border-radius: 6px;
            background: var(--bg-surface);
            flex-shrink: 0;
        }
        .pm-cart-item-text { min-width: 0; }
        .pm-cart-name {
            display: block;
            font-size: .85rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .pm-cart-unit-price {
            display: block;
            font-size: .75rem;
            color: var(--text-muted);
        }
        .pm-cart-item-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        .pm-cart-subtotal {
            font-size: .85rem;
            font-weight: 700;
            color: var(--accent);
            min-width: 52px;
            text-align: right;
        }
        .pm-remove-btn {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--bg-surface);
            font-size: .7rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background var(--transition), color var(--transition);
        }
        .pm-remove-btn:hover { background: #ef4444; color: #fff; }

        /* ─── Cart Footer ───────────────────────────────────────── */
        .pm-cart-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--border);
            flex-shrink: 0;
        }
        .pm-cart-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }
        .pm-cart-total-label { font-size: .875rem; color: var(--text-muted); }
        #pm-cart-total { font-size: 1.15rem; font-weight: 700; color: var(--accent); }
        #pm-whatsapp-btn {
            width: 100%;
            padding: 15px;
            border-radius: var(--radius);
            background: linear-gradient(135deg, var(--accent-wa), var(--accent-wa-d));
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: opacity var(--transition), transform var(--transition);
            box-shadow: 0 4px 20px rgba(37,211,102,.3);
        }
        #pm-whatsapp-btn:hover { opacity: .92; transform: translateY(-1px); }
        #pm-whatsapp-btn:active { transform: scale(.98); }
        #pm-whatsapp-btn svg { width: 22px; height: 22px; fill: #fff; flex-shrink: 0; }

        /* ─── No Categories ─────────────────────────────────────── */
        .pm-empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        .pm-empty-state-icon { font-size: 3rem; margin-bottom: 12px; }
        .pm-empty-state h3 { font-size: 1.1rem; margin-bottom: 6px; }
        .pm-empty-state p { font-size: .875rem; }

        /* ─── Scrollbar ─────────────────────────────────────────── */
        #pm-cart-list::-webkit-scrollbar { width: 4px; }
        #pm-cart-list::-webkit-scrollbar-track { background: transparent; }
        #pm-cart-list::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

        /* ─── Variant Modal ─────────────────────────────────────── */
        #pm-variant-modal {
            position: fixed;
            inset: 0;
            z-index: 400;
            background: rgba(0,0,0,.72);
            display: flex;
            align-items: flex-end;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity var(--transition);
        }
        #pm-variant-modal.pm-open {
            opacity: 1;
            pointer-events: all;
        }
        .pm-variant-sheet {
            width: 100%;
            background: var(--bg-surface);
            border-radius: var(--radius) var(--radius) 0 0;
            padding: 0 0 24px;
            transform: translateY(60px);
            transition: transform .3s cubic-bezier(.4,0,.2,1);
            overflow: hidden;
        }
        #pm-variant-modal.pm-open .pm-variant-sheet { transform: translateY(0); }
        .pm-variant-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 20px 14px;
            border-bottom: 1px solid var(--border);
        }
        .pm-variant-header h3 { font-size: 1rem; font-weight: 700; }
        #pm-variant-close {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            background: var(--bg-card);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: background var(--transition);
        }
        #pm-variant-close:hover { background: var(--border); }
        .pm-variant-subtitle {
            font-size: .8rem;
            color: var(--text-muted);
            padding: 12px 20px 0;
        }
        .pm-variant-options {
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 14px 20px;
            max-height: 50vh;
            overflow-y: auto;
        }

        /* ─── Variant Groups (labelled chip rows) ─────────────────────────────── */
        .pm-variant-group { display: flex; flex-direction: column; gap: 8px; }
        .pm-variant-group-label {
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--text-muted);
        }
        .pm-variant-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .pm-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 14px;
            border-radius: 999px;
            background: var(--bg-card);
            border: 1.5px solid var(--border);
            font-size: .85rem;
            font-weight: 500;
            color: var(--text);
            cursor: pointer;
            transition: border-color var(--transition), background var(--transition), color var(--transition), transform var(--transition);
            white-space: nowrap;
        }
        .pm-chip:hover { border-color: var(--accent); }
        .pm-chip.selected {
            background: var(--accent);
            border-color: var(--accent);
            color: var(--btn-text);
            font-weight: 700;
        }
        .pm-chip-price {
            font-size: .75rem;
            opacity: .8;
        }
        .pm-variant-footer {
            padding: 0 20px;
        }
        #pm-variant-add-btn {
            width: 100%;
            padding: 14px;
            border-radius: var(--radius);
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: var(--btn-text);
            font-size: 1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: opacity var(--transition), transform var(--transition);
            box-shadow: 0 4px 16px rgba(56,189,248,.3);
        }
        #pm-variant-add-btn:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); }
        #pm-variant-add-btn:active:not(:disabled) { transform: scale(.98); }
        #pm-variant-add-btn:disabled { opacity: .4; cursor: not-allowed; }

        /* ─── Skeleton Loader ───────────────────────────────────────── */
        @keyframes skeletonPulse {
            0%   { opacity: 1; }
            50%  { opacity: .45; }
            100% { opacity: 1; }
        }
        .pm-skeleton-card {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 10px 12px;
            animation: skeletonPulse 1.4s ease-in-out infinite;
        }
        .pm-skeleton-img {
            width: 64px;
            height: 64px;
            border-radius: var(--radius-sm);
            background: var(--bg-surface);
            flex-shrink: 0;
        }
        .pm-skeleton-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .pm-skeleton-line {
            height: 12px;
            border-radius: 6px;
            background: var(--bg-surface);
        }
        .pm-skeleton-line.wide  { width: 70%; }
        .pm-skeleton-line.mid   { width: 45%; }
        .pm-skeleton-line.short { width: 30%; }
        .pm-skeleton-action {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            background: var(--bg-surface);
            flex-shrink: 0;
        }

        /* ─── Load-more spinner ─────────────────────────────────────── */
        .pm-load-more-spinner {
            display: none;
            justify-content: center;
            padding: 16px 0 8px;
            width: 100%;
        }
        .pm-load-more-spinner.active { display: flex; }
        .pm-spinner {
            width: 28px;
            height: 28px;
            border: 3px solid var(--border);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: pmSpin .7s linear infinite;
        }
        @keyframes pmSpin { to { transform: rotate(360deg); } }

        /* ─── End of list notice ────────────────────────────────────── */
        #pm-end-notice {
            display: none;
            text-align: center;
            font-size: .78rem;
            color: var(--text-faint);
            padding: 14px 0 6px;
            letter-spacing: .03em;
        }
        #pm-end-notice.active { display: block; }
        /* ─── Checkout Form Styles ────────────────────────── */
        .pm-checkout-form {
            padding: 16px;
            border-top: 1px solid var(--border);
            background: var(--bg-surface);
        }
        .pm-form-group {
            margin-bottom: 12px;
        }
        .pm-form-group label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 6px;
            color: var(--text-muted);
        }
        .pm-form-group input, .pm-form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
            transition: var(--transition);
        }
        .pm-form-group input:focus, .pm-form-group textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-alpha);
        }
        .pm-cart-sheet {
            max-height: 95vh !important;
        }

        /* ─── Image Slideshow ──────────────────────────────────── */
        .pm-slideshow {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        .pm-slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity .5s ease;
        }
        .pm-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        /* Dots indicator */
        .pm-slide-dots {
            position: absolute;
            bottom: 4px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 4px;
            z-index: 2;
        }
        .pm-slide-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            transition: background .3s;
        }
        .pm-slide-dot.active {
            background: #fff;
        }
    </style>
</head>
<body>

{{-- ─── Sticky Header ────────────────────────────────── --}}
<header class="pm-sticky-header">
    <div class="pm-header-brand">
        <h1>{{ $warehouse->name }}</h1>
        <div style="display:flex;align-items:center;margin-top:5px;font-size:13px">
            <svg style="width: 18px; height: 18px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"></path>
            </svg>
            <span style="margin-left:5px">{{ $warehouse->address }}</span>
        </div>
    </div>
    <div class="pm-header-actions">
        <button id="pm-theme-toggle" class="pm-icon-btn" aria-label="Toggle theme">
            <svg id="pm-icon-sun" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-sun-high"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M14.828 14.828a4 4 0 1 0 -5.656 -5.656a4 4 0 0 0 5.656 5.656" /><path d="M6.343 17.657l-1.414 1.414" /><path d="M6.343 6.343l-1.414 -1.414" /><path d="M17.657 6.343l1.414 -1.414" /><path d="M17.657 17.657l1.414 1.414" /><path d="M4 12h-2" /><path d="M12 4v-2" /><path d="M20 12h2" /><path d="M12 20v2" /></svg>

            <svg id="pm-icon-moon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-moon"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454l0 .008" /></svg>
        </button>
        <button id="pm-cart-icon-btn" class="pm-icon-btn" aria-label="Open cart">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-cart"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M4 19a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M15 19a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 17h-11v-14h-2" /><path d="M6 5l14 1l-1 7h-13" /></svg>
            <span id="pm-header-cart-count" class="pm-badge">0</span>
        </button>
    </div>
</header>

{{-- ─── Search ───────────────────────────────────────── --}}
<div class="pm-search-wrap">
    <div class="pm-search-inner">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input type="search" id="pm-search-input" placeholder="Search items..." autocomplete="off" autocorrect="off" spellcheck="false">
    </div>
</div>

{{-- ─── Category Nav ─────────────────────────────────── --}}
@if($categories->count())
<nav class="pm-cat-nav" aria-label="Product categories">
    @foreach($categories as $cat)
        <button class="pm-cat-tab {{ $loop->first ? 'active' : '' }}" data-id="{{ $cat->id }}">{{ $cat->name }}</button>
    @endforeach
</nav>
@endif

{{-- ─── Main Menu ────────────────────────────────────── --}}
<main class="pm-main">

    @if($categories->count())
        @foreach (get_active_categories() as $category)
        <section class="pm-category-section" data-category-id="{{ $category->id }}">
            <div class="pm-category-title">
                {{ $category->name }}
            </div>
            <div class="pm-items-grid" id="pm-items-{{ $category->id }}"></div>
            <div class="pm-load-more-spinner" id="pm-spinner-{{ $category->id }}"><div class="pm-spinner"></div></div>
        </section>
        @endforeach
    @else
        <div class="pm-empty-state">
            <div class="pm-empty-state-icon">🏪</div>
            <h3>No items available</h3>
            <p>Check back later for our selection.</p>
        </div>
    @endif

</main>

{{-- ─── Floating Cart Bar ────────────────────────────── --}}
<div id="pm-floating-bar" role="button" aria-label="View cart" tabindex="0">
    <div class="pm-bar-left">
        <span class="pm-bar-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-cart-plus"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M4 19a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M12.5 17h-6.5v-14h-2" /><path d="M6 5l14 1l-.86 6.017m-2.64 .983h-10.5" /><path d="M16 19h6" /><path d="M19 16v6" /></svg></span>
        <span id="pm-bar-count" class="pm-bar-count">0 items</span>
    </div>
    <span id="pm-bar-total" class="pm-bar-total">{{ format_currency(0)}}</span>
    <span class="pm-bar-cta">View Cart →</span>
</div>

{{-- ─── Cart Modal (Bottom Sheet) ───────────────────── --}}
<div id="pm-cart-modal" role="dialog" aria-modal="true" aria-labelledby="pm-cart-heading">
    <div class="pm-cart-sheet">
        <div class="pm-cart-header">
            <h2 id="pm-cart-heading">🛒 Your Cart</h2>
            <button id="pm-cart-close" aria-label="Close cart">✕</button>
        </div>
        <div id="pm-cart-list"></div>

        <div class="pm-checkout-form" id="pm-checkout-form">
            <div class="pm-form-group">
                <label for="pm-name">Full Name *</label>
                <input type="text" id="pm-name" placeholder="Enter your name" required>
            </div>
            <div class="pm-form-group">
                <label for="pm-phone">Phone Number *</label>
                <input type="tel" id="pm-phone" placeholder="Enter phone number" required>
            </div>
            <div class="pm-form-group">
                <label for="pm-address">Address / Note</label>
                <textarea id="pm-address" rows="2" placeholder="Delivery address or special requests"></textarea>
            </div>
        </div>

        <div class="pm-cart-footer">
            <div class="pm-cart-total-row">
                <span class="pm-cart-total-label">Total</span>
                <span id="pm-cart-total">{{ format_currency(0)}}</span>
            </div>
            <button id="pm-whatsapp-btn" aria-label="Send order via WhatsApp">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.940 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.480-8.413z"/>
                </svg>
                Send Order via WhatsApp
            </button>
        </div>
    </div>
</div>

{{-- ─── Variant Selection Modal ─────────────────────── --}}
<div id="pm-variant-modal" role="dialog" aria-modal="true" aria-labelledby="pm-variant-heading">
    <div class="pm-variant-sheet">
        <div class="pm-variant-header">
            <h3 id="pm-variant-heading">Choose an option</h3>
            <button id="pm-variant-close" aria-label="Close">✕</button>
        </div>
        <p class="pm-variant-subtitle">Select a variant to add to your cart</p>
        <div class="pm-variant-options" id="pm-variant-options"></div>
        <div class="pm-variant-footer">
            <button id="pm-variant-add-btn" disabled>Add to Cart</button>
        </div>
    </div>
</div>

{{-- ─── JS Config ────────────────────────────────────── --}}
<script>
window.MENU_CONFIG = {
    baseUrl: "{{ url('/') }}",
    warehouse_name:  @json($warehouse->name),
    whatsapp_number: @json(preg_replace('/\D/', '', $warehouse->phone ?? '')),
    table_name:      @json($table ? $table->name : null),
    slug:            @json($slug),
    warehouse_id:    @json($warehouse->id),
    currency_symbol: @json(gen_setting()->currency ? \App\Models\Currency::find(gen_setting()->currency)->symbol : '$'),
    place_order_url: @json(route('public.menu.placeOrder'))
};

window.appConfig = {
    currency: "{{ config('currency') }}",
    currency_position: "{{ config('currency_position') }}",
    decimal: {{ config('decimal') ?? 2 }}
};

function formatCurrency(amount) {
    let formatted = parseFloat(amount).toFixed(window.appConfig.decimal);

    if (window.appConfig.currency_position === 'prefix') {
        return window.appConfig.currency + ' ' + formatted;
    }

    return formatted + ' ' + window.appConfig.currency;
}
</script>
<script>
    document.getElementById('pm-theme-toggle').addEventListener('click', () => {
        const root = document.documentElement;
        const currentTheme = root.getAttribute('data-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        
        if (newTheme === 'light') {
            root.setAttribute('data-theme', 'light');
            document.getElementById('meta-theme-color').setAttribute('content', '#f8fafc');
            localStorage.setItem('pm-theme', 'light');
        } else {
            root.removeAttribute('data-theme');
            document.getElementById('meta-theme-color').setAttribute('content', '#0f172a');
            localStorage.setItem('pm-theme', 'dark');
        }
    });
</script>
@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
<script src="{{ asset($asset_prefix . 'js/public-menu.js') }}?v={{ time() }}"></script>

</body>
</html>
