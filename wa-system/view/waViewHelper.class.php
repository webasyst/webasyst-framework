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
                    foreach ($domain_config['apps'] as &$row) {
                        $row['name'] = htmlspecialchars($row['name']);
                    }
                    unset($row);
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

    public function headJs()
    {
        $domain = $this->wa->getRouting()->getDomain(null, true);
        $domain_config_path = $this->wa->getConfig()->getConfigPath('domains/'.$domain.'.php', true, 'site');
        if (file_exists($domain_config_path)) {
            /**
             * @var $domain_config array
             */
            $domain_config = include($domain_config_path);
            $html = '';
            if (!empty($domain_config['head_js'])) {
                $html .= $domain_config['head_js'];
            }
            if (!empty($domain_config['google_analytics'])) {
                $html .= <<<HTML
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '{$domain_config['google_analytics']}']);
  _gaq.push(['_trackPageview']);
 {$this->wa->getResponse()->getGoogleAnalytics()}
  (function() {
      var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
      ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
      var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
HTML;
            }
            return $html;
        }
        return '';
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

    public function userRights($name)
    {
        return $this->wa->getUser()->getRights($this->wa->getApp(), $name);
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

    public function pluginName($plugin_id, $escape = true)
    {
        $plugin = $this->wa->getConfig()->getPluginInfo($plugin_id);
        $name = $plugin['name'];
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

    /**
     * @param bool|string $app_id - true for system version
     * @return string
     */
    public function version($app_id = null)
    {
        if ($app_id === true) {
            $app_info = $this->wa->getAppInfo('webasyst');
            return isset($app_info['version']) ? $app_info['version'] : '0.0.1';
        } else {
            if ($this->version === null) {
                $app_info = $this->wa->getAppInfo($app_id);
                $this->version = isset($app_info['version']) ? $app_info['version'] : '0.0.1';
                if (SystemConfig::isDebug()) {
                    $this->version .= ".".time();
                } elseif (!$app_id) {
                    $file = $this->wa->getAppPath('lib/config/build.php', $app_id);
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

    public function currentUrl($absolute = false, $without_params = false)
    {
        $url = $this->wa->getConfig()->getCurrentUrl();
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

    public function meta($name, $value = null)
    {
        if ($value) {
            $this->wa->getResponse()->setMeta($name, $value);
        } else {
            return $this->wa->getResponse()->getMeta($name);
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
        return $this->wa->getEnv();
    }

    public function block($id)
    {
        if ($id &&  $this->wa->appExists('site')) {
            wa('site');
            $model = new siteBlockModel();
            $block = $model->getById($id);

            if (!$block && strpos($id, '.') !== false) {
                list($app_id, $id) = explode('.', $id);
                $path = $this->wa->getConfig()->getAppsPath($app_id, 'lib/config/site.php');
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


    public function sendEmail($to, &$errors)
    {
        if (!$to) {
            $to = waMail::getDefaultFrom();
        }
        if (!$to) {
            $errors['all'] = _ws('Recipient (administrator) email is not valid');
            return false;
        }
        if (!$this->wa->getCaptcha()->isValid()) {
            $errors['captcha'] = _ws('Invalid captcha');
        }
        $email = $this->post('email');
        $email_validator = new waEmailValidator();
        $subject = trim($this->post('subject', 'Website request'));
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
            $m = new waMailMessage($subject, $body);
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

    public function captcha($options = array(), $error = null, $absolute = false, $refresh = null)
    {
        if (!is_array($options)) {
            $refresh = $absolute;
            $absolute = $error;
            $error = $options;
            $options = array();
        }
        return $this->wa->getCaptcha($options)->getHtml($error, $absolute, $refresh);
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

    public function loginForm($error = '', $form = true)
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
        return '<div class="wa-form">'.
            ($form ? '<form action="'.$this->loginUrl().'" method="post">' : '').'
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
                </div>'.
            ($form ? '</form>' : '').'
        </div>';
    }

    public function forgotPasswordForm($error = '')
    {
        return '<div class="wa-form">
    <form action="" method="post">
        <div class="wa-field">
            <div class="wa-name">'._ws('Email').'</div>
            <div class="wa-value">
                <input'.($error ? ' class="wa-error"' : '').' type="text" name="login" value="'.htmlspecialchars(waRequest::request('login', '', waRequest::TYPE_STRING)).'" autocomplete="off">
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
            <input type="submit" value="'._ws('Sign up').'"> '.sprintf(_ws('or <a href="%s">login</a> if you already have an account'), $this->getUrl('/login')).'
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
        if ($f->isMulti()) {
            $f->setParameter('multi', false);
        }
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
        $url = $this->wa->getRootUrl(false, true).'oauth.php?app='.$this->app().'&provider=';
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
            $wa = $this->wa;
            if ($this->app() !== $app) {
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