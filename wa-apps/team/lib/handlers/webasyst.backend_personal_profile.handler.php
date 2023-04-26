<?php
class teamWebasystBackend_personal_profileHandler extends waEventHandler
{
    public function execute(&$params)
    {
        if (wa()->getUser()->getRights('team', 'backend') && !defined('WA_FORCE_SYSTEM_PROFILE')) {
            $user_login = urlencode($params['user']['login']);
            wa('team', 1)->getResponse()->redirect(wa()->getUrl()."u/$user_login/info/");
        }
    }
}
