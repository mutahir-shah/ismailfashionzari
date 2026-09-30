@extends('backend.layout.main') @section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

@push('css')
<style>
    .payment-settings-card {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 12px 35px rgba(15, 23, 42, .08);
        overflow: hidden;
    }

    .payment-settings-card > .card-header {
        align-items: center;
        background: linear-gradient(135deg, #fff 0%, #f7f9ff 100%);
        border-bottom: 1px solid #edf0f7;
        display: flex;
        justify-content: space-between;
        padding: 1.25rem 1.5rem;
    }

    .payment-settings-title {
        align-items: center;
        display: flex;
        gap: .85rem;
    }

    .payment-settings-title-icon {
        align-items: center;
        background: linear-gradient(135deg, var(--theme-color), #8b5cf6);
        border-radius: 12px;
        box-shadow: 0 8px 18px rgba(99, 102, 241, .25);
        color: #fff;
        display: inline-flex;
        height: 42px;
        justify-content: center;
        width: 42px;
    }

    .payment-settings-title-copy h4 {
        color: #172033;
        font-size: 1.05rem;
        font-weight: 700;
        line-height: 1.25;
        margin: 0;
    }

    .payment-settings-title-copy p {
        color: #778197;
        font-size: .79rem;
        margin: .2rem 0 0;
    }

    .payment-header-count {
        background: #eef2ff;
        border-radius: 999px;
        color: #4f46e5;
        font-size: .76rem;
        font-weight: 700;
        padding: .42rem .7rem;
        white-space: nowrap;
    }

    .payment-settings-card > .card-body {
        background: #f8fafc;
        padding: 1.35rem;
    }

    .payment-toolbar {
        align-items: center;
        display: flex;
        gap: .8rem;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .gateway-search {
        max-width: 390px;
        position: relative;
        width: 100%;
    }

    .gateway-search i {
        color: #98a2b3;
        left: 14px;
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
    }

    .gateway-search input {
        background: #fff;
        border: 1px solid #e3e8f2;
        border-radius: 11px;
        box-shadow: 0 3px 10px rgba(15, 23, 42, .03);
        color: #263247;
        font-size: .86rem;
        height: 44px;
        padding: 0 14px 0 40px;
        width: 100%;
    }

    .gateway-search input:focus {
        border-color: #818cf8;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, .12);
        outline: 0;
    }

    .gateway-filters {
        background: #eef1f6;
        border-radius: 10px;
        display: inline-flex;
        padding: 3px;
    }

    .gateway-filter {
        background: transparent;
        border: 0;
        border-radius: 8px;
        color: #667085;
        cursor: pointer;
        font-size: .76rem;
        font-weight: 700;
        padding: .48rem .72rem;
    }

    .gateway-filter.active {
        background: #fff;
        box-shadow: 0 2px 7px rgba(15, 23, 42, .08);
        color: #4f46e5;
    }

    .payment-gateway-workspace {
        background: #fff;
        border: 1px solid #e7ebf2;
        border-radius: 16px;
        display: grid;
        grid-template-columns: minmax(255px, 31%) 1fr;
        min-height: 540px;
        overflow: hidden;
    }

    .gateway-sidebar {
        background: #fbfcff;
        border-right: 1px solid #e7ebf2;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .gateway-sidebar-heading {
        border-bottom: 1px solid #edf0f5;
        padding: 1rem 1rem .8rem;
    }

    .gateway-sidebar-heading strong {
        color: #273247;
        display: block;
        font-size: .84rem;
    }

    .gateway-sidebar-heading span {
        color: #98a2b3;
        font-size: .72rem;
    }

    .gateway-list {
        max-height: 610px;
        overflow-y: auto;
        padding: .65rem;
        scrollbar-color: #cbd2df transparent;
        scrollbar-width: thin;
    }

    .gateway-list-item {
        align-items: center;
        border: 1px solid transparent;
        border-radius: 12px;
        display: flex;
        gap: .55rem;
        margin-bottom: .45rem;
        padding: .55rem .55rem .55rem .65rem;
        transition: background-color .18s ease, border-color .18s ease, box-shadow .18s ease;
    }

    .gateway-list-item:hover {
        background: #fff;
        border-color: #e1e6ef;
    }

    .gateway-list-item.is-selected {
        background: #fff;
        border-color: #c7d2fe;
        box-shadow: 0 7px 18px rgba(79, 70, 229, .09);
    }

    .gateway-select {
        align-items: center;
        background: transparent;
        border: 0;
        cursor: pointer;
        display: flex;
        flex: 1;
        gap: .7rem;
        min-width: 0;
        padding: 0;
        text-align: left;
    }

    .gateway-logo {
        align-items: center;
        border-radius: 10px;
        color: #fff;
        display: inline-flex;
        flex: 0 0 38px;
        font-size: .72rem;
        font-weight: 800;
        height: 38px;
        justify-content: center;
        letter-spacing: .02em;
    }

    .gateway-accent-0 { background: linear-gradient(135deg, #635bff, #8b5cf6); }
    .gateway-accent-1 { background: linear-gradient(135deg, #0ea5e9, #2563eb); }
    .gateway-accent-2 { background: linear-gradient(135deg, #10b981, #059669); }
    .gateway-accent-3 { background: linear-gradient(135deg, #f59e0b, #ea580c); }
    .gateway-accent-4 { background: linear-gradient(135deg, #ec4899, #db2777); }
    .gateway-accent-5 { background: linear-gradient(135deg, #14b8a6, #0f766e); }

    .gateway-list-copy {
        min-width: 0;
    }

    .gateway-list-name {
        color: #263247;
        display: block;
        font-size: .82rem;
        font-weight: 700;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .gateway-list-meta {
        align-items: center;
        display: flex;
        gap: .35rem;
        margin-top: .18rem;
    }

    .gateway-status-dot {
        background: #cbd5e1;
        border-radius: 50%;
        height: 6px;
        width: 6px;
    }

    .gateway-status-dot.is-active {
        background: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, .12);
    }

    .gateway-status-label,
    .gateway-mode-label {
        color: #8a94a6;
        font-size: .66rem;
        font-weight: 600;
    }

    .gateway-mode-label {
        background: #f1f5f9;
        border-radius: 999px;
        color: #64748b;
        padding: .08rem .35rem;
        text-transform: capitalize;
    }

    .gateway-list-empty {
        color: #8a94a6;
        display: none;
        font-size: .8rem;
        padding: 2rem 1rem;
        text-align: center;
    }

    .gateway-toggle {
        flex: 0 0 34px;
        margin: 0;
        min-height: 22px;
        padding-left: 2.25rem;
    }

    .gateway-toggle .custom-control-label {
        cursor: pointer;
        height: 20px;
    }

    .gateway-toggle .custom-control-label::before {
        border-radius: .6rem;
        left: -2.25rem;
        pointer-events: all;
        width: 1.75rem;
    }

    .gateway-toggle .custom-control-label::after {
        background-color: #adb5bd;
        border-radius: .5rem;
        height: calc(1rem - 4px);
        left: calc(-2.25rem + 2px);
        top: calc(.25rem + 2px);
        transition: transform .15s ease-in-out;
        width: calc(1rem - 4px);
    }

    .gateway-toggle .custom-control-input:checked ~ .custom-control-label::before {
        background-color: var(--theme-color);
        border-color: var(--theme-color);
    }

    .gateway-toggle .custom-control-input:checked ~ .custom-control-label::after {
        background-color: #fff;
        transform: translateX(.75rem);
    }

    .gateway-panel {
        display: none;
        min-height: 100%;
        padding: 1.4rem 1.5rem;
    }

    .gateway-panel.is-active {
        display: block;
    }

    .gateway-panel-header {
        align-items: flex-start;
        border-bottom: 1px solid #edf0f5;
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        margin-bottom: 1.15rem;
        padding-bottom: 1rem;
    }

    .gateway-panel-identity {
        align-items: center;
        display: flex;
        gap: .85rem;
    }

    .gateway-panel-identity .gateway-logo {
        border-radius: 12px;
        flex-basis: 46px;
        font-size: .78rem;
        height: 46px;
    }

    .gateway-panel-title {
        color: #172033;
        font-size: 1.05rem;
        font-weight: 700;
        margin: 0;
    }

    .gateway-panel-subtitle {
        color: #8b95a7;
        font-size: .76rem;
        margin: .18rem 0 0;
    }

    .gateway-config-state {
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 700;
        padding: .4rem .65rem;
        white-space: nowrap;
    }

    .gateway-config-state.is-configured {
        background: #ecfdf3;
        color: #047857;
    }

    .gateway-config-state.needs-setup {
        background: #fff7ed;
        color: #c2410c;
    }

    .gateway-section-title {
        color: #64748b;
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .08em;
        margin: 0 0 .8rem;
        text-transform: uppercase;
    }

    .gateway-module-box {
        background: #f8faff;
        border: 1px solid #e3e8f2;
        border-radius: 12px;
        margin-bottom: 1.2rem;
        padding: .9rem;
    }

    .gateway-module-options {
        display: grid;
        gap: .65rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .gateway-module-option {
        position: relative;
    }

    .gateway-module-option input {
        opacity: 0;
        position: absolute;
        pointer-events: none;
    }

    .gateway-module-option label {
        align-items: center;
        background: #fff;
        border: 1px solid #dfe5ee;
        border-radius: 10px;
        color: #5f6b7d;
        cursor: pointer;
        display: flex;
        font-size: .78rem;
        font-weight: 700;
        gap: .55rem;
        margin: 0;
        padding: .7rem .8rem;
        transition: border-color .15s ease, background-color .15s ease, color .15s ease;
    }

    .gateway-module-check {
        align-items: center;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
        display: inline-flex;
        height: 18px;
        justify-content: center;
        width: 18px;
    }

    .gateway-module-option input:checked + label {
        background: #f4f2ff;
        border-color: var(--theme-color);
        color: var(--theme-color);
    }

    .gateway-module-option input:checked + label .gateway-module-check {
        background: var(--theme-color);
        border-color: var(--theme-color);
        color: #fff;
    }

    .gateway-module-option input:not(:checked) + label .gateway-module-check i {
        display: none;
    }

    .gateway-field-card {
        background: #fbfcfe;
        border: 1px solid #e7ebf2;
        border-radius: 11px;
        height: calc(100% - 1rem);
        margin-bottom: 1rem;
        padding: .85rem;
    }

    .gateway-field-card label {
        color: #39465c;
        display: block;
        font-size: .76rem;
        font-weight: 700;
        margin-bottom: .45rem;
    }

    .gateway-field-card .form-control,
    .gateway-field-card .bootstrap-select > .dropdown-toggle {
        background-color: #fff;
        border-color: #dfe5ee;
        border-radius: 9px;
        font-size: .82rem;
        min-height: 42px;
    }

    .gateway-field-card .form-control:focus {
        border-color: #818cf8;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, .1);
    }

    .gateway-field-help {
        color: #98a2b3;
        display: block;
        font-size: .67rem;
        line-height: 1.4;
        margin-top: .38rem;
    }

    .gateway-secret-wrap {
        position: relative;
    }

    .gateway-secret-wrap .form-control {
        padding-right: 42px;
    }

    .gateway-secret-toggle {
        align-items: center;
        background: transparent;
        border: 0;
        color: #7c8799;
        cursor: pointer;
        display: flex;
        height: 40px;
        justify-content: center;
        position: absolute;
        right: 2px;
        top: 1px;
        width: 38px;
    }

    .payment-save-bar {
        align-items: center;
        background: rgba(255, 255, 255, .96);
        border: 1px solid #e3e8f1;
        border-radius: 13px;
        bottom: 12px;
        box-shadow: 0 9px 28px rgba(15, 23, 42, .11);
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        margin-top: 1rem;
        padding: .8rem .9rem .8rem 1rem;
        position: sticky;
        z-index: 10;
    }

    .payment-save-copy strong {
        color: #344054;
        display: block;
        font-size: .8rem;
    }

    .payment-save-copy span {
        color: #98a2b3;
        font-size: .69rem;
    }

    .payment-unsaved {
        color: #b45309 !important;
        display: none;
        font-weight: 700;
    }

    .payment-save-button {
        background: linear-gradient(135deg, var(--theme-color), #7c3aed);
        border: 0;
        border-radius: 10px;
        box-shadow: 0 7px 16px rgba(99, 102, 241, .24);
        color: #fff;
        font-size: .8rem;
        font-weight: 700;
        min-width: 145px;
        padding: .7rem 1rem;
    }

    .payment-save-button:hover,
    .payment-save-button:focus {
        color: #fff;
        filter: brightness(1.04);
    }

    .gateway-select:focus-visible,
    .gateway-filter:focus-visible,
    .gateway-secret-toggle:focus-visible,
    .payment-save-button:focus-visible {
        box-shadow: 0 0 0 3px rgba(99, 102, 241, .2);
        outline: 0;
    }

    .dark-mode .payment-settings-card > .card-header {
        background: linear-gradient(135deg, #283046 0%, #222a3d 100%);
        border-bottom-color: #3b4253;
    }

    .dark-mode .payment-settings-title-copy h4,
    .dark-mode .gateway-sidebar-heading strong,
    .dark-mode .gateway-list-name,
    .dark-mode .gateway-panel-title,
    .dark-mode .payment-save-copy strong {
        color: #e5e7eb;
    }

    .dark-mode .payment-settings-title-copy p,
    .dark-mode .gateway-panel-subtitle,
    .dark-mode .gateway-field-help,
    .dark-mode .payment-save-copy span {
        color: #9aa4b7;
    }

    .dark-mode .payment-header-count {
        background: rgba(124, 92, 196, .18);
        color: #c4b5fd;
    }

    .dark-mode .payment-settings-card > .card-body {
        background: #141b2e;
    }

    .dark-mode .gateway-search input,
    .dark-mode .payment-gateway-workspace,
    .dark-mode .gateway-list-item:hover,
    .dark-mode .gateway-list-item.is-selected,
    .dark-mode .payment-save-bar {
        background: #283046;
        border-color: #3b4253;
        color: #d0d2d6;
    }

    .dark-mode .gateway-filters,
    .dark-mode .gateway-mode-label {
        background: #1d2538;
    }

    .dark-mode .gateway-filter {
        color: #9aa4b7;
    }

    .dark-mode .gateway-filter.active {
        background: #343d55;
        color: #c4b5fd;
    }

    .dark-mode .gateway-sidebar {
        background: #20283a;
        border-color: #3b4253;
    }

    .dark-mode .gateway-sidebar-heading,
    .dark-mode .gateway-panel-header {
        border-color: #3b4253;
    }

    .dark-mode .gateway-module-box,
    .dark-mode .gateway-field-card {
        background: #20283a;
        border-color: #3b4253;
    }

    .dark-mode .gateway-module-option label,
    .dark-mode .gateway-field-card .form-control,
    .dark-mode .gateway-field-card .bootstrap-select > .dropdown-toggle {
        background-color: #343d55;
        border-color: #465069;
        color: #d0d2d6;
    }

    .dark-mode .gateway-field-card label,
    .dark-mode .gateway-section-title {
        color: #cbd5e1;
    }

    .dark-mode .gateway-config-state.is-configured {
        background: rgba(16, 185, 129, .13);
        color: #6ee7b7;
    }

    .dark-mode .gateway-config-state.needs-setup {
        background: rgba(245, 158, 11, .13);
        color: #fbbf24;
    }

    @media (max-width: 991.98px) {
        .payment-gateway-workspace {
            display: block;
        }

        .gateway-sidebar {
            border-bottom: 1px solid #e7ebf2;
            border-right: 0;
        }

        .gateway-list {
            display: flex;
            gap: .5rem;
            max-height: none;
            overflow-x: auto;
        }

        .gateway-list-item {
            flex: 0 0 235px;
            margin-bottom: 0;
        }
    }

    @media (max-width: 575.98px) {
        .payment-settings-card > .card-body {
            padding: .85rem;
        }

        .payment-toolbar,
        .gateway-panel-header {
            align-items: stretch;
            flex-direction: column;
        }

        .gateway-filters {
            display: flex;
            width: 100%;
        }

        .gateway-filter {
            flex: 1;
        }

        .gateway-panel {
            padding: 1rem;
        }

        .gateway-module-options {
            grid-template-columns: 1fr;
        }

        .payment-save-copy {
            display: none;
        }

        .payment-save-button {
            width: 100%;
        }
    }
</style>
@endpush
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                @php
                    $initialGateway = $payment_gateways->firstWhere('active', 1) ?: $payment_gateways->first();
                    $initialGatewayName = $initialGateway ? $initialGateway->name : '';
                    $activeGatewayCount = $payment_gateways->where('active', 1)->count();
                @endphp
                <div class="card payment-settings-card">
                    <div class="card-header">
                        <div class="payment-settings-title">
                            <span class="payment-settings-title-icon">
                                <i class="dripicons-card"></i>
                            </span>
                            <div class="payment-settings-title-copy">
                                <h4>{{__('db.Payment Gateways')}}</h4>
                                <p>Choose where each gateway is available and manage its credentials.</p>
                            </div>
                        </div>
                        <span class="payment-header-count">
                            <span id="active-gateway-count">{{$activeGatewayCount}}</span>
                            active of {{$payment_gateways->count()}}
                        </span>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('setting.gateway.update') }}" method="POST">
                            @csrf

                            <div class="payment-toolbar">
                                <div class="gateway-search">
                                    <i class="dripicons-search"></i>
                                    <label class="sr-only" for="gateway-search-input">Search payment gateways</label>
                                    <input id="gateway-search-input" type="search"
                                        placeholder="Search payment gateways..." autocomplete="off">
                                </div>
                                <div class="gateway-filters" role="group" aria-label="Filter payment gateways">
                                    <button type="button" class="gateway-filter active" data-filter="all">All</button>
                                    <button type="button" class="gateway-filter" data-filter="active">Active</button>
                                    <button type="button" class="gateway-filter" data-filter="inactive">Inactive</button>
                                </div>
                            </div>

                            <div class="payment-gateway-workspace">
                                <aside class="gateway-sidebar" aria-label="Payment gateways">
                                    <div class="gateway-sidebar-heading">
                                        <strong>Payment gateways</strong>
                                        <span>Select a provider to configure it</span>
                                    </div>
                                    <div class="gateway-list" role="tablist" aria-orientation="vertical">
                                        @foreach($payment_gateways as $pg)
                                            @php
                                                $gatewayId = Str::slug($pg->name);
                                                $gatewayTitle = Str::headline($pg->name);
                                                $gatewayInitials = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $pg->name), 0, 2));
                                                $detailParts = array_pad(explode(';', (string) $pg->details, 2), 2, '');
                                                $detailKeys = array_map('trim', explode(',', $detailParts[0]));
                                                $detailValues = array_pad(
                                                    array_map('trim', explode(',', $detailParts[1])),
                                                    count($detailKeys),
                                                    ''
                                                );
                                                $gatewayDetails = $detailKeys
                                                    ? array_combine($detailKeys, array_slice($detailValues, 0, count($detailKeys)))
                                                    : [];
                                                $gatewayMode = $gatewayDetails['Mode'] ?? $gatewayDetails['mode'] ?? '';
                                                $isSelectedGateway = $pg->name === $initialGatewayName;
                                            @endphp
                                            <div class="gateway-list-item {{$isSelectedGateway ? 'is-selected' : ''}}"
                                                data-gateway-item="{{$gatewayId}}"
                                                data-gateway-name="{{strtolower($gatewayTitle)}}"
                                                data-status="{{$pg->active == 1 ? 'active' : 'inactive'}}">
                                                <button type="button" class="gateway-select"
                                                    id="gateway-tab-{{$gatewayId}}"
                                                    data-gateway-target="{{$gatewayId}}"
                                                    role="tab"
                                                    aria-controls="gateway-panel-{{$gatewayId}}"
                                                    aria-selected="{{$isSelectedGateway ? 'true' : 'false'}}">
                                                    <span class="gateway-logo gateway-accent-{{$loop->index % 6}}">
                                                        {{$gatewayInitials}}
                                                    </span>
                                                    <span class="gateway-list-copy">
                                                        <span class="gateway-list-name">{{$gatewayTitle}}</span>
                                                        <span class="gateway-list-meta">
                                                            <span class="gateway-status-dot {{$pg->active == 1 ? 'is-active' : ''}}"></span>
                                                            <span class="gateway-status-label">
                                                                {{$pg->active == 1 ? 'Active' : 'Inactive'}}
                                                            </span>
                                                            @if($gatewayMode !== '')
                                                                <span class="gateway-mode-label">{{$gatewayMode}}</span>
                                                            @endif
                                                        </span>
                                                    </span>
                                                </button>
                                                <input type="hidden" name="pg_name[]" value="{{$pg->name}}">
                                                <div class="custom-control custom-switch gateway-toggle">
                                                    <input type="checkbox" @if($pg->active == 1) checked @endif
                                                        class="activate custom-control-input"
                                                        id="gateway-active-{{$gatewayId}}"
                                                        aria-label="Activate {{$gatewayTitle}}">
                                                    <label class="custom-control-label" for="gateway-active-{{$gatewayId}}">
                                                        <span class="sr-only">Activate {{$gatewayTitle}}</span>
                                                    </label>
                                                    <input type="hidden" name="active[]" value="{{$pg->active}}">
                                                </div>
                                            </div>
                                        @endforeach
                                        <div class="gateway-list-empty" aria-live="polite">
                                            No payment gateways match your search.
                                        </div>
                                    </div>
                                </aside>

                                <section class="gateway-panel-area" aria-label="Gateway configuration">
                                    @forelse($payment_gateways as $pg)
                                        @php
                                            $gatewayIndex = $loop->index;
                                            $gatewayId = Str::slug($pg->name);
                                            $gatewayTitle = Str::headline($pg->name);
                                            $gatewayInitials = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $pg->name), 0, 2));
                                            $modules = json_decode($pg->module_status, true) ?: [];
                                            $detailParts = array_pad(explode(';', (string) $pg->details, 2), 2, '');
                                            $detailKeys = array_map('trim', explode(',', $detailParts[0]));
                                            $detailValues = array_pad(
                                                array_map('trim', explode(',', $detailParts[1])),
                                                count($detailKeys),
                                                ''
                                            );
                                            $results = $detailKeys
                                                ? array_combine($detailKeys, array_slice($detailValues, 0, count($detailKeys)))
                                                : [];
                                            $ignoredConfigurationKeys = [
                                                'mode', 'target_environment', 'country_code',
                                                'return_url', 'return url', 'callback_url'
                                            ];
                                            $configurationFields = collect($results)->reject(function ($value, $key) use ($ignoredConfigurationKeys) {
                                                return in_array(strtolower(trim((string) $key)), $ignoredConfigurationKeys, true);
                                            });
                                            $configuredFieldCount = $configurationFields->filter(function ($value) {
                                                return trim((string) $value) !== '';
                                            })->count();
                                            $isConfigured = $configurationFields->count() > 0
                                                && $configuredFieldCount === $configurationFields->count();
                                            $isSelectedGateway = $pg->name === $initialGatewayName;
                                        @endphp
                                        <div class="gateway-panel {{$isSelectedGateway ? 'is-active' : ''}}"
                                            id="gateway-panel-{{$gatewayId}}"
                                            data-gateway-panel="{{$gatewayId}}"
                                            role="tabpanel"
                                            aria-labelledby="gateway-tab-{{$gatewayId}}"
                                            @if(!$isSelectedGateway) hidden @endif>
                                            <div class="gateway-panel-header">
                                                <div class="gateway-panel-identity">
                                                    <span class="gateway-logo gateway-accent-{{$loop->index % 6}}">
                                                        {{$gatewayInitials}}
                                                    </span>
                                                    <div>
                                                        <h5 class="gateway-panel-title">{{$gatewayTitle}}</h5>
                                                        <p class="gateway-panel-subtitle">
                                                            Configure availability and provider credentials.
                                                        </p>
                                                    </div>
                                                </div>
                                                <span class="gateway-config-state {{$isConfigured ? 'is-configured' : 'needs-setup'}}">
                                                    {{$isConfigured ? 'Configured' : 'Needs setup'}}
                                                </span>
                                            </div>

                                            <div class="gateway-module-box">
                                                <p class="gateway-section-title">Available in modules</p>
                                                <div class="gateway-module-options">
                                                    @foreach(['pos' => 'Point of Sale', 'ecommerce' => 'Ecommerce'] as $module => $moduleLabel)
                                                        <div class="gateway-module-option">
                                                            <input type="checkbox"
                                                                id="gateway-module-{{$gatewayId}}-{{$module}}"
                                                                name="module_status[{{$gatewayIndex}}][]"
                                                                value="{{$module}}"
                                                                @if(!empty($modules[$module])) checked @endif>
                                                            <label for="gateway-module-{{$gatewayId}}-{{$module}}">
                                                                <span class="gateway-module-check">
                                                                    <i class="dripicons-checkmark"></i>
                                                                </span>
                                                                {{$moduleLabel}}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <p class="gateway-section-title">Connection details</p>
                                            <div class="row gateway-fields">
                                                @foreach ($results as $key => $value)
                                                    @php
                                                        $normalizedKey = strtolower(str_replace([' ', '-'], '_', trim($key)));
                                                        $isSensitive = !str_contains($normalizedKey, 'public')
                                                            && (
                                                                str_contains($normalizedKey, 'secret')
                                                                || str_contains($normalizedKey, 'password')
                                                                || str_contains($normalizedKey, 'passkey')
                                                                || str_contains($normalizedKey, 'api_key')
                                                                || str_contains($normalizedKey, 'subscription_key')
                                                                || str_contains($normalizedKey, 'token')
                                                                || str_contains($normalizedKey, 'salt')
                                                            );
                                                        $isUrlField = str_contains($normalizedKey, 'url');
                                                        $fieldName = $pg->name . '_' . str_replace(' ', '_', $key);
                                                    @endphp
                                                    <div class="col-xl-6 col-md-12">
                                                        <div class="gateway-field-card">
                                                            <label for="gateway-field-{{$gatewayId}}-{{$loop->index}}">
                                                                {{Str::headline($key)}}
                                                            </label>
                                                            @if($key == 'Mode')
                                                                <select id="gateway-field-{{$gatewayId}}-{{$loop->index}}"
                                                                    name="{{$fieldName}}"
                                                                    class="selectpicker form-control gateway-mode-select"
                                                                    data-gateway-id="{{$gatewayId}}">
                                                                    <option @if($value == 'sandbox') selected @endif value="sandbox">
                                                                        Sandbox
                                                                    </option>
                                                                    <option @if($value == 'live') selected @endif value="live">
                                                                        Live
                                                                    </option>
                                                                </select>
                                                                <small class="gateway-field-help">
                                                                    Use Sandbox for testing and Live only with production credentials.
                                                                </small>
                                                            @elseif($isSensitive)
                                                                <div class="gateway-secret-wrap">
                                                                    <input id="gateway-field-{{$gatewayId}}-{{$loop->index}}"
                                                                        type="password" name="{{$fieldName}}"
                                                                        class="form-control gateway-secret-input"
                                                                        value="{{$value}}" autocomplete="new-password">
                                                                    <button type="button" class="gateway-secret-toggle"
                                                                        aria-label="Show {{Str::headline($key)}}"
                                                                        title="Show or hide value">
                                                                        <i class="dripicons-preview"></i>
                                                                    </button>
                                                                </div>
                                                                <small class="gateway-field-help">
                                                                    Keep this credential private and use the value supplied by the provider.
                                                                </small>
                                                            @else
                                                                <input id="gateway-field-{{$gatewayId}}-{{$loop->index}}"
                                                                    type="text" name="{{$fieldName}}"
                                                                    class="form-control" value="{{$value}}">
                                                                <small class="gateway-field-help">
                                                                    @if($isUrlField)
                                                                        Enter the complete URL required by the provider.
                                                                    @elseif($normalizedKey === 'country_code')
                                                                        Use the international dialing code without the plus sign.
                                                                    @elseif($normalizedKey === 'target_environment')
                                                                        Enter the provider's live country environment identifier.
                                                                    @else
                                                                        Enter the value from your {{$gatewayTitle}} account.
                                                                    @endif
                                                                </small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @empty
                                        <div class="gateway-panel is-active">
                                            <div class="gateway-list-empty" style="display:block">
                                                No payment gateways are available.
                                            </div>
                                        </div>
                                    @endforelse
                                </section>
                            </div>

                            <div class="payment-save-bar">
                                <div class="payment-save-copy">
                                    <strong>Payment gateway settings</strong>
                                    <span class="payment-save-default">Changes are applied when you save this form.</span>
                                    <span class="payment-unsaved">You have unsaved payment changes.</span>
                                </div>
                                <button type="submit" class="payment-save-button">
                                    <i class="dripicons-checkmark mr-1"></i> Save changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    $('.selectpicker').selectpicker('refresh');

    let gatewayFilter = 'all';

    function selectGateway(gatewayId) {
        const $item = $('[data-gateway-item="' + gatewayId + '"]');
        const $panel = $('[data-gateway-panel="' + gatewayId + '"]');

        if (!$item.length || !$item.is(':visible') || !$panel.length) {
            return;
        }

        $('.gateway-list-item').removeClass('is-selected');
        $('.gateway-select').attr('aria-selected', 'false');
        $item.addClass('is-selected');
        $item.find('.gateway-select').attr('aria-selected', 'true');

        $('.gateway-panel').removeClass('is-active').prop('hidden', true);
        $panel.addClass('is-active').prop('hidden', false);

        window.setTimeout(function () {
            $panel.find('.selectpicker').selectpicker('refresh');
        }, 0);
    }

    function applyGatewayFilters() {
        const query = ($('#gateway-search-input').val() || '').toLowerCase().trim();
        let visibleCount = 0;

        $('.gateway-list-item').each(function () {
            const $item = $(this);
            const matchesSearch = !query || ($item.data('gateway-name') || '').indexOf(query) !== -1;
            const matchesStatus = gatewayFilter === 'all' || $item.attr('data-status') === gatewayFilter;
            const isVisible = matchesSearch && matchesStatus;

            $item.toggle(isVisible);
            if (isVisible) {
                visibleCount++;
            }
        });

        $('.gateway-list-empty').first().toggle(visibleCount === 0);

        const $selected = $('.gateway-list-item.is-selected');
        if (!$selected.length || !$selected.is(':visible')) {
            const $firstVisible = $('.gateway-list-item:visible').first();
            if ($firstVisible.length) {
                selectGateway($firstVisible.data('gateway-item'));
            } else {
                $('.gateway-panel').removeClass('is-active').prop('hidden', true);
            }
        }
    }

    function updateGatewayCount() {
        $('#active-gateway-count').text($('.gateway-list-item .activate:checked').length);
    }

    $(document).on('click', '.gateway-select', function () {
        selectGateway($(this).data('gateway-target'));
    });

    $(document).on('input', '#gateway-search-input', applyGatewayFilters);

    $(document).on('click', '.gateway-filter', function () {
        gatewayFilter = $(this).data('filter');
        $('.gateway-filter').removeClass('active');
        $(this).addClass('active');
        applyGatewayFilters();
    });

    $(document).on('change', '.activate', function () {
        const isActive = $(this).is(':checked');
        const $item = $(this).closest('.gateway-list-item');

        $(this).siblings('input[type="hidden"]').val(isActive ? 1 : 0);
        $item.attr('data-status', isActive ? 'active' : 'inactive');
        $item.find('.gateway-status-dot').toggleClass('is-active', isActive);
        $item.find('.gateway-status-label').text(isActive ? 'Active' : 'Inactive');
        updateGatewayCount();
        applyGatewayFilters();
    });

    $(document).on('changed.bs.select change', '.gateway-mode-select', function () {
        const gatewayId = $(this).data('gateway-id');
        $('[data-gateway-item="' + gatewayId + '"] .gateway-mode-label').text($(this).val());
    });

    $(document).on('click', '.gateway-secret-toggle', function () {
        const $input = $(this).siblings('.gateway-secret-input');
        const showValue = $input.attr('type') === 'password';

        $input.attr('type', showValue ? 'text' : 'password');
        $(this).attr('aria-label', showValue ? 'Hide credential' : 'Show credential');
        $(this).find('i').toggleClass('dripicons-preview dripicons-conversation');
    });

    $('.payment-settings-card form').on('input change', ':input[name]', function () {
        $('.payment-save-default').hide();
        $('.payment-unsaved').show();
    });
</script>
@endpush
