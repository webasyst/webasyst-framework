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

    $.installer_settings = {
        options: {
            url: '?module=settings&action=clearCache',
            container: '#installer-cache-state',
            containerStatus: '.js-container-status',
            messages: {}
        },
        container: null,
        containerStatus: null,
        init: function (options) {
            var that = this;

            options = options || {};

            this.container = $(that.options.container);
            this.containerStatus = $(that.options.containerStatus);

            $('input[name="clear_cache"]').on('click', function (eventObject) {
                return that.clear_cache.apply(that, [that, eventObject]);
            });

            that.options.messages = options.messages || {};
            that.options.loading = options.loading || '<i class="fas fa-spinner fa-spin"></i>';

            that.initShowStaticIDLink();
            that.initDisconnectBetaTestProductLink(that.options.messages);

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
                    self.containerStatus.html(data.data.message).show().addClass('state-success-hint').css('color', 'green');
                } else {
                    var error = '';
                    for (var error_item in data.errors) {
                        error += (error ? '\n' : '') + data.errors[error_item][0];
                    }
                    self.containerStatus.html('&mdash;&nbsp;' + error).show().addClass('state-error-hint red');
                }
            } catch (e) {
                console.log(e)
            } finally {
                self.container.empty();
            }
        },

        initShowStaticIDLink: function () {
            var $link = $('.js-show-installer-static-id'),
                $copy_link = $('.js-installer-static-id-copy'),
                $static_id = $('.js-installer-static-id'),
                $copied_success = $('.js-installer-static-id-copied'),
                $beta_test_products_wrapper = $('.js-beta-test-products-wrapper'),
                $beta_test_products = $('.js-beta-test-products'),
                $beta_test_product_template = $beta_test_products.find('.js-beta-test-product.js-is-template'),
                is_loading = false;

            var showStaticID = function (static_id) {
                $link.remove();
                $static_id.text(static_id);
                $copy_link.show();
            };

            var showBetaTestProducts = function (products) {
                if ($.isEmptyObject(products)) {
                    return;
                }

                $beta_test_products.children().not($beta_test_product_template).remove();
                $beta_test_products_wrapper.show();

                $.each(products, function (_, product) {
                    var $product = $beta_test_product_template.clone().fadeIn();
                    $product.find('.js-name').text(product.name || '');
                    $product.data('id', product.id);
                    $beta_test_products.prepend($product);
                });
            };

            $link.on('click', function (e) {
                e.preventDefault();

                if (is_loading) {
                    return;
                }

                is_loading = true;
                $link.find('.loading').show();

                $.get('?module=settings&action=staticID')
                    .done(function (r) {
                        var static_id = '',
                            products = [];
                        if (r && r.data) {
                            static_id = r.data.id || '';
                            products = r.data.beta_test_products || [];
                        }
                        showStaticID(static_id);
                        showBetaTestProducts(products)

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
        },

        initDisconnectBetaTestProductLink: function (messages) {

            var that = this,
              $beta_test_products_wrapper = $('.js-beta-test-products-wrapper');

            var onClickDisconnectLink = function ($link) {
                const is_loading = $link.data('is_loading');
                if (is_loading) {
                    return;
                }

                if (typeof $.waDialog.confirm === 'undefined') {
                    let confirmDisconnect = confirm(that.options.messages.confirm_disconnect);

                    if (confirmDisconnect) {
                        disconnectFromBeta();
                    }

                    return;
                }

                $.waDialog.confirm({
                    title: that.options.messages.confirm_disconnect,
                    success_button_title: that.options.messages.disconnect,
                    success_button_class: 'danger',
                    cancel_button_title: that.options.messages.cancel,
                    cancel_button_class: 'light-gray',
                    onSuccess: function() {
                        disconnectFromBeta();
                    }
                });

                function disconnectFromBeta() {
                    $link.find('.loading').show();
                    $link.data('is_loading', true);

                    const $product = $link.closest('.js-beta-test-product');
                    const id = $product.data('id');

                    $.post('?module=settings&action=disconnectBetaTestProduct', { product_id: id })
                      .done(function (r) {
                          if (r && r.status === 'ok') {
                              $product.remove();
                          }
                      })
                      .always(function () {
                          $link.find('.loading').hide();
                          $link.data('is_loading', false);
                      });
                }
            };

            $beta_test_products_wrapper.on('click', '.js-disconnect-beta-test-product', function (e) {
                e.preventDefault();
                onClickDisconnectLink($(this));
            });
        },
    };
})(jQuery, this);
