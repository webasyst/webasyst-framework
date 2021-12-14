<?php

class installerPluginsEnableController extends waJsonController
{
    public function execute()
    {
        try {
            $app_id = waRequest::post('app_id');
            $plugin_id = waRequest::post('plugin_id');
            $apps = wa()->getApps();
            if (empty($app_id) || empty($plugin_id) || empty($apps[$app_id])) {
                throw new waException('Plugin not found');
            }
            $installer = new waInstallerApps();
            //TODO check that plugin a exists
            $installer->updateAppPluginsConfig($app_id, $plugin_id, true);

            $params = array(
                'type' => 'plugins',
                'id'   => sprintf('%s/%s', $app_id, $plugin_id),
                'ip'   => waRequest::getIp(),
            );

            $this->logAction('item_enable', $params);

            $errors = installerHelper::flushCache();

            $this->response['message'] = _w('Cache cleared');
            if ($errors) {
                $this->response['message'] .= "<br>"._w('But with errors:')."<br>".implode("<br>", $errors);
            }

            /**
             * @event plugin.enable
             * @return void
             */
            wa($app_id)->event('plugin.enable');
        } catch (Exception $ex) {
            $this->setError($ex->getMessage());
        }
    }
}
