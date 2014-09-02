<?php
/**
 * @author Webasyst
 *
 */
class blogPluginsAction extends waViewAction
{
    public function execute()
    {
        if (!$this->getUser()->isAdmin($this->getApp())) {
            throw new waRightsException(_w('Access denied'));
        }
        $this->getResponse()->setTitle(_w('Plugin settings page'));

        $this->setLayout(new blogDefaultLayout());
        $this->view->assign('plugins', wa()->getConfig()->getPlugins());
    }
}