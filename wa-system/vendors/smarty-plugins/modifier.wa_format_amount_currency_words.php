<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


function smarty_modifier_wa_format_amount_currency_words($string, $currency_id=null, $locale=null)
{
    if ($locale === null || $currency_id === null) {
        /**
         * @var waSmartyView
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
    if ($currency_id === null) {
        $currency_id = $smarty->getVars('currency_id');
        if (isset($currency_id->value)) {
            $currency_id = $currency_id->value;
        } else {
            $currency_id = null;
        }
    }

    if ($currency_id && $locale) {
        $string = waCurrency::format('%.W{n0} %.2{f0}', $string, $currency_id, $locale);
    }
    return $string;
}

