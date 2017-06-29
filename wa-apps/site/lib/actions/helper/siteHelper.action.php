<?php

class siteHelperAction extends waViewAction
{
    public function execute()
    {

        $wa_vars = array(
                    '$wa_url' => _w('URL of this Webasyst installation (relative).'),
                    '$wa_app_url' => _w('URL of the current app settlement (relative).'),
                    '$wa_backend_url' => _w('URL to access Webasyst backend (relative).'),
                    '$wa_theme_url' => _w('URL of the current app’s design theme folder (relative).'),

                    '$wa->title()' => _w('Title'),
                    '$wa->title("<em>title</em>")' => _w('Assigns a new title.'),
                    '$wa->accountName(bool <em>$escape</em>)' => _w('Returns name of this Webasyst installation specified in Installer app settings.<br><em>escape = false</em> requires to embed special characters in account name instead of displaying them.'),
                    '$wa->apps()' => _w('Returns this site’s core navigation menu which is either defined automatically or manually in the Site‘s “Settings” screen.'),
                    '$wa->currentUrl(bool <em>$absolute</em>, bool <em>$without_params</em>)' => _w('Returns current page URL.<br><em>$absolute = true</em> requires to return an absolute URL instead of a relative one.<br><em>$without_params = true</em> requires to return current URL without GET parameters.'),
                    '$wa->domainUrl()' => _w('Returns this domain’s root URL (absolute).'),
                    '$wa->globals("<em>key</em>")' => _w('Returns value of the global var by <em>key</em>. Global var array is initially empty, and can be used arbitrarily.'),
                    '$wa->globals("<em>key</em>", "<em>value</em>")' => _w('Assigns a new value to a global variable.'),
                    '$wa->isMobile()' => _w('Based on current session data, returns <em>true</em> or <em>false</em> depending on whether a multi-touch mobile device is used. If no session variable indicates the requested website version (mobile or desktop), User Agent information is used.'),
                    '$wa->locale()' => _w('Returns user locale; e.g., “en_US”, “ru_RU”. If the user is authorized, the locale is retrieved from the user’s record in the Contacts app or is detected automatically otherwise.'),
                    '$wa->get("<em>key</em>", "<em>default</em>")' => _w('Returns GET parameter value (same as PHP $_GET["<em>key</em>"]).<br>"<em>default</em>" is an optional default value which is returned if specified GET parameter is not found.'),
                    '$wa->post("<em>key</em>", "<em>default</em>")' => _w('Returns POST parameter value (same as $_POST["<em>key</em>"] in PHP).<br>"<em>default</em>" is an optional default value which is returned if specified POST parameter is not found.'),
                    '$wa->server("<em>key</em>", "<em>default</em>")' => _w('Returns SERVER parameter value (same as $_SERVER["<em>KEY</em>"] in PHP).<br>"<em>default</em>" is an optional default value which is returned if specified server parameter is not found.'),
                    '$wa->session("<em>key</em>", "<em>default</em>")' => _w('Returns SESSION var value (same as $_SESSION["<em>key</em>"] in PHP).<br>"<em>default</em>" is an optional default value which is returned if specified session parameter is not found.'),
                    '$wa->block("<em>id</em>", "<em>$params</em>")' => _w('Embeds HTML block by ID.<br><em>$params</em> is array of Smarty variables to be passed to block contents.'),
                    '$wa->user("<em>field</em>", "<em>format</em>")' => _w('Returns authorized user‘s data. "<em>field</em>" (string) is optional and indicates the field id to be returned. Returns <em>false</em> if user is not authorized.<br><em>$format</em> parameter (defaults to "html") defines the <a href="https://developers.webasyst.com/cookbook/basics/classes/waContact/#method-get" target="_blank">format</a> of the returned value.'),
                    '$wa->userAgent("<em>key</em>")' => _w('Returns User Agent info by specified “<em>key</em>” parameter:').'<br />'.
                        _w('— <em>"platform"</em>: current visitor device platform name, e.g. <em>windows, mac, linux, ios, android, blackberry</em>;').'<br />'.
                        _w('— <em>"isMobile"</em>: returns <em>true</em> or <em>false</em> if user is using a multi-touch mobile device (iOS, Android and similar), based solely on User Agent string;'),
                    '$wa-><em>app_id</em>->themePath("<em>theme_id</em>")' => _w('Returns path to design theme folder by <em>app_id</em> and <em>theme_id</em>.'),
                    '$wa-><em>app_id</em>->themeUrl("<em>theme_id</em>")' => _w('Returns current URL of design theme folder by <em>app_id</em> and <em>theme_id</em>.'),
                    '$wa-><em>app_id</em>->page(<em>$id</em>)' => _w('Returns data array of a page by specified <em>app_id</em> and page <em>id</em>.'),
                    '$wa-><em>app_id</em>->pages(<em>$parent_id</em>, bool <em>$with_params</em>)' => _w('Returns array of published pages set up in the app with specified <em>app_id</em>.<br><em>$parent_id</em> denotes the ID of the parent page whose subpages must be returned. If <em>0</em> is specified (default value), all app‘s pages are returned.<br><em>$with_params = false</em> means that pages are returned their without custom parameters. By default (<em>true</em>), custom parameters are returned.'),
        );

        $app_id = waRequest::get('app');
        $file = waRequest::get('file');
        $vars = array();
        if ($app_id) {
            $app = wa()->getAppInfo($app_id);
            $path = $this->getConfig()->getAppsPath($app_id, 'lib/config/site.php');
            if (file_exists($path)) {
                $site = include($path);
                if (isset($site['vars'])) {
                    if (isset($site['vars'][$file])) {
                        $vars += $site['vars'][$file];
                    }
                    if (isset($site['vars']['$wa'])) {
                        $vars += $site['vars']['$wa'];
                    }
                    if (isset($site['vars']['all'])) {
                        $vars += $site['vars']['all'];
                    }
                }
            }
            if ($app_id == 'site' && ($id = waRequest::get('id'))) {
                $page_model = new sitePageModel();
                $page = $page_model->getById($id);
                $file = $page['name'];
                $vars += $site['vars']['page.html'];
            }
        } else {
            $app = null;
        }

        $this->view->assign('vars', $vars);
        $this->view->assign('file', $file);
        $this->view->assign('app', $app);
        $this->view->assign('wa_vars', $wa_vars);
        $this->view->assign('smarty_vars', array(
            '{$foo}' => _w('Displays a simple variable (non array/object)'),
            '{$foo[4]}' => _w('Displays the 5th element of a zero-indexed array'),
            '{$foo.bar}' => _w('Displays the "<em>bar</em>" key value of an array. Similar to PHP $foo["bar"]'),
            '{$foo.$bar}' => _w('Displays variable key value of an array. Similar to PHP $foo[$bar]'),
            '{$foo->bar}' => _w('Displays the object property named <em>bar</em>'),
            '{$foo->bar()}' => _w('Displays the return value of object method named <em>bar()</em>'),
            '{$foo|print_r}' => _w('Displays structured information about variable. Arrays and objects are explored recursively with values indented to show structure. Similar to PHP var_dump($foo)'),
            '{$foo|escape}' => _w('Escapes a variable for safe display in HTML'),
            '{$foo|wa_datetime:$format}' => _w('Outputs <em>$var</em> datetime in a user-friendly form. Supported <em>$format</em> values: <em>monthdate, date, dtime, datetime, fulldatetime, time, fulltime, humandate, humandatetime</em>'),
            '{$x+$y}' => _w('Outputs the sum of <em>$x</em> and <em>$y</em>'),
            '{$foo=3*4}' => _w('Assigns variable a value'),
            '{time()}' => _w('Direct PHP function access. E.g. <em>{time()}</em> displays the current timestamp'),
            '{literal}...{/literal}' => _w('Content between {literal} tags will not be parsed by Smarty'),
            '{include file="..."}' => _w('Embeds a Smarty template into the current content. <em>file</em> attribute specifies a template filename within the current design theme folder'),
            '{if}...{else}...{/if}' => _w('Similar to PHP if statements'),
            '{foreach from=$a key=k item=v}...{foreachelse}...{/foreach}' => _w('{foreach} is for looping over arrays of data'),
        ));

        $model = new siteBlockModel();
        $blocks = $model->order('sort')->fetchAll('id');

        $active_app = wa()->getApp();
        $apps = wa()->getApps();
        foreach ($apps as $app_id => $app) {
            $path = $this->getConfig()->getAppsPath($app_id, 'lib/config/site.php');
            if (file_exists($path)) {
                waLocale::load(wa()->getLocale(), $this->getConfig()->getAppsPath($app_id, 'locale'), $app_id, true);
                $site_config = include($path);
                if (!empty($site_config['blocks'])) {
                    foreach ($site_config['blocks'] as $block_id => $block) {
                        if (!is_array($block)) {
                            $block = array('content' => $block, 'description' => '');
                        }
                        $block_id = $app_id.'.'.$block_id;
                        if (!isset($blocks[$block_id])) {
                            $block['id'] = $block_id;
                            $block['app'] = $app;
                            $blocks[$block_id] = $block;
                        }
                    }
                }
            }
        }
        wa()->setActive($active_app);
        $this->view->assign('blocks', $blocks);
    }
}