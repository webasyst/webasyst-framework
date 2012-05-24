<?php 

class siteFilesUploadimageController extends siteFilesUploadController
{
    protected $name;
    
    protected function process()
    {
        $f = waRequest::file('file');
        $this->name = $f->name;
        if ($this->processFile($f)) {
            $this->response = wa()->getDataUrl('img/'.$this->name, true);
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
            $name = substr($f->name, 0, $i);
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
}