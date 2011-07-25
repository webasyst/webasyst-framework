<?php

function smarty_gettext_translate($matches)
{
    return _wp(str_replace('\"', '"', $matches[1]));
}

function smarty_prefilter_translate($source, &$smarty)
{
    return preg_replace_callback("/\[\`([^\`]+)\`\]/usi", "smarty_gettext_translate", $source);
}
