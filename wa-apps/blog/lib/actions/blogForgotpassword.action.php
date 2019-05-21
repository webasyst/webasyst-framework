<?php

class blogForgotpasswordAction extends waForgotPasswordAction
{
    public function execute()
    {
        $this->setLayout(new blogFrontendLayout());
        $this->setThemeTemplate('forgotpassword.html');
        parent::execute();
    }
}
