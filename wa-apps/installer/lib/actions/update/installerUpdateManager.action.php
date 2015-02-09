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

class installerUpdateManagerAction extends waViewAction
{

    private $vendors = array();
    private $module = 'update';


    private $urls;

    private function init()
    {
        $url = parse_url($r = waRequest::server('HTTP_REFERER'), PHP_URL_QUERY);
        if (preg_match('/(^|&)module=(update|apps|plugins)($|&)/', $url, $matches)) {
            $this->module = $matches[2];
        }
        if (installerHelper::isDeveloper()) {
            if (waRequest::request('install')) {
                $msg = _w('Unable to install application (developer version is on)');
            } else {
                $msg = _w('Unable to install application (developer version is on)');
            }
            $this->redirect(array(
                'module' => $this->module,
                'msg'    => installerMessage::getInstance()->raiseMessage($msg, 'fail'),
            ));
        }
    }

    public function execute()
    {
        $this->init();

        try {
            $updater = new waInstaller(waInstaller::LOG_TRACE);
            $state = $updater->getState();
            if (!isset($state['stage_status'])
                || (
                    ($state['stage_name'] != waInstaller::STAGE_NONE)
                    && ($state['heartbeat'] > (waInstaller::TIMEOUT_RESUME + 5))
                )
                || (
                    ($state['stage_name'] == waInstaller::STAGE_UPDATE)
                    && ($state['heartbeat'])
                )
                || (
                    ($state['stage_status'] == waInstaller::STATE_ERROR)
                    && ($state['heartbeat'])
                )
                || (
                    ($state['stage_name'] == waInstaller::STAGE_NONE)
                    && ($state['heartbeat'] === false)
                )
            ) {
                $updater->setState();
                $state = $updater->getState();

                $apps = installerHelper::getInstaller();

                $items = $apps->getUpdates(null, $this->getItemsList());
                $queue_apps = array();
                $execute_actions = array(
                    waInstallerApps::ACTION_INSTALL,
                    waInstallerApps::ACTION_CRITICAL_UPDATE,
                    waInstallerApps::ACTION_UPDATE,
                );

                foreach ($items as $app_id => $info) {
                    if (!empty($info['download_url']) && in_array($info['action'], $execute_actions)) {
                        $info['subject'] = 'app';
                        if ($app_id == 'installer') {
                            foreach ($info['download_url'] as $target => $url) {
                                $_info = $info;
                                $_info['download_url'] = $url;
                                $_info['name'] = _w('Webasyst Framework').' ('.$target.')';
                                $this->add($target, $_info);
                                $queue_apps[$target] = $_info;
                                unset($_info);
                            }
                        } else {
                            $target = 'wa-apps/'.$app_id;
                            $this->add($target, $info, $app_id);
                            $queue_apps[$target] = $info;
                        }
                    }

                    foreach (array('themes', 'plugins') as $type) {
                        if (!empty($info[$type]) && is_array($info[$type])) {
                            foreach ($info[$type] as $extra_id => $extras_info) {
                                if (!empty($extras_info['download_url']) && in_array($extras_info['action'], $execute_actions)) {
                                    $extras_info['subject'] = 'app_'.$type;
                                    if (($type == 'themes') && is_array($extras_info['download_url'])) {
                                        foreach ($extras_info['download_url'] as $target => $url) {
                                            $__info = $extras_info;
                                            $__info['download_url'] = $url;
                                            $__info['slug'] = preg_replace('@^wa-apps/@', '', $target);
                                            $__info['app'] = preg_replace('@^wa-apps/([^/]+)/.+$@', '$1', $target);
                                            if (!isset($queue_apps[$target])) {
                                                if (($__info['app'] == $app_id) || empty($items[$__info['app']][$type][$extra_id])) {
                                                    if (!empty($items[$__info['app']][$type][$extra_id]['name'])) {
                                                        $__info['name'] .= " ({$info['name']})";
                                                    } elseif ($app_info = wa()->getAppInfo($__info['app'])) {

                                                        $__info['name'] .= " ({$app_info['name']})";
                                                    } else {
                                                        $__info['name'] .= " ({$__info['app']})";
                                                    }
                                                    $this->add($target, $__info);
                                                    $queue_apps[$target] = $__info;
                                                }
                                            }
                                        }
                                    } else {
                                        if (!empty($info['name'])) {
                                            $extras_info['name'] .= " ({$info['name']})";
                                        }
                                        if (strpos($app_id, '/')) {
                                            //system plugins
                                            $target = $app_id.'/'.$extra_id;
                                        } else {
                                            $target = 'wa-apps/'.$app_id.'/'.$type.'/'.$extra_id;
                                        }
                                        $this->add($target, $extras_info, $target);
                                        $queue_apps[$target] = $extras_info;
                                    }
                                }
                            }
                        }
                    }
                    unset($info);
                }

                if (!$queue_apps) {
                    throw new waException(_w('Please select items for update'));
                }

                if (!waRequest::get('_')) {
                    $this->setLayout(new installerBackendLayout());
                    $this->getLayout()->assign('no_ajax', true);
                }

                $this->view->assign('action', 'update');
                $this->view->assign('queue_apps', $queue_apps);
                $install = waRequest::request('install');
                $this->view->assign('install', !empty($install) ? 'install' : '');
                $this->view->assign('title', _w('Updates'));
                $this->view->assign('thread_id', $state['thread_id']);
                $this->view->assign('return_url', waRequest::post('return_url'));
                $cache = new waSerializeCache($this->getApp().'.'.$state['thread_id']);
                $cache->set($this->urls);
            } else {
                $msg = _w('Update is already in progress. Please wait while previous update session is finished before starting update session again.');
                $this->redirect(array(
                    'module' => $this->module,
                    'msg'    => installerMessage::getInstance()->raiseMessage($msg, installerMessage::R_FAIL),
                ));
            }
        } catch (Exception $ex) {
            $this->redirect(array(
                'module' => $this->module,
                'msg'    => installerMessage::getInstance()->raiseMessage($ex->getMessage(), installerMessage::R_FAIL),
            ));
        }
    }

    private function getItemsList()
    {
        $app_ids = waRequest::request('app_id');
        $default_info = array('vendor' => waInstallerApps::VENDOR_SELF, 'edition' => '');

        if ($app_ids && is_array($app_ids)) {
            foreach ($app_ids as & $info) {
                if (!is_array($info)) {
                    if (strpos($info, ':') === false) {
                        $vendor = $info;
                        $edition = '';
                    } else {
                        list($vendor, $edition) = explode(':', $info, 2);
                    }
                    $info = array('vendor' => $vendor, 'edition' => $edition);
                } else {
                    $info = array_merge($info, $default_info);
                }
                $this->vendors[] = $info['vendor'];
                unset($info);
            }
        } else {
            $app_ids = array();
        }

        $this->vendors = array_unique($this->vendors);
        return $app_ids;
    }

    protected function add($target, $info, $item_id = null)
    {
        $this->urls[$target] = array(
            'source' => $info['download_url'],
            'target' => $target,
            'slug'   => $target,
            'md5'    => !empty($info['md5']) ? $info['md5'] : null,
        );

        if ($item_id) {
            $this->urls[$target] = array_merge($this->urls[$target], array(
                'slug'    => $item_id,
                'pass'    => false && ($this->getAppId() != $item_id),
                'name'    => $info['name'],
                'icon'    => $info['icon'],
                'update'  => !empty($info['installed']),
                'subject' => empty($info['subject']) ? 'system' : $info['subject'],
                'edition' => empty($info['edition']) ? true : $info['edition'],
            ));

        }
    }
}
//EOF
