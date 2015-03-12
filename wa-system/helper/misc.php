<?php

/**
 * Debug helper. Print all arguments and exit.
 *
 * - Human-readable like print_r() for nested structures.
 * - Distinguishable like var_export() for plain values.
 * - Handles self-references correctly like print_r().
 * - Adds <pre> and escapes all output with htmlspecialchars(), unless in CLI mode.
 * - Shows both protected and private fields of objects.
 * - Skips huge objects like waSystem or Smarty in nested structures.
 */
function wa_dump()
{
    $args = func_get_args(); // Can't be used as a function argument directly before PHP 5.3
    call_user_func_array('wa_dumpc', $args);
    exit;
}

/** Same as wa_dump(), but does not call exit. */
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
        foreach(debug_backtrace() as $row) {
            if (ifset($row['file']) == __FILE__ || (empty($row['file']) && ifset($row['function']) == 'wa_dumpc')) {
                continue;
            }
            echo ifset($row['file'], '???'), ' line #', ifset($row['line'], '???'), ":\n";
            break;
        }
    }

    foreach(func_get_args() as $v) {
        echo "\n".wa_dump_helper($v)."\n";
    }
    if (php_sapi_name() != 'cli') {
        echo "</pre>\n";
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
function wao($o)
{
    if (!$o || !is_object($o)) {
        throw new waException('Argument is not an object.');
    }
    return $o;
}

/** Wrapper around create_function() that caches functions it creates to avoid memory leaks when used in a loop. */
function wa_lambda($args, $body)
{
    if (!isset($body)) {
        $body = $args = '';
    }

    static $fn = array();
    $hash = $args.md5($args.$body).md5($body);
    if(!isset($fn[$hash])) {
        $fn[$hash] = create_function($args, $body);
    }
    return $fn[$hash];
}

/**
 * Return value of $var or $def when $var is unset.
 * Use of this function does not produce a notice for undefined vars and array indexes,
 * but has a side-effect of creating var or index with NULL value.
 */
function ifset(&$var, $def=null)
{
    if (isset($var)) {
        return $var;
    }
    return $def;
}

/**
 * Return value of $var or $def when $var is empty.
 * Use of this function does not produce a notice for undefined vars and array indexes,
 * but has a side-effect of creating var or index with NULL value.
 */
function ifempty(&$var, $def=null)
{
    if (empty($var)) {
        return $def;
    }
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
 * Helper function for wa_print_r() / wa_dump()
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

    // Simple types
    if (!is_array($value) && !is_object($value)) {
        $result = var_export($value, true);
        if (!$cli) {
            $result = htmlspecialchars($result);
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

        // Cast to array to show protected and private members
        $value_to_iterate = (array) $value;

        if ($value_to_iterate) {
            $str .= $br.'{';
        } else {
            return $str.' {}';
        }

    } else {
        $str = 'Array';
        if ($value) {
            $str .= $br.'(';
        } else {
            return $str.'()';
        }
        $value_to_iterate =& $value;
    }

    $level_arr[] = &$value;
    foreach($value_to_iterate as $key => &$val) {
        if (!$cli) {
            $key = htmlspecialchars($key);
        }
        $str .= $br."  ".$key.' => '.wa_dump_helper($val, $level_arr, $cli);
    }
    array_pop($level_arr);

    $str .= is_array($value) ? $br.')' : $br.'}';
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


