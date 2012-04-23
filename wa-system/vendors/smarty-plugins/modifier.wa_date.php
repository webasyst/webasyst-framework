<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

function smarty_modifier_wa_date($string, $format = 'date', $timezone = null, $locale = null)
{
    if ($locale === null) {
        /**
         * @var waSmarty3View
         */
        $smarty = waConfig::get('current_smarty');
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


    return wa_date($format, $string, $timezone, $locale);
}

