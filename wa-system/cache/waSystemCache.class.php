<?php 

class waSystemCache extends waVarExportCache
{
    protected function getFilePath()
    {
        $path = waConfig::get('wa_path_cache').'/'.$this->key.'.php';
        waFiles::create($path);
        return $path;
    }
}