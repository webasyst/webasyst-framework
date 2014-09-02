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
 * @subpackage auth
 */
class waAuth implements waiAuth
{
    protected $options = array(
        'cookie_expire' => 2592000,
    );

    public function __construct($options = array())
    {
        if (is_array($options)) {
            foreach ($options as $k => $v) {
                $this->options[$k] = $v;
            }
        }
        if (!isset($this->options['login'])) {
            $this->options['login'] = wa()->getEnv() == 'backend' ? 'login' : 'email';
        }

        if (!isset($this->options['is_user'])) {
            // only contacts with is_user = 1 can auth
            $this->options['is_user'] = wa()->getEnv() == 'backend';
        }

        if (!isset($this->options['remember_enabled'])) {
            if (wa()->getEnv() == 'backend') {
                try {
                    $app_settings_model = new waAppSettingsModel();
                    $this->options['remember_enabled'] = $app_settings_model->get('webasyst', 'rememberme', true);
                } catch (waException $e) {
                    $this->options['remember_enabled'] = true;
                }
            } else {
                $this->options['remember_enabled'] = true;
            }
        }
    }

    /**
     * Auth user returns result of auth
     *
     * @param array $params
     * @return mixed
     */
    public function auth($params = array())
    {
        $result = $this->_auth($params);
        if ($result !== false) {
            waSystem::getInstance()->getStorage()->write('auth_user', $result);
            waSystem::getInstance()->getUser()->init();
        }
        return $result;
    }

    public function isAuth()
    {
        $info = waSystem::getInstance()->getStorage()->read('auth_user');
        if (!$info) {
            $info = $this->_authByCookie();
            if ($info) {
                waSystem::getInstance()->getStorage()->write('auth_user', $info);
            }
        }
        // check options
        if ($info && $info['id'] && (!$this->getOption('is_user') || !empty($info['is_user']))) {
            return $info;
        }
        return false;
    }

    public function getByLogin($login)
    {
        $result = array();
        $model = new waContactModel();
        if ($this->options['login'] == 'login') {
            $result = $model->getByField('login', $login);
        } elseif ($this->options['login'] == 'email') {
            if (strpos($login, '@') === false) {
                $result = $model->getByField('login', $login);
            } else {
                $sql = "SELECT c.* FROM wa_contact c
                JOIN wa_contact_emails e ON c.id = e.contact_id
                WHERE ".($this->options['is_user'] ? "c.is_user = 1 AND " : "")."e.email LIKE s:email AND e.sort = 0 AND c.password != ''
                ORDER BY c.id LIMIT 1";
                $result = $model->query($sql, array('email' => $login))->fetch();
            }
        }
        if ($result) {
            $this->checkBan($result);
        }
        return $result;
    }

    protected function checkBan($data)
    {
        if ($data['is_user'] == -1) {
            throw new waException(_ws('Access denied.'));
        }
    }

    protected function _auth($params)
    {
        if ($params && isset($params['id'])) {
            $contact_model = new waContactModel();
            $user_info = $contact_model->getById($params['id']);
            if ($user_info && ($user_info['is_user'] || !$this->options['is_user'])) {
                waSystem::getInstance()->getResponse()->setCookie('auth_token', null, -1);
                return $this->getAuthData($user_info);
            }
            return false;
        } elseif ($params && isset($params['login']) && isset($params['password'])) {
            $login = $params['login'];
            $password = $params['password'];
        } elseif (waRequest::getMethod() == 'post' && waRequest::post('wa_auth_login')) {
            $login = waRequest::post('login');
            $password = waRequest::post('password');
            if (!strlen($login)) {
                throw new waException(_ws('Login is required'));
            }
        } else {
            $login = null;
        }
        if ($login && strlen($login)) {
            $user_info = $this->getByLogin($login);
            if ($user_info && ($user_info['is_user'] || !$this->options['is_user']) &&
                waContact::getPasswordHash($password) === $user_info['password']) {
                $response = waSystem::getInstance()->getResponse();
                // if remember
                if (waRequest::post('remember')) {
                    $response->setCookie('auth_token', $this->getToken($user_info), time() + 2592000, null, '', false, true);
                    $response->setCookie('remember', 1);
                } else {
                    $response->setCookie('remember', 0);
                }

                // return array with compact user info
                return $this->getAuthData($user_info);
            } else {
                if ($this->options['login'] == 'email') {
                    throw new waException(_ws('Invalid email or password'));
                } else {
                    throw new waException(_ws('Invalid login or password'));
                }
            }
        } else {
            // try auth by cookie
            return $this->_authByCookie();
        }
    }


    protected function _authByCookie()
    {
        if ($this->getOption('remember_enabled') && $token = waRequest::cookie('auth_token')) {
            $model = new waContactModel();
            $response = waSystem::getInstance()->getResponse();
            $id = substr($token, 15, -15);
            $user_info = $model->getById($id);
            $this->checkBan($user_info);
            if ($user_info && ($user_info['is_user'] || !$this->options['is_user']) &&
                $token === $this->getToken($user_info)) {
                $response->setCookie('auth_token', $token, time() + 2592000, null, '', false, true);
                return $this->getAuthData($user_info);
            } else {
                $response->setCookie('auth_token', null, -1);
            }
        }
        return false;
    }


    protected function getAuthData($user_info)
    {
        return array(
            'id' => $user_info['id'],
            'login' => $user_info['login'],
            'is_user' => $user_info['is_user'],
            'token' => $this->getToken($user_info)
        );
    }

    public function getToken($user_info)
    {
        $hash = md5($user_info['create_datetime'] . $user_info['login'] . $user_info['password']);
        return substr($hash, 0, 15).$user_info['id'].substr($hash, -15);
    }

    /**
     * Clear all auth tokens in storage and cookies
     *
     * @return void
     */
    public function clearAuth()
    {
        waSystem::getInstance()->getStorage()->destroy();
        if (waRequest::cookie('auth_token')) {
            waSystem::getInstance()->getResponse()->setCookie('auth_token', null, -1);
        }
    }

    public function checkAuth($data = null)
    {
        if ($auth_info = $this->isAuth()) {
            if (!isset($auth_info['token']) || $auth_info['token'] != $this->getToken($data)) {
                $this->clearAuth();
                return false;
            }
        }
        return true;
    }

    public function updateAuth($data)
    {
        wa()->getStorage()->set('auth_user', $this->getAuthData($data));
        if (waRequest::cookie('auth_token')) {
            wa()->getResponse()->setCookie('auth_token', $this->getToken($data), time() + 2592000, null, '', false, true);
        }
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getOption($name)
    {
        return $this->options[$name];
    }
}

