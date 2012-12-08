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
    public function execute()
    {
        $module = 'update';
        $url = parse_url(waRequest::server('HTTP_REFERER'), PHP_URL_QUERY);
        if (preg_match('/(^|&)module=(update|apps|plugins)($|&)/', $url, $matches)) {
            $module = $matches[2];
        }
        try {
            $updater = new waInstaller(waInstaller::LOG_TRACE);
            $state = $updater->getState();
            if (
            !isset($state['stage_status'])
            ||
            (($state['stage_name'] != waInstaller::STAGE_NONE) && ($state['heartbeat']>(waInstaller::TIMEOUT_RESUME+5)))
            ||
            (($state['stage_name'] == waInstaller::STAGE_UPDATE) && ($state['heartbeat']))
            ||
            (($state['stage_status'] == waInstaller::STATE_ERROR) && ($state['heartbeat']))
            ||
            (($state['stage_name'] == waInstaller::STAGE_NONE) && ($state['heartbeat'] === false))
            ) {
                $updater->setState();
                $this->view->assign('action', 'update');
                $app_ids = waRequest::request('app_id');
                $default_info = array('vendor' => waInstallerApps::VENDOR_SELF,'edition'=>'');

                $vendors = array();
                if ($app_ids && is_array($app_ids)) {
                    foreach ($app_ids as $app_id=>&$info) {
                        if (!is_array($info)) {
                            if (strpos($info, ':') === false) {
                                $vendor = $info;
                                $edition = '';
                            } else {
                                list($vendor, $edition) = explode(':', $info, 2);
                            }
                            $info = array('vendor'=>$vendor, 'edition'=>$edition);
                        } else {
                            $info = array_merge($info, $default_info);
                        }
                        $vendors[] = $info['vendor'];
                        unset($info);
                    }
                } else {
                    $app_ids = array();
                }

                $vendors = array_unique($vendors);
                if (!$vendors) {
                    $vendors = array();
                }


                $model = new waAppSettingsModel();
                $license = $model->get('webasyst', 'license', false);
                $locale = wa()->getLocale();
                $apps = new waInstallerApps($license, $locale);
                $app_list = $vendors?$apps->getApplicationsList(false, $vendors):array();
                $model->ping();
                $queue_apps = array();
                foreach ($app_list as &$info) {
                    $app_id = $info['slug'];

                    if ($app_id == 'installer') {
                        $info['name'] = _w('Webasyst Framework');
                    }
                    if (isset($app_ids[$app_id])) {
                        if (installerHelper::equals($app_ids[$app_id], $info)) {
                            $queue_apps[] = $info;
                        }
                    } else {
                        //TODO: add warning message
                    }
                    if (!empty($info['extras'])) {
                        foreach ($info['extras'] as $type => &$extras) {
                            foreach ($extras as $extra_id => &$extras_info) {
                                $extras_id = $extras_info['slug'];
                                $extras_info['name'] .= " ({$info['name']})";
                                if (isset($app_ids[$extras_id]) && installerHelper::equals($app_ids[$extras_id], $extras_info)) {
                                    $queue_apps[] = $extras_info;
                                } else {
                                    //TODO: add warning message
                                }
                            }
                            unset($extras_info);
                        }
                        unset($extras);
                    }
                    unset($info);
                }
                if (!$queue_apps) {
                    throw new waException(_w('Please select items for update'));
                }
                $this->view->assign('queue_apps', $queue_apps);

                $this->view->assign('apps', $app_list);
                $this->view->assign('install', waRequest::request('install'));
                $this->view->assign('title', _w('Updates'));
            } else {
                $this->redirect(array('module'=>$module, 'msg'=>installerMessage::getInstance()->raiseMessage(_w('Update is already in progress. Please wait while previous update session is finished before starting update session again.'), 'fail')));
            }
        } catch(Exception $ex) {
            $this->redirect(array('module'=>$module, 'msg'=>installerMessage::getInstance()->raiseMessage($ex->getMessage(), installerMessage::R_FAIL)));
        }
    }
}
//EOF