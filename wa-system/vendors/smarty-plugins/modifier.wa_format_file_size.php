<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


function smarty_modifier_wa_format_file_size($file_size, $format='%0.2f', $dimensions='Bytes,KBytes,MBytes,GBytes')
{
    $dimensions = explode(',',$dimensions);
    $dimensions = array_map('trim',$dimensions);
    $dimension = array_shift($dimensions);
    while(($file_size>768)&&($dimension = array_shift($dimensions))){
        $file_size = $file_size/1024;
    }
    return sprintf($format,$file_size).' '.$dimension;
}
