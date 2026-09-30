function showSaleReturnQuantityError($form, $input) {
    var $message = $form.find('.sale-return-quantity-error');

    if (!$message.length) {
        $message = $('<div>', {
            'class': 'alert alert-danger sale-return-quantity-error',
            'role': 'alert',
            'text': 'Quantity must be greater than zero.'
        });
        $form.prepend($message);
    }

    $message.removeClass('d-none').attr('tabindex', '-1').focus();
    $input.addClass('is-invalid').attr('aria-invalid', 'true').focus();
}

function validateSaleReturnQuantities($form) {
    var $invalidInput = null;

    $form.find('input.qty[name="qty[]"]').each(function() {
        var $input = $(this);
        var inputValue = $input.val();
        var rawQuantity = String(inputValue === null ? '' : inputValue).trim();
        var quantity = Number(rawQuantity);

        $input.removeClass('is-invalid').removeAttr('aria-invalid');

        if (rawQuantity === '' || !Number.isFinite(quantity) || quantity <= 0) {
            $invalidInput = $input;
            return false;
        }
    });

    if ($invalidInput) {
        showSaleReturnQuantityError($form, $invalidInput);
        return false;
    }

    $form.find('.sale-return-quantity-error').addClass('d-none');
    return true;
}

$(document).on('input change', '.sale-return-form input.qty[name="qty[]"]', function() {
    var inputValue = $(this).val();
    var rawQuantity = String(inputValue === null ? '' : inputValue).trim();
    var quantity = Number(rawQuantity);

    if (rawQuantity !== '' && Number.isFinite(quantity) && quantity > 0) {
        $(this).removeClass('is-invalid').removeAttr('aria-invalid');

        var $form = $(this).closest('.sale-return-form');
        if (!$form.find('input.qty.is-invalid').length) {
            $form.find('.sale-return-quantity-error').addClass('d-none');
        }
    }
});

document.addEventListener('invalid', function(event) {
    var $input = $(event.target);

    if (!$input.is('.sale-return-form input.qty[name="qty[]"]')) {
        return;
    }

    var inputValue = $input.val();
    var rawQuantity = String(inputValue === null ? '' : inputValue).trim();
    var quantity = Number(rawQuantity);
    if (rawQuantity === '' || !Number.isFinite(quantity) || quantity <= 0) {
        showSaleReturnQuantityError($input.closest('.sale-return-form'), $input);
    }
}, true);
