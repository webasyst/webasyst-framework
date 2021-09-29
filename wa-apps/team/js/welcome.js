var WelcomePage = ( function($) {

    WelcomePage = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$inviteWrapper = that.$wrapper.find("#t-invite-wrapper");
        that.$inviteList = that.$inviteWrapper.find(".t-invite-list");

        // VARS
        that.locales = options["locales"];
        that.error_class = "state-error";

        // DYNAMIC VARS
        that.is_locked = false;
        that.switch_count = 3;

        // INIT
        that.initClass();
    };

    WelcomePage.prototype.initClass = function() {
        var that = this;

        // Disable ajax links
        $(document).on("ready", function () {
            $.team.content.is_enabled = false;
        });

        that.initWaToggle();

        that.bindEvents();
    };

    WelcomePage.prototype.bindEvents = function() {
        var that = this;

        that.$wrapper.on("click", ".js-skip-page", function(event) {
            event.stopPropagation();
        });

        that.$wrapper.on("click", ".js-send-invites", function(event) {
            event.preventDefault();
            if (!that.is_locked) {
                that.sendInvites();
            }
        });

        that.$inviteWrapper.on("click", ".js-add-invite", function(event) {
            event.preventDefault();
            that.addNewInvite();
        });

        that.$inviteWrapper.on("click", ".js-remove-invite", function(event) {
            event.preventDefault();
            $(this).closest("li").remove();
        });

        that.$inviteWrapper.on("click", "." + that.error_class, function() {
            that.removeErrors( $(this) );
        });
    };

    WelcomePage.prototype.initWaToggle = function() {
        var that = this,
            $accessToggle = that.$inviteWrapper.find(".js-access-toggle");

        $accessToggle.waToggle({
            change(event, target, toggle) {
                toggle.$wrapper
                    .find('[type="hidden"]')
                    .prop('checked', target.dataset.type === 'full')
                    .attr('checked', target.dataset.type === 'full')
            }
        });
    };

    WelcomePage.prototype.addNewInvite = function() {
        var that = this,
            html = that.$inviteWrapper.find(".t-invite-template").clone().html(),
            $template = $("<li>" + html + "</li>");

        that.switch_count = that.switch_count + 1;

        $template
            .find('[type="checkbox"]')
            .attr('id', `access_switch_${that.switch_count}`)
        $template
            .find('label')
            .attr('for', `access_switch_${that.switch_count}`)

        that.$inviteList.append($template);

        that.initWaToggle();
    };

    WelcomePage.prototype.sendInvites = function() {
        var that = this,
            href = $.team.app_url + "?module=welcome&action=save",
            data = prepareData(),
            $send_invites_btn = that.$wrapper.find('.js-send-invites');

        that.is_locked = true;
        $send_invites_btn.attr('disabled', that.is_locked).find('svg').toggleClass('hidden', !that.is_locked);

        if (data) {
            $.post(href, data, function(response) {
                if (response.errors && response.errors.length) {
                    showErrors(response.errors);
                } else {
                    location.href = $.team.app_url;
                }
            }).always( function() {
                that.is_locked = false;
                $send_invites_btn.attr('disabled', that.is_locked).find('svg').toggleClass('hidden', !that.is_locked);
            });
        } else {
            that.is_locked = false;
            $send_invites_btn.attr('disabled', that.is_locked).find('svg').toggleClass('hidden', !that.is_locked);
        }

        function prepareData() {
            var result = [],
                $items = that.$inviteList.find(".t-invite-item"),
                errors = [];

            $items.each( function(index) {
                var $email = $(this).find('[type="email"]'),
                    $access = $(this).find('[type="checkbox"]'),
                    email = $email.val(),
                    access = $access.prop("checked");

                if ( $.trim(email).length ) {
                    var is_email_good = checkEmail(email);
                    if (is_email_good) {
                        result.push({
                            name: "data[" + index + "][email]",
                            value: email
                        });

                        result.push({
                            name: "data[" + index + "][access]",
                            value: access
                        });
                    } else {
                        errors.push({
                            $field: $email,
                            locale: that.locales["incorrect"]
                        });
                    }
                }
            });

            if (errors.length) {
                that.displayErrors(errors);
                return false;
            }

            return (result.length) ? result : false;

            function checkEmail(email) {
                return email.match(".+@.+");
            }
        }

        function showErrors(errors) {
            var result = [];
            $.each(errors, function(i, error) {
                var name = error.name,
                    locale = error.text,
                    index = parseInt( name.replace(/[^0-9]/g, '') );

                var $field = that.$inviteList.find(".t-invite-item").eq(index).find('[type="email"]');
                if ($field.length) {
                    result.push({
                        $field: $field,
                        locale: locale
                    });
                }
            });
            that.displayErrors(result);
        }
    };

    WelcomePage.prototype.removeErrors = function( $input ) {
        var that = this,
            error_class = that.error_class;

        if ($input) {
            $input.removeClass(error_class);
            $input.parent().find(".state-error-hint").remove();
        } else {
            that.$inviteList.find(".state-error-hint").remove();
            that.$inviteList.find("." + error_class).removeClass(error_class);
        }
    };

    WelcomePage.prototype.displayErrors = function(errors) {
        var that = this,
            error_class = that.error_class;

        that.removeErrors();

        $.each(errors, function(index, item) {
            var error = '<span class="state-error-hint" style="display: block">' + item.locale + '</span>';

            item.$field
                .addClass(error_class)
                .after( error );
        });

    };

    return WelcomePage;

})(jQuery);
