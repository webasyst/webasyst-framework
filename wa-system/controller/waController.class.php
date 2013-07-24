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
 * @subpackage controller
 */
abstract class waController
{

    public function run($params = null)
    {
        $this->preExecute();
        $this->execute();
    }

    protected function preExecute()
    {
        // nothing to do.. can be redefined in subclasses
    }

    /**
     *
     * @return waAuthUser
     */
    public function getUser()
    {
        return waSystem::getInstance()->getUser();
    }

    public function getApp()
    {
        return $this->getConfig()->getApplication();
    }

    public function getAppId()
    {
        return $this->getConfig()->getApplication();
    }


    public function getRights($name = null, $assoc = true)
    {
        return $this->getUser()->getRights($this->getApp(), $name, $assoc);
    }

    /**
     * Add record to log
     *
     * @param string $action
     * @param int $count
     * @param int $contact_id
     * @param mixed $params
     * @return bool|int
     */
    public function log($action, $count = null, $contact_id = null, $params = null)
    {
        /**
         * @var waSystem
         */
        $system = waSystem::getInstance();
        /**
         * @var waAppConfig
         */
        $config = $system->getConfig();
        if ($config instanceof waAppConfig) {
            // Get actions of current application available to log
            $actions = $config->getLogActions();
            // Check action
            if (!isset($actions[$action])) {
                if (waSystemConfig::isDebug()) {
                    throw new waException('Unknown action for log '.$action);
                } else {
                    return false;
                }
            }
            if ($actions[$action] === false) {
                return false;
            }
            $app_id = $system->getApp();
        } else {
            $app_id = 'wa-system';
        }
        // Save to database
        $data = array(
            'app_id' => $app_id,
            'contact_id' => $contact_id === null ? $system->getUser()->getId() : $contact_id,
            'datetime' => date("Y-m-d H:i:s"),
            'action' => $action
        );
        if ($count !== null) {
            $data['count'] = $count;
        }
        
        if ($params !== null) {
            $data['params'] = $params;
        }

        if (!class_exists('waLogModel')) {
            wa('webasyst');
        }
        $log_model = new waLogModel();
        return $log_model->insert($data);
    }

    /**
     * @return waRequest
     */
    public function getRequest()
    {
        return waSystem::getInstance()->getRequest();
    }

    /**
     * @return waResponse
     */
    public function getResponse()
    {
        return waSystem::getInstance()->getResponse();
    }

    /**
     * @return waSessionStorage
     */
    public function getStorage()
    {
        return waSystem::getInstance()->getStorage();
    }

    public function storage()
    {
        $n = func_num_args();
        $args = func_get_args();
        if ($n == 1) {
            return $this->getStorage()->get($this->getApp().'/'.$args[0]);
        } elseif ($n == 2) {
            if ($args[1] === null) {
                return $this->getStorage()->del($this->getApp().'/'.$args[0]);
            } else {
                return $this->getStorage()->set($this->getApp().'/'.$args[0], $args[1]);
            }
        }
        return null;
    }

    /**
     * @return waAppConfig
     */
    public function getConfig()
    {
        return waSystem::getInstance()->getConfig();
    }

    /**
     *
     * Returns path to config file
     * if $custom returns path in wa-config/apps/$app_id/$name
     * else wa-apps/$app_id/lib/config/$name
     *
     * @param string $name - filename
     * @param bool $custom - system or custom config
     */
    public function configPath($name, $custom = false)
    {
        if ($custom) {
            $path = $this->getConfig()->getPath('config').'/apps/'.$this->getApp().'/'.$name;
            waFiles::create($path);
            return $path;
        } else {
            $path = $this->getConfig()->getAppPath('lib/config/'.$name);
        }
        return $path;
    }

    public function redirect($params = array())
    {
        if ((!is_array($params) && $params)) {
            $params = array(
                'url' => $params
            );
        }
        if (isset($params['url']) && $params['url']) {
            header('Location: '.$params['url']);
            exit;
        }

        if ($params) {
            $url = waSystem::getInstance()->getUrl();
            $i = 0;
            foreach ($params as $k => $v) {
                $url .= ($i++ ? '&' : '?'). $k . '=' . urlencode($v);
            }
        } else {
            $url = waSystem::getInstance()->getConfig()->getCurrentUrl();
        }

        header('Location: '.$url);
        exit;
    }


    public function appSettings($name, $default = '')
    {
        $app_settings_model = new waAppSettingsModel();
        return $app_settings_model->get($this->getApp(), $name, $default);
    }

    /**
     * Relative path from app root to plugin root this controller belongs to
     * (no leading slash, with trailing slash). For application controllers return ''.
     * @return string relative path or ''
     */
    public function getPluginRoot()
    {
        $path = waAutoload::getInstance()->get(get_class($this));
        if (!$path) {
            return '';
        }

        // Remove path to application from path to a controller to get relative path
        $appsPath = str_replace('\\', '/', waConfig::get('wa_path_apps'));
        $path = dirname($path);
        $path = str_replace('\\', '/', $path);

        if (false === strpos($path, $appsPath)) {
            // webasyst app
            $appsPath = str_replace('\\', '/', waConfig::get('wa_path_system'));
            if (false === strpos($path, $appsPath)) {
                return '';
            }
        }

        $path = str_replace($appsPath, '', $path);
        $path = trim($path, '\/');
        $path = preg_replace('~^[^/]+/~', '', $path); // remove app dir from the begining of the path

        // /lib dir indicates that we've found either a plugin root or an application root
        $prevBase = '';
        while($prevBase != 'lib') {
            $prevBase = basename($path);
            $path = dirname($path);
            if(!$path || $path == '.') {
                return '';
            }
        }

        return $path.'/';
    }

    public function __call($name, $arguments)
    {
        throw new waException("Call to undefined method ".$name);
    }
}
