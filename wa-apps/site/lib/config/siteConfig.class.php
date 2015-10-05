<?php 

class siteConfig extends waAppConfig
{
    protected $_routes = null;

    public function checkRights($module, $action)
    {
        switch ($module) {
            case 'files':
                if ($action == 'uploadimage') {
                    return true;
                }
                return wa()->getUser()->isAdmin($this->application);
            case 'domains':
            case 'themes':
            case 'blocks':
                return wa()->getUser()->isAdmin($this->application); 
        }
        return true;
    }

    public function getRouting($route = array(), $dispatch = true)
    {
        if ($this->_routes === null || $dispatch) {
            $routes = parent::getRouting($route);
            /**
             * Extend routing via plugin routes
             * @event routing
             * @param array $routes
             * @return array routes collected for every plugin
             */
            $result = wa()->event(array('site', 'routing'), $routes);
            $all_plugins_routes = array();
            foreach ($result as $plugin_id => $routing_rules) {
                if ($routing_rules) {
                    $plugin = str_replace('-plugin', '', $plugin_id);
                    foreach ($routing_rules as $url => &$route) {
                        if (!is_array($route)) {
                            list($route_ar['module'], $route_ar['action']) = explode('/', $route);
                            $route = $route_ar;
                        }
                        $route['plugin'] = $plugin;
                        $all_plugins_routes[$url] = $route;
                    }
                    unset($route);
                }
            }
            $routes = array_merge($all_plugins_routes, $routes);
            if ($dispatch) {
                return $routes;
            }
            $this->_routes = $routes;
        }
        return $this->_routes;
    }


    public function explainLogs($logs)
    {
        $page_ids = array();
        foreach ($logs as $l_id => $l) {
            if (in_array($l['action'], array('page_add', 'page_edit', 'page_move'))) {
                $page_ids[] = $l['params'];
            } elseif (substr($l['action'], 0, 8) == 'template' || substr($l['action'], 0, 5) == 'theme' ||
                substr($l['action'], 0, 5) == 'route') {
                $logs[$l_id]['params_html'] = htmlspecialchars($l['params']);
            }
        }
        if ($page_ids) {
            $class_name = $this->application . 'PageModel';
            /**
             * @var waPageModel $model
             */
            $model = new $class_name();
            $pages = $model->query('SELECT id, domain_id, name FROM '.$model->getTableName().' WHERE id IN (i:ids)', array('ids' => $page_ids))->fetchAll('id');
            $app_url = wa()->getConfig()->getBackendUrl(true).$l['app_id'].'/';
            foreach ($logs as &$l) {
                if (in_array($l['action'], array('page_add', 'page_edit', 'page_move')) && isset($pages[$l['params']])) {
                    $l['params_html'] = '<div class="activity-target"><a href="'.$app_url.'?domain_id='.$pages[$l['params']]['domain_id'].'#/pages/'.$l['params'].'">'.
                        htmlspecialchars($pages[$l['params']]['name']).'</a></div>';
                }
            }
            unset($l);
        }
        return $logs;
    }
}