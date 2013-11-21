<?php 

class waSystemCache extends waVarExportCache
{
    protected function getFilePath()
    {
        $path = waConfig::get('wa_path_cache').'/'.$this->key.'.php';
        waFiles::create($path);
        return $path;
    }

    public function getFilemtime()
    {
        $path = $this->getFilePath();
        if (file_exists($path)) {
            return filemtime($path);
        }
        return 0;
    }
}