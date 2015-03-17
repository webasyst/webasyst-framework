<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty plugin to print all template vars similar to print_r in a <pre> block.
 * Uses webasyst library helpers. Does not work outside of WA framework without further adjustments.
 *
 * Type:     function<br>
 * Name:     wa_template_vars<br>
 * Purpose:  Print all template vars similar to print_r in a <pre> block.<br>
 * @param array
 * @param Smarty
 * @return string
 */
function smarty_function_wa_tpl_vars($params, &$smarty)
{
    $str = '<div style="overflow: auto; min-width: 500px; height: 400px;"><pre>'."\n\n";
    $v = $smarty->getTemplateVars();
    $str .= wa_dump_helper($v); // $smarty->tpl_vars
    $str .= "\n\n</pre></div>";
    return $str;
}

