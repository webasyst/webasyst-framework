<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty plugin to print all template vars similar to print_r in a <pre> block.
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
    $str .= smarty_function_wa_tpl_vars_print_r($smarty->getTemplateVars()); // $smarty->tpl_vars
    $str .= "\n\n</pre></div>";
    return $str;
}

/** Helper function. Similar to print_r(). */
function smarty_function_wa_tpl_vars_print_r($value, $level = 0)
{
    if ($level > 9) {
        // Being paranoid
        return 'Too big level of nesting';
    }

    if (!is_array($value) && !is_object($value)) {
        if ($value === true) {
            return 'TRUE';
        } else if ($value === false) {
            return 'FALSE';
        } else if ($value === null) {
            return 'NULL';
        }
        return $value;
    }

    $br = "\n"; // line break with tabs
    for($i = 0; $i < $level; $i++) {
        $br .= "\t";
    }

    if (is_object($value)) {
        // Skip huge core objects
        /*$class = get_class($value);
        do {
            if(in_array($class, array('CmsObject', 'Smarty', 'CMSModule'))) {
                return get_class($value)." Object (skipped as a descendant of $class)";
            }
        } while ( ( $class = get_parent_class($class))); */
        $str = get_class($value).' Object'.$br.'{';
    } else {
        $str = 'Array'.$br.'(';
    }

    foreach($value as $key => $val) {
        $str .= $br."\t".$key.' => '.smarty_function_wa_tpl_vars_print_r($val, $level + 1);
    }
    $str .= is_array($value) ? $br.')' : $br.'}';
    return $str;
}

?>
