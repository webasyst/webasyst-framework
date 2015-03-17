<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {math} function plugin
 *
 * Type:     function<br>
 * Name:     math<br>
 * Purpose:  handle math computations in template<br>
 * @link http://smarty.php.net/manual/en/language.function.math.php {math}
 *          (Smarty online manual)
 * @author   Monte Ohrt <monte at ohrt dot com>
 * @param array
 * @param Smarty
 * @return string
 */
function smarty_function_wa_t($params, &$smarty)
{
    $str =  _w($params['message'], isset($params['message2']) ? $params['message2'] : null, isset($params['n']) ? $params['n'] : null);
    if (isset($params['n'])) {
        return sprintf($str, $params['n']);
    } else {
        return $str;
    }
}

/* vim: set expandtab: */

