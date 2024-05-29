<?php

class webasystCreateAppCli extends webasystCreateCliController
{
    protected function initPath()
    {
        parent::initPath();
        $this->path = wa()->getAppPath(null, $this->app_id).'/';
    }

    protected function verifyParams($params = array())
    {
        $errors = array();
        if (!empty($params['version']) && !preg_match('@^[\d]+(\.\d+)*$@', $params['version'])) {
            $errors[] = 'Invalid version format '.var_export($params['version'], true);
        }
        if (!empty($params['api']) && ($params['api'] !== true) && !preg_match('@^v?[\d]+(\.\d+)*$@', $params['api'])) {
            $errors[] = 'Invalid API version format '.var_export($params['api'], true);
        }
        if (file_exists($this->path)) {
            $errors[] = "App with id '{$this->app_id}' already exists.";
        }
        return $errors;
    }

    protected function create($params = array())
    {
        //TODO layout|ajax|simple mode
        $structure = array(
            "css/{$this->app_id}.css",
            "js/{$this->app_id}.js",
            "img/",
            "img/{$this->app_id}48.png"                             => $this->root_path.'wa-content/img/dummy-app-icon-48.png',
            "img/{$this->app_id}96.png"                             => $this->root_path.'wa-content/img/dummy-app-icon-96.png',
            "lib/",
            "lib/actions/backend/",
            "lib/actions/backend/{$this->app_id}Backend.action.php" => $this->getActionCode(),
            "templates/actions/backend/Backend.html"                => $this->getDefaultTemplate(),
            "lib/classes/",
            "lib/models/",
            "lib/config/",
            "locale/"
        );

        if (!empty($params['layout'])) {
            $structure["lib/layouts/{$this->app_id}Default.layout.php"] = $this->getLayoutCode();
            $structure['templates/layouts/Default.html'] = $this->getLayoutTemplate();
        }

        $features = array_map('trim', preg_split('@[,\s]+@', ifset($params['features'], $this->getDefaults('features'))));
        // api
        if (in_array('api', $features, true)) {
            $structure = array_merge($structure, array(
                "lib/api/v1/",

            ));
        }
        if (in_array('cli', $features, true)) {
            $structure = array_merge($structure, array(
                "lib/cli/",
                "lib/cli/{$this->app_id}Example.cli.php" => $this->getCliController(),
            ));
        }
        $protect = array(
            'lib',
            'templates',
        );


        // app description
        $app = array(
            'name'    => empty($params['name']) ? ucfirst($this->app_id) : $params['name'],
            'icon'    => array(
                48 => "img/{$this->app_id}48.png",
                96 => "img/{$this->app_id}96.png",
            ),
            'version' => ifempty($params['version'], $this->getDefaults('version')),
            'vendor'  => ifempty($params['vendor'], $this->getDefaults('vendor')),
            'ui' => '2.0',
        );

        if (isset($params['frontend'])) {

            $app['frontend'] = true;
            if ($params['frontend'] == 'themes') {
                $app['themes'] = true;
            }
            $routing = array('*' => 'frontend');

            $structure['lib/config/routing.php'] = $routing;

            $structure = array_merge($structure, array(
                'lib/actions/frontend/',
                "lib/actions/frontend/{$this->app_id}Frontend.action.php" => $this->getActionCode('default', false, $app),
            ));

            if (!empty($app['themes'])) {
                $htaccess = <<<HTACCESS
<FilesMatch "\.(php\d*|html?|xml)$">
    Deny from all
</FilesMatch>
HTACCESS;

                $structure = array_merge($structure, array(
                    'themes/.htaccess'          => $htaccess,
                    'themes/default/index.html' => $this->getFrontendTemplate(),
                    'themes/default/css/default.css',
                ));
            } else {
                $structure = array_merge($structure, array(
                    "templates/actions/frontend/Frontend.html" => $this->getFrontendTemplate(),
                    "css/frontend/{$this->app_id}.css",
                ));
            }
        }

        if (isset($params['plugins'])) {
            $structure = array_merge($structure, array(
                "plugins/",
            ));
            $app['plugins'] = true;
        }

        $structure['lib/config/app.php'] = $app;

        $this->createStructure($structure);
        $this->protect($protect);

        if (!empty($app['themes'])) {
            waFiles::delete(wa()->getDataPath('themes/default', true, $this->app_id));
            $theme = new waTheme('default', $this->app_id, true);
            $theme->name = 'Default theme';
            $theme->description = 'Auto generated default theme';
            $theme->vendor = $app['vendor'];
            $theme->version = $app['version'];
            $theme->addFile('index.html', 'Frontend index file');
            $theme->addFile('css/default.css', 'Frontend CSS file');
            $theme->save();
            waFiles::move($theme->path.'/theme.xml', $this->path.'themes/default/theme.xml');
        }

        if (!isset($params['disable'])) {
            $this->installApp();
            $errors = $this->flushCache();
            if ($errors) {
                print "Error during delete cache files:\n\t".implode("\n\t", $errors)."\n";
            }
        }
        return $app;
    }

    protected function showHelp()
    {
        echo <<<HELP
Usage: php wa.php createApp [app_id] [parameters]
    app_id - App ID (string in lower case)
Optional parameters:
    -name (app name; if comprised of several words, enclose in quotes; e.g., 'My app')
    -version (app version; e.g., 1.0.0)
    -vendor (numerical vendor id)
    -frontend (1|true|themes) (Has frontend and if value is themes support design themes)
    -features (comma separated values)
        plugins (supports plugins)
        cli (has CLI handlers)
        api (has API)
    -disable (1|true) not enable application in wa-config/apps.php

Example: php wa.php createApp myapp -name 'My app' -version 1.0.0 -vendor 123456 -frontend themes -plugins -cli -api
HELP;
        parent::showHelp();
    }

    protected function showReport($config = array(), $params = array())
    {
        $report = <<<REPORT
App with id "{$this->app_id}" created!

Useful commands:
    # generate app's database description file db.php
    php wa.php generateDb {$this->app_id}

    # generate app's locale files
    php wa.php locale {$this->app_id}

    # generate layouts, controllers and actions
    php wa.php createLayout {$this->app_id} backend
    php wa.php createAction --help
REPORT;

        if (!empty($config['plugins'])) {
            $report .= "\n\n".<<<REPORT
    #create a plugin with specified 'plugin_id' for your app
    php wa.php createPlugin {$this->app_id} plugin_id
REPORT;
        }
        $report .= "\n\n".<<<REPORT
    #check & compress application code for store
    php wa.php compress {$this->app_id}
REPORT;
        echo $report;
    }

    private function getActionCode($action = 'default', $backend = true, $app = array())
    {
        $action = ucfirst(strtolower($action));
        if ($action == 'Default') {
            $action = '';
        }
        $side = $backend ? 'Backend' : 'Frontend';
        if (!$backend && !empty($app['themes'])) {
            $code = '$this->setThemeTemplate("index.html");';
        } else {
            $code = '';
        }
        $code = <<<PHP
class {$this->app_id}{$side}{$action}Action extends waViewAction
{
    public function execute()
    {
        {$code}
        \$message = 'Hello world!';
        \$this->view->assign('message', \$message);
    }
}
PHP;
        return "<?php\n{$code}\n";
    }

    private function getLayoutCode()
    {
        $code = <<<PHP
class {$this->app_id}DefaultLayout extends waLayout
{
    public function execute()
    {
        //TODO put actual code here
        //e.g.  \$this->executeAction('sidebar', new {$this->app_id}BackendSidebarAction());
    }
}
PHP;
        return "<?php\n{$code}\n";
    }

    private function getFrontendTemplate()
    {
        $template = '<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{$wa->appName()}</title>
</head>

<body>
    <h1>{$wa->appName()}</h1>
    <p>{$message|escape}</p>
</body>

</html>';
        return str_replace('%app_id%', $this->app_id, $template);
    }

    private function getDefaultTemplate()
    {
        $template = '<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{$wa->appName()} &mdash; {$wa->accountName()}</title>
    {$wa->css()}
    <link type="text/css" rel="stylesheet" href="{$wa_app_static_url}css/%app_id%.css?v{$wa->version()}">
    <script src="{$wa_url}wa-content/js/jquery/jquery-3.6.0.min.js"></script>
    <script src="{$wa_url}wa-content/js/jquery-wa/wa.js?v={$wa->version(true)}"></script>
    <script src="{$wa_app_static_url}js/%app_id%.js?v{$wa->version()}"></script>
</head>
<body>
    <div id="wa">
        {$wa->header()}
        <div id="wa-app" class="flexbox wrap-mobile">
            <div class="sidebar flexbox overflow-visible width-adaptive-wider mobile-friendly js-app-sidebar">
                <ul class="menu mobile-friendly">
                     <li class="selected">
                         <a href="#">
                             <i class="fas fa-smile"></i>
                             <span>[`Hello world`]</span>
                         </a>
                     </li>
                </ul>
                <h5 class="heading">[`Navigation`]</h5>
                <ul class="menu mobile-friendly">
                     <li>
                         <a href="#">
                             <i class="fas fa-folder"></i>
                             <span>[`Menu item 1`]</span>
                         </a>
                     </li>
                     <li>
                         <a href="#">
                             <i class="fas fa-folder"></i>
                             <span>[`Menu item 2`]</span>
                         </a>
                     </li>
                     <li>
                         <a href="#">
                             <i class="fas fa-folder"></i>
                             <span>[`Menu item 3`]</span>
                         </a>
                     </li>
                </ul>
            </div>
            <div id="content" class="content blank">
                <div class="box contentbox">
                    <h1>{$message|escape}</h1>
                    <p>[`Woohoo, Webasyst app is ready!`]</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
        return str_replace('%app_id%', $this->app_id, $template);
    }

    private function getLayoutTemplate()
    {
        $template = '<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<title>{$title|escape} &mdash; {$wa->accountName()}</title>
{$wa->css()}
<link rel="stylesheet" type="text/css" href="{$wa_app_static_url}css/%app_id%.css?v{$wa->version()}" media="screen" />
<script src="{$wa_url}wa-content/js/jquery/jquery-3.6.0.min.js"></script>
<script src="{$wa_url}wa-content/js/jquery-wa/wa.js?v={$wa->version(true)}"></script>
</head>
<body id="{$wa_app}">
    <div id="wa">
    {$wa->header()}
        <div id="wa-app" class="flexbox wrap-mobile">
        <div class="sidebar flexbox overflow-visible width-adaptive-wider mobile-friendly js-app-sidebar">
            {if !empty($sidebar)}
                {$sidebar}
            {/if}
        </div>
        <div id="content" class="content blank">
            <div class="box contentbox">
               {if !empty($content)}
                    {$content}
                {/if}
            </div>
        </div>
    </div>
</body>
</html>
';
        return str_replace('%app_id%', $this->app_id, $template);
    }


    private function getCliController($method = 'example')
    {
        $method = ucfirst(strtolower($method));
        $code = <<<PHP
class {$this->app_id}{$method}Cli extends waCliController
{
    public function execute()
    {
        //TODO: put actual code here
        echo "it's works!";
    }
}
PHP;

        return "<?php\n{$code}\n";
    }

    private function installApp()
    {
        $path = wa()->getConfig()->getPath('config', 'apps');
        $apps = null;
        if (file_exists($path)) {
            $apps = include($path);
        }
        if (!is_array($apps)) {
            $apps = array();
        }
        $apps[$this->app_id] = true;
        waUtils::varExportToFile($apps, $path);
    }
}
