var newsWidget = ( function($) {

    newsWidget = function(options) {
        var that = this;

        // DOM
        that.widget_id = options.widget_id;

        // DYNAMIC VARS
        that.uniqid = (new Date).getTime() + Math.random();

        // INIT
        that.autoReload();
    };

    newsWidget.prototype.autoReload = function() {
        var that = this;

        setTimeout(function() {
            try {
                DashboardWidgets[that.widget_id].uniqid = that.uniqid;
                setTimeout(function() {
                    try {
                        if (that.uniqid == DashboardWidgets[that.widget_id].uniqid) {
                            DashboardWidgets[that.widget_id].renderWidget();
                        }
                    } catch (e) {
                        console && console.log('Error updating News widget', e);
                    }
                }, 60*2*1000);
            } catch (e) {
                console && console.log('Error setting up News widget updater', e);
            }
        }, 0);
    };

    return newsWidget;

})(jQuery);