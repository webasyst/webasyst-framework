(function($) {
    $.fn.activeMenu = function(options, ext, value) {

        if (typeof options == 'string') {
            if (options == 'disable' || options == 'enable') {
                if (!$.isArray(ext)) {
                    ext = [ext];
                }
                this.find('li').each(function() {
                    var self = $(this);
                    if (~ext.indexOf(self.attr('data-action'))) {
                        options == 'disable' ? self.hide() : self.show();
                    }
                });
                return this;
            }
            if (options == 'setOption') {
                var settings = this.data('activeMenuSettings');
                if (typeof ext === 'string') {
                    var o = {};
                    o[ext] = value;
                    ext = o;
                }
                $.extend(settings, ext);
                return this;
            }
        }

        if (typeof options == 'string') {
            if (options == 'fire') {
                var settings = this.data('activeMenuSettings');
                settings['on'+options.substr(0,1).toUpperCase() + options.substr(1)].call(this);
                return this;
            }
        };

        this.data('activeMenuSettings', $.extend({
            beforeAnyAction: function() {},
            defaultAction: function() {},
            onInit: function(){},
            onFire: function(){}
        }, options || {}));

        var settings = this.data('activeMenuSettings');


        init.call(this);

        function init() {
            var self = this;
            if (this.data('inited')) {  // has inited already. Don't init again
                return;
            }
            this.click(function(e) {
                var item = e.target;
                var root = self.get(0);
                while (item.tagName != 'LI') {
                    if (item == root) {
                        return;
                    }
                    item = $(item).parent().get(0);
                }
                item = $(item);
                var action = item.attr('data-action') || 'default',
                    parts = action.split('-');
                for (var i = 1; i < parts.length; i++) {
                    parts[i] = parts[i][0].toUpperCase() + parts[i].slice(1);
                }
                action = parts.join('');
                var callback_name = action + 'Action';
                if (!settings[callback_name] || typeof settings[callback_name] != 'function') {
                    callback_name = 'defaultAction';
                }
                var r = settings.beforeAnyAction(item.attr('data-action'));
                if (r !== false) {
                    settings[callback_name](item, e);
                }
                return false;
            });
            settings.onInit(this);
            this.data('inited', true);
        }

        return this;

    };
})(jQuery);