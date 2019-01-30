<?php

class teamFrontController extends waFrontController
{
    public function dispatch()
    {
        $env = $this->system->getEnv();
        if ($env == 'backend') {
            // Assign routing parameters to waRequest::param()
            // to enable routing.backend.php
            $module = waRequest::get($this->options['module']);
            if (empty($module)) {
                $routing = new waRouting($this->system, array(
                    'default' => array(
                        array(
                            'url' => wa()->getConfig()->systemOption('backend_url').'/team/*',
                            'app' => 'team',
                        ),
                    ),
                ));
                $routing->dispatch();
                if (!waRequest::param('module')) {
                    throw new waException('Page not found', 404);
                }
            }
        }
        parent::dispatch();
    }

    protected function runController($controller, $params = null)
    {
        $class = get_class($controller);
        if ($class === 'waDefaultViewController' && $controller->getAction()) {
            $class = $controller->getAction();
            if (is_object($class)) {
                $class = get_class($class);
            }
        }
        $evt_params = array(
            'controller' => $controller,
            'params' => &$params,
        );
        $handled = wa('team')->event('controller_before.'.$class, $evt_params);
        if ($handled) {
            return;
        }
        $result = parent::runController($controller, $params);
        wa('team')->event('controller_after.'.$class, $params);
        return $result;
    }
}
