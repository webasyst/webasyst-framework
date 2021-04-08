var $currency_sign = $('[name="currency_sign"]');

$currency_sign.on('click', function() {
    $('.save-button').addClass('yellow');
});

$('[name="currency_custom_sign"], [name="status"]').on('change', function() {
    $('.save-button').addClass('yellow');
});

$('[name="status"]').on('change', function() {
    if ($(this).val() == 1) {
        $currency_sign.removeAttr('disabled');
    } else {
        $currency_sign.attr('disabled', 1);
    }
});

$("#s-settings-form").submit(function (e) {
    var form = $(this);
    e.preventDefault();
    $.post(form.attr('action'), form.serialize(), function (response) {
        if (response.status === 'ok') {
            $('.save-button').removeClass('yellow');
            $('.success-save').fadeIn();
            setTimeout(function() {
                $('.success-save').fadeOut();
            }, 1000);
        }
    }, 'json');
});