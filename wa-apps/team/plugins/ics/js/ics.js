( function($) {

    var TeamImportDialog = ( function($) {

        TeamImportDialog = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];
            that.$form = that.$wrapper.find("form");
            that.$frame = that.$wrapper.find("iframe");

            // VARS
            that.dialog = that.$wrapper.data("teamDialog");

            // DYNAMIC VARS
            that.is_locked = false;

            // INIT
            that.initClass();
            that.initCalendarToggle();
        };

        TeamImportDialog.prototype.initClass = function() {
            var that = this,
                $form = that.$form,
                $loading = $form.find('.loading');

            $form.on("submit", function(event) {
                if (that.is_locked) {
                    event.preventDefault();
                } else {
                    that.is_locked = true;
                    that.clearErrors();
                    $loading.show();
                }
            });

            that.$frame.on("load", function() {
                that.is_locked = false;
                $loading.hide();
                that.submit();
            });
        };

        TeamImportDialog.prototype.submit = function() {
            var that = this,
                $form = that.$form;

            var response = that.$frame.contents().find('body').html();

            try {
                response = $.parseJSON(response);
            } catch (e) {
                response = null;
            }
            if (!response) {
                return;
            }
            if (response.status === 'fail') {
                $.each(response.errors, function (i, error) {
                    var msg = error[0];
                    var name = error[1];
                    if (name && name.length) {
                        $form.find('[name="' + name + '"]').addClass('error').after('<em class="errormsg">' + msg + '</em>');
                    } else {
                        alert(msg);
                    }
                });
            } else {
                that.$wrapper.trigger("formSaved");
                that.dialog.close();
            }
        };

        TeamImportDialog.prototype.initCalendarToggle = function() {
            var that = this;

            // Calendar Toggle

                var $calendarToggle = that.$wrapper.find(".t-calendar-toggle"),
                    $input = $calendarToggle.find("input"),
                    $hiddenMenu = $calendarToggle.find(".menu-v"),
                    active_class = "selected",
                    $selected = $hiddenMenu.find("li." + active_class);

                $hiddenMenu.on("click", "a", function() {
                    setCalendar( $(this) );
                });

                function setCalendar( $link ) {
                    var $li = $link.closest("li"),
                        calendar_id = ( $li.data("calendar-id") || false ),
                        is_active = $li.hasClass(active_class);

                    if (calendar_id && !is_active) {
                        // Set menu
                        if ($selected.length) {
                            $selected.removeClass(active_class);
                        }
                        $li.addClass(active_class);
                        $selected = $li;

                        // Render
                        $calendarToggle.find(".t-selected-item").html( $link.html() );

                        // Set value
                        $input.val(calendar_id).trigger("change");

                        //
                        $hiddenMenu.hide();
                        setTimeout( function() {
                            $hiddenMenu.removeAttr("style");
                        }, 500);
                    }
                }

        };

        TeamImportDialog.prototype.clearErrors = function() {
            var that = this;

            that.$wrapper.find('.error').removeClass('error');
            that.$wrapper.find('.errormsg').remove();
        };

        return TeamImportDialog;

    })(jQuery);

    new TeamImportDialog({
        $wrapper: $("#t-ics-import-dialog")
    });

})(jQuery);