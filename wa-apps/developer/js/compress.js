$(function () {
    'use strict';
    const $form = $('#d-form-compress');

    $form.on('submit', function (event) {
        event.preventDefault();
        $.post(
            window.location,
            $form.serialize(),
            function (response) {
                if (response.status === 'fail') {
                    $('#d-compress-result').html(`<pre class="errormsg">${response.error}</pre>`);
                } else {
                    $('#d-compress-result').html(`<pre class="successmsg">${response.data}</pre>`);
                    $form.trigger('reset');
                }
            }
        );
    });

    $('#d-product-category').on('change', function () {
        let type = $(this).val();
        $('.field', $form).show();
        $('.field.hide-all,.field.hide-' + type, $form).hide();
        $('.field.show-' + type, $form).show();
        
        const $app = $('#d-product-app');
        $app.val('').trigger('change');
        $('option', $app).show();
        if (type == 'theme' || type == 'plugin') {
            $('option', $app).each(function () {
                const $option = $(this);
                if (!$option.data(type + 's')) {
                    $option.hide();
                }
            });
        }
    }).trigger('change');
});