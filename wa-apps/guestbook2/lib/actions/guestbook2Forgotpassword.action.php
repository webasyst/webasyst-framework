<?php

/**
 * Экш восстановления пароля /forgotpassword
 * @see https://www.webasyst.com/ru/framework/docs/dev/auth-frontend/
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