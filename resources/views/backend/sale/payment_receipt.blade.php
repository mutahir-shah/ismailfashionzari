<!DOCTYPE html>
<html>
@php
    $show = json_decode($invoice_settings->show_column);
@endphp

<head>
    <link rel="icon" type="image/png" href="{{ url('logo', gen_setting()->site_logo) }}" />
    <title>Payment_Receipt_{{ $lims_payment_data->payment_reference }}</title>
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
        span,
        td {
            font-size: 13px;
            line-height: 1.4;
        }

        @media print {
            .hidden-print {
                display: none !important;
            }

            tr.table-header {
                background-color: {{ $primary_color }} !important;
                -webkit-print-color-adjust: exact;
            }

            td.td-text {
                background-color: rgb(205, 218, 235) !important;
                -webkit-print-color-adjust: exact;
            }
        }

        table,
        tr,
        td {
            font-family: sans-serif;
            border-collapse: collapse;
        }

        #download-btn {
            background-color: #c9001c;
            color: white;
            border: none;
            padding: 3px 10px;
            cursor: pointer;
            font-size: 13px;
            border-radius: 3px;
        }

        #download-btn:hover {
            background-color: #b10000;
        }

        #download-btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
    </style>
</head>

<body>
    @php $url = url()->previous(); @endphp
    <div class="hidden-print">
        <table>
            <tr>
                <td><a href="{{ $url }}" class="btn btn-info"><i class="ti ti-arrow-left"></i>
                        {{ __('db.Back') }}</a></td>
                <td><button onclick="window.print();" class="btn btn-primary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-printer"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" /><path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" /><path d="M7 15a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2l0 -4" /></svg></button></td>
                <td><button id="download-btn" onclick="downloadPDF()"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-file-type-pdf"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4" /><path d="M5 18h1.5a1.5 1.5 0 0 0 0 -3h-1.5v6" /><path d="M17 18h2" /><path d="M20 15h-3v6" /><path d="M11 15v6h1a2 2 0 0 0 2 -2v-2a2 2 0 0 0 -2 -2h-1" /></svg></button></td>
            </tr>
        </table>
        <br>
    </div>

    <div id="invoice-content">

        {{-- ── Header: Company Info + Logo ── --}}
        <table style="width: 100%;border-collapse: collapse;">
            <tr>
                <td colspan="2" style="padding:9px 0;width:40%">
                    @if (isset($show->show_warehouse_info) && $show->show_warehouse_info == 1)
                        <h1 style="margin:0">{{ gen_setting()->company_name ?? '' }}</h1>
                        <div>
                            <span>{{ __('db.Address') }}:</span>&nbsp;&nbsp;<span>{{ $lims_warehouse_data->address }}</span>
                        </div>
                        <div>
                            <span>{{ __('db.Phone') }}:</span>&nbsp;&nbsp;<span>{{ $lims_warehouse_data->phone }}</span>
                        </div>
                        @if (
                            gen_setting()->vat_registration_number &&
                                isset($show->show_vat_registration_number) &&
                                $show->show_vat_registration_number == 1)
                            <div>
                                <span>{{ __('db.VAT Number') }}:</span>&nbsp;&nbsp;<span>{{ gen_setting()->vat_registration_number }}</span>
                            </div>
                        @endif
                    @endif
                </td>
                <td style="width:30%; text-align: middle; vertical-align: top;">
                    @if (gen_setting()->site_logo || $invoice_settings->company_logo)
                        <img src="{{ $invoice_settings->company_logo ? url('invoices', $invoice_settings->company_logo) : url('logo', gen_setting()->site_logo) }}"
                            height="{{ $invoice_settings->logo_height ?? 'auto' }}"
                            width="{{ $invoice_settings->logo_width ?? 'auto' }}" style="margin:5px 0;">
                    @endif
                </td>
                <td style="padding:5px 0;width:30%;text-align:right;">
                    <div style="display:flex;justify-content:space-between;border-bottom:1px solid #aaa">
                        <span>{{ __('db.reference') }}:</span>
                        <span>{{ $lims_payment_data->payment_reference }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;border-bottom:1px solid #aaa">
                        <span>{{ __('db.date') }}:</span>
                        <span>{{ date(config('date_format'), strtotime($lims_payment_data->payment_at)) }}</span>
                    </div>
                    @if (isset($lims_sale_data))
                        <div style="display:flex;justify-content:space-between;border-bottom:1px solid #aaa">
                            <span>{{ __('db.Sale Reference') }}:</span>
                            <span>{{ $lims_sale_data->reference_no }}</span>
                        </div>
                    @endif

                    <div style="display:flex;justify-content:space-between;border-bottom:1px solid #aaa">
                        <span>{{ __('db.Paid By') }}:</span>
                        <span>{{ $lims_payment_data->paying_method }}</span>
                    </div>
                    @if ($lims_payment_data->payment_receiver)
                        <div style="display:flex;justify-content:space-between;border-bottom:1px solid #aaa">
                            <span>{{ __('db.Payment Receiver') }}:</span>
                            <span>{{ $lims_payment_data->payment_receiver }}</span>
                        </div>
                    @endif
                </td>
            </tr>
        </table>

        {{-- ── Bill To ── --}}
        @if (isset($show->show_bill_to_info) && $show->show_bill_to_info == 1)
            <table style="width: 100%;border-collapse: collapse; margin-top: 4px;">
                <tr>
                    <td colspan="3" style="padding:4px 0;width:30%;vertical-align:top">
                        <h2
                            style="background-color: {{ $primary_color }}; color: white; padding: 3px 10px; margin-bottom: 0;">
                            {{ __('db.payment_receipt') }}
                        </h2>
                        <div style="margin-top:10px;margin-left:10px">
                            <span>{{ __('db.customer') }}: {{ $lims_customer_data->name }}</span>
                        </div>
                        <div style="margin-left:10px">
                            <span>{{ __('db.Address') }}:</span>&nbsp;&nbsp;<span>{{ $lims_customer_data->address }}</span>
                        </div>
                        @if (isset($lims_customer_data->phone_number))
                            <div style="margin-bottom:10px;margin-left:10px">
                                <span>Phone:</span>&nbsp;&nbsp;<span>{{ $lims_customer_data->phone_number }}</span>
                            </div>
                        @endif
                    </td>
                </tr>
            </table>
        @endif

        {{-- ── Cheque Details ── --}}
        @if ($lims_payment_data->paying_method == 'Cheque' && $cheque_no)
            <table style="width: 100%;border-collapse: collapse; margin-top: 4px; margin-bottom: 4px;">
                <tr class="table-header" style="background-color: {{ $primary_color }}; color: white;">
                    <td style="border:1px solid #222;padding:2px 6px;" colspan="4">
                        {{ __('db.cheque_details') }}
                    </td>
                </tr>
                <tr>
                    <td class="td-text"
                        style="border:1px solid #222;padding:2px 6px;background-color:rgb(205,218,235);width:25%">
                        {{ __('db.Cheque Number') }}</td>
                    <td style="border:1px solid #222;padding:2px 6px;width:25%">{{ $cheque_no }}</td>
                    <td class="td-text"
                        style="border:1px solid #222;padding:2px 6px;background-color:rgb(205,218,235);width:25%">
                        {{ __('db.cheque_amount') }}</td>
                    <td style="border:1px solid #222;padding:2px 6px;width:25%">
                        {{ config('currency') }}
                        {{ number_format($lims_payment_data->amount, gen_setting()->decimal) }}
                    </td>
                </tr>
            </table>
        @endif

        {{-- ── Payment Details ── --}}
        <table style="width: 100%;border-collapse: collapse; margin-top: 10px;">
            <tr class="table-header" style="background-color: {{ $primary_color }}; color: white;">
                <td colspan="2" style="border:1px solid #222;padding:4px 8px;">
                    {{ __('db.Payment Details') }}
                </td>
            </tr>
            <tr>
                <td class="td-text"
                    style="border:1px solid #222;padding:4px 8px;background-color:rgb(205,218,235);width:50%">
                    {{ __('db.Payment Reference') }}</td>
                <td style="border:1px solid #222;padding:4px 8px;text-align:right">
                    {{ $lims_payment_data->payment_reference }}
                </td>
            </tr>
            <tr>
                <td class="td-text" style="border:1px solid #222;padding:4px 8px;background-color:rgb(205,218,235)">
                    {{ __('db.Payment Date') }}</td>
                <td style="border:1px solid #222;padding:4px 8px;text-align:right">
                    {{ date(config('date_format'), strtotime($lims_payment_data->payment_at)) }}
                </td>
            </tr>
            <tr>
                <td class="td-text" style="border:1px solid #222;padding:4px 8px;background-color:rgb(205,218,235)">
                    {{ __('db.Paid By') }}</td>
                <td style="border:1px solid #222;padding:4px 8px;text-align:right">
                    {{ $lims_payment_data->paying_method }}
                </td>
            </tr>
            <tr>
                <td class="td-text" style="border:1px solid #222;padding:4px 8px;background-color:rgb(205,218,235)">
                    {{ __('db.Recieved Amount') }}</td>
                <td style="border:1px solid #222;padding:4px 8px;text-align:right">
                    {{ number_format($lims_payment_data->amount + $lims_payment_data->change, gen_setting()->decimal) }}
                </td>
            </tr>
            <tr>
                <td class="td-text" style="border:1px solid #222;padding:4px 8px;background-color:rgb(205,218,235)">
                    {{ __('db.Paying Amount') }}</td>
                <td style="border:1px solid #222;padding:4px 8px;text-align:right">
                    {{ number_format($lims_payment_data->amount, gen_setting()->decimal) }}
                </td>
            </tr>
            <tr>
                <td class="td-text" style="border:1px solid #222;padding:4px 8px;background-color:rgb(205,218,235)">
                    {{ __('db.Change') }}</td>
                <td style="border:1px solid #222;padding:4px 8px;text-align:right">
                    {{ number_format($lims_payment_data->change, gen_setting()->decimal) }}
                </td>
            </tr>
            @if ($lims_payment_data->payment_receiver)
                <tr>
                    <td class="td-text" style="border:1px solid #222;padding:4px 8px;background-color:rgb(205,218,235)">
                        {{ __('db.Payment Receiver') }}</td>
                    <td style="border:1px solid #222;padding:4px 8px;text-align:right">
                        {{ $lims_payment_data->payment_receiver }}
                    </td>
                </tr>
            @endif
            @if ($lims_payment_data->payment_note)
                <tr>
                    <td class="td-text" style="border:1px solid #222;padding:4px 8px;background-color:rgb(205,218,235)">
                        {{ __('db.Payment Note') }}</td>
                    <td style="border:1px solid #222;padding:4px 8px">
                        {{ $lims_payment_data->payment_note }}
                    </td>
                </tr>
            @endif
        </table>

        {{-- ── Footer ── --}}
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <tr>
                <td style="width: 100%; text-align: center">
                    @if (isset($show->show_barcode) && $show->show_barcode == 1)
                        <br>
                        <?php echo '<img style="max-width:100%" src="data:image/png;base64,' . DNS1D::getBarcodePNG($lims_payment_data->payment_reference, 'C128') . '" alt="barcode" />'; ?>
                    @endif
                    @if (isset($show->show_qr_code) && $show->show_qr_code == 1)
                        <br>
                        <?php echo '<img style="width:5%" src="data:image/png;base64,' . DNS2D::getBarcodePNG($lims_payment_data->payment_reference, 'QRCODE') . '" alt="qrcode" />'; ?>
                    @endif
                </td>
            </tr>
            <tr>
                <td>
                    @if (isset($show->show_footer_text) && $show->show_footer_text == 1)
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
                margin: [8, 8, 8, 8],
                filename: 'Payment_Receipt_{{ $lims_payment_data->payment_reference }}.pdf',
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    allowTaint: true,
                    logging: false
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait'
                }
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
