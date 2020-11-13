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

        // There is no contacts yet - first login action
        if (!$contact_model->countAll()) {
            $this->executeAction(new webasystLoginFirstAction());
            return;
        }

        $cm = new waWebasystIDClientManager();

        $webasyst_id_forced_auth = $cm->isBackendAuthForced() && !wa()->getRequest()->get('force_login_form');

        // Webasyst ID oauth not forced - standard backend auth login
        if (!$webasyst_id_forced_auth) {
            $this->setLayout(new webasystLoginLayout());
            if (waRequest::get('forgotpassword') !== null) {
                $this->executeAction(new webasystForgotPasswordAction());
            } else {
                $this->executeAction(new webasystLoginAction());
            }
            return;
        }

        // force run auth flow
        $auth = new waWebasystIDWAAuth();
        $auth_url = $auth->getBackendAuthUrl();

        $this->redirect($auth_url);
    }
}

