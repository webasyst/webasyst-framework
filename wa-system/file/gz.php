<?php


/**
 * @see https://bugs.php.net/bug.php?id=53829
 */
if (!function_exists('gzopen') && function_exists('gzopen64')) {
    function gzopen($filename, $mode, $use_include_path = 0)
    {
        return gzopen64($filename, $mode, $use_include_path);
    }
}

if (!function_exists('gztell') && function_exists('gztell64')) {
    function gztell($zp)
    {
        return gztell64($zp);
    }
}

if (!function_exists('gzseek') && function_exists('gzseek64')) {
    function gzseek($zp, $offset, $whence = SEEK_SET)
    {
        return gzseek64($zp, $offset, $whence);
    }
}
