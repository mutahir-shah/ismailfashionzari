<!DOCTYPE html>
<html>
@php
    $show = json_decode($invoice_settings->show_column);
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
            font-family: 'Ubuntu', sans-serif;
            text-transform: capitalize;
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

        @media print {
            * {
                font-size: 11px; /* Slightly smaller base font */
                line-height: 14px; /* Squeezes wrapped text lines closer together */
            }

            td, th {
                padding: 2px 0; /* Minimal vertical padding between items */
            }

            /* Target secondary information specifically */
            small, .metadata {
                font-size: 9px !important;
                line-height: 11px !important;
                display: block; /* Forces a tight stack */
            }

            .hidden-print {
                display: none !important;
            }

            @page {
                margin: 0.2cm 0.2cm; /* Reduce giant top/bottom margins */
            }
        }
    </style>
</head>

<body>

    <div style="max-width:290px;margin:0 auto">
        @if (preg_match('~[0-9]~', url()->previous()))
            @php $url = '../../pos'; @endphp
        @else
            @php $url = url()->previous(); @endphp
        @endif
        <div class="hidden-print">
            <table>
                <tr>
                    <td><a href="{{ $url }}" class="btn btn-info"><i class="ti ti-arrow-left"></i>
                            {{ __('db.Back') }}</a> </td>
                    <td><button onclick="window.print();" class="btn btn-primary"><i class="ti ti-printer"></i>
                            {{ __('db.print') }}</button></td>
                </tr>
            </table>
            <br>
        </div>

        <div id="receipt-data">
            @if (isset($show->show_warehouse_info) && $show->show_warehouse_info == 1)
                <div class="centered">

                    @if (gen_setting()->site_logo || $invoice_settings->company_logo)
                    <img src="{{ $invoice_settings->company_logo ? url('invoices', $invoice_settings->company_logo) : url('logo', gen_setting()->site_logo) }}"
                            height="{{ $invoice_settings->logo_height ?? 'auto' }}" width="{{ $invoice_settings->logo_width ?? 'auto' }}" style="margin:5px 0;">
                    @endif

                    <h2 style="margin: 0 0 5px">{{ gen_setting()->company_name ?? $lims_biller_data->company_name }}</h2>

                    <p style="margin: 0 0 5px">{{ __('db.Address') }} : {{ $lims_warehouse_data->address }}
                        <br>{{ __('db.Phone Number') }}: {{ $lims_warehouse_data->phone }}
                        @if (gen_setting()->vat_registration_number && isset($show->show_vat_registration_number) && $show->show_vat_registration_number == 1)
                        <br>{{__('db.VAT Number')}}: {{gen_setting()->vat_registration_number}}
                        @endif
                    </p>
                </div>
            @endif
            <p>{{ __('db.date') }}:
                @if (isset($show->active_date_format) && $show->active_date_format == 1)
                {{ Carbon\Carbon::parse($lims_sale_data->created_at)->format($invoice_settings->invoice_date_format) }}
                @else
                    {{ $lims_sale_data->created_at }}
                @endif
                <br>
                @if (isset($show->show_ref_number) && $show->show_ref_number == 1)
                {{ __('db.reference') }}: {{ $lims_sale_data->reference_no }}<br>
                @endif

                @if (isset($show->show_customer_name) && $show->show_customer_name == 1)
                {{ __('db.customer') }}: {{ $lims_customer_data->name }}<br>

                @if(isset($lims_customer_data->tax_no))
                {{__('db.Tax Number')}}: {{ $lims_customer_data->tax_no }}<br>
                @endif

                @endif

                @if ($lims_sale_data->table_id)
                    <br>{{ __('db.table') }}: {{ $lims_sale_data->table->name }}
                    <br>{{ __('db.Queue') }}: {{ $lims_sale_data->queue }}
                @endif
                <?php
                foreach ($sale_custom_fields as $key => $fieldName) {
                    $field_name = str_replace(' ', '_', strtolower($fieldName));
                    echo '<br>' . $fieldName . ': ' . $lims_sale_data->$field_name;
                }
                foreach ($customer_custom_fields as $key => $fieldName) {
                    $field_name = str_replace(' ', '_', strtolower($fieldName));
                    echo '<br>' . $fieldName . ': ' . $lims_customer_data->$field_name;
                }
                ?>

            </p>
            <table class="table-data">
                <tbody>

                    @foreach ($line_items as $key => $item)
                        @if (isset($show->show_description) && $show->show_description == 1 )
                            <tr style="border-top: 1px dotted #999">
                                <td colspan="2" style="width: 75%; padding-right: 5px;">
                                    <!-- Product Name + Qty on the same block context -->
                                    <strong>{!! $item->product_name !!}</strong>
                                    @if (!empty($item->variant_name))
                                        <br><span class="metadata">Variant: {{ $item->variant_name }}</span>
                                    @endif

                                    @if ($item->imei_number)
                                        <br><span class="metadata">{{ trans('IMEI') }}: {{ $item->imei_number }}</span>
                                    @endif

                                    @if ($item->warranty_duration)
                                        <br><span class="metadata">Warranty: {{ $item->warranty_duration }} (Exp: {{ $item->warranty_end }})
                                            @if ($item->guarantee_duration)
                                                | Guarantee: {{ $item->guarantee_duration }} (Exp: {{ $item->guarantee_end }})
                                            @endif
                                        </span>
                                    @elseif ($item->guarantee_duration)
                                        <br><span class="metadata">Guarantee: {{ $item->guarantee_duration }} (Exp: {{ $item->guarantee_end }})</span>
                                    @endif

                                    @if (!empty($item->topping_names))
                                        <span class="metadata">+ Toppings: {{ implode(', ', $item->topping_names) }}</span>
                                    @endif

                                    @foreach ($item->custom_fields as $fieldName => $fieldValue)
                                        @if ($fieldValue)
                                            <span class="metadata">{{ $fieldName . ': ' . $fieldValue }}</span>
                                        @endif
                                    @endforeach
                                    
                                    <!-- Compact Qty string -->
                                    <span class="metadata">
                                        {{ $item->qty }} x {{ format_currency($item->total / $item->qty, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}
                                        @if ($item->tax_rate)
                                            [Tax: {{ $item->tax_rate }}%]
                                        @endif
                                    </span>
                                </td>
                                <td style="text-align:right; vertical-align:bottom; width:25%;">
                                    {{ format_currency($item->subtotal, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}
                                </td>
                            </tr>
                        @endif
                    @endforeach

                    <!-- <tfoot> -->
                    <tr>
                        <th colspan="2" style="text-align:left">{{ __('db.Total Before Tax') }}</th>
                        <th style="text-align:right">
                            {{ format_currency($lims_sale_data->total_price - ($lims_sale_data->total_tax + $lims_sale_data->order_tax), $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}
                        </th>
                    </tr>
                    @if (gen_setting()->invoice_format == 'gst' && gen_setting()->state == 1)
                        <tr>
                            <td colspan="2">IGST</td>
                            <td style="text-align:right">
                                {{format_currency($total_product_tax, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}
                            </td>
                        </tr>
                    @elseif(gen_setting()->invoice_format == 'gst' && gen_setting()->state == 2)
                        <tr>
                            <td colspan="2">SGST</td>
                            <td style="text-align:right">
                                @php $total_product_tax_amount = ((float) ($total_product_tax / 2)) @endphp
                                {{format_currency($total_product_tax_amount, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">CGST</td>
                            <td style="text-align:right">
                                @php $total_product_tax_amount = ((float) ($total_product_tax / 2)) @endphp
                                {{format_currency($total_product_tax_amount, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}
                            </td>
                        </tr>
                    @else
                        <tr>
                            <th colspan="2" style="text-align:left">{{ __('db.Tax') }}</th>
                            <th style="text-align:right">
                                {{ format_currency($lims_sale_data->total_tax + $lims_sale_data->order_tax, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}
                            </th>
                        </tr>
                    @endif 
                    @if ($lims_sale_data->order_discount)
                        <tr>
                            <th colspan="2" style="text-align:left">{{ __('db.Order Discount') }}</th>
                            <th style="text-align:right">
                                {{format_currency($lims_sale_data->order_discount, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}
                            </th>
                        </tr>
                    @endif
                    @if ($lims_sale_data->coupon_discount)
                        <tr>
                            <th colspan="2" style="text-align:left">{{ __('db.Coupon Discount') }}</th>
                            <th style="text-align:right">
                                {{format_currency($lims_sale_data->coupon_discount, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}
                            </th>
                        </tr>
                    @endif
                    @if ($lims_sale_data->shipping_cost)
                        <tr>
                            <th colspan="2" style="text-align:left">{{ __('db.Shipping Cost') }}</th>
                            <th style="text-align:right">
                                {{format_currency($lims_sale_data->shipping_cost, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}
                            </th>
                        </tr>
                    @endif
                    <tr>
                        <th colspan="2" style="text-align:left">{{ __('db.grand total') }}</th>
                        <th style="text-align:right">
                            {{format_currency($lims_sale_data->grand_total, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}
                        </th>
                    </tr>
                    @if ($lims_sale_data->grand_total - $lims_sale_data->paid_amount > 0)
                        <tr>
                            <th colspan="2" style="text-align:left">{{ __('db.Due') }}</th>
                            <th style="text-align:right">
                                {{format_currency($lims_sale_data->grand_total - $lims_sale_data->paid_amount, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}
                            </th>
                        </tr>
                    @endif
                    @if ($totalDue && isset($show->hide_total_due) && $lims_customer_data->type != 'walkin')
                        @if (!$show->hide_total_due)
                        <tr>
                            <th colspan="2" style="text-align:left">{{ __('db.Previous Due') }}</th>
                            <th style="text-align:right">
                                {{format_currency($prevDue, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}
                            </th>
                        </tr>
                        <tr>
                            <th colspan="2" style="text-align:left">{{ __('db.Total Due') }}</th>
                            <th style="text-align:right">
                                {{format_currency($totalDue, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}
                            </th>
                        </tr>
                        @endif
                    @endif
                    <tr>
                        @if (isset($show->show_in_words) && $show->show_in_words == 1)
                            @if (gen_setting()->currency_position == 'prefix')
                                <th class="centered" colspan="3">{{ __('db.In Words') }}:
                                    <span>{{ $currency_code }}</span>
                                    <span>{{ str_replace('-', ' ', $numberInWords) }}</span>
                                </th>
                            @else
                                <th class="centered" colspan="3">{{ __('db.In Words') }}:
                                    <span>{{ str_replace('-', ' ', $numberInWords) }}</span>
                                    <span>{{ $currency_code }}</span>
                                </th>
                            @endif
                        @endif
                    </tr>
                    <tr>
                    @if($installment_info)
                    <tr>
                        <td colspan="3" style="border: 1px dotted #999; padding: 5px;">
                            <p style="text-align: center; margin: 0; font-weight: bold; border-bottom: 1px dotted #999;">INSTALMENT SALE</p>
                            <p style="margin: 2px 0;">Plan: {{ $installment_info->plan->name }}</p>
                            <p style="margin: 2px 0;">Duration: {{ $installment_info->plan->months }} Mo</p>
                            <p style="margin: 2px 0;">Add. Amt: {{ format_currency($installment_info->plan->additional_amount, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}</p>
                            <p style="margin: 2px 0;">Down Pay: {{ format_currency($installment_info->plan->down_payment, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}</p>
                            <p style="margin: 2px 0; font-weight: bold;">Paid: {{ $installment_info->paid }}/{{ $installment_info->total }} Instalments</p>
                            @if($installment_info->next)
                            <p style="margin: 2px 0;">Next Due: {{ \Carbon\Carbon::parse($installment_info->next->payment_date)->format('d M Y') }}</p>
                            @endif
                        </td>
                    </tr>
                    @endif

                    @if (isset($show->show_paid_info) && $show->show_paid_info == 1)
                        @foreach ($lims_payment_data as $payment_data)
                            <tr style="background-color:#ddd;">
                                <td style="padding: 5px;width:30%">{{ __('db.Paid By') }}:
                                    {{ $payment_data->paying_method }}</td>
                                <td style="padding: 5px;width:40%">{{ __('db.Amount') }}:
                                    {{format_currency($payment_data->amount + $payment_data->change, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}
                                </td>
                                <td style="padding: 5px;width:30%">{{ __('db.Change') }}:
                                    {{format_currency($payment_data->change, $lims_sale_data->currency->symbol ?? $lims_sale_data->currency->code ?? '$') }}
                                </td>
                            </tr>
                        @endforeach
                    @endif
                    <tr>
                        <td class="centered" colspan="3">
                            <small>
                                @if (isset($show->show_biller_info) && $show->show_biller_info == 1)
                                {{ __('db.Served By') }}: {{ $lims_bill_by['name'] }} - ({{ $lims_bill_by['user_name'] }})
                                @endif
                            </small><br>
                            @if (isset($show->show_footer_text) && $show->show_footer_text == 1)
                                @if ($invoice_settings->footer_text)
                                {!! $invoice_settings->footer_text !!}
                                @else
                                <strong>{{ __('db.thank_you_for_shopping') }}</strong>
                                @endif                            
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="centered" colspan="3">
                            @if (isset($show->show_barcode) && $show->show_barcode == 1)
                                <?php echo '<img style="margin-top:10px;" src="data:image/png;base64,' . DNS1D::getBarcodePNG($lims_sale_data->reference_no, 'C128') . '" width="300" alt="barcode"   />'; ?>
                            @endif
                            <br>
                            @if (isset($show->show_qr_code) && $show->show_qr_code == 1)
                                <?php echo '<img style="margin-top:10px;" src="data:image/png;base64,' . DNS2D::getBarcodePNG($qrText, 'QRCODE') . '" alt="QRcode"   />'; ?>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
            <!-- <div class="centered" style="margin:30px 0 50px">
            <small>{{ __('db.Invoice Generated By') }} {{ gen_setting()->site_title }}.
            {{ __('db.Developed By') }} LionCoders</strong></small>
        </div> -->
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
