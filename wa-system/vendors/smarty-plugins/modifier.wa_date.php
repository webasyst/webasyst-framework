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
         * @var waSmarty3View $smarty
         */
        $smarty = waConfig::get('current_smarty');
        $locale = $smarty->getVars('locale');
    }
    if ($locale === null) {
        $locale = waSystem::getInstance()->getUser()->getLocale();
    }

    if (!$string) {
        return '';
    }
    return wa_date($format, $string, $timezone, $locale);
}

