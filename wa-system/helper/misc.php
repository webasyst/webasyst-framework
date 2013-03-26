<?php

/** print_r() all arguments inside <pre> and die(). */
function wa_print_r() {
    echo '<pre rel="waException">';
    foreach(func_get_args() as $v) {
        echo "\n".wa_print_r_helper($v, TRUE);
    }
    echo "</pre>\n";
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

/** Helper function. More human-readable print_r(). */
function wa_print_r_helper($value, $level = 0)
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
        return htmlspecialchars($value);
    }

    $br = "\n"; // line break with tabs
    for($i = 0; $i < $level; $i++) {
        $br .= "\t";
    }

    if (is_object($value)) {
        // Skip huge core objects
        $class = get_class($value);
        do {
            if(in_array($class, array('Smarty'))) {
                return get_class($value)." Object (skipped as a descendant of $class)";
            }
        } while ( ( $class = get_parent_class($class)));
        $str = get_class($value).' Object'.$br.'{';
    } else {
        $str = 'Array'.$br.'(';
    }

    foreach($value as $key => $val) {
        $str .= $br."\t".$key.' => '.wa_print_r_helper($val, $level + 1);
    }
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
