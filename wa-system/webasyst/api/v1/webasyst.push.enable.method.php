<?php

class webasystPushEnableMethod extends waAPIMethod
{
    protected $method = 'POST';
    protected $request_body = null;
    protected $push_adapter = null;

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

        $scope = ifset($request_data['scope'], null);
        unset($request_data['scope']);

        try {
            $push_adapter->addSubscriber($request_data, $scope);
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

    protected function getPushAdapter()
    {
        if (!empty($this->push_adapter)) {
            return $this->push_adapter;
        }

        try {
            $this->push_adapter = wa()->getPush();
        } catch (waException $ex) {
            return null;
        }

        if (!$this->push_adapter->isEnabled()) {
            return null;
        }
        return $this->push_adapter;
    }

    protected function readBodyAsJson()
    {
        $body = $this->readBody();
        if ($body) {
            return json_decode($body, true);
        }
        return null;
    }

    protected function readBody()
    {
        if ($this->request_body === null) {
            $this->request_body = '';
            $contents = file_get_contents('php://input');
            if (is_string($contents) && strlen($contents)) {
                $this->request_body = $contents;
            }
        }
        return $this->request_body;
    }
}
