<?php

/**
 * Available by URL:
 *     http://ROOT_PATH/BACKEND_URL/webasyst/shipping/s:module_id/s:plugin_action/
 * Class webasystShippingActionsController
 */
class webasystShippingActionsController extends webasystPluginActionsController
{
    public function preExecute()
    {
        parent::preExecute();

        $plugin_id = waRequest::param('plugin_id');
        $instance_id = waRequest::get('instance_id', null, waRequest::TYPE_STRING);
        $this->plugin = waShipping::factory($plugin_id, $instance_id, $this->app_id);
    }
}
