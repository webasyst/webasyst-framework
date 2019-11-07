<?php

class waSystemPluginModel extends waModel
{
    /** @var waSystemPlugin */
    protected $plugin;

    public function setPlugin(waSystemPlugin $plugin, $table = null)
    {
        $this->plugin = $plugin;
        if (empty($this->table)) {
            $type = constant(get_class($plugin).'::PLUGIN_TYPE');
            $this->table = sprintf('wa_%s_%s', $type, $plugin->getId());
            if (strlen($table)) {
                $this->table .= '_'.$table;
            }
        }
    }
}
