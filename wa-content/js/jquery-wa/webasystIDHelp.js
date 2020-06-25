var webasystIDHelp = ( function($) {

    var webasystIDHelp = function(options) {
        var that = this;

        // DOM
        that.$dialog = options.$dialog;

        // VARS
        that.dialog = null;
        that.steps = options.steps || 4;    // total number of steps in dialog
        that.current_step = 1;              // current step number (start from 1, not 0)
        // DYNAMIC VARS

        // INIT
        $.waDialog({
            html: that.$dialog,
            animate: false,
            onOpen: function ($dialog, dialog) {
                that.dialog = dialog;
                that.init();
            },
            onClose: function () {
                that.onClose();
            }
        });

    };

    webasystIDHelp.prototype.init = function() {
        var that = this;
        that.initNavigation();
        that.initAuth();
        that.initConnect();
        setTimeout(function () {
            that.dialog.show();
            that.dialog.resize();
        }, 200);
    };

    webasystIDHelp.prototype.initNavigation = function() {
        var that = this,
            $dialog = that.$dialog,
            $back = $dialog.find('.js-back'),
            $next = $dialog.find('.js-next'),
            $finish = $dialog.find('.js-finish'),
            $steps = $dialog.find('.js-step'),
            $dots = $dialog.find('.js-dots li');

        var renderCurrentStep = function() {
            $steps.hide();
            $steps.filter('[data-id="' + that.current_step + '"]').show();
        };

        var renderNavButtons = function() {
            $back.show();
            $next.show();
            $finish.hide();
            if (that.current_step <= 1) {
                $back.hide();
            } else if (that.current_step >= that.steps) {
                $next.hide();
                $finish.show();
            }
        };

        var renderNavDots = function() {
            $dots.filter('[data-id="' + that.current_step + '"]').addClass('active').siblings().removeClass('active');
        };

        $back.on('click', function (e) {
            e.preventDefault();
            that.current_step = Math.max(that.current_step - 1, 0);
            renderCurrentStep();
            renderNavDots();
            renderNavButtons();
        });

        $next.on('click', function (e) {
            e.preventDefault();
            that.current_step = Math.min(that.current_step + 1, that.steps);
            renderCurrentStep();
            renderNavDots();
            renderNavButtons();
        });
    };

    webasystIDHelp.prototype.initAuth = function(oauth_modal) {
        var that = this,
            $dialog = that.$dialog,
            $link = $dialog.find('.js-auth');

        $link.on('click', function (e) {
            e.preventDefault();
            
            var href = $(this).attr('href');

            if (!oauth_modal) {
                var referrer_url = window.location.href;
                window.location = href + '&referrer_url=' + referrer_url;
                return;
            }

            var width = 600;
            var height = 500;
            var left = (screen.width - width) / 2;
            var top = (screen.height - height) / 2;

            window.open(href,'oauth', "width=" + 600 + ",height=" + height + ",left="+left+",top="+top+",status=no,toolbar=no,menubar=no");
            return false;
        });
    };

    webasystIDHelp.prototype.initConnect = function() {
        var that = this,
            $dialog = that.$dialog,
            dialog = that.dialog,
            $link = $dialog.find('.js-connect');
            $link.on('click', function () {
                dialog.close();
            });
    };

    webasystIDHelp.prototype.onClose = function () {
        var that = this;
    };

    return webasystIDHelp;

})(jQuery);
