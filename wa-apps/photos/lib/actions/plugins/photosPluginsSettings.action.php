<?php

class photosPluginsSettingsAction extends waViewAction
{
    public function execute()
    {
        $plugin_id = waRequest::get('id', null);
        $plugins_count = 0;
        if ($plugin_id) {
            $plugins = $this->getConfig()->getPlugins();
            $plugins_count = count($plugins);
            if (isset($plugins[$plugin_id])) {
                /**
                 * @var photosPlugin $plugin
                 */
                $plugin = waSystem::getInstance()->getPlugin($plugin_id);
                waSystem::pushActivePlugin($plugin_id, 'photos');
                $namespace = 'photos_'.$plugin_id;

                $params = array();
                $params['id'] = $plugin_id;
                $params['namespace'] = $namespace;
                $params['title_wrapper'] = '%s';
                $params['description_wrapper'] = '<br><span class="hint">%s</span>';
                $params['control_wrapper'] = '<div class="name">%s</div><div class="value">%s %s</div>';

                $settings_controls = $plugin->getControls($params);
                $this->getResponse()->setTitle(_w(sprintf('Plugin %s settings', $plugin->getName())));

                $this->view->assign('plugin_info', $plugins[$plugin_id]);

                $this->view->assign('plugin_id', $plugin_id);
                $this->view->assign('settings_controls', $settings_controls);
                waSystem::popActivePlugin();
            }
        }
        $this->view->assign('plugins_count', $plugins_count);
    }
}