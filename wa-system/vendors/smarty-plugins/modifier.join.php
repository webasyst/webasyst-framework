<?php
// Avoids PHP 7.4 deprecation notice when used as $arr|join:' '
function smarty_modifier_join($arr, $str)
{
    if (is_array($str)) {
        list($arr, $str) = [$str, $arr];
    }
    return implode($str, $arr);
}
