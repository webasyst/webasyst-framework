<?php

class webasystSettingsSidebarAction extends webasystSettingsViewAction
{
    public function execute()
    {
        $this->view->assign(array(
            'items' => webasystHelper::getSettingsSidebarItems(),
        ));
        $this->setTemplate('templates/actions/settings/sidebar/Sidebar.html');
    }
}
