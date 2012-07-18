<?php

/**
 * Экш формы логина /login
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