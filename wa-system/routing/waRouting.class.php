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
    protected $url_placeholders = array();

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

    public function isAlias($domain)
    {
        return isset($this->aliases[$domain]) ? $this->aliases[$domain] : false;
    }

    /**
     * @return array[string] mirror domain => actual domain mirror points to
     * @since 1.14.5
     */
    public function getAliases()
    {
        return $this->aliases;
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

    protected function formatRoutes($routes, $app_id = false)
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
                if ($app_id) {
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
            if ($app_id && empty($r['app'])) {
                $r['app'] = $app_id;
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

    public function getDomain($domain = null, $check = false, $return_alias = true)
    {
        if ($domain) {
            return $domain;
        }
        if ($this->domain === null || !$return_alias) {
            $this->domain = $this->getDomainNoAlias();
            if ($this->domain === null) {
                return null;
            }
        }

        if ($check && !isset($this->routes[$this->domain])) {
            if (substr($this->domain, 0, 4) == 'www.') {
                $domain = substr($this->domain, 4);
            } else {
                $domain = 'www.'.$this->domain;
            }
            if (isset($this->routes[$domain])) {
                $this->domain = $domain;
                if (wa()->getEnv() == 'frontend' && !waRequest::param('no_domain_www_redirect')) {
                    $url = 'http'.(waRequest::isHttps()? 's' : '').'://';
                    $url .= $this->getDomainUrl($domain).'/'.wa()->getConfig()->getRequestUrl();
                    wa()->getResponse()->redirect($url, 301);
                }
            }
            return $this->domain;
        }

        if ($return_alias && isset($this->aliases[$this->domain])) {
            $this->domain = $this->aliases[$this->domain];
        }

        return $this->domain;
    }

    protected function getDomainNoAlias()
    {
        $domain = waRequest::server('HTTP_HOST');
        if (!$domain) {
            return null;
        }
        $pos = strpos($domain, ':');
        if ($pos !== false) {
            $port = substr($domain, $pos + 1);
            if ($port == '80' || $port === '443') {
                $domain = substr($domain, 0, $pos);
            }
        }
        $u = trim($this->system->getRootUrl(), '/');
        if ($u) {
            $domain .= '/'.$u;
        }
        return $domain;
    }

    public function getRootUrl()
    {
        return $this->root_url;
    }

    public function dispatch()
    {
        $url = $this->system->getConfig()->getRequestUrl(true, true);
        $decoded_url = urldecode($url);
        if (mb_check_encoding($decoded_url, 'UTF-8')) {
            $url = $decoded_url;
        }
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
                    $s = (isset($params[$v]) && $v != 'url') ? $params[$v] : '';
                    $u = substr($u, 0, $m[0][1] + $offset).$s.substr($u, $m[0][1] + $offset + strlen($m[0][0]));
                    $offset += strlen($s) - strlen($m[0][0]);
                }
            }
            $this->root_url = self::clearUrl($u);
            $url = isset($params['url']) ? $params['url'] : substr($url, strlen($this->root_url));
            $this->dispatchRoutes($this->getAppRoutes($r['app'], $r, true), $url);
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

            if ($cache = wa($app_id)->getCache()) {
                $cache_key = 'urls/'.self::getDomain(null, true). '/'. self::clearUrl($route['url']);
                $rows = $cache->get($cache_key, 'pages');
            }

            if (!$cache || $rows === null) {
                $class = $app_id . 'PageModel';
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
                if ($cache) {
                    $cache->set($cache_key, $rows, 3600, 'pages');
                }
            }

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
        if (!$dispatch) {
            $cache_key = md5(serialize($route));
            $cache = new waRuntimeCache('approutes/'.$app.'/'.$cache_key, -1, 'webasyst');
            if ($cache->isCached()) {
                return $cache->get();
            }
        }
        $routes = waSystem::getInstance($app, null, $dispatch)->getConfig()->getRouting($route, $dispatch);
        $routes = $this->formatRoutes($routes, $app);
        if ($dispatch && wa($app)->getConfig()->getInfo('pages') && $app != 'site') {
            $page_routes = $this->getPageRoutes($app, $route);
            if ($page_routes) {
                $routes = array_merge($page_routes, $routes);
            }
        } else if (isset($cache)) {
            $cache->set($routes);
        }
        return $routes;
    }

    public function getRuleForUrl($routes, $url) {
        $result = null;
        foreach ($routes as $r) {
            if ($this->route && isset($this->route['module']) &&
                (!isset($r['module']) || $r['module'] != $this->route['module'])) {
                continue;
            }
            $pattern = str_replace(array(' ', '.', '('), array('\s', '\.', '(?:'), ifset($r, 'url', ''));
            $pattern = preg_replace('/(^|[^\.])\*/ui', '$1.*?', $pattern);
            if (preg_match_all('/<([a-z_]+):?([^>]*)?>/ui', $pattern, $match, PREG_OFFSET_CAPTURE|PREG_SET_ORDER)) {
                $offset = 0;
                foreach ($match as $m) {
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
                $result = $r;
                break;
            }
        }
        return $result;
    }

    protected function dispatchRoutes($routes, $url)
    {
        $result = null;
        foreach ($routes as $r) {
            if (($this->route && isset($this->route['module']) &&
                (!isset($r['module']) || $r['module'] != $this->route['module']))
                || (isset($r['redirect']) && !empty($r['disabled']))
            ) {
                continue;
            }
            $vars = array();
            $pattern = str_replace(array(' ', '.', '('), array('\s', '\.', '(?:'), ifset($r, 'url', ''));
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
                if (isset($r['redirect']) && empty($r['disabled'])) {
                    $p = str_replace('.*?', '(.*?)', $pattern);
                    if ($p != $pattern) {
                        preg_match('!^'.$p.'$!ui', $url, $m);
                        if (isset($m[1])) {
                            $r['redirect'] = str_replace('*', $m[1], $r['redirect']);
                            if (waRequest::server('QUERY_STRING')) {
                                $r['redirect'] .= '?'.waRequest::server('QUERY_STRING');
                            }
                        }
                    }
                    $redirect_code = (!empty($r['code']) && $r['code'] == 302) ? 302 : 301;
                    wa()->getResponse()->redirect($r['redirect'], $redirect_code);
                } elseif (isset($r['static_content'])) {
                    $response = wa()->getResponse();
                    switch (ifset($r['static_content_type'])){
                        case 'text/plain':
                            $response->addHeader('Content-Type', 'text/plain; charset=utf-8');
                            break;
                        case 'text/html':
                            $response->addHeader('Content-Type', 'text/html; charset=utf-8');
                            break;
                        default:
                            if ($type = waFiles::getMimeType($r['url'])) {
                                $response->addHeader('Content-Type', $type);
                            }

                            break;
                    }
                    $response->sendHeaders();
                    print $r['static_content'];
                    exit;
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

    /**
     * @param string $path
     * @param array $params
     * @param bool $absolute
     * @param string $domain_url
     * @param string $route_url
     * @return string
     */
    public function getUrl($path, $params = array(), $absolute = false, $domain_url = null, $route_url = null)
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

        if (!$this->appExists($app)) {
            return null;
        }

        if (isset($parts[1])) {
            $params['module'] = $parts[1];
        }

        if (isset($parts[2])) {
            $params['action'] = $parts[2];
        }

        $routes = array();
        $current_domain = $this->getDomain();

        $is_set_route = $this->route && is_array($this->route);
        $is_current_route = true;
        $is_different_app = false;
        $is_different_domain = $is_set_route && $domain_url && $domain_url != $current_domain;
        $is_different_route_url = false;
        $is_different_module = false;

        if ($is_set_route) {
            foreach ($params as $k => $v) {
                if ($k != 'url' && isset($this->route[$k]) && $this->route[$k] != $v) {
                    $is_current_route = false;
                    break;
                }
            }

            $is_different_app = $this->route['app'] != $app;
            $is_different_route_url = $route_url && $this->route['url'] != $route_url;
            $is_different_module = isset($this->route['module']) && isset($params['module'])
                && $this->route['module'] != $params['module'];
        }

        $is_current_route = $is_current_route && !$is_different_app
            && !$is_different_domain && !$is_different_route_url && !$is_different_module;

        if (!$is_set_route || !$is_current_route) {
            if (isset($params['domain']) && !$domain_url) {
                $domain_url = $params['domain'];
                unset($params['domain']);
            }

            if ($domain_url) {
                $routes[$domain_url] = $this->getRoutes($domain_url);
            } else {
                $routes = $this->routes;
            }

            foreach ($routes as $domain => $domain_routes) {
                foreach ($domain_routes as $i => $route) {
                    $is_different_app = !isset($route['app']) || $route['app'] != $app;
                    $is_different_route_url = $domain_url && $route_url && $route['url'] != $route_url;
                    $is_different_module = isset($params['module']) && isset($route['module'])
                        && $route['module'] != $params['module'];

                    if ($is_different_app || $is_different_route_url || $is_different_module) {
                        unset($routes[$domain][$i]);
                    }
                }

                if (!$routes[$domain]) {
                    unset($routes[$domain]);
                }
            }
        } else {
            $routes[$current_domain] = array($this->route);
        }

        $max = -1;
        $result = null;

        foreach ($routes as $domain => $domain_routes) {
            foreach ($domain_routes as $route) {
                $route_score = $this->countParams($route, $params);

                if (isset($params['module']) && isset($route['module'])) {
                    $route_score++;
                }

                if ($route_score > $max) {
                    $max = $route_score;
                    $result = array(
                        'domain' => $domain,
                        'route' => $route,
                        'params' => $params,
                        'app_route' => null,
                        'url_placeholders' => null,
                    );
                }

                $app_routes = $this->getAppRoutes($route['app'], $route);

                foreach ($app_routes as $app_route) {
                    $app_route_score = $route_score + $this->countParams($app_route, $params);

                    if (empty($params['action']) && empty($app_route['action'])) {
                        $app_route_score++;
                    }

                    $url = $app_route['url'];
                    $url_placeholders = $this->getUrlPlaceholders($url);

                    foreach ($url_placeholders as $url_placeholder) {
                        $k = $url_placeholder['key'];
                        $is_optional = $url_placeholder['is_optional'];

                        if (isset($params[$k])) {
                            $app_route_score++;
                        } elseif (!$is_optional) {
                            continue 2;
                        }
                    }

                    $is_preferred_domain = !$current_domain || $domain == $current_domain;
                    $is_overflow_max = $app_route_score > $max || ($app_route_score == $max && $is_preferred_domain);

                    if ($is_overflow_max || !isset($result)) {
                        $max = $app_route_score;
                        $result = array(
                            'domain' => $domain,
                            'route' => $route,
                            'params' => $params,
                            'app_route' => $app_route,
                            'url_placeholders' => $url_placeholders,
                        );
                    }
                }
            }
        }

        $result_url = null;

        if (isset($result)) {
            if ($absolute &&                                // When asked for an absolute URL
                $current_domain == $result['domain'] &&     // to current domain,
                $current_domain != $domain_url &&           // and did not specifically ask for original domain,
                $this->aliases                              // and there's possibility we're on a mirror (alias) domain
            ) {
                // if we are indeed on a mirror domain, then use full url to mirror instead of original domain.
                $alias_domain = $this->getDomainNoAlias();
                if (ifset($this->aliases, $alias_domain, null) == $current_domain) {
                    $result['domain'] = $alias_domain;
                }
            }
            $result_url = self::getUrlByRoute($result['route'], $result['domain'], ($absolute || $current_domain != $result['domain']));
            $result_url = preg_replace('/<url.*?>/i', '', $result_url);

            if (isset($result['app_route'])) {
                $url = $result['app_route']['url'];
                $url_placeholders = $result['url_placeholders'];
                $params = $result['params'];
                $offset = 0;

                foreach ($url_placeholders as $url_placeholder) {
                    $k = $url_placeholder['key'];
                    $start = $url_placeholder['start'];
                    $length = $url_placeholder['length'];

                    if (isset($params[$k])) {
                        $url = substr($url, 0, $start + $offset)
                            . $params[$k]
                            . substr($url, $start + $offset + $length);
                        $offset += strlen($params[$k]) - $length;
                    } else {
                        $url = substr($url, 0, $start - 1)
                            . substr($url, $start + $length + 3);
                    }
                }

                $url = self::clearUrl($url);

                if (substr($result_url, -1) == '/' && substr($url, 0, 1) == '/') {
                    $url = substr($url, 1);
                }

                $result_url = $result_url . $url;
            }
        }

        return $result_url;
    }

    /**
     * @since 1.14.13
     */
    protected function appExists($app_id)
    {
        // overriden in unit tests
        return wa()->appExists($app_id);
    }

    protected function getUrlPlaceholders($url) {
        if (!isset($this->url_placeholders[$url])) {
            if (preg_match_all(
                '/<([a-z_]+):?([^>]*)?>/ui',
                $url, $match, PREG_OFFSET_CAPTURE|PREG_SET_ORDER
            )) {
                $url_placeholders = array();

                foreach ($match as $m) {
                    $k = $m[1][0];
                    $start = $m[0][1];
                    $length = strlen($m[0][0]);
                    $is_optional = substr($url, $start - 1, 1) === '('
                        && substr($url, $start + $length, 3) === '/)?';
                    $url_placeholders[] = array(
                        'key' => $k,
                        'start' => $start,
                        'length' => $length,
                        'is_optional' => $is_optional,
                    );
                }

                $this->url_placeholders[$url] = $url_placeholders;
            } else {
                $this->url_placeholders[$url] = array();
            }
        }

        return $this->url_placeholders[$url];
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
    public static function getUrlByRoute($route, $domain = null, $absolute = true)
    {
        $result = self::clearUrl($route['url']);
        if ($domain) {
            $result = self::getDomainUrl($domain, $absolute).'/'.$result;
            if ($absolute) {
                if (!empty($route['ssl_all'])) {
                    $https = true;
                } elseif (parse_url('http://'.$domain, PHP_URL_HOST) == waRequest::server('HTTP_HOST')) {
                    $https = waRequest::isHttps();
                } else {
                    $https = false;
                }
                $result = 'http'.($https ? 's' : '').'://'.$result;
            }
        }
        return $result;
    }

    public static function clearUrl($url)
    {
        return preg_replace('~(?<=/)\?|\.?\*$~i', '', $url);
    }

    public static function getDomainUrl($domain, $absolute = true)
    {
        $domain_parts = @parse_url('http://'.$domain);
        $result = isset($domain_parts['path']) ? $domain_parts['path'] : '';
        if (!waSystemConfig::systemOption('mod_rewrite')) {
            // without /index.php/
            $root_url_static = rtrim(wa()->getRootUrl(false, false), '/');

            // with /index.php/
            $root_url_script = rtrim(wa()->getRootUrl(false, true), '/');

            // Add /index.php/ to result in appropriate place
            if (substr($result, 0, strlen($root_url_static)) == $root_url_static) {
                $result = $root_url_script.substr($result, strlen($root_url_static));
            }
        }

        if ($absolute) {
            $host = $domain_parts['host'].(isset($domain_parts['port']) ? ':'.$domain_parts['port'] : '');
            return $host.$result;
        } else {
            return $result;
        }
    }

    public static function getDomainConfig($name=null, $domain=null)
    {
        static $domain_configs = array();

        if ($domain === null) {
            $domain = wa()->getRouting()->getDomain(null, true);
        }
        if (!$domain || false !== strpos($domain, '..')) {
            return $name === null ? array() : null;
        }

        if (!isset($domain_configs[$domain])) {
            $domain = waIdna::enc($domain);
        }
        if (!isset($domain_configs[$domain])) {
            $domain_configs[$domain] = array();
            $domain_config_path = wa()->getConfig()->getConfigPath('domains/' . $domain . '.php', true, 'site');
            if (file_exists($domain_config_path)) {
                $domain_configs[$domain] = include($domain_config_path);
            }
        }

        if ($name === null) {
            return $domain_configs[$domain];
        } else if (isset($domain_configs[$domain][$name])) {
            return $domain_configs[$domain][$name];
        } else {
            return null;
        }
    }
}
