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
        $config = $this->wa->getAuthConfig();
        return isset($config['auth']) && $config['auth'];
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

    public function userId()
    {
        return $this->wa->getUser()->getId();
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
        if ($this->wa->getEnv() == 'backend') {
            $css = '<link href="'.$this->wa->getRootUrl().'wa-content/css/wa/wa-1.0.css?v'.$this->version(true).'" rel="stylesheet" type="text/css" >
<!--[if IE 9]><link type="text/css" href="'.$this->wa->getRootUrl().'wa-content/css/wa/wa-1.0.ie9.css" rel="stylesheet"><![endif]-->
<!--[if IE 8]><link type="text/css" href="'.$this->wa->getRootUrl().'wa-content/css/wa/wa-1.0.ie8.css" rel="stylesheet"><![endif]-->
<!--[if IE 7]><link type="text/css" href="'.$this->wa->getRootUrl().'wa-content/css/wa/wa-1.0.ie7.css" rel="stylesheet"><![endif]-->'."\n";
        } else {
            $css = '';
        }
        return $css.$this->wa->getResponse()->getCss(true);
    }

    public function js()
    {
        return $this->wa->getResponse()->getJs(true);
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

    public function get($name, $default = null)
    {
        return waRequest::get($name, $default);
    }

    public function server($name, $default = null)
    {
        return waRequest::server($name, $default);
    }

    public function post($name, $default = null)
    {
        return waRequest::post($name, $default);
    }

    public function request($name, $default = null)
    {
        return waRequest::request($name, $default);
    }

    public function param($name, $default = null)
    {
        return waRequest::param($name, $default);
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

    public function captcha($options = array())
    {
        return $this->wa->getCaptcha($options)->getHtml();
    }

    public function captchaUrl($add_random = true)
    {
        return $this->url().$this->app().'/captcha.php'.($add_random ? '?v='.uniqid(time()) : '');
    }

    public function signupUrl()
    {
        $auth = $this->wa->getAuthConfig();
        return $this->wa->getRouteUrl((isset($auth['app']) ? $auth['app'] : '').'/signup');
    }

    public function loginUrl()
    {
        $auth = $this->wa->getAuthConfig();
        return $this->wa->getRouteUrl((isset($auth['app']) ? $auth['app'] : '').'/login');
    }

    public function forgotPasswordUrl()
    {
        $auth = $this->wa->getAuthConfig();
        return $this->wa->getRouteUrl((isset($auth['app']) ? $auth['app'] : '').'/forgotpassword');
    }

    public function loginForm($error = '')
    {
        $auth = $this->wa->getAuth();
        $field_id = $auth->getOption('login');
        if ($field_id == 'login') {
            $field_name = _ws('Login');
        } else {
            $field = waContactFields::get($field_id);
            if ($field) {
                $field_name = $field->getName();
            } else {
                $field_name = ucfirst($field_id);
            }
        }
        return '<div class="wa-form">
            <form action="" method="post">
                <div class="wa-field">
                    <div class="wa-name">'.$field_name.'</div>
                    <div class="wa-value">
                        <input'.($error ? ' class="wa-error"' : '').' type="text" name="login" value="'.htmlspecialchars(waRequest::post('login')).'">
                    </div>
                </div>
                <div class="wa-field">
                    <div class="wa-name">'._ws('Password').'</div>
                    <div class="wa-value">
                        <input'.($error ? ' class="wa-error"' : '').' type="password" name="password">
                        '.($error ? '<em class="wa-error-msg">'.$error.'</em>' : '').'
                    </div>
                </div>
                <div class="wa-field">
                    <div class="wa-value wa-submit">
                        <input type="hidden" name="wa_auth_login" value="1">
                        <input type="submit" value="'._ws('Sign In').'"> <a href="'.$this->getUrl('/forgotpassword').'">'._ws('Forgot password?').'</a>
                    </div>
                </div>
            </form>
        </div>';
    }

    public function forgotPasswordForm($error = '')
    {
        return '<div class="wa-form">
    <form action="" method="post">
        <div class="wa-field">
            <div class="wa-name">'._ws('Email').'</div>
            <div class="wa-value">
                <input'.($error ? ' class="wa-error"' : '').' type="text" name="login" value="'.htmlspecialchars($this->request('login')).'" autocomplete="off">
                '.($error ? '<em class="wa-error-msg">'.$error.'</em>' : '').'
            </div>
        </div>
        <div class="wa-field">
            <div class="wa-value wa-submit">
                <input type="submit" value="'._ws('Reset password').'"> <a href="'.$this->getUrl('/login').'">'._ws('I remember it now!').'</a>
            </div>
        </div>
    </form>
</div>';

    }

    public function setPasswordForm($error = '')
    {
        return '<div class="wa-form">
    <form action="" method="post">
        <div class="wa-field">
            <div class="wa-name">'._ws('Enter a new password').'</div>
            <div class="wa-value">
                <input'.($error ? ' class="wa-error"' : '').' name="password" type="password">
            </div>
        </div>
        <div class="wa-field">
            <div class="wa-name">'._ws('Re-enter password').'</div>
            <div class="wa-value">
                <input'.($error ? ' class="wa-error"' : '').' name="password_confirm" type="password">
                '.($error ? '<em class="wa-error-msg">'.$error.'</em>' : '').'
            </div>
        </div>
        <div class="wa-field">
            <div class="wa-value wa-submit">
                <input type="submit" value="'._ws('Save and log in').'">
            </div>
        </div>
    </form>
</div>';
    }

    public function signupFields($errors = array())
    {
        $config = $this->wa->getAuthConfig();
        $config_fields = isset($config['fields']) ? $config['fields']: array(
            'firstname',
            'lastname',
            '',
            'email' => array('required' => true),
            'password' => array('required' => true),
        );
        $format_fields = array();
        foreach ($config_fields as $k => $v) {
            if (is_numeric($k)) {
                if ($v) {
                    $format_fields[$v] = array();
                } else {
                    $format_fields[] = '';
                }
            } else {
                $format_fields[$k] = $v;
            }
        }
        $fields = array();
        foreach ($format_fields as $field_id => $field) {
            if (!is_numeric($field_id)) {
                if (strpos($field_id, '.')) {
                    $field_id_parts = explode('.', $field_id);
                    $id = $field_id_parts[0];
                    $field['ext'] = $field_id_parts[1];
                } else {
                    $id = $field_id;
                }
                $f = waContactFields::get($id);
                if ($f) {
                    $fields[$field_id] = array($f, $field);
                } elseif ($field_id == 'login') {
                    $fields[$field_id] = array(new waContactStringField($field_id, _ws('Login')), $field);
                } elseif ($field_id == 'password') {
                    $fields[$field_id] = array(new waContactPasswordField($field_id, _ws('Password')), $field);
                    $field_id .= '_confirm';
                    $fields[$field_id] = array(new waContactPasswordField($field_id, _ws('Confirm password')), $field);
                }
            } else {
                $fields[] = '';
            }
        }
        return $fields;
    }

    public function signupForm($errors = array())
    {
        $fields = $this->signupFields($errors);
        $html = '<div class="wa-form"><form action="" method="post">';
        foreach ($fields as $field_id => $field) {
            if ($field) {
                $f = $field[0];
                /**
                 * @var waContactField $f
                 */
                if (isset($errors[$field_id])) {
                    $field_error = is_array($errors[$field_id]) ? implode(', ', $errors[$field_id]): $errors[$field_id];
                } else {
                    $field_error = false;
                }
                $field[1]['id'] = $field_id;
                if ($f instanceof waContactCompositeField) {
                    foreach ($f->getFields() as $sf) {
                        /**
                         * @var waContactField $sf
                         */
                        $html .= $this->signupFieldHTML($sf, array('parent' => $field_id, 'id' => $sf->getId()), $field_error);
                    }
                } else {
                    $html .= $this->signupFieldHTML($f, $field[1], $field_error);
                }
            } else {
                $html .= '<div class="wa-field wa-separator"></div>';
            }
        }
        $config = $this->wa->getAuthConfig();
        if (isset($config['signup_captcha']) && $config['signup_captcha']) {
            $html .= '<div class="wa-field"><div class="wa-value">';
            $html .= $this->wa->getCaptcha()->getHtml(isset($errors['captcha']) ? $errors['captcha'] : '');
            if (isset($errors['captcha'])) {
                $html .= '<em class="wa-error-msg">'.$errors['captcha'].'</em>';
            }
            $html .= '</div></div>';
        }
        $html .= '<div class="wa-field"><div class="wa-value wa-submit">
            <input type="submit" value='._ws('"Sign up"').'> '.sprintf(_ws('or <a href="%s">login</a> if you already have an account'), $this->getUrl('/login')).'
        </div></div>';
        $html .= '</form></div>';
        return $html;
    }


    private function signupFieldHTML(waContactField $f, $params, $error = '')
    {
        $data = waRequest::post('data');
        // get value
        if (isset($params['parent'])) {
            $parent_value = $data[$params['parent']];
            $params['value'] = $parent_value[$params['id']];
        } else {
            $params['value'] = isset($data[$params['id']]) ? $data[$params['id']] : '';
        }

        $name = $f->getName();
        if (isset($params['ext'])) {
            $exts = $f->getParameter('ext');
            if (isset($exts[$params['ext']])) {
                $name .= ' ('._ws($exts[$params['ext']]).')';
            } else {
                $name .= ' ('.$params['ext'].')';
            }
        }
        $params['namespace'] = 'data';
        $html = '<div class="wa-field">
                <div class="wa-name">'.$name.'</div>
                <div class="wa-value">'.$f->getHTML($params, $error !== false ? 'class="wa-error"' : '');
        if ($error) {
            $html .= '<em class="wa-error-msg">'.$error.'</em>';
        }
        $html .= '</div></div>';
        return $html;
    }

    public function authAdapters($return_array = false)
    {
        $adapters = $this->wa->getAuthAdapters();
        if ($return_array) {
            return $adapters;
        }
        if (!$adapters) {
            return '';
        }
        $html = '<div class="wa-auth-adapters"><ul>';
        $url = $this->url().'oauth.php?app='.$this->app().'&provider=';
        foreach ($adapters as $adapter) {
            /**
             * @var waAuthAdapter $adapter
             */
            $html .= '<li><a href="'.$url.$adapter->getId().'"><img alt="'.$adapter->getName().'" src="'.$adapter->getIcon().'">'.$adapter->getName().'</a></li>';
        }
        $html .= '</ul><p>';
        $html .= _ws("Authorize either by entering your contact information, or through one of the websites listed above.");
        $html .= '</p></div>';
        $html .= <<<HTML
<script>
$("div.wa-auth-adapters a").click(function () {
    var left = (screen.width - 600) / 2;
    var top = (screen.height - 400) / 2;
    window.open($(this).attr('href'),'oauth', "width=600,height=400,left="+left+",top="+top+",status=no,toolbar=no,menubar=no");
    return false;
});
</script>
HTML;

        return $html;
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