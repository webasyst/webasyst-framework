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

class installerUpdateExecuteController extends waJsonController
{
    private $urls = array();
    private $thread_id;
    /**
     * @var waAppSettingsModel
     */
    private $model;

    public function execute()
    {
        if ($this->thread_id = waRequest::get('thread_id', false)) {
            $cache = new waSerializeCache($this->getApp().'.'.$this->thread_id);
            $this->urls = $cache->get();
            $cache->delete();
        }
        if ($this->urls) {
            wa()->getStorage()->close();
            ob_start();


            try {
                $this->model = new waAppSettingsModel();
                $log_level = waSystemConfig::isDebug() ? waInstaller::LOG_DEBUG : waInstaller::LOG_WARNING;

                $updater = new waInstaller($log_level, $this->thread_id);
                $this->getStorage()->close();
                $updater->init();


                $this->model->ping();

                $storage = wa()->getStorage();
                $storage->close();
                $this->urls = $updater->update($this->urls);
                if (waRequest::request('install')) {
                    $this->install();
                }

                $this->response['sources'] = $this->getResult();
                $this->response['current_state'] = $updater->getState();
                $this->response['state'] = $updater->getFullState(waRequest::get('mode', 'apps'));

                //cleanup cache
                $this->cleanup();

                $this->getConfig()->setCount(false);

                $response = $this->getResponse();
                $response->addHeader('Content-Type', 'application/json; charset=utf-8');
                $response->sendHeaders();
            } catch (Exception $ex) {
                $this->setError($ex->getMessage());
            }
            if ($ob = ob_get_clean()) {
                $this->response['warning'] = $ob;
                waLog::log('Output at '.__METHOD__.': '.$ob);
            }
        } else {
            throw new Exception('nothing to update');
        }
    }

    private function install()
    {
        $this->model->ping();
        $apps = installerHelper::getInstaller();
        $this->model->ping();
        $user = $this->getUser();
        $set_rights = false;
        if (!$user->isAdmin()) {
            $set_rights = true;
        }

        foreach ($this->urls as $target => &$url) {
            //TODO workaround exceptions
            if (empty($url['skipped']) && preg_match('@^wa-apps/@', $target)) {
                try {
                    $apps->installWebAsystItem($s = preg_replace('@^wa-apps/@', '', $target), null, isset($url['edition']) ? $url['edition'] : true);
                } catch (Exception $e) {
                    waLog::log($e->getMessage());
                    $url['skipped'] = true;
                }
                $this->model->ping();
                if ($set_rights) {
                    $user->setRight($url['slug'], 'backend', 2);
                }
            } else {
                $url['update'] = true;
            }
            unset($url);
        }
    }

    private function cleanup()
    {
        $apps = array();
        foreach ($this->urls as $url) {
            if (!isset($url['skipped']) || !$url['skipped']) {
                if (preg_match('@^wa-apps/[^/]+$@', $url['target'])) {
                    $apps[] = array(
                        'installed' => true,
                        'slug'      => $url['target'],
                    );
                }
            }
        }
        installerHelper::flushCache($apps);
        $this->model->ping();
    }

    private function getResult()
    {
        $result_urls = $this->urls;
        $secure_properties = array('archive', 'source', 'backup', 'md5', 'extract_path', 'download_url');
        foreach ($result_urls as & $result_url) {
            foreach ($secure_properties as $property) {
                if (isset($result_url[$property])) {
                    unset($result_url[$property]);
                }
            }
            unset($result_url);
        }
        return $result_urls;
    }
}
//EOF
