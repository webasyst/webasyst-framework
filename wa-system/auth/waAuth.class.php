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

    /**
     * @param array $options
     */
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

    /**
     * @return array|bool|null
     * @throws waException
     */
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
        if ($info && $info['id'] && (!$this->getOption('is_user') || ifempty($info['is_user']) > 0)) {
            return $info;
        }
        return false;
    }

    /**
     * @param string $email
     * @return array
     */
    protected function getByEmail($email)
    {
        $model = new waContactModel();
        $sql = "SELECT c.* FROM wa_contact c
                JOIN wa_contact_emails e ON c.id = e.contact_id
                WHERE ".($this->options['is_user'] ? "c.is_user = 1 AND " : "")."e.email LIKE s:email AND e.sort = 0 AND c.password != ''
                ORDER BY c.id LIMIT 1";
        return $model->query($sql, array('email' => $email))->fetch();
    }

    /**
     * @param string $login
     * @return array
     * @throws waException
     */
    public function getByLogin($login)
    {
        if (!$login) {
            return null;
        }
        $result = array();
        $model = new waContactModel();
        if ($this->options['login'] == 'login') {
            $result = $model->getByField('login', $login);
            if (!$result) {
                $result = $this->getByEmail($login);
            }
        } elseif ($this->options['login'] == 'email') {
            if (strpos($login, '@') !== false) {
                $result = $this->getByEmail($login);
            }
            if (!$result) {
                $result = $model->getByField('login', $login);
            }
        }
        if ($result) {
            $this->checkBan($result);
        }
        return $result;
    }

    /**
     * @param array $data - contact/user info
     * @throws waException
     */
    protected function checkBan($data)
    {
        if ($data['is_user'] == -1) {
            throw new waException(_ws('Access denied.'));
        }
    }

    /**
     * @param $params
     * @return array|bool
     * @throws waException
     */
    protected function _auth($params)
    {
        if ($params && isset($params['id'])) {
            $contact_model = new waContactModel();
            $user_info = $contact_model->getById($params['id']);
            if ($user_info && ($user_info['is_user'] > 0 || !$this->options['is_user'])) {
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
            if ($user_info && ($user_info['is_user'] > 0 || !$this->options['is_user']) &&
                waContact::getPasswordHash($password) === $user_info['password']) {
                $auth_config = wa()->getAuthConfig();
                if (wa()->getEnv() == 'frontend' && !empty($auth_config['params']['confirm_email'])) {
                    $contact_emails_model = new waContactEmailsModel();
                    $email_row = $contact_emails_model->getByField(array('contact_id' => $user_info['id'], 'sort' => 0));
                    if ($email_row && $email_row['status'] == 'unconfirmed') {
                        $login_url = wa()->getRouteUrl((isset($auth_config['app']) ? $auth_config['app'] : '').'/login', array());
                        $html = sprintf(_ws('A confirmation link has been sent to your email address provided during the signup. Please click this link to confirm your email and to sign in. <a class="send-email-confirmation" href="%s">Resend the link</a>'), $login_url.'?send_confirmation=1');
                        $html = '<div class="block-confirmation-email">'.$html.'</div>';
                        $html .= <<<HTML
<script type="text/javascript">
    $(function () {
        $('a.send-email-confirmation').click(function () {
            $.post($(this).attr('href'), {
                    login: $(this).closest('form').find("input[name='login']").val()
                }, function (response) {
                $('.block-confirmation-email').html(response);
            });
            return false;
        });
    });
</script>
HTML;

                        throw new waException($html);
                    }
                }

                $response = waSystem::getInstance()->getResponse();
                // if remember
                if (waRequest::post('remember')) {
                    $cookie_domain = ifset($this->options['cookie_domain'], '');
                    $response->setCookie('auth_token', $this->getToken($user_info), time() + 2592000, null, $cookie_domain, false, true);
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

    /**
     * @return array|bool
     * @throws waException
     */
    protected function _authByCookie()
    {
        if ($this->getOption('remember_enabled') && $token = waRequest::cookie('auth_token')) {
            $model = new waContactModel();
            $response = waSystem::getInstance()->getResponse();
            $id = substr($token, 15, -15);
            $user_info = $model->getById($id);
            $this->checkBan($user_info);
            $cookie_domain = ifset($this->options['cookie_domain'], '');
            if ($user_info && ($user_info['is_user'] > 0 || !$this->options['is_user']) &&
                $token === $this->getToken($user_info)) {
                $response->setCookie('auth_token', $token, time() + 2592000, null, $cookie_domain, false, true);
                return $this->getAuthData($user_info);
            } else {
                $response->setCookie('auth_token', null, -1, null, $cookie_domain);
            }
        }
        return false;
    }


    /**
     * @param $user_info
     * @return array
     */
    protected function getAuthData($user_info)
    {
        return array(
            'id' => $user_info['id'],
            'login' => $user_info['login'],
            'is_user' => $user_info['is_user'],
            'token' => $this->getToken($user_info)
        );
    }

    /**
     * @param $user_info
     * @return string
     */
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
            $cookie_domain = ifset($this->options['cookie_domain'], '');
            waSystem::getInstance()->getResponse()->setCookie('auth_token', null, -1, null, $cookie_domain);
            if ($cookie_domain) {
                waSystem::getInstance()->getResponse()->setCookie('auth_token', null, -1);
            }
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
            $cookie_domain = ifset($this->options['cookie_domain'], '');
            wa()->getResponse()->setCookie('auth_token', $this->getToken($data), time() + 2592000, null, $cookie_domain, false, true);
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

