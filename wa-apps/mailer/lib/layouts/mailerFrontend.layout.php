<?php

class mailerFrontendLayout extends waLayout
{
    public function execute()
    {
        $this->view->assign('my_url', wa()->getRouteUrl('mailer/frontend/mySubscriptions'));
        $this->setThemeTemplate('index.html');
    }
}
