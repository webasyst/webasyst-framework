// {* This js including in smarty on all backend layout pages as part of $wa->header() call. *}
(function($) { "use strict";

    var firebase_core_path = {if !empty($firebase_core_path)}{$firebase_core_path|json_encode}{else}null{/if};
    {*
        firebase_messaging_path = {if !empty($firebase_messaging_path)}{$firebase_messaging_path|json_encode}{else}null{/if},
        firebase_sender_id = {if !empty($sender_id)}{$sender_id|json_encode}{else}null{/if};
    *}
    if (!$ || !firebase_core_path /* || !firebase_sender_id */) return;

    const id = '#firebase-core-{$update_ts}';
    if (!$(id).length) {
        const script = document.createElement("script");
        script.type = "module";
        script.async = true;

        document.getElementsByTagName("head")[0].appendChild(script);

        $(script).attr("id", id)
                 .attr("src", firebase_core_path + "?ts={$update_ts}");
    }

    $(window).on('wa_push_settings_reload', () => {
        const script = document.createElement("script");
        script.type = "module";
        script.async = true;

        document.getElementsByTagName("head")[0].appendChild(script);
        const ts = Date.now();
        $(script).attr("id", "#firebase-core-" + ts)
                 .attr("src", firebase_core_path + "?ts=" + ts);
    });

}(window.jQuery));