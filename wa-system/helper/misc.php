<?php

/** print_r() all arguments inside <pre> and die(). */
function wa_print_r() {
    echo '<pre rel="waException">';
    foreach(func_get_args() as $v) {
        echo "\n".print_r($v, TRUE);
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
 * Check if the given value represents integer.
 * @return boolean true if $val contains integer or a string that represents integer.
 */
function int_ok($val)
{
    // check against objects to avoid nasty object to int convertion errors
    if (is_object($val)) {
        return false;
    }
    // typecast trick works fine for anything else except boolean true
    return ($val !== true) && ((string)(int) $val) === ((string) $val);
}
