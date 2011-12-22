<?php

class waRouting
{
    /**
     * @var waSystem
     */
    protected $system;
    protected $routes;
    protected $domain;
    protected $route;
    protected $root_url;
    
    public function __construct(waSystem $system, $routes = array())
    {
    	$this->system = $system;
    	if (!$routes) {
    	    $routes = $this->system->getConfig()->getConfigFile('routing');
    	}
    	$this->setRoutes($routes);
    }
    
    public function setRoutes($routes) 
    {       
        foreach ($routes as $domain => $domain_routes) {
        	$this->routes[$domain] = $this->formatRoutes($domain_routes, false);
        }
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
                return $domain;
            }
        }
        return $this->domain;
    }
    
    public function getRootUrl()
    {
    	return $this->root_url;
    }
    
    public function dispatch()
    {
        $url = $this->system->getConfig()->getRequestUrl();
        $url = preg_replace("!\?.*$!", '', $url);
        $url = urldecode($url);
        $r = $this->dispatchRoutes($this->getRoutes(), $url);
        if (!$r && substr($url, -1) !== '/') {
            $url .= '/';
            if ($r = $this->dispatchRoutes($this->getRoutes(), $url)) {
                $this->system->getResponse()->redirect($this->system->getRootUrl().$url);
            }
        }
        // if route found and app exists
        if ($r && isset($r['app']) && $r['app'] && $this->system->appExists($r['app'])) {
            $this->route = $r;
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
            $this->dispatchRoutes($this->getAppRoutes($r['app'], $r), $url);
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
    
    protected function getAppRoutes($app, $route = array())
    {
        $routes = wa($app)->getConfig()->getRouting($route);
        return $this->formatRoutes($routes, true);
    }
        
    protected function dispatchRoutes($routes, $url)
    {
        $result = null;
    	foreach ($routes as $r_id => $r) {
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
    				header("Location: ".$r['redirect']);
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
    	return $result;
    }
    
    
    public function getUrl($path, $params = array(), $absolute = false)
    {
        if (is_bool($params)) {
        	$all = $absolute;
        	$absolute = $params;
        	$params = array();
        }
        $parts = explode('/', $path);
        $app = $parts[0];
        if (!$app) {
            $app = $this->system->getApp();
        }
        if (isset($parts[1])) {
            $params['module'] = $parts[1];
        }
        if (isset($parts[2])) {
        	$params['action'] = $parts[2];
        }
        $routes = array();
        if (!$this->route || $this->route['app'] != $app ||
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
        $pattern = '/<([a-z_]+):?([^>]*)?>/ui';
        
        foreach ($routes as $domain => $domain_routes) {
            foreach ($domain_routes as $r) {
                $i = $this->countParams($r, $params);
                if ($absolute || $this->getDomain() != $domain) {
                	$root_url = self::getUrlByRoute($r, $domain);
                } else {
                	$root_url = $this->system->getRootUrl().self::clearUrl($r['url']);
                }
                if ($i > $max) {
                    $max = $i;
                    $result = $root_url;
                }
                $app_routes = $this->getAppRoutes($r['app'], $r);
                foreach ($app_routes as $app_r) {
                    $j = $i + $this->countParams($app_r, $params);
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
                    			continue 2;
                    		}
                    	}
                    }
                    if ($j >= $max || $result === null) {
                        if ($j == $max && $this->getDomain() && $domain != $this->getDomain() && $result) {
                        } else {
                            $max = $j;
                            $result = $root_url.self::clearUrl($u);
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
        
    public static function getUrlByRoute($route, $domain = false)
    {
    	$url = $route['url'];
    	return ($domain ? 'http://'.self::getDomainUrl($domain).'/' : '') .self::clearUrl($route['url']);
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
    	return ($absolute ? $domain_parts['host'] : '').$u;
    }    
}