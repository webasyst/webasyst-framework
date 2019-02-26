<?php

/**
 * Class webasystBackendCheatSheetActions
 *
 * Purpose:
 *
 * 1) Can embed plug-in tips into the Webasyst basic cheat sheet.
 * If your plugin can be used in the design editor or the page editor, you can integrate into the webasyst cheat sheet.
 * To do this, create site.php in the folder of your plugin in ../lib/config/
 * It must return an array with the vars key. E.g. array('vars' => array('page.html => 'Text'))
 *
 * 2) You can change the work of the cheat sheet. No restrictions.
 * You can make your cheat sheet design and place it in your plugin at '../templates/cheatSheet.html'
 * E.g.:
      a) You need to insert the help menu on the page
      b) Your plugin uses a text editor and you want to use only your variables.
 *
 * How it works (with basic settings):
 * 1) The "Cheat Sheet" button is added to the page. When connected, an array of options is passed. Together with the js button, the JS object returns $.cheatsheet.you_cheatsheet_name
 * 2) Each cheat sheet in the object has three methods: 1) getHelpEvent, 2) insertEvent, 3) init.
 * 3) After the object is declared, the trigger is called 'wa_cheatsheet_init.you_cheatsheet_name'. This is necessary so that you can reassign the functions of the object
 * 4) The body of the cheat sheet is formed based on the settings that live in the $.cheatsheet.you_cheatsheet_name.data property
 *
 *
 * How to connect:
 * There are 3 ways to connect to the template:
    1) Directly request the "Button" - /webasyst?module=backendCheatSheet&action=button
    2) Directly request the Cheat Sheet body - /webasyst?module=backendCheatSheet&action=cheatSheet
    3) Calling the getCheetSheetButton method from waView - {$wa->getCheatSheetButton()}.
 *
 * Options:
 * 1) 'name' - Attention! Name 'webasyst' reserved. The name by which your cheat sheet will be available in the $.cheatsheet array. It will generate identifiers on the page.
 * 2) 'app' - Id of the application. Need to find cheat sheets of plugins
 * 3) 'plugin_id' - Show only the requested plugin. Does not work without 'app'.
 * 4) 'only_plugin' - Will show the plugin without standard Webasyst prompts. Does not work without 'app'. Takes a boolean value - 1/0
 * 5) 'custom_template' - Connect the plugin template. Does not work without 'app' and 'plugin_id'. Takes a boolean value - 1/0
 * 6) 'key' - The key/s in the array vars. Can be a string or array.
 * 7) 'only_key' - From the array vars only the requested keys will be taken. Takes a boolean value - 1/0
 * 8) 'page_id' - Get page name and page.html from vars array.
 *
 * If the parameters are not sent, cheat sheet from all applications will be collected, ignoring the plug-ins
 *
 * Request format:
    a) If you call a "Button" then all parameters must be in the "options" array.
    E.g.:
        1) Smarty - {$wa->getCheatSheetButton(["name" => "webasyst", "app" => {$wa->app()}])}
        2) Js - $("#wa-editor-help-webasyst").load('/webasyst?module=backendCheatSheet&action=button', {options : { name: 'webasyst', app: 'shop'}}, function () {})

    b) If you call a Cheat Sheet, send options to POST or GET array
    E.g.:
        1) $("#wa-editor-help-webasyst").load('/webasyst?module=backendCheatSheet&action=cheatSheet', { name: 'webasyst', app: 'shop'}, function () {})
 *
 * Vars format:
 * 1) All keys must be in the vars array.
 * 2) Keys "fix" and "$wa" always returned if "only_key" == 0
 *
 */
class webasystBackendCheatSheetActions extends waActions
{
    protected $fields = array();

    public function buttonAction($options = array())
    {
        if (!$options) {
            $options = waRequest::request('options', array(), waRequest::TYPE_ARRAY);
        }

        $assign = array(
            'cheat_sheet_name' => ifset($options, 'name', 'webasyst'),
            'data'             => ifset($options, array())
        );

        return $this->display($assign, $this->getConfig()->getRootPath().'/wa-system/page/templates/Button.html');
    }

    public function cheatSheetAction()
    {
        $name = waRequest::request('name', 'webasyst', waRequest::TYPE_STRING);
        $app = waRequest::request('app', null, waRequest::TYPE_STRING);
        $plugin_id = waRequest::request('plugin_id', null, waRequest::TYPE_STRING);
        $only_plugin = waRequest::request('only_plugin', 0, waRequest::TYPE_INT);
        $custom_template = waRequest::request('custom_template', null, waRequest::TYPE_STRING);

        $template = $this->getConfig()->getRootPath().'/wa-system/page/templates/Help.html';

        if (empty($only_plugin) || (int)$only_plugin === 0) {
            $assign = array(
                'vars'        => $this->getVars(),
                'tab_names'   => $this->getVarsTabNames(),
                'page'        => ifset($this->fields, 'page', null),
                'apps_info'   => wa()->getApps(true),
                'app_id'      => $app,
                'name'        => $name,
                'blocks'      => $this->getBlocks(),
                'plugins'     => $this->getPluginsVars(),
                'wa_vars'     => $this->getWaVars(),
                'smarty_vars' => $this->getSmartyVars(),
            );
        } else {
            $assign = array(
                'plugins' => $this->getPluginsVars(),
                'name'    => $name,
            );
        }

        //Set plugin template to cheat sheet
        if ($app && $plugin_id && $custom_template) {
            $custom_template_path = wa($app)->getConfig()->getPluginPath($plugin_id).'/templates/cheatSheet.html';

            if (!file_exists($custom_template_path)) {
                throw new waException('Plugin template not found');
            }
            $template = $custom_template_path;
        }

        return $this->display($assign, $template);
    }

    protected function getVars()
    {
        $app = waRequest::request('app', null, waRequest::TYPE_STRING);
        $all_apps_site_config = $this->getCacheAppsSiteConfig();
        $vars = array();

        if ($app) {
            $site_config = ifset($all_apps_site_config, $app, array());
            $var = $this->varsParser($site_config);
            if (!empty($var)) {
                $vars[$app] = $var;
            }
        } else {
            if ($all_apps_site_config) {
                foreach ($all_apps_site_config as $app_id => $site_config) {
                    $var = $this->varsParser($site_config);
                    if (!empty($var)) {
                        $vars[$app_id] = $var;
                    }
                }
            }
        }

        return $vars;
    }

    /**
     * @return array|null
     */
    protected function getVarsTabNames()
    {
        $app = waRequest::request('app', null, waRequest::TYPE_STRING);
        $all_apps_site_config = $this->getCacheAppsSiteConfig();

        $all_apps_site_config = is_array($all_apps_site_config) ? $all_apps_site_config : array();

        $names = array();

        if ($app) {
            $site_config = ifset($all_apps_site_config, $app, array());
            $name = $this->extractVarsTabName($site_config);
            if (!empty($name)) {
                $names[$app] = $name;
            }
        } else {
            foreach ($all_apps_site_config as $app_id => $site_config) {
                $name = $this->extractVarsTabName($site_config);
                if (!empty($name)) {
                    $names[$app_id] = $name;
                }
            }
        }

        return $names;
    }

    /**
     * @param array $site_config
     * @return null|string
     */
    protected function extractVarsTabName($site_config)
    {
        $key = waRequest::request('key', null);

        if (empty($key)) {
            return null;
        }

        $vars_tab_names_presented = $site_config && is_array($site_config) && isset($site_config['vars_tab_names']) &&
            is_array($site_config['vars_tab_names']);

        if (!$vars_tab_names_presented) {
            return null;
        }

        return isset($site_config['vars_tab_names'][$key]) ? $site_config['vars_tab_names'][$key] : null;
    }

    protected function varsParser($site_config)
    {
        $key = waRequest::request('key', null);
        $only_key = waRequest::request('only_key', 0, waRequest::TYPE_INT);
        $app = waRequest::request('app', null, waRequest::TYPE_STRING);
        $page_id = waRequest::request('page_id', null, waRequest::TYPE_INT);

        $app_vars = array();

        if ($site_config && is_array($site_config) && isset($site_config['vars'])) {

            //Search vars by key name/s
            if (!empty($key)) {
                if (is_array($key)) {
                    foreach ($key as $item) {
                        if (!empty($site_config['vars'][$item])) {
                            $app_vars += $site_config['vars'][$item];
                        }
                    }
                } elseif (isset($site_config['vars'][$key])) {
                    $app_vars += $site_config['vars'][$key];
                }
            }

            if (isset($site_config['vars']['$wa']) && $only_key == 0) {
                $app_vars += $site_config['vars']['$wa'];
            }

            if (isset($site_config['vars']['fix']) && $only_key == 0) {
                $app_vars += $site_config['vars']['fix'];
            }

            if ($page_id && $only_key == 0) {
                $page_model_name = $app.'PageModel';
                wa($app);
                if (class_exists($page_model_name)) {
                    $page_model = new $page_model_name;
                    $page = $page_model->getById($page_id);
                    if ($page) {
                        $this->fields['page'] = $page['name'];
                        if (isset($site_config['vars']['page.html'])) {
                            $app_vars += $site_config['vars']['page.html'];
                        }
                    }

                }
            }
        }

        return $app_vars;
    }

    protected function getBlocks()
    {
        $apps_site_config = $this->getCacheAppsSiteConfig();
        $blocks = array();

        if (wa()->appExists('site')) {
            try {
                //get all Block
                $waModel = new waModel();
                $blocks = $waModel->query('SELECT * FROM site_block ORDER BY sort')->fetchAll('id');
                if ($apps_site_config) {
                    foreach ($apps_site_config as $_app_id => $_app) {
                        wa($_app_id, 1);
                        if (!empty($_app['blocks'])) {
                            foreach ($_app['blocks'] as $block_id => $block) {
                                if (!is_array($block)) {
                                    $block = array('content' => $block, 'description' => '');
                                }
                                $block_id = $_app_id.'.'.$block_id;
                                if (!isset($blocks[$block_id])) {
                                    $block['id'] = $block_id;
                                    $block['app'] = $_app;
                                    $blocks[$block_id] = $block;
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e) {
            }
        }

        return $blocks;
    }

    protected function getPluginsVars()
    {
        $app = waRequest::request('app');
        $plugin_id = waRequest::request('plugin_id', null, waRequest::TYPE_STRING);

        $plugins_vars = array();
        $plugins_site_config = $this->getCachePluginsSiteConfig();

        if ($app && $plugins_site_config) {

            //Get vars for all plugins
            if (empty($plugin_id) && !empty($plugins_site_config)) {
                foreach ($plugins_site_config as $app_id => $site_config) {
                    $vars = $this->varsParser($site_config);
                    if (!empty($vars)) {
                        $plugins_vars[$app_id] = $vars;
                    }
                }
            } else {
                //Get vars for the requested plugin
                $site_config = ifset($plugins_site_config, $plugin_id, array());
                $plugins_vars[$plugin_id] = $this->varsParser($site_config);
            }
        }

        return $plugins_vars;
    }

    protected function getCachePluginsSiteConfig()
    {
        $locale = wa()->getLocale();
        $app = waRequest::request('app');
        $app_plugins_site_config = array();

        if ($app) {
            $cache_plugins = new waVarExportCache($app.'_plugins_site_config_'.$locale, 86400, 'webasyst/backend/cheatsheet');
            $app_plugins_site_config = $cache_plugins->get($app.'_plugins_site_config_'.$locale);

            if ($app_plugins_site_config === null) {
                $plugins = wa($app)->getConfig()->getPlugins();
                if (!empty($plugins)) {
                    foreach ($plugins as $id => $data) {
                        $plugin_path = wa($app, 1)->getConfig()->getPluginPath($id).'/lib/config/site.php';
                        if (file_exists($plugin_path)) {
                            //Set localization
                            waSystem::pushActivePlugin($id);
                            $app_plugins_site_config[$id] = include($plugin_path);
                            //Unset
                            waSystem::popActivePlugin();
                        }
                    }
                }
                $cache_plugins->set($app_plugins_site_config);
            }
        }

        return $app_plugins_site_config;
    }

    protected function getCacheAppsSiteConfig()
    {
        $locale = wa()->getLocale();
        $cache_apps = new waVarExportCache('cheat_sheet_apps_'.$locale, 86400, 'webasyst/backend/cheatsheet');
        $all_apps_site_config = $cache_apps->get('cheat_sheet_apps_'.$locale);

        //If null cache not set.
        if ($all_apps_site_config === null) {
            foreach (wa()->getApps(true) as $_app_id => $_app) {
                //Set app name and other data
                $path = $this->getConfig()->getAppsPath($_app_id, 'lib/config/site.php');
                if (file_exists($path)) {
                    wa($_app_id, 1);
                    $all_apps_site_config[$_app_id] = include($path);
                }
            }
            $cache_apps->set($all_apps_site_config);
        }

        return $all_apps_site_config;
    }

    public function getWaVars()
    {
        return array(
            '$wa_url'                                                                      => _ws('URL of this Webasyst installation (relative)'),
            '$wa_app_url'                                                                  => _ws('URL of the current app settlement (relative)'),
            '$wa_backend_url'                                                              => _ws('URL to access Webasyst backend (relative)'),
            '$wa_theme_url'                                                                => _ws('URL of the current app design theme folder (relative)'),
            '$wa->title()'                                                                 => _ws('Title'),
            '$wa->title("<em>title</em>")'                                                 => _ws('Assigns a new title'),
            '$wa->accountName()'                                                           => _ws('Returns name of this Webasyst installation (name is specified in “Installer” app settings)'),
            '$wa->apps()'                                                                  => _ws('Returns this site’s core navigation menu which is either set automatically or manually in the “Site settings” screen'),
            '$wa->currentUrl(bool <em>$absolute</em>)'                                     => _ws('Returns current page URL (either absolute or relative)'),
            '$wa->domainUrl()'                                                             => _ws('Returns this domain’s root URL (absolute)'),
            '$wa->globals("<em>key</em>")'                                                 => _ws('Returns value of the global var by <em>key</em>. Global var array is initially empty, and can be used arbitrarily.'),
            '$wa->globals("<em>key</em>", "<em>value</em>")'                               => _ws('Assigns global var a new value'),
            '$wa->get("<em>key</em>")'                                                     => _ws('Returns GET parameter value (same as PHP $_GET["<em>key</em>"])'),
            '$wa->isMobile()'                                                              => _ws('Based on current session data returns <em>true</em> or <em>false</em> if user is using a multi-touch mobile device; if no session var reflecting current website version (mobile or desktop) is available, User Agent information is used'),
            '$wa->locale()'                                                                => _ws('Returns user locale, e.g. "en_US", "ru_RU". In case user is authorized, locale is retrieved from “Contacts” app user record, or detected automatically otherwise'),
            '$wa->post("<em>key</em>")'                                                    => _ws('Returns POST parameter value (same as PHP $_POST["<em>key</em>"])'),
            '$wa->server("<em>key</em>")'                                                  => _ws('Returns SERVER parameter value (same as PHP $_SERVER["KEY"])'),
            '$wa->session("<em>key</em>")'                                                 => _ws('Returns SESSION var value (same as PHP $_SESSION["<em>key</em>"])'),
            '$wa->snippet("<em>id</em>")'                                                  => _ws('Embeds HTML snippet by ID'),
            '$wa->user("<em>field</em>")'                                                  => _ws('Returns authorized user data from associated record in “Contacts” app. "<em>field</em>" (string) is optional and indicates the field id to be returned. If not  Returns <em>false</em> if user is not authorized'),
            '$wa->userAgent("<em>key</em>")'                                               => _ws('Returns User Agent info by specified “<em>key</em>” parameter:').'<br />'.
                _ws('— <em>"platform"</em>: current visitor device platform name, e.g. <em>windows, mac, linux, ios, android, blackberry</em>;').'<br />'.
                _ws('— <em>"isMobile"</em>: returns <em>true</em> or <em>false</em> if user is using a multi-touch mobile device (iOS, Android and similar), based solely on User Agent string;').'<br />'.
                _ws('— not specified: returns entire User Agent string;').'<br />',
            '$wa-><em>APP_ID</em>->themePath("<em>theme_id</em>")'                         => _ws('Returns path to theme folder by <em>theme_id</em> and <em>APP_ID</em>'),
            '$wa-><em>app_id</em>->themeUrl("<em>theme_id</em>")'                          => _w('Returns current URL of design theme folder by <em>app_id</em> and <em>theme_id</em>.'),
            '$wa-><em>app_id</em>->page(<em>$id</em>)'                                     => _w('Returns data array of a page by specified <em>app_id</em> and page <em>id</em>.'),
            '$wa-><em>app_id</em>->pages(<em>$parent_id</em>, bool <em>$with_params</em>)' => _w('Returns array of published pages set up in the app with specified <em>app_id</em>.<br><em>$parent_id</em> denotes the ID of the parent page whose subpages must be returned. If <em>0</em> is specified (default value), all app‘s pages are returned.<br><em>$with_params = false</em> means that pages are returned their without custom parameters. By default (<em>true</em>), custom parameters are returned.'),
        );
    }

    public function getSmartyVars()
    {
        return array(
            '{$foo}'                                                => _ws('Displays a simple variable (non array/object)'),
            '{$foo[4]}'                                             => _ws('Displays the 5th element of a zero-indexed array'),
            '{$foo.bar}'                                            => _ws('Displays the "<em>bar</em>" key value of an array. Similar to PHP $foo["bar"]'),
            '{$foo.$bar}'                                           => _ws('Displays variable key value of an array. Similar to PHP $foo[$bar]'),
            '{$foo->bar}'                                           => _ws('Displays the object property named <em>bar</em>'),
            '{$foo->bar()}'                                         => _ws('Displays the return value of object method named <em>bar()</em>'),
            '{$foo|print_r}'                                        => _ws('Displays structured information about variable. Arrays and objects are explored recursively with values indented to show structure. Similar to PHP var_dump($foo)'),
            '{$foo|escape}'                                         => _ws('Escapes a variable for safe display in HTML'),
            '{$foo|wa_datetime:$format}'                            => _ws('Outputs <em>$var</em> datetime in a user-friendly form. Supported <em>$format</em> values: <em>monthdate, date, dtime, datetime, fulldatetime, time, fulltime, humandate, humandatetime</em>'),
            '{$x+$y}'                                               => _ws('Outputs the sum of <em>$x</em> and <em>$y</em>'),
            '{$foo=3*4}'                                            => _ws('Assigns variable a value'),
            '{time()}'                                              => _ws('Direct PHP function access. E.g. <em>{time()}</em> displays the current timestamp'),
            '{literal}...{/literal}'                                => _ws('Content between {literal} tags will not be parsed by Smarty'),
            '{include file="..."}'                                  => _ws('Embeds a Smarty template into the current content. <em>file</em> attribute specifies a template filename within the current design theme folder'),
            '{if}...{else}...{/if}'                                 => _ws('Similar to PHP if statements'),
            '{foreach $a as $k => $v}...{foreachelse}...{/foreach}' => _ws('{foreach} is for looping over arrays of data'),
        );
    }
}
