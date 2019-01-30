<?php

class webasystSettingsLayout extends waLayout
{
    public function execute()
    {
        $this->executeAction('sidebar', new webasystSettingsSidebarAction());

        /**
         * Include plugins js and css
         * @event backend_assets
         * @return array[string]string $return[%plugin_id%] Extra head tag content
         */
        $this->view->assign('backend_assets', wa('webasyst')->event('backend_assets'));
    }
}