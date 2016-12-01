<?php
class teamWebasystBackend_personal_profileHandler extends waEventHandler
{
    public function execute(&$params)
    {
        if (wa()->getUser()->getRights('team', 'backend') && !defined('WA_FORCE_SYSTEM_PROFILE')) {
            wa('team', 1)->getResponse()->redirect(wa()->getUrl()."u/{$params['user']['login']}/info/");
        }
    }
}
