<?php

/**
 * Экш формы логина /login
 * @see https://www.webasyst.com/ru/framework/docs/dev/auth-frontend/
 */
class guestbook2LoginAction extends waLoginAction
{

    public function execute()
    {
        $this->setLayout(new guestbook2FrontendLayout());
        $this->setThemeTemplate('login.html');
        parent::execute();
    }

}