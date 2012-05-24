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
 * @subpackage view
 */
class waViewHelper
{
    /**
     * @var waSystem
     */
    protected $wa;
    protected $view;
    protected $version;
    protected static $helpers = array();
    protected static $params = array();

    public function __construct(waView $view)
    {
        $this->view = $view;
        $this->wa = wa();
    }

    public function header()
    {
        return wa_header();
    }

    public function app()
    {
        return $this->wa->getApp();
    }

    public function apps()
    {
        if ($this->wa->getEnv() == 'frontend') {
            $domain = $this->wa->getRouting()->getDomain(null, true);
            $domain_config_path = $this->wa->getConfig()->getConfigPath('domains/'.$domain.'.php', true, 'site');
            if (file_exists($domain_config_path)) {
                /**
                 * @var $domain_config array
                 */
                $domain_config = include($domain_config_path);
                if (isset($domain_config['apps']) && $domain_config['apps']) {
                    return $domain_config['apps'];
                }
                return $this->wa->getFrontendApps($domain, isset($domain_config['name']) ? $domain_config['name'] : null, true);
            } else {
                return $this->wa->getFrontendApps($domain, null, true);
            }
        } else {
            return $this->wa->getUser()->getApps();
        }
    }

    public function isAuthEnabled()
    {
        $domain = $this->wa->getRouting()->getDomain();
        $domain_config_path = $this->wa->getConfig()->getConfigPath('domains/'.$domain.'.php', true, 'site');
        if (file_exists($domain_config_path)) {
            $domain_config = include($domain_config_path);
            if (isset($domain_config['auth_enabled']) && $domain_config['auth_enabled']) {
                return $domain_config['auth_enabled'];
            }
        }
        return false;
    }


    public function user($field=null, $format='html')
    {
        $user = $this->wa->getUser();
        if ($field !== null) {
            return $user->get($field, $format);
        } else {
            return $user;
        }
    }

    public function locale()
    {
        return $this->wa->getLocale();
    }

    public function userLocale()
    {
        return $this->locale();
    }

    public function appName($escape = true)
    {
        $app_info = $this->wa->getAppInfo();
        $name = $app_info['name'];
        return $escape ? htmlspecialchars($name) : $name;
    }

    public function accountName($escape = true)
    {
        $app_settings_model = new waAppSettingsModel();
        $name = $app_settings_model->get('webasyst', 'name', 'Webasyst');
        return $escape ? htmlspecialchars($name) : $name;
    }

    public function module($default = null)
    {
        return waRequest::get('module', $default);
    }

    public function css()
    {
        return '<link href="'.$this->wa->getRootUrl().'wa-content/css/wa/wa-1.0.css?v'.$this->version(true).'" rel="stylesheet" type="text/css" >
<!--[if IE 8]><link type="text/css" href="'.$this->wa->getRootUrl().'wa-content/css/wa/wa-1.0.ie8.css" rel="stylesheet"><![endif]-->
<!--[if IE 7]><link type="text/css" href="'.$this->wa->getRootUrl().'wa-content/css/wa/wa-1.0.ie7.css" rel="stylesheet"><![endif]-->'.
        $this->wa->getResponse()->getCss(true);
    }

    public function js($include_jquery = true)
    {
        return ($include_jquery ?
            '<script src="'.$this->wa->getRootUrl().'wa-content/js/jquery/jquery-1.5.2.min.js" type="text/javascript"></script>' :
            '').$this->wa->getResponse()->getJs(true);
    }

    public function version($system = false)
    {
        if ($system) {
            $app_info = $this->wa->getAppInfo('webasyst');
            return isset($app_info['version']) ? $app_info['version'] : '0.0.1';
        } else {
            if ($this->version === null) {
                $app_info = $this->wa->getAppInfo();
                $this->version = isset($app_info['version']) ? $app_info['version'] : '0.0.1';
                if (SystemConfig::isDebug()) {
                    $this->version .= ".".time();
                } elseif (!$system) {
                    $file = $this->wa->getAppPath('lib/config/build.php');
                    if (file_exists($file)) {
                        $build = include($file);
                        $this->version .= '.'.$build;
                    }
                }
            }
            return $this->version;
        }
    }

    public function get($name)
    {
        return waRequest::get($name);
    }

    public function server($name)
    {
        return waRequest::server($name);
    }

    public function post($name, $default = null)
    {
        return waRequest::post($name, $default);
    }

    public function request($name)
    {
        return waRequest::request($name);
    }

    public function url($absolute = false)
    {
        return $this->wa->getRootUrl($absolute);
    }

    public function domainUrl()
    {
        return $this->wa->getConfig()->getHostUrl();
    }

    public function currentUrl($absolute = false)
    {
        $url = $this->wa->getConfig()->getCurrentUrl();
        if ($absolute) {
            return $this->domainUrl().$url;
        } else {
            return $url;
        }
    }

    public function getUrl($route, $params = array(), $absolute = false)
    {
        return $this->wa->getRouteUrl($route, $params, $absolute);
    }

    public function contacts($hash = null, $fields = 'id,name')
    {
        $collection = new waContactsCollection($hash, array('check_rights' => false));
        return $collection->getContacts($fields);
    }

    public function contact($id)
    {
        if (!is_numeric($id)) {
            $collection = new waContactsCollection('/search/'.$id.'/', array('check_rights' => false));
            $result = $collection->getContacts('id', 0, 1);
            if ($result) {
                $c = current($result);
                return new waContact($c['id']);
            } else {
                return new waContact();
            }
        }
        return new waContact($id);
    }

    public function title($title = null)
    {
        if (!$title) {
            return $this->wa->getResponse()->getTitle();
        } else {
            return $this->wa->getResponse()->setTitle($title);
        }
    }

    public function isMobile()
    {
        return waRequest::isMobile();
    }


    public function userAgent($type = null)
    {
        $user_agent = waRequest::server('HTTP_USER_AGENT');

        if (!$type) {
            return $user_agent;
        } elseif ($type == 'isMobile') {
            return waRequest::isMobile(false);
        } elseif ($type == 'platform' || $type == 'os') {
            $patterns = array(
                'android' => 'android',
                'blackberry' => 'blackberry',
                'linux' => 'Linux',
                'ios' => '(ipad|iphone|ipod)',
                'mac' => '(Macintosh|Mac\sOS)',
                'windows' => 'Windows',
            );
        } elseif ($type == 'device') {
            $patterns = array(
                'ipad' => 'ipad',
                'ipod' => 'ipod',
                'iphone' => 'iphone',
                'android' => 'android'
            );
        }
        foreach ($patterns as $id => $pattern) {
            if (preg_match('/'.$pattern.'/i', $user_agent)) {
                return $id;
            }
        }
        return '';
    }

    /**
     * @param string|array $key
     * @param string|null $value
     * @return string|void
     */
    public function globals($key, $value = null)
    {
        if (func_num_args() == 1) {
            if (is_array($key)) {
                foreach ($key as $k => $v) {
                    self::$params[$k] = $v;
                }
            } else {
                return isset(self::$params[$key]) ? self::$params[$key] : null;
            }
        } elseif (func_num_args() == 2) {
            self::$params[$key] = $value;
        }
    }

    public function getEnv()
    {
        return $this->wa->getEnv();
    }

    public function snippet($id)
    {
        if ($id &&  $this->wa->appExists('site')) {
            wa('site');
            $model = new siteSnippetModel();
            $snippet = $model->getById($id);

            if ($snippet) {
                //$cache_id = $this->view->getCacheId();
                return $this->view->fetch('string:'.$snippet['content']);//, $cache_id ? $cache_id : null);
            }
        }
        return '';
    }

    public function csrf()
    {
        return '<input type="hidden" name="_csrf" value="'.waRequest::cookie('_csrf', '').'" />';
    }

    public function captcha()
    {
        return $this->wa->getCaptcha()->getHtml();
    }

    public function captchaUrl($add_random = true)
    {
        return $this->url().$this->app().'/captcha.php'.($add_random ? '?v='.uniqid(time()) : '');
    }

    public function __get($app)
    {
        if (!isset(self::$helpers[$app])) {
            if ($this->app() !== $app) {
                if (wa()->appExists($app)) {
                    wa($app);
                } else {
                    return null;
                }
            }
            $class = $app.'ViewHelper';
            if (class_exists($class)) {
                self::$helpers[$app] = new $class($this->wa);
            } else {
                self::$helpers[$app] = null;
            }
        }
        return self::$helpers[$app];
    }
}