<?php

class webasystSettingsSidebarAction extends webasystSettingsViewAction
{
    public function execute()
    {
        $this->view->assign(array(
            'items' => webasystHelper::getSettingsSidebarItems(),
        ));
        $this->setTemplate('settings/sidebar/Sidebar.html', true);
    }
}
