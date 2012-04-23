<?php 

class waSystemCache extends waVarExportCache
{
    protected function getFilePath()
    {
        return waFiles::create(waConfig::get('wa_path_cache').'/'.$this->key.'.php');
    }
}