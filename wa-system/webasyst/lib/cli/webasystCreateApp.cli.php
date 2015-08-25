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
            // "lib/layouts/{$this->app_id}Default.layout.php"         => $this->getLayoutCode(),
            "templates/actions/backend/Backend.html"                => $this->getDefaultTemplate(),
            //'templates/layouts/Default.html'                  => $this->getLayoutTemplate(),
            "lib/classes/",
            "lib/models/",
            "lib/config/",
            "locale/"
        );

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
                $structure = array_merge($structure, array(
                    'themes/.htaccess'          => '
<FilesMatch "\.(php\d*|html?|xml)$">
    Deny from all
</FilesMatch>
',
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
    app_id - App id (string in lower case)
Optional parameters:
    -name (App name; if comprised of several words, enclose in quotes; e.g., 'My app')
    -version (App version; e.g., 1.0.0)
    -vendor (Numerical vendor id)
    -frontend (1|true|themes) (Has frontend and if value is themes support design themes)
    -features (comma separated values)
        plugins (Supports plugins)
        cli (Has CLI handlers)
        api (Has API)
    -disable (1|true) not enable application at wa-config/apps.php

Example: php wa.php createApp myapp -name 'My app' -version 1.0.0 -vendor 123456 -frontend themes -plugins -cli -api
HELP;
        parent::showHelp();
    }

    protected function showReport($config = array())
    {
        $report = <<<REPORT
App with id "{$this->app_id}" created!

Useful commands:
    # generate app's database description file db.php
    php wa.php generateDb {$this->app_id}

    # generate app's locale files
    php wa-system/locale/locale.php {$this->app_id}

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
    <link type="text/css" rel="stylesheet" href="{$wa_app_static_url}css/%app_id%.css">
    <script type="text/javascript" src="{$wa_url}wa-content/js/jquery/jquery-1.8.2.min.js"></script>
    <script type="text/javascript" src="{$wa_url}wa-content/js/jquery-wa/wa.core.js"></script>
    <script type="text/javascript" src="{$wa_app_static_url}js/%app_id%.js"></script>

</head>
<body>
    <div id="wa">
        {$wa->header()}
        <div id="wa-app">
            <div class="sidebar left200px">
                <div class="block">
                    <ul class="menu-v with-icons">
                         <li class="selected">
                             <a href="#" class="bold"><i class="icon16 smiley"></i>[`Hello world`]</a>
                         </li>
                    </ul>
                </div>
                <div class="block">
                    <h5 class="heading top-padded"><b>[`Navigation`]</b></h5>
                    <ul class="menu-v with-icons collapsible">
                         <li>
                             <a href="#"><i class="icon16 folder"></i>[`Menu item 1`]</a>
                         </li>
                         <li>
                             <a href="#"><i class="icon16 folder"></i>[`Menu item 2`]</a>
                         </li>
                         <li>
                             <a href="#"><i class="icon16 folder"></i>[`Menu item 3`]</a>
                         </li>
                    </ul>
                </div>
            </div>
            <div class="content left200px">
                <div id="content">
                    <div class="block">
                        <h1>{$message|escape}</h1>
                        <p>[`Woohoo, Webasyst app is ready!`]</p>
                    </div>
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
<script type="text/javascript" src="{$wa_url}wa-content/js/jquery/jquery-1.8.2.min.js"></script>
<script type="text/javascript" src="{$wa_url}wa-content/js/jquery-wa/wa.core.js"></script>
</head>
<body id="{$wa_app}"><div id="wa">
    {$wa->header()}
    <div id="wa-app">
        <div class="sidebar left200px">
            {if !empty($sidebar)}
                {$sidebar}
            {/if}
        </div>
        <div class="content left200px" id="cl-core">
            <div class="shadowed %app_id%-content">
                {if !empty($content)}
                    {$content}
                {/if}
            </div>
        </div>
    </div>
</div></body>
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
