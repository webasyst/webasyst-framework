<?php
class installerShopBackend_extended_menuHandler extends waEventHandler
{
    public function execute(&$params)
    {
        if (!wa()->getUser()->getRights('installer')) {
            return;
        }
        $app_id = 'shop';
        $wa_app_url = wa($app_id)->getAppUrl(null, true);

        $list = (new installerAnnouncementList())->withFilteredByApp($app_id);
        $promotions = $list->getPromotionList('2.0');
        if (!$promotions) {
            return;
        }

        $promotion = reset($promotions);
        $promo_id = key($promotions);
        $promo_url = ifset($promotion, 'url', '');
        if (!preg_match('~^(https?||javascript):~i', $promo_url)) {
            $promo_url = "{$wa_app_url}../".$promo_url;
        }

        $additional_script = '';
        if (!empty($promotion['html'])) {
            // dialog mode
            $installer_backend_url = wa()->getAppUrl('installer', true);
            $additional_script = '<script style="display:none" id="installer-ssx-promo-link-script">(function() { "use strict";
                var app_id = '.json_encode($app_id).';
                var promo_id = '.json_encode($promo_id).';
                var installer_backend_url = '.json_encode($installer_backend_url).';
             
                var script = $("#installer-ssx-promo-link-script");
                var $link = script.closest("a").addClass("js-int-promo-link"+promo_id);
             
                if (localStorage.getItem("installer_hide_promo_"+promo_id)) {
                    $link.remove();
                    return;
                }

                $link.click(function(e) {
                    e.preventDefault();
                    $("#s-inst-ssx-dialog").remove();
                    $.get(installer_backend_url + "?module=widgets&action=promotion&app_id="+app_id+"&ui_version=2.0").then(function(html) {
                        $.waDialog({
                            html: html,
                            onOpen: function($dialog, dialog) {
                                $dialog.data("installer_backend_url", installer_backend_url).trigger("inst_ssx_dialog_loaded");
                            }
                        });
                    });
                });

                script.remove();
            })();</script>';
        } else if (!empty($promotion['open_new_tab'])) {
            $additional_script = <<<EOF
                <script style="display:none" id="installer-ssx-promo-link-script">
                    var script = $('#installer-ssx-promo-link-script');
                    script.closest('a').attr('target', '_blank');
                    script.remove();
                </script>
EOF;
        }

        $params['menu']['ssx_link'] = [
            "name" => ifset($promotion, 'title', ''),
            "icon" => ifset($promotion, 'icon', '').$additional_script,
            "url" => $promo_url,
        ];
    }
}
