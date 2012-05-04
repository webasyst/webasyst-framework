<?php
/**
 * @author WebAsyst Team
 *
 */
class blogPluginsAction extends waViewAction
{
    public function preExecute()
    {
        if (!$this->getUser()->isAdmin($this->getApp())) {
			throw new waRightsException(_w('Access denied'));
		}
    }
    public function execute()
    {
		$this->setLayout(new blogDefaultLayout());
        $plugin_id = waRequest::get('slug', false);

        $plugins = wa()->getConfig()->getPlugins();
        $plugin_settings = array();
        $content = '';
        if ($plugins) {
            $storage = wa()->getStorage();
            if (!$plugin_id || !isset($plugins[$plugin_id])) {
                $plugin_id = $storage->read('blog_select_plugin');
                if (!$plugin_id) {
                    $plugin = array_shift($plugins);
                    $plugin_id = $plugin['id'];
                }
                if ($plugin_id) {
                    $this->redirect('?module=plugins&slug='.$plugin_id);
                }
            }
            else {
                $storage->write('blog_select_plugin', $plugin_id);
            }

            if ($plugin_id) {

                $app_id = $this->getApp();
                $namespace = $app_id.'_'.$plugin_id;
                $plugin_instance = waSystem::getInstance()->getPlugin($plugin_id);
                if ($post = $this->getRequest()->post($namespace)) {
                    $plugin_instance->setup($post)->saveSettings();
                }
                $params = array();
                $params['namespace'] = $namespace;
                $params['title_wrapper'] = '<div class="name">%s</div>';
                $params['description_wrapper'] = '<br><span class="hint">%s</span>';
                $params['control_separator'] = '</div><div class="value">';

                $params['control_wrapper'] = <<<HTML
<div class="name">%s</div>
<div class="value">
	%s
	%s
</div>
HTML;
                $plugin_settings = $plugin_instance->getControls($params);
                waSystem::popActivePlugin();
                $this->getResponse()->setTitle(sprintf(_w('Plugin %s settings'),$plugins[$plugin_id]['name']));
            } else {
                $this->getResponse()->setTitle(_w('Plugin settings page'));
            }
        }

        $this->view->assign('plugin_slug', $plugin_id);
        $this->view->assign('plugin_settings', $plugin_settings);
        $this->view->assign('plugins', $plugins);
    }
}