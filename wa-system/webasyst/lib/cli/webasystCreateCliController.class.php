<?php

abstract class webasystCreateCliController extends waCliController
{
    protected $path = false;
    protected $root_path = false;
    protected $app_id = false;

    public function execute()
    {
        $params = waRequest::param();

        if (!$this->init() || isset($params['help'])) {
            $this->showHelp();
        } else {
            $errors = $this->verifyParams($params);
            if ($errors) {
                print "ERROR:\n";
                print implode("\n", $errors);
            } else {
                try {
                    $this->initPath();
                    $config = $this->create($params);
                    print $this->showReport($config, $params)."\n";
                } catch (waException $ex) {
                    print sprintf("ERROR:\n%s\n\n", $ex->getMessage());
                    $this->showHelp();
                }
            }
        }
    }

    protected function showHelp()
    {
        echo <<<HELP

Hint: use wa-config/developer.php to set up common defaults; e.g. vendor, version

HELP;

    }

    /**
     * @return string
     */
    protected function getAction()
    {
        if (preg_match('/^webasyst(\w+)Cli$/', __CLASS__, $matches)) {
            $callback = wa_lambda('$m', 'return strtolower($m[1]);');
            $action = preg_replace_callback('/^([\w]{1})/', $callback, $matches[1]);
        } else {
            $action = '';
        }
        return $action;
    }

    protected function init()
    {
        $this->app_id = waRequest::param(0);
        return !empty($this->app_id);
    }

    protected function initPath()
    {
        $this->root_path = wa()->getConfig()->getRootPath().'/';
    }

    protected function verifyParams($params = array())
    {
        $errors = array();
        if (!preg_match('@^[a-z][a-z0-9]+$@', $this->app_id)) {
            $errors['app_id'] = "Invalid app ID";
        }

        if (!empty($params['version']) && !preg_match('@^[\d]+(\.\d+)*$@', $params['version'])) {
            $errors['version'] = 'Invalid version format';
        }

        return $errors;
    }

    abstract protected function create($params = array());

    abstract protected function showReport($config = array(), $params = array());

    protected function flushCache()
    {
        $config = wa()->getConfig();
        $path_cache = $config->getPath('cache');
        waFiles::protect($path_cache);

        $caches = array();
        $paths = waFiles::listdir($path_cache);
        foreach ($paths as $path) {
            #skip long action & data path
            if ($path != 'temp') {
                $path = $path_cache.'/'.$path;
                if (is_dir($path)) {
                    $caches[] = $path;
                }
            }
        }
        $root_path = $config->getRootPath();
        $errors = array();
        foreach ($caches as $path) {
            try {
                waFiles::delete($path);
            } catch (Exception $ex) {
                $errors[] = str_replace($root_path.DIRECTORY_SEPARATOR, '', $ex->getMessage());
                waFiles::delete($path, true);
            }
        }
        waFiles::protect($path_cache);
        return $errors;
    }

    /**
     * @param $paths
     */
    protected function createStructure($paths)
    {
        foreach ($paths as $path => $content) {
            if (is_integer($path)) {
                $path = $content;
                $content = false;
            }
            if (preg_match('@\.[\w]+$@', $path)) {
                $file = $this->path.$path;
                waFiles::create(dirname($file), true);
                if (is_array($content)) {
                    waUtils::varExportToFile($content, $file);
                } elseif ($content && file_exists($content)) {
                    waFiles::copy($content, $file);
                } else {
                    waFiles::write($file, $content);
                }
            } else {
                waFiles::create($this->path.$path, true);
            }
        }
    }

    protected function protect($paths)
    {
        foreach ($paths as $path) {
            waFiles::protect($this->path.$path);
        }
    }

    protected function getDefaults($field = null)
    {
        static $default = null;
        if (empty($default)) {
            $default_path = wa()->getConfig()->getPath('config', 'developer');

            if (file_exists($default_path)) {
                $default = include($default_path);
            }
            if (!is_array($default)) {
                $default = array();
            }
            $default += array(
                'version' => '0.0.1',
                'vendor'  => '--',
                'author'  => isset($default['vendor']) ? $default['vendor'] : '',
            );
        }
        return $field == null ? $default : (isset($default[$field]) ? $default[$field] : null);
    }
}
