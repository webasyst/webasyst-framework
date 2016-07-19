<?php

class webasystLoginController extends waViewController
{
    public function execute()
    {
        try {
            $contact_model = new waContactModel();
        } catch (waException $e) {
            // db.php not found: this is the first time framework is started.
            // User has to specify DB access and create the first backend account.
            if ($e->getCode() == 600) {
                $this->executeAction(new webasystLoginConfigAction());
                return;
            } else {
                throw $e;
            }
        }

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
    }
}

