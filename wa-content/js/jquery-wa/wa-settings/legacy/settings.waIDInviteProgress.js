var WASettingsWaIDInviteProgress = ( function($) {

    WASettingsWaIDInviteProgress = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options.$wrapper;
        that.$progress_bar = that.$wrapper.find('.js-invite-progressbar')
        that.$in_progress_icon = that.$wrapper.find('.js-in-progress');
        that.$done_icon = that.$wrapper.find('.js-done');
        that.$fail_icon = that.$wrapper.find('.js-fail');
        that.$error_msg = that.$wrapper.find('.js-error-msg');
        that.$report = that.$wrapper.find('.s-waid-report');
        that.$warning = that.$wrapper.find('.js-warning');

        // VARS
        that.wa_backend_url = options.wa_backend_url || '';
        that.url = options.url || '';
        that.processId = null;
        that.progress_deferred = $.Deferred();
        that.onStepDone = options.onStepDone;

        // INIT
        that.init();
    };

    WASettingsWaIDInviteProgress.prototype.init = function() {
        var that = this;

        that.$progress_bar.hide();
        that.$progress_bar.find('.js-invite-progressbar-progress').css({ width: 0 })
    };

    WASettingsWaIDInviteProgress.prototype.run = function () {
        var that = this;

        // already in running progress
        if (that.processId) {
            return that.progress_deferred;
        }

        that.$progress_bar.show();
        that.$progress_bar.find('.js-invite-progressbar-progress').css({ width: 0 })
        that.$in_progress_icon.removeClass('hidden');
        that.$warning.removeClass('hidden');
        that.$fail_icon.addClass('hidden');
        that.$report.addClass('hidden');
        that.$error_msg.addClass('hidden');

        var step = function () {
            return sendRequest().done(onRequestDone);
        };

        var sendRequest = function () {
            return $.post(that.url, { processId: that.processId, t: Math.random() }, 'json');
        };

        var onComplete = function (response) {
            $.post(that.url, { processId: that.processId, cleanup: 1 }, 'json');

            that.$in_progress_icon.addClass('hidden');
            that.$warning.addClass('hidden');
            that.$done_icon.removeClass('hidden');

            if (response && response.report) {
                that.$report.removeClass('hidden').text(response.report);
            }

            that.progress_deferred.resolve();
        };

        var onRequestDone = function (response) {
            var duration = 250;

            var $progress_bar_val = that.$progress_bar.find('.js-invite-progressbar-progress');
            $progress_bar_val.stop();
            $progress_bar_val.clearQueue();

            if (response.ready) {
                $progress_bar_val.animate({ width: '100%' }, {
                    duration: duration,
                    complete: function () {
                        that.onStepDone && that.onStepDone(response);
                        onComplete(response);
                    },
                    queue: false
                });
                return;
            }

            $progress_bar_val.animate({ width: ""+Math.round( response.done * 100.0 / response.total ) + '%' }, {
                duration: duration,
                queue: false
            });

            setTimeout(step, 2000 + (Math.random() - 0.5) * 400);

            that.onStepDone && that.onStepDone(response);

        };

        var start = function() {
            $.post(that.url, { t: Math.random() }, function (data) {
                that.processId = data && data.processId;
                if (that.processId) {
                    that.$progress_bar.find('.js-invite-progressbar-progress').css({ width: 0 });
                    step();
                } else {
                    var error = data && data.error;
                    that.$progress_bar.find('.js-invite-progressbar-progress').css({ width: '100%' });
                    that.$in_progress_icon.addClass('hidden');
                    that.$warning.addClass('hidden');
                    that.$fail_icon.removeClass('hidden');
                    if (error) {
                        that.$error_msg.removeClass('hidden').html(error);
                    }
                    that.progress_deferred.reject();
                }
            }, 'json');
        };

        start();


        return that.progress_deferred;
    };

    return WASettingsWaIDInviteProgress;

})(jQuery);
