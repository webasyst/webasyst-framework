<?php

abstract class waSystemPluginActions extends waActions
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
        if (preg_match("/^([a-z]+)(Payment|Shipping)([A-Z][^A-Z]+)([A-Za-z]*)Actions$/", $class, $match)) {
            $plugin_id = $match[1];
            $type = strtolower($match[2]);
            $module = strtolower($match[3]);
            $action = $match[4].ucfirst($this->action);

            $name = ucfirst($module).$action.$this->getView()->getPostfix();
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
