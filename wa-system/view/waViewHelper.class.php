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
     * @throws waException
     */
    protected function getConfig()
    {
        return wa($this->app_id)->getConfig();
    }

    public function getCheatSheetButton($options = array())
    {
        $cheat_sheet_button = new webasystBackendCheatSheetActions();
        return $cheat_sheet_button->buttonAction($options);
    }

    /**
     * Show webasyst header
     * @param array $options
     *      array  $options['custom']               some custom data for injecting into webasyst header
     *      string $options['custom']['content']    html content that will be shown in header
     *      string $options['custom']['user']       html content that will be shown inside user aread
     *
     * @return string
     */
    public function header(array $options = [])
    {
        return wa_header($options);
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
     * @throws waException
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

        $result = $this->getRoutesByApps();

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
                $tmp[$app_id] = $r;
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
                try {
                    // Because in waMyNavAction we call static method with check on is_template var
                    $is_from_template = waConfig::get('is_template');
                    waConfig::set('is_template', null);

                    /**
                     * @var waMyNavAction $action
                     */
                    $action = new $class_name();
                    wa()->getView()->assign('my_nav_selected', $app_id == $old_app ? $my_nav_selected : '');
                    $result[$app_id] = $action->display(false);

                    // restore is_template var
                    waConfig::set('is_template', $is_from_template);

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

    protected function getRoutesByApps()
    {
        $routes = wa()->getRouting()->getRoutes();
        $apps = wa()->getApps();
        $result = [];

        foreach ($routes as $r) {
            if (isset($r['app']) && !empty($apps[$r['app']]['my_account'])) {
                $result[$r['app']] = $r;
            }
        }

        return $result;
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
                $html .= '<meta property="'.htmlspecialchars($k).'" content="'.htmlspecialchars($v).'" />'.PHP_EOL;
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

        $canonical = wa()->getResponse()->getCanonical();
        if ($canonical) {
            $html .= '<link rel="canonical" href="' . htmlspecialchars($canonical) . '" />' . PHP_EOL;
        }

        return $html;
    }

    public function headJs()
    {
        return $this->head();
    }

    /**
     * Is auth (log in, sign up, my account page) turned on for current domainisAuthEnabled
     * @return bool
     */
    public function isAuthEnabled()
    {
        $is_from_template = waConfig::get('is_template');
        waConfig::set('is_template', null);
        $auth_config = waDomainAuthConfig::factory();
        $is_enabled = $auth_config->isAuthEnabled();
        waConfig::set('is_template', $is_from_template);
        return $is_enabled;
    }

    public function user($field = null, $format = 'html')
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

    /**
     * If in backend intentionally need use legacy wa-1.3.css UI
     * @param bool $strict
     * @return string
     * @throws waException
     */
    public function legacyCss($strict = false)
    {
        //legacy wa-1.3.css UI environment
        $css = '<link href="'.wa()->getRootUrl().'wa-content/css/wa/wa-1.3.css?v'.$this->version(true).'" rel="stylesheet" type="text/css" >
            <!--[if IE 9]><link type="text/css" href="'.wa()->getRootUrl().'wa-content/css/wa/wa-1.0.ie9.css" rel="stylesheet"><![endif]-->
            <!--[if IE 8]><link type="text/css" href="'.wa()->getRootUrl().'wa-content/css/wa/wa-1.0.ie8.css" rel="stylesheet"><![endif]-->
            <!--[if IE 7]><link type="text/css" href="'.wa()->getRootUrl().'wa-content/css/wa/wa-1.0.ie7.css" rel="stylesheet"><![endif]-->
            <link type="text/css" rel="stylesheet" href="'.wa()->getRootUrl().'wa-content/font/ruble/arial/fontface.css">'."\n";

        // for handling iPad and tablet computer default view properly
        if (!waRequest::isMobile(false)) {
            $css .= '<meta name="viewport" content="width=device-width, initial-scale=1" />'."\n";
        }

        return $css.wa()->getResponse()->getCss(true, $strict);
    }

    /**
     * @param bool $strict
     * @return string
     * @throws waException
     */
    public function css($strict = false)
    {
        $ui_version = $this->whichUI();

        $css = '';
        if (wa()->getEnv() == 'backend' || wa()->getEnv() == 'api') {

            if ($ui_version != '2.0') {
                return $this->legacyCss($strict);
            }

            $css = '<link href="'.wa()->getRootUrl().'wa-content/css/wa/wa-2.0.css?v'.$this->version(true).'" rel="stylesheet" type="text/css">
            <link id="wa-dark-mode" href="'.wa()->getRootUrl().'wa-content/css/wa/wa-2.0-dark.css?v'.$this->version(true).'" rel="stylesheet" type="text/css" media="(prefers-color-scheme: dark)">
            <script src="'.wa()->getRootUrl().'wa-content/js/jquery-wa/wa.switch-mode.js?v'.$this->version(true).'"></script>
    <script defer src="'.wa()->getRootUrl().'wa-content/js/fontawesome/fontawesome-all.min.js?v=513"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no, user-scalable=0" />';

            // no referrer for backend urls
            $css .= '<meta name="referrer" content="origin-when-cross-origin" />';
        }

        return $css.wa()->getResponse()->getCss(true, $strict);
    }

    public function js($strict = false)
    {
        return wa()->getResponse()->getJs(true, $strict);
    }

    /**
     * @param bool|string $app_id - true for system version
     * @return string
     */
    public function version($app_id = null)
    {
        // Framework version?
        if ($app_id === true) {
            $app_info = wa()->getAppInfo('webasyst');
            $result = isset($app_info['version']) ? $app_info['version'] : '0.0.1';
            if (SystemConfig::isDebug() && defined('DEBUG_WA_ASSETS')) {
                $result .= ".".time();
            }
            return $result;
        }

        if (!$app_id) {
            $app_id = wa()->getApp();
        }
        if ($app_id == $this->app_id && $this->version !== null) {
            return $this->version;
        }

        $app_info = wa()->getAppInfo($app_id);
        $result = isset($app_info['version']) ? $app_info['version'] : '0.0.1';
        if (SystemConfig::isDebug()) {
            $result .= ".".time();
        } else {
            $file = wa()->getAppPath('lib/config/build.php', $app_id);
            if (file_exists($file)) {
                $build = include($file);
                $result .= '.'.$build;
            }
        }
        if ($app_id == $this->app_id) {
            $this->version = $result;
        }
        return $result;
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
        if (wa()->getEnv() === 'cli') {
            $app_settings_model = new waAppSettingsModel();
            return $app_settings_model->get('webasyst', 'url', '#');
        } else {
            return $this->getConfig()->getHostUrl();
        }
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

    public function getUrl($route, $params = array(), $absolute = false, $domain = null, $route_url = null)
    {
        return wa()->getRouteUrl($route, $params, $absolute, $domain, $route_url);
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
                'android'    => 'android',
                'blackberry' => 'blackberry',
                'linux'      => 'Linux',
                'ios'        => '(ipad|iphone|ipod)',
                'mac'        => '(Macintosh|Mac\sOS)',
                'windows'    => 'Windows',
            );
        } elseif ($type == 'device') {
            $patterns = array(
                'ipad'    => 'ipad',
                'ipod'    => 'ipod',
                'iphone'  => 'iphone',
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
        }
        if (is_array($key)) {
            $str_key = $key[0];
        } else {
            $str_key = $key;
        }
        if (substr($str_key, 0, 5) !== 'auth_') {
            wa()->getStorage()->set($key, $value);
        }
    }

    public function getEnv()
    {
        return wa()->getEnv();
    }

    public function block($id, $params = array())
    {
        if ($id && wa()->appExists('site')) {
            wa('site');
            $model = new siteBlockModel();
            $block = $model->getById($id);

            if (!$block && strpos($id, '.') !== false) {
                list($app_id, $id) = explode('.', $id);
                if (wa()->appExists($app_id)) {

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
            }
            if ($block) {
                try {
                    $this->view->assign($params);
                    return $this->view->fetch('string:'.$block['content']);
                } catch (Exception $e) {
                    if (waSystemConfig::isDebug()) {
                        return '<pre class="error">'.htmlentities($e->getMessage(), ENT_QUOTES, 'utf-8')."</pre>";
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
            return false;
        }

        $agreed_to_terms = $this->post('agree_to_terms');
        if ($agreed_to_terms !== null && !$agreed_to_terms) {
            $errors['agree_to_terms'] = _ws('Please confirm your agreement');
        }

        $email = $this->post('email');
        $email_validator = new waEmailValidator();
        if (!$email) {
            $errors['email'] = _ws('Email is required');
        } elseif (!$email_validator->isValid($email)) {
            $errors['email'] = implode(', ', $email_validator->getErrors());
        }

        $subject = trim($this->post('subject', _ws("Request from website")));
        $domain = wa()->getRouting()->getDomain();
        if ($domain) {
            if (false !== strpos($domain, '--')) {
                $idna = new waIdna();
                $domain = $idna->decode($domain);
            }
            $subject = $subject.' '.$domain;
        }

        $body = trim($this->post('body'));
        if (!$body) {
            $errors['body'] = _ws('Please define your request');
        }
        if ($errors) {
            return false;
        }

        $body = nl2br(htmlspecialchars($body));
        $body = _ws('Name').': '.htmlspecialchars($this->post('name'))."<br>\n".
            _ws('Email').': '.htmlspecialchars($email)."<br><br>\n".$body;
        $m = new waMailMessage($subject, $body);
        $m->setTo($to);
        $m->setReplyTo(array($email => $this->post('name')));
        if (!$m->send()) {
            $errors['all'] = _ws('An error occurred while attempting to send your request. Please try again in a minute.');
            return false;
        }

        return true;
    }

    public function csrf()
    {
        return '<input type="hidden" name="_csrf" value="'.htmlspecialchars(waRequest::cookie('_csrf', '')).'" />';
    }

    public function captcha($options = array(), $error = null, $absolute = null, $refresh = null)
    {
        if (!is_array($options)) {
            $refresh = $absolute;
            $absolute = $error;
            $error = $options;
            $options = array();
        }
        // $options['app_id'] is supported since 1.8.2
        $app_id = ifset($options, 'app_id', $this->app_id);
        $options['app_id'] = $app_id;
        return wa($app_id)->getCaptcha($options)->getHtml($error, $absolute, $refresh);
    }

    public function captchaUrl($add_random = true)
    {
        return $this->url().$this->app().'/captcha.php'.($add_random ? '?v='.uniqid(time()) : '');
    }

    public function loginUrl($absolute = false)
    {
        $is_from_template = waConfig::get('is_template');
        waConfig::set('is_template', null);
        try {
            $auth_config = waDomainAuthConfig::factory();
            $url = $auth_config->getLoginUrl(array(), $absolute);
        } catch (Exception $e) {
            $url = '';
        }
        waConfig::set('is_template', $is_from_template);
        return $url;
    }

    public function forgotPasswordUrl($absolute = false)
    {
        $is_from_template = waConfig::get('is_template');
        waConfig::set('is_template', null);
        try {
            $auth_config = waDomainAuthConfig::factory();
            $url = $auth_config->getForgotPasswordUrl(array(), $absolute);
        } catch (Exception $e) {
            $url = '';
        }
        waConfig::set('is_template', $is_from_template);
        return $url;
    }

    /**
     * Show login form
     *
     * @param string|array $errors initial errors to display
     *
     * @param array $options
     *
     *   bool   'show_title' - need or not to show own title. Default - FALSE
     *
     *   bool   'show_sub_title' - need or not to show own title. Default - FALSE
     *
     *   bool   'show_oauth_adapters' - need or not to show html block of o-auth adapters - Eg vk, facebook etc. Default - FALSE
     *
     *   bool   'need_redirects' - need or not server trigger redirects. Default - TRUE
     *
     *   bool   'include_css' - include or not default css. Default - TRUE
     *
     *   string 'url' - custom url of login action. Default (if skip option) - login_url from proper auth config. You can also pass empty string ''
     *
     * @return string
     */
    public function loginForm($errors = array(), $options = array())
    {
        $options = is_array($options) ? $options : array();

        if (is_scalar($errors)) {
            $error = (string)$errors;
            $errors = array();
            if (strlen($error) > 0) {
                $errors = array('' => $error);
            }
        } else {
            $errors = is_array($errors) ? $errors : array();
        }

        $data = wa()->getRequest()->post();

        $is_from_template = waConfig::get('is_template');
        waConfig::set('is_template', null);

        try {

            $renderer = new waFrontendLoginForm($options);

            $ns = $renderer->getNamespace();
            if (is_array($data) && isset($data[$ns]) && is_array($data[$ns])) {
                $data = $data[$ns];
            }

            $html = $renderer->render($data, $errors);
        } catch (Exception $e) {
            $html = '';
        }

        waConfig::set('is_template', $is_from_template);

        return $html;
    }

    /**
     *
     * Show forgot password form
     * First step form in recovery password process
     *
     * @param string|array $errors initial errors to display
     *
     * @param array $options
     *
     *   bool   'show_title' - need or not to show own title. Default - FALSE
     *
     *   bool   'show_sub_title' - need or not to show own title. Default - FALSE
     *
     *   bool   'show_oauth_adapters' - need or not to show html block of o-auth adapters - Eg vk, facebook etc. Default - FALSE
     *
     *   bool   'need_redirects' - need or not server trigger redirects. Default - TRUE
     *
     *   bool   'include_css' - include or not default css. Default - TRUE
     * @return string
     *
     */
    public function forgotPasswordForm($errors = array(), $options = array())
    {
        if (is_scalar($errors)) {
            $errors = array('' => (string)$errors);
        } else {
            $errors = is_array($errors) ? $errors : array();
        }

        $data = wa()->getRequest()->post();


        $is_from_template = waConfig::get('is_template');
        waConfig::set('is_template', null);

        try {

            $renderer = new waFrontendForgotPasswordForm($options);

            $ns = $renderer->getNamespace();
            if (is_array($data) && isset($data[$ns]) && is_array($data[$ns])) {
                $data = $data[$ns];
            }

            $html = $renderer->render($data, $errors);
        } catch (Exception $e) {
            $html = '';
        }

        waConfig::set('is_template', $is_from_template);

        return $html;
    }

    /**
     *
     * Show set password form
     * Second step form in recovery password process
     *
     * @param string|array $errors initial errors to display
     *
     * @param array $options
     *
     *   bool   'show_title' - need or not to show own title. Default - FALSE
     *
     *   bool   'show_sub_title' - need or not to show own title. Default - FALSE
     *
     *   bool   'show_oauth_adapters' - need or not to show html block of o-auth adapters - Eg vk, facebook etc. Default - FALSE
     *
     *   bool   'need_redirects' - need or not server trigger redirects. Default - TRUE
     *
     *   bool   'include_css' - include or not default css. Default - TRUE
     *
     *   string 'url' - custom url of login action. Default (if skip option) - login_url from proper auth config. You can also pass empty string ''
     *
     * @return string
     *
     */
    public function setPasswordForm($errors = array(), $options = array())
    {
        if (is_scalar($errors)) {
            $errors = array('' => (string)$errors);
        } else {
            $errors = is_array($errors) ? $errors : array();
        }

        $data = wa()->getRequest()->post();

        $is_from_template = waConfig::get('is_template');
        waConfig::set('is_template', null);

        try {

            $renderer = new waFrontendSetPasswordForm($options);

            $ns = $renderer->getNamespace();
            if (is_array($data) && isset($data[$ns]) && is_array($data[$ns])) {
                $data = $data[$ns];
            }

            $html = $renderer->render($data, $errors);
        } catch (Exception $e) {
            $html = '';
        }

        waConfig::set('is_template', $is_from_template);

        return $html;
    }

    /**
     * @param array $errors
     * @return array
     * @throws waException
     * @deprecated since version 1.10
     */
    public function signupFields($errors = array())
    {
        $config = wa()->getAuthConfig();
        $config_fields = isset($config['fields']) ? $config['fields'] : array(
            'firstname',
            'lastname',
            '',
            'email'    => array('required' => true),
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

    public function signupUrl($absolute = false)
    {
        $is_from_template = waConfig::get('is_template');
        waConfig::set('is_template', null);

        try {
            $config = waDomainAuthConfig::factory();
            $url = $config->getSignUpUrl(array(), $absolute);
        } catch (Exception $e) {
            $url = '';
        }

        waConfig::set('is_template', $is_from_template);

        return $url;

    }

    /**
     * @param array $errors
     * @param array $options
     *
     *   bool   'show_title' - need show own title. Default - FALSE
     *
     *   bool   'show_oauth_adapters' - need show html block of o-auth adapters - Eg vk, facebook etc. Default - FALSE
     **
     *   bool   'need_redirects' - need server trigger redirects. Default - TRUE
     *
     *   string 'contact_type' - what type of contact to create 'person' or 'company'. Default - 'person'
     *
     *   bool   'include_css' - include or not default css. Default - TRUE
     *
     * @return mixed|string
     */
    public function signupForm($errors = array(), $options = array())
    {
        $options = is_array($options) ? $options : array();

        if (is_scalar($errors)) {
            $error = (string)$errors;
            $errors = array();
            if (strlen($error) > 0) {
                $errors = array('' => $error);
            }
        } else {
            $errors = is_array($errors) ? $errors : array();
        }

        $is_from_template = waConfig::get('is_template');
        waConfig::set('is_template', null);

        try {
            $data = wa()->getRequest()->post();

            $form = waSignupForm::factory($options);

            $ns = $form->getNamespace();
            if (is_array($data) && isset($data[$ns]) && is_array($data[$ns])) {
                $data = $data[$ns];
            }

            $html = $form->render($data, $errors);
        } catch (Exception $e) {
            waLog::log($e->getMessage().PHP_EOL.$e->getTraceAsString());
            $html = '';
        }

        waConfig::set('is_template', $is_from_template);

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
        $view = wa()->getView();
        $template = wa()->getConfig()->getRootPath().'/wa-system/auth/templates/adapters_list.html';
        $view->assign(array(
            'adapters' => $adapters,
            'options'  => $options,
        ));
        $html = $view->fetch($template);
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

        try {
            $data = $auth->getUserData($token);
        } catch (waException $e) {
            return false;
        }
        if (empty($data)) {
            return false;
        }

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
            'sort'  => 0
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
                    'field'      => $data['source'].'_id',
                    'value'      => $data['source_id'],
                    'sort'       => 0
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
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
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

    public function contactProfileTabs($id, $options = array())
    {
        if (!wa_is_int($id)) {
            throw new waException('bad parameters', 500);
        }

        $tabs = ifset($options['tabs']);
        if (!is_array($tabs)) {
            $tabs = $this->getContactTabs((int)$id);
        }

        $selected_tab = ifset($options['selected_tab']);
        if (!$selected_tab) {
            $selected_tab = key($tabs);
        }

        $legacy_suffix = '';
        if ($this->whichUI() == '1.3') {
            $legacy_suffix = '-legacy';
        }

        $view = wa()->getView();
        $view->assign(array(
            'profile_content_layout_template' => wa()->getAppPath('templates/actions'.$legacy_suffix.'/profile/ProfileContent.html', 'webasyst'),
            'uniqid'                          => str_replace('.', '-', uniqid('s', true)),
            'selected_tab'                    => $selected_tab,
            'contact_id'                      => $id,
            'tabs'                            => $tabs,
        ));

        $template_file = $this->getConfig()->getConfigPath('ProfileTabs.html', true, 'webasyst');
        if (file_exists($template_file)) {
            return $view->fetch('file:'.$template_file);
        } else {
            return $view->fetch(wa()->getAppPath('templates/actions'.$legacy_suffix.'/profile/ProfileTabs.html', 'webasyst'));
        }
    }

    public function getContactTabs($id)
    {
        $id = (int)$id;
        if (!$id || wa()->getEnv() !== 'backend') {
            return array();
        }

        // Before trigger event, temporary turn-off 'is_template' flag, cause some of handlers might call static function that protected from calling by template file
        $is_template = waConfig::get('is_template');
        if ($is_template) {
            waConfig::set('is_template', null);
        }

        // Tabs of 'Team' app should always be on the left
        $event_result = wa()->event(array('contacts', 'profile.tab'), $id);

        // restore is_template flag
        if ($is_template) {
            waConfig::get('is_template', $is_template);
        }

        if (!empty($event_result['team'])) {
            $event_result = array(
                    'team' => $event_result['team'],
                ) + $event_result;
        }

        $links = array();
        foreach ($event_result as $plugin_app_id => $one_or_more_links) {
            if (isset($one_or_more_links['html']) || isset($one_or_more_links['url']) || isset($one_or_more_links['id'])) {
                $one_or_more_links = array($one_or_more_links);
            }

            // App to check access rights
            $app_id = $plugin_app_id;
            if (substr($app_id, -7) === '-plugin') {
                $app_id = 'contacts';
            }

            $i = '';
            foreach ($one_or_more_links as $link) {
                while (empty($link['id']) || isset($links[$link['id']])) {
                    $link['id'] = $plugin_app_id.$i;
                    $i++;
                }

                // Do not show tabs user has no access to and would not be able to load
                if (!empty($link['url']) && !wa()->getUser()->getRights($app_id, 'backend')) {
                    continue;
                }

                $links[$link['id']] = $link + array(
                        'url'   => '',
                        'title' => '',
                        'count' => '',
                        'html'  => '',
                    );
            }
        }

        return $links;
    }

    public function getCdn($url = null)
    {
        return wa()->getCdn($url);
    }

    /**
     * Which UI version supported current app
     * @param string|null|false $app_id - app for which need to check webasyst UI version, default is current app (null)
     *                                  If pass FALSE will returns version from cookie, ignoring app ui option in app config
     * @return string '1.3' or '2.0'
     * @throws waException
     */
    public function whichUI($app_id = null)
    {
        return wa()->whichUI($app_id);
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
