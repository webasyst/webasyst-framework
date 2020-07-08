<?php

// Is required for backward compatibility js-routing of the installer
// with the version when the Store appeared in the iframe.
class installerFrontController extends waFrontController
{
    public function dispatch()
    {
        try {
            parent::dispatch();
            return;
        } catch (Exception $e) {
            $env = wa()->getEnv();
            $is_deprecated_route = $this->isDeprecatedRoute();

            if ($env === 'backend' && $is_deprecated_route) {
                $view = wa()->getView();
                $view->assign(array(
                    'store_url' => wa()->getAppUrl('installer'),
                ));

                echo $view->fetch(wa()->getAppPath('templates/includes/deprecated_route.html', 'installer'));

                return;
            }

            throw $e;
        }
    }

    protected function getDeprecatedRoutes()
    {
        return $deprecated_routes = array(
            array('apps', null),
            array('apps', 'info'),

            array('plugins', null),
            array('plugins', 'info'),

            array('themes', null),
            array('themes', 'info'),

            array('widgets', null),
            array('widgets', 'info'),

            array('featured', null),
        );
    }

    protected function isDeprecatedRoute()
    {
        $plugin = waRequest::get('plugin', null, 'string');
        $module = waRequest::get($this->options['module'], 'backend', 'string');
        $action = waRequest::get($this->options['action'], null, 'string');

        $deprecated_routes = $this->getDeprecatedRoutes();

        if (!$plugin && in_array(array($module, $action), $deprecated_routes)) {
            return true;
        }

        return false;
    }
}