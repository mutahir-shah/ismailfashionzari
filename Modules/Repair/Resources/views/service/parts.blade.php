@extends('backend.layout.main')

@push('css')
    <style>
        :root {
            --theme: #7c5cc4;
            --theme-dk: #6548b0;
            --theme-lt: #f5f3fc;
            --theme-mid: #ece8f8;
            --slate: #5a6872;
            --slate-dk: #455a64;
            --success: #22c55e;
            --danger: #ef4444;
            --warn: #f59e0b;
            --border: #e4dff5;
            --text: #1e1b2e;
            --text-muted: #7c7895;
            --radius: 10px;
            --shadow: 0 2px 12px rgba(124, 92, 196, .10);
        }

        body { background: #f7f6fb; }
        .pb-page { padding: 20px 0; }

        /* ── Page Header ── */
        .page-hdr {
            background: #fff; border: 1px solid var(--border);
            border-radius: var(--radius); padding: 14px 20px;
            margin-bottom: 20px; box-shadow: var(--shadow);
            display: flex; align-items: center;
            justify-content: space-between; flex-wrap: wrap; gap: 10px;
        }
        .page-hdr-title { font-size: 1.1rem; font-weight: 700; color: var(--theme); margin: 0; }
        .page-hdr-meta { font-size: .82rem; color: var(--text-muted); margin-top: 2px; }
        .page-hdr-meta strong { color: var(--text); }

        /* ── Card ── */
        .pb-card {
            background: #fff; border: 1px solid var(--border);
            border-radius: var(--radius); margin-bottom: 18px;
            box-shadow: var(--shadow); overflow: hidden;
        }
        .pb-card-head {
            padding: 11px 18px; display: flex; align-items: center;
            gap: 8px; font-weight: 600; font-size: .88rem;
            border-bottom: 1px solid var(--border);
        }
        .pb-card-head.purple { background: var(--theme-lt); color: var(--theme-dk); }
        .pb-card-head.slate  { background: #f0f2f4; color: var(--slate); }
        .pb-card-head.dark   { background: #eceff1; color: var(--slate-dk); }
        .pb-card-head i { opacity: .75; }
        .pb-card-body { padding: 18px; }

        /* ── Search ── */
        .search-wrap { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
        .search-box { position: relative; flex: 1; max-width: 400px; }
        .search-box input {
            width: 100%; height: 36px; border: 1.5px solid var(--border);
            border-radius: 8px; padding: 0 12px 0 36px; font-size: .875rem;
            color: var(--text); transition: border-color .2s, box-shadow .2s; background: #faf9fe;
        }
        .search-box input:focus {
            outline: none; border-color: var(--theme);
            box-shadow: 0 0 0 3px rgba(124,92,196,.12); background: #fff;
        }
        .search-box .si {
            position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
            color: var(--theme); font-size: .85rem; pointer-events: none;
        }
        .search-hint { font-size: .8rem; color: var(--text-muted); }

        .ui-autocomplete {
            z-index: 9999; max-height: 240px; overflow-y: auto;
            border: 1px solid var(--border); border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0,0,0,.10); font-size: .875rem;
        }
        .ui-menu-item .ui-menu-item-wrapper.ui-state-active {
            background: var(--theme); border-color: var(--theme); color: #fff;
        }

        /* ── Parts Table ── */
        #parts-table { margin: 0; font-size: .86rem; }
        #parts-table thead th {
            background: var(--theme-lt); color: var(--theme-dk);
            font-weight: 600; font-size: .78rem; text-transform: uppercase;
            letter-spacing: .04em; border-color: var(--border); padding: 9px 12px;
        }
        #parts-table td { vertical-align: middle; border-color: var(--border); padding: 9px 12px; color: var(--text); }
        #parts-table tbody tr { transition: background .12s; }
        #parts-table tbody tr:hover { background: var(--theme-lt); }
        #parts-table tbody tr.dup-flash { animation: dupFlash .6s ease; }
        @keyframes dupFlash {
            0%,100% { background: #fff; }
            30% { background: #fef9c3; }
            60% { background: #fde68a; }
        }

        /* ── Qty Stepper ── */
        .qty-wrap { display: flex; align-items: center; gap: 3px; }
        .qty-btn {
            width: 26px; height: 26px; border: 1.5px solid var(--border);
            border-radius: 6px; background: #faf9fe; color: var(--theme);
            font-size: .95rem; cursor: pointer; display: flex; align-items: center;
            justify-content: center; transition: .15s; flex-shrink: 0;
        }
        .qty-btn:hover { background: var(--theme); color: #fff; border-color: var(--theme); }
        .qty-input {
            width: 56px; height: 26px; border: 1.5px solid var(--border);
            border-radius: 6px; text-align: center; font-size: .84rem;
            color: var(--text); background: #faf9fe;
        }
        .qty-input:focus { outline: none; border-color: var(--theme); background: #fff; }

        /* ── Price Input ── */
        .price-input {
            width: 90px; height: 26px; border: 1.5px solid var(--border);
            border-radius: 6px; text-align: right; font-size: .84rem;
            padding: 0 7px; color: var(--text); background: #faf9fe;
        }
        .price-input:focus { outline: none; border-color: var(--theme); background: #fff; }

        #parts-table tfoot td {
            background: var(--theme-mid); font-weight: 700;
            color: var(--theme-dk); border-color: var(--border); padding: 10px 12px;
        }

        /* ── Payments Table ── */
        #payments-table { font-size: .86rem; }
        #payments-table thead th {
            background: #f0f2f4; color: var(--slate); font-weight: 600;
            font-size: .78rem; text-transform: uppercase; letter-spacing: .04em;
            border-color: var(--border); padding: 9px 12px;
        }
        #payments-table td { vertical-align: middle; border-color: var(--border); padding: 9px 12px; }
        #payments-table tfoot td {
            background: #eceff1; font-weight: 700;
            border-color: var(--border); padding: 10px 12px;
        }

        /* ── Charge Inputs ── */
        .charge-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
        .charge-field { flex: 1; min-width: 130px; }
        .charge-field label { font-size: .79rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 5px; }
        .charge-inp {
            width: 100%; height: 34px; border: 1.5px solid var(--border);
            border-radius: 7px; font-size: .875rem; padding: 0 10px;
            color: var(--text); background: #faf9fe; transition: .15s;
        }
        .charge-inp:focus {
            outline: none; border-color: var(--theme);
            box-shadow: 0 0 0 3px rgba(124,92,196,.10); background: #fff;
        }

        /* ── Payment Form ── */
        .pay-block {
            background: var(--theme-lt); border: 1.5px dashed #c5b8ee;
            border-radius: 9px; padding: 14px 16px; margin-bottom: 10px;
            position: relative;
        }
        .pay-block-title {
            font-size: .80rem; font-weight: 700; color: var(--theme);
            margin-bottom: 10px; display: flex; align-items: center; gap: 6px;
        }
        .pay-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
        .pay-field { flex: 1; min-width: 120px; }
        .pay-field label { font-size: .79rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 5px; }
        .pay-field input,
        .pay-field select {
            width: 100%; height: 32px; border: 1.5px solid var(--border);
            border-radius: 6px; font-size: .84rem; padding: 0 9px;
            color: var(--text); background: #fff;
        }
        .pay-field input:focus,
        .pay-field select:focus {
            outline: none; border-color: var(--theme);
            box-shadow: 0 0 0 2px rgba(124,92,196,.12);
        }

        .remove-pay-block {
            position: absolute; top: 10px; right: 12px;
            width: 22px; height: 22px; border-radius: 50%;
            background: #fef2f2; border: 1px solid #fecaca;
            color: var(--danger); cursor: pointer; font-size: .75rem;
            display: flex; align-items: center; justify-content: center;
            transition: .15s;
        }
        .remove-pay-block:hover { background: var(--danger); color: #fff; }

        /* ── Buttons ── */
        .btn-pb {
            height: 32px; padding: 0 14px; font-size: .83rem; font-weight: 600;
            border-radius: 7px; border: none; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px; transition: .15s;
        }
        .btn-pb.primary   { background: var(--theme); color: #fff; }
        .btn-pb.primary:hover { background: var(--theme-dk); color: #fff; }
        .btn-pb.outline   { background: #fff; color: var(--theme); border: 1.5px solid var(--theme); }
        .btn-pb.outline:hover { background: var(--theme); color: #fff; }
        .btn-pb.secondary { background: #e9edf0; color: var(--slate); }
        .btn-pb.secondary:hover { background: #d6dce0; }
        .btn-pb.sm        { height: 26px; padding: 0 10px; font-size: .78rem; border-radius: 5px; }
        .btn-pb.danger    { background: #fef2f2; color: var(--danger); border: 1px solid #fecaca; }
        .btn-pb.danger:hover { background: var(--danger); color: #fff; }
        .btn-pb.success   { background: #f0fdf4; color: var(--success); border: 1px solid #bbf7d0; }
        .btn-pb.success:hover { background: var(--success); color: #fff; }

        /* ── Summary Panel ── */
        .sum-panel {
            background: #fff; border: 1px solid var(--border);
            border-radius: var(--radius); overflow: hidden;
            box-shadow: var(--shadow); margin-bottom: 18px;
        }
        .sum-head {
            background: var(--theme); color: #fff; padding: 12px 18px;
            font-weight: 700; font-size: .88rem; display: flex; align-items: center; gap: 8px;
        }
        .sum-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 9px 18px; border-bottom: 1px solid var(--theme-lt); font-size: .875rem;
        }
        .sum-row:last-child { border-bottom: none; }
        .sum-row .lbl { color: var(--text-muted); }
        .sum-row .val { font-weight: 600; color: var(--text); }
        .sum-row.grand { background: var(--theme-lt); }
        .sum-row.grand .lbl { color: var(--theme-dk); font-weight: 700; font-size: .95rem; }
        .sum-row.grand .val { color: var(--theme); font-size: 1.05rem; font-weight: 800; }
        .sum-row.paid-r .val { color: var(--success); }
        .sum-row.due-r .val { color: var(--danger); font-weight: 700; }
        .sum-row.due-r.ok .val { color: var(--success); }

        .sum-badge-wrap { text-align: center; padding: 14px; border-top: 1px solid var(--border); }
        .sum-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 20px; border-radius: 20px; font-size: .84rem; font-weight: 700;
        }
        .sum-badge.due   { background: #fef2f2; color: var(--danger);  border: 1.5px solid #fecaca; }
        .sum-badge.clear { background: #f0fdf4; color: var(--success); border: 1.5px solid #bbf7d0; }

        /* ── Job Info Table ── */
        .ji-table { width: 100%; }
        .ji-table tr td,
        .ji-table tr th { padding: 7px 14px; border: none; border-bottom: 1px solid var(--theme-lt); font-size: .83rem; }
        .ji-table tr:last-child td,
        .ji-table tr:last-child th { border-bottom: none; }
        .ji-table th { color: var(--text-muted); font-weight: 600; width: 100px; }
        .ji-table td { color: var(--text); }

        /* ── Spinner ── */
        .sp-ov {
            display: none; position: fixed; inset: 0;
            background: rgba(30,27,46,.28); z-index: 9999;
            justify-content: center; align-items: center;
        }
        .sp-ov.on { display: flex; }
        .sp-ring {
            width: 44px; height: 44px; border: 4px solid rgba(255,255,255,.25);
            border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        tr.saving { opacity: .55; pointer-events: none; }

        .empty-state { text-align: center; padding: 30px; color: var(--text-muted); font-size: .86rem; }
        .empty-state i { font-size: 1.5rem; display: block; margin-bottom: 6px; opacity: .5; }

        #dup-notice {
            display: none; background: #fffbeb; border: 1.5px solid #fde68a;
            border-radius: 7px; padding: 7px 14px; font-size: .82rem;
            color: #92400e; margin-bottom: 10px; align-items: center; gap: 8px;
        }
        #dup-notice.show { display: flex; }

        /* ── Pay blocks wrapper ── */
        #pay-blocks-wrap { margin-bottom: 14px; }
        .add-more-pay-btn {
            background: #f0fdf4; border: 1.5px dashed #86efac;
            color: var(--success); border-radius: 8px; padding: 8px 16px;
            font-size: .83rem; font-weight: 600; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px; transition: .15s;
        }
        .add-more-pay-btn:hover { background: var(--success); color: #fff; border-color: var(--success); }

        /* pay block number badge */
        .pay-num {
            background: var(--theme); color: #fff; width: 20px; height: 20px;
            border-radius: 50%; font-size: .72rem; font-weight: 700;
            display: inline-flex; align-items: center; justify-content: center;
        }
    </style>
@endpush

@section('content')
    @if (session('message'))
        <div class="alert alert-success alert-dismissible text-center" style="border-radius:8px;">
            <button class="close" data-dismiss="alert">&times;</button>{{ session('message') }}
        </div>
    @endif

    <div class="sp-ov" id="sp"><div class="sp-ring"></div></div>

    {{-- Hidden template for a payment block (cloned by JS) --}}
    <template id="pay-block-tpl">
        <div class="pay-block pay-block-item" data-idx="0">
            <div class="pay-block-title">
                <span class="pay-num">1</span>
                <span>{{ __('db.Payment') }}</span>
            </div>
            <button type="button" class="remove-pay-block" title="Remove"><i class="ti ti-trash"></i></button>
            <div class="pay-row">
                <div class="pay-field">
                    <label>{{ __('db.Amount') }} *</label>
                    <input type="number" class="pa" placeholder="0.00" step="any" min="0.01" />
                </div>
                <div class="pay-field">
                    <label>{{ __('db.Method') }} *</label>
                    <select class="pm">
                        @if(in_array('cash', $payment_options ?? []))
                            <option value="Cash">{{ __('db.Cash') }}</option>
                        @endif
                        @if(in_array('cheque', $payment_options ?? []))
                            <option value="Cheque">{{ __('db.Cheque') }}</option>
                        @endif
                        @if(in_array('card', $payment_options ?? []))
                            <option value="Card">{{ __('db.Card') }}</option>
                        @endif
                        @if(in_array('bkash', $payment_options ?? []))
                            <option value="bKash">bKash</option>
                        @endif
                        @if(in_array('nagad', $payment_options ?? []))
                            <option value="Nagad">Nagad</option>
                        @endif
                        @if(in_array('rocket', $payment_options ?? []))
                            <option value="Rocket">Rocket</option>
                        @endif
                        @if(in_array('bank_transfer', $payment_options ?? []))
                            <option value="Bank Transfer">{{ __('db.bank_transfer') }}</option>
                        @endif
                        @foreach($payment_options ?? [] as $opt)
                            @if(!in_array($opt, ['cash','cheque','card','bkash','nagad','rocket','bank_transfer','gift_card','deposit','paypal','pesapal']))
                                <option value="{{ ucfirst($opt) }}">{{ ucfirst($opt) }}</option>
                            @endif
                        @endforeach
                        {{-- fallback if pos setting empty --}}
                        @if(empty($payment_options))
                            <option value="Cash">Cash</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Card">Card</option>
                            <option value="bKash">bKash</option>
                            <option value="Nagad">Nagad</option>
                            <option value="Rocket">Rocket</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        @endif
                    </select>
                </div>
                <div class="pay-field">
                    <label>{{ __('db.Account') }} *</label>
                    <select class="pact">
                        @foreach ($lims_account_list as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pay-field">
                    <label>{{ __('db.reference') }}</label>
                    <input type="text" class="pr" placeholder="{{ __('db.payment_ref_placeholder') }}" />
                </div>
            </div>
            <div class="mt-2">
                <span class="pay-err text-danger" style="font-size:.82rem;"></span>
            </div>
        </div>
    </template>

    <section class="pb-page">
        <div class="container-fluid">

            {{-- Page Header --}}
            <div class="page-hdr">
                <div>
                    <div class="page-hdr-title">
                        <i class="ti ti-wrench"></i>&nbsp; {{ __('db.parts_billing') }}
                        <span style="font-weight:400;color:var(--text-muted);font-size:.85rem;margin-left:6px;">{{ $job->reference_no }}</span>
                    </div>
                    <div class="page-hdr-meta">
                        {{ __('db.customer') }}: <strong>{{ optional($job->customer)->name }}</strong>
                        &nbsp;·&nbsp; {{ __('db.Warehouse') }}: <strong>{{ optional($job->warehouse)->name }}</strong>
                        &nbsp;·&nbsp; {!! $job->status_badge !!} {!! $job->priority_badge !!}
                    </div>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="{{ route('repair.service.show', $job->id) }}" class="btn-pb outline"><i class="ti ti-eye"></i> {{ __('db.view_job') }}</a>
                    <a href="{{ route('repair.service.index') }}" class="btn-pb secondary"><i class="ti ti-list"></i> {{ __('db.all_jobs') }}</a>
                    <button onclick="openPrintModal()" class="btn-pb secondary"><i class="ti ti-printer"></i> {{ __('db.print') }}</button>
                </div>
            </div>


            <div class="row">
                <div class="col-md-8">

                    {{-- PARTS --}}
                    <div class="pb-card">
                        <div class="pb-card-head purple">
                            <i class="ti ti-cogs"></i> {{ __('db.parts_items_used') }}
                        </div>
                        <div class="pb-card-body">
                            <div class="search-wrap">
                                <div class="search-box">
                                    <i class="ti ti-search si"></i>
                                    <input type="text" id="product-search" placeholder="{{ __('db.search_product') }}" autocomplete="off" />
                                </div>
                                <span class="search-hint">{{ __('db.duplicates_merge') }}</span>
                            </div>

                            <div id="dup-notice">
                                <i class="ti ti-info-circle"></i>
                                <span id="dup-msg">{{ __('db.product_already_added') }}</span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered mb-0" id="parts-table">
                                    <thead>
                                        <tr>
                                            <th width="34">#</th>
                                            <th>{{ __('db.product') }}</th>
                                            <th width="148">{{ __('db.Quantity') }}</th>
                                            <th width="110">{{ __('db.Unit Price') }}</th>
                                            <th width="95">{{ __('db.Total') }}</th>
                                            <th width="42"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="parts-tbody">
                                        @forelse($job->items as $idx => $item)
                                            <tr data-item-id="{{ $item->id }}" data-product-id="{{ $item->product_id }}" data-stock="{{ optional($item->product)->qty ?? 9999 }}">
                                                <td class="rn text-muted">{{ $idx + 1 }}</td>
                                                <td>
                                                    <div style="font-weight:600;color:var(--text);">{{ optional($item->product)->name }}</div>
                                                    <div style="font-size:.76rem;color:var(--text-muted);">{{ optional($item->product)->code }}</div>
                                                </td>
                                                <td>
                                                    <div class="qty-wrap">
                                                        <button type="button" class="qty-btn qm">−</button>
                                                        <input type="number" class="qty-input qi" value="{{ $item->quantity }}" min="0.01" step="any" data-item="{{ $item->id }}" data-price="{{ $item->unit_price }}" />
                                                        <button type="button" class="qty-btn qp">+</button>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="number" class="price-input pi" value="{{ $item->unit_price }}" data-item="{{ $item->id }}" step="any" min="0" />
                                                </td>
                                                <td class="pt" style="font-weight:700;color:var(--theme-dk);">{{ number_format($item->total, config('decimal')) }}</td>
                                                <td>
                                                    <button type="button" class="btn-pb danger sm rp" data-item="{{ $item->id }}"><i class="ti ti-trash"></i></button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr id="epr">
                                                <td colspan="6">
                                                    <div class="empty-state">
                                                        <i class="ti ti-cubes"></i>{{ __('db.search_product_to_add') }}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-right">{{ __('db.parts_total') }}</td>
                                            <td id="ft-parts" colspan="2">{{ number_format($job->items->sum('total'), config('decimal')) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- SERVICE CHARGES --}}
                    <div class="pb-card">
                        <div class="pb-card-head slate">
                            <i class="ti ti-calculator"></i> {{ __('db.service_charges') }}
                        </div>
                        <div class="pb-card-body">
                            <div class="charge-row">
                                <div class="charge-field">
                                    <label>{{ __('db.service_charge') }}</label>
                                    <input type="number" id="sc" class="charge-inp" value="{{ $job->service_charge }}" step="any" min="0" />
                                </div>
                                <div class="charge-field">
                                    <label>{{ __('db.Discount') }}</label>
                                    <input type="number" id="dc" class="charge-inp" value="{{ $job->discount }}" step="any" min="0" />
                                </div>
                                <div class="charge-field">
                                    <label>{{ __('db.Tax') }}</label>
                                    <input type="number" id="tx" class="charge-inp" value="{{ $job->tax }}" step="any" min="0" />
                                </div>
                            </div>
                            <button type="button" id="save-ch" class="btn-pb primary">
                                <i class="ti ti-check"></i> {{ __('db.save_charges') }}
                            </button>
                            <span class="text-muted" style="font-size:.79rem;margin-left:8px;">{{ __('db.totals_update_after_saving') }}</span>
                        </div>
                    </div>

                    {{-- PAYMENTS --}}
                    <div class="pb-card">
                        <div class="pb-card-head dark">
                            <i class="ti ti-money"></i> {{ __('db.payments_received') }}
                        </div>
                        <div class="pb-card-body">

                            {{-- Payment Blocks --}}
                            <div id="pay-blocks-wrap">
                                {{-- First block injected by JS on ready --}}
                            </div>

                            <div class="d-flex align-items-center gap-2 mb-3" style="gap:10px;">
                                <button type="button" id="add-more-pay" class="add-more-pay-btn">
                                    <i class="ti ti-plus-circle"></i> {{ __('db.Add More Payment') }}
                                </button>
                                <button type="button" id="collect-all-pay" class="btn-pb primary">
                                    <i class="ti ti-check"></i> {{ __('db.collect_payment') }}
                                </button>
                                <span id="pay-err-global" class="text-danger" style="font-size:.82rem;"></span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered mb-0" id="payments-table">
                                    <thead>
                                        <tr>
                                            <th width="34">#</th>
                                            <th>{{ __('db.date') }}</th>
                                            <th>{{ __('db.Amount') }}</th>
                                            <th>{{ __('db.Method') }}</th>
                                            <th>{{ __('db.Account') }}</th>
                                            <th>{{ __('db.reference') }}</th>
                                            <th>{{ __('db.note') }}</th>
                                            <th width="42"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="pay-tbody">
                                        @forelse($job->payments as $idx => $p)
                                            <tr data-pid="{{ $p->id }}">
                                                <td class="text-muted">{{ $idx + 1 }}</td>
                                                <td>{{ $p->payment_at ? $p->payment_at->format(config('date_format')) : '—' }}</td>
                                                <td style="font-weight:700;color:var(--success);">{{ number_format($p->amount, config('decimal')) }}</td>
                                                <td><span class="badge badge-secondary">{{ $p->paying_method }}</span></td>
                                                <td>
                                                    @if($p->account_id)
                                                        <span style="font-size:.82rem;color:var(--text);">
                                                            <i class="ti ti-university" style="color:var(--theme);opacity:.7;margin-right:3px;"></i>
                                                            {{ optional($p->account)->name ?? '—' }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>{{ $p->payment_reference ?? '—' }}</td>
                                                <td>{{ $p->payment_note ?? '—' }}</td>
                                                <td>
                                                    <button type="button" class="btn-pb danger sm rpy" data-payment="{{ $p->id }}"><i class="ti ti-trash"></i></button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr id="epr-pay">
                                                <td colspan="8">
                                                    <div class="empty-state">
                                                        <i class="ti ti-credit-card"></i>{{ __('db.no_payments_yet') }}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="2" class="text-right" style="color:var(--slate);font-weight:700;">{{ __('db.Paid Amount') }}</td>
                                            <td id="ft-paid" style="color:var(--success);font-weight:700;">{{ number_format($job->payments->sum('amount'), config('decimal')) }}</td>
                                            <td colspan="5"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>{{-- col-md-8 --}}

                <div class="col-md-4">

                    {{-- Billing Summary --}}
                    <div class="sum-panel">
                        <div class="sum-head"><i class="ti ti-file-type-csv"></i> {{ __('db.billing_summary') }}</div>
                        <div class="sum-row">
                            <span class="lbl">{{ __('db.parts_total') }}</span>
                            <span id="s-parts" class="val">{{ number_format($job->items->sum('total'), config('decimal')) }}</span>
                        </div>
                        <div class="sum-row">
                            <span class="lbl">{{ __('db.service_charge') }}</span>
                            <span id="s-sc" class="val">{{ number_format($job->service_charge, config('decimal')) }}</span>
                        </div>
                        <div class="sum-row">
                            <span class="lbl">{{ __('db.Discount') }}</span>
                            <span id="s-dc" class="val" style="color:var(--danger);">− {{ number_format($job->discount, config('decimal')) }}</span>
                        </div>
                        <div class="sum-row">
                            <span class="lbl">{{ __('db.Tax') }}</span>
                            <span id="s-tx" class="val">{{ number_format($job->tax, config('decimal')) }}</span>
                        </div>
                        <div class="sum-row grand">
                            <span class="lbl">{{ __('db.grand total') }}</span>
                            <span id="s-gt" class="val">{{ number_format($job->total_amount, config('decimal')) }}</span>
                        </div>
                        <div class="sum-row paid-r">
                            <span class="lbl">{{ __('db.Paid Amount') }}</span>
                            <span id="s-paid" class="val">{{ number_format($job->paid_amount, config('decimal')) }}</span>
                        </div>
                        <div class="sum-row due-r {{ $job->due_amount <= 0 ? 'ok' : '' }}" id="due-r">
                            <span class="lbl">{{ __('db.Due') }}</span>
                            <span id="s-due" class="val">{{ number_format($job->due_amount, config('decimal')) }}</span>
                        </div>
                        <div class="sum-badge-wrap">
                            <span id="s-badge" class="sum-badge {{ $job->due_amount <= 0 ? 'clear' : 'due' }}">
                                @if($job->due_amount <= 0)
                                    <i class="ti ti-shield-check"></i> {{ __('db.fully_paid') }}
                                @else
                                    <i class="ti ti-exclamation-circle"></i> {{ __('db.amount_due') }}
                                @endif
                            </span>
                        </div>
                    </div>

                    {{-- Job Info --}}
                    <div class="sum-panel">
                        <div class="sum-head" style="background:var(--slate);"><i class="ti ti-info-circle"></i> {{ __('db.job_info') }}</div>
                        <table class="ji-table">
                            <tr><th>{{ __('db.reference') }}</th><td>{{ $job->reference_no }}</td></tr>
                            <tr><th>{{ __('db.Type') }}</th><td>{{ ucfirst($job->service_type) }}</td></tr>
                            <tr><th>{{ __('db.Title') }}</th><td>{{ $job->title }}</td></tr>
                            <tr><th>{{ __('db.customer') }}</th><td>{{ optional($job->customer)->name }}</td></tr>
                            <tr><th>{{ __('db.Warehouse') }}</th><td>{{ optional($job->warehouse)->name }}</td></tr>
                            <tr><th>{{ __('db.status') }}</th><td>{!! $job->status_badge !!}</td></tr>
                            @if($job->expected_delivery_date)
                                <tr><th>{{ __('db.expected_delivery') }}</th><td>{{ $job->expected_delivery_date->format(config('date_format')) }}</td></tr>
                            @endif
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </section>

    {{-- Print Modal --}}
    <div id="print-modal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header d-print-none py-2">
                    <button id="do-print-btn" type="button" class="btn btn-default btn-sm">
                        <i class="ti ti-printer"></i> {{ __('db.print') }}
                    </button>
                    <button type="button" data-dismiss="modal" class="close ml-auto"><span><i class="ti ti-x"></i></span></button>
                </div>
                <div class="modal-body" id="print-modal-body">
                    @include('repair::service.partials.print_content', ['job' => $job, 'general_setting' => $general_setting])
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    var JOB   = {{ $job->id }};
    var PC    = {{ $job->items->count() }};
    var PYC   = {{ $job->payments->count() }};
    var DEC   = {{ config('decimal') }};
    var QTY_TMR = null;

    /* ═══════════════════════════════════════════
       PAYMENT BLOCKS
    ═══════════════════════════════════════════ */
    var payBlockCount = 0;

    function clonePayBlock() {
        payBlockCount++;
        var tpl  = document.getElementById('pay-block-tpl');
        var node = tpl.content.cloneNode(true);
        var $blk = $(node.querySelector('.pay-block-item'));

        $blk.attr('data-idx', payBlockCount);
        $blk.find('.pay-num').text(payBlockCount);

        // First block cannot be removed
        if (payBlockCount === 1) {
            $blk.find('.remove-pay-block').hide();
        }

        $('#pay-blocks-wrap').append($blk);
        return payBlockCount;
    }

    // Init first block
    $(function () { clonePayBlock(); });

    // Add more
    $('#add-more-pay').on('click', function () {
        var due = parseFloat($('#s-due').text().replace(/,/g,'')) || 0;
        if (due <= 0) {
            alert('{{ __("db.amount_due") ?? "No amount due." }}');
            return;
        }
        clonePayBlock();
        renumberBlocks();
    });

    // Remove a block
    $(document).on('click', '.remove-pay-block', function () {
        $(this).closest('.pay-block-item').remove();
        renumberBlocks();
    });

    function renumberBlocks() {
        $('#pay-blocks-wrap .pay-block-item').each(function (i) {
            $(this).find('.pay-num').text(i + 1);
            $(this).find('.remove-pay-block').toggle(i > 0);
        });
    }

    /* ═══════════════════════════════════════════
       COLLECT ALL PAYMENTS
    ═══════════════════════════════════════════ */
    $('#collect-all-pay').on('click', function () {
        var $blocks = $('#pay-blocks-wrap .pay-block-item');
        var payments = [];
        var hasErr = false;
        var totalEntered = 0;

        $blocks.each(function () {
            var $b = $(this);
            var amt  = parseFloat($b.find('.pa').val()) || 0;
            var meth = $b.find('.pm').val();
            var acct = $b.find('.pact').val();
            var ref  = $b.find('.pr').val();
            $b.find('.pay-err').text('');

            if (amt <= 0) { $b.find('.pay-err').text('{{ __("db.enter_valid_amount") }}'); hasErr = true; return; }
            if (!acct)    { $b.find('.pay-err').text('{{ __("db.select_account") }}');     hasErr = true; return; }

            totalEntered += amt;
            payments.push({ amount: amt, paying_method: meth, account_id: acct, payment_reference: ref });
        });

        if (hasErr) return;

        var due = parseFloat($('#s-due').text().replace(/,/g,'')) || 0;
        if (totalEntered > due + 0.001) {
            $('#pay-err-global').text('{{ __("db.amount_exceeds_due") ?? "Total entered exceeds due amount." }} (' + $('#s-due').text() + ')');
            return;
        }
        $('#pay-err-global').text('');

        sp(true);

        // Send payments sequentially
        var chain = Promise.resolve();
        payments.forEach(function (pay) {
            chain = chain.then(function () {
                return $.post('{{ route("repair.service.add-payment", $job->id) }}', pay)
                    .then(function (res) {
                        if (res.success) {
                            addPayRow(res.payment);
                            sync(res.job_totals);
                        }
                    });
            });
        });

        chain.then(function () {
            sp(false);
            // Clear all blocks and start fresh
            $('#pay-blocks-wrap').empty();
            payBlockCount = 0;
            clonePayBlock();
            toast('{{ __("db.payment_collected") }}');
        }).catch(function (xhr) {
            sp(false);
            var msg = xhr.responseJSON ? xhr.responseJSON.message : '{{ __("db.Something went wrong") }}';
            $('#pay-err-global').text(msg);
        });
    });

    function addPayRow(p) {
        $('#epr-pay').remove();
        PYC++;
        $('#pay-tbody').append(
            '<tr data-pid="'+ p.id +'">' +
            '<td class="text-muted">'+ PYC +'</td>' +
            '<td>'+ p.date +'</td>' +
            '<td style="font-weight:700;color:var(--success);">'+ p.amount +'</td>' +
            '<td><span class="badge badge-secondary">'+ p.method +'</span></td>' +
            '<td><span style="font-size:.82rem;"><i class="ti ti-university" style="color:var(--theme);opacity:.7;margin-right:3px;"></i>'+ (p.account || '—') +'</span></td>' +
            '<td>'+ (p.reference || '—') +'</td>' +
            '<td>'+ (p.note || '—') +'</td>' +
            '<td><button type="button" class="btn-pb danger sm rpy" data-payment="'+ p.id +'"><i class="ti ti-trash"></i></button></td>' +
            '</tr>'
        );
    }

    $(document).on('click', '.rpy', function () {
        if (!confirm('{{ __("db.delete_payment_confirm") }}')) return;
        var pid  = $(this).data('payment'), $row = $(this).closest('tr');
        sp(true);
        $.post('{{ route("repair.service.delete-payment", ["id" => ":id", "paymentId" => ":pid"]) }}'.replace(':id', JOB).replace(':pid', pid), { _method: 'DELETE' }, function (res) {
            if (res.success) {
                $row.remove(); renum('#pay-tbody'); sync(res.job_totals);
                if (!$('#pay-tbody tr').length)
                    $('#pay-tbody').append('<tr id="epr-pay"><td colspan="7"><div class="empty-state"><i class="ti ti-credit-card"></i>{{ __("db.no_payments_yet") }}</div></td></tr>');
                toast('{{ __("db.payment_removed") }}');
            }
        }).always(function () { sp(false); });
    });

    /* ═══════════════════════════════════════════
       PRODUCT SEARCH (AUTOCOMPLETE)
    ═══════════════════════════════════════════ */
    $('#product-search').autocomplete({
        source: function (req, res) {
            var m = new RegExp('.?' + $.ui.autocomplete.escapeRegex(req.term), 'i');
            res($.grep([
                @foreach ($productArray as $p)
                    "{{ $p }}",
                @endforeach
            ], function (x) { return m.test(x); }));
        },
        minLength: 1,
        select: function (e, ui) {
            e.preventDefault();
            $('#product-search').val('').prop('disabled', true);
            sp(true);
            $.get('{{ route('repair.products.search') }}', { data: ui.item.value }, function (res) {
                if (!res || !res[0]) { sp(false); $('#product-search').prop('disabled', false); return; }
                var d = res[0];
                $.post('{{ route("repair.service.add-part", $job->id) }}', { product_id: d[8], quantity: 1, unit_price: d[2] }, function (r) {
                    if (r.success) { addRow(r.item, d[8], d[7] ?? 9999); sync(r.job_totals); toast('Part added!'); }
                }).fail(function (xhr) {
                    alert(xhr.responseJSON ? xhr.responseJSON.message : 'Error adding part.');
                }).always(function () { sp(false); $('#product-search').prop('disabled', false).focus(); });
            });
        }
    });

    function addRow(item, productId, stock) {
        $('#epr').remove(); PC++;
        var uPrice = parseFloat(String(item.unit_price).replace(/,/g,'')) || 0;
        $('#parts-tbody').append(
            '<tr data-item-id="'+ item.id +'" data-product-id="'+ productId +'" data-stock="'+ stock +'">' +
            '<td class="rn text-muted">'+ PC +'</td>' +
            '<td><div style="font-weight:600;color:var(--text);">'+ item.product_name +'</div>' +
            '<div style="font-size:.76rem;color:var(--text-muted);">'+ productId +'</div></td>' +
            '<td><div class="qty-wrap">' +
            '<button type="button" class="qty-btn qm">−</button>' +
            '<input type="number" class="qty-input qi" value="'+ item.quantity +'" min="0.01" step="any" data-item="'+ item.id +'" data-price="'+ uPrice +'"/>' +
            '<button type="button" class="qty-btn qp">+</button>' +
            '</div></td>' +
            '<td><input type="number" class="price-input pi" value="'+ uPrice +'" data-item="'+ item.id +'" step="any" min="0"/></td>' +
            '<td class="pt" style="font-weight:700;color:var(--theme-dk);">'+ item.total +'</td>' +
            '<td><button type="button" class="btn-pb danger sm rp" data-item="'+ item.id +'"><i class="ti ti-trash"></i></button></td>' +
            '</tr>'
        );
    }

    /* ═══════════════════════════════════════════
       QTY / PRICE CONTROLS
    ═══════════════════════════════════════════ */
    $(document).on('click', '.qm', function () {
        var $i = $(this).siblings('.qi');
        $i.val(Math.max(0.01, parseFloat(($i.val() || 1) - 1).toFixed(4))).trigger('qty-change');
    });
    $(document).on('click', '.qp', function () {
        var $i = $(this).siblings('.qi'), stock = parseFloat($i.closest('tr').data('stock')) || 9999;
        var nv = parseFloat((parseFloat($i.val() || 0) + 1).toFixed(4));
        if (nv > stock) { alert('{{ __("db.stock_exceeded") }} (' + stock + ')'); return; }
        $i.val(nv).trigger('qty-change');
    });
    $(document).on('input', '.qi', function () { $(this).trigger('qty-change'); });
    $(document).on('qty-change', '.qi', function () {
        var $i = $(this), itemId = $i.data('item'), price = parseFloat($i.data('price')) || 0;
        var qty = parseFloat($i.val()) || 0, stock = parseFloat($i.closest('tr').data('stock')) || 9999, $row = $i.closest('tr');
        if (qty < 0.01) { qty = 0.01; $i.val(qty); }
        if (qty > stock) { qty = stock; $i.val(qty); }
        $row.find('.pt').text((qty * price).toFixed(DEC));
        clearTimeout(QTY_TMR);
        QTY_TMR = setTimeout(function () { doUpdatePart(itemId, qty, price, $row); }, 700);
    });

    $(document).on('change blur', '.pi', function () {
        var $i = $(this), itemId = $i.data('item'), price = parseFloat($i.val()) || 0, $row = $i.closest('tr');
        var qty = parseFloat($row.find('.qi').val()) || 1;
        $row.find('.qi').data('price', price);
        $row.find('.pt').text((qty * price).toFixed(DEC));
        doUpdatePart(itemId, qty, price, $row);
    });

    function doUpdatePart(itemId, qty, price, $row) {
        $row.addClass('saving');
        $.post('{{ route("repair.service.update-part", ["id" => ":id", "itemId" => ":itemId"]) }}'.replace(':id', JOB).replace(':itemId', itemId), { quantity: qty, unit_price: price }, function (res) {
            if (res.success) { $row.find('.pt').text(res.item_total); sync(res.job_totals); }
        }).fail(function (xhr) {
            alert(xhr.responseJSON ? xhr.responseJSON.message : '{{ __("db.Something went wrong") }}');
        }).always(function () { $row.removeClass('saving'); });
    }

    $(document).on('click', '.rp', function () {
        if (!confirm('{{ __("db.remove_part_confirm") }}')) return;
        var itemId = $(this).data('item'), $row = $(this).closest('tr');
        sp(true);
        $.post('{{ route("repair.service.remove-part", ["id" => ":id", "itemId" => ":itemId"]) }}'.replace(':id', JOB).replace(':itemId', itemId), { _method: 'DELETE' }, function (res) {
            if (res.success) {
                $row.remove(); renum('#parts-tbody'); sync(res.job_totals);
                if (!$('#parts-tbody tr').length)
                    $('#parts-tbody').append('<tr id="epr"><td colspan="6"><div class="empty-state"><i class="ti ti-cubes"></i>{{ __("db.search_product_to_add") }}</div></td></tr>');
                toast('{{ __("db.part_removed") }}');
            }
        }).always(function () { sp(false); });
    });

    /* ═══════════════════════════════════════════
       CHARGES
    ═══════════════════════════════════════════ */
    $('#save-ch').on('click', function () {
        sp(true);
        $.post('{{ route("repair.service.update-charges", $job->id) }}', {
            service_charge: $('#sc').val(), discount: $('#dc').val(), tax: $('#tx').val()
        }, function (res) {
            if (res.success) { sync(res.job_totals); toast('{{ __("db.charges_saved") }}'); }
        }).always(function () { sp(false); });
    });

    /* ═══════════════════════════════════════════
       SYNC SUMMARY
    ═══════════════════════════════════════════ */
    function sync(t) {
        $('#ft-parts').text(t.parts_total);
        $('#ft-paid').text(t.paid_amount);
        $('#s-parts').text(t.parts_total);
        $('#s-sc').text(t.service_charge);
        $('#s-dc').text('− ' + t.discount);
        $('#s-tx').text(t.tax);
        $('#s-gt').text(t.total_amount);
        $('#s-paid').text(t.paid_amount);
        $('#s-due').text(t.due_amount);
        var due = parseFloat(String(t.due_amount).replace(/,/g,'')) || 0;
        if (due < 0.001) {
            $('#due-r').addClass('ok');
            $('#s-badge').removeClass('due').addClass('clear').html('<i class="ti ti-shield-check"></i> {{ __("db.fully_paid") }}');
        } else {
            $('#due-r').removeClass('ok');
            $('#s-badge').removeClass('clear').addClass('due').html('<i class="ti ti-exclamation-circle"></i> {{ __("db.amount_due") }}');
        }
    }

    /* ═══════════════════════════════════════════
       HELPERS
    ═══════════════════════════════════════════ */
    function sp(on) { on ? $('#sp').addClass('on') : $('#sp').removeClass('on'); }
    function renum(sel) { $(sel + ' tr').each(function (i) { $(this).find('.rn, td:first-child').first().text(i + 1); }); }
    function toast(m) { if (typeof toastr !== 'undefined') toastr.success(m); }

    $("ul#repair").siblings('a').attr('aria-expanded', 'true');
    $("ul#repair").addClass("show");
    $("ul#repair #service-list-menu").addClass("active");

    /* ═══════════════════════════════════════════
       PRINT
    ═══════════════════════════════════════════ */
    function openPrintModal() {
        $('#print-modal').modal('show');
    }

    $('#do-print-btn').on('click', function () {
        var content = document.getElementById('print-modal-body').innerHTML;
        var a = window.open('');
        a.document.write('<html><head><title>{{ addslashes($general_setting->site_title) }}</title></head><body style="margin:20px;">');
        a.document.write(content);
        a.document.write('</body></html>');
        a.document.close();
        setTimeout(function () { a.print(); a.close(); }, 600);
    });
</script>
@endpush
