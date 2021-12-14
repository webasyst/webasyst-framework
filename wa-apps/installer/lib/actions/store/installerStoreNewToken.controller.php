<?php

class installerStoreNewTokenController extends waJsonController
{
    public function execute()
    {
        $config = installerStoreHelper::getInstallerConfig();

        try {
            $token = $config->getTokenData(true);
        } catch (Exception $e) {
            return $this->errors = $e->getMessage();
        }

        $this->response = $token;
    }
}