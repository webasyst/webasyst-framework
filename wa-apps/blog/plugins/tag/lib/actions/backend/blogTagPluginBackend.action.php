<?php

class blogTagPluginBackendAction extends waViewAction
{
    public function execute()
    {
        $config = include(wa()->getAppPath().'/'.$this->getPluginRoot().'lib/config/config.php');
        $tag_model = new blogTagPluginModel();
        $this->view->assign('tags', $tag_model->getAllTags($config));
    }
}

