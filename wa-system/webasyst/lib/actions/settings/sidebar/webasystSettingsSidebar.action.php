<?php

class webasystSettingsSidebarAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign(array(
            'items' => $this->getSidebarItems(),
        ));
        $this->setTemplate('templates/actions/settings/sidebar/Sidebar.html');
    }

    protected function getSidebarItems()
    {
        $app_url = wa('webasyst')->getAppUrl().'webasyst/settings/';

        $items = array(
            'general' => array(
                'name' => _ws('General settings'),
                'url'  => $app_url,
            ),
            'field' => array(
                'name' => _ws('Contact fields'),
                'url'  => $app_url.'field/',
            ),
            'regions' => array(
                'name' => _ws('Countries & regions'),
                'url'  => $app_url.'regions/',
            ),
            'email'   => array(
                'name' => _ws('Email settings'),
                'url'  => $app_url.'email/',
            ),
            'maps'    => array(
                'name' => _ws('Maps'),
                'url'  => $app_url.'maps/',
            ),
            'captcha' => array(
                'name' => _ws('Captcha'),
                'url'  => $app_url.'captcha/',
            ),
        );

        return $items;
    }
}