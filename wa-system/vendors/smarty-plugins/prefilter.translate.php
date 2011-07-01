<?php

function smarty_gettext_translate($str)
{
    return _wp(str_replace('\"', '"', $str));
}

function smarty_prefilter_translate($source, &$smarty)
{
    return preg_replace("/\[\`([^\`]+)\`\]/usie", "smarty_gettext_translate('$1')", $source);
}
