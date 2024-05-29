(function ($) {

    var Page = ( function($) {

        Page = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];

            // CONST
            that.urls = options["urls"];

            // DYNAMIC VARS

            // INIT
            that.init();
        };

        Page.prototype.init = function() {
            var that = this;

            that.initHeader();

            var hover_class = "is-hover";

            that.$wrapper.on("click", ".b-day-wrapper", function (event) {
                if (this === event.target) {
                    event.preventDefault();
                    var date = $(this).data("date");
                    if (date) {
                        $.waLoading().animate(10000);
                        location.href = that.urls["create_post"].replace("%date%", date);
                    }
                }
            });
        };

        Page.prototype.initHeader = function() {
            var that = this,
                $header = that.$wrapper.find(".b-page-header:first");

            $header.find(".dropdown").each( function() {
                $(this).waDropdown({
                    hover: false,
                    items: ".menu > li",
                    change: function(event, target) {
                        var $target = $(target),
                            month = $target.data("month"),
                            year = $target.data("year");

                        $.waLoading().animate(10000);
                        location.href = that.urls["change_period"].replace("%month%", month).replace("%year%", year);
                    }
                });
            });
        };

        return Page;

    })($);

    $.wa_blog.init.initCalendarPage = function(options) {
        return new Page(options);
    }

})(jQuery);
