<?php
/**
 * Add promo link (that opens dialog) as the rightmost tab in app sections. Only applies to UI 1.3.
 */
class installerShopBackend_menuHandler extends waEventHandler
{
    public function execute(&$params)
    {
        if (!wa()->getUser()->getRights('installer')) {
            return;
        }

        $app_id = 'shop';

        // Make sure there are active promotions for the app
        $list = (new installerAnnouncementList())->withFilteredByApp($app_id);
        $promotions = $list->getPromotionList('1.3');
        if (!$promotions) {
            return;
        }
        $promotion = reset($promotions);
        $promo_id = key($promotions);
        $promo_link_title = ifset($promotion, 'title', '<i class="icon16 star"></i>');

        $wa_app_url = wa()->getAppUrl($app_id, true);
        $installer_backend_url = wa()->getAppUrl('installer', true);

        // Link to open in new window if user middle-clicks
        $link = $wa_app_url.'?action=settings#/premium/';

        // URL to fetch dialog content from if user left-clicks
        $dialog_url = $wa_app_url.'?action=settings#/premium/';

        return array(
            'aux_li' => '<li class="small float-right no-tab js-'.$promo_id.'" id="s-ssx-link" style="margin:0 30px 0 -30px"><a href="'.$link.'">'.
                $promo_link_title
            .'</a></li>'.
            '
<script>(function() { "use strict";
    var app_id = '.json_encode($app_id).';
    var installer_backend_url = '.json_encode($installer_backend_url).';

    var $link = $("#s-ssx-link");
    $link.prependTo($link.parent());

    if (localStorage.getItem('.json_encode('installer_hide_promo_'.$promo_id).')) {
        $link.remove();
        return;
    }

    $link.click(function(e) {
        e.preventDefault();
        $("#s-inst-ssx-dialog").remove();
        $(\'<div id="s-inst-ssx-dialog"></div>\').data("installer_backend_url", installer_backend_url).waDialog({
            url: installer_backend_url + "?module=widgets&action=promotion&app_id="+app_id+"&ui_version=1.3",
            onLoad: function() {
                var $dialog_wrapper = $("#s-inst-ssx-dialog");
                if ($dialog_wrapper.find(".dialog-content-indent").children().length) {
                    $dialog_wrapper.trigger("inst_ssx_dialog_loaded");
                } else {
                    $dialog_wrapper.trigger("close");
                    window.location = $link.find("a")[0].href;
                }
            }
        });
    });
})();</script>
',
        );
    }
}
