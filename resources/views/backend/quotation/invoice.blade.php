<!DOCTYPE html>
<html>
@php
    $show = json_decode($invoice_settings->show_column);
@endphp

<head>
    <link rel="icon" type="image/png" href="{{ url('logo', gen_setting()->site_logo) }}" />
    <title>{{ $lims_quotation_data->customer->name . '_Quotation_' . $lims_quotation_data->reference_no }}</title>
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
        #download-btn { background-color: #c9001c; color: white; border: none; padding: 3px 10px; cursor: pointer; font-size: 13px; border-radius: 3px; }
        #download-btn:hover { background-color: #b10000; }
        #download-btn:disabled { background-color: #6c757d; cursor: not-allowed; }
    </style>
</head>

<body>
    @php $url = url()->previous(); @endphp
    <div class="hidden-print">
        <table><tr>
            <td><a href="{{ $url }}" class="btn btn-info"><i class="ti ti-arrow-left"></i> {{ __('db.Back') }}</a></td>
            <td><button onclick="window.print();" class="btn btn-primary"><i class="ti ti-printer"></i> {{ __('db.print') }}</button></td>
            <td><button id="download-btn" onclick="downloadPDF()"><i class="ti ti-download"></i> {{ __('db.download_pdf') }}</button></td>
        </tr></table>
        <br>
    </div>

    <div id="invoice-content">

        <table style="width: 100%;border-collapse: collapse;">
            <tr>
                <td colspan="2" style="padding:9px 0;width:40%">
                    @if (isset($show->show_warehouse_info) && $show->show_warehouse_info == 1)
                        <h1 style="margin:0">{{ gen_setting()->company_name ?? $lims_biller_data->company_name }}</h1>
                        <div><span>{{ __('db.Address') }}:</span>&nbsp;&nbsp;<span>{{ $lims_warehouse_data->address }}</span></div>
                        <div><span>{{ __('db.Phone') }}:</span>&nbsp;&nbsp;<span>{{ $lims_warehouse_data->phone }}</span></div>
                        @if (gen_setting()->vat_registration_number && isset($show->show_vat_registration_number) && $show->show_vat_registration_number == 1)
                            <div><span>{{ __('db.VAT Number') }}:</span>&nbsp;&nbsp;<span>{{ gen_setting()->vat_registration_number }}</span></div>
                        @endif
                    @endif
                </td>
                <td style="width:30%; text-align: middle; vertical-align: top;">
                    @if (gen_setting()->site_logo || $invoice_settings->company_logo)
                        <img src="{{ $invoice_settings->company_logo ? url('invoices', $invoice_settings->company_logo) : url('logo', gen_setting()->site_logo) }}"
                            height="{{ $invoice_settings->logo_height ?? auto }}"
                            width="{{ $invoice_settings->logo_width ?? auto }}" style="margin:5px 0;">
                    @endif
                </td>
                <td style="padding:5px -19px;width:30%;text-align:right;">
                    <div style="display: flex;justify-content: space-between;border-bottom:1px solid #aaa">
                        <span>{{ __('db.reference') }}:</span> <span>{{ $lims_quotation_data->reference_no }}</span>
                    </div>
                    <div style="display: flex;justify-content: space-between;border-bottom:1px solid #aaa">
                        <span>{{ __('db.date') }}:</span>
                        @if (isset($show->active_date_format) && $show->active_date_format == 1)
                            {{ Carbon\Carbon::parse($lims_quotation_data->created_at)->format($invoice_settings->invoice_date_format) }}
                        @else
                            {{ $lims_quotation_data->created_at }}
                        @endif
                    </div>
                    <div style="display: flex;justify-content: space-between;border-bottom:1px solid #aaa">
                        <span>{{ __('db.Status') }}:</span>
                        <span>{{ $lims_quotation_data->quotation_status == 1 ? __('db.Pending') : __('db.Sent') }}</span>
                    </div>
                    @if (isset($show->show_biller_info) && $show->show_biller_info == 1)
                        <div style="display: flex;justify-content: space-between;border-bottom:1px solid #aaa">
                            <span>{{ __('db.Served By') }}:</span>
                            <span>{{ $lims_bill_by['name'] }} - ({{ $lims_bill_by['user_name'] }})</span>
                        </div>
                    @endif
                </td>
            </tr>
        </table>

        <table style="width: 100%;border-collapse: collapse; margin-top: 4px;">
            <tr>
                @if (isset($show->show_bill_to_info) && $show->show_bill_to_info == 1)
                    <td colspan="3" style="padding:4px 0;width:30%;vertical-align:top">
                        <h2 style="background-color: {{ isset($show->active_primary_color) && $show->active_primary_color == 1 && !empty($invoice_settings->primary_color) ? $invoice_settings->primary_color : '#014b94' }}; color: white; padding: 3px 10px; margin-bottom: 0;">
                            {{ __('db.quotation') }}
                        </h2>
                        <div style="margin-top: 10px;margin-left: 10px"><span>{{ __('db.customer') }}: {{ $lims_customer_data->name }}</span></div>
                        <div style="margin-left: 10px"><span>{{ __('db.Address') }}:</span>&nbsp;&nbsp;<span>{{ $lims_customer_data->address }}</span></div>
                        @if (isset($lims_customer_data->phone_number))
                            <div style="margin-bottom: 10px;margin-left: 10px"><span>Phone:</span>&nbsp;&nbsp;<span>{{ $lims_customer_data->phone_number }}</span></div>
                        @endif
                    </td>
                @endif
            </tr>
        </table>

        <table dir="@if (Config::get('app.locale') == 'ar' || gen_setting()->is_rtl) {{ 'rtl' }} @endif" style="width: 100%;border-collapse: collapse;">
            <tr class="table-header" style="background-color: {{ isset($show->active_primary_color) && $show->active_primary_color == 1 && !empty($invoice_settings->primary_color) ? $invoice_settings->primary_color : '#014b94' }}; color: white;">
                <td style="border:1px solid #222;padding:1px 3px;width:4%;text-align:center">#</td>
                <td style="border:1px solid #222;padding:1px 3px;width:49%;text-align:center">{{ __('db.Description') }}</td>
                <td style="border:1px solid #222;padding:1px 3px;width:6%;text-align:center">{{ __('db.qty') }}</td>
                <td style="border:1px solid #222;padding:1px 3px;width:9%;text-align:center">{{ __('db.Unit Price') }}</td>
                <td style="border:1px solid #222;padding:1px 3px;width:7%;text-align:center">{{ __('db.Total') }}</td>
                <td style="border:1px solid #222;padding:1px 3px;width:7%;text-align:center">{{ __('db.Tax') }}</td>
                <td style="border:1px solid #222;padding:1px 2px;width:13%;text-align:center;">{{ __('db.Subtotal') }}</td>
            </tr>

            @foreach ($lims_product_quotation_data as $key => $pq)
                @php
                    $lims_product_data = \App\Models\Product::find($pq->product_id);
                    $unit_code = $pq->sale_unit_id ? (\App\Models\Unit::select('unit_code')->find($pq->sale_unit_id)->unit_code ?? '') : '';
                    $variant_name = $pq->variant_id ? (\App\Models\Variant::select('name')->find($pq->variant_id)->name ?? '') : '';
                    $total    = $pq->net_unit_price * $pq->qty;
                    $subtotal = $pq->total;
                @endphp
                <tr>
                    <td style="border:1px solid #222;padding:1px 3px;text-align:center">{{ $key + 1 }}</td>
                    <td style="border:1px solid #222;padding:1px 3px;font-size: 15px;line-height: 1.2;">
                        {!! $lims_product_data->name !!}
                        @if($pq->product_batch_id)
                            @php $batch = \App\Models\ProductBatch::select('batch_no')->find($pq->product_batch_id); @endphp
                            @if($batch)<br><small>Batch: {{ $batch->batch_no }}</small>@endif
                        @endif
                    </td>
                    <td style="border:1px solid #222;padding:1px 3px;text-align:center">{{ $pq->qty . ' ' . $unit_code . ' ' . $variant_name }}</td>
                    <td style="border:1px solid #222;padding:1px 3px;text-align:center">{{ number_format($pq->net_unit_price, gen_setting()->decimal) }}</td>
                    <td style="border:1px solid #222;padding:1px 3px;text-align:center">{{ number_format($total, gen_setting()->decimal) }}</td>
                    <td style="border:1px solid #222;padding:1px 3px;text-align:center">{{ number_format($pq->tax, gen_setting()->decimal) }}</td>
                    <td style="border:1px solid #222;border-right:1px solid #222;padding:1px 3px;text-align:center;font-size: 15px;">{{ number_format($subtotal, gen_setting()->decimal) }}</td>
                </tr>
            @endforeach

            <tr>
                <td colspan="3" rowspan="@if (gen_setting()->invoice_format == 'gst' && gen_setting()->state == 2) 6 @else 5 @endif"
                    style="border:1px solid #222;padding:1px 3px;text-align: center; vertical-align: top;">
                    @if ($lims_quotation_data->note)
                        <p><strong>{{ __('db.note') }}:</strong> {{ $lims_quotation_data->note }}</p>
                    @endif
                </td>
                <td class="td-text" colspan="3" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);">{{ __('db.Total Before Tax') }}</td>
                <td class="td-text" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);text-align: center;font-size: 15px;">
                    {{ number_format($lims_quotation_data->total_price - ($lims_quotation_data->total_tax + $lims_quotation_data->order_tax), gen_setting()->decimal) }}
                </td>
            </tr>
            @if (gen_setting()->invoice_format == 'gst' && gen_setting()->state == 1)
                <tr>
                    <td class="td-text" colspan="3" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);">IGST</td>
                    <td class="td-text" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);text-align: center;font-size: 15px;">{{ number_format($lims_quotation_data->total_tax + $lims_quotation_data->order_tax, gen_setting()->decimal) }}</td>
                </tr>
            @elseif (gen_setting()->invoice_format == 'gst' && gen_setting()->state == 2)
                <tr>
                    <td class="td-text" colspan="3" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);">SGST</td>
                    <td class="td-text" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);text-align: center;font-size: 15px;">
                        @php $total_tax_amount = ($lims_quotation_data->total_tax + $lims_quotation_data->order_tax) / 2; @endphp
                        {{ number_format($total_tax_amount, gen_setting()->decimal) }}
                    </td>
                </tr>
                <tr>
                    <td class="td-text" colspan="3" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);">CGST</td>
                    <td class="td-text" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);text-align: center;font-size: 15px;">{{ number_format($total_tax_amount, gen_setting()->decimal) }}</td>
                </tr>
            @else
                <tr>
                    <td class="td-text" colspan="3" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);">{{ __('db.Tax') }}</td>
                    <td class="td-text" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);text-align: center;font-size: 15px;">{{ number_format($lims_quotation_data->total_tax + $lims_quotation_data->order_tax, gen_setting()->decimal) }}</td>
                </tr>
            @endif
            <tr>
                <td class="td-text" colspan="3" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);">{{ __('db.Discount') }}</td>
                <td class="td-text" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);text-align: center;font-size: 15px;">{{ number_format($lims_quotation_data->total_discount + $lims_quotation_data->order_discount, gen_setting()->decimal) }}</td>
            </tr>
            <tr>
                <td class="td-text" colspan="3" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);">{{ __('db.Shipping Cost') }}</td>
                <td class="td-text" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);text-align: center;font-size: 15px;">{{ number_format($lims_quotation_data->shipping_cost ?? 0, gen_setting()->decimal) }}</td>
            </tr>
            <tr>
                <td class="td-text" colspan="3" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);">{{ __('db.grand total') }}</td>
                <td class="td-text" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);text-align: center;font-size: 15px;">{{ number_format($lims_quotation_data->grand_total, gen_setting()->decimal) }}</td>
            </tr>
            <tr>
                @if (gen_setting()->currency_position == 'prefix')
                    <td class="td-text" colspan="3" rowspan="2" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);text-align: center;font-size: 15px;vertical-align: top;">
                        @if (isset($show->show_in_words) && $show->show_in_words == 1)
                            {{ __('db.In Words') }}<br>{{ $currency_code }} <span style="text-transform:capitalize;font-size: 15px;">{{ str_replace('-', ' ', $numberInWords) }}</span> only
                        @endif
                    </td>
                @else
                    <td class="td-text" colspan="3" rowspan="2" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);text-align: center;font-size: 15px;vertical-align: top;">
                        @if (isset($show->show_in_words) && $show->show_in_words == 1)
                            {{ __('db.In Words') }}:<br><span style="text-transform:capitalize;font-size: 15px;">{{ str_replace('-', ' ', $numberInWords) }}</span> {{ $currency_code }} only
                        @endif
                    </td>
                @endif
                <td class="td-text" colspan="3" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);">{{ __('db.Quotation Status') }}</td>
                <td class="td-text" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);text-align: center;font-size: 15px;">
                    {{ $lims_quotation_data->quotation_status == 1 ? __('db.Pending') : __('db.Sent') }}
                </td>
            </tr>
            <tr>
                <td class="td-text" colspan="3" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);">{{ __('db.Validity') }}</td>
                <td class="td-text" style="border:1px solid #222;padding:1px 3px;background-color:rgb(205, 218, 235);text-align: center;font-size: 15px;">
                    {{ \Carbon\Carbon::parse($lims_quotation_data->created_at)->addDays(30)->format('d-m-Y') }}
                </td>
            </tr>
        </table>

        <table style="width: 100%; border-collapse: collapse;margin-top:-9px;">
            <tr>
                <td style="width: 100%; text-align: center">
                    <br><br>
                    @if (isset($show->show_barcode) && $show->show_barcode == 1)
                        <?php echo '<img style="max-width:100%" src="data:image/png;base64,' . DNS1D::getBarcodePNG($lims_quotation_data->reference_no, 'C128') . '" alt="barcode" />'; ?>
                    @endif
                    <br><br>
                    @if (isset($show->show_qr_code) && $show->show_qr_code == 1)
                        <?php echo '<img style="width:5%" src="data:image/png;base64,' . DNS2D::getBarcodePNG($lims_quotation_data->reference_no, 'QRCODE') . '" alt="qrcode" />'; ?>
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
                margin:      [8, 8, 8, 8],
                filename:    'Quotation_{{ $lims_quotation_data->reference_no }}.pdf',
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
