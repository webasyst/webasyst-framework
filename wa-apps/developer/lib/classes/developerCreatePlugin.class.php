<?php

/**
 * Create application plugin.
 */
class developerCreatePlugin extends webasystCreatePluginCli
{
    protected function create($params = [])
    {
        $info = parent::create($params);
        
        $info['icon'] = $info['img'] = 'img/icon.png';
        $info['description'] = ifset($params['description'], '');
        
        $app = wa('developer');

        $parentClass = $this->app_id . 'Plugin';
        wa($this->app_id);
        if (!class_exists($parentClass)) {
            $parentClass = 'waPlugin';
        }
        $plugin = $this->app_id . ucfirst($this->plugin_id);
        $class = $plugin . 'Plugin';

        $paths = [
            'img/icon.png' => $app->getAppPath('img/icons/'.$params['img']),
            'lib/config/plugin.php' => $info,
            'lib/' . $plugin . '.plugin.php' => include($app->getAppPath('lib/config/dummy/appPlugin.php')),
        ];
        if (isset($params['settings'])) {
            $paths['lib/config/settings.php'] = $app->getAppPath('lib/config/dummy/settings.php');
        }
        if (isset($params['db'])) {
            $paths['lib/config/db.php'] = [];
        }
        $this->createStructure($paths);
        // Delete wrong plugin's file
        unlink($this->path.'/lib/' . $plugin . 'Plugin.class.php');

        if (isset($params['db'])) {
            $this->generateDb();
        }

        return $info;
    }

    /**
     * @return void
     */
    protected function generateDb()
    {
        $params = waRequest::param();
        waRequest::setParam([
            0 => $this->app_id . '/' . $this->plugin_id,
            'ignore' => 'config',
        ]);
        ob_start();
        wao(new webasystGenerateDbCli())->execute();
        ob_end_clean();
        // reset params
        waRequest::setParam($params);
    }
}
