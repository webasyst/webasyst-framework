<?php 

function smarty_prefilter_content_nofilter($source, &$smarty) {
    $source = str_replace('{$content}', '{$content nofilter}', $source);
    $source = preg_replace('/({\$wa->snippet\([^}]*)}/i', '$1 nofilter}', $source);
    return $source;
}