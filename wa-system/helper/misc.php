<?php

/**
 * Debug helper. Print all arguments and exit.
 *
 * - Human-readable like print_r() for nested structures.
 * - Distinguishable like var_export() for plain values.
 * - Handles self-references correctly like print_r().
 * - Output is a valid PHP code, if input only contains arrays and no self-references.
 * - Escapes all output with htmlspecialchars(), unless in CLI mode.
 * - Adds <pre>, file and line called from.
 * - Shows both protected and private fields of objects.
 * - Skips huge objects like waSystem or Smarty in nested structures.
 * - Accepts any number of arguments.
 *
 * @see wa_dumpc() - does not call exit
 * @see wa_dump_helper() - no exit, no <pre>, no 'called from' message, optionally escapes
 * @see waLog::dump() - dumps to log file
 * @see waException::dump() - throws exception with dumped data
 */
function wa_dump()
{
    $args = func_get_args(); // Can't be used as a function argument directly before PHP 5.3
    call_user_func_array('wa_dumpc', $args);
    exit;
}

/**
 * Same as wa_dump(), but does not call exit.
 * Unless called from template, returns its first argument.
 */
function wa_dumpc()
{
    if (php_sapi_name() != 'cli') {
        // 'waException' is a trigger for default JS error handlers
        // to show output in a dialog.
        echo '<pre rel="waException">';
    }

    // Show where we've been called from
    if(function_exists('debug_backtrace')) {
        echo "dumped from ";

        $root_path = realpath(dirname(__FILE__).'/../..');
        $root_path = str_replace('\\', '/', $root_path);
        $root_path = preg_quote($root_path, '~');
        foreach(debug_backtrace() as $row) {
            if (ifset($row['file']) == __FILE__ || (empty($row['file']) && ifset($row['function']) == 'wa_dumpc')) {
                continue;
            }
            if (!empty($row['file'])) {
                $row['file'] = str_replace('\\', '/', $row['file']);
                $row['file'] = preg_replace("~^{$root_path}/?~", '$1', $row['file']);
            }
            echo ifset($row['file'], '???'), ' line #', ifset($row['line'], '???'), ":\n";
            break;
        }
    }

    $args = func_get_args();
    foreach($args as $v) {
        echo "\n".wa_dump_helper($v)."\n";
    }
    if (php_sapi_name() != 'cli') {
        echo "</pre>\n";
    }
    if (class_exists('waConfig') && !waConfig::get('is_template')) {
        return reset($args);
    }
}

/**
 * Alias for wa_dump()
 * @deprecated
 */
function wa_print_r()
{
    $args = func_get_args(); // Can't be used as a function argument directly before PHP 5.3
    call_user_func_array('wa_dumpc', $args);
    exit;
}

/**
 * Helper to chain constructor calls.
 * When argument is an object, return it. Otherwise, throw waException.
 */
function &wao($o)
{
    if (!$o || !is_object($o)) {
        throw new waException('Argument is not an object.');
    }
    return $o;
}

/** Wrapper around create_function that caches functions it creates to avoid memory leaks when used in a loop. */
function wa_lambda($args, $body)
{
    if (!isset($body)) {
        $body = $args = '';
    }

    static $fn = array();
    $hash = md5($args.$body);
    if(!isset($fn[$hash])) {
        /*      Uncomment with the minimum version 5.6
        $f = 'return function('.$args.') {'.$body.'};';
        $fn[$hash] = eval($f);*/

        $i = count($fn);
        do {
            $fn_name = 'wa_lambda_'.$i;
            $i++;
        } while (function_exists($fn_name));

        $f = 'function '.$fn_name. '('.$args.') {'.$body.'};';
        eval($f);
        $fn[$hash] = $fn_name;

    }
    return $fn[$hash];

}

/**
 * Return value of $var or $def when $var is unset.
 * Use of this function does not produce a notice for undefined vars and array indexes,
 * but has a side-effect of creating var or index with NULL value.
 *
 * To avoid side-effect if creating keys in array, use alternative syntax:
 *   ifset($arr, $key1, $key2, ..., $def)
 * is the same as:
 *   ifset($arr[$key1][$key2]..., $def)
 * but does not create a NULL value in array.
 * Also, it will not trigger warnings if $arr is an ArrayAccess object.
 *
 * Note that alternative syntax makes the $def parameter required.
 */
function &ifset(&$var, $def=null)
{
    if (func_num_args() > 2) {
        $keys = func_get_args();
        $def = array_pop($keys);
        $arr = array_shift($keys);
    } else {
        $keys = array();
        $arr = $var;
    }

    while($keys) {
        $key = array_shift($keys);
        if (is_object($arr) && !$arr instanceof ArrayAccess) {
            return $def;
        } else if (!isset($arr[$key])) {
            return $def;
        } else {
            $arr = $arr[$key];
        }
    }

    if (isset($arr)) {
        return $arr;
    }
    return $def;
}

/**
 * Return value of $var or $def when $var is empty.
 * Use of this function does not produce a notice for undefined vars and array indexes,
 * but has a side-effect of creating var or index with NULL value.
 *
 * To avoid side-effect if creating keys in array, use alternative syntax:
 *   ifempty($arr, $key1, $key2, ..., $def)
 * is the same as
 *   ifempty($arr[$key1][$key2]..., $def)
 * but does not create a NULL value in array.
 * Also, it will not trigger warnings if $arr is an ArrayAccess object.
 *
 * Note that alternative syntax makes the $def parameter required.
 */
function &ifempty(&$var, $def=null)
{
    if (func_num_args() > 2) {
        $keys = func_get_args();
        $def = array_pop($keys);
        $arr = array_shift($keys);
    } else {
        $keys = array();
        $arr = $var;
    }

    while($keys) {
        $key = array_shift($keys);
        if (is_object($arr) && !$arr instanceof ArrayAccess) {
            return $def;
        } else if (empty($arr[$key])) {
            return $def;
        } else {
            $arr = $arr[$key];
        }
    }

    if (!empty($arr)) {
        return $arr;
    }
    return $def;
}

/**
 * Returns its argument by reference.
 * Useful to pass function return into ifset() or ifempty()
 * which otherwise would trigger a notice:
 * Strict standards: Only variables should be passed by reference
 * @since 1.8.2
 */
function &ref($var)
{
    return $var;
}

/**
 * Check if the given value represents integer.
 * @return boolean true if $val contains integer or a string that represents integer.
 */
function wa_is_int($val)
{
    // check against objects to avoid nasty object to int convertion errors
    if (!is_numeric($val)) {
        return false;
    }
    // Test for very large integers
    if (function_exists('ctype_digit')) {
        $val = (string) $val;
        if (ctype_digit($val)) {
            return true;
        } else if ($val && $val[0] == '-' && ctype_digit(substr($val, 1))) {
            return true;
        }
    }
    // typecast trick works fine for anything else except boolean true
    return ($val !== true) && ((string)(int) $val) === ((string) $val);
}

/**
 * @param $val
 * @return bool
 * @deprecated
 */
function int_ok($val)
{
    return wa_is_int($val);
}

/**
 * Helper function for wa_dump()
 */
function wa_dump_helper(&$value, &$level_arr = array(), $cli = null)
{
    $level_arr || $level_arr = array();
    $cli === null && $cli = php_sapi_name() == 'cli';

    $level = count($level_arr);
    if ($level > 29) {
        // Being paranoid
        return '** Too big level of nesting **';
    }

    $htmlspecialchars_mode = ENT_NOQUOTES;
    if (defined('ENT_SUBSTITUTE')) {
        $htmlspecialchars_mode |= ENT_SUBSTITUTE;
    } else if (defined('ENT_IGNORE')) {
        $htmlspecialchars_mode |= ENT_IGNORE;
    }

    // Simple types
    if (is_resource($value)) {
        return print_r($value, 1).' ('.get_resource_type($value).')';
    } else if (is_float($value)) {
        $result = var_export($value, 1);
        if (false === strpos($result, '.') && false === strpos($result, ',')) {
            $result .= '.0';
        }
        return $result;
    } else if (!is_array($value) && !is_object($value)) {
        $result = var_export($value, true);
        if (!$cli) {
            $result = htmlspecialchars($result, $htmlspecialchars_mode, 'utf-8');
            if (!strlen($result)) {
                $result = '&lt;encoding problems&gt;';
            }
        }
        return $result;
    }

    // So, we have a nested type like array or object.
    // Check for recursion, and build line break with tabs
    $br = "\n";
    foreach($level_arr as $k => &$v) {
        $br .= "  ";

        // Check for references we've already seen.
        // This avoids infinite recursion.
        $same = false;
        if (is_array($v) && is_array($value)) {
            // Checking whether arrays are same is not that trivial.
            // === operator will try to compare them recursively and fail
            // when array contains a reference to itself.
            // So, we use a clever trick here.
            $key = uniqid('same?', true);
            $v[$key] = $key;
            $same = isset($value[$key]) && $value[$key] === $key;
            unset($v[$key]);
        } else if ($v === $value) {
            $same = true;
        }
        if ($same) {
            return (is_object($value) ? get_class($value).' object' : 'Array').' ** RECURSION (level '.($k - $level).') **';
        }
    }
    unset($v);

    if (is_object($value)) {
        // Skip huge core objects in nested structures
        if ($level > 0) {
            $huge_classes = array_flip(array('waSystem', 'waModel', 'waSmarty3View', 'waViewHelper', 'waWorkflow', 'Smarty', 'Smarty_Internal_Template'));
            $class = get_class($value);
            do {
                if(isset($huge_classes[$class])) {
                    return get_class($value)." object { ** skipped".(isset($huge_classes[get_class($value)]) ? '' : " as a descendant of $class").' ** }';
                }
            } while ( ( $class = get_parent_class($class)));
        }
        $str = get_class($value).' object';

        // Special treatment for DOM nodes to make output useful and readable
        if ($value instanceof DOMNodeList || $value instanceof DOMNamedNodeMap) {
            $value_to_iterate = iterator_to_array($value);
        } else if ($value instanceof DOMNode) {
            if (!$value instanceof DOMDocument) {
                $str = get_class($value);
                if (!$value instanceof DOMText && !$value instanceof DOMComment && !$value instanceof DOMDocumentType) {
                    $val = " <".$value->nodeName.">";
                    if (!$cli) {
                        $val = htmlspecialchars($val, $htmlspecialchars_mode, 'utf-8');
                    }
                    $str .= $val;
                }
            }

            if ($value->attributes) {
                $arr = [];
                foreach ($value->attributes as $k => $v) {
                    $arr[$k] = $v->value;
                }
                if ($arr) {
                    $level_arr[] =& $value->attributes;
                    $dom_attributes_str = wa_dump_helper($arr, $level_arr, $cli);
                    $dom_attributes_str = $br."  attributes ".$dom_attributes_str;
                    array_pop($level_arr);
                }
            }

            if ($value->childNodes) {
                $value_to_iterate = iterator_to_array($value->childNodes);
                $dont_show_keys = true;
            } else {
                $value_to_iterate = [];
            }

            if (!$value_to_iterate) {
                if (trim($value->nodeValue)) {
                    $lines = explode("\n", trim($value->nodeValue));
                    $val = trim($lines[0]);
                    if (mb_strlen($val) > 40 || count($lines) > 1) {
                        $val = mb_substr(trim($lines[0]), 0, 25);
                        if (strlen($val) < strlen($lines[0]) || count($lines) > 1) {
                            $truncated = true;
                            $val .= '...';
                        }
                        $val = wa_dump_helper($val, ref([]), $cli);
                        if (count($lines) > 1) {
                            $val .= " (".count($lines)." lines)";
                        } else if (!empty($truncated)) {
                            $val .= " (".mb_strlen($lines[0])." characters)";
                        }
                    } else {
                        $val = wa_dump_helper($val, ref([]), $cli);
                    }
                    $str .= ' = '.$val;
                } else if ($value->nodeValue) {
                    $str .= ' whitespace';
                }
                if (empty($dom_attributes_str)) {
                    return $str; // no {} at the end
                }
            }
        } else {
            // Cast to array to show protected and private members
            $value_to_iterate = (array) $value;
        }

        if ($value_to_iterate || isset($dom_attributes_str)) {
            $str .= ' {';
            if (isset($dom_attributes_str)) {
                $str .= $dom_attributes_str;
            }
            if (!$value_to_iterate) {
                return $str.$br.'}';
            }
        } else {
            return $str.' {}';
        }

    } else {
        if (!$value) {
            return '[]';
        }
        $str = '[';
        $value_to_iterate =& $value;

        // do not show indices if array only contains numeric keys from 0 up to its size
        $size = count($value_to_iterate);
        if (!isset($value_to_iterate[$size])) {
            $value_to_iterate[] = false;
            $dont_show_keys = isset($value_to_iterate[$size]);
            array_pop($value_to_iterate);
        }
    }

    $level_arr[] = &$value;
    $keys = array_keys($value_to_iterate);
    if (class_exists('waConfig') && waConfig::get('wa_dump_sort_keys')) {
        sort($keys);
    }
    foreach($keys as $key) {
        if (is_array($value)) {
            $escaped_key = wa_dump_helper($key, ref([]), $cli);
        } else if (!$cli) {
            $escaped_key = htmlspecialchars($key, $htmlspecialchars_mode, 'utf-8');
        } else {
            $escaped_key = $key;
        }
        $str .= $br."  ";
        if (empty($dont_show_keys)) {
            $str .= $escaped_key.' => ';
        }
        $str .= wa_dump_helper($value_to_iterate[$key], $level_arr, $cli).(is_array($value) ? ',' : '');
    }
    array_pop($level_arr);

    $str .= is_array($value) ? $br.']' : $br.'}';
    return $str;
}

function wa_make_pattern($string, $separator = '/')
{
    $metacharacters = array('?','+','*','.','(',')','[',']','{','}','<','>','^','$');
    $metacharacters[] = $separator;
    foreach($metacharacters as &$char){
        $char = "\\{$char}";
        unset($char);
    }
    $cleanup_pattern = '@('.implode('|',$metacharacters).')@';
    return preg_replace($cleanup_pattern,'\\\\$1',$string);
}

/**
 * Calculate diff of multidimensional and hierarchical arrays
 * @param array $value1
 * @param array $value2
 * @param mixed $diff Result of diff
 * @return boolean If or not differ
 */
function wa_array_diff_r($value1, $value2, &$diff) {
    if (is_array($value1) && is_array($value2)) {
        $kyes = array_unique(array_merge(array_keys($value1), array_keys($value2)));
        $result = false;
        foreach ($kyes as $k) {
            $v1 = ifset($value1[$k]);
            $v2 = ifset($value2[$k]);
            $r = wa_array_diff_r($v1, $v2, $diff[$k]);
            if (!$r) {
                unset($diff[$k]);
            }
            $result = $result || $r;
        }
        return $result;
    } elseif ($value1 !== $value2) {
        $diff = $value1;
        return true;
    } else {
        return false;
    }
}


