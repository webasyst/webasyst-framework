<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


function smarty_modifier_wa_date_add($string, $format, $timezone = null, $locale = null)
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

    $string = wa_date('date', $string, $timezone, $locale);
    if (preg_match('/(\d+)([d])/i', $format, $match)) {
        $string = wa_date('date', date('Y-m-d', strtotime($string) + $match[1] * 60 * 60 * 24), $timezone, $locale);
    }
    return $string;
}

