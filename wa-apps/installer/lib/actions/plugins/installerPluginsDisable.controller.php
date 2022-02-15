<?php

class installerPluginsDisableController extends waJsonController
{
    public function execute()
    {
        $result  = installerHelper::pluginSetStatus(waRequest::post('app_id'), waRequest::post('plugin_id'));
        $this->response['message'] = _w('Cache cleared');
        if ($result !== true) {
            $this->response['message'] .= "<br>"._w('But with errors:')."<br>".implode("<br>", $result);
        }
    }
}
