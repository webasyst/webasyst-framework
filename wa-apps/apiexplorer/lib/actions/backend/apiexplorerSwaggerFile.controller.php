<?php

class apiexplorerSwaggerFileController extends waController
{
    public function execute()
    {
        $app_id = waRequest::get('app_id', 'webasyst');
        $api_version = waRequest::get('version', 'v1');

        $file = wa()->getAppPath('api/swagger/' . $api_version . '.yaml', $app_id);
        if (!file_exists($file)) {
            throw new waException('Not found', 404);
        }

        $this->getResponse()
            ->addHeader('Content-Type', 'application/yaml')
            ->addHeader('Content-Disposition', 'attachment; filename="' . $app_id . '-' . $api_version . '.yaml"')
            ->sendHeaders();
        echo file_get_contents($file);
        exit;
    }
}