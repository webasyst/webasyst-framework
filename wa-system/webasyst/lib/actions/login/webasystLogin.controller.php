<?php

class webasystLoginController extends waViewController
{
    public function execute()
    {
        try {
            $contact_model = new waContactModel();
            if ($contact_model->countAll()) {
                $this->setLayout(new webasystLoginLayout());
                if (waRequest::get('forgotpassword') !== null) {
                    $this->executeAction(new webasystForgotPasswordAction());
                } else {
                    $this->executeAction(new webasystLoginAction());
                }
            } else {
                $this->executeAction(new webasystLoginFirstAction());
            }
        } catch (waException $e) {
            // db.php not found
            if ($e->getCode() == 600) {
                $this->executeAction(new webasystLoginConfigAction());
            }
        }
    }
}

