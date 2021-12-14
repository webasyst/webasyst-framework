<?php

class webasystSettingsWaIDConnectController extends waJsonController
{
    public function execute()
    {
        $result = $this->connect();
        if (!$result['status']) {
            $error_code = $result['details']['error_code'];
            $error_message = $result['details']['error_message'];
            $this->errors[$error_code] = $error_message;
            return;
        }

        $this->response['webasyst_id_auth_url'] = $this->getWebasystIDAuthUrl();
    }

    public function connect()
    {
        $manager = new waWebasystIDClientManager();
        return $manager->connect();
    }

    protected function getWebasystIDAuthUrl()
    {
        $m = new waWebasystIDClientManager();
        if ($m->isConnected()) {
            $auth = new waWebasystIDWAAuth();
            return $auth->getUrl();
        }
        return '';
    }
}
