<?php

class waMyNavAction extends waViewAction
{
    public function execute()
    {
        $app = '';
        if (wa()->getEnv() === 'frontend') {
            $domain_config = waDomainAuthConfig::factory();
            $app = $domain_config->getApp();
        }
        $this->view->assign('my_app', $app);
        $this->setThemeTemplate('my.nav.html');
    }
}
