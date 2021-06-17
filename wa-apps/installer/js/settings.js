(function ($) {

    // hack from here: https://stackoverflow.com/questions/51805395/navigator-clipboard-is-undefined
    function copyToClipboard(textToCopy) {
        // navigator clipboard api needs a secure context (https)
        if (navigator.clipboard && window.isSecureContext) {
            // navigator clipboard api method'
            return navigator.clipboard.writeText(textToCopy);
        } else {
            // text area method
            let textArea = document.createElement("textarea");
            textArea.value = textToCopy;
            // make the textarea out of viewport
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            return new Promise((res, rej) => {
                // here the magic happens
                document.execCommand('copy') ? res() : rej();
                textArea.remove();
            });
        }
    }


    var initShowStaticIDLink = function () {
        var $link = $('.js-show-installer-static-id'),
            $copy_link = $('.js-installer-static-id-copy'),
            $static_id = $('.js-installer-static-id'),
            $copied_success = $('.js-installer-static-id-copied'),
            is_loading = false;

        $link.on('click', function (e) {
            e.preventDefault();

            if (is_loading) {
                return;
            }

            is_loading = true;
            $link.find('.loading').show();

            $.get('?module=settings&action=staticID')
                .done(function (r) {
                    var static_id = '';
                    if (r && r.data && r.data.id) {
                        static_id = r.data.id;
                    }
                    $link.remove();
                    $static_id.text(static_id);
                    $copy_link.show();
                })
                .always(function () {
                    is_loading = false;
                    $link.find('.loading').hide();
                });
        });

        $copy_link.click(function () {
            copyToClipboard($static_id.text()).then(function() {
                $copied_success.show();
                setTimeout(function() {
                    $copied_success.fadeOut();
                }, 2000);
            });

        });
    };

    $.installer_settings = {
        options: {
            url: '?module=settings&action=clearCache',
            loading: '<i class="icon16 loading"></i>',
            container: '#installer-cache-state'
        },
        container: null,
        init: function (options) {
            var that = this;

            this.container = $(that.options.container);

            $('input[name="clear_cache"]').on('click', function (eventObject) {
                return that.clear_cache.apply(that, [that, eventObject]);
            });
            
            initShowStaticIDLink();

        },
        clear_cache: function () {
            $.ajax({
                url: this.options.url,
                type: 'GET',
                dataType: 'json',
                success: this.response_handler,
                beforeSend: this.show_loader
            });
        },
        show_loader: function () {
            var self = $.installer_settings;
            self.container.html(self.options.loading).show();
        },
        response_handler: function (data, textStatus, XMLHttpRequest) {
            var self = $.installer_settings;
            try {
                if (data.status == 'ok') {
                    self.container.html(data.data.message).show().css('color', 'green');
                } else {
                    var error = '';
                    for (var error_item in data.errors) {
                        error += (error ? '\n' : '') + data.errors[error_item][0];
                    }
                    self.container.html('&mdash;&nbsp;' + error).show().css('color', 'red');
                }
            } catch (e) {
            }

        }
    };
})(jQuery, this);
