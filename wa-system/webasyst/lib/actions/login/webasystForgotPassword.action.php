<?php

class webasystForgotPasswordAction extends waForgotPasswordAction
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

    protected function getResetPasswordUrl($hash)
    {
        if (wa()->getEnv() == 'backend') {
            return wa()->getRootUrl(true).wa()->getConfig()->getBackendUrl(false).'/?forgotpassword&key='.$hash;
        } else {
            return wa()->getRootUrl(true).wa()->getConfig()->getRequestUrl(true, true).'?forgotpassword&key='.$hash;
        }
    }
}
