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

                // make sure it's utf-8 or at least something we can json_encode
                $f_encoded = $f;
                if (!preg_match('!!u', $f)) {
                    $f_encoded = @iconv('windows-1251', 'utf-8//ignore', $f);
                    if (!$f_encoded) {
                        $f_encoded = utf8_encode($f);
                    }
                }

                if ($sub_dirs = $this->getDirs($path.'/'.$f)) {
                    $result[] = array(
                        'id' => $f_encoded,
                        'childs' => $sub_dirs
                    );
                } else {
                    $result[] = $f_encoded;
                }
            }
        }
        closedir($dh);
        return $result;
    }
}