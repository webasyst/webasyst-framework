<?php

class siteFilesUploadController extends waJsonController
{   
    protected $path;
    
    public function execute()
    {
        $this->path = $this->getPath();
        
        if (!is_writable($this->path)) {
        	$p = substr($this->path, strlen(wa()->getDataPath('', true)));
        	$this->errors = sprintf(_w("File could not bet saved due to the insufficient file write permissions for the %s folder."), $p);
        	return false;
        }
        
        $this->errors = array();
        $this->process();
        $this->errors = implode(" \r\n", $this->errors);
    }
    
    protected function process()
    {
        foreach (waRequest::file('files') as $f) {
        	$this->processFile($f);
        }        
    }
    
    protected function getPath()
    {
        $path = rtrim(waRequest::post('path'), ' /');
        return wa()->getDataPath($path, true);
    }
    
    protected function isValid($f)
    {
        if ($f->extension == 'php') {
        	$this->errors[] = sprintf(_w("Files with extension .%s are not allowed for upload due to the security considerations."), $f->extension);
            return false;
        }
        return true;
    }
    
    protected function processFile($f)
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
        return $f->moveTo($this->path, $f->name);
    }
}