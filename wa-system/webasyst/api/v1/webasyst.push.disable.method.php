<?php

class webasystPushDisableMethod extends webasystPushEnableMethod
{
    public function execute()
    {
        $_json = $this->readBodyAsJson();
        $request_data = (array) ifempty($_json, []);

        $push_adapter = $this->getPushAdapter();
        if (empty($push_adapter)) {
            $this->http_status_code = 400;
            $this->response = [
                'error' => 'push_not_enabled',
                'error_description' => _ws('No web push provider is configured.'),
            ];
            return;
        }

        try {
            $push_adapter->deleteSubscriber($request_data);
        } catch (waException $ex) {
            $this->http_status_code = 400;
            $this->response = [
                'error' => 'invalid_data',
                'error_description' => $ex->getMessage(),
            ];
            return;
        }
        $this->http_status_code = 204;
    }
}
