<?php

class webasystSettingsWaIDDisconnectController extends waJsonController
{
    public function execute()
    {
        $result = $this->disconnect();
        if (!$result['status']) {
            $error_code = $result['details']['error_code'];
            $error_message = $result['details']['error_message'];
            $this->errors[$error_code] = $error_message;
            return;
        }
    }

    public function disconnect()
    {
        $manager = new waWebasystIDClientManager();
        return $manager->disconnect();
    }
}
