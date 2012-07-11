<?php

class blogPostImageController extends waJsonController
{
    protected $name;

    protected function process()
    {
        $f = waRequest::file('file');
        $this->name = $f->name;
    	if ($this->processFile($f)) {
    	    $this->response =  wa()->getDataUrl('img/'.$this->name, true, null, true);
    	}
    }

    protected function getPath()
    {
    	return wa()->getDataPath('img', true);
    }

    protected function isValid($f)
    {
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
    	if (!in_array(strtolower($f->extension), $allowed)) {
    		$this->errors[] = sprintf(_w("Files with extensions %s are allowed only."), '*.'.implode(', *.', $allowed));
    		return false;
    	}
    	return true;
    }

    protected function save(waRequestFile $f)
    {
        if (file_exists($this->path.DIRECTORY_SEPARATOR.$f->name)) {
            $i = strrpos($f->name, '.');
            $name = urlencode(substr($f->name, 0, $i));
            $ext = substr($f->name, $i + 1);
            $i = 1;
            while (file_exists($this->path.DIRECTORY_SEPARATOR.$name.'-'.$i.'.'.$ext)) {
                $i++;
            }
            $this->name = $name.'-'.$i.'.'.$ext;
            return $f->moveTo($this->path, $this->name);
        }
        return $f->moveTo($this->path, $f->name);
    }

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

    /**
     * @param waRequestFile $f
     * @return bool
     */
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
}