<?php

class waMyNavAction extends waViewAction
{
    public function execute()
    {
        $auth = wa()->getAuthConfig();
        $this->view->assign('my_app', wa()->getEnv() == 'frontend' ? ifset($auth['app']) : '');
        $this->setThemeTemplate('my.nav.html');
    }
}