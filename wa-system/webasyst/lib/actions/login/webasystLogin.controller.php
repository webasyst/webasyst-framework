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
        if ($contact_model->isEmpty()) {
            $this->executeAction(new webasystLoginFirstAction());
            return;
        }

        $webasyst_id_forced_auth = false;
        // Check for Webasyst ID oauth only for non AJAX requests & not forced standard login form
        if (!waRequest::isXMLHttpRequest() && empty(waRequest::request('wa_json_mode')) && empty(waRequest::get('force_login_form'))) {
            $cm = new waWebasystIDClientManager();
            try {
                $webasyst_id_forced_auth = $cm->isBackendAuthForced();
            } catch (waException $e) {
                // do nothing
            }
        }

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

