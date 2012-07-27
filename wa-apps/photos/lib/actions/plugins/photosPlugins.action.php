<?php

class photosPluginsAction extends waViewAction
{
    public function execute()
    {
        if (!$this->getUser()->isAdmin('photos')) {
            throw new waException(_w('Access denied'));
        }
        $this->view->assign('plugins', $this->getConfig()->getPlugins());
    }
}