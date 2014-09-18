<?php

class waUploadJsonController extends waJsonController
{   
    protected $path;
    
    public function execute()
    {
        $this->path = $this->getPath();
        
        if (!is_writable($this->path)) {
            $p = substr($this->path, strlen(wa()->getDataPath('', true)));
            $this->errors = sprintf(_w("File could not bet saved due to the insufficient file write permissions for the %s folder."), $p);
        } else {
            $this->errors = array();
            $this->process();
            $this->errors = implode(" \r\n", $this->errors);
        }
    }
    
    protected function process()
    {
        foreach (waRequest::file('files') as $f) {
            $this->processFile($f);
        }        
    }
    
    protected function getPath()
    {
        return wa()->getDataPath('upload', true);
    }
    
    protected function isValid($f)
    {
        $ext = $f->extension;
        if (strpos(strtolower($f->name), '.php') !== false) {
            if (strtolower($ext) != 'php') {
                $ext = 'php';
            }
        }
        if (in_array(strtolower($ext), array('php', 'phtml', 'htaccess'))) {
            $this->errors[] = sprintf(_w("Files with extension .%s are not allowed for upload due to the security considerations."), $ext);
            return false;
        }
        return true;
    }

    /**
     * @param waRequestFile $f
     * @return bool
     */
    protected function processFile(waRequestFile $f)
    {
        if ($f->uploaded()) {
            if (!$this->isValid($f)) {
                return false;
            }
            if (!$this->save($f)) {
                $this->errors[] = sprintf(_w('Failed to upload file %s.'), $f->name);
                return false;
            }
            return true;
        } else {
            $this->errors[] = sprintf(_w('Failed to upload file %s.'), $f->name).' ('.$f->error.')';
            return false;
        }
    }
    
    protected function save(waRequestFile $f)
    {
        $name = $f->name;
        if (!preg_match('//u', $name)) {
            $tmp_name = @iconv('windows-1251', 'utf-8//ignore', $name);
            if ($tmp_name) {
                $name = $tmp_name;
            }
        }
        return $f->moveTo($this->path, $name);
    }
}