<?php

class webasystCreateAppCli extends waCliController
{
    public function execute()
    {
        $app_id = waRequest::param(0);
        $params = waRequest::param();
        if (empty($app_id) || isset($params['help'])) {
            $help = <<<HELP
Usage: php wa.php createApp [app_id] [parameters] 
    app_id - App id (string in lower case)
Optional parameters:
    -name (App name; if comprised of several words, enclose in quotes; e.g., 'My app')
    -version (App version; e.g., 1.0.0)
    -vendor (Numerical vendor id)
    -frontend (Has frontend)
    -themes (Supports design themes)
    -plugins (Supports plugins)
    -cli (Has CLI handlers)
    -api (Has API)
Example: php wa.php createApp myapp -name 'My app' -version 1.0.0 -vendor 123456 -frontend -themes -plugins -cli -api
HELP;
            print $help."\n";
        } else {
            $errors = array();
            if (!empty($params['version']) && !preg_match('@^[\d]+(\.\d+)*$@', $params['version'])) {
                $errors[] = 'Invalid version format';
            }
            if ($errors) {
                print "ERROR:\n";
                print implode("\n", $errors);
            } else {
                $app_path = wa()->getAppPath(null, $app_id);
                $this->create($app_id, $app_path, $params);
            }
        }
    }

    protected function create($app_id, $path, $params = array())
    {
        $report = '';
        if (!file_exists($path)) {
            $path .= '/';
            mkdir($path);
            mkdir($path.'css');
            touch($path.'css/'.$app_id.'.css');
            mkdir($path.'js');
            touch($path.'js/'.$app_id.'.js');
            mkdir($path.'img');
            // lib
            mkdir($path.'lib');
            waFiles::protect($path.'lib');
            mkdir($path.'lib/actions');
            // backend controller
            mkdir($path.'lib/actions/backend');

            // api
            if (isset($params['api'])) {
                mkdir($path.'lib/api');
                if ($params['api'] !== true) {
                    mkdir($path.'lib/api/'.$params['api']);
                } else {
                    mkdir($path.'lib/api/v1');
                }
            }

            // cli
            if (isset($params['cli'])) {
                mkdir($path.'lib/cli');
            }

            mkdir($path.'lib/classes');
            mkdir($path.'lib/models');
            // config
            mkdir($path.'lib/config');
            // app description
            $app = array(
                'name'    => empty($params['name']) ? ucfirst($app_id) : $params['name'],
                'icon'    => 'img/'.$app_id.'.gif',
                'version' => $version = empty($params['version']) ? '0.1' : $params['version'],
                'vendor'  => $vendor = empty($params['vendor']) ? '--' : $params['vendor'],
            );

            if (isset($params['frontend'])) {
                $app['frontend'] = true;
                if (isset($params['themes'])) {
                    $app['themes'] = true;
                }
                $routing = array('*' => 'frontend');
                waUtils::varExportToFile($routing, $path.'lib/config/routing.php');

                // frontend controller
                mkdir($path.'lib/actions/frontend');
            }
            // plugins
            if (isset($params['plugins'])) {
                $app['plugins'] = true;
                mkdir($path.'plugins');
            }
            waUtils::varExportToFile($app, $path.'lib/config/app.php');

            // templates
            mkdir($path.'templates');
            waFiles::protect($path.'templates');
            mkdir($path.'templates/actions');
            mkdir($path.'templates/actions/backend');
            // backend template
            if (isset($params['frontend'])) {
                // frontend template
                mkdir($path.'templates/actions/frontend');
            }
            // locale
            mkdir($path.'locale');
            waFiles::protect($path.'locale');
            if (isset($params['frontend']) && isset($params['themes'])) {
                // themes
                mkdir($path.'themes');
                mkdir($path.'themes/default');
                $theme = new waTheme('default', $app_id, true);
                $theme->name = 'Default theme';
                $theme->description = 'Auto generated default theme';
                $theme->vendor = $vendor;
                $theme->system = 1;
                $theme->addFile('index.html', 'Frontend index file');
                touch($path.'themes/default/index.html');
                $theme->addFile('default.css', 'Frontend CSS file');
                touch($path.'themes/default/default.css');
                $theme->version = $version;
                $theme->save();
                waFiles::move($theme->path.'/theme.xml', $path.'themes/default/theme.xml');
            }

            $report .= <<<REPORT
App with id "{$app_id}" created!

Useful commands:
    # generate app's database description file db.php
    php wa.php generateDb $app_id

    # generate app's locale files
    php wa-system/locale/locale.php $app_id

    # generate layouts, controllers and actions
    php wa.php createLayout $app_id backend
    php wa.php createAction --help
REPORT;

            if (isset($params['plugins'])) {
                $report .= "\n\n".<<<REPORT
    #create a plugin with specified 'plugin_id' for your app
    php wa.php createPlugin $app_id plugin_id
REPORT;
            }

            //TODO add hint about compress command

        } else {
            $report .= <<<REPORT
App with id "$app_id" already exists.
REPORT;
        }
        print $report."\n";
    }

}
