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
            $this->initPath();
            $errors = $this->verifyParams($params);
            if ($errors) {
                print "ERROR:\n";
                print implode("\n", $errors);
            } else {
                $config = $this->create($params);
                print $this->showReport($config)."\n";
            }
        }
    }

    protected function showHelp()
    {
        echo <<<HELP

Hint: use wa-config/developer.php to setup common defaults e.g. vendor, version

HELP;

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
            $errors[] = "Invalid app ID";
        }
        return $errors;
    }

    abstract protected function create($params = array());

    abstract protected function showReport($config = array());

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
                'version' => '0.1',
                'vendor'  => '--',
            );
        }
        return $field == null ? $default : (isset($default[$field]) ? $default[$field] : null);
    }
}