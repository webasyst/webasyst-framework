<?php

class waRouting
{
    /**
     * @var waSystem
     */
    protected $system;
    protected $routes = array();
    protected $domain;
    protected $route;
    protected $root_url;
    protected $aliases = array();

    public function __construct(waSystem $system, $routes = array())
    {
        $this->system = $system;
        if (!$routes) {
            $routes = $this->system->getConfig()->getConfigFile('routing');
            if (!is_array($routes)) {
                waLog::log("Invalid or missed routing config file");
                $routes = array();
            }
        }
        $this->setRoutes($routes);
    }

    public function setRoutes($routes)
    {
        foreach ($routes as $domain => $domain_routes) {
            if (is_array($domain_routes)) {
                $this->routes[$domain] = $this->formatRoutes($domain_routes, false);
            } else {
                $this->aliases[$domain] = $domain_routes;
                $this->routes[$domain] = isset($routes[$domain_routes]) ? $routes[$domain_routes] : array();
            }
        }
    }


    public function getDomains()
    {
        return array_diff(array_keys($this->routes), array_keys($this->aliases));
    }

    public function setRoute($route, $domain = null)
    {
        $this->route = $route;
        if ($domain !== null) {
            $this->domain = $domain;
        }
    }

    public function getRoute($name = null)
    {
        if ($name) {
            return isset($this->route[$name]) ? $this->route[$name] : null;
        }
        return $this->route;
    }

    protected function formatRoutes($routes, $is_app = false)
    {
        $result = array();
        $routes[] = false;
        $keys = array_keys($routes);
        $n = end($keys);
        unset($keys);
        unset($routes[$n]);
        foreach ($routes as $r_id => $r) {
            $key = false;
            if (!is_array($r)) {
                $r_parts = explode('/', $r);
                $r = array('url' => $r_id);
                if ($is_app) {
                    $r['module'] = $r_parts[0];
                    if (isset($r_parts[1])) {
                        $r['action'] = $r_parts[1];
                    }
                } else {
                    $r['app'] = $r_parts[0];
                    if (isset($r_parts[1])) {
                        $r['module'] = $r_parts[1];
                    }
                }
            } elseif (!isset($r['url'])) {
                $r['url'] = $r_id;
            } else {
                $key = true;
            }
            if ($key) {
                $result[$r_id] = $r;
            } else {
                $result[$n++] = $r;
            }
        }
        return $result;
    }

    public function getByApp($app_id, $domain = null)
    {
        $result = array();
        foreach ($this->routes as $d => $routes) {
            if (isset($this->aliases[$d])) {
                continue;
            }
            foreach ($routes as $r_id => $r) {
                if (isset($r['app']) && $r['app'] == $app_id) {
                    $result[$d][$r_id] = $r;
                }
            }
        }
        if ($domain) {
            return isset($result[$domain]) ? $result[$domain] : array();
        }
        return $result;
    }

    public function getRoutes($domain = null)
    {
        $domain = $this->getDomain($domain, true);
        if (isset($this->routes[$domain])) {
            return $this->routes[$domain];
        }
        if (isset($this->routes['default']) && $this->routes['default']) {
            return $this->routes['default'];
        }
        return array();
    }

    public function getDomain($domain = null, $check = false)
    {
        if ($domain) {
            return $domain;
        }
        if ($this->domain === null) {
            $this->domain = waRequest::server('HTTP_HOST');
            if ($this->domain === null) {
                return null;
            }
            $u = trim($this->system->getRootUrl(), '/');
            if ($u) {
                $this->domain .= '/'.$u;
            }
        }
        if ($check) {
            if (!isset($this->routes[$this->domain])) {
                if (substr($this->domain, 0, 4) == 'www.') {
                    $domain = substr($this->domain, 4);
                } else {
                    $domain = 'www.'.$this->domain;
                }
                if (wa()->getEnv() == 'frontend' && isset($this->routes[$domain])) {
                    $https = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : '';
                    $url = 'http'.(strtolower($https) == 'on' ? 's' : '').'://';
                    $url .= $this->getDomainUrl($domain).'/'.wa()->getConfig()->getRequestUrl();
                    wa()->getResponse()->redirect($url, 301);
                }
                return $domain;
            }
        }
        if (isset($this->aliases[$this->domain])) {
            $this->domain = $this->aliases[$this->domain];
        }

        return $this->domain;
    }

    public function getRootUrl()
    {
        return $this->root_url;
    }

    public function dispatch()
    {
        $url = $this->system->getConfig()->getRequestUrl(true, true);
        $url = urldecode($url);
        $r = $this->dispatchRoutes($this->getRoutes(), $url);
        if (!$r  || ($r['url'] == '*' && $url && strpos(substr($url, -5), '.') === false) && substr($url, -1) !== '/') {
            $r2 = $this->dispatchRoutes($this->getRoutes(), $url.'/');
            if ($r2 && (!$r || $r2['url'] != '*')) {
                $this->system->getResponse()->redirect($this->system->getRootUrl().$url.'/', 301);
            }
        }
        // if route found and app exists
        if ($r && isset($r['app']) && $r['app'] && $this->system->appExists($r['app'])) {
            $this->setRoute($r);
            // dispatch app routes
            $params = waRequest::param();
            $u = $r['url'];
            if (preg_match_all('/<([a-z_]+):?([^>]*)?>/ui', $u, $match, PREG_OFFSET_CAPTURE|PREG_SET_ORDER)) {
                $offset = 0;
                foreach ($match as $m) {
                    $v = $m[1][0];
                    $s = isset($params[$v]) ? $params[$v] : '';
                    $u = substr($u, 0, $m[0][1] + $offset).$s.substr($u, $m[0][1] + $offset + strlen($m[0][0]));
                    $offset += strlen($s) - strlen($m[0][0]);
                }
            }
            $this->root_url = self::clearUrl($u);
            $url = substr($url, strlen($this->root_url));
            $this->dispatchRoutes($this->getAppRoutes($r['app'], $r, true), $url);
        }

        // Default routing via GET parameters
        if (waRequest::param('module') === null && ($module = waRequest::get('module'))) {
            waRequest::setParam('module', $module);
        }
        if (waRequest::param('action') === null && ($action = waRequest::get('action'))) {
            waRequest::setParam('action', $action);
        }
        if (waRequest::param('plugin') === null && ($plugin = waRequest::get('plugin'))) {
            waRequest::setParam('plugin', $plugin);
        }
        return $r;
    }

    public function getRouteParam($name)
    {
        if ($this->route && isset($this->route[$name])) {
            return $this->route[$name];
        }
        return null;
    }

    public function getCurrentUrl()
    {
        $url = $this->system->getConfig()->getRequestUrl();
        $url = preg_replace("!\?.*$!", '', $url);
        $url = urldecode($url);
        return substr($url, strlen($this->root_url));
    }


    /**
     * Returns routes for pages
     * @param string $app_id APP_ID
     * @return array
     */
    protected function getPageRoutes($app_id, $route = array())
    {
        static $_page_routes;

        if ($_page_routes === null || !isset($_page_routes[$app_id])) {
            $class = $app_id.'PageModel';
            /**
             * @var waPageModel $model
             */
            $model = new $class();
            $query = $model->select('id, full_url');

            $query->where("domain = ? AND route = ?", array(self::getDomain(null, true), $route['url']));

            if (!waRequest::get('preview')) {
                $query->where("status = 1");
            }
            $rows = $query->fetchAll();
            $page_routes = array();
            foreach ($rows as $row) {
                $page_routes[] = array(
                    'url' => $row['full_url'],
                    'module' => 'frontend',
                    'action' => 'page',
                    'page_id' => $row['id']
                );
            }
            $_page_routes[$app_id] = $page_routes;
        }
        return $_page_routes[$app_id];
    }


    protected function getAppRoutes($app, $route = array(), $dispatch = false)
    {
        $routes = waSystem::getInstance($app, null, $dispatch)->getConfig()->getRouting($route, $dispatch);
        $routes = $this->formatRoutes($routes, true);
        if ($dispatch && wa($app)->getConfig()->getInfo('pages') && $app != 'site') {
            $page_routes = $this->getPageRoutes($app, $route);
            if ($page_routes) {
                $routes = array_merge($page_routes, $routes);
            }
        }
        return $routes;
    }

    protected function dispatchRoutes($routes, $url)
    {
        $result = null;
        foreach ($routes as $r) {
            if ($this->route && isset($this->route['module']) &&
            (!isset($r['module']) || $r['module'] != $this->route['module'])) {
                continue;
            }
            $vars = array();
            $pattern = str_replace(array(' ', '.', '('), array('\s', '\.', '(?:'), $r['url']);
            $pattern = preg_replace('/(^|[^\.])\*/ui', '$1.*?', $pattern);
            if (preg_match_all('/<([a-z_]+):?([^>]*)?>/ui', $pattern, $match, PREG_OFFSET_CAPTURE|PREG_SET_ORDER)) {
                $offset = 0;
                foreach ($match as $m) {
                    $vars[] = $m[1][0];
                    if ($m[2][0]) {
                        $p = $m[2][0];
                    } else {
                        $p = '.*?';
                    }
                    $pattern = substr($pattern, 0, $offset + $m[0][1]).'('.$p.')'.substr($pattern, $offset + $m[0][1] + strlen($m[0][0]));
                    $offset = $offset + strlen($p) + 2 - strlen($m[0][0]);
                }
            }
            if (preg_match('!^'.$pattern.'$!ui', $url, $match)) {
                if (isset($r['redirect'])) {
                    $p = str_replace('.*?', '(.*?)', $pattern);
                    if ($p != $pattern) {
                        preg_match('!^'.$p.'$!ui', $url, $m);
                        if (isset($m[1])) {
                            $r['redirect'] = str_replace('*', $m[1], $r['redirect']);
                        }
                    }
                    wa()->getResponse()->redirect($r['redirect'], 301);
                }
                if ($vars) {
                    array_shift($match);
                    foreach ($vars as $i => $v) {
                        if (isset($match[$i]) && !waRequest::param($v)) {
                            waRequest::setParam($v, $match[$i]);
                        }
                    }
                }
                foreach ($r as $k => $v) {
                    if ($k !== 'url') {
                        waRequest::setParam($k, $v);
                    }
                }
                $result = $r;
                break;
            }
        }
        return $result;
    }


    public function getUrl($path, $params = array(), $absolute = false)
    {
        if (is_bool($params)) {
            $absolute = $params;
            $params = array();
        }
        $parts = explode('/', $path);
        $app = $parts[0];
        if (!$app) {
            $app = $this->system->getApp();
        }
        if (!wa()->appExists($app)) {
            return null;
        }
        if (isset($parts[1])) {
            $params['module'] = $parts[1];
        }
        if (isset($parts[2])) {
            $params['action'] = $parts[2];
        }
        $routes = array();
        if (!$this->route || $this->route['app'] != $app ||
        (!isset($this->route['module']) && isset($params['module']) && $params['module'] != 'frontend') ||
        (isset($this->route['module']) && isset($params['module']) && $this->route['module'] != $params['module'])
        ){
            // find base route
            if (isset($params['domain'])) {
                $routes[$params['domain']] = $this->getRoutes($params['domain']);
                unset($params['domain']);
            } else {
                $routes = $this->routes;
            }
            // filter by app and module
            foreach ($routes as $domain => $domain_routes) {
                foreach ($domain_routes as $r_id => $r) {
                    if (!isset($r['app']) ||
                    $r['app'] != $app ||
                    (isset($params['module']) && isset($r['module']) && $r['module'] != $params['module'])) {
                        unset($routes[$domain][$r_id]);
                    }
                }
                if (!$routes[$domain]) {
                    unset($routes[$domain]);
                }
            }
        } else {
            $routes[$this->getDomain()] = array($this->route);
        }
        $max = -1;
        $result = null;

        foreach ($routes as $domain => $domain_routes) {
            foreach ($domain_routes as $r) {
                $i = $this->countParams($r, $params);
                if (isset($params['module']) && isset($r['module'])) {
                    $i++;
                }
                if ($absolute || $this->getDomain() != $domain) {
                    $root_url = self::getUrlByRoute($r, $domain);
                } else {
                    $root_url = $this->system->getRootUrl(false, true).self::clearUrl($r['url']);
                }
                if ($i > $max) {
                    $max = $i;
                    $result = $root_url;
                }
                $app_routes = $this->getAppRoutes($r['app'], $r);
                foreach ($app_routes as $app_r) {
                    $j = $i + $this->countParams($app_r, $params);
                    if (!isset($params['action']) && !isset($app_r['action'])) {
                        $j++;
                    }
                    $u = $app_r['url'];
                    if (preg_match_all('/<([a-z_]+):?([^>]*)?>/ui', $u, $match, PREG_OFFSET_CAPTURE|PREG_SET_ORDER)) {
                        $offset = 0;
                        foreach ($match as $m) {
                            $v = $m[1][0];
                            if (isset($params[$v])) {
                                $u = substr($u, 0, $m[0][1] + $offset).$params[$v].substr($u, $m[0][1] + $offset + strlen($m[0][0]));
                                $offset += strlen($params[$v]) - strlen($m[0][0]);
                                $j++;
                            } else {
                                if (substr($u, $m[0][1] - 1, 1) === '(' &&
                                substr($u, $m[0][1] + strlen($m[0][0]), 3) === '/)?') {
                                    $u = substr($u, 0, $m[0][1] - 1).substr($u, $m[0][1] + strlen($m[0][0]) + 3);
                                } else {
                                    continue 2;
                                }
                            }
                        }
                    }
                    if ($j >= $max || $result === null) {
                        if ($j == $max && $this->getDomain() && $domain != $this->getDomain() && $result) {
                        } else {
                            $max = $j;
                            $u = self::clearUrl($u);
                            if (substr($result, -1) == '/' && substr($u, 0, 1) == '/') {
                                $u = substr($u, 1);
                            }
                            $result = $root_url.$u;
                        }
                    }
                }
            }
        }
        return $result;
    }

    protected function countParams($r, $params)
    {
        $n = 0;
        foreach ($params as $key => $value) {
            if (isset($r[$key]) && $r[$key] == $value) {
                $n++;
            }
        }
        return $n;
    }

    /**
     * @static
     * @param array $route
     * @param string $domain
     * @return string
     */
    public static function getUrlByRoute($route, $domain = null)
    {
        $url = self::clearUrl($route['url']);
        if ($domain) {
            $https = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : '';
            return 'http'.(strtolower($https) == 'on' ? 's' : '').'://'.self::getDomainUrl($domain).'/'.$url;
        }
        return $url;
    }

    public static function clearUrl($url)
    {
        $url = preg_replace('/\.?\*$/i', '', $url);
        $url = str_replace('/?', '/', $url);
        return $url;
    }

    public static function getDomainUrl($domain, $absolute = true)
    {
        $u1 = rtrim(wa()->getRootUrl(false, false), '/');
        $u2 = rtrim(wa()->getRootUrl(false, true), '/');
        $domain_parts = @parse_url('http://'.$domain);
        $u = isset($domain_parts['path']) ? $domain_parts['path'] : '';
        if ($u1 != $u2 && substr($u, 0, strlen($u1)) == $u1) {
            $u = $u2.substr($u, strlen($u1));
        }
        if ($absolute) {
            return $domain_parts['host'].(isset($domain_parts['port']) ? ':'.$domain_parts['port'] : '').$u;
        }
        return $u;
    }
}
