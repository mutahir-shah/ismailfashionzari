    // ========= NUMBER TO WORDS HELPER =========
    function saleDetailsFormatCurrency(amount) {
        if (typeof window.formatCurrency === 'function') {
            return window.formatCurrency(amount);
        }

        var num = parseFloat(amount);
        if (isNaN(num)) {
            num = 0;
        }

        var decimal = parseInt(
            (window.appConfig && window.appConfig.decimal) ||
            @json((int) gen_setting()->decimal),
            10
        );
        var currency = (window.appConfig && window.appConfig.currency) || @json(config('currency'));
        var position = (window.appConfig && window.appConfig.currency_position) || @json(gen_setting()->currency_position ?? 'prefix');
        var formatted = num.toFixed(isNaN(decimal) ? 2 : decimal).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

        return position === 'prefix'
            ? currency + '\u00A0' + formatted
            : formatted + '\u00A0' + currency;
    }

    function numberToWords(num) {
        if (num === undefined || num === null || isNaN(num)) return '';
        var n = parseFloat(num);
        var ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
                    'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
                    'Seventeen','Eighteen','Nineteen'];
        var tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        function words(n) {
            if (n < 20) return ones[n];
            if (n < 100) return tens[Math.floor(n/10)] + (n%10 ? ' '+ones[n%10] : '');
            if (n < 1000) return ones[Math.floor(n/100)] + ' Hundred' + (n%100 ? ' '+words(n%100) : '');
            if (n < 1000000) return words(Math.floor(n/1000)) + ' Thousand' + (n%1000 ? ' '+words(n%1000) : '');
            if (n < 1000000000) return words(Math.floor(n/1000000)) + ' Million' + (n%1000000 ? ' '+words(n%1000000) : '');
            return words(Math.floor(n/1000000000)) + ' Billion' + (n%1000000000 ? ' '+words(n%1000000000) : '');
        }
        var intPart = Math.floor(n);
        var decPart = Math.round((n - intPart) * 100);
        var result = (intPart === 0 ? 'Zero' : words(intPart)) + ' only';
        return result;
    }

    // ========= STATUS BADGE HELPER =========
    function saleStatusBadge(statusText) {
        var cls = 'inv-badge-default';
        var t = (statusText || '').toLowerCase();
        if (t.indexOf('complet') !== -1) cls = 'inv-badge-completed';
        else if (t.indexOf('pending') !== -1) cls = 'inv-badge-pending';
        else if (t.indexOf('return') !== -1) cls = 'inv-badge-returned';
        else if (t.indexOf('process') !== -1 || t.indexOf('cook') !== -1) cls = 'inv-badge-processing';
        return '<span class="inv-badge ' + cls + '">' + (statusText || '—') + '</span>';
    }
    function paymentStatusBadge(statusText) {
        var cls = 'inv-badge-default';
        var t = (statusText || '').toLowerCase();
        if (t.indexOf('paid') !== -1) cls = 'inv-badge-paid';
        else if (t.indexOf('due') !== -1) cls = 'inv-badge-due';
        else if (t.indexOf('partial') !== -1) cls = 'inv-badge-partial';
        else if (t.indexOf('pending') !== -1) cls = 'inv-badge-pending';
        return '<span class="inv-badge ' + cls + '">' + (statusText || '—') + '</span>';
    }

    // ========= MAIN SALE DETAILS FUNCTION =========
    function saleDetails(sale, trElement = null) {
        // sale is now the named-key object returned by getSale() (same as POS):
        // sale.id, sale.date, sale.reference_no, sale.sale_status, sale.grand_total, etc.

        // ---- Set sale_id in hidden field (email form) ----
        $("#sale-details input[name='sale_id']").val(sale.id);

        // ---- Populate dynamic action buttons from TR DOM (unchanged) ----
        if (trElement) {
            var optionsHtml = trElement.find('.edit-options');

            // Edit
            var editBtn = optionsHtml.find('.ti ti-edit').parent('a');
            if (editBtn.length) { $('#inv-edit-btn').show().attr('href', editBtn.attr('href')); } else { $('#inv-edit-btn').hide(); }
            // Installment Plan
            var installBtn = optionsHtml.find('.fa-info-circle').parent('a');
            if (installBtn.length) { $('#inv-installment-btn').show().attr('href', installBtn.attr('href')); } else { $('#inv-installment-btn').hide(); }
            // Packing Slip
            var packingBtn = optionsHtml.find('.create-packing-slip-btn');
            if (packingBtn.length) { $('#inv-packing-slip-btn').show().attr('data-id', packingBtn.attr('data-id')); } else { $('#inv-packing-slip-btn').hide(); }
            // View Payment
            var getPaymentBtn = optionsHtml.find('.get-payment');
            if (getPaymentBtn.length) {
                $('#inv-get-payment-btn').show().attr('data-id', getPaymentBtn.attr('data-id'))
                                               .attr('data-deposit', trElement.find('.deposit').val() || 0);
            } else { $('#inv-get-payment-btn').hide(); }
            // Add Payment
            var addPaymentBtn = optionsHtml.find('button.add-payment');
            var s_grandTotal = parseFloat(sale.grand_total || 0);
            var s_returnedAmount = parseFloat(sale.returned_amount || 0);
            var s_paidAmount = parseFloat(sale.paid_amount || 0);
            var s_dueAmount = s_grandTotal - s_returnedAmount - s_paidAmount;
            
            if (addPaymentBtn.length && s_dueAmount > 0.01) {
                $('#inv-add-payment-btn').show()
                    .attr('data-id',           addPaymentBtn.attr('data-id'))
                    .attr('data-due',          addPaymentBtn.attr('data-due'))
                    .attr('data-currency_id',  addPaymentBtn.attr('data-currency_id'))
                    .attr('data-currency_name',addPaymentBtn.attr('data-currency_name'))
                    .attr('data-exchange_rate',addPaymentBtn.attr('data-exchange_rate'))
                    .attr('data-deposit',      trElement.find('.deposit').val() || 0);
            } else { $('#inv-add-payment-btn').hide(); }
            // Add Return
            var returnBtn = optionsHtml.find('.ti ti-arrow-back').parent('a');
            if (returnBtn.length) { $('#inv-add-return-btn').show().attr('href', returnBtn.attr('href')); } else { $('#inv-add-return-btn').hide(); }
            // Send SMS
            var smsBtn = optionsHtml.find('.send-sms');
            if (smsBtn.length) {
                $('#inv-send-sms-btn').show()
                    .attr('data-id',             smsBtn.attr('data-id'))
                    .attr('data-customer_id',    smsBtn.attr('data-customer_id'))
                    .attr('data-reference_no',   smsBtn.attr('data-reference_no'))
                    .attr('data-sale_status',    smsBtn.attr('data-sale_status'))
                    .attr('data-payment_status', smsBtn.attr('data-payment_status'));
            } else { $('#inv-send-sms-btn').hide(); }
            // WhatsApp
            var wappBtn = optionsHtml.find('.fa-whatsapp').closest('form');
            if (wappBtn.length) {
                $('#inv-whatsapp-form').show();
                $('#inv-whatsapp-form input[name="customer_id"]').val(wappBtn.find('input[name="customer_id"]').val());
                $('#inv-whatsapp-form input[name="sale_id"]').val(wappBtn.find('input[name="sale_id"]').val());
            } else { $('#inv-whatsapp-form').hide(); }
            // Add Delivery
            var deliveryBtn = optionsHtml.find('.add-delivery');
            if (deliveryBtn.length) { $('#inv-add-delivery-btn').show().attr('data-id', deliveryBtn.attr('data-id')); } else { $('#inv-add-delivery-btn').hide(); }
            // Delete
            var deleteBtn = optionsHtml.find('.ti ti-trash').closest('form');
            if (deleteBtn.length) { $('#inv-delete-form').show().attr('action', deleteBtn.attr('action')); } else { $('#inv-delete-form').hide(); }
        } else {
            $('#inv-action-bar .inv-btn').not('#print-btn').not('#close-btn').not('.sendmail-form .inv-btn').hide();
            $('#inv-whatsapp-form').hide();
        }

        // ---- Header ----
        var refNo = sale.reference_no || '—';
        $('#inv-date').text(sale.date || '—');
        $('#inv-ref').text(refNo);
        $('#inv-status').html(saleStatusBadge(sale.sale_status || '—'));
        // Payment status is not returned by getSale(); leave blank or derive from paid/grand_total
        var calcPayStatus = (parseFloat(sale.paid_amount) >= parseFloat(sale.grand_total)) ? '{{__("db.Paid")}}'
                          : (parseFloat(sale.paid_amount) > 0)                             ? '{{__("db.Partial")}}'
                          :                                                                   '{{__("db.Due")}}';
        $('#inv-payment').html(paymentStatusBadge(calcPayStatus));
        var currency_code = sale.currency_code || '{{gen_setting()->currency ?? "USD"}}';
        $('#inv-exchange-rate').text(currency_code + '/' + parseFloat(sale.exchange_rate || 1).toFixed(2));

        // ---- BILL TO (customer) ----
        var billHtml = '<strong>' + (sale.customer_name || '—') + '</strong>';
        if (sale.customer_phone)   billHtml += '<div class="addr-row"><span class="addr-lbl">Phone:</span><span>'   + sale.customer_phone   + '</span></div>';
        if (sale.customer_address) billHtml += '<div class="addr-row"><span class="addr-lbl">Address:</span><span>' + sale.customer_address + '</span></div>';
        if (sale.customer_city)    billHtml += '<div class="addr-row"><span class="addr-lbl">City:</span><span>'    + sale.customer_city    + '</span></div>';
        $('#inv-bill-to').html(billHtml);

        // ---- FROM (biller / warehouse) ----
        var fromHtml = '<strong>' + (sale.biller_name || sale.biller_company_name || sale.warehouse_name || '—') + '</strong>';
        if (sale.biller_company_name && sale.biller_company_name !== sale.biller_name)
            fromHtml += '<div class="addr-row"><span class="addr-lbl">Company:</span><span>' + sale.biller_company_name + '</span></div>';
        if (sale.biller_phone)   fromHtml += '<div class="addr-row"><span class="addr-lbl">Phone:</span><span>'      + sale.biller_phone   + '</span></div>';
        if (sale.biller_address) fromHtml += '<div class="addr-row"><span class="addr-lbl">Address:</span><span>'    + sale.biller_address + '</span></div>';
        if (sale.biller_email)   fromHtml += '<div class="addr-row"><span class="addr-lbl">Email:</span><span>'      + sale.biller_email   + '</span></div>';
        $('#inv-from').html(fromHtml);

        // ---- Product rows (loaded from separate endpoint — unchanged) ----
        var loaderHtml = '<tbody id="inv-loader-tbody"><tr><td colspan="6" class="text-center"><div class="loader" title="4" style="border:none;min-height:150px;display:flex;align-items:center;justify-content:center;"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="30px" viewBox="0 0 24 30" style="enable-background:new 0 0 50 50;" xml:space="preserve"><rect x="0" y="0" width="4" height="10" fill="#333"><animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0" dur="0.6s" repeatCount="indefinite"></animateTransform></rect><rect x="10" y="0" width="4" height="10" fill="#333"><animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0.2s" dur="0.6s" repeatCount="indefinite"></animateTransform></rect><rect x="20" y="0" width="4" height="10" fill="#333"><animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0.4s" dur="0.6s" repeatCount="indefinite"></animateTransform></rect></svg></div></td></tr></tbody>';
        $(".product-sale-list tbody").remove();
        $(".product-sale-list").append(loaderHtml);

        $.get('{{url("sales/product_sale")}}/' + sale.id, function(data) {
            $(".product-sale-list tbody").remove();
            
            @if(file_exists(base_path('Modules/Tailoring')))
            if (data && data.type === 'tailoring') {
                var tailoringOrderUrlTemplate = @json(route('tailoring.orders.show', ['order' => '__ORDER_ID__']));
                var tailoringOrderUrl = tailoringOrderUrlTemplate.replace('__ORDER_ID__', data.tailoring_order_id);

                var newBody = $("<tbody>");
                var newRow = $("<tr>");
                newRow.append('<td colspan="9" class="text-center" style="padding: 20px;"><strong>Tailoring Order:</strong> ' + data.tailoring_order_code + ' - ' + data.piece_count + ' piece(s)<br><br><a href="' + tailoringOrderUrl + '" class="btn btn-primary btn-sm mt-2" target="_blank">View Tailoring Order</a></td>');
                newBody.append(newRow);
                $(".product-sale-list").append(newBody);

                function formatOrDash(val) {
                    if (val === null || val === undefined || val === '') {
                        return '—';
                    }
                    var num = parseFloat(val);
                    if (isNaN(num)) {
                        return '—';
                    }
                    return saleDetailsFormatCurrency(num);
                }

                var subtotalVal = (sale.total_price !== undefined && sale.total_price !== null) ? sale.total_price : sale.grand_total;
                var grandTotalVal = sale.grand_total;
                var paidAmountVal = sale.paid_amount;
                var orderTaxVal = sale.order_tax;
                var discountAmtVal = sale.order_discount;
                var shippingVal = sale.shipping_cost;

                $('#inv-subtotal').text(formatOrDash(subtotalVal));
                $('#inv-order-tax').text(formatOrDash(orderTaxVal));
                if (discountAmtVal === null || discountAmtVal === undefined || discountAmtVal === '') {
                    $('#inv-discount').text('—');
                } else {
                    var dNum = parseFloat(discountAmtVal);
                    $('#inv-discount').text(isNaN(dNum) ? '—' : (dNum > 0 ? '- ' + saleDetailsFormatCurrency(dNum) : saleDetailsFormatCurrency(0)));
                }
                $('#inv-shipping').text(formatOrDash(shippingVal));
                $('#inv-grand-total').text(formatOrDash(grandTotalVal));
                $('#inv-paid').text(formatOrDash(paidAmountVal));

                if (grandTotalVal === null || grandTotalVal === undefined || grandTotalVal === '') {
                    $('#inv-due').text('—');
                    $('#inv-inwords').text('—');
                } else {
                    var gNum = parseFloat(grandTotalVal) || 0;
                    var pNum = parseFloat(paidAmountVal) || 0;
                    var dueNum = gNum - pNum;
                    $('#inv-due').text(saleDetailsFormatCurrency(dueNum < 0 ? 0 : dueNum));
                    $('#inv-inwords').text(numberToWords(gNum));
                }

                try {
                    var barcodeVal = refNo.replace(/[^A-Za-z0-9\-\.\$\%\/\+\s]/g, '');
                    if (barcodeVal.length < 1) barcodeVal = '000000';
                    JsBarcode('#inv-barcode', barcodeVal, {
                        format: 'CODE128',
                        lineColor: '#000',
                        width: 2,
                        height: 55,
                        displayValue: true,
                        fontSize: 12,
                        margin: 4
                    });
                    $('#inv-barcode').show();
                } catch(e) {
                    $('#inv-barcode').hide();
                }

                $('#inv-qrcode').empty();
                try {
                    var qrContent = 'Invoice: ' + refNo +
                        '\nDate: '     + (sale.date || '') +
                        '\nCustomer: ' + (sale.customer_name || '') +
                        '\nTotal: '   + (grandTotalVal !== null && grandTotalVal !== undefined ? saleDetailsFormatCurrency(parseFloat(grandTotalVal)) : '') +
                        '\nPaid: '    + (paidAmountVal !== null && paidAmountVal !== undefined ? saleDetailsFormatCurrency(parseFloat(paidAmountVal)) : '');
                    new QRCode(document.getElementById('inv-qrcode'), {
                        text: qrContent,
                        width: 80,
                        height: 80,
                        colorDark: '#000',
                        colorLight: '#fff',
                        correctLevel: QRCode.CorrectLevel.M
                    });
                } catch(e) {}

                return;
            }
            @endif

            var name_code    = data.product;
            var qty          = data.qty;
            var unit_code    = data.unit;
            var tax          = data.tax;
            var tax_rate     = data.tax_rate;
            var discount     = data.discount;
            var subtotal     = data.total;
            var batch_no     = data.batch_no;
            var return_qty   = data.return_qty;
            var is_delivered = data.is_delivered;
            var toppings     = data.topping_id || [];
            var total_qty    = 0;
            var total_subtotal = 0;
            var newBody = $("<tbody>");

            $.each(name_code, function(index) {
                var newRow = $("<tr>");
                var cols = '';

                // Product name + topping names
                var prodName = name_code[index];
                if (toppings[index]) {
                    try {
                        var td = JSON.parse(toppings[index]);
                        prodName += ' (' + td.map(function(t) {
                            var p = parseFloat(t.price_adjustment || t.price || 0);
                            return t.name + (p > 0 ? ' (+' + p.toFixed({{gen_setting()->decimal}}) + ')' : '');
                        }).join(', ') + ')';
                    } catch(e) {}
                }

                var unitPrice = parseFloat(subtotal[index] / qty[index]).toFixed({{gen_setting()->decimal}});
                var rowSubtotal = parseFloat(subtotal[index]);
                total_subtotal += rowSubtotal;

                cols += '<td><div class="inv-prod-title">' + prodName + '</div></td>';
                cols += '<td>' + saleDetailsFormatCurrency(unitPrice) + '</td>';
                cols += '<td>' + parseFloat(qty[index]).toFixed(2) + ' ' + (unit_code[index] || 'pc') + '</td>';
                cols += '<td class="disc-red">' + saleDetailsFormatCurrency(discount[index] || 0) + '</td>';
                cols += '<td>' + saleDetailsFormatCurrency(tax[index] || 0) + '</td>';
                cols += '<td>' + saleDetailsFormatCurrency(rowSubtotal) + '</td>';

                total_qty += parseFloat(qty[index]);
                newRow.append(cols);
                newBody.append(newRow);
            });

            $("table.product-sale-list").append(newBody);

            // ---- Totals (named keys from getSale()) ----
            var grandTotal  = parseFloat(sale.grand_total)  || 0;
            var paidAmount  = parseFloat(sale.paid_amount)  || 0;
            var dueAmount   = grandTotal - paidAmount;
            var orderTax    = parseFloat(sale.order_tax)    || 0;
            var discountAmt = parseFloat(sale.order_discount) || 0;
            var shipping    = parseFloat(sale.shipping_cost) || 0;
            var currency    = sale.currency_code || '{{gen_setting()->currency ?? "USD"}}';

            $('#inv-subtotal').text(saleDetailsFormatCurrency(total_subtotal));
            $('#inv-order-tax').text(saleDetailsFormatCurrency(orderTax));
            $('#inv-discount').text('- ' + saleDetailsFormatCurrency(discountAmt));
            $('#inv-shipping').text(saleDetailsFormatCurrency(shipping));
            $('#inv-grand-total').text(saleDetailsFormatCurrency(grandTotal));
            $('#inv-paid').text(saleDetailsFormatCurrency(paidAmount));
            $('#inv-due').text(saleDetailsFormatCurrency((dueAmount < 0 ? 0 : dueAmount)));

            $('#inv-inwords').text(numberToWords(grandTotal));

            // ---- Barcode ----
            try {
                var barcodeVal = refNo.replace(/[^A-Za-z0-9\-\.\$\%\/\+\s]/g, '');
                if (barcodeVal.length < 1) barcodeVal = '000000';
                JsBarcode('#inv-barcode', barcodeVal, {
                    format: 'CODE128',
                    lineColor: '#000',
                    width: 2,
                    height: 55,
                    displayValue: true,
                    fontSize: 12,
                    margin: 4
                });
                $('#inv-barcode').show();
            } catch(e) {
                $('#inv-barcode').hide();
                console.warn('Barcode error', e);
            }

            // ---- QR Code ----
            $('#inv-qrcode').empty();
            try {
                var qrContent = 'Invoice: ' + refNo +
                    '\nDate: '     + (sale.date || '') +
                    '\nCustomer: ' + (sale.customer_name || '') +
                    '\nTotal: '   + saleDetailsFormatCurrency(grandTotal) +
                    '\nPaid: '    + saleDetailsFormatCurrency(paidAmount);
                new QRCode(document.getElementById('inv-qrcode'), {
                    text: qrContent,
                    width: 80,
                    height: 80,
                    colorDark: '#000',
                    colorLight: '#fff',
                    correctLevel: QRCode.CorrectLevel.M
                });
            } catch(e) {
                console.warn('QR Code error', e);
            }
        });

        // ---- Notes footer (named keys) ----
        var htmlfooter = '';
        if (sale.sale_note)  htmlfooter += '<p><strong>{{__("db.Sale Note")}}:</strong> '  + sale.sale_note  + '</p>';
        if (sale.staff_note) htmlfooter += '<p><strong>{{__("db.Staff Note")}}:</strong> ' + sale.staff_note + '</p>';
        if (sale.user_name)  htmlfooter += '<small>{{__("db.Created By")}}: ' + sale.user_name + ' &lt;' + (sale.user_email || '') + '&gt;</small>';
        $('#sale-footer').html(htmlfooter);

        $('#sale-details').modal('show');
    }

