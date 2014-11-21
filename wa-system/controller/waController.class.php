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

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->getUser()->getId();
    }

    public function getApp()
    {
        return $this->getConfig()->getApplication();
    }

    public function getAppId()
    {
        return $this->getApp();
    }


    public function getRights($name = null, $assoc = true)
    {
        return $this->getUser()->getRights($this->getApp(), $name, $assoc);
    }

    /**
     * Add record to table wa_log
     *
     * @param string $action
     * @param mixed $params
     * @param int $subject_contact_id
     * @param int $contact_id - actor contact id
     * @throws waException
     * @return bool|int
     */
    public function logAction($action, $params = null, $subject_contact_id = null, $contact_id = null)
    {
        if (!class_exists('waLogModel')) {
            wa('webasyst');
        }
        $log_model = new waLogModel();
        return $log_model->add($action, $params, $subject_contact_id, $contact_id);
    }

    /**
     * @deprecated
     */
    public function log($action, $count = null, $contact_id = null, $params = null)
    {
        return $this->logAction($action, $params, null, $contact_id);
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

    public function redirect($params = array(), $code = null)
    {
        if ((!is_array($params) && $params)) {
            $params = array(
                'url' => $params
            );
        }
        if (isset($params['url']) && $params['url']) {
            wa()->getResponse()->redirect($params['url'], $code);
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
        wa()->getResponse()->redirect($url, $code);
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
