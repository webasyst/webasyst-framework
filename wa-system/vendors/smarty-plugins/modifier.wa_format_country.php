<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

function smarty_modifier_wa_format_country($code, $locale=null)
{
    if ($locale === null ) {
        /**
         * @var waSmarty3View
         */
        $smarty = waConfig::get('current_smarty');
    }
    if ($locale === null) {
        $locale = $smarty->getVars('locale');
        if (isset($locale->value)) {
            $locale = $locale->value;
        } else {
            $locale = null;
        }
    }
    if ($locale === null) {
        $locale = waSystem::getInstance()->getUser()->getLocale();
    }
    $country_model = new waCountryModel();
    $country = $country_model->get($code, $locale);

    return isset($country['name']) ? $country['name'] : $code;
}


