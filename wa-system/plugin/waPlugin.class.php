<?php

class waPlugin
{
    protected $fromAppToPluginDir = null;
    protected $pluginDirName = null;
    protected $myApp = null;

    /** Name of an application this plugin belongs to */
    public function myApp() {
        if (!$this->myApp) {
            $this->loadPluginData();
        }
        return $this->myApp;
    }

    public function getDirName() {
        if (!$this->pluginDirName) {
            $this->loadPluginData();
        }
        return $this->pluginDirName;
    }

    protected function loadPluginData() {
        $path = waAutoload::getInstance()->get(get_class($this));
        if (!$path) {
            throw new waException('Unable to get path to '.get_class($this).' class file.');
        }

        // Remove path to application from path to a controller to get relative path
        $appsPath = str_replace('\\', '/', waConfig::get('wa_path_apps'));
        $path = dirname($path);
        $path = str_replace('\\', '/', $path);

        if (false === strpos($path, $appsPath)) {
            // webasyst app
            $appsPath = str_replace('\\', '/', waConfig::get('wa_path_system'));
            if (false === strpos($path, $appsPath)) {
                throw new waException('Unknown location for plugin: '.$path);
            }
        }

        $path = str_replace($appsPath, '', $path);
        $path = trim($path, '\/');

        // remove app dir from the begining of the path
        if (FALSE === ( $p = strpos($path, '/'))) {
            throw new waException('No app directory found for plugin '.get_class($this));
        }
        $this->myApp = substr($path, 0, $p);
        $path = substr($path, $p+1);

        // /lib dir indicates that we've found either a plugin root or an application root
        $prevBase = '';
        while($prevBase != 'lib') {
            $prevBase = basename($path);
            $path = dirname($path);
            if(!$path || $path == '.') {
                throw new waException('Unable to locate lib dir for '.get_class($this));
            }
        }

        $this->fromAppToPluginDir = $path.'/';
        $this->pluginDirName = basename($path);
    }

    /** Construct URL by prepending path to a plugin folder */
    public function getPluginStaticUrl($path = '') {
        if (!$this->fromAppToPluginDir) {
            $this->loadPluginData();
        }
        return wa()->getAppStaticUrl($this->myApp()).$this->fromAppToPluginDir.$path;
    }

    public function getRights($name = null, $assoc = true) {
        return wa()->getUser()->getRights(wa()->getConfig()->getApplication(), $name, $assoc);
    }
}

