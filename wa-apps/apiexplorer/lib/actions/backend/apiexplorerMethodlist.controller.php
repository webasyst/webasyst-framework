<?php

class apiexplorerMethodlistController extends apiexplorerJsonController
{
    public function execute()
    {
        $api_version = waRequest::get('version', 'v1');
        $force_renew = waRequest::get('renew', false);
        $methods = (new apiexplorerAllMethods(wa()->getUser()))->getList($force_renew);
        $this->response = ['methods' => $methods];
    }
}
