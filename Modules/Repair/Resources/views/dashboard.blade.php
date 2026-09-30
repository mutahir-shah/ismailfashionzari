@extends('backend.layout.main')

@push('css')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
/* ── Design tokens ─────────────────────────────────────────────── */
:root {
    --p:     #7c5cc4;
    --p-dk:  #5e43a0;
    --p-md:  #9b7dd4;
    --p-lt:  #f0ecfb;
    --p-xlt: #f8f6fd;
    --green: #22c55e;
    --red:   #ef4444;
    --amber: #f59e0b;
    --blue:  #3b82f6;
    --slate: #64748b;
    --ink:   #1e1b2e;
    --card:  #ffffff;
    --bg:    #f5f3fb;
    --border:#e8e2f5;
    --radius:12px;
    --shadow:0 2px 12px rgba(124,92,196,.10);
    --shadow-md:0 4px 24px rgba(124,92,196,.16);
    font-family:'DM Sans',sans-serif;
}

/* ── Page bg ───────────────────────────────────────────────────── */
.section { background:var(--bg); min-height:100vh; }
.container-fluid { padding:0 20px 32px; }

/* ── Page header ───────────────────────────────────────────────── */
.dash-header {
    background: linear-gradient(135deg, var(--p) 0%, var(--p-dk) 60%, #3b1f8c 100%);
    border-radius: var(--radius);
    padding: 24px 28px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    color: #fff;
}
.dash-header::before {
    content:'';
    position:absolute; top:-40px; right:-40px;
    width:220px; height:220px;
    border-radius:50%;
    background: rgba(255,255,255,.06);
}
.dash-header::after {
    content:'';
    position:absolute; bottom:-60px; right:100px;
    width:140px; height:140px;
    border-radius:50%;
    background: rgba(255,255,255,.04);
}
.dash-header h4 { font-weight:700; font-size:1.35rem; margin:0 0 4px; letter-spacing:-.3px; }
.dash-header p  { margin:0; opacity:.8; font-size:.88rem; }
.dash-header .date-filter { position:relative; z-index:2; }

/* ── Date filter form ──────────────────────────────────────────── */
.date-filter .form-control {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.25);
    color: #fff;
    border-radius: 8px;
    font-size: .85rem;
    height: 34px;
}
.date-filter .form-control::placeholder { color:rgba(255,255,255,.6); }
.date-filter .btn-filter {
    background: rgba(255,255,255,.2);
    border: 1px solid rgba(255,255,255,.3);
    color: #fff;
    border-radius: 8px;
    height: 34px;
    padding: 0 16px;
    font-size: .85rem;
    font-weight: 600;
    transition: .2s;
}
.date-filter .btn-filter:hover { background: rgba(255,255,255,.35); }

/* ── Alert pills ───────────────────────────────────────────────── */
.alert-pills { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
.alert-pill {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 14px;
    border-radius: 999px;
    font-size: .82rem; font-weight: 600;
    cursor: pointer;
    transition: .15s;
    text-decoration: none;
}
.alert-pill:hover { transform:translateY(-1px); box-shadow:var(--shadow-md); }
.alert-pill.red   { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
.alert-pill.amber { background:#fffbeb; color:#d97706; border:1px solid #fde68a; }
.alert-pill.blue  { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
.alert-pill.green { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
.alert-pill .dot  { width:7px; height:7px; border-radius:50%; background:currentColor; }

/* ── KPI grid ──────────────────────────────────────────────────── */
.kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:20px; }
@media(max-width:1100px){ .kpi-grid{ grid-template-columns:repeat(2,1fr); } }

.kpi-card {
    background: var(--card);
    border-radius: var(--radius);
    padding: 18px 20px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    position: relative;
    overflow: hidden;
    transition: .2s;
}
.kpi-card:hover { transform:translateY(-2px); box-shadow:var(--shadow-md); }
.kpi-card::after {
    content:'';
    position:absolute; top:0; left:0;
    width:4px; height:100%;
    background: var(--accent, var(--p));
    border-radius:0;
}
.kpi-card .kpi-icon {
    width:40px; height:40px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    font-size:1.1rem; margin-bottom:12px;
    background: var(--icon-bg, var(--p-lt));
    color: var(--accent, var(--p));
}
.kpi-card .kpi-val {
    font-size:1.75rem; font-weight:700; color:var(--ink);
    line-height:1; font-family:'DM Mono',monospace;
    letter-spacing:-1px; margin-bottom:4px;
}
.kpi-card .kpi-label {
    font-size:.78rem; color:var(--slate); font-weight:500;
    text-transform:uppercase; letter-spacing:.5px;
}
.kpi-card .kpi-sub {
    font-size:.78rem; color:var(--slate); margin-top:6px;
}
.kpi-card .kpi-trend {
    position:absolute; top:16px; right:16px;
    font-size:.75rem; font-weight:600; padding:2px 8px;
    border-radius:999px;
}
.kpi-card .kpi-trend.up   { background:#f0fdf4; color:#16a34a; }
.kpi-card .kpi-trend.down { background:#fef2f2; color:#dc2626; }

/* Revenue kpi special */
.kpi-revenue {
    grid-column: span 2;
    background: linear-gradient(135deg, var(--p) 0%, var(--p-dk) 100%);
    color: #fff;
    border-color: transparent;
}
.kpi-revenue::after { background: rgba(255,255,255,.3); }
.kpi-revenue .kpi-val   { color:#fff; font-size:2rem; }
.kpi-revenue .kpi-label { color:rgba(255,255,255,.75); }
.kpi-revenue .kpi-sub   { color:rgba(255,255,255,.7); }
.kpi-revenue .kpi-icon  { background:rgba(255,255,255,.15); color:#fff; }
.kpi-revenue .rev-split { display:flex; gap:20px; margin-top:12px; }
.kpi-revenue .rev-item  { flex:1; padding:10px; background:rgba(255,255,255,.1); border-radius:8px; }
.kpi-revenue .rev-item .rv { font-size:1.1rem; font-weight:700; font-family:'DM Mono',monospace; }
.kpi-revenue .rev-item .rl { font-size:.72rem; opacity:.75; text-transform:uppercase; letter-spacing:.5px; margin-top:2px; }
.kpi-revenue .rv-green { color:#86efac; }
.kpi-revenue .rv-red   { color:#fca5a5; }

/* ── Two-column section ────────────────────────────────────────── */
.row-panels { display:grid; grid-template-columns:1fr 340px; gap:16px; margin-bottom:20px; }
@media(max-width:1024px){ .row-panels{ grid-template-columns:1fr; } }

/* ── Panel card ────────────────────────────────────────────────── */
.panel {
    background: var(--card);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    overflow: hidden;
}
.panel-head {
    padding: 14px 18px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items:center; justify-content:space-between;
}
.panel-head h6 {
    margin:0; font-weight:700; font-size:.9rem; color:var(--ink);
    display:flex; align-items:center; gap:8px;
}
.panel-head h6 .ph-icon {
    width:28px; height:28px; border-radius:7px;
    background:var(--p-lt); color:var(--p);
    display:flex; align-items:center; justify-content:center;
    font-size:.82rem;
}
.panel-head .panel-link {
    font-size:.78rem; color:var(--p); font-weight:600;
    text-decoration:none;
}
.panel-head .panel-link:hover { color:var(--p-dk); }
.panel-body { padding:16px; }

/* ── Chart wrappers ────────────────────────────────────────────── */
.chart-wrap { position:relative; height:220px; }
.chart-wrap canvas { max-height:220px; }

/* ── Status donut legend ───────────────────────────────────────── */
.donut-wrap { position:relative; height:180px; width:180px; margin:0 auto 12px; }
.status-legend { display:flex; flex-direction:column; gap:6px; }
.legend-item { display:flex; align-items:center; justify-content:space-between; font-size:.82rem; }
.legend-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; margin-right:7px; }
.legend-label { flex:1; color:#555; }
.legend-val { font-weight:700; color:var(--ink); font-family:'DM Mono',monospace; }

/* ── Recent jobs table ─────────────────────────────────────────── */
.rj-table { width:100%; border-collapse:collapse; }
.rj-table th { font-size:.75rem; text-transform:uppercase; letter-spacing:.5px;
               color:var(--slate); font-weight:600; padding:8px 12px;
               background:var(--p-xlt); border-bottom:1px solid var(--border); }
.rj-table td { padding:10px 12px; font-size:.85rem; border-bottom:1px solid #f5f3fb; color:#333; vertical-align:middle; }
.rj-table tr:last-child td { border-bottom:none; }
.rj-table tr:hover td { background:#faf8ff; }
.rj-table a { color:var(--p); font-weight:600; text-decoration:none; }
.rj-table a:hover { color:var(--p-dk); }

/* ── Type badge ────────────────────────────────────────────────── */
.type-pill { display:inline-flex; align-items:center; gap:4px;
             padding:2px 8px; border-radius:999px; font-size:.72rem; font-weight:600; }
.type-pill.device  { background:#eff6ff; color:#1d4ed8; }
.type-pill.vehicle { background:#fffbeb; color:#b45309; }

/* ── Status mini badge ─────────────────────────────────────────── */
.status-dot { display:inline-flex; align-items:center; gap:5px; font-size:.78rem; font-weight:600; }
.status-dot::before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; display:inline-block; }
.s-pending     { color:#d97706; }
.s-diagnosed   { color:#0891b2; }
.s-in_progress { color:#2563eb; }
.s-completed   { color:#16a34a; }
.s-delivered   { color:#7c3aed; }
.s-cancelled   { color:#dc2626; }

/* ── Priority dot ──────────────────────────────────────────────── */
.pri { display:inline-block; width:8px; height:8px; border-radius:50%; }
.pri-low    { background:var(--green); }
.pri-medium { background:var(--amber); }
.pri-high   { background:var(--red); box-shadow:0 0 0 3px rgba(239,68,68,.2); }

/* ── Top techs ─────────────────────────────────────────────────── */
.tech-row { display:flex; align-items:center; gap:10px; padding:10px 0;
            border-bottom:1px solid var(--border); }
.tech-row:last-child { border-bottom:none; }
.tech-avatar {
    width:36px; height:36px; border-radius:50%;
    background: linear-gradient(135deg, var(--p), var(--p-dk));
    color:#fff; display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:.88rem; flex-shrink:0;
}
.tech-name  { font-size:.88rem; font-weight:600; color:var(--ink); }
.tech-meta  { font-size:.75rem; color:var(--slate); }
.tech-bar-wrap { flex:1; height:5px; background:#eee; border-radius:999px; overflow:hidden; }
.tech-bar { height:100%; background:linear-gradient(90deg,var(--p),var(--p-md)); border-radius:999px; transition:.6s; }
.tech-count { font-size:.85rem; font-weight:700; color:var(--p); font-family:'DM Mono',monospace; }

/* ── Payment methods ───────────────────────────────────────────── */
.pay-method-row { display:flex; align-items:center; gap:10px; padding:8px 0;
                  border-bottom:1px solid var(--border); font-size:.85rem; }
.pay-method-row:last-child { border-bottom:none; }
.pm-icon { width:30px; height:30px; border-radius:7px; background:var(--p-lt);
           color:var(--p); display:flex; align-items:center; justify-content:center;
           font-size:.78rem; font-weight:700; flex-shrink:0; }
.pm-label { flex:1; font-weight:500; color:#444; }
.pm-count { font-size:.75rem; color:var(--slate); }
.pm-amount { font-weight:700; color:var(--ink); font-family:'DM Mono',monospace; font-size:.88rem; }

/* ── Animated count ────────────────────────────────────────────── */
.kpi-val[data-count] { transition:.3s; }

/* ── Pulse on high priority ────────────────────────────────────── */
@keyframes pulse-ring {
    0%   { box-shadow:0 0 0 0 rgba(239,68,68,.4); }
    70%  { box-shadow:0 0 0 8px rgba(239,68,68,0); }
    100% { box-shadow:0 0 0 0 rgba(239,68,68,0); }
}
.pulse { animation:pulse-ring 2s infinite; }

/* ── Fade-in ───────────────────────────────────────────────────── */
@keyframes fadeUp {
    from { opacity:0; transform:translateY(14px); }
    to   { opacity:1; transform:translateY(0); }
}
.fade-up { animation:fadeUp .4s ease both; }
.kpi-card:nth-child(1){ animation-delay:.05s; }
.kpi-card:nth-child(2){ animation-delay:.10s; }
.kpi-card:nth-child(3){ animation-delay:.15s; }
.kpi-card:nth-child(4){ animation-delay:.20s; }
.kpi-card:nth-child(5){ animation-delay:.25s; }
.kpi-card:nth-child(6){ animation-delay:.30s; }
</style>
@endpush

@section('content')
<section class="section">
<div class="container-fluid pt-3">

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- HEADER                                                     --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="dash-header fade-up">
        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:16px;">
            <div>
                <h4><i class="ti ti-wrench mr-2" style="opacity:.8"></i>Repair &amp; Service Dashboard</h4>
                <p>Overview of all service jobs, revenue and technician performance</p>
            </div>
            {{-- Date filter --}}
            <form method="GET" action="{{ route('repair.dashboard') }}" class="date-filter d-flex align-items-center" style="gap:8px;">
                <input type="text" name="starting_date" id="sd" class="form-control"
                    value="{{ $starting_date }}" placeholder="Start date" style="width:120px;" readonly/>
                <span style="color:rgba(255,255,255,.6); font-size:.85rem;">→</span>
                <input type="text" name="ending_date" id="ed" class="form-control"
                    value="{{ $ending_date }}" placeholder="End date" style="width:120px;" readonly/>
                <button type="submit" class="btn-filter">
                    <i class="ti ti-filter mr-1"></i>Filter
                </button>
                <a href="{{ route('repair.dashboard') }}" class="btn-filter" style="text-decoration:none; padding:0 12px; display:flex; align-items:center; height:34px;">
                    <i class="ti ti-refresh"></i>
                </a>
            </form>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- ALERT PILLS                                                --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    @if($overdue > 0 || $high_priority > 0 || $today_jobs > 0)
    <div class="alert-pills fade-up">
        @if($overdue > 0)
        <a href="{{ route('repair.service.index') }}" class="alert-pill red">
            <span class="dot"></span>
            <span>{{ $overdue }} Overdue Job{{ $overdue > 1 ? 's' : '' }}</span>
        </a>
        @endif
        @if($high_priority > 0)
        <a href="{{ route('repair.service.index') }}" class="alert-pill amber">
            <span class="dot"></span>
            <span>{{ $high_priority }} High Priority</span>
        </a>
        @endif
        @if($today_jobs > 0)
        <span class="alert-pill blue">
            <span class="dot"></span>
            <span>{{ $today_jobs }} New Today</span>
        </span>
        @endif
        @if($total_due > 0)
        <span class="alert-pill red" style="cursor:default;">
            <span class="dot"></span>
            <span>{{ config('currency') }} {{ number_format($total_due, config('decimal')) }} Outstanding</span>
        </span>
        @endif
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- KPI CARDS                                                  --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="kpi-grid">

        {{-- Revenue span-2 --}}
        <div class="kpi-card kpi-revenue fade-up">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <div class="kpi-icon"><i class="ti ti-chart-bar"></i></div>
                    <div class="kpi-label">Total Revenue</div>
                    <div class="kpi-val">{{ config('currency') }} {{ number_format($total_revenue, config('decimal')) }}</div>
                </div>
                <div style="text-align:right;">
                    <div class="kpi-label">{{ $total_jobs }} Jobs</div>
                    <div style="font-size:.82rem; opacity:.7; margin-top:4px;">
                        Avg {{ $total_jobs > 0 ? number_format($total_revenue / $total_jobs, config('decimal')) : '0.00' }} / job
                    </div>
                </div>
            </div>
            <div class="rev-split">
                <div class="rev-item">
                    <div class="rv rv-green">{{ config('currency') }} {{ number_format($total_collected, config('decimal')) }}</div>
                    <div class="rl">Collected</div>
                </div>
                <div class="rev-item">
                    <div class="rv rv-red">{{ config('currency') }} {{ number_format($total_due, config('decimal')) }}</div>
                    <div class="rl">Outstanding</div>
                </div>
                <div class="rev-item">
                    <div class="rv">{{ $total_revenue > 0 ? number_format(($total_collected / $total_revenue) * 100, 1) : '0' }}%</div>
                    <div class="rl">Collection Rate</div>
                </div>
            </div>
        </div>

        {{-- Total Jobs --}}
        <div class="kpi-card fade-up" style="--accent:#7c5cc4; --icon-bg:#f0ecfb;">
            <div class="kpi-icon"><i class="ti ti-list-numbers"></i></div>
            <div class="kpi-val" data-count="{{ $total_jobs }}">{{ $total_jobs }}</div>
            <div class="kpi-label">Total Jobs</div>
            <div class="kpi-sub">
                <span style="color:#1d4ed8;">📱 {{ $device_jobs }} {{ __('db.device') }}</span>
                &nbsp;|&nbsp;
                <span style="color:#b45309;">🚗 {{ $vehicle_jobs }} {{ __('db.vehicle') }}</span>
            </div>
        </div>

        {{-- In Progress --}}
        <div class="kpi-card fade-up" style="--accent:#3b82f6; --icon-bg:#eff6ff;">
            <div class="kpi-icon"><i class="ti ti-reload"></i></div>
            <div class="kpi-val" data-count="{{ $in_progress }}">{{ $in_progress }}</div>
            <div class="kpi-label">{{ __('db.in_progress') }}</div>
            <div class="kpi-sub">+{{ $diagnosed }} {{ __('db.diagnosed') }} &nbsp;·&nbsp; {{ $pending }} {{ __('db.Pending') }}</div>
        </div>

        {{-- Completed --}}
        <div class="kpi-card fade-up" style="--accent:#22c55e; --icon-bg:#f0fdf4;">
            <div class="kpi-icon"><i class="ti ti-shield-check"></i></div>
            <div class="kpi-val" data-count="{{ $completed + $delivered }}">{{ $completed + $delivered }}</div>
            <div class="kpi-label">Completed</div>
            <div class="kpi-sub">{{ $completed }} completed &nbsp;·&nbsp; {{ $delivered }} delivered</div>
        </div>

    </div>{{-- /kpi-grid --}}

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- CHARTS ROW                                                 --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="row-panels mb-4">

        {{-- Monthly trend chart --}}
        <div class="panel fade-up">
            <div class="panel-head">
                <h6>
                    <span class="ph-icon"><i class="ti ti-chart-line"></i></span>
                    Monthly Trend <small style="color:var(--slate);font-weight:400;font-size:.75rem;margin-left:4px;">(last 6 months)</small>
                </h6>
                <a href="{{ route('repair.service.index') }}" class="panel-link">View All →</a>
            </div>
            <div class="panel-body">
                <div class="chart-wrap">
                    <canvas id="trend-chart"></canvas>
                </div>
            </div>
        </div>

        {{-- Status donut --}}
        <div class="panel fade-up">
            <div class="panel-head">
                <h6>
                    <span class="ph-icon"><i class="ti ti-chart-pie"></i></span>
                    Job Status
                </h6>
            </div>
            <div class="panel-body">
                <div class="donut-wrap">
                    <canvas id="donut-chart"></canvas>
                </div>
                <div class="status-legend">
                    @php
                        $statusColors = [
                            'pending'     => '#f59e0b',
                            'diagnosed'   => '#0891b2',
                            'in_progress' => '#3b82f6',
                            'completed'   => '#22c55e',
                            'delivered'   => '#7c5cc4',
                            'cancelled'   => '#ef4444',
                        ];
                        $statusLabels = [
                            'pending'     => __('db.Pending'),
                            'diagnosed'   => __('db.diagnosed'),
                            'in_progress' => __('db.in_progress'),
                            'completed'   => __('db.Completed'),
                            'delivered'   => __('db.Delivered'),
                            'cancelled'   => __('db.Cancelled'),
                        ];
                        $total_sc = array_sum($status_chart);
                    @endphp
                    @foreach($statusColors as $key => $color)
                        @php $cnt = $status_chart[$key] ?? 0; @endphp
                        @if($cnt > 0)
                        <div class="legend-item">
                            <span class="legend-dot" style="background:{{ $color }};"></span>
                            <span class="legend-label">{{ $statusLabels[$key] }}</span>
                            <span class="legend-val">{{ $cnt }}</span>
                        </div>
                        @endif
                    @endforeach
                    @if($total_sc === 0)
                        <p class="text-muted text-center py-3" style="font-size:.85rem;">No data for period</p>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- BOTTOM ROW  — Recent jobs + Techs + Payments              --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div style="display:grid; grid-template-columns:1fr 280px 240px; gap:16px; margin-bottom:20px;">

        {{-- Recent Jobs --}}
        <div class="panel fade-up">
            <div class="panel-head">
                <h6>
                    <span class="ph-icon"><i class="ti ti-clock"></i></span>
                    Recent Service Jobs
                </h6>
                <a href="{{ route('repair.service.create') }}" class="panel-link" style="background:var(--p-lt);padding:4px 12px;border-radius:999px;">
                    + New Job
                </a>
            </div>
            <div style="overflow-x:auto;">
                <table class="rj-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Pri</th>
                            <th>Total</th>
                            <th>Due</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($recent_jobs as $job)
                        <tr>
                            <td>
                                <a href="{{ route('repair.service.show', $job->id) }}">
                                    {{ $job->reference_no }}
                                </a>
                                <div style="font-size:.72rem;color:var(--slate);">
                                    {{ date(config('date_format'), strtotime($job->created_at)) }}
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:500;">{{ optional($job->customer)->name ?? '—' }}</div>
                                <div style="font-size:.72rem;color:var(--slate);">{{ optional($job->warehouse)->name }}</div>
                            </td>
                            <td>
                                <span class="type-pill {{ $job->service_type }}">
                                    {{ $job->service_type === 'device' ? '📱' : '🚗' }}
                                    {{ $job->service_type === 'device' ? __('db.device') : __('db.vehicle') }}
                                </span>
                            </td>
                            <td>
                                <span class="status-dot s-{{ $job->status }}">
                                    {{ ucfirst(str_replace('_',' ',$job->status)) }}
                                </span>
                            </td>
                            <td>
                                <span class="pri pri-{{ $job->priority }}" title="{{ ucfirst($job->priority) }}"></span>
                            </td>
                            <td style="font-family:'DM Mono',monospace; font-size:.82rem; font-weight:600;">
                                {{ number_format($job->total_amount, config('decimal')) }}
                            </td>
                            <td style="font-family:'DM Mono',monospace; font-size:.82rem;
                                color:{{ $job->due_amount > 0 ? '#dc2626' : '#16a34a' }}; font-weight:600;">
                                {{ number_format($job->due_amount, config('decimal')) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4" style="font-size:.88rem;">
                                No service jobs yet.
                                <a href="{{ route('repair.service.create') }}" style="color:var(--p);">Create one →</a>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Top Technicians --}}
        <div class="panel fade-up">
            <div class="panel-head">
                <h6>
                    <span class="ph-icon"><i class="ti ti-users"></i></span>
                    Top Technicians
                </h6>
            </div>
            <div class="panel-body">
                @php $max_done = $top_technicians->max('done') ?: 1; @endphp
                @forelse($top_technicians as $idx => $tech)
                    <div class="tech-row">
                        <div class="tech-avatar" style="background:linear-gradient(135deg,
                            {{ ['#7c5cc4','#3b82f6','#22c55e','#f59e0b','#ef4444'][$idx % 5] }},
                            {{ ['#5e43a0','#1d4ed8','#16a34a','#d97706','#dc2626'][$idx % 5] }});">
                            {{ strtoupper(substr(optional($tech->assignedTo)->name ?? '?', 0, 1)) }}
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div class="tech-name text-truncate">
                                {{ optional($tech->assignedTo)->name ?? 'Unknown' }}
                            </div>
                            <div class="tech-bar-wrap mt-1">
                                <div class="tech-bar"
                                    style="width:{{ ($tech->done / $max_done) * 100 }}%;
                                    background:linear-gradient(90deg,
                                    {{ ['#7c5cc4','#3b82f6','#22c55e','#f59e0b','#ef4444'][$idx % 5] }},
                                    {{ ['#9b7dd4','#60a5fa','#4ade80','#fbbf24','#f87171'][$idx % 5] }});"></div>
                            </div>
                            <div class="tech-meta">{{ number_format($tech->earned, config('decimal')) }} earned</div>
                        </div>
                        <div class="tech-count">{{ $tech->done }}</div>
                    </div>
                @empty
                    <p class="text-muted text-center py-4" style="font-size:.85rem;">No completed jobs yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Payment Methods --}}
        <div class="panel fade-up">
            <div class="panel-head">
                <h6>
                    <span class="ph-icon"><i class="ti ti-credit-card"></i></span>
                    Payment Methods
                </h6>
            </div>
            <div class="panel-body">
                @php
                    $pm_icons = ['Cash'=>'💵','Cheque'=>'📝','Card'=>'💳','bKash'=>'📲','Nagad'=>'📱','Rocket'=>'🚀','Bank Transfer'=>'🏦'];
                @endphp
                @forelse($payment_methods as $pm)
                    <div class="pay-method-row">
                        <div class="pm-icon">{{ $pm_icons[$pm->paying_method] ?? '💰' }}</div>
                        <div style="flex:1; min-width:0;">
                            <div class="pm-label">{{ $pm->paying_method }}</div>
                            <div class="pm-count">{{ $pm->cnt }} payment{{ $pm->cnt > 1 ? 's' : '' }}</div>
                        </div>
                        <div class="pm-amount">{{ number_format($pm->total, config('decimal')) }}</div>
                    </div>
                @empty
                    <p class="text-muted text-center py-4" style="font-size:.85rem;">No payments yet.</p>
                @endforelse
            </div>
        </div>

    </div>

</div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Sidebar active ───────────────────────────────────────────────
$("ul#repair").siblings('a').attr('aria-expanded','true');
$("ul#repair").addClass("show");
$("ul#repair #repair-dashboard-menu").addClass("active");

// ── Datepickers ──────────────────────────────────────────────────
$('#sd, #ed').datepicker({
    format: 'yyyy-mm-dd',
    autoclose: true,
    todayHighlight: true
});

// ── Chart.js defaults ────────────────────────────────────────────
Chart.defaults.font.family = "'DM Sans', sans-serif";
Chart.defaults.font.size   = 12;
Chart.defaults.color       = '#64748b';

// ── Monthly Trend Chart ──────────────────────────────────────────
@php
    $trend_labels    = $monthly_trend->pluck('label')->toJson();
    $trend_jobs      = $monthly_trend->pluck('jobs')->toJson();
    $trend_revenue   = $monthly_trend->pluck('revenue')->toJson();
    $trend_collected = $monthly_trend->pluck('collected')->toJson();
@endphp

var trendCtx = document.getElementById('trend-chart').getContext('2d');
new Chart(trendCtx, {
    type: 'bar',
    data: {
        labels: {!! $trend_labels !!},
        datasets: [
            {
                label: 'Revenue',
                data: {!! $trend_revenue !!},
                backgroundColor: 'rgba(124,92,196,.15)',
                borderColor: '#7c5cc4',
                borderWidth: 2,
                borderRadius: 6,
                order: 2,
            },
            {
                label: 'Collected',
                data: {!! $trend_collected !!},
                backgroundColor: 'rgba(34,197,94,.15)',
                borderColor: '#22c55e',
                borderWidth: 2,
                borderRadius: 6,
                order: 2,
            },
            {
                type: 'line',
                label: 'Jobs',
                data: {!! $trend_jobs !!},
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245,158,11,.08)',
                borderWidth: 2.5,
                pointRadius: 4,
                pointBackgroundColor: '#f59e0b',
                tension: 0.4,
                yAxisID: 'y2',
                order: 1,
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        interaction: { mode:'index', intersect:false },
        plugins: {
            legend: { position:'top', labels:{ boxWidth:10, usePointStyle:true, padding:16 } },
            tooltip: {
                backgroundColor: '#1e1b2e',
                titleColor: '#fff',
                bodyColor: 'rgba(255,255,255,.8)',
                padding: 12,
                cornerRadius: 8,
            }
        },
        scales: {
            x: { grid:{ color:'rgba(0,0,0,.04)' }, ticks:{ font:{size:11} } },
            y: {
                position:'left',
                grid:{ color:'rgba(0,0,0,.04)' },
                ticks:{ font:{size:11}, callback:v=>'{{ config("currency") }}'+v.toLocaleString() }
            },
            y2: {
                position:'right',
                grid:{ display:false },
                ticks:{ font:{size:11}, stepSize:1 }
            }
        }
    }
});

// ── Status Donut Chart ───────────────────────────────────────────
@php
    $donut_labels = [];
    $donut_data   = [];
    $donut_colors = [];
    $clr = ['pending'=>'#f59e0b','diagnosed'=>'#0891b2','in_progress'=>'#3b82f6','completed'=>'#22c55e','delivered'=>'#7c5cc4','cancelled'=>'#ef4444'];

    $lbl = [
        'pending'     => __('db.Pending'),
        'diagnosed'   => __('db.diagnosed'),
        'in_progress' => __('db.in_progress'),
        'completed'   => __('db.Completed'),
        'delivered'   => __('db.Delivered'),
        'cancelled'   => __('db.Cancelled')
    ];

    foreach($clr as $k=>$c){
        if(isset($status_chart[$k]) && $status_chart[$k] > 0){
            $donut_labels[] = $lbl[$k];
            $donut_data[]   = $status_chart[$k];
            $donut_colors[] = $c;
        }
    }
@endphp

var donutCtx = document.getElementById('donut-chart').getContext('2d');
new Chart(donutCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($donut_labels) !!},
        datasets: [{
            data: {!! json_encode($donut_data) !!},
            backgroundColor: {!! json_encode($donut_colors) !!},
            borderWidth: 3,
            borderColor: '#fff',
            hoverBorderWidth: 0,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
            legend: { display:false },
            tooltip: {
                backgroundColor: '#1e1b2e',
                titleColor: '#fff',
                bodyColor: 'rgba(255,255,255,.8)',
                padding: 12,
                cornerRadius: 8,
                callbacks: {
                    label: function(ctx){
                        var total = ctx.dataset.data.reduce((a,b)=>a+b,0);
                        var pct   = total > 0 ? ((ctx.parsed / total)*100).toFixed(1) : 0;
                        return ' ' + ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                    }
                }
            }
        }
    }
});

// ── Counter animation ────────────────────────────────────────────
document.querySelectorAll('.kpi-val[data-count]').forEach(function(el){
    var target = parseInt(el.dataset.count);
    var start  = 0;
    var dur    = 800;
    var step   = Math.ceil(target / (dur / 16));
    var t = setInterval(function(){
        start = Math.min(start + step, target);
        el.textContent = start;
        if (start >= target) clearInterval(t);
    }, 16);
});
</script>
@endpush
