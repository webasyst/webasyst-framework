<?php
/**
 * @author WebAsyst Team
 *
 */
class blogPluginsSettingsViewAction extends waViewAction
{
    /**
     *
     * @var blogPlugin
     */
    protected $plugin_instance;
    /**
     * @var string
     */
    protected $plugin_id;

    public function execute()
    {
        if (!$this->getUser()->isAdmin($this->getApp())) {
            throw new waRightsException(_w('Access denied'));
        }
        $plugins = wa()->getConfig()->getPlugins();
        if (($this->plugin_id || ($this->plugin_id = waRequest::request('slug', false)))) {
            if (!isset($plugins[$this->plugin_id]) || !($this->plugin_instance = waSystem::getInstance()->getPlugin($this->plugin_id))) {
                throw new waException(_w('Plugin not found', 404));
            }
            $namespace = $this->getApp().'_'.$this->plugin_id;

            if ($post = $this->getRequest()->post($namespace)) {
                $this->plugin_instance->setup($post)->saveSettings();
                if (get_class($this) == 'blogPluginsSettingsAction') {
                    $this->getResponse()->redirect('?module=plugins');
                }
            }

            $params = array();
            $params['namespace'] = $namespace;
            $params['title_wrapper'] = '<div class="name">%s</div>';
            $params['description_wrapper'] = '<br><span class="hint">%s</span><br>';
            $params['control_separator'] = '</div><br><div class="value no-shift">';

            $params['control_wrapper'] = <<<HTML
%s
<div class="value no-shift">
	%s
	%s
</div>
HTML;


            $this->view->assign('plugin_settings', $this->plugin_instance->getControls($params));
            $this->view->assign('plugin_info', $plugins[$this->plugin_id]);
            $this->view->assign('plugin_slug', $this->plugin_id);

            $title = sprintf(_w('Plugin %s settings'), $plugins[$this->plugin_id]['name']);
            $title .= " &mdash; ".wa()->accountName();

            $this->view->assign('title', html_entity_decode($title, ENT_NOQUOTES, 'utf-8'));
            waSystem::popActivePlugin();
        } else {
            $this->view->assign('plugin_slug', false);
        }
    }
}