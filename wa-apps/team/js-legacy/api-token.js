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
            var $token_item = $(this).parents('.js-token-item'),
                $icon = $token_item.find('.icon16'),
                token_id = $token_item.data('token'),
                contact_id = $token_item.data('contact-id'),
                href = '?module=apiTokensRemove',
                data = {action: 'remove', token_id: token_id, contact_id: contact_id};

            if (!is_locked && token_id && confirm(that.locale['remove_ask'])) {
                is_locked = true;

                $icon.removeClass('no').addClass('loading');

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
                        $icon.removeClass('loading').addClass('no');
                    }
                }).always( function() {
                    is_locked = false;
                    $icon.removeClass('loading').addClass('no');
                });
            }
        });
    };

    return SiteApiTokenPage;

})(jQuery);