<?php

class webasystLoginConfigAction extends waViewAction
{
    public function execute()
    {
        if (file_exists($this->getConfig()->getPath('config', 'db'))) {
            $this->redirect($this->getConfig()->getBackendUrl(true));
        }

        if ($config = waRequest::post()) {
            $database = $config['database'];
            $error = false;
            try {
                $config['database'] = null;
                $model = new waModel($config);
            } catch (waDbException $e) {
                $error = _w('Failed to connect to specified MySQL database server.');
            }

            $config['database'] = $database;

            if (!$error) {
                if (!$model->database($database)) {
                    try {
                        // try create database
                        $sql = "CREATE DATABASE ".$model->escape($database);
                        $model->exec($sql);
                    } catch (waDbException $e) {
                        $error = sprintf(_w('Failed to connect to the “%s” database.'), $database);
                    }
                }
            }

            if (!$error) {
                // try save config
                $file = $this->getConfig()->getPath('config');
                if (!is_writable($file)) {
                    $error = sprintf(_w("Not enough access permissions to write in the folder %s"), $file);
                } else {
                    $data = array(
                        'default' => $config
                    );
                    if (!waUtils::varExportToFile($data, $file.'/db.php')) {
                        $error = sprintf(_w("Error creating file %s"), $file.'/routing.php');
                    } else {
                        // check routing.php
                        if (!file_exists($file.'/routing.php')) {
                            $apps = wa()->getApps();
                            $data = array();
                            $domain = $this->getConfig()->getDomain();
                            $site = false;
                            foreach ($apps as $app_id => $app) {
                                if ($app_id == 'site') {
                                    $site = true;
                                } elseif (!empty($app['frontend'])) {
                                    $data[$domain][] = array(
                                        'url' => $app_id.'/*',
                                        'app' => $app_id
                                    );
                                }
                            }
                            if ($site) {
                                $data[$domain][] = array('url' => '*', 'app' => 'site');
                            }
                            waUtils::varExportToFile($data, $file.'/routing.php');
                        }
                        // redirect to backend
                        $this->redirect($this->getConfig()->getBackendUrl(true));
                    }
                }
            }
            if ($error) {
                $this->view->assign('error', $error);
            }
        }
    }
}