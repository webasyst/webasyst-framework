<?php

class webasystCreatePluginCli extends waCliController
{
    public function execute()
    {
        $app_id = waRequest::param(0);
        $plugin_id = waRequest::param(1);
        $params = waRequest::param();
        if (empty($app_id) || empty($plugin_id) || isset($params['help'])) {
            $help = <<<HELP
Usage: php wa.php createPlugin [app_id] [plugin_id] [parameters]
    app_id - App id (string in lower case) 
    plugin_id - Plugin id (string in lower case)
Optional parameters:
    -name (Plugin name; if comprised of several words, enclose in quotes; e.g., 'My plugin')
    -version (Plugin version; e.g., 1.0.0)
    -vendor (Numerical vendor id)
    -frontend (Has frontend)
    -settings (Implements custom settings screen)
Example: php wa.php createPlugin someapp myplugin -name 'My plugin' -version 1.0.0 -vendor 123456 -frontend -settings
HELP;
            print $help."\n";
        } else {
            $errors = array();
            if (!empty($params['version']) && !preg_match('@^[\d]+(\.\d+)*$@', $params['version'])) {
                $errors[] = 'Invalid version format';
            }
            if ($info = wa()->getAppInfo($app_id)) {
                if (empty($info['plugins'])) {
                    $errors[] = "Application '{$app_id}' doesn't support plugins";
                } else {
                    if (!preg_match('@^[a-z][a-z0-9]+$@', $plugin_id)) {
                        $errors[] = "Invalid plugin ID";
                    }
                    if (isset($params['frontend']) && empty($info['frontend'])) {
                        $errors[] = "Invalid option frontend, application {$app_id} doesn't support frontend";
                    }
                }
            } else {
                $errors[] = "Application not found";
            }

            if ($errors) {
                print "ERROR:\n\t";
                print implode("\n\t", $errors);
                print "\n";
            } else {
                $plugin_path = wa()->getAppPath('plugins/'.$plugin_id, $app_id);
                $this->create($app_id, $plugin_id, $plugin_path, $params);
            }
        }
    }


    protected function create($app_id, $plugin_id, $path, $params = array())
    {
        $report = '';
        if (!file_exists($path)) {
            $plugin = array(
                'name'     => empty($params['name']) ? ucfirst($plugin_id) : $params['name'],
                'icon'     => 'img/'.$plugin_id.'.gif',
                'version'  => $version = empty($params['version']) ? '0.1' : $params['version'],
                'vendor'   => $vendor = empty($params['vendor']) ? '--' : $params['vendor'],
                'handlers' => array( //TODO optional include some demo handlers
                ),
            );
            $path .= '/';
            mkdir($path);

            mkdir($path.'css');
            touch($path.'css/'.$plugin_id.'.css');
            mkdir($path.'js');
            touch($path.'js/'.$plugin_id.'.js');
            mkdir($path.'img');
            // lib
            mkdir($path.'lib');
            waFiles::protect($path.'lib');
            $class_name = $app_id.ucfirst($plugin_id).'Plugin';
            wa($app_id, true);
            $extends = $app_id.'Plugin';
            if (!class_exists($extends)) {
                $extends = 'waPlugin';
            }
            $class = <<<PHP
<?php

class {$class_name} extends {$extends}
{

}

PHP;
            waFiles::write($path.'lib/'.$app_id.ucfirst($plugin_id).'Plugin.class.php', $class);
            mkdir($path.'lib/config');
            mkdir($path.'lib/actions');

            mkdir($path.'lib/classes');
            if (isset($params['db'])) {
                mkdir($path.'lib/models');
            }


            if (isset($params['frontend'])) {
                $plugin['frontend'] = true;
                $routing = array('*' => 'frontend');
                waUtils::varExportToFile($routing, $path.'lib/config/routing.php');

                // frontend controller
                mkdir($path.'lib/actions/frontend');
            }

            // config
            waUtils::varExportToFile($plugin, $path.'lib/config/plugin.php');
            if (isset($params['settings'])) {
                waUtils::varExportToFile(array(), $path.'lib/config/settings.php');
            }

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
            if (isset($params['locale'])) {
                mkdir($path.'locale');
                waFiles::protect($path.'locale');
            }
            $report .= <<<REPORT
Plugin with id "$plugin_id" created!

Useful commands:
    #generate plugin's database description file db.php
    php wa.php generateDb $app_id/$plugin_id table1 table2 table3

    #generate plugin's locale files
    php wa-system/locale/locale.php $app_id/plugins/$plugin_id
REPORT;
        } else {
            $report .= <<<REPORT
Plugin with id "$plugin_id" already exists.
REPORT;
        }
        print $report."\n";
    }
}