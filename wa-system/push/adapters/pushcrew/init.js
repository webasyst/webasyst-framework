// {* This js including in smarty on all backend layout pages as part of $wa->header() call. *}
(function($) { "use strict";

    var pushcrew_account_id = {if !empty($account_id)}{$account_id|json_encode}{else}null{/if};

    if (!$ || !pushcrew_account_id) return;

    // Load and init PushCrew SDK
    (function(window, document, script, el) {
        window._pcq = window._pcq || [];
        window._pcq.push(['_currentTime', Date.now()]);
        script = document.createElement('script');
        script.type = 'text/javascript';
        script.async = true;
        script.src = 'https://cdn.pushcrew.com/js/'+ pushcrew_account_id +'.js';
        el = document.getElementsByTagName('script')[0];
        el.parentNode.insertBefore(script, el);

        _pcq.push(['APIReady', initOptIn]);
        _pcq.push(['subscriptionSuccessCallback', saveSubscriber]);

        function initOptIn() {
            if (!pushcrew.subscriberId) {
                window._pcq.push(['triggerOptIn', { httpWindowOnly: false }]);
            }
        }

        function saveSubscriber(subscriber_id) {
            var href = {$webasyst_app_url|json_encode}+"?module=push&action=addSubscriber",
                data = {
                    provider_id: 'pushcrew',
                    data: {
                        account_id: pushcrew_account_id,
                        subscriber_id: subscriber_id
                    }
                };

            $.post(href, data);
        }

    })(window, document);

}(window.jQuery));