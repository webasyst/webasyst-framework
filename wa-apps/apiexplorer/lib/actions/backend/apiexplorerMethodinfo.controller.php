<?php

class apiexplorerMethodinfoController extends apiexplorerJsonController
{
    public function execute()
    {
        $method = waRequest::get('method', false, waRequest::TYPE_STRING_TRIM);
        $app = explode('.', $method)[0];
        $app2api = new apiexplorerMethods($app);
        $method = $app2api->getMethod($method);
        $this->response = [
            'type'    => $method->getType(),
            'name'    => $method->getName(),
            'app'     => $app,
        ];

    }
}
