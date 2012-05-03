<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

function smarty_modifier_wa_datetime($string, $format = 'datetime', $timezone = null, $locale = null)
{
    if ($locale === null) {
        /**
         * @var waSmarty3View
         */
        $smarty = waConfig::get('current_smarty');
        $locale = $smarty->getVars('locale');
    }
    if ($locale === null) {
        $locale = waSystem::getInstance()->getUser()->getLocale();
    }

    return waDateTime::format($format, $string, $timezone, $locale);

}

