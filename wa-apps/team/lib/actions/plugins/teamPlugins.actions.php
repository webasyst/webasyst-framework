<?php

class teamPluginsActions extends waPluginsActions
{
    protected $plugins_hash = '#';
    protected $is_ajax = false;
    protected $shadowed = true;

    public function preExecute()
    {
        if (!teamHelper::hasRights()) {
            throw new waRightsException();
        }

        if (!teamHelper::isAjax()) {
            $this->setLayout(new teamDefaultLayout());
        }
        $this->getResponse()->setTitle(_w('Plugin settings page'));
    }

    public function getTemplatePath($action = null)
    {
        $path = parent::getTemplatePath($action);
        if ($action !== 'settings') {
            $path = parent::getTemplatePath($action);
        } else {

            $is_calendar_external = false;
            $has_settings = false;
            $plugin_id = waRequest::get('id', null);
            if ($plugin_id) {
                $plugins = teamCalendarExternalPlugin::getPlugins();
                if (isset($plugins[$plugin_id])) {
                    $plugin = teamCalendarExternalPlugin::factory($plugin_id);
                    $has_settings = false;
                    if (is_object($plugin) && $plugin instanceof teamCalendarExternalPlugin) {
                        $is_calendar_external = true;
                        $has_settings = $plugin->hasSettings();
                    }
                }
            }

            if ($is_calendar_external) {
                $this->getView()->assign(array(
                    'orig_path' => $path,
                    'has_settings' => $has_settings,
                    'plugin' => $plugin
                ));
                $path = $this->getConfig()->getAppPath('templates/actions/plugins/Settings.html');
            }

        }

        return $path;
    }

}
