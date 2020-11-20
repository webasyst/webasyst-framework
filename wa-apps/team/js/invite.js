var InvitePage = ( function($) {

    InvitePage = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$block = that.$wrapper.find(".t-invite-block");
        that.$form = that.$wrapper.find("form");

        // VARS
        that.errors = options["errors"];
        that.error_class = "error";
        that.backend_url = options.backend_url || '';

        // DYNAMIC VARS
        that.is_locked = false;

        // INIT
        that.initClass();
    };

    InvitePage.prototype.initClass = function() {
        var that = this;
        //
        that.showErrors();
        //
        that.bindEvents();
        //
        that.autoCenter();

        that.initSubmit();

        that.initProfileWebasystIDHelpLink();
    };

    InvitePage.prototype.bindEvents = function() {
        var that = this,
            $form = that.$form;

        $form.on("keydown change", "." + that.error_class, function() {
            $(this)
                .removeClass(that.error_class)
                .parent()
                    .find(".errormsg").remove();
        });

        $(window).on("resize", function() {
            that.autoCenter();
        });
    };

    InvitePage.prototype.showErrors = function() {
        var that = this,
            $form = that.$form,
            errors = that.errors,
            $submit = $form.find(':submit:first');

        $.each(errors, function(field, error_text) {
            var $field = $form.find('[name="data['+field+']"]');
            if (!$field.length) {
                $field = $submit;
            } else {
                $field.addClass(that.error_class);
            }
            $field.parent().append($('<em class="errormsg"></em>').text(error_text));
        });
    };

    InvitePage.prototype.autoCenter = function() {
        var that = this,
            $block = that.$block,
            block_h = $block.outerHeight(),
            window_h = $(window).height(),
            top,
            min = 10;

        top = parseInt( (window_h - block_h)/2 );

        if (top < min) {
            top = min;
        }

        $block.css({
            top: top
        });
    };

    InvitePage.prototype.initProfileWebasystIDHelpLink = function() {
        var that = this;

        var onHelp = function() {
            var url = that.backend_url + "?module=backend&action=webasystIDHelp";
            $.get(url, function (html) {
                $('body').append(html);
            });
        };

        // click on link in current document
        $('.js-waid-hint').on('click', function (e) {
            e.preventDefault();
            onHelp();
        });
    };

    return InvitePage;

})(jQuery);
