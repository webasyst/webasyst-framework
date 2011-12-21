<?php

class siteFilesAction extends waViewAction
{
    public function execute()
    {
        $path = wa()->getDataPath(null, true);
        $dirs = $this->getDirs($path);
        $this->view->assign('dirs', $dirs);
        $this->view->assign('domain', siteHelper::getDomain());
    }
    
    protected function getDirs($path)
    {
        $result = array();
        $dh = opendir($path);
        while (($f = readdir($dh)) !== false) {
            if ($f !== '.' && $f !== '..' && is_dir($path.'/'.$f)) {
                if ($sub_dirs = $this->getDirs($path.'/'.$f)) {
                    $result[] = array(
                        'id' => $f,
                        'childs' => $sub_dirs 
                    );
                } else {
                    $result[] = $f;
                }
            }
        }
        closedir($dh);
        return $result;
    }
}