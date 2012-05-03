<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


function smarty_modifier_wa_format_amount_currency($string, $currency_id=null, $locale=null)
{
    if ($locale === null || $currency_id === null) {
        /**
         * @var waSmarty3View
         */
        $smarty = waConfig::get('current_smarty');
    }
    if ($locale === null) {
        $locale = $smarty->getVars('locale');
    }
    if ($locale === null) {
        $locale = wa()->getLocale();
    }
    if ($currency_id === null) {
        $currency_id = $smarty->getVars('currency_id');
    }

    if ($currency_id && $locale) {
        $string = waCurrency::format('%{s}', $string, $currency_id, $locale);
    }
    return $string;
}

