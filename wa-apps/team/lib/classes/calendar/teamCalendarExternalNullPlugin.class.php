<?php

class teamCalendarExternalNullPlugin extends teamCalendarExternalPlugin
{
    public function __construct($info)
    {
        $plugin_id = is_array($info) ? $info['plugin_id'] : $info;
        $this->id = null;
        $this->info = is_array($info) ? $info : array(
            'plugin_id' => $plugin_id
        );
        $this->app_id = 'team';
        $this->path = wa()->getAppPath('plugins/'.$plugin_id, $this->app_id);

        $plugin_info_path = $this->path . '/lib/config/plugin.php';

        if (file_exists($plugin_info_path)) {
            $plugin_info = include $plugin_info_path;
            $this->info = array_merge($this->info, $plugin_info);
            $this->info['name'] = sprintf(_w('%s - Uninstalled'), $this->info['name']);
        } else {
            $this->info = array_merge($this->info, array(
                'name' => sprintf(_w('%s - Uninstalled'), "#{$plugin_id}"),
                'description' => _w('Uninstalled or not existed plugin'),
                'icon' => '',
                'img' => '',
                'version' => '',
                'vendor' => '',
                'frontend' => '',
                'external_calendar' => true,
                'integration_level' => teamCalendarExternalModel::INTEGRATION_LEVEL_SUBSCRIPTION
            ));
        }
    }

    /**
     * @param $id
     * @param array $options
     * @return string
     */
    public function authorizeBegin($id, $options = array())
    {
        return '';
    }

    /**
     * @param array $options
     * @return array
     */
    public function authorizeEnd($options = array())
    {
        return array();
    }

    /**
     * @throws teamCalendarExternalTokenInvalidException
     * @param array $options
     * @return array
     */
    public function getCalendars($options = array())
    {
        return array();
    }

    /**
     * @param array $options
     * @return array|false
     */
    public function getEvents($options = array())
    {
        return array();
    }

    /**
     * @return bool
     */
    public function isImported()
    {
        return false;
    }

    /**
     * @param array $options
     * @return array|false
     */
    public function getChanges($options = array())
    {
        return array();
    }

    /**
     * @return mixed
     */
    public function isConnected()
    {
        return false;
    }

    /**
     * @param array $options
     * @return string
     */
    public function getAccountInfoHtml($options = array())
    {
        return ifset($this->info['name'], _w('Plugin not found'));
    }

    /**
     * @return string
     */
    public function getIconUrl()
    {
        return '';
    }
}
