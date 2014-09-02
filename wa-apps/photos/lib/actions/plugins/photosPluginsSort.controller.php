<?php
/**
 * @author Webasyst
 *
 */
class photosPluginsSortController extends waJsonController
{
    public function execute()
    {
        if (!$this->getUser()->isAdmin($this->getApp())) {
            throw new waRightsException(_w('Access denied'));
        }
        $plugin_slug = waRequest::post('slug');
        $new_pos = waRequest::post('pos', 0, 'int');

        $response = 'fail';

        $plugins_config = $this->getConfig()->getConfigPath('plugins.php', true);
        if ( file_exists($plugins_config) ) {
            $plugins = include($plugins_config);
            if (isset($plugins[$plugin_slug]) && $plugins[$plugin_slug]) {
                $result = array();
                $pos = 0;
                foreach ($plugins as $name => $plugin) {
                    if ($new_pos === $pos) {
                        $result[$plugin_slug] = $plugins[$plugin_slug];
                        $new_pos = false;
                    }

                    if($name != $plugin_slug) {
                        $result[$name] = $plugin;
                        if ($plugin) {
                            ++$pos;
                        }
                    }
                }
                if(!isset($result[$plugin_slug])) {
                    $result[$plugin_slug] = $plugins[$plugin_slug];
                }

                if(waUtils::varExportToFile($result,$plugins_config)) {
                    $response = 'ok';
                } else {
                    $response = 'io error';
                }
            }
        }
        $this->response = $response;

        $this->getResponse()->addHeader('Content-type', 'application/json');
    }
}