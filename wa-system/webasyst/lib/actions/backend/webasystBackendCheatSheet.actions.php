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
    3) Calling the getCheatSheetButton method from waView - {$wa->getCheatSheetButton()}.
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

        $this->setTemplate('Button.html', true);
        return $this->display($assign);
    }

    public function cheatSheetAction()
    {
        $name = waRequest::request('name', 'webasyst', waRequest::TYPE_STRING);
        $app = waRequest::request('app', null, waRequest::TYPE_STRING);
        $plugin_id = waRequest::request('plugin_id', null, waRequest::TYPE_STRING);
        $only_plugin = waRequest::request('only_plugin', 0, waRequest::TYPE_INT);
        $custom_template = waRequest::request('custom_template', null, waRequest::TYPE_STRING);

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
        $template = null;
        if ($app && $plugin_id && $custom_template) {
            $custom_template_path = wa($app)->getConfig()->getPluginPath($plugin_id).'/templates/cheatSheet.html';

            if (!file_exists($custom_template_path)) {
                throw new waException('Plugin template not found');
            }
            $template = $custom_template_path;
        }

        // set default template for this action, otherwise will be used custom template ($custom_template_path)
        if (!$template && !$this->template) {
            $this->setTemplate('Help.html', true);
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

    public function updateAppCacheConfig($app_id)
    {
        $locale = wa()->getLocale();
        $cache_apps = new waVarExportCache('cheat_sheet_apps_'.$locale, 86400, 'webasyst/backend/cheatsheet');
        $all_apps_site_config = $cache_apps->get('cheat_sheet_apps_'.$locale);

        if ($all_apps_site_config !== null) {
            $path = $this->getConfig()->getAppsPath($app_id, 'lib/config/site.php');
            if (file_exists($path)) {
                wa($app_id, 1);
                $all_apps_site_config[$app_id] = include($path);
            }
            $cache_apps->set($all_apps_site_config);
        }
    }

    public function getWaVars()
    {
        return array(
            '$wa_url'                                                                     => _ws('URL of this Webasyst installation (relative).'),
            '$wa_app_url'                                                                 => _ws('URL of the current app settlement (relative).'),
            '$wa_backend_url'                                                             => _ws('URL to access Webasyst backend (relative).'),
            '$wa_theme_url'                                                               => _ws('URL of the current app’s design theme directory (relative).'),
            '$wa->url(<em>$absolute</em>)'                                                => _ws('Returns a relative root URL, or absolute if the argument is set to <em>true</em>.'),
            '$wa->title()'                                                                => _ws('Returns the current page’s title.'),
            '$wa->title(<em>$title</em>)'                                                 => _ws('Sets a new page title.'),
            '$wa->meta(<em>$field</em>)'                                                  => _ws('Returns the value of a page meta tag. Supported meta tag names are "<em>title</em>" (&lt;title&gt; tag), "<em>description</em>" (description meta tag), "<em>keywords</em>" (keywords meta tag); e.g., <code>{$meta_description = $wa-&gt;meta("description")}</code>.'),
            '$wa->meta(<em>$field</em>, <em>$value</em>)'                                 => _ws('Sets a new meta tag value; e.g., <code>{$wa-&gt;meta("title", "My super page")}</code>.'),
            '$wa->accountName()'                                                          => _ws('Returns the value of system setting “Company name”.'),
            '$wa->apps()'                                                                 => _ws('Returns items of the current site’s navigation menu, which is either generated automatically or is set up manually in the “Site → Settings” screen.'),
            '$wa->currentUrl(<em>$absolute</em>)'                                         => _ws('Returns current page’s relative URL, or absolute if the argument is set to <em>true</em>.'),
            '$wa->domainUrl()'                                                            => _ws('Returns current domain’s root URL (absolute).'),
            '$wa->globals(<em>$key</em>)'                                                 => _ws('Returns the value of a global variable.'),
            '$wa->globals(<em>$key</em>, <em>$value</em>)'                                => _ws('Assigns a new value to a global variable.'),
            '$wa->isMobile()'                                                             => _ws('Returns <em>true</em> or <em>false</em> depending on whether a mobile device is used.'),
            '$wa->locale()'                                                               => _ws('Returns user’s locale; e.g., "en_US".'),
            '$wa->get(<em>$key</em>)'                                                     => _ws('Returns a GET parameter value.'),
            '$wa->post(<em>$key</em>)'                                                    => _ws('Returns a POST variable value.'),
            '$wa->server(<em>$key</em>)'                                                  => _ws('Returns a server variable value.'),
            '$wa->session(<em>$key</em>)'                                                 => _ws('Returns a session variable value.'),
            '$wa->user(<em>$field</em>)'                                                  => _ws('Returns authorized user’s field value. If no field value is specified then a user object is returned.'),
            '$wa->userId()'                                                               => _ws('Returns authorized user’s ID.'),
            '$wa->userAgent(<em>$key</em>)'                                               => _ws('Returns a User-Agent field value:').'<br>'.
                _ws('— <em>"platform"</em>: current visitor’s device platform name; e.g., <em>windows, mac, linux, ios, android, blackberry</em>;').'<br>'.
                _ws('— <em>"isMobile"</em>: returns <em>true</em> or <em>false</em> depending on whether a mobile device (iOS, Android and similar) is used, based on the User-Agent value;').'<br>'.
                _ws('— not specified: returns entire User Agent string;'),
            '$wa->setting(<em>$name</em>)'                                                => _ws('Returns the value of a current app’s setting.'),
            '$wa->setting(<em>$name</em>, <em>$default</em>)'                             => _ws('Returns the value of a current app’s setting, or a default value if the setting is empty.'),
            '$wa->setting(<em>$name</em>, <em>$default</em>, <em>$app_id</em>)'           => _ws('Returns the value of a specified app’s setting, or a default value if the setting is empty. To get a system setting’s value, specify "webasyst" as the app ID.'),
            '$wa->version(<em>$app_id</em>)'                                              => _ws('Returns the version number of a specified app. To get the Webasyst version, specify <em>true</em> instead of an app ID.'),
            '$wa-><em>app_id</em>->themePath(<em>$theme_id</em>)'                         => _ws('Returns the path to the design theme directory of a specified app.'),
            '$wa-><em>app_id</em>->themeUrl(<em>$theme_id</em>)'                          => _ws('Returns the current URL of a design theme directory of a specified app.'),
            '$wa-><em>app_id</em>->page(<em>$id</em>)'                                    => _ws('Returns the data array of an app’s page.'),
            '$wa-><em>app_id</em>->pages(<em>$parent_id</em>, <em>$with_params</em>)'     => _ws('Returns the array of published pages of a specified app.<br><br><em>$parent_id</em> is the ID of the parent page whose subpages must be returned. <em>0</em> means that all app’s pages must be returned.<br><br><em>$with_params</em> means whether pages must be returned with custom parameters specified in their settings.'),
        );
    }

    public function getSmartyVars()
    {
        return array(
            '{$foo}'                                                => _ws('Displays a simple variable (non array/object).'),
            '{$foo[4]}'                                             => _ws('Displays the 5th element of a zero-indexed array.'),
            '{$foo.bar}'                                            => _ws('Displays the "<em>bar</em>" key value of an array. Similar to PHP $foo["bar"].'),
            '{$foo.$bar}'                                           => _ws('Displays variable key value of an array. Similar to PHP $foo[$bar].'),
            '{$foo->bar}'                                           => _ws('Displays the object property named <em>bar</em>.'),
            '{$foo->bar()}'                                         => _ws('Displays the return value of object method named <em>bar()</em>.'),
            '{$foo|print_r}'                                        => _ws('Displays structured information about variable. Arrays and objects are explored recursively with values indented to show structure. Similar to PHP var_dump($foo).'),
            '{$foo|escape}'                                         => _ws('Escapes a variable for safe display in HTML.'),
            '{$foo|wa_datetime:$format}'                            => _ws('Outputs <em>$var</em> datetime in a user-friendly form. Supported <em>$format</em> values: <em>monthdate, date, dtime, datetime, fulldatetime, time, fulltime, humandate, humandatetime</em>.'),
            '{$x+$y}'                                               => _ws('Outputs the sum of <em>$x</em> and <em>$y</em>.'),
            '{$foo=3*4}'                                            => _ws('Assigns a value to a variable.'),
            '{time()}'                                              => _ws('Direct PHP function access. E.g., <em>{time()}</em> displays the current timestamp value.'),
            '{literal}...{/literal}'                                => _ws('Content between {literal} tags is not parsed by Smarty.'),
            '{include file="..."}'                                  => _ws('Embeds a Smarty template from a specified file in the current content. The <em>file</em> attribute must contain the path to a file.'),
            '{if}...{else}...{/if}'                                 => _ws('Similar to <em>if</em> statements in PHP.'),
            '{foreach $a as $k => $v}...{foreachelse}...{/foreach}' => _ws('Looping over arrays of data.'),
        );
    }

    /**
     * Default path of legacy templates for this action
     * @inheritDoc
     */
    protected function getLegacyTemplateDir()
    {
        return $this->getConfig()->getRootPath().'/wa-system/page/templates-legacy/';
    }

    /**
     * Default path of templates for this action
     * @inheritDoc
     */
    protected function getTemplateDir()
    {
        return $this->getConfig()->getRootPath().'/wa-system/page/templates/';
    }

    protected function whichUI($app_id = null)
    {
        $ui = $this->getRequest()->get('ui');

        // control UI version of cheat sheet UI block
        // it is all temporary
        if (!$ui) {
            return parent::whichUI($app_id);
        }

        $ui = $ui === '2.0' ? '2.0' : '1.3';
        return $ui;
    }
}
