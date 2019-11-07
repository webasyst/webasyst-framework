<?php

abstract class waSystemPluginAction extends waViewAction
{
    /** @var waSystemPlugin */
    protected $plugin;

    public function setPlugin(waSystemPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function getTemplate()
    {
        $match = array();
        $class = get_class($this);
        if (preg_match("/^([a-z]+)(Payment|Shipping)([A-Z][^A-Z]+)([A-Za-z]*)([A-Za-z]*)Action$/", $class, $match)) {
            $plugin_id = $match[1];
            $type = strtolower($match[2]);
            $module = strtolower($match[3]);
            $action = $match[4];

            $name = ucfirst($module).$action.$this->view->getPostfix();
        } else {
            throw new Exception('bad class name for waSystemPluginActions class');
        }

        $template = array(
            waConfig::get('wa_path_plugins'),
            $type,
            $plugin_id,
            'templates',
            'actions',
            $module,
            $name,
        );

        return implode('/', $template);
    }
}
