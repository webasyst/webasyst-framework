<?php 

class siteHelperAction extends waViewAction
{
    public function execute()
    {
        
        $wa_vars = array(
                    '$wa_url' => _w('URL of this Webasyst installation (relative)'),
                    '$wa_app_url' => _w('URL of the current app settlement (relative)'),
                    '$wa_backend_url' => _w('URL to access Webasyst backend (relative)'),                    
                    '$wa_theme_url' => _w('URL of the current app design theme folder (relative)'),                    

                    '$wa->title()' => _w('Title'),
                    '$wa->title("<em>title</em>")' => _w('Assigns a new title'),
                    '$wa->accountName()' => _w('Returns name of this Webasyst installation (name is specified in “Installer” app settings)'),
                    '$wa->apps()' => _w('Returns this site’s core navigation menu which is either set automatically or manually in the “Site settings” screen'),
                    '$wa->currentUrl(bool <em>$absolute</em>)' => _w('Returns current page URL (either absolute or relative)'),
                    '$wa->domainUrl()' => _w('Returns this domain’s root URL (absolute)'),
                    '$wa->globals("<em>key</em>")' => _w('Returns value of the global var by <em>key</em>. Global var array is initially empty, and can be used arbitrarily.'),
                    '$wa->globals("<em>key</em>", "<em>value</em>")' => _w('Assigns global var a new value'),
                    '$wa->get("<em>key</em>")' => _w('Returns GET parameter value (same as PHP $_GET["<em>key</em>"])'),
                    '$wa->isMobile()' => _w('Based on current session data returns <em>true</em> or <em>false</em> if user is using a multi-touch mobile device; if no session var reflecting current website version (mobile or desktop) is available, User Agent information is used'),
                    '$wa->locale()' => _w('Returns user locale, e.g. "en_US", "ru_RU". In case user is authorized, locale is retrieved from “Contacts” app user record, or detected automatically otherwise'),
                    '$wa->post("<em>key</em>")' => _w('Returns POST parameter value (same as PHP $_POST["<em>key</em>"])'),
                    '$wa->server("<em>key</em>")' => _w('Returns SERVER parameter value (same as PHP $_SERVER["KEY"])'),
                    '$wa->session("<em>key</em>")' => _w('Returns SESSION var value (same as PHP $_SESSION["<em>key</em>"])'),
                    '$wa->block("<em>id</em>")' => _w('Embeds HTML block by ID'),
                    '$wa->user("<em>field</em>")' => _w('Returns authorized user data from associated record in “Contacts” app. "<em>field</em>" (string) is optional and indicates the field id to be returned. If not  Returns <em>false</em> if user is not authorized'),
                    '$wa->userAgent("<em>key</em>")' => _w('Returns User Agent info by specified “<em>key</em>” parameter:').'<br />'.
                    _w('— <em>"platform"</em>: current visitor device platform name, e.g. <em>windows, mac, linux, ios, android, blackberry</em>;').'<br />'.
                    _w('— <em>"isMobile"</em>: returns <em>true</em> or <em>false</em> if user is using a multi-touch mobile device (iOS, Android and similar), based solely on User Agent string;'),
                    '$wa-><em>APP_ID</em>->themePath("<em>theme_id</em>")' => _w('Returns path to theme folder by <em>theme_id</em> and <em>APP_ID</em>'),
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

        $apps = wa()->getApps();
        foreach ($apps as $app_id => $app) {
            $path = $this->getConfig()->getAppsPath($app_id, 'lib/config/site.php');
            if (file_exists($path)) {
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
        $this->view->assign('blocks', $blocks);
    }
}