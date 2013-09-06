<?php

/** print_r() all arguments inside <pre> and die(). */
function wa_print_r() {
    if (php_sapi_name() != 'cli') {
        echo '<pre rel="waException">';
    }
    foreach(func_get_args() as $v) {
        echo "\n".wa_print_r_helper($v);
    }
    if (php_sapi_name() != 'cli') {
        echo "</pre>\n";
    }
    exit;
}

/** Wrapper around create_function() that caches functions it creates to avoid memory leaks. */
function wa_lambda($args, $body) {
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
function int_ok($val)
{
    // check against objects to avoid nasty object to int convertion errors
    if (!is_numeric($val)) {
        return false;
    }
    // typecast trick works fine for anything else except boolean true
    return ($val !== true) && ((string)(int) $val) === ((string) $val);
}

/**
 * Helper function for wa_print_r().
 * - Human-readable like print_r() for nested structures.
 * - Distinguishable like var_export() for plain values.
 * - Handles self-references correctly like print_r().
 * - Escapes all output with htmlspecialchars(), unless in CLI mode.
 * - Shows both protected and private fields of objects.
 * - Skips huge objects like waSystem or Smarty in nested structures.
 */
function wa_print_r_helper(&$value, &$level_arr = array())
{
    $level = count($level_arr);
    if ($level > 29) {
        // Being paranoid
        return '** Too big level of nesting **';
    }

    // Simple types
    if (!is_array($value) && !is_object($value)) {
        $result = var_export($value, true);
        if (php_sapi_name() != 'cli') {
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
            $huge_classes = array_flip(array('waSystem', 'waModel', 'waSmarty3View', 'waViewHelper', 'Smarty', 'Smarty_Internal_Template'));
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
        if (php_sapi_name() != 'cli') {
            $key = htmlspecialchars($key);
        }
        $str .= $br."  ".$key.' => '.wa_print_r_helper($val, $level_arr);
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
