<?php

/**
 * Экш восстановления пароля /forgotpassword
 */
class guestbook2ForgotpasswordAction extends waForgotPasswordAction
{
    public function execute()
    {
        $this->setLayout(new guestbook2FrontendLayout());
        $this->setThemeTemplate('forgotpassword.html');
        parent::execute();
    }
}