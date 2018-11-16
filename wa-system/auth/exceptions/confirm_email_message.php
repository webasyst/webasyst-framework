<?php
    $class_id = 'wa-confirmation-email-block-wrapper';
    $wrapper_id = uniqid($class_id);
?>
<div class="<?php echo $class_id; ?>" id="<?php echo $wrapper_id; ?>">
    <div class="block-confirmation-email"><?php echo $message; ?></div>
    <script type="text/javascript">
        $(function () {
            var wrapper_id = '<?php echo $wrapper_id; ?>',
                $wrapper = $('#' + wrapper_id);
            $wrapper.find('a:first').click(function () {
                var $link = $(this),
                    $form = $link.closest('form');
                if (!$form.length) {
                    $form = $link.closest('.js-wa-form-item')
                }
                var data = {
                    login: $form.find("input[name='login']").val()
                };
                $.ajax({
                    url: $(this).attr('href'),
                    data: data,
                    dataType: 'text',
                    method: 'POST'
                }).done(function (response) {
                    var r = null;
                    try {
                        r = $.parseJSON(response)
                    } catch (e) {

                    }
                    if (!r) {
                        // html - backward compatibility
                        $('.block-confirmation-email').html(response);
                        return;
                    }
                    var ok = r && r.status === 'ok',
                        errors = (r && r.errors) || {},
                        data = (r && r.data) || {};

                    if (!ok) {
                        if (console && console.error) {
                            console.error(errors);
                        }
                        return;
                    }

                    if (data && data.redirect_url) {
                        window.location.href = data.redirect_url;
                    }

                    //
                })
                return false;
            });
        });
    </script>
</div>
