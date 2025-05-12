<?php

/**
 * Translates app, plugin, theme or widget.
 */
class developerBackendTranslateAction extends developerAction
{
    public function execute()
    {
        $this->layout->assign('page', 'translate');
        
        $waConfig = $this->getConfig();
        $apps = $this->getUser()->getApps();
        
        if (waRequest::method() == 'post') {
            waRequest::setParam([waRequest::post('hash')]);

            ob_start();
            try {
                wao(new webasystLocaleCli())->run();
                $this->view->assign('result', ob_get_clean());
            } catch (Throwable $e) {
                ob_end_clean();
                $this->view->assign('error', $e->getMessage());
            }
        }
        
        $plugins = $themes = $widgets = [];
        foreach ($apps as $app_id => $app) {
            $appConfig = $waConfig->getAppConfig($app_id);
            if (ifset($app['plugins'])) {
                $app_plugins = $appConfig->getPlugins();
                if ($app_plugins) {
                    $plugins[$app['name']] = [];
                    foreach ($app_plugins as $plugin) {
                        $plugins[$app['name']][$app_id . '/plugins/' . $plugin['id']] = $plugin['name'];
                    }
                }
            }
            if (ifset($app['themes'])) {
                $app_themes = waSystem::getInstance()->getThemes($app_id);
                if ($app_themes) {
                    $themes[$app['name']] = [];
                    foreach ($app_themes as $theme) {
                        $themes[$app['name']][$app_id . '/themes/' . $theme['id']] = $theme['name'];
                    }
                }
            }
            $app_widgets = $appConfig->getWidgets();
            if ($app_widgets) {
                $widgets[$app['name']] = [];
                foreach ($app_widgets as $widget) {
                    $widgets[$app['name']][$app_id . '/widgets/' . $widget['widget']] = $widget['name'];
                }
            }
        }
        
        $this->view->assign('apps', waUtils::getFieldValues($apps, 'name', 'id'));
        $this->view->assign('plugins', $plugins);
        $this->view->assign('themes', $themes);
        $this->view->assign('widgets', $widgets);
    }
}
