<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage routing
 */
class waRouting
{
    protected static $instance;
    protected $routes;
    /**
     * @var waSystem
     */
    protected $system;
    protected $root_url;

    public function __construct(waSystem $system)
    {
        $this->system = $system;
        $this->routes = $system->getConfig()->getRouting();
    }


    public function getUrl($route, $params = array(), $absolute = false)
    {
        $exists = false;
        $root_url = $this->system->getRootUrl($absolute, true);
        foreach ($this->routes as $domain => $routes) {
            if (isset($routes[$route])) {
                if (isset($params['domain'])) {
                    if ($params['domain'] == $domain) {
                        $exists = true;
                        $root_url = 'http://'.$domain.'/';
                        break;    
                    }
                } elseif ($domain !== 'default') {
                    $exists = true;
                    $root_url = 'http://'.$domain.'/';
                    break;
                }
                
            }
        }
        if (!$exists) {
            throw new waException('Route not found');
        }

        $url = $routes[$route]['url'];
        $offset = 0;
        if (preg_match_all('/\[([i|s]?:[a-z_]+)\]/ui', $url, $match, PREG_OFFSET_CAPTURE|PREG_SET_ORDER)) {
            foreach ($match as $m) {
                $var = explode(':', $m[1][0]);
                if (isset($params[$var[1]])) {
                    $v = $params[$var[1]];
                } else {
                    $v = '';
                }
                $url = substr($url, 0, $m[0][1] + $offset).$v.substr($url, $m[0][1] + $offset + strlen($m[0][0]));
                $offset = strlen($v) - strlen($m[0][0]);
            }
        } 
        foreach ($params as $k => $v) {
            //$url = preg_replace("!\[[(i|s)]?:[a-z]+\]!ui", $v, $url);
        }
        $url = preg_replace('!(/{2,}|/?\*)$!i', '/', $url);
        if ($url == '/') {
            $url = '';
        }
        return $root_url.$url;
    }

    public function getRootUrl()
    {
        return $this->root_url;
    }

    public function getRoutes($domain = null)
    {
        if ($domain === null) {
            $domain = $this->getDomain();
        }
        $u = $domain.'/'.$this->system->getConfig()->getRequestUrl(true);
        foreach ($this->routes as $d => $a) {
            if (strpos($u, $d) === 0) {
                return $this->routes[$d];
            }
        }
        if (isset($this->routes[$domain])) {
            return $this->routes[$domain];
        } elseif (isset($this->routes['default']) && $this->routes['default']) {
            return $this->routes['default'];
        }
        return array();
    }
    
    public function getDomain()
    {
        $domain = waRequest::server('HTTP_HOST');
        $u = trim($this->system->getRootUrl(false, true), '/');
        if ($u) {
            $domain .= '/'.$u;
        }
        return $domain;        
    }

    public function dispatch($route = array(), $add_slash = false)
    {
        $routes = $this->getRoutes();
        if ($route && !$routes) {
            return array();
        }
        $url = $this->system->getConfig()->getRequestUrl();
        $url = preg_replace("!\?.*$!", '', $url);
        if ($route) {
            $this->root_url = preg_replace("/^([^?*\[]*).*$/is", "$1", $route['url']);
            $url = substr($url, strlen($this->root_url));
        }
        if ($add_slash && (substr($url, -1) !== '/')) {
            $url .= '/';
        }
        $found = false;
        foreach ($routes as $r) {
            $vars = array();
            $pattern = str_replace(array(' ', '.', '*'), array('\s', '\.', '.*?'), $r['url']);
            if (preg_match_all('/\[([i|s]?):([a-z_]+)\]/ui', $r['url'], $match, PREG_OFFSET_CAPTURE|PREG_SET_ORDER)) {
                $offset = 0;
                foreach ($match as $m) {
                    $vars[] = $m[2][0];
                    if ($route && (waRequest::param($m[2][0]) !== null)) {
                        return false;
                    }
                    switch ($m[1][0]) {
                        case 'i':
                            $p = '[0-9]+';
                            break;
                        case 's':
                            $p = '.*?';
                            break;
                        default:
                            if (isset($r[':'.$m[2][0]])) {
                                $p = $r[':'.$m[2][0]];
                                unset($r[':'.$m[2][0]]);
                            } else {
                                $p = '.*?';
                            }
                    }
                    $pattern = substr($pattern, 0, $offset + $m[0][1]).'('.$p.')'.substr($pattern, $offset + $m[0][1] + strlen($m[0][0]));
                    $offset = $offset + strlen($p) + 2 - strlen($m[0][0]);
                }
            }
            if (preg_match('!^'.$pattern.'$!ui', $url, $match)) {
                if (isset($r['redirect'])) {
                    header("Location: ".$r['redirect']);
                    exit;
                }
                if ($vars) {
                    array_shift($match);
                    foreach ($vars as $i => $v) {
                        if (isset($match[$i])) {
                            waRequest::setParam($v, $match[$i]);
                        }
                    }
                }
                foreach ($r as $k => $v) {
                    if ($k !== 'url') {
                        waRequest::setParam($k, $v);
                    }
                }
                $found = true;
                break;

            }

        }
        if (!$found && !$add_slash && (substr($url, -1) !== '/')) {
            return $this->dispatch($route, true);
        }
        
        if ($found && $add_slash) {
            $this->system->getResponse()->redirect($this->system->getRootUrl().$url);
        }

        $r = $found ? $r: array();

        // Default routing via GET parameters
        if (waRequest::param('module') === null) {
            if ($module = waRequest::get('module')) {
                waRequest::setParam('module', $module);
            }
        }

        if (waRequest::param('action') === null) {
            if ($action = waRequest::get('action')) {
                waRequest::setParam('action', $action);
            }
        }

        if (waRequest::param('plugin') === null) {
            if ($plugin = waRequest::get('plugin')) {
                waRequest::setParam('plugin', $plugin);
            }
        }
        return $r;
    }
}