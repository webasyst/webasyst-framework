<?php

function smarty_gettext_translate($matches)
{
    if ($str = waLocale::getString($matches[1])) {
        return $str;
    }
    return _wp(str_replace('\"', '"', $matches[1]));
}

function smarty_gettext_s_translate($matches)
{
    return _ws(str_replace('\"', '"', $matches[1]));
}

function smarty_prefilter_translate($source, &$smarty)
{
    $mid_result = preg_replace_callback("/\[\`([^\`]+)\`\]/usi", "smarty_gettext_translate", $source);
    if ($mid_result === null) {
        return $source;
    }
    return preg_replace_callback("/\[s\`([^\`]+)\`\]/usi", "smarty_gettext_s_translate", $mid_result);
}
