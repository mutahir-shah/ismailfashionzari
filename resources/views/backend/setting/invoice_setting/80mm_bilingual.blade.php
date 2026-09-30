<!DOCTYPE html>
<html>
@php
    $show = json_decode($invoice_settings->show_column ?? '{}');

    // Helper: determine if a field should be displayed.
    // If current active setting size is not thermal (e.g. A4), thermal fields were saved as 0 by default.
    // In that case, or when flags are missing, default to showing thermal content.
    $showField = function($field, $default = true) use ($show, $invoice_settings) {
        if (isset($invoice_settings->size) && !in_array($invoice_settings->size, ['80mm', '80mm_bilingual', '58mm'])) {
            return true;
        }
        if (!isset($show->$field)) {
            return $default;
        }
        return (bool) $show->$field;
    };
@endphp

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" type="image/png" href="{{ url('logo', gen_setting()->site_logo) }}" />
    <title>{{ gen_setting()->site_title }}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="all,follow">

    <style type="text/css">
        * {
            font-size: 14px;
            line-height: 24px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, Arial, sans-serif;
        }

        .btn {
            padding: 7px 10px;
            text-decoration: none;
            border: none;
            display: block;
            text-align: center;
            margin: 7px;
            cursor: pointer;
        }

        .btn-info {
            background-color: #999;
            color: #FFF;
        }

        .btn-primary {
            background-color: #6449e7;
            color: #FFF;
            width: 100%;
        }

        td,
        th,
        tr,
        table {
            border-collapse: collapse;
        }

        tr {
            border-bottom: 1px dotted #999;
        }

        td,
        th {
            padding: 7px 0;
            width: 50%;
        }

        table {
            width: 100%;
        }

        tfoot tr th:first-child {
            text-align: left;
        }

        .centered {
            text-align: center;
            align-content: center;
        }

        small {
            font-size: 11px;
        }

        /* Bilingual Label Styles */
        .invoice-label {
            display: inline-flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 2px;
            vertical-align: middle;
        }

        .invoice-label-en {
            font-weight: 500;
        }

        .invoice-label-separator {
            color: #666;
            padding: 0 1px;
        }

        .invoice-label-ar {
            font-weight: 600;
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
        }

        .invoice-label-stacked {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0px;
            line-height: 1.2;
        }

        .invoice-label-stacked .invoice-label-separator {
            display: none;
        }

        .invoice-label-centered {
            align-items: center;
            text-align: center;
        }

        .invoice-number-val {
            direction: ltr;
            unicode-bidi: embed;
            display: inline-block;
        }

        @media print {
            * {
                font-size: 11px;
                line-height: 14px;
            }

            td, th {
                padding: 2px 0;
            }

            small, .metadata {
                font-size: 9px !important;
                line-height: 11px !important;
                display: block;
            }

            .hidden-print {
                display: none !important;
            }

            @page {
                size: 80mm auto;
                margin: 0.2cm;
            }
        }
    </style>
</head>

<body>

    <div style="max-width:290px;width:80mm;margin:0 auto">
        @if (preg_match('~[0-9]~', url()->previous()))
            @php $url = '../../pos'; @endphp
        @else
            @php $url = url()->previous(); @endphp
        @endif
        <div class="hidden-print">
            <table>
                <tr>
                    <td>
                        <a href="{{ $url }}" class="btn btn-info">
                            <i class="ti ti-arrow-left"></i>
                            <x-bilingual-label en="Back" ar="رجوع" />
                        </a>
                    </td>
                    <td>
                        <button onclick="window.print();" class="btn btn-primary">
                            <i class="ti ti-printer"></i>
                            <x-bilingual-label en="Print" ar="طباعة" />
                        </button>
                    </td>
                </tr>
            </table>
            <br>
        </div>

        <div id="receipt-data">
            @if ($showField('show_warehouse_info'))
                <div class="centered">
                    @if (gen_setting()->site_logo || $invoice_settings->company_logo)
                        <img src="{{ $invoice_settings->company_logo ? url('invoices', $invoice_settings->company_logo) : url('logo', gen_setting()->site_logo) }}"
                            height="{{ $invoice_settings->logo_height ?? 'auto' }}" width="{{ $invoice_settings->logo_width ?? 'auto' }}" style="margin:5px 0;">
                    @endif

                    <h2 style="margin: 0 0 5px">{{ gen_setting()->company_name ?? $lims_biller_data->company_name }}</h2>

                    <p style="margin: 0 0 5px">
                        <x-bilingual-label en="Address" ar="العنوان" /> : <span class="invoice-number-val">{{ $lims_warehouse_data->address }}</span>
                        <br><x-bilingual-label en="Phone" ar="الهاتف" />: <span class="invoice-number-val">{{ $lims_warehouse_data->phone }}</span>
                        @if (gen_setting()->vat_registration_number && $showField('show_vat_registration_number'))
                            <br><x-bilingual-label en="VAT No." ar="الرقم الضريبي" />: <span class="invoice-number-val">{{ gen_setting()->vat_registration_number }}</span>
                        @endif
                    </p>
                </div>
            @endif

            <p>
                <x-bilingual-label en="Invoice Date" ar="تاريخ الفاتورة" />:
                <span class="invoice-number-val">
                    @if ($showField('active_date_format', false) && !empty($invoice_settings->invoice_date_format))
                        {{ Carbon\Carbon::parse($lims_sale_data->created_at)->format($invoice_settings->invoice_date_format) }}
                    @else
                        {{ $lims_sale_data->created_at }}
                    @endif
                </span>
                <br>
                @if ($showField('show_ref_number'))
                    <x-bilingual-label en="Invoice No." ar="رقم الفاتورة" />: <span class="invoice-number-val">{{ $lims_sale_data->reference_no }}</span><br>
                @endif

                @if ($showField('show_customer_name'))
                    <x-bilingual-label en="Customer" ar="العميل" />: <span>{{ $lims_customer_data->name }}</span><br>

                    @if(isset($lims_customer_data->tax_no))
                        <x-bilingual-label en="VAT No." ar="الرقم الضريبي" />: <span class="invoice-number-val">{{ $lims_customer_data->tax_no }}</span><br>
                    @endif
                @endif

                @if ($lims_sale_data->table_id)
                    <br><x-bilingual-label en="Table" ar="الطاولة" />: <span>{{ $lims_sale_data->table->name }}</span>
                    <br><x-bilingual-label en="Queue" ar="الانتظار" />: <span class="invoice-number-val">{{ $lims_sale_data->queue }}</span>
                @endif

                <?php
                foreach ($sale_custom_fields as $key => $fieldName) {
                    $field_name = str_replace(' ', '_', strtolower($fieldName));
                    echo '<br>' . htmlspecialchars($fieldName) . ': ' . htmlspecialchars($lims_sale_data->$field_name ?? '');
                }
                foreach ($customer_custom_fields as $key => $fieldName) {
                    $field_name = str_replace(' ', '_', strtolower($fieldName));
                    echo '<br>' . htmlspecialchars($fieldName) . ': ' . htmlspecialchars($lims_customer_data->$field_name ?? '');
                }
                ?>
            </p>

            <table class="table-data">
                <tbody>
                    @foreach ($line_items as $key => $item)
                        @if ($showField('show_description'))
                            <tr style="border-top: 1px dotted #999">
                                <td colspan="2" style="width: 75%; padding-right: 5px;">
                                    <strong>{!! $item->product_name !!}</strong>
                                    @if (!empty($item->variant_name))
                                        <br><span class="metadata"><x-bilingual-label en="Variant" ar="المتغير" />: {{ $item->variant_name }}</span>
                                    @endif

                                    @if ($item->imei_number)
                                        <br><span class="metadata"><x-bilingual-label en="IMEI" ar="الرقم التسلسلي" />: <span class="invoice-number-val">{{ $item->imei_number }}</span></span>
                                    @endif

                                    @if ($item->warranty_duration)
                                        <br><span class="metadata"><x-bilingual-label en="Warranty" ar="الضمان" />: <span class="invoice-number-val">{{ $item->warranty_duration }}</span> (<x-bilingual-label en="Exp" ar="انتهائه" />: <span class="invoice-number-val">{{ $item->warranty_end }}</span>)
                                            @if ($item->guarantee_duration)
                                                | <x-bilingual-label en="Guarantee" ar="الكفالة" />: <span class="invoice-number-val">{{ $item->guarantee_duration }}</span> (<x-bilingual-label en="Exp" ar="انتهائها" />: <span class="invoice-number-val">{{ $item->guarantee_end }}</span>)
                                            @endif
                                        </span>
                                    @elseif ($item->guarantee_duration)
                                        <br><span class="metadata"><x-bilingual-label en="Guarantee" ar="الكفالة" />: <span class="invoice-number-val">{{ $item->guarantee_duration }}</span> (<x-bilingual-label en="Exp" ar="انتهائها" />: <span class="invoice-number-val">{{ $item->guarantee_end }}</span>)</span>
                                    @endif

                                    @if (!empty($item->topping_names))
                                        <span class="metadata">+ <x-bilingual-label en="Toppings" ar="الإضافات" />: {{ implode(', ', $item->topping_names) }}</span>
                                    @endif

                                    @foreach ($item->custom_fields as $fieldName => $fieldValue)
                                        @if ($fieldValue)
                                            <span class="metadata">{{ $fieldName . ': ' . $fieldValue }}</span>
                                        @endif
                                    @endforeach

                                    <!-- Compact Qty string -->
                                    <span class="metadata">
                                        <span class="invoice-number-val">{{ $item->qty }}</span> x <span class="invoice-number-val">{{ format_currency($item->total / $item->qty, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}</span>
                                        @if ($item->tax_rate)
                                            [<x-bilingual-label en="Tax" ar="الضريبة" />: <span class="invoice-number-val">{{ $item->tax_rate }}%</span>]
                                        @endif
                                    </span>
                                </td>
                                <td style="text-align:right; vertical-align:bottom; width:25%;">
                                    <span class="invoice-number-val">{{ format_currency($item->subtotal, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}</span>
                                </td>
                            </tr>
                        @endif
                    @endforeach

                    <!-- Totals -->
                    <tr>
                        <th colspan="2" style="text-align:left">
                            <x-bilingual-label en="Total Before Tax" ar="الإجمالي قبل الضريبة" />
                        </th>
                        <th style="text-align:right">
                            <span class="invoice-number-val">{{ format_currency($lims_sale_data->total_price - ($lims_sale_data->total_tax + $lims_sale_data->order_tax), $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}</span>
                        </th>
                    </tr>

                    @if (gen_setting()->invoice_format == 'gst' && gen_setting()->state == 1)
                        <tr>
                            <td colspan="2"><x-bilingual-label en="IGST" ar="ضريبة IGST" /></td>
                            <td style="text-align:right">
                                <span class="invoice-number-val">{{ format_currency($total_product_tax, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}</span>
                            </td>
                        </tr>
                    @elseif(gen_setting()->invoice_format == 'gst' && gen_setting()->state == 2)
                        <tr>
                            <td colspan="2"><x-bilingual-label en="SGST" ar="ضريبة SGST" /></td>
                            <td style="text-align:right">
                                @php $total_product_tax_amount = ((float) ($total_product_tax / 2)) @endphp
                                <span class="invoice-number-val">{{ format_currency($total_product_tax_amount, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"><x-bilingual-label en="CGST" ar="ضريبة CGST" /></td>
                            <td style="text-align:right">
                                @php $total_product_tax_amount = ((float) ($total_product_tax / 2)) @endphp
                                <span class="invoice-number-val">{{ format_currency($total_product_tax_amount, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}</span>
                            </td>
                        </tr>
                    @else
                        <tr>
                            <th colspan="2" style="text-align:left">
                                <x-bilingual-label en="Tax" ar="الضريبة" />
                            </th>
                            <th style="text-align:right">
                                <span class="invoice-number-val">{{ format_currency($lims_sale_data->total_tax + $lims_sale_data->order_tax, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}</span>
                            </th>
                        </tr>
                    @endif

                    @if ($lims_sale_data->order_discount)
                        <tr>
                            <th colspan="2" style="text-align:left">
                                <x-bilingual-label en="Order Discount" ar="خصم الطلب" />
                            </th>
                            <th style="text-align:right">
                                <span class="invoice-number-val">{{ format_currency($lims_sale_data->order_discount, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}</span>
                            </th>
                        </tr>
                    @endif

                    @if ($lims_sale_data->coupon_discount)
                        <tr>
                            <th colspan="2" style="text-align:left">
                                <x-bilingual-label en="Coupon Discount" ar="خصم القسيمة" />
                            </th>
                            <th style="text-align:right">
                                <span class="invoice-number-val">{{ format_currency($lims_sale_data->coupon_discount, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}</span>
                            </th>
                        </tr>
                    @endif

                    @if ($lims_sale_data->shipping_cost)
                        <tr>
                            <th colspan="2" style="text-align:left">
                                <x-bilingual-label en="Shipping Cost" ar="تكلفة الشحن" />
                            </th>
                            <th style="text-align:right">
                                <span class="invoice-number-val">{{ format_currency($lims_sale_data->shipping_cost, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}</span>
                            </th>
                        </tr>
                    @endif

                    <tr>
                        <th colspan="2" style="text-align:left">
                            <x-bilingual-label en="Grand Total" ar="الإجمالي النهائي" />
                        </th>
                        <th style="text-align:right">
                            <span class="invoice-number-val">{{ format_currency($lims_sale_data->grand_total, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}</span>
                        </th>
                    </tr>

                    @if ($lims_sale_data->grand_total - $lims_sale_data->paid_amount > 0)
                        <tr>
                            <th colspan="2" style="text-align:left">
                                <x-bilingual-label en="Due" ar="المستحق" />
                            </th>
                            <th style="text-align:right">
                                <span class="invoice-number-val">{{ format_currency($lims_sale_data->grand_total - $lims_sale_data->paid_amount, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}</span>
                            </th>
                        </tr>
                    @endif

                    @if ($totalDue && $showField('hide_total_due', false) === false && $lims_customer_data->type != 'walkin')
                        <tr>
                            <th colspan="2" style="text-align:left">
                                <x-bilingual-label en="Previous Due" ar="المستحق السابق" />
                            </th>
                            <th style="text-align:right">
                                <span class="invoice-number-val">{{ format_currency($prevDue, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}</span>
                            </th>
                        </tr>
                        <tr>
                            <th colspan="2" style="text-align:left">
                                <x-bilingual-label en="Total Due" ar="إجمالي المستحق" />
                            </th>
                            <th style="text-align:right">
                                <span class="invoice-number-val">{{ format_currency($totalDue, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}</span>
                            </th>
                        </tr>
                    @endif

                    <tr>
                        @if ($showField('show_in_words'))
                            @if (gen_setting()->currency_position == 'prefix')
                                <th class="centered" colspan="3">
                                    <x-bilingual-label en="In Words" ar="المبلغ كتابةً" />:
                                    <span>{{ $currency_code }}</span>
                                    <span>{{ str_replace('-', ' ', $numberInWords) }}</span>
                                </th>
                            @else
                                <th class="centered" colspan="3">
                                    <x-bilingual-label en="In Words" ar="المبلغ كتابةً" />:
                                    <span>{{ str_replace('-', ' ', $numberInWords) }}</span>
                                    <span>{{ $currency_code }}</span>
                                </th>
                            @endif
                        @endif
                    </tr>

                    @if($installment_info)
                        <tr>
                            <td colspan="3" style="border: 1px dotted #999; padding: 5px;">
                                <p style="text-align: center; margin: 0; font-weight: bold; border-bottom: 1px dotted #999;">
                                    <x-bilingual-label en="Instalment Sale" ar="بيع بالتقسيط" />
                                </p>
                                <p style="margin: 2px 0;">
                                    <strong><x-bilingual-label en="Plan" ar="الخطة" />:</strong> {{ $installment_info->plan->name }}
                                </p>
                                <p style="margin: 2px 0;">
                                    <strong><x-bilingual-label en="Duration" ar="المدة" />:</strong> <span class="invoice-number-val">{{ $installment_info->plan->months }}</span> <x-bilingual-label en="Mo" ar="أشهر" />
                                </p>
                                <p style="margin: 2px 0;">
                                    <strong><x-bilingual-label en="Add. Amt" ar="المبلغ الإضافي" />:</strong> <span class="invoice-number-val">{{ format_currency($installment_info->plan->additional_amount, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}</span>
                                </p>
                                <p style="margin: 2px 0;">
                                    <strong><x-bilingual-label en="Down Pay" ar="الدفعة الأولى" />:</strong> <span class="invoice-number-val">{{ format_currency($installment_info->plan->down_payment, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}</span>
                                </p>
                                <p style="margin: 2px 0; font-weight: bold;">
                                    <strong><x-bilingual-label en="Paid" ar="المدفوع" />:</strong> <span class="invoice-number-val">{{ $installment_info->paid }}/{{ $installment_info->total }}</span> <x-bilingual-label en="Instalments" ar="الأقساط" />
                                </p>
                                @if($installment_info->next)
                                    <p style="margin: 2px 0;">
                                        <strong><x-bilingual-label en="Next Due" ar="تاريخ الاستحقاق القادم" />:</strong> <span class="invoice-number-val">{{ \Carbon\Carbon::parse($installment_info->next->payment_date)->format('d M Y') }}</span>
                                    </p>
                                @endif
                            </td>
                        </tr>
                    @endif

                    @if ($showField('show_paid_info'))
                        @foreach ($lims_payment_data as $payment_data)
                            <tr style="background-color:#ddd;">
                                <td style="padding: 5px;width:30%">
                                    <x-bilingual-label class="invoice-label-stacked" en="Paid By" ar="طريقة الدفع" />:
                                    <span>{{ $payment_data->paying_method }}</span>
                                </td>
                                <td style="padding: 5px;width:40%">
                                    <x-bilingual-label class="invoice-label-stacked" en="Amount" ar="المبلغ" />:
                                    <span class="invoice-number-val">{{ format_currency($payment_data->amount + $payment_data->change, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}</span>
                                </td>
                                <td style="padding: 5px;width:30%">
                                    <x-bilingual-label class="invoice-label-stacked" en="Change" ar="الباقي" />:
                                    <span class="invoice-number-val">{{ format_currency($payment_data->change, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    @endif

                    <tr>
                        <td class="centered" colspan="3">
                            <small>
                                @if ($showField('show_biller_info'))
                                    <x-bilingual-label en="Served By" ar="مندوب المبيعات" />: {{ $lims_bill_by['name'] }} - ({{ $lims_bill_by['user_name'] }})
                                @endif
                            </small><br>
                            @if ($showField('show_footer_text'))
                                @if ($invoice_settings->footer_text)
                                    {!! $invoice_settings->footer_text !!}
                                @else
                                    <strong><x-bilingual-label class="invoice-label-centered" en="Thank you for shopping with us. Please come again." ar="شكراً لتعاملكم معنا. نتمنى زيارتكم مجدداً." /></strong>
                                @endif
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="centered" colspan="3">
                            @if ($showField('show_barcode'))
                                <?php echo '<img style="margin-top:10px;" src="data:image/png;base64,' . DNS1D::getBarcodePNG($lims_sale_data->reference_no, 'C128') . '" width="300" alt="barcode" />'; ?>
                            @endif
                            <br>
                            @if ($showField('show_qr_code'))
                                <?php echo '<img style="margin-top:10px;" src="data:image/png;base64,' . DNS2D::getBarcodePNG($qrText, 'QRCODE') . '" alt="QRcode" />'; ?>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script type="text/javascript">
        localStorage.clear();

        function auto_print() {
            window.print();
        }
        //setTimeout(auto_print, 1000);
    </script>

</body>

</html>
