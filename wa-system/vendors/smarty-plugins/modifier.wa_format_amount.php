<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

function smarty_modifier_wa_format_amount($string, $currency_id = null, $format = '%', $locale = null)
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
        $locale = wa()->getUser()->getLocale();
    }
    if ($currency_id === null) {
        $currency_id = $smarty->getVars('currency_id');
    }

    if ($currency_id && $locale) {
        if ($format == 'words') {
            $format = '%.W{n0} %.2{f0}';
        }
        $string = waCurrency::format($format, $string, $currency_id, $locale);
    }
    return $string;
}
