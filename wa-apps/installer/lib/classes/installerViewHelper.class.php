<?php

class installerViewHelper
{
    /**
     * @param null $type
     * @param null $key
     * @return string
     */
    public function getFilters($type = null, $key = null)
    {
        $filters = installerStoreHelper::getFilters();

        if ($type) {
            if (!array_key_exists($type, $filters) || $filters[$type] != $key) {
                $filters[$type] = $key;
            } else {
                unset($filters[$type]);
            }
        }
        if (!$filters) {
            return '';
        }
        ksort($filters);
        return '?'.http_build_query(array('filters' => $filters));
    }

    /**
     * @param int|string $id - integer ID of product as it in DB OR string ID of product in form of url in store:
     *      Kind of ids in form of store's urls:
     *          - app/{app_id} OR just {app_id}         - application
     *          - theme/{ext_id}                        - themes family
     *          - plugin/{app_id}/{ext_id}              - application plugin
     *          - plugin/payment/{ext_id}               - payment plugin
     *          - plugin/shipping/{ext_id}              - shipping plugin
     *          - plugin/sms/{ext_id}                   - sms plugin
     *          - widget/{app_id}/{ext_id}              - application widget
     *          - widget/webasyst/{ext_id}              - system widget
     * @param array $options
     *      bool $options['is_inline']
     *          Will widget rendered inline or absolutely positioned? Default is FALSE (not inline).
     *          Also notice that in default variant widget is not allowed be shown too often,
     *              this moment can be handled by listening on event in JS (wa_installer_product_review_widget_init_widget_fail)
     *
     *      boo $options['is_debug'] [optional]
     *          Is widget in debug mode.
     *          In debug mode if there is errors they will be printed into js console,
     *              also widget always will render product info even if there is no license for this product
     *              but leaving review will not be working.
     *          Default is same as in system (if there is in system debug mode than in widget is debug mode)
     *
     * Widget support some js events, that triggered on widget DOM element:
     *      - 'wa_installer_product_review_widget_init_widget_fail' - when fail on widget initializing
     *      - 'wa_installer_product_review_widget_loading_product_fail' - when fail on loading product info from webasyst store
     *
     * @return string
     * @throws waException
     * @throws SmartyException
     */
    public function reviewWidget($id, $options = [])
    {
        $options = is_array($options) ? $options : [];

        $is_template = waConfig::get('is_template');
        waConfig::set('is_template', false);

        $is_inline = !empty($options['is_inline']);
        $parts = explode('/', $id, 3);
        if (count($parts) > 1 && $parts[0] !== 'app') {
            // force inline mode for non app products (plugins, themes, widgets)
            $is_inline = true;
        }

        $is_debug = waSystemConfig::isDebug();
        if (isset($options['is_debug'])) {
            $is_debug = $options['is_debug'];
        }

        $widget = new installerProductReviewWidget($id, [
            'check_can_show' => !$is_inline,  // in default (not inline) mode is not allowed show widget too often
            'is_debug' => $is_debug
        ]);

        $html = $widget->render([
            'is_modal' => !$is_inline,
            'installer_app_url' => wa()->getAppUrl('installer', true)
        ]);

        waConfig::set('is_template', $is_template);

        return $html;
    }
}
