@php
    $originalSaleCreditData = isset($lims_sale_data)
        ? [
            'customer_id' => $lims_sale_data->customer_id,
            'due' => max(
                0,
                (float) $lims_sale_data->grand_total - (float) $lims_sale_data->paid_amount
            ),
        ]
        : null;
@endphp

<!-- Shared Sale Credit Limit + Request Feedback -->
<div id="credit-limit-error-box"
     class="alert alert-danger"
     role="alert"
     aria-live="assertive"
     style="display:none; margin-top:10px;">
    <strong id="cl-error-title">{{ __('db.Credit limit exceeded') }}!</strong>
    <div id="cl-error-message" class="mt-1 mb-2"></div>
    <div id="cl-error-details">
        {{ __('db.Credit Limit') }}: <span id="cl-limit-display">0.00</span><br>
        {{ __('db.Existing Due') }}: <span id="cl-existing-due-display">0.00</span><br>
        {{ __('db.New Due') }}: <span id="cl-new-due-display">0.00</span><br>
        {{ __('db.Total Due After Sale') }}: <span id="cl-total-due-display">0.00</span>
    </div>
</div>

<div id="sale-request-error-box"
     class="alert alert-danger"
     role="alert"
     aria-live="assertive"
     style="display:none; margin-top:10px;">
    <strong id="sale-request-error-message"></strong>
</div>

@push('scripts')
<script>
(function ($) {
    'use strict';

    if (window.SaleCreditLimit && window.SaleRequestFeedback) {
        return;
    }

    let creditCheckToken = 0;
    let currentCreditXhr = null;
    let refreshTimer = null;

    const customerDueUrlTemplate = @json(url('customer/__CUSTOMER_ID__/due'));
    const genericRequestError = @json(__('db.generic_error_please_try_again'));
    const creditVerificationError = @json(__('db.Could not verify credit limit. Server error.'));
    const errorLabel = @json(__('db.Error'));
    const noCreditAllowedLabel = @json(__('db.No Credit Allowed'));
    const creditLimitExceededLabel = @json(__('db.Credit limit exceeded'));

    const originalSaleData = @json($originalSaleCreditData);

    function scrollToBox($box) {
        if (!$box.length || !$box.is(':visible')) {
            return;
        }

        $('html, body').animate({
            scrollTop: Math.max(0, $box.offset().top - 120)
        }, 200);
    }

    function parseMoney(value) {
        const parsed = parseFloat(String(value ?? '').replace(/,/g, '').trim());
        return Number.isNaN(parsed) ? 0 : parsed;
    }

    function getGrandTotal() {
        const $grandTotalInput = $('input[name="grand_total"]').first();
        if ($grandTotalInput.length) {
            return parseMoney($grandTotalInput.val());
        }

        return parseMoney($('#grand-total').text());
    }

    function getTotalPaid() {
        /*
         * POS maintains .total_paying as the amount actually applied to the sale.
         * It excludes change and excludes the financed portion of a Multiple Payment
         * Credit Sale row. Once present, it is the authoritative POS value.
         */
        const $posTotalPaying = $('.total_paying').first();
        if ($posTotalPaying.length) {
            return parseMoney($posTotalPaying.text());
        }

        let totalPaid = 0;
        const $arrayInputs = $('input[name="paid_amount[]"]');

        if ($arrayInputs.length) {
            $arrayInputs.each(function (index) {
                const $input = $(this);
                let $row = $input.closest('.new-row, .row');
                let $paymentSelect = $row.find('select[name="paid_by_id_select[]"]').first();

                if (!$paymentSelect.length) {
                    $paymentSelect = $('select[name="paid_by_id_select[]"]').eq(index);
                }

                if ($paymentSelect.length && String($paymentSelect.val() || '') === 'credit_sale') {
                    return;
                }

                totalPaid += parseMoney($input.val());
            });
            return totalPaid;
        }

        const $singleInput = $('input[name="paid_amount"]').first();
        if ($singleInput.length) {
            return parseMoney($singleInput.val());
        }

        return 0;
    }

    function getNewDue() {
        return Math.max(0, getGrandTotal() - getTotalPaid());
    }

    function isDraft() {
        const $saleStatus = $('[name="sale_status"]').first();
        return $saleStatus.length && String($saleStatus.val() || '') === '3';
    }

    function shouldCheck() {
        return !isDraft() && getGrandTotal() > 0 && getNewDue() > 0;
    }

    function clearCreditError() {
        $('#credit-limit-error-box').hide();
        $('#cl-error-details').show();
        $('#cl-error-message').text('');
        $('#cl-limit-display').text('0.00');
        $('#cl-existing-due-display').text('0.00');
        $('#cl-new-due-display').text('0.00');
        $('#cl-total-due-display').text('0.00');
    }

    function showNoCreditError(newDue, focusError) {
        $('#cl-error-title').text(noCreditAllowedLabel);
        $('#cl-error-message').text('');
        $('#cl-error-details').show();
        $('#cl-limit-display').text('0.00 (' + noCreditAllowedLabel + ')');
        $('#cl-existing-due-display').text('-');
        $('#cl-new-due-display').text(newDue.toFixed(2));
        $('#cl-total-due-display').text('-');
        $('#credit-limit-error-box').show();

        if (focusError) {
            scrollToBox($('#credit-limit-error-box'));
        }
    }

    function showExceededError(creditLimit, existingDue, newDue, totalDueAfter, focusError) {
        $('#cl-error-title').text(creditLimitExceededLabel + '!');
        $('#cl-error-message').text('');
        $('#cl-error-details').show();
        $('#cl-limit-display').text(creditLimit.toFixed(2));
        $('#cl-existing-due-display').text(existingDue.toFixed(2));
        $('#cl-new-due-display').text(newDue.toFixed(2));
        $('#cl-total-due-display').text(totalDueAfter.toFixed(2));
        $('#credit-limit-error-box').show();

        if (focusError) {
            scrollToBox($('#credit-limit-error-box'));
        }
    }

    function showVerificationError(newDue, focusError) {
        $('#cl-error-title').text(creditVerificationError);
        $('#cl-error-message').text('');
        $('#cl-error-details').show();
        $('#cl-limit-display').text(errorLabel);
        $('#cl-existing-due-display').text(errorLabel);
        $('#cl-new-due-display').text(newDue.toFixed(2));
        $('#cl-total-due-display').text(errorLabel);
        $('#credit-limit-error-box').show();

        if (focusError) {
            scrollToBox($('#credit-limit-error-box'));
        }
    }

    function abortCurrentCreditRequest() {
        if (currentCreditXhr) {
            currentCreditXhr.abort();
            currentCreditXhr = null;
        }
    }

    function resetCreditCheck() {
        creditCheckToken++;
        abortCurrentCreditRequest();
        clearCreditError();
    }

    function validateCredit(options) {
        options = options || {};
        const focusError = options.focusError === true;
        const deferred = $.Deferred();

        // A pending debounced refresh must never cancel a final submit-time check.
        clearTimeout(refreshTimer);
        refreshTimer = null;

        creditCheckToken++;
        const currentToken = creditCheckToken;

        if (!shouldCheck()) {
            abortCurrentCreditRequest();
            clearCreditError();
            deferred.resolve(true);
            return deferred.promise();
        }

        const $selectedCustomer = $('#customer_id option:selected');
        if (!$selectedCustomer.length || !$selectedCustomer.val()) {
            abortCurrentCreditRequest();
            clearCreditError();
            deferred.resolve(true);
            return deferred.promise();
        }

        const customerId = $selectedCustomer.val();
        const customerType = String($selectedCustomer.attr('data-type') || $selectedCustomer.data('type') || '').toLowerCase().trim();
        const creditLimit = parseMoney($selectedCustomer.attr('data-credit-limit'));
        const newDue = getNewDue();

        if (customerType === 'walkin') {
            abortCurrentCreditRequest();
            showNoCreditError(newDue, focusError);
            deferred.resolve(false);
            return deferred.promise();
        }

        if (creditLimit <= 0) {
            abortCurrentCreditRequest();
            showNoCreditError(newDue, focusError);
            deferred.resolve(false);
            return deferred.promise();
        }

        abortCurrentCreditRequest();

        currentCreditXhr = $.ajax({
            url: customerDueUrlTemplate.replace('__CUSTOMER_ID__', customerId),
            type: 'GET'
        });

        currentCreditXhr.done(function (existingDueRaw) {
            if (currentToken !== creditCheckToken) {
                deferred.resolve(false);
                return;
            }

            let existingDue = parseMoney(existingDueRaw);

            if (
                originalSaleData &&
                String(originalSaleData.customer_id) === String(customerId)
            ) {
                existingDue = Math.max(0, existingDue - parseMoney(originalSaleData.due));
            }

            const totalDueAfter = existingDue + newDue;

            if (totalDueAfter > creditLimit) {
                showExceededError(creditLimit, existingDue, newDue, totalDueAfter, focusError);
                deferred.resolve(false);
                return;
            }

            clearCreditError();
            deferred.resolve(true);
        });

        currentCreditXhr.fail(function (xhr, status) {
            if (status === 'abort' || currentToken !== creditCheckToken) {
                deferred.resolve(false);
                return;
            }

            console.error('Credit limit verification failed:', xhr.status, xhr.responseText);
            showVerificationError(newDue, focusError);
            deferred.resolve(false);
        });

        currentCreditXhr.always(function () {
            if (currentToken === creditCheckToken) {
                currentCreditXhr = null;
            }
        });

        return deferred.promise();
    }

    function scheduleRefresh() {
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(function () {
            /*
             * Do not raise a credit warning while the user is still building the
             * sale/payment. Final submission is the authoritative validation gate.
             * Clearing here also removes a stale warning immediately after the
             * payment is corrected to fully paid.
             */
            resetCreditCheck();
        }, 200);
    }

    function extractRequestMessage(xhr) {
        const response = (xhr && xhr.responseJSON) ? xhr.responseJSON : {};

        if (response.message) {
            return String(response.message);
        }

        if (response.error) {
            return String(response.error);
        }

        if (response.errors && typeof response.errors === 'object') {
            const messages = [];

            Object.keys(response.errors).forEach(function (field) {
                const fieldErrors = response.errors[field];
                if (Array.isArray(fieldErrors)) {
                    fieldErrors.forEach(function (message) {
                        if (message) {
                            messages.push(String(message));
                        }
                    });
                } else if (fieldErrors) {
                    messages.push(String(fieldErrors));
                }
            });

            if (messages.length) {
                return messages.join(' ');
            }
        }

        return genericRequestError;
    }

    function showRequestError(message, focusError) {
        const safeMessage = String(message || genericRequestError);
        $('#sale-request-error-message').text(safeMessage);
        $('#sale-request-error-box').show();

        if (focusError !== false) {
            scrollToBox($('#sale-request-error-box'));
        }
    }

    function clearRequestError() {
        $('#sale-request-error-box').hide();
        $('#sale-request-error-message').text('');
    }

    window.SaleCreditLimit = {
        validate: validateCredit,
        refresh: scheduleRefresh,
        reset: resetCreditCheck,
        shouldCheck: shouldCheck,
        getGrandTotal: getGrandTotal,
        getTotalPaid: getTotalPaid,
        getNewDue: getNewDue,
        isDraft: isDraft
    };

    window.SaleRequestFeedback = {
        extractMessage: extractRequestMessage,
        show: showRequestError,
        clear: clearRequestError,
        showXhrError: function (xhr, focusError) {
            showRequestError(extractRequestMessage(xhr), focusError);
        }
    };

    // Backward-compatible aliases for existing callers.
    window.checkCreditLimit = function (options) {
        return validateCredit(options);
    };

    window.shouldCheckCreditLimit = shouldCheck;
    window.resetCreditLimitCheck = resetCreditCheck;

    $(document).ready(function () {
        $('#customer_id').on('change', scheduleRefresh);

        $(document).on(
            'input change',
            [
                'input[name="grand_total"]',
                'input[name="paid_amount"]',
                'input[name="paid_amount[]"]',
                'input[name="paying_amount[]"]',
                'select[name="payment_status"]',
                '[name="sale_status"]',
                'select[name="paid_by_id[]"]',
                'select[name="paid_by_id_select[]"]'
            ].join(','),
            scheduleRefresh
        );

        $(document).on('credit_limit_check_required', scheduleRefresh);
        $('#add-payment').on('shown.bs.modal hidden.bs.modal', scheduleRefresh);

        resetCreditCheck();
    });
})(jQuery);
</script>
@endpush
