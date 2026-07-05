<?php

class webasystSettingsWasenderValidateAction extends waViewAction
{

    public function execute()
    {
        $email = waRequest::get('sender');
        if (empty($email) || !$this->isValidEmail($email)) {
            return;
        }

        $errormsg = null;
        $result = null;

        wa('installer');
        $api = new installerServicesApi();
        if (!$api->isConnected()) {
            $errormsg = sprintf_wp(
                '<%s>Connect to Webasyst ID<%s> to use the Webasyst sender server.',
                sprintf('a href="%s"', wa()->getConfig()->getBackendUrl(true) . 'webasyst/settings/waid/'),
                '/a'
            );
            $this->view->assign('errormsg', $errormsg);
            return;
        }

        try {
            $check_from_email = $api->serviceCall('SENDERCHECK', [
                'from_email' => $email,
            ]);

            if (ifset($check_from_email, 'response', 'need_replace', null)) {
                $result = $check_from_email['response'];
                $result['original_from_email'] = $email;
            }
        } catch (waException $e) {
            // should never happen
            $errormsg = $e->getMessage();
            $this->view->assign('errormsg', $errormsg);
            return;
        }

        if (empty($result)) {
            return;
        }

        $this->view->assign('data', $result);
    }

    protected function isValidEmail($email)
    {
        $email = (string)$email;
        $email = trim($email);
        if (strlen($email) <= 0) {
            return false;
        }
        $validator = new waEmailValidator();
        return $validator->isValid($email);
    }
}
