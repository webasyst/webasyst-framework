<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


function smarty_modifier_wa_format_number($n, $decimals=0, $locale=null)
{
    if ($locale === null ) {
        /**
         * @var waSmarty3View
         */
        $smarty = waConfig::get('current_smarty');
    }
    if ($locale === null) {
        $locale = $smarty->getVars('locale');
    }
    if ($locale === null) {
        $locale = waSystem::getInstance()->getUser()->getLocale();
    }
    return waLocale::format($n, $decimals, $locale);
}


