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
        'config' => true,
    );

    public function execute()
    {
        //TODO use POST
        $app_ids = waRequest::request('app_id');
        try {
            if (installerHelper::isDeveloper()) {
                throw new waException(_w('Unable to delete the app (developer mode is on).').
                    "\n"._w("A .git or .svn directory has been detected. To ignore the developer mode, add option 'installer_in_developer_mode' => true to wa-config/config.php file."));
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
            $options = array(
                'installed' => true,
            );
            $app_list = $this->apps->getApps($options);
            $deleted_apps = array();
            foreach ($app_list as $info) {
                $app_id = $info['slug'];
                if (isset($app_ids[$app_id])) {
                    if ($app_ids[$app_id]['vendor'] == $info['vendor']) {
                        if (!empty($info['installed']['system'])) {
                            throw new waException(sprintf(_w('Can not delete system application "%s"'), $info['name']));
                        }
                        $deleted_apps[] = $this->deleteApp($app_id);
                    }
                    unset($app_ids[$app_id]);
                }
            }

            foreach ($app_ids as $app_id => $info) {
                $deleted_apps[] = $this->cleanupApp($app_id);
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
        // app_id and app plugins
        $deleted_extras_slug = array($app_id);

        //remove db tables and etc

        $paths = array();

        /** @var waAppConfig */
        $system = wa($app_id);
        $system->setActive($app_id);
        $app = SystemConfig::getAppConfig($app_id);
        $info = $app->getInfo();
        $name = _wd($app_id, $info['name']);
        /** @var waAppConfig $config */
        $config = $system->getConfig();

        if (!empty($info['plugins'])) {
            $plugins = $config->getPlugins();
            foreach ($plugins as $plugin => $enabled) {
                try {
                    $system->setActive($app_id);
                    if ($enabled && ($plugin_instance = $system->getPlugin($plugin))) {
                        $plugin_instance->uninstall();
                    }
                } catch (Exception $ex) {
                    waLog::log($ex->getMessage(), 'installer.log');
                }
                $this->apps->updateAppPluginsConfig($app_id, $plugin, null);

                //wa-apps/$app_id/plugins/$slug
                $paths[] = wa()->getAppPath("plugins/".$plugin, $app_id);
                $deleted_extras_slug[] = $app_id.'/plugins/'.$plugin;
                while ($paths) {
                    waFiles::delete(array_shift($paths), true);
                }
                $paths = array();

                $params = array(
                    'type' => 'plugins',
                    'id'   => sprintf('%s/%s', $app_id, $plugin),
                    'ip'   => waRequest::getIp(),
                );

                $system->setActive('installer');
                $this->logAction('item_uninstall', $params);
            }
        }

        $config->uninstall();
        $this->cleanupApp($app_id);

        $system->setActive('installer');

        $params = array(
            'type' => 'apps',
            'id'   => $app_id,
            'ip'   => waRequest::getIp(),
        );

        $this->logAction('item_uninstall', $params);
        $this->updateFactProducts($deleted_extras_slug);
        return $name;
    }

    private function cleanupApp($app_id)
    {
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

        $retry_paths = array();
        foreach ($paths as $path) {
            try {
                waFiles::delete($path, true);
            } catch (waException $ex) {
                waLog::log($ex->getMessage(), 'installer/remove.apps.log');
                $retry_paths[] = $path;
            }
        }
        if ($retry_paths) {
            sleep(5);
            foreach ($retry_paths as $path) {
                try {
                    waFiles::delete($path, true);
                } catch (waException $ex) {
                    waLog::log($ex->getMessage(), 'installer/remove.apps.log');
                }
            }
        }
        return $app_id;
    }

    /**
     * Informs the remote update server about changes to the installation package
     * @param $list
     */
    private function updateFactProducts($list)
    {
        if (!empty($list)) {
            $sender = new installerUpdateFact(installerUpdateFact::ACTION_DEL, $list);
            $sender->query();
        }
    }
}
