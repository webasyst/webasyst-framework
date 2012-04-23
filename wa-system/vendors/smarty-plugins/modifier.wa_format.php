<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


function smarty_modifier_wa_format($n, $decimals = null)
{
    return waLocale::format($n, $decimals);
}

