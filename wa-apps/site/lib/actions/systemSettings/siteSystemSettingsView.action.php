<?php

class siteSystemSettingsViewAction extends waViewAction
{
    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->view->assign(array(
            'settings_template' => waViewAction::getTemplate(),
        ));
    }

    protected function getTemplate()
    {
        return 'templates/actions/system/SystemSettings.html';
    }
}