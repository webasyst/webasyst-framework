<?php

class webasystForgotPasswordAction extends waBackendForgotPasswordAction
{
    public function execute()
    {
        $this->view->setOptions(array('left_delimiter' => '{', 'right_delimiter' => '}'));

        if ($this->layout) {
            if (waRequest::get('key')) {
                $this->layout->assign('dialog_class', 'newpassword');
            } else {
                $this->layout->assign('dialog_style', 'min-height: 150px; height: 200px');
            }
        }
        $this->template = wa()->getAppPath('templates/actions/forgot/ForgotPassword.html', 'webasyst');

        parent::execute();

        if ($this->layout) {
            $this->layout->assign('error', $this->view->getVars('error'));
        }
    }
}
