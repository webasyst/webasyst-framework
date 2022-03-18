<?php

class installerPluginsEnableController extends waJsonController
{
    /**
     * @return void
     * @throws waException
     */
    public function execute()
    {
        $app_id = waRequest::post('app_id');
        $result = installerHelper::pluginSetStatus($app_id, waRequest::post('plugin_id'), true);
        $this->response['message'] = _w('Cache cleared');
        if ($result !== true) {
            $this->response['message'] .= "<br>"._w('But with errors:')."<br>".implode("<br>", $result);
        }

        /**
         * @event plugin.enable
         * @return void
         */
        wa($app_id)->event('plugin.enable');
    }
}
