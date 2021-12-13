var SiteApiTokenPage = ( function($) {

    SiteApiTokenPage = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];

        // VARS
        that.locale = options["locale"];

        // DYNAMIC VARS

        // INIT
        that.initClass();
    };

    SiteApiTokenPage.prototype.initClass = function() {
        var that = this;

        that.initRemoveApiToken();
    };

    SiteApiTokenPage.prototype.initRemoveApiToken = function() {
        var that = this,
            $list_table = that.$wrapper.find('.js-api-tokens-list'),
            is_locked;

        that.$wrapper.on('click', '.js-remove-api-token', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            var $token_item = $(this).closest('.js-token-item'),
                $icon = $token_item.find('svg'),
                token_id = $token_item.data('token'),
                contact_id = $token_item.data('contact-id'),
                href = '?module=apiTokensRemove',
                data = {action: 'remove', token_id: token_id, contact_id: contact_id};

            $.waDialog.confirm({
                title: that.locale['remove_ask'],
                success_button_title: that.locale.delete,
                success_button_class: 'danger',
                cancel_button_title: that.locale.cancel,
                cancel_button_class: 'light-gray',
                onSuccess() {
                    if (is_locked && !token_id) {
                        console.warn(`System error. is_locked=${is_locked}. token_id=${token_id}`);
                        return;
                    }

                    is_locked = true;

                    $icon.removeClass('fa-times').addClass('fa-spin fa-spinner wa-animation-spin speed-1000');

                    $.post(href, data, function(res) {
                        if (res.status && res.status === 'ok') {
                            // Remove tr from tokens list
                            $token_item.remove();
                            // Remove the entire list if it is empty
                            if ($list_table.find('.js-token-item').length === 0) {
                                //that.$wrapper.remove();
                            }
                        } else {
                            is_locked = false;
                            $icon.removeClass('fa-spin fa-spinner wa-animation-spin speed-1000').addClass('fa-times');
                        }
                    }).always( function() {
                        is_locked = false;
                        $icon.removeClass('fa-spin fa-spinner wa-animation-spin speed-1000').addClass('fa-times');
                    });
                }
            });
        });
    };

    return SiteApiTokenPage;

})(jQuery);
