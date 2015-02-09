<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package installer
 */

class installerAppsRemoveAction extends waViewAction
{
    /**
     * @var waInstallerApps
     */
    private $apps;
    private $options = array(
        'log'    => false,
        'config' => false,
    );

    public function execute()
    {
        //TODO use POST
        $app_ids = waRequest::request('app_id');
        try {
            if (installerHelper::isDeveloper()) {
                throw new waException(_w('Unable to delete application (developer version is on)'));
            }

            if (!$app_ids || !is_array($app_ids)) {
                throw new waException(_w('Application not found'));
            }
            foreach ($app_ids as &$info) {
                if (!is_array($info)) {
                    $info = array('vendor' => $info);
                }
            }
            unset($info);

            $this->apps = new waInstallerApps();
            $app_list = $this->apps->getApps(array('installed' => true));
            $deleted_apps = array();
            foreach ($app_list as $info) {
                $app_id = $info['slug'];
                if (isset($app_ids[$app_id]) && ($app_ids[$app_id]['vendor'] == $info['vendor'])) {
                    if (!empty($info['installed']['system'])) {
                        throw new waException(sprintf(_w('Can not delete system application "%s"'), $info['name']));
                    }
                    $deleted_apps[] = $this->deleteApp($app_id);
                }
            }
            wa()->setActive('installer');
            if (!$deleted_apps) {
                throw new waException(_w('Application not found'));
            }
            $message = _w('Application %s has been deleted', 'Applications %s have been deleted', min(2, count($deleted_apps)), false);
            $message = sprintf($message, implode(', ', $deleted_apps));
            $msg = installerMessage::getInstance()->raiseMessage($message);
        } catch (Exception $ex) {
            wa()->setActive('installer');
            $msg = installerMessage::getInstance()->raiseMessage($ex->getMessage(), installerMessage::R_FAIL);
        }
        //'module' => installerHelper::getModule(),
        $this->redirect(array('msg' => $msg));
    }

    private function deleteApp($app_id)
    {
        //remove db tables and etc

        $paths = array();

        /**
         * @var waAppConfig
         */

        $system = wa($app_id);
        $system->setActive($app_id);
        $app = SystemConfig::getAppConfig($app_id);
        $info = $app->getInfo();
        $name = _wd($app_id, $info['name']);
        /**
         * @var waAppConfig $config ;
         */
        $config = $system->getConfig();

        if (!empty($info['plugins'])) {
            $plugins = $config->getPlugins();
            foreach ($plugins as $plugin => $enabled) {
                if ($enabled && ($plugin_instance = $system->getPlugin($plugin))) {
                    $plugin_instance->uninstall();
                }
                $this->apps->updateAppPluginsConfig($app_id, $plugin, null);

                //wa-apps/$app_id/plugins/$slug
                $paths[] = wa()->getAppPath("plugins/".$plugin, $app_id);
                while ($path = array_shift($paths)) {
                    waFiles::delete($path, true);
                }
                $paths = array();
            }
        }

        $config->uninstall();
        $this->apps->updateAppConfig($app_id, null);
        $paths[] = wa()->getTempPath(null, $app_id); //wa-cache/temp/$app_id/
        $paths[] = wa()->getAppCachePath(null, $app_id); //wa-cache/apps/$app_id/

        $paths[] = wa()->getDataPath(null, true, $app_id); //wa-data/public/$app_id/
        $paths[] = wa()->getDataPath(null, false, $app_id); //wa-data/protected/$app_id/
        if ($this->options['log']) {
            $paths[] = wa()->getConfig()->getPath('log').'/'.$app_id; //wa-log/$app_id/
        }
        if ($this->options['config']) {
            $paths[] = wa()->getConfigPath($app_id); //wa-config/$app_id/
        }

        $paths[] = wa()->getAppPath(null, $app_id); //wa-apps/$app_id/

        $paths[] = wa()->getAppCachePath(null, 'webasyst'); //wa-cache/apps/webasyst/

        foreach ($paths as $path) {
            try {
                waFiles::delete($path, true);
            } catch (waException $ex) {

            }
        }
        return $name;
    }
}
//EOF
