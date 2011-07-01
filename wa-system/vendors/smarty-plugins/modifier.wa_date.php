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
		 * @var waSmartyView
		 */
		$smarty = waConfig::get('current_smarty');
		$locale = $smarty->getVars('locale');
		if (isset($locale->value)) {
			$locale = $locale->value;
		}
	}
	if ($locale === null) {
		$locale = waSystem::getInstance()->getUser()->getLocale();
	}
	
	
	return wa_date($format, $string, $timezone, $locale);
}

?>
