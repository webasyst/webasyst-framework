<?php

class photosPluginsAction extends waViewAction
{
    public function execute()
    {
        if (!$this->getUser()->isAdmin('photos')) {
            throw new waException(_w('Access denied'));
        }
        
        $config = $this->getConfig();
        
        $this->view->assign('sidebar_width', $config->getSidebarWidth());
        $this->view->assign('plugins', $this->getConfig()->getPlugins());
    }
}