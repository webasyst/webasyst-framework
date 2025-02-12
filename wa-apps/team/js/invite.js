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
        that.initSubmit();
    };

    InvitePage.prototype.bindEvents = function() {
        var that = this,
            $form = that.$form;

        $form.on("keydown change", "." + that.error_class, function() {
            $(this)
                .removeClass(that.error_class)
                .parent()
                    .find(".state-error-hint").remove();
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
            $field.parent().append($('<div class="state-error-hint custom-mt-4 custom-ml-2"></div>').text(error_text));
        });
    };

    InvitePage.prototype.initSubmit = function() {
        this.$form.find('[type="submit"]').on('click', function () {
            setTimeout(() => $(this).attr('disabled', true));
        });
    };

    return InvitePage;

})(jQuery);
