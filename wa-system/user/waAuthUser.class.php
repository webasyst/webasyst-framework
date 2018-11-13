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
 * @subpackage user
 */
class waAuthUser extends waUser
{
    protected $auth = false;

    // cache for $this->getTimezone()
    protected $auth_user_timezone = false;

    public function __construct($id = null, $options = array())
    {
        foreach ($options as $name => $value) {
            self::$options[$name] = $value;
        }
        $this->init();
    }

    public function init()
    {
        parent::init();
        if (wa()->getEnv() === 'cli') {
            return;
        }

        if (!isset(self::$options['session_timeout'])) {
            self::$options['session_timeout'] = 1800;
        }

        if (!headers_sent() && ini_get('session.gc_maxlifetime') < self::$options['session_timeout']) {
            @ini_set('session.gc_maxlifetime', self::$options['session_timeout']);
        }

        $auth = waSystem::getInstance()->getAuth();
        $info = $auth->isAuth();
        if ($info && isset($info['id']) && $info['id']) {
            $this->auth = true;
            $this->id = $info['id'];

            try {
                // Update last user activity time.
                if (!waRequest::request('background_process')) {
                    $this->updateLastTime();
                }
                $is_data_loaded = !!$this->getCache();
                $last_check = time() - ifset($info['storage_set'], 0);

                // Make sure that the user did not change the password
                // We do this once in a while, or in case user data is already loaded anyway.
                $session_user = wa()->getStorage()->get('auth_user');
                $session_token = (!empty($session_user['token'])) ? $session_user['token'] : null;
                if ($session_token && ($is_data_loaded || $last_check >= 120 || defined('WA_STRICT_PASSWORD_CHECK'))) {
                    if ($auth->getToken($this) !== $session_token) {
                        throw new waException('Password changed');
                    } else {
                        $auth->updateAuth($this->getCache());
                    }
                }

                // Make sure user is not banned.
                // We do this once in a while, or in case user data is already loaded anyway.
                if ($is_data_loaded || $last_check >= 120 || defined('WA_STRICT_BAN_CHECK')) {
                    if ($this['is_user'] < 0) {
                        throw new waException('Contact is banned');
                    } else {
                        $auth->updateAuth($this->getCache());
                    }
                }
            } catch (waException $e) {
                // Contact is banned or deleted
                $auth->clearAuth();
                $this->id = 0;
            }

            // Set CSRF protection cookie
            if (!waRequest::cookie('_csrf')) {
                waSystem::getInstance()->getResponse()->setCookie('_csrf', uniqid('', true));
            }
        }
    }

    public function updateLastPage()
    {
        if (waRequest::isXMLHttpRequest() || !$this->id || wa()->getEnv() !== 'backend' || waRequest::method() == 'post') {
            return;
        }
        $page = wa()->getRequest()->server('REQUEST_URI');
        $backend = wa()->getConfig()->getBackendUrl(true);
        if ($page === $backend || substr($page, 0, strlen($backend)+1) === $backend.'?') {
            return;
        }
        wa()->getResponse()->setCookie('last_page', $this->getId().'^^^'.$page, time() + 3600*24*31, null, '', false, true);
    }

    public function getLastPage()
    {
        if (! ( $page = wa()->getRequest()->cookie('last_page'))) {
            return '';
        }

        $page = explode('^^^', $page, 2);
        if(!is_array($page) || !isset($page[1]) || $page[0] != $this->getId()) {
            wa()->getResponse()->setCookie('last_page', '');
            return '';
        }
        return $page[1];
    }

    public function updateLastTime($force = false)
    {
        $time = wa()->getStorage()->read('user_last_datetime');
        if (!$time || $force || $time == '0000-00-00 00:00:00' ||
             (time() - strtotime($time) > 120)
        ) {
            try {
                $login_log_model = new waLoginLogModel();
                $last_activity = $login_log_model->getCurrent($this->id);
            } catch (waDbException $e) {
                if ($e->getCode() == 1146) {
                    waSystem::getInstance()->getAuth()->clearAuth();
                    header("Location: ".wa()->getConfig()->getBackendUrl(true));
                    exit;
                }
            }

            $contact_model = new waContactModel();
            $contact_info = $contact_model->getById($this->id);
            $auth = waSystem::getInstance()->getAuth();
            if (!$auth->checkAuth($contact_info)) {
                header("Location: ".wa()->getConfig()->getRequestUrl(false));
                exit;
            }
            if (!$contact_info || (waSystem::getInstance()->getEnv() == 'backend' && !$contact_info['is_user'])) {
                waSystem::getInstance()->getAuth()->clearAuth();
                header("Location: ".wa()->getConfig()->getBackendUrl(true));
                exit;
            } else {
                $this->setCache($contact_info);
            }

            if (!$time) {
                $time = $contact_info['last_datetime'];
            }
            if (!$last_activity) {
                $login_log_model->insert(array(
                    'contact_id'   => $this->id,
                    'datetime_in'  => date("Y-m-d H:i:s"),
                    'datetime_out' => $force == 'logout' ? date("Y-m-d H:i:s") : null,
                    'ip'           => waRequest::getIp(),
                ));
                // TODO: insert record in waLog
            } else {
                if ($force == 'logout') {
                    $login_log_model->updateById($last_activity['id'], array('datetime_out' => date("Y-m-d H:i:s")));
                } elseif ($last_datetime = strtotime($time)) {
                    if (time() - $last_datetime > self::$options['activity_timeout']) {
                        $login_log_model->updateById($last_activity['id'], array('datetime_out' => $time));
                        $login_log_model->insert(array(
                            'contact_id'   => $this->id,
                            'datetime_in'  => date("Y-m-d H:i:s"),
                            'datetime_out' => null,
                            'ip'           => waRequest::getIp(),
                        ));
                        // TODO: insert record in waLog
                    }
                }
            }
            $t = date("Y-m-d H:i:s");
            $contact_model->updateById($this->id, array('last_datetime' => $t));
            wa()->getStorage()->write('user_last_datetime', $t);
        }
    }

    public function setLocale($locale)
    {

    }

    public function isAuth()
    {
        return (bool)$this->auth;
    }

    public function logout()
    {
        // Update last datetime of the current user
        $this->updateLastTime('logout');
        // clear auth
        waSystem::getInstance()->getAuth()->clearAuth();
        $this->id = $this->data = null;
        $this->auth = false;
    }

    public function getTimezone($return_object=false)
    {
        // this cache significantly speeds up date and time formatting
        if ($this->auth_user_timezone === false) {
            $this->auth_user_timezone = null;
            $data = array(
                $this->get('timezone'),
                waRequest::cookie('tz', '', 'string'),
                waRequest::cookie('oldtz', '', 'string'),
                self::$options['default']['timezone'],
            );
            foreach($data as $timezone) {
                if ($timezone) {
                    try {
                        // Make sure it's a valid timezone
                        $this->auth_user_timezone = new DateTimeZone($timezone);
                        break;
                    } catch (Exception $e) {
                    }
                }
            }
        }

        if ($this->auth_user_timezone) {
            if ($return_object) {
                return $this->auth_user_timezone;
            } else {
                return $this->auth_user_timezone->getName();
            }
        } else if ($return_object) {
            throw new waException('No timezone selected');
        } else {
            return null;
        }
    }
}
