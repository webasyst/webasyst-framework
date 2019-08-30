(function ($) {
    $.wa_push = $.extend($.wa_push || {}, {
        init: function () {
            var source = {
                    id: 'wa-push-init',
                    uri: backend_url + "webasyst/?module=push&action=initJs"
                },
                $script = $("#" + source.id);

            if (!$script.length) {
                var script = document.createElement("script");
                document.getElementsByTagName("head")[0].appendChild(script);

                $script = $(script).attr("id", source.id);

                $script
                    .on("load", function() {
                        $(window).trigger('wa_push_ready');
                    })
                    .on("error", function() {
                        $(window).trigger('wa_push_init_error');
                    });

                $script.attr("src", source.uri);
            }
        }
    });
})($);
