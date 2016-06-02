<?php

class waPluginsActions extends waActions
{
    protected $plugins_hash = '#/plugins';
    protected $is_ajax = true;
    protected $shadowed = false;

    public function defaultAction()
    {
        $template = $this->getTemplatePath();
        $this->display(array(
            'plugins_hash' => $this->plugins_hash,
            'plugins'      => $this->getConfig()->getPlugins(),
            'installer'    => $this->getUser()->getRights('installer', 'backend'),
            'is_ajax'      => $this->is_ajax,
            'shadowed'     => $this->shadowed,
        ), $template);
    }

    protected function getTemplatePath($action = null)
    {
        $path = $this->getConfig()->getRootPath().'/wa-system/plugin/templates/';
        if ($action == 'settings') {
            return $path.'Settings.html';
        } else {
            return $path.'Plugins.html';
        }
    }

    public function settingsAction()
    {
        $plugin_id = waRequest::get('id', null);
        $plugins_count = 0;
        $vars = array();
        if ($plugin_id) {
            $plugins = $this->getConfig()->getPlugins();
            $plugins_count = count($plugins);
            if (isset($plugins[$plugin_id])) {
                $plugin = waSystem::getInstance()->getPlugin($plugin_id, true);
                $namespace = wa()->getApp().'_'.$plugin_id;

                $params = array();
                $params['id'] = $plugin_id;
                $params['namespace'] = $namespace;
                $params['title_wrapper'] = '%s';
                $params['description_wrapper'] = '<br><span class="hint">%s</span>';
                $params['control_wrapper'] = '<div class="name">%s</div><div class="value">%s %s</div>';

                $settings_controls = $plugin->getControls($params);
                $this->getResponse()->setTitle(_w(sprintf('Plugin %s settings', $plugin->getName())));

                $vars['plugin_info'] = $plugins[$plugin_id];

                $vars['plugin_id'] = $plugin_id;
                $vars['settings_controls'] = $settings_controls;
            }
            waSystem::popActivePlugin();
        }
        $template = $this->getTemplatePath('settings');
        $vars['plugins_count'] = $plugins_count;
        $this->display($vars, $template);

    }

    public function sortAction()
    {
        try {
            $this->getConfig()->setPluginSort(waRequest::post('slug'), waRequest::post('pos', 0, 'int'));
            $this->displayJson(array());
        } catch (waException $e) {
            $this->displayJson(array(), $e->getMessage());
        }
    }

    public function saveAction()
    {
        $plugin_id = waRequest::get('id');
        if (!$plugin_id) {
            throw new waException(_ws("Can't save plugin settings: unknown plugin id"));
        }
        $namespace = $this->getAppId().'_'.$plugin_id;
        $plugin = waSystem::getInstance()->getPlugin($plugin_id);
        $settings = (array)$this->getRequest()->post($namespace);
        try {
            $files = waRequest::file($namespace);
            $settings_definitions = $plugin->getSettings();
            foreach ($files as $name => $file) {
                if (true
                    || #TODO use this check in future
                    (isset($settings_definitions[$name])
                        && !empty($settings_definitions[$name]['control_type'])
                        && ($settings_definitions[$name]['control_type'] == waHtmlControl::FILE)
                    )
                ) {
                    $settings[$name] = $file;
                }
            }
            $response = (array)$plugin->saveSettings($settings);
            $response['message'] = _w('Saved');
            $this->displayJson($response);
        } catch (Exception $e) {
            $this->displayJson(array(), $e->getMessage());
        }
    }
}
