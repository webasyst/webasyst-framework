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
    protected $view;
    protected $version;
    protected $app_id;
    protected static $helpers = array();
    protected static $params = array();

    public function __construct(waView $view)
    {
        $this->view = $view;
        $this->app_id = wa()->getApp();
    }

    /**
     * @return waAppConfig
     */
    protected function getConfig()
    {
        return wa($this->app_id)->getConfig();
    }

    public function header()
    {
        return wa_header();
    }

    public function app()
    {
        return wa()->getApp();
    }

    public function apps()
    {
        if (wa()->getEnv() == 'frontend') {
            $domain = wa()->getRouting()->getDomain(null, true);
            $domain_config_path = $this->getConfig()->getConfigPath('domains/'.$domain.'.php', true, 'site');
            if (file_exists($domain_config_path)) {
                /**
                 * @var $domain_config array
                 */
                $domain_config = include($domain_config_path);
                if (isset($domain_config['apps']) && $domain_config['apps']) {
                    foreach ($domain_config['apps'] as &$row) {
                        $row['name'] = htmlspecialchars($row['name']);
                    }
                    unset($row);
                    return $domain_config['apps'];
                }
                return wa()->getFrontendApps($domain, isset($domain_config['name']) ? $domain_config['name'] : null, true);
            } else {
                return wa()->getFrontendApps($domain, null, true);
            }
        } else {
            return wa()->getUser()->getApps();
        }
    }

    /**
     * @return array
     * array(
     *     'shop' => 'shop my nav html...',
     *     'helpdesk' => 'helpdesk my nav html...',
     *     ...
     * )
     */
    public function myNav($ul_class = true)
    {

        $domain = wa()->getRouting()->getDomain(null, true);
        $domain_config_path = wa()->getConfig()->getConfigPath('domains/'.$domain.'.php', true, 'site');
        if (file_exists($domain_config_path)) {
            $domain_config = include($domain_config_path);
        } else {
            $domain_config = array();
        }

        $routes = wa()->getRouting()->getRoutes();
        $apps = wa()->getApps();
        $result = array();
        foreach ($routes as $r) {
            if (isset($r['app']) && !empty($apps[$r['app']]['my_account'])) {
                $result[$r['app']] = $r;
            }
        }


        if (isset($domain_config['personal'])) {
            $tmp = array();
            foreach ($domain_config['personal'] as $app_id => $enabled) {
                if (!isset($result[$app_id])) {
                    continue;
                }
                if ($enabled) {
                    $tmp[$app_id] = $result[$app_id];
                } else {
                    unset($result[$app_id]);
                }
            }
            foreach ($result as $app_id => $r) {
                $tmp[$app_id]  = $r;
            }
            $result = array_reverse($tmp, true);
        }

        $old_app = wa()->getApp();
        $my_nav_selected = $this->view->getVars('my_nav_selected');
        $old_params = waRequest::param();

        $i = 0;
        foreach ($result as $app_id => $r) {
            unset($r['url']);
            unset($r['app']);
            if ($i || $old_app != $app_id) {
                waSystem::getInstance($app_id, null, true);
                waRequest::setParam($r);
            }
            $class_name = $app_id.'MyNavAction';
            if (class_exists($class_name)) {
                /**
                 * @var waViewAction $action
                 */
                try {
                    $action = new $class_name();
                    wa()->getView()->assign('my_nav_selected', $app_id == $old_app ? $my_nav_selected : '');
                    $result[$app_id] = $action->display(false);
                } catch (Exception $e) {
                    unset($result[$app_id]);
                }
            } else {
                unset($result[$app_id]);
            }
            $i++;
        }

        if (isset($app_id) && $old_app != $app_id) {
            waRequest::setParam($old_params);
            wa()->setActive($old_app);
        }

        $result = array_reverse($result, true);
        if ($ul_class) {
            $html = '<ul'.(is_string($ul_class) ? ' class="'.$ul_class.'"' : '').'>';
            foreach ($result as $app_result) {
                $html .= $app_result;
            }
            $html .= '</ul>';
            return $html;
        } else {
            return $result;
        }
    }

    public function myUrl()
    {
        $auth = wa()->getAuthConfig();
        if (!empty($auth['app'])) {
            $app = wa()->getAppInfo($auth['app']);
            if (!empty($app['my_account'])) {
                return wa()->getRouteUrl($auth['app'].'/frontend/my');
            }
        }
        $app = wa()->getAppInfo();
        if (!empty($app['my_account'])) {
            return wa()->getRouteUrl('/frontend/my');
        }
        return null;
    }

    public function head()
    {
        $domain = wa()->getRouting()->getDomain(null, true);
        $domain_config_path = $this->getConfig()->getConfigPath('domains/'.$domain.'.php', true, 'site');
        $html = '';
        $og = wa()->getResponse()->getMeta('og');
        if ($og) {
            foreach ($og as $k => $v) {
                $html .= '<meta property="og:'.htmlspecialchars($k).'" content="'.htmlspecialchars($v).'" />'.PHP_EOL;
            }
        }

        if (file_exists($domain_config_path)) {
            /**
             * @var $domain_config array
             */
            $domain_config = include($domain_config_path);
            if (!empty($domain_config['head_js'])) {
                $html .= $domain_config['head_js'];
            }
            $response = wa()->getResponse();
            if (isset($domain_config['google_analytics']) && !is_array($domain_config['google_analytics'])) {
                $domain_config['google_analytics'] = array(
                    'code' => $domain_config['google_analytics']
                );
            }
            if (!empty($domain_config['google_analytics']['code'])) {
                if (!empty($domain_config['google_analytics']['universal'])) {
                    $html .= <<<HTML
<script type="text/javascript">
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
ga('create', '{$domain_config['google_analytics']['code']}', 'auto');
ga('send', 'pageview');
{$response->getGoogleAnalytics()}
</script>
HTML;
                } else {
                    $html .= <<<HTML
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '{$domain_config['google_analytics']['code']}']);
  _gaq.push(['_trackPageview']);
 {$response->getGoogleAnalytics()}
  (function() {
      var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
      ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
      var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
HTML;
                }
            }
        }
        return $html;
    }

    public function headJs()
    {
        return $this->head();
    }


    public function isAuthEnabled()
    {
        $config = wa()->getAuthConfig();
        return isset($config['auth']) && $config['auth'];
    }

    public function user($field=null, $format='html')
    {
        $user = wa()->getUser();
        if ($field !== null) {
            return $user->get($field, $format);
        } else {
            return $user;
        }
    }

    public function userRights($name)
    {
        return wa()->getUser()->getRights(wa()->getApp(), $name);
    }

    public function userId()
    {
        return wa()->getUser()->getId();
    }

    public function locale()
    {
        return wa()->getLocale();
    }

    public function userLocale()
    {
        return $this->locale();
    }

    public function appName($escape = true)
    {
        $app_info = wa()->getAppInfo();
        $name = $app_info['name'];
        return $escape ? htmlspecialchars($name) : $name;
    }

    public function pluginName($plugin_id, $escape = true)
    {
        $plugin = $this->getConfig()->getPluginInfo($plugin_id);
        $name = $plugin['name'];
        return $escape ? htmlspecialchars($name) : $name;
    }

    public function accountName($escape = true)
    {
        $name = wa()->getSetting('name', 'Webasyst', 'webasyst');
        return $escape ? htmlspecialchars($name) : $name;
    }

    public function setting($name, $default = '', $app_id = null)
    {
        return wa()->getSetting($name, $default, $app_id);
    }

    public function module($default = null)
    {
        return waRequest::get('module', $default);
    }

    public function css()
    {
        if (wa()->getEnv() == 'backend') {
            $css = '<link href="'.wa()->getRootUrl().'wa-content/css/wa/wa-1.3.css?v'.$this->version(true).'" rel="stylesheet" type="text/css" >
<!--[if IE 9]><link type="text/css" href="'.wa()->getRootUrl().'wa-content/css/wa/wa-1.0.ie9.css" rel="stylesheet"><![endif]-->
<!--[if IE 8]><link type="text/css" href="'.wa()->getRootUrl().'wa-content/css/wa/wa-1.0.ie8.css" rel="stylesheet"><![endif]-->
<!--[if IE 7]><link type="text/css" href="'.wa()->getRootUrl().'wa-content/css/wa/wa-1.0.ie7.css" rel="stylesheet"><![endif]-->
<link type="text/css" rel="stylesheet" href="'.wa()->getRootUrl().'wa-content/font/ruble/arial/fontface.css">'."\n";
            
            if ( !waRequest::isMobile(false) )
                $css .= '<meta name="viewport" content="width=device-width, initial-scale=1" />'."\n"; //for handling iPad and tablet computer default view properly
            
        } else {
            $css = '';
        }
        return $css.wa()->getResponse()->getCss(true);
    }

    public function js()
    {
        return wa()->getResponse()->getJs(true);
    }

    /**
     * @param bool|string $app_id - true for system version
     * @return string
     */
    public function version($app_id = null)
    {
        if ($app_id === true) {
            $app_info = wa()->getAppInfo('webasyst');
            return isset($app_info['version']) ? $app_info['version'] : '0.0.1';
        } else {
            if ($this->version === null) {
                $app_info = wa()->getAppInfo($app_id);
                $this->version = isset($app_info['version']) ? $app_info['version'] : '0.0.1';
                if (SystemConfig::isDebug()) {
                    $this->version .= ".".time();
                } else {
                    $file = wa()->getAppPath('lib/config/build.php', $app_id);
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

    public function cookie($name, $default = null)
    {
        return waRequest::cookie($name, $default);
    }

    public function param($name, $default = null)
    {
        return waRequest::param($name, $default);
    }

    public function url($absolute = false)
    {
        return wa()->getRootUrl($absolute);
    }

    public function domainUrl()
    {
        return $this->getConfig()->getHostUrl();
    }

    public function currentUrl($absolute = false, $without_params = false)
    {
        $url = $this->getConfig()->getCurrentUrl();
        if ($without_params) {
            if (($i = strpos($url, '?')) !== false) {
                $url = substr($url, 0, $i);
            }
        }
        if ($absolute) {
            return $this->domainUrl().$url;
        } else {
            return $url;
        }
    }

    public function getUrl($route, $params = array(), $absolute = false)
    {
        return wa()->getRouteUrl($route, $params, $absolute);
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
        $contact_model = new waContactModel();
        if ($contact = $contact_model->getById($id)) {
            return new waContact($contact);
        }
        return new waContact();
    }

    public function title($title = null)
    {
        if (!$title) {
            return wa()->getResponse()->getTitle();
        } else {
            wa()->getResponse()->setTitle($title);
            return '';
        }
    }

    public function meta($name, $value = null)
    {
        if ($value) {
            wa()->getResponse()->setMeta($name, $value);
        } else {
            return wa()->getResponse()->getMeta($name);
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

    public function session($key, $value = null)
    {
        return $this->storage($key, $value);
    }

    public function storage($key, $value = null)
    {
        if ($value === null) {
            return wa()->getStorage()->get($key);
        } else {
            wa()->getStorage()->set($key, $value);
        }
    }

    public function getEnv()
    {
        return wa()->getEnv();
    }

    public function block($id, $params = array())
    {
        if ($id &&  wa()->appExists('site')) {
            wa('site');
            $model = new siteBlockModel();
            $block = $model->getById($id);

            if (!$block && strpos($id, '.') !== false) {
                list($app_id, $id) = explode('.', $id);
                $path = $this->getConfig()->getAppsPath($app_id, 'lib/config/site.php');
                if (file_exists($path)) {
                    $site_config = include($path);
                    if (isset($site_config['blocks'][$id])) {
                        if (!is_array($site_config['blocks'][$id])) {
                            $block = array('content' => $site_config['blocks'][$id]);
                        } else {
                            $block = $site_config['blocks'][$id];
                        }
                    }
                }
            }
            if ($block) {
                try {
                    $this->view->assign($params);
                    return $this->view->fetch('string:'.$block['content']);
                } catch (Exception $e) {
                    if (waSystemConfig::isDebug()) {
                        return '<pre class="error">'.htmlentities($e->getMessage(),ENT_QUOTES,'utf-8')."</pre>";
                    } else {
                        waLog::log($e->__toString());
                        return '<div class="error">'._ws('Syntax error at block').' '.$id.'</div>';
                    }
                }
            }
        }
        return '';
    }

    public function snippet($id)
    {
        return $this->block($id);
    }


    /**
     * @param string $to
     * @param array $errors
     * @return bool
     */
    public function sendEmail($to, &$errors)
    {
        if (!$to) {
            $app_settings_model = new waAppSettingsModel();
            $to = $app_settings_model->get('webasyst', 'email');
        }
        if (!$to) {
            $errors['all'] = _ws('Recipient (administrator) email is not valid');
            return false;
        }
        if (!wa($this->app_id)->getCaptcha()->isValid()) {
            $errors['captcha'] = _ws('Invalid captcha');
        }
        $email = $this->post('email');
        $email_validator = new waEmailValidator();
        $subject = trim($this->post('subject', _ws('Website request')));
        $body = trim($this->post('body'));
        if (!$body) {
            $errors['body'] = _ws('Please define your request');
        }
        if (!$email) {
            $errors['email'] = _ws('Email is required');
        } elseif (!$email_validator->isValid($email)) {
            $errors['email'] = implode(', ', $email_validator->getErrors());
        }
        if (!$errors) {
            $m = new waMailMessage($subject, nl2br($body));
            $m->setTo($to);
            $m->setFrom(array($email => $this->post('name')));
            if (!$m->send()) {
                $errors['all'] = _ws('An error occurred while attempting to send your request. Please try again in a minute.');
            } else {
                return true;
            }
        }
        return false;
    }

    public function csrf()
    {
        return '<input type="hidden" name="_csrf" value="'.waRequest::cookie('_csrf', '').'" />';
    }

    public function captcha($options = array(), $error = null, $absolute = null, $refresh = null)
    {
        if (!is_array($options)) {
            $refresh = $absolute;
            $absolute = $error;
            $error = $options;
            $options = array();
        }
        return wa($this->app_id)->getCaptcha($options)->getHtml($error, $absolute, $refresh);
    }

    public function captchaUrl($add_random = true)
    {
        return $this->url().$this->app().'/captcha.php'.($add_random ? '?v='.uniqid(time()) : '');
    }

    public function signupUrl($absolute = false)
    {
        $auth = wa()->getAuthConfig();
        return wa()->getRouteUrl((isset($auth['app']) ? $auth['app'] : '').'/signup', array(), $absolute);
    }

    public function loginUrl($absolute = false)
    {
        $auth = wa()->getAuthConfig();
        return wa()->getRouteUrl((isset($auth['app']) ? $auth['app'] : '').'/login', array(), $absolute);
    }

    public function forgotPasswordUrl()
    {
        $auth = wa()->getAuthConfig();
        return wa()->getRouteUrl((isset($auth['app']) ? $auth['app'] : '').'/forgotpassword');
    }

    /**
     * @param string $error
     * @param int $form
     * 1 - with <form action="">
     * 2 - with <form action="LOGIN_URL">
     * @return string
     */
    public function loginForm($error = '', $form = 1, $placeholders = false)
    {
        $auth = wa($this->app_id)->getAuth();
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
        $html = '<div class="wa-form">'.
            ($form ? '<form action="'.($form === 2 ? $this->loginUrl() : '').'" method="post">' : '').'
                <div class="wa-field wa-field-'.$field_id.'">
                    <div class="wa-name">'.$field_name.'</div>
                    <div class="wa-value">
                        <input'.($error ? ' class="wa-error"' : '').' type="text" name="login" value="'.htmlspecialchars(waRequest::post('login')).'"'.($placeholders ? ' placeholder="'.$field_name.'"' : '').'>
                    </div>
                </div>
                <div class="wa-field wa-field-password">
                    <div class="wa-name">'._ws('Password').'</div>
                    <div class="wa-value">
                        <input'.($error ? ' class="wa-error"' : '').' type="password" name="password"'.($placeholders ? ' placeholder="'._ws('Password').'"' : '').'>'.
                        ($error ? '<em class="wa-error-msg">'.$error.'</em>' : '').'
                    </div>
                </div>';

        $auth_config = wa()->getAuthConfig();
        if (!empty($auth_config['rememberme'])) {
            $html .= '<div class="wa-field wa-field-remember-me">
                <div class="wa-value">
                    <label><input name="remember" type="checkbox" '.(waRequest::post('remember') ? 'checked="checked"' : '').' value="1"> '._ws('Remember me').'</label>
                </div>
            </div>';
        }

        $html .= '<div class="wa-field">
                    <div class="wa-value wa-submit">
                        <input type="hidden" name="wa_auth_login" value="1">
                        <input type="submit" value="'._ws('Sign In').'">
                        &nbsp;
                        <a href="'.$this->getUrl('/forgotpassword').'">'._ws('Forgot password?').'</a>
                        &nbsp;
                        <a href="'.$this->getUrl('/signup').'">'._ws('Sign up').'</a>
                    </div>
                </div>'.(waRequest::param('secure') ? $this->csrf() : '').
            ($form ? '</form>' : '').'
        </div>';
        return $html;
    }

    public function forgotPasswordForm($error = '', $placeholders = false)
    {
        return '<div class="wa-form">
    <form action="" method="post">
        <div class="wa-field">
            <div class="wa-name wa-field-email">'._ws('Email').'</div>
            <div class="wa-value">
                <input'.($error ? ' class="wa-error"' : '').' type="text" name="login" value="'.htmlspecialchars(waRequest::request('login', '', waRequest::TYPE_STRING)).'" autocomplete="off" '.($placeholders ? ' placeholder="'._ws('Email').'"' : '').'>
                '.($error ? '<em class="wa-error-msg">'.$error.'</em>' : '').'
            </div>
        </div>
        <div class="wa-field">
            <div class="wa-value wa-submit">
                <input type="submit" value="'._ws('Reset password').'">
                &nbsp;
                <a href="'.$this->getUrl('/login').'">'._ws('I remember it now!').'</a>
            </div>
        </div>'.(waRequest::param('secure') ? $this->csrf() : '').'
    </form>
</div>';

    }

    public function setPasswordForm($error = '')
    {
        return '<div class="wa-form">
    <form action="" method="post">
        <div class="wa-field">
            <div class="wa-name wa-field-password">'._ws('Enter a new password').'</div>
            <div class="wa-value">
                <input'.($error ? ' class="wa-error"' : '').' name="password" type="password">
            </div>
        </div>
        <div class="wa-field wa-field-password">
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
        </div>'.(waRequest::param('secure') ? $this->csrf() : '').'
    </form>
</div>';
    }

    public function signupFields($errors = array())
    {
        $config = wa()->getAuthConfig();
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

    public function signupForm($errors = array(), $placeholders = false)
    {
        $fields = $this->signupFields($errors);
        $html = '<div class="wa-form"><form action="'.$this->signupUrl().'" method="post">';
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
                        $params = array('parent' => $field_id, 'id' => $sf->getId());
                        if ($placeholders) {
                            $params['placeholder'] = $sf->getName();
                        }
                        $html .= $this->signupFieldHTML($sf, $params, $field_error);
                    }
                } else {
                    if ($placeholders) {
                        $field[1]['placeholder'] = $f->getName();
                    }
                    $html .= $this->signupFieldHTML($f, $field[1], $field_error);
                }
            } else {
                $html .= '<div class="wa-field wa-separator"></div>';
            }
        }
        $config = wa()->getAuthConfig();
        if (isset($config['signup_captcha']) && $config['signup_captcha']) {
            $html .= '<div class="wa-field"><div class="wa-value">';
            $html .= wa($this->app_id)->getCaptcha()->getHtml(isset($errors['captcha']) ? $errors['captcha'] : '');
            if (isset($errors['captcha'])) {
                $html .= '<em class="wa-error-msg">'.$errors['captcha'].'</em>';
            }
            $html .= '</div></div>';
        }
        $signup_submit_name = !empty($config['params']['button_caption']) ? htmlspecialchars($config['params']['button_caption']) : _ws('Sign Up');
        $html .= '<div class="wa-field"><div class="wa-value wa-submit">
            <input type="submit" value="'.$signup_submit_name.'"> '.sprintf(_ws('or <a href="%s">login</a> if you already have an account'), $this->getUrl('/login')).'
        </div></div>';
        if (waRequest::param('secure')) {
            $html .= $this->csrf();
        }
        $html .= '</form></div>';
        return $html;
    }


    private function signupFieldHTML(waContactField $f, $params, $error = '')
    {
        $data = waRequest::post('data');
        // get value
        if (isset($params['parent'])) {
            $parent_value = $data[$params['parent']];
            $params['value'] = isset($parent_value[$params['id']]) ? $parent_value[$params['id']] : '';
        } else {
            $params['value'] = isset($data[$params['id']]) ? $data[$params['id']] : '';
        }

        $config = wa()->getAuthConfig();
        if (!empty($config['fields'][$f->getId()]['caption'])) {
            $name = htmlspecialchars($config['fields'][$f->getId()]['caption']);
        } else {
            $name = $f->getName(null, true);

            if (isset($params['ext'])) {
                $exts = $f->getParameter('ext');
                if (isset($exts[$params['ext']])) {
                    $name .= ' ('._ws($exts[$params['ext']]).')';
                } else {
                    $name .= ' ('.$params['ext'].')';
                }
            }
        }
        $params['namespace'] = 'data';
        $is_multi = $f->isMulti();
        if ($is_multi) {
            $f->setParameter('multi', false);
        }
        $attrs = $error !== false ? 'class="wa-error"' : '';
        if (!empty($config['fields'][$f->getId()]['placeholder'])) {
            $attrs .= ' placeholder="'.htmlspecialchars($config['fields'][$f->getId()]['placeholder']).'"';
        } elseif (!empty($params['placeholder'])) {
            $attrs .= ' placeholder="'.htmlspecialchars($params['placeholder']).'"';
        }
		
		if ($f instanceof waContactHiddenField) {
			$html = $f->getHTML($params, $attrs);
		} else {
			$html = '<div class="wa-field wa-field-'.$f->getId().'">
					<div class="wa-name">'.$name.'</div>
					<div class="wa-value">'.$f->getHTML($params, $attrs);
			if ($error) {
				$html .= '<em class="wa-error-msg">'.$error.'</em>';
			}
			$html .= '</div></div>';
		}
        if ($is_multi) {
            $f->setParameter('multi', $is_multi);
        }
        return $html;
    }

    public function authAdapters($return_array = false, $options = array())
    {
        $adapters = wa()->getAuthAdapters();
        if ($return_array) {
            return $adapters;
        }
        if (!$adapters) {
            return '';
        }
        $html = '<div class="wa-auth-adapters"><ul>';

        foreach ($adapters as $adapter) {
            /**
             * @var waAuthAdapter $adapter
             */
            $html .= '<li class="wa-auth-adapter-'.$adapter->getId().'"><a href="'.$adapter->getUrl().'"><img alt="'.$adapter->getName().'" src="'.$adapter->getIcon().'"/>'.$adapter->getName().'</a></li>';
        }
        $html .= '</ul><p>';
        $html .= _ws("Authorize either by entering your contact information, or through one of the websites listed above.");
        $html .= '</p></div>';
        $w = isset($options['width']) ? $options['width'] : 600;
        $h = isset($options['height']) ? $options['height'] : 500;
        $html .= <<<HTML
<script type="text/javascript">
$("div.wa-auth-adapters a").click(function () {
    var left = (screen.width - {$w}) / 2;
    var top = (screen.height - {$h}) / 2;
    window.open($(this).attr('href'),'oauth', "width={$w},height={$h},left="+left+",top="+top+",status=no,toolbar=no,menubar=no");
    return false;
});
</script>
HTML;

        return $html;
    }

    public function debug()
    {
        return waSystemConfig::isDebug();
    }

    public function oauth($provider, $config, $token, $code = null)
    {
        /**
         * @var waOAuth2Adapter $auth
         */
        $auth = wa()->getAuth($provider, $config);
        if (!$token && $code) {
            $token = $auth->getAccessToken($code);
        }
        $data = $auth->getUserData($token);

        if (wa()->getUser()->getId()) {
            wa()->getUser()->save(array(
                $data['source'].'_id' => $data['source_id']
            ));
            return wa()->getUser();
        }

        $app_id = wa()->getApp();
        $contact_id = 0;
        // find contact by auth adapter id, i.e. facebook_id
        $contact_data_model = new waContactDataModel();
        $row = $contact_data_model->getByField(array(
            'field' => $data['source'].'_id',
            'value' => $data['source_id'],
            'sort' => 0
        ));
        if ($row) {
            $contact_id = $row['contact_id'];
        }
        // try find user by email
        if (!$contact_id && isset($data['email'])) {
            $sql = "SELECT c.id FROM wa_contact_emails e
            JOIN wa_contact c ON e.contact_id = c.id
            WHERE e.email = s:email AND e.sort = 0 AND c.password != ''";
            $contact_model = new waContactModel();
            $contact_id = $contact_model->query($sql, array('email' => $data['email']))->fetchField('id');
            // save source_id
            if ($contact_id) {
                $contact_data_model->insert(array(
                    'contact_id' => $contact_id,
                    'field' => $data['source'].'_id',
                    'value' => $data['source_id'],
                    'sort' => 0
                ));
            }
        }
        // create new contact
        if (!$contact_id) {
            $contact = new waContact();
            $data[$data['source'].'_id'] = $data['source_id'];
            $data['create_method'] = $data['source'];
            $data['create_app_id'] = $app_id;
            // set random password (length = default hash length - 1, to disable ability auth using login and password)
            $contact->setPassword(substr(waContact::getPasswordHash(uniqid(time(), true)), 0, -1), true);
            unset($data['source']);
            unset($data['source_id']);
            if (isset($data['photo_url'])) {
                $photo_url = $data['photo_url'];
                unset($data['photo_url']);
            } else {
                $photo_url = false;
            }
            $contact->save($data);
            $contact_id = $contact->getId();

            if ($contact_id && $photo_url) {
                $photo_url_parts = explode('/', $photo_url);
                // copy photo to tmp dir
                $path = wa()->getTempPath('auth_photo/'.$contact_id.'.'.md5(end($photo_url_parts)), $app_id);
                if (function_exists('curl_init')) {
                    $ch = curl_init($photo_url);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 25);
                    $photo = curl_exec($ch);
                    curl_close($ch);
                } else {
                    $photo = file_get_contents($photo_url);
                }
                if ($photo) {
                    file_put_contents($path, $photo);
                    $contact->setPhoto($path);
                }
            }
        } else {
            $contact = new waContact($contact_id);
        }

        // auth user
        if ($contact_id) {
            wa()->getAuth()->auth(array('id' => $contact_id));
            return $contact;
        }
        return false;

    }

    public function __get($app)
    {
        if (!isset(self::$helpers[$app])) {
            $wa = wa($this->app_id);
            if ($wa->getConfig()->getApplication() !== $app) {
                if (wa()->appExists($app)) {
                    $wa = wa($app);
                } else {
                    return null;
                }
            }
            $class = $app.'ViewHelper';
            if (class_exists($class)) {
                self::$helpers[$app] = new $class($wa);
            } else {
                self::$helpers[$app] = null;
            }
        }
        return self::$helpers[$app];
    }
}
