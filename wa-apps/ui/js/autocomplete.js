( function($) { "use strict";

    var plugin_name = "autocomplete";

    $.fn.waAutocomplete = function(plugin_options) {
        var return_instance = ( typeof plugin_options === "string" && plugin_options === plugin_name),
            $items = this,
            result = this,
            is_loaded = false;

        plugin_options = ( typeof plugin_options === "object" ? plugin_options : {});

        if (return_instance) { result = getInstance(); } else { init(); }

        return result;

        function init() {
            $items.each( function(index, item) {
                var $wrapper = $(item);

                if (!$wrapper.data(plugin_name)) {
                    load().then( function() {
                        var instance = $wrapper.autocomplete(plugin_options).autocomplete("instance");
                        $wrapper.data(plugin_name, instance);
                    });
                }
            });
        }

        function getInstance() {
            return $items.first().data(plugin_name);
        }

        /**
         * @return Promise
         * */
        function load() {
            var deferred = $.Deferred();

            if (is_loaded) {
                deferred.resolve();
            } else {
                $.getScript("https://code.jquery.com/ui/1.12.1/jquery-ui.js", function () {
                    $('<link/>', { href: "//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css", rel: 'stylesheet' }).appendTo('head');
                    is_loaded = true;
                    deferred.resolve();
                });
            }

            return deferred.promise();
        }
    };

})(jQuery);