<?php

class webasystCreatePluginCli extends webasystCreateCliController
{
    protected $plugin_id = false;

    protected function showHelp()
    {
        echo <<<HELP
Usage: php wa.php createPlugin [app_id] [plugin_id] [parameters]
    app_id - App ID (string in lower case)
    plugin_id - Plugin ID (string in lower case)
Optional parameters:
    -name (plugin name; if comprised of several words, enclose in quotes; e.g., 'My plugin')
    -version (plugin version; e.g., 1.0.0)
    -vendor (numerical vendor ID)
    -frontend (Has frontend)
    -settings (implements custom settings screen)
    -disable (1|true) not enable plugin in wa-config/apps/app_id/plugins.php
Example: php wa.php createPlugin someapp myplugin -name 'My plugin' -version 1.0.0 -vendor 123456 -frontend -settings
HELP;
        parent::showHelp();
    }


    protected function init()
    {
        $init = parent::init();
        $this->plugin_id = waRequest::param(1);
        return $init && !empty($this->plugin_id);
    }

    protected function initPath()
    {
        parent::initPath();
        $this->path = wa()->getAppPath('plugins/'.$this->plugin_id, $this->app_id).'/';
    }

    protected function verifyParams($params = array())
    {
        $errors = parent::verifyParams($params);

        if (!preg_match('@^[a-z][a-z0-9]+$@', $this->plugin_id)) {
            $errors[] = "Invalid plugin ID";
        }
        if (empty($errors)) {
            if (!empty($params['version']) && !preg_match('@^[\d]+(\.\d+)*$@', $params['version'])) {
                $errors[] = 'Invalid version format';
            }
            if ($info = wa()->getAppInfo($this->app_id)) {
                if (empty($info['plugins'])) {
                    $errors[] = "Application '{$this->app_id}' doesn't support plugins";
                } else {
                    if (!preg_match('@^[a-z][a-z0-9]+$@', $this->plugin_id)) {
                        $errors[] = "Invalid plugin ID";
                    }
                    if (isset($params['frontend']) && empty($info['frontend'])) {
                        $errors[] = "Invalid option frontend, application {$this->app_id} doesn't support frontend";
                    }
                }
            } else {
                $errors[] = "Application not found";
            }
        }
        return $errors;
    }


    protected function create($params = array())
    {
        $config = array(
            'name'     => empty($params['name']) ? ucfirst($this->plugin_id) : $params['name'],
            'img'     => 'img/'.$this->plugin_id.'.gif',
            'version'  => ifempty($params['version'], $this->getDefaults('version')),
            'vendor'   => ifempty($params['vendor'], $this->getDefaults('vendor')),
            'handlers' => array(),//TODO optional include some demo handlers
        );


        $paths = array(
            'css/'.$this->plugin_id.'.css',
            'js/'.$this->plugin_id.'.js',
            'img/',
            'lib/',
            'lib/actions/backend/',
            'lib/classes/',
            'lib/config/',
            'lib/'.$this->app_id.ucfirst($this->plugin_id).'Plugin.class.php' => $this->getPluginClassCode(),
            'lib/vendors/',
        );

        if (isset($params['db'])) {
            array_push($paths, 'lib/models/');
        }
        if (isset($params['locale'])) {
            array_push($paths, 'locale/');
        }

        if (isset($params['frontend'])) {
            $config['frontend'] = true;

            array_push(
                $paths,
                'lib/actions/frontend/',
                'templates/actions/frontend/'
            );
            $paths['lib/config/routing.php'] = array($this->plugin_id.'/*' => 'frontend/');

        }

        if (isset($params['settings'])) {
            $paths['lib/config/settings.php'] = array();
        }

        $paths['lib/config/plugin.php'] = $config;

        $protected_paths = array(
            'lib/',
            'templates/',
        );
        if (isset($params['locale'])) {
            array_push($protected_paths, 'locale/');
        }

        $this->createStructure($paths);
        $this->protect($protected_paths);

        if (!isset($params['disable'])) {
            $this->installPlugin();
            $errors = $this->flushCache();
            if ($errors) {
                print "Error during delete cache files:\n\t".implode("\n\t", $errors)."\n";
            }
        }

        return $config;
    }

    private function getPluginClassCode()
    {
        $class_name = $this->app_id.ucfirst($this->plugin_id).'Plugin';
        wa($this->app_id, true);
        $extends = $this->app_id.'Plugin';
        if (!class_exists($extends)) {
            $extends = 'waPlugin';
        }
        $code = <<<PHP
<?php

class {$class_name} extends {$extends}
{

}

PHP;
        return $code;
    }

    protected function showReport($data = array(), $params = array())
    {
        echo <<<REPORT
Plugin with id "$this->plugin_id" created!

Useful commands:
    #generate plugin's database description file db.php
    php wa.php generateDb $this->app_id/$this->plugin_id table1 table2 table3

    #generate plugin's locale files
    php wa.php locale $this->app_id/plugins/$this->plugin_id
REPORT;
    }

    private function installPlugin()
    {
        $path = wa()->getConfig()->getConfigPath('plugins.php', true, $this->app_id);
        $plugins = null;
        if (file_exists($path)) {
            $plugins = include($path);
        }
        if (!is_array($plugins)) {
            $plugins = array();
        }
        $plugins[$this->plugin_id] = true;
        waUtils::varExportToFile($plugins, $path);
    }
}
