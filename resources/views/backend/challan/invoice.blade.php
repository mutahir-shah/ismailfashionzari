@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
<!DOCTYPE html>
<html>
@php
    $show = json_decode($invoice_settings->show_column);
@endphp

<head>
    <link rel="icon" type="image/png" href="{{ url('images/logo_2.png') }}" />
    <title>{{ gen_setting()->site_title }} | Challan Invoice</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="{{ asset($asset_prefix . 'vendor/bootstrap/css/bootstrap.min.css') }}" type="text/css">

    @php
        $primary_color =
            isset($show->active_primary_color) &&
            $show->active_primary_color == 1 &&
            !empty($invoice_settings->primary_color)
                ? $invoice_settings->primary_color
                : '#014b94';
    @endphp

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style type="text/css">
        span, td { font-size: 13px; line-height: 1.4; }

        @media print {
            .hidden-print { display: none !important; }
            tr.table-header { background-color: {{ $primary_color }} !important; -webkit-print-color-adjust: exact; }
            td.td-text { background-color: rgb(205, 218, 235) !important; -webkit-print-color-adjust: exact; }
        }

        table, tr, td { font-family: sans-serif; border-collapse: collapse; }


        #download-btn:disabled { background-color: #6c757d; cursor: not-allowed; }
    </style>
</head>

<body>
    @php $url = route('challan.index'); @endphp
    <div class="hidden-print">
        <table>
            <tr>
                <td><a href="{{ $url }}" class="btn btn-info btn-sm"><i class="ti ti-arrow-left"></i> {{ __('db.Back') }}</a></td>
                <td><button onclick="window.print();" class="btn btn-primary btn-sm"><i class="ti ti-printer"></i> {{ __('db.print') }}</button></td>
                <td><button class="btn btn-sm btn-danger" id="download-btn" onclick="downloadPDF()"><i class="ti ti-download"></i> {{ __('db.download_pdf') }}</button></td>
            </tr>
        </table>
        <br>
    </div>

    <div id="invoice-content">

        {{-- ── Header: Company Info + Logo + Challan Info ── --}}
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                <td colspan="2" style="padding:9px 0;width:40%">
                    @if(isset($show->show_warehouse_info) && $show->show_warehouse_info == 1)
                        <h1 style="margin:0">{{ gen_setting()->company_name ?? '' }}</h1>
                        @if(gen_setting()->vat_registration_number && isset($show->show_vat_registration_number) && $show->show_vat_registration_number == 1)
                            <div><span>{{ __('db.VAT Number') }}:</span>&nbsp;&nbsp;<span>{{ gen_setting()->vat_registration_number }}</span></div>
                        @endif
                    @endif
                </td>
                <td style="width:30%;text-align:middle;vertical-align:top;">
                    @if(gen_setting()->site_logo || $invoice_settings->company_logo)
                        <img src="{{ $invoice_settings->company_logo ? url('invoices', $invoice_settings->company_logo) : url('logo', gen_setting()->site_logo) }}"
                            height="{{ $invoice_settings->logo_height ?? 'auto' }}"
                            width="{{ $invoice_settings->logo_width ?? 'auto' }}" style="margin:5px 0;">
                    @endif
                </td>
                <td style="padding:5px 0;width:30%;text-align:right;">
                    <div style="display:flex;justify-content:space-between;border-bottom:1px solid #aaa">
                        <span>{{ __('db.reference') }}:</span>
                        <span>DC-{{ $challan->reference_no }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;border-bottom:1px solid #aaa">
                        <span>{{ __('db.date') }}:</span>
                        <span>{{ date(gen_setting()->date_format, strtotime($challan->created_at->toDateString())) }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;border-bottom:1px solid #aaa">
                        <span>{{ __('db.status') }}:</span>
                        <span>{{ $challan->status }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;border-bottom:1px solid #aaa">
                        <span>Courier:</span>
                        <span>{{ $challan->courier->name }} [{{ $challan->courier->phone_number }}]</span>
                    </div>
                    @if($challan->closing_date)
                    <div style="display:flex;justify-content:space-between;border-bottom:1px solid #aaa">
                        <span>Closing Date:</span>
                        <span>{{ date(gen_setting()->date_format, strtotime($challan->closing_date)) }}</span>
                    </div>
                    @endif
                </td>
            </tr>
        </table>

        {{-- ── Title bar ── --}}
        @if(isset($show->show_bill_to_info) && $show->show_bill_to_info == 1)
        <table style="width:100%;border-collapse:collapse;margin-top:4px;">
            <tr>
                <td colspan="3" style="padding:4px 0;width:30%;vertical-align:top">
                    <h2 style="background-color:{{ $primary_color }};color:white;padding:3px 10px;margin-bottom:0;">
                        DELIVERY CHALLAN
                    </h2>
                </td>
            </tr>
        </table>
        @endif

        {{-- ── Packing Slip Table ── --}}
        @php
            $packing_slip_list = explode(',', $challan->packing_slip_list);
            $amount_list       = explode(',', $challan->amount_list);
            $cash_list         = $challan->cash_list         ? explode(',', $challan->cash_list)         : [];
            $cheque_list       = $challan->cheque_list       ? explode(',', $challan->cheque_list)       : [];
            $online_list       = $challan->online_payment_list ? explode(',', $challan->online_payment_list) : [];
            $delivery_list     = $challan->delivery_charge_list ? explode(',', $challan->delivery_charge_list) : [];
            $status_list       = $challan->status_list       ? explode(',', $challan->status_list)       : [];
            $sum = array_sum($amount_list);
        @endphp

        <table dir="@if(Config::get('app.locale') == 'ar' || gen_setting()->is_rtl) rtl @endif"
            style="width:100%;border-collapse:collapse;margin-top:4px;">
            <tr class="table-header" style="background-color:{{ $primary_color }};color:white;">
                <td style="border:1px solid #222;padding:2px 4px;width:4%;text-align:center">#</td>
                <td style="border:1px solid #222;padding:2px 4px;text-align:center">Order Ref</td>
                <td style="border:1px solid #222;padding:2px 4px;text-align:center">Shipping Info</td>
                <td style="border:1px solid #222;padding:2px 4px;text-align:center">Amount</td>
                <td style="border:1px solid #222;padding:2px 4px;text-align:center">Cash</td>
                <td style="border:1px solid #222;padding:2px 4px;text-align:center">Cheque</td>
                <td style="border:1px solid #222;padding:2px 4px;text-align:center">Online</td>
                <td style="border:1px solid #222;padding:2px 4px;text-align:center">Del. Charge</td>
                <td style="border:1px solid #222;padding:2px 4px;text-align:center">Status</td>
            </tr>

            @foreach($packing_slip_list as $key => $packing_slip_id)
            @php
                $packing_slip = \App\Models\PackingSlip::with('sale.customer')->find(trim($packing_slip_id));
                if(!$packing_slip) continue;
                $sale = $packing_slip->sale;
                if($sale->shipping_address) {
                    $address = $sale->shipping_address;
                    $city    = $sale->shipping_city;
                    $phone   = $sale->shipping_phone;
                } else {
                    $address = $sale->customer->address;
                    $city    = $sale->customer->city;
                    $phone   = $sale->customer->phone_number;
                }
            @endphp
            <tr>
                <td style="border:1px solid #222;padding:2px 4px;text-align:center">{{ $key + 1 }}</td>
                <td style="border:1px solid #222;padding:2px 4px;text-align:center">{{ $sale->reference_no }}</td>
                <td style="border:1px solid #222;padding:2px 4px;">
                    {{ $address }}, {{ $city }}<br><strong>{{ $phone }}</strong>
                </td>
                <td style="border:1px solid #222;padding:2px 4px;text-align:center">{{ $amount_list[$key] ?? '' }}</td>
                <td style="border:1px solid #222;padding:2px 4px;text-align:center">{{ $cash_list[$key] ?? '' }}</td>
                <td style="border:1px solid #222;padding:2px 4px;text-align:center">{{ $cheque_list[$key] ?? '' }}</td>
                <td style="border:1px solid #222;padding:2px 4px;text-align:center">{{ $online_list[$key] ?? '' }}</td>
                <td style="border:1px solid #222;padding:2px 4px;text-align:center">{{ $delivery_list[$key] ?? '' }}</td>
                <td style="border:1px solid #222;padding:2px 4px;text-align:center">{{ $status_list[$key] ?? '' }}</td>
            </tr>
            @endforeach

            {{-- Total row --}}
            <tr>
                <td class="td-text" colspan="3" style="border:1px solid #222;padding:2px 4px;background-color:rgb(205,218,235);text-align:right;font-weight:bold;">Total</td>
                <td class="td-text" style="border:1px solid #222;padding:2px 4px;background-color:rgb(205,218,235);text-align:center;">{{ $sum }}</td>
                <td style="border:1px solid #222;padding:2px 4px;"></td>
                <td style="border:1px solid #222;padding:2px 4px;"></td>
                <td style="border:1px solid #222;padding:2px 4px;"></td>
                <td style="border:1px solid #222;padding:2px 4px;"></td>
                <td style="border:1px solid #222;padding:2px 4px;"></td>
            </tr>
        </table>
        <br><br>

        {{-- ── Signature Row ── --}}
        <table style="width:100%;border-collapse:collapse;margin-top:30px;">
            <tr>
                <td style="width:50%;padding:0 20px;text-align:center;vertical-align:bottom;">
                    <hr style="border-top:1px solid #222;margin-bottom:4px;">
                    <span>Rider Signature</span>
                </td>
                <td style="width:50%;padding:0 20px;text-align:center;vertical-align:bottom;">
                    <hr style="border-top:1px solid #222;margin-bottom:4px;">
                    <span>Authorized Signature</span>
                </td>
            </tr>
        </table>

        {{-- ── Footer ── --}}
        <table style="width:100%;border-collapse:collapse;margin-top:10px;">
            <tr>
                <td style="width:100%;text-align:center;">
                    @if(isset($show->show_barcode) && $show->show_barcode == 1)
                        <br>
                        <?php echo '<img style="max-width:100%" src="data:image/png;base64,' . DNS1D::getBarcodePNG($challan->reference_no, 'C128') . '" alt="barcode" />'; ?>
                        <br>
                    @endif
                    @if(isset($show->show_qr_code) && $show->show_qr_code == 1)
                        <br>
                        <?php echo '<img style="width:5%" src="data:image/png;base64,' . DNS2D::getBarcodePNG($challan->reference_no, 'QRCODE') . '" alt="qrcode" />'; ?>
                        <br>
                    @endif
                </td>
            </tr>
            <tr>
                <td>
                    @if(isset($show->show_footer_text) && $show->show_footer_text == 1)
                        {!! $invoice_settings->footer_text ?? __('db.Thank you for shopping with us Please come again') !!}
                    @endif
                </td>
            </tr>
        </table>

    </div>

    <script type="text/javascript">
        localStorage.clear();

        function downloadPDF() {
            var btn = document.getElementById('download-btn');
            btn.disabled = true;
            btn.innerHTML = '⏳ Generating...';

            var element = document.getElementById('invoice-content');
            var opt = {
                margin:      [8, 8, 8, 8],
                filename:    'Challan_DC-{{ $challan->reference_no }}.pdf',
                image:       { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, allowTaint: true, logging: false },
                jsPDF:       { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf()
                .set(opt)
                .from(element)
                .save()
                .then(function() {
                    btn.disabled = false;
                    btn.innerHTML = '⬇ Download PDF';
                })
                .catch(function(err) {
                    console.error(err);
                    btn.disabled = false;
                    btn.innerHTML = '⬇ Download PDF';
                    alert('PDF generation failed!');
                });
        }
    </script>
</body>
</html>
