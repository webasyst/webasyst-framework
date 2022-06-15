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

    private $is_trial = false;

    protected $params = [];

    public $response;
    public $errors;
    public $status_code;

    /**
     * installerUpdateExecuteController constructor.
     * @param array $params
     *  - string $params['thread_id']
     *  - bool $params['is_trial']
     *  - bool $params['install']
     *  - string $params['mode']
     *  - bool $params['send_response'] - default is TRUE
     */
    public function __construct($params = null)
    {
        if (is_array($params)) {
            $this->params = $params;
        }
    }

    public function execute()
    {
        if (array_key_exists('trial', $this->params)) {
            $this->is_trial = $this->params['trial'];
        } else {
            $this->is_trial = waRequest::request('trial', false);
        }
        $this->is_trial = (bool)$this->is_trial;

        try {
            if (array_key_exists('thread_id', $this->params)) {
                $this->thread_id = strval($this->params['thread_id']);
            } else {
                $this->thread_id = waRequest::get('thread_id', '', 'string');
            }
            $this->thread_id = preg_replace('@[^a-zA-Z0-9]+@', '', $this->thread_id);

            if ($this->thread_id) {
                $path = wa()->getCachePath(sprintf('update.%s.php', $this->thread_id), 'installer');
                if (file_exists($path)) {
                    $this->urls = include($path);
                }

                $log_level = waSystemConfig::isDebug() ? waInstaller::LOG_DEBUG : waInstaller::LOG_WARNING;
                $updater = new waInstaller($log_level, $this->thread_id);

                if ($this->urls) {

                    $this->getStorage()->close();
                    ob_start();

                    try {
                        $this->model = new waAppSettingsModel();

                        $updater->init();

                        $this->model->ping();

                        $this->getStorage()->close();
                        $this->urls = $updater->update($this->urls);

                        if (array_key_exists('install', $this->params)) {
                            $install = $this->params['install'];
                        } else {
                            $install = waRequest::request('install');
                        }

                        if ($install) {
                            $this->install();
                        }

                        $this->logItems($install);

                        $result = $this->getResult();
                        if ($this->is_trial) {
                            // Get theme for site app
                            if (wa()->appExists('site')) {
                                foreach ($result as $source) {
                                    if (!empty($source['real_slug']) && preg_match('~^site/themes/(\w+)$~', $source['real_slug'], $match)) {
                                        $this->response['design_redirect'] = wa()->getConfig()->getBackendUrl(true) . 'site/#/design/theme=' . $match[1];
                                    }
                                }
                            }
                        } else {
                            $this->updateFactProducts($result);
                        }

                        $this->response['sources'] = $result;
                        $this->response['current_state'] = $updater->getState();

                        if (array_key_exists('mode', $this->params)) {
                            $mode = $this->params['mode'];
                        } else {
                            $mode = waRequest::get('mode', 'apps');
                        }

                        $this->response['state'] = $updater->getFullState($mode);

                        //cleanup cache
                        $this->storage($this->thread_id, null);
                        $this->cleanup();

                        //update themes
                        foreach ($this->urls as $url) {
                            if (preg_match('@(wa-apps/)?(.+)/themes/(.+)@', $url['slug'], $matches)) {
                                try {
                                    $theme = new waTheme($matches[3], $matches[2]);
                                    $theme->update();
                                } catch (Exception $ex) {
                                    waLog::log(sprintf('Error during theme %s@%s update: %s', $matches[3], $matches[2], $ex->getMessage()));
                                }
                            }
                        }

                        //and again cleanup
                        $this->cleanup();

                        $this->getConfig()->setCount(false);
                        if (!array_key_exists('send_response', $this->params) || $this->params['send_response']) {
                            $response = $this->getResponse();
                            $response->addHeader('Content-Type', 'application/json; charset=utf-8');
                            $response->sendHeaders();
                        }

                    } catch (waInstallerDownloadException $ex) {
                        $this->setError($ex->getMessage());
                        $this->status_code = $ex->getCode();
                    } catch (Exception $ex) {
                        $this->setError($ex->getMessage());
                    }

                    $ob = ob_get_clean();
                    if ($ob) {

                        $ob = preg_replace('@([\?&](hash|previous_hash|token)=)([^&\?]+)@', '$1*hash*', $ob);

                        $this->response['warning'] = $ob;
                        waLog::log('Output at '.__METHOD__.': '.$ob);
                    }

                    /**
                     * @event end_installation
                     */
                    $event_results = wa('installer')->event('end_installation', $this->urls);

                    if (!empty($event_results)) {
                        foreach ($event_results as $event_result) {
                            if (!empty($event_result['redirect'])) {
                                $this->response['redirect'] = $event_result['redirect'];
                                break;
                            }
                        }
                    }
                } else {
                    $updater->flush();
                    throw new waException(sprintf('Nothing to update at thread %s', $this->thread_id));
                }
            } else {
                throw new waException('Empty thread id');
            }

        } catch (Exception $ex) {
            throw $ex;
            //TODO use redirect/errors
        }
    }

    private function logItems($install)
    {
        $action = $install ? 'item_install' : 'item_update';
        $ip = waRequest::getIp();
        foreach ($this->urls as $target => $url) {
            if (empty($url['skipped'])) {
                $params = null;
                if (preg_match('@^wa-apps/([^/]+)$@', $target, $matches)) {
                    $params = array(
                        'type' => 'apps',
                        'id'   => $matches[1],
                        'ip'   => $ip,
                    );
                } elseif (preg_match('@^wa-apps/([^/]+)/(plugins|themes|widgets)/([^/]+)$@', $target, $matches)) {
                    $params = array(
                        'type' => $matches[2],
                        'id'   => sprintf('%s/%s', $matches[1], $matches[3]),
                        'ip'   => $ip,
                    );
                } elseif (preg_match('@^wa-plugins/(payment|shipping|sms)/([^/]+)$@', $target, $matches)) {
                    $params = array(
                        'type' => 'plugins',
                        'id'   => sprintf('wa-plugins/%s/%s', $matches[1], $matches[2]),
                        'ip'   => $ip,
                    );
                }

                $this->logAction($action, $params);
            }
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
                    $slug = preg_replace('@^wa-apps/@', '', $target);
                    $apps->installWebAsystItem($slug, null, isset($url['edition']) ? $url['edition'] : true);
                } catch (Exception $e) {
                    $message = sprintf(
                        'Error occurred during install %s: %s',
                        $target,
                        $e->getMessage()
                    );
                    waLog::log($message);
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
        $this->model->ping();
        installerHelper::flushCache();
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

    /**
     * Informs the remote update server about changes to the installation package
     * @param $list
     */
    private function updateFactProducts($list)
    {
        $updated_slugs = array();
        foreach ($list as $item) {
            if (empty($item['skipped']) && !empty($item['real_slug'])) {
                $updated_slugs[] = $item['real_slug'];
            }
        }

        if (!empty($updated_slugs)) {
            $sender = new installerUpdateFact(installerUpdateFact::ACTION_ADD, $updated_slugs);
            $sender->query();
        }
    }
}
