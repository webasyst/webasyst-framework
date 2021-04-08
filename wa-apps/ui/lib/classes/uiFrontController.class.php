<?php
/**
 * Implements routing-based navigation in backend
 */
class uiFrontController extends waFrontController
{
    public function dispatch()
    {
        $env = $this->system->getEnv();
        if ($env == 'backend') {
            // Assign routing parameters to waRequest::param()
            // to enable routing.backend.php
            $module = waRequest::get($this->options['module']);
            $plugin = waRequest::get('plugin', null, 'string');
            if (empty($module) && empty($plugin)) {
                $routing = new waRouting($this->system, array(
                    'default' => array(
                        array(
                            'url' => wa()->getConfig()->systemOption('backend_url').'/ui/*',
                            'app' => 'ui',
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

    /**
     * @param waDefaultViewController $controller
     * @param null $params
     * @return mixed|void
     */
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
            'params'     => &$params,
        );
        $handled = wa('ui')->event('controller_before.'.$class, $evt_params);
        if ($handled) {
            return;
        }
        $result = parent::runController($controller, $params);
        wa('ui')->event('controller_after.'.$class, $params);
        return $result;
    }
}
