/**
 *
 */
(function($) {
    $.installer_settings = {
        options : {
            'url' : '?module=settings&action=clearCache',
            'loading' : '<i class="icon16 loading"></i>',
            'container' : '#installer-cache-state'
        },
        container : null,
        init : function(options) {
            this.container = $(this.options.container);
            var self = this;
            $('input[name=clear_cache]').click(function(eventObject) {
                return self.clear_cache.apply(self, [this, eventObject]);
            });
        },
        clear_cache : function() {
            $.ajax({
                'url' : this.options.url,
                'type' : 'GET',
                'dataType' : 'json',
                'success' : this.response_handler,
                'beforeSend' : this.show_loader
            });
        },
        show_loader : function() {
            var self = $.installer_settings;
            self.container.html(self.options.loading).show();
        },
        response_handler : function(data, textStatus, XMLHttpRequest) {
            var self = $.installer_settings;
            try {
                if (data.status == 'ok') {
                    self.container.html(data.data.message).show().css('color', 'green');
                } else {
                    var error = '';
                    for (error_item in data.errors) {
                        error += (error ? '\n' : '') + data.errors[error_item][0];
                    }
                    self.container.html('&mdash;&nbsp;' + error).show().css('color', 'red');
                }
            } catch (e) {
            }

        }
    };
})(jQuery, this);
