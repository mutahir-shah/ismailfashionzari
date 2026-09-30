{{-- ====================== INVOICE-STYLE SALE DETAILS MODAL ====================== --}}
<style id="invoice-style">
/* ===== Action Dropdown Fix ===== */
.sale-list .dropdown-menu.edit-options {
    min-width: 250px !important;
}
.sale-list .dropdown-menu.edit-options > li > a,
.sale-list .dropdown-menu.edit-options > li > button {
    white-space: nowrap !important;
}

/* ===== Invoice Modal Styles ===== */
#sale-details .modal-dialog { max-width: 880px; width: 96%; }
#sale-details .modal-content {
    border-radius: 0; border: none;
    font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #222;
}

/* ---- ACTION BAR (icon-only toolbar) ---- */
#inv-action-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 7px 14px;
    background: #f0f4ff;
    border-bottom: 1px solid #d5ddf0;
}
#inv-action-bar .inv-btn-group { display: flex; gap: 4px; align-items: center; }
#inv-action-bar .inv-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 34px; height: 34px; border-radius: 6px; border: 1px solid #c8d3ea;
    background: #fff; color: #3a5090; font-size: 18px; cursor: pointer;
    transition: background 0.15s, color 0.15s;
    position: relative;
}
#inv-action-bar .inv-btn:hover { background: #3a5090; color: #fff; border-color: #3a5090; }
#inv-action-bar .inv-btn[title]:hover::after {
    content: attr(title);
    position: absolute; bottom: -28px; left: 50%; transform: translateX(-50%);
    background: #333; color: #fff; font-size: 11px; padding: 2px 8px;
    border-radius: 4px; white-space: nowrap; pointer-events: none; z-index: 99;
}
#inv-action-bar .inv-close {
    font-size: 20px; color: #888; background: none; border: none;
    cursor: pointer; line-height: 1; padding: 2px 6px; border-radius: 4px;
}
#inv-action-bar .inv-close:hover { color: #c0392b; background: #fde8e8; }

/* ---- INVOICE WRAPPER ---- */
#invoice-wrapper { padding: 22px 30px 18px 30px; background: #fff; }

/* ---- 3-COLUMN HEADER (Logo | Labels | Values+Title) ---- */
#invoice-header {
    display: grid;
    grid-template-columns: 150px 1fr 1fr;
    align-items: center;
    gap: 0;
    margin-bottom: 12px;
    min-height: 90px;
}
#invoice-logo-area img {
    max-width: 140px; max-height: 72px; object-fit: contain; display: block;
}
#invoice-meta-labels {
    text-align: right;
    padding-right: 10px;
    line-height: 2;
}
#invoice-meta-labels span {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #555;
}
#invoice-meta-right {
    text-align: right;
    padding-left: 10px;
    border-left: 2px solid #e0e7f3;
}
#invoice-title-text {
    font-size: 22px; font-weight: 800;
    color: #1a3a6b; letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 4px;
    line-height: 1.1;
}
#invoice-ref-badge {
    display: inline-block;
    background: #e8edf5; color: #1a3a6b;
    font-weight: 700; font-size: 13px;
    padding: 2px 12px; border-radius: 4px;
    margin-bottom: 4px;
}
#invoice-meta-right .meta-val-row {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #222;
    line-height: 2;
    text-align: right;
}

/* ---- BADGES (lighter / softer) ---- */
.inv-badge {
    display: inline-block; padding: 1px 9px; border-radius: 10px;
    font-size: 11px; font-weight: 600; letter-spacing: 0.3px; text-transform: uppercase;
}
.inv-badge-completed  { background: #e8f8ee; color: #2e7d52; border: 1px solid #b7e4c7; }
.inv-badge-pending    { background: #fffbea; color: #9a6e00; border: 1px solid #ffe48c; }
.inv-badge-returned   { background: #fdf0f0; color: #a94442; border: 1px solid #f5baba; }
.inv-badge-processing { background: #eaf4ff; color: #1a6eb5; border: 1px solid #b3d7f5; }
.inv-badge-paid       { background: #e8f8ee; color: #2e7d52; border: 1px solid #b7e4c7; }
.inv-badge-due        { background: #fdf0f0; color: #a94442; border: 1px solid #f5baba; }
.inv-badge-partial    { background: #fffbea; color: #9a6e00; border: 1px solid #ffe48c; }
.inv-badge-default    { background: #f2f3f5; color: #555;    border: 1px solid #dde0e6; }

/* ---- DIVIDER ---- */
.inv-divider { border: none; border-top: 2px solid #1a3a6b; margin: 10px 0; }

/* ---- ADDRESS BOXES ---- */
.inv-address-row { display: flex; gap: 14px; margin-bottom: 13px; }
.inv-address-box { flex: 1; border: 1px solid #dde3ee; border-radius: 4px; overflow: hidden; }
.inv-address-header { background: #1a3a6b; color: #fff; font-weight: 700; font-size: 12px; padding: 5px 12px; letter-spacing: 0.5px; text-transform: uppercase; }
.inv-address-body { padding: 9px 13px; font-size: 13px; line-height: 1.7; }
.inv-address-body strong { display: block; font-size: 14px; font-weight: 700; margin-bottom: 2px; }
.inv-address-body .addr-row { display: flex; gap: 4px; }
.inv-address-body .addr-lbl { color: #666; min-width: 65px; font-weight: 600; }

/* ---- PRODUCT TABLE ---- */
.inv-product-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
.inv-product-table thead tr { background: #1a3a6b; color: #fff; }
.inv-product-table thead th { padding: 8px 10px; font-size: 12px; font-weight: 700; letter-spacing: 0.4px; text-transform: uppercase; border: none; }
.inv-product-table tbody tr { border-bottom: 1px solid #e8edf5; }
.inv-product-table tbody tr:nth-child(even) { background: #f7f9fc; }
.inv-product-table tbody td { padding: 7px 10px; font-size: 13px; border: none; vertical-align: top; }
.inv-product-table td.disc-red { color: #c0392b; font-weight: 600; }

/* ---- TOTALS ---- */
.inv-totals-row { display: flex; justify-content: space-between; align-items: flex-end; gap: 14px; margin-top: 10px; }
.inv-inwords-box {
    flex: 1; background: #eef2fa; border: 1px solid #d0d9ee; border-radius: 4px; padding: 10px 14px;
    display: flex; flex-direction: column; justify-content: center; min-height: 50px;
}
.inv-inwords-box .inwords-title { font-weight: 700; font-size: 13px; margin-bottom: 4px; color: #1a3a6b; }
.inv-inwords-box .inwords-text { font-size: 12px; color: #333; }
.inv-totals-box { width: 340px; }
.inv-totals-table { width: 100%; border-collapse: collapse; }
.inv-totals-table td { padding: 5px 10px; font-size: 13px; border: 1px solid #e2e8f0; }
.inv-totals-table .tlbl { color: #444; width: 50%; }
.inv-totals-table .tval { text-align: right; font-weight: 600; }
.inv-totals-table .tval.disc { color: #c0392b; }
.inv-grand-row td { background: #1a3a6b; color: #fff; font-size: 14px; font-weight: 700; }
.inv-paid-row td { background: #eafaf1; color: #155724; font-weight: 700; }
.inv-due-row td { background: #fffde7; color: #856404; font-weight: 700; }

/* ---- BARCODE + QR (centered) ---- */
.inv-codes-row { text-align: center; margin-top: 16px; padding-top: 12px; border-top: 1px solid #e2e8f0; }
.inv-codes-row .barcode-wrap { margin-bottom: 10px; }
.inv-codes-row svg, .inv-codes-row canvas { display: inline-block; }
#inv-qrcode { display: flex; justify-content: center; margin-top: 4px; }
#inv-qrcode canvas, #inv-qrcode img { display: block; margin: 0 auto; }

/* ---- THANK YOU ---- */
.inv-thankyou { text-align: left; color: #555; font-size: 12px; margin-top: 10px; font-style: italic; }

@media print {
    #inv-action-bar { display: none !important; }
    #sale-details .modal-dialog { max-width: 100%; }
    #invoice-wrapper { padding: 10px; }
}
</style>

<div id="sale-details" tabindex="-1" role="dialog" aria-labelledby="saleDetailsLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">

            {{-- Action Bar — icon-only toolbar (hidden on print) --}}
            <div id="inv-action-bar" class="d-print-none">
                <div class="inv-btn-group">
                    {{-- Fixed Buttons --}}
                    <button id="print-btn" type="button" class="inv-btn" title="{{__('db.print')}}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-printer"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" /><path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" /><path d="M7 15a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2l0 -4" /></svg>
                    </button>
                    <form action="{{ route('sale.sendmail') }}" method="POST" class="sendmail-form" style="margin:0;">
                        @csrf
                        <input type="hidden" name="sale_id">
                        <button type="submit" class="inv-btn" title="{{__('db.email')}}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-mail"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10" /><path d="M3 7l9 6l9 -6" /></svg>
                        </button>
                    </form>

                    {{-- Dynamic Buttons (shown via JS based on row options) --}}
                    <a href="#" id="inv-edit-btn" class="inv-btn" title="{{__('db.edit')}}" style="display:none;"><i class="ti ti-edit"></i></a>
                    <a href="#" id="inv-installment-btn" class="inv-btn" title="{{__('db.Installment Plan')}}" style="display:none;"><i class="ti ti-info-circle"></i></a>
                    <button type="button" id="inv-packing-slip-btn" class="inv-btn create-packing-slip-btn" title="{{__('db.Create Packing Slip')}}" style="display:none;" data-toggle="modal" data-target="#packing-slip-modal"><i class="ti ti-box"></i></button>
                    <button type="button" id="inv-get-payment-btn" class="inv-btn get-payment" title="{{__('db.View Payment')}}" style="display:none;"><i class="ti ti-cash-banknote"></i></button>
                    <button type="button" id="inv-add-payment-btn" class="inv-btn add-payment" title="{{__('db.Add Payment')}}" style="display:none;" data-toggle="modal" data-target="#add-payment"><i class="ti ti-plus"></i></button>
                    <a href="#" id="inv-add-return-btn" class="inv-btn" title="{{__('db.Add Return')}}" style="display:none;"><i class="ti ti-arrow-back"></i></a>
                    <button type="button" id="inv-send-sms-btn" class="inv-btn send-sms" title="{{__('db.Send SMS')}}" style="display:none;" data-toggle="modal" data-target="#send-sms"><i class="ti ti-message"></i></button>
                    <form action="{{route('sale.wappnotification')}}" method="POST" id="inv-whatsapp-form" style="margin:0; display:none;">
                        @csrf
                        <input type="hidden" name="customer_id">
                        <input type="hidden" name="sale_id">
                        <button type="submit" class="inv-btn" title="WhatsApp"><i class="ti ti-brand-whatsapp"></i></button>
                    </form>
                    <button type="button" id="inv-add-delivery-btn" class="inv-btn add-delivery" title="{{__('db.Add Delivery')}}" style="display:none;"><i class="ti ti-truck"></i></button>
                    <form action="" method="POST" id="inv-delete-form" style="margin:0; display:none;">
                        @csrf
                        @method("DELETE")
                        <button type="submit" class="inv-btn" title="{{__('db.delete')}}" onclick="return confirmDelete()"><i class="ti ti-trash"></i></button>
                    </form>
                </div>
                {{-- Close --}}
                <button type="button" id="close-btn" data-dismiss="modal" aria-label="Close" class="inv-close">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-x"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>
                </button>
            </div>

            {{-- Invoice Body --}}
            <div id="invoice-wrapper">

                {{-- === 3-COLUMN HEADER: Logo | Labels | Values+Title === --}}
                <div id="invoice-header">

                    {{-- COL 1: Logo (left) --}}
                    <div id="invoice-logo-area">
                        <img src="{{url('logo', gen_setting()->site_logo)}}" alt="Logo">
                    </div>

                    {{-- COL 2: Meta Labels (center, right-aligned) --}}
                    <div id="invoice-meta-labels">
                        <span>&nbsp;</span>{{-- spacer for title row --}}
                        <span>Date:</span>
                        <span>Invoice #:</span>
                        <span>Status:</span>
                        <span>Payment:</span>
                        <span>Currency/Ex. Rate:</span>
                    </div>

                    {{-- COL 3: SALES INVOICE title + Meta Values (right) --}}
                    <div id="invoice-meta-right">
                        <div id="invoice-title-text">SALES INVOICE</div>
                        <span class="meta-val-row" id="inv-date">—</span>
                        <span class="meta-val-row" id="inv-ref">—</span>
                        <span class="meta-val-row" id="inv-status">—</span>
                        <span class="meta-val-row" id="inv-payment">—</span>
                        <span class="meta-val-row" id="inv-exchange-rate">—</span>
                    </div>

                </div>

                <hr class="inv-divider">

                {{-- === BILL TO / FROM === --}}
                <div class="inv-address-row">
                    <div class="inv-address-box">
                        <div class="inv-address-header">Bill To</div>
                        <div class="inv-address-body" id="inv-bill-to">—</div>
                    </div>
                    <div class="inv-address-box">
                        <div class="inv-address-header">From</div>
                        <div class="inv-address-body" id="inv-from">—</div>
                    </div>
                </div>

                {{-- === PRODUCT TABLE === --}}
                <div class="table-responsive">
                    <table class="inv-product-table product-sale-list">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Disc</th>
                                <th>Tax</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                {{-- === TOTALS === --}}
                <div class="inv-totals-row">
                    <div class="inv-inwords-box">
                        <div class="inwords-title">In Words</div>
                        <div class="inwords-text" id="inv-inwords">—</div>
                    </div>
                    <div class="inv-totals-box">
                        <table class="inv-totals-table">
                            <tbody>
                                <tr><td class="tlbl">Subtotal:</td><td class="tval" id="inv-subtotal">—</td></tr>
                                <tr><td class="tlbl">Order Tax:</td><td class="tval" id="inv-order-tax">—</td></tr>
                                <tr><td class="tlbl">Discount:</td><td class="tval disc" id="inv-discount">—</td></tr>
                                <tr><td class="tlbl">Shipping:</td><td class="tval" id="inv-shipping">—</td></tr>
                                <tr class="inv-grand-row"><td class="tlbl">TOTAL:</td><td class="tval" id="inv-grand-total">—</td></tr>
                                <tr class="inv-paid-row"><td class="tlbl">Paid Amount:</td><td class="tval" id="inv-paid">—</td></tr>
                                <tr class="inv-due-row"><td class="tlbl">Amount Due:</td><td class="tval" id="inv-due">—</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- === BARCODE + QR CODE === --}}
                <div class="inv-codes-row">
                    <div class="barcode-wrap">
                        <svg id="inv-barcode"></svg>
                    </div>
                    <div id="inv-qrcode"></div>
                </div>

                {{-- === THANK YOU + NOTES === --}}
                <div class="inv-thankyou">Thank you for shopping with us!</div>
                <div id="sale-content" style="display:none;"></div>
                <div id="sale-footer" style="margin-top:8px; font-size:12px; color:#555;"></div>

            </div>{{-- end invoice-wrapper --}}
        </div>
    </div>
</div>
