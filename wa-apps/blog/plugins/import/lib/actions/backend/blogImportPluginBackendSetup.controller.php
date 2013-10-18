<?php

class blogImportPluginBackendSetupController extends waJsonController
{
    public function execute()
    {
        $transport = ucfirst($this->getRequest()->post('transport', '', waRequest::TYPE_STRING_TRIM));
        $class = "blogImportPlugin{$transport}Transport";
        if ($transport && class_exists($class)) {
            try {
                $plugin = wa()->getPlugin('import');
                /**
                 * @var $plugin blogImportPlugin
                 */
                $settings = $plugin->getSettings();
                $instance = new $class($settings);
                /**
                 * @var $instance blogImportPluginTransport
                 */
                $namespace = $this->getApp().'_import_'.strtolower($transport);
                $this->response = $instance->getControls($namespace);
            } catch (waException $ex) {
                $this->errors['generic'] = $ex->getMessage();
            }
        } else {
            $this->errors['transport'] = sprintf(_wp("Transport type %s not found"), $transport);
        }
    }
}
