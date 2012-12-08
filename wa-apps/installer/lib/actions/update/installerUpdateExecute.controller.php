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
    function execute()
    {
        $update_ids = waRequest::get('app_id');
        if ($update_ids && is_array($update_ids)) {
            $app_ids = array();
            $vendors = array();
            foreach ($update_ids as $app_id=>&$info) {
                if (!is_array($info)) {
                    if (strpos($info, ':') === false) {
                        $vendor = $info;
                        $edition = '';
                    } else {
                        list($vendor, $edition) = explode(':', $info, 2);
                    }
                    $app_ids[$app_id] = array('vendor'=>$info, 'slug'=>$app_id);
                } else {
                    if (isset($info['slug'])) {
                        $app_id = $info['slug'];
                    }
                    $app_ids[$app_id] = $info;
                }
                $vendors[] = $info['vendor'];
                unset($info);
            }
            $vendors = array_unique($vendors);
            $model = new waAppSettingsModel();
            $license = $model->get('webasyst', 'license', false);
            $locale = wa()->getLocale();
            try {
                $log_level = waSystemConfig::isDebug()?waInstaller::LOG_DEBUG:waInstaller::LOG_WARNING;
                $thread_id = $this->getRequest()->request('thread_id', false);
                $updater = new waInstaller($log_level, $thread_id);
                $this->getStorage()->close();
                $updater->init();
                $apps = new waInstallerApps($license, $locale, false);
                $app = $this->getApp();

                if (isset($app_ids[$app])) {
                    #update system items
                    $system_list = $apps->getSystemList();
                    foreach ($system_list as $target=>$item) {
                        $this->add(!empty($item['target']) ? $item['target'] : $item['slug'], $item);
                    }
                }

                $app_list = $apps->getApplicationsList(false, $vendors, wa()->getDataPath('images', true));
                $model->ping();
                $this->pass = (count($this->urls) || (count($app_ids)>1))?true:false;
                $added = true;
                $execute_actions = array( waInstallerApps::ACTION_INSTALL, waInstallerApps::ACTION_CRITICAL_UPDATE, waInstallerApps::ACTION_UPDATE);
                while ($app_ids && $added) {

                    $added = false;
                    foreach ($app_list as &$info) {
                        $app_id = $info['slug'];
                        if ($app_id == 'installer') {
                            $info['name'] = _w('Webasyst Framework');
                        }

                        if (isset($app_ids[$app_id]) && installerHelper::equals($app_ids[$app_id], $info)) {
                            $target = 'wa-apps/'.$app_id;
                            $info['subject'] = 'app';
                            $this->add($target, $info, $app_id);
                            unset($app_ids[$app_id]);
                        }
                        if (isset($info['extras']) && is_array($info['extras'])) {
                            foreach ($info['extras'] as $subject=>$extras) {
                                foreach ($extras as $extras_id=>$extras_info) {
                                    $extras_id = $app_id.'/'.$subject.'/'.$extras_id;
                                    if (isset($app_ids[$extras_id]) && installerHelper::equals($app_ids[$extras_id], $extras_info)) {
                                        if( !empty($app_ids[$extras_id]['dependent']) && (empty($extras_info['action']) || !in_array($extras_info['action'],$execute_actions))) {
                                            continue;
                                        }
                                        $target = 'wa-apps/'.$extras_id;
                                        $extras_info['subject'] = 'app_'.$subject;
                                        $this->add($target, $extras_info, $extras_info['slug']);

                                        if ($extras_info['dependency']) {
                                            foreach($extras_info['dependency'] as $dependency) {
                                                $app_ids[$dependency] = $app_ids[$extras_id];
                                                $app_ids[$dependency]['slug'] = $dependency;
                                                $app_ids[$dependency]['dependent'] = $target;
                                                $added = true;
                                            }

                                        }
                                        if ($subject == 'themes') {
                                            if (!empty($extras_info['current']['parent_theme_id'])) {
                                                $parent_id = $extras_info['current']['parent_theme_id'];
                                                $parent_app_id = $app_id;
                                                if (strpos($parent_id,':')) {
                                                    list($parent_app_id, $parent_id) = explode(':', $parent_id);
                                                }
                                                $dependency = "{$parent_app_id}/{$subject}/{$parent_id}";
                                                $app_ids[$dependency] = $app_ids[$extras_id];
                                                $app_ids[$dependency]['slug'] = $dependency;
                                                $app_ids[$dependency]['dependent'] = $target;
                                                $added = true;
                                            }
                                        }
                                        unset($app_ids[$extras_id]);
                                    }
                                }
                            }
                        }
                    }
                    unset($info);
                }
                $storage = wa()->getStorage();
                $storage->close();
                $result_urls = $updater->update($this->urls);
                if (waRequest::get('install')) {
                    $model->ping();
                    $user = $this->getUser();
                    $set_rights = false;
                    if (!$user->isAdmin()) {
                        $set_rights = true;
                    }
                    foreach ($this->urls as $url) {
                        //TODO workaround exceptions
                        if (!isset($url['skipped']) || !$url['skipped']) {
                            $apps->installWebAsystItem($url['slug'], null , isset($url['edition'])?$url['edition']:true);
                            if ($set_rights) {
                                $user->setRight($url['slug'], 'backend', 2);
                            }
                        }
                    }
                }
                $secure_properties = array('archive', 'source', 'backup', 'md5', 'extract_path');
                foreach ($result_urls as &$result_url) {
                    foreach ($secure_properties as $property) {
                        if (isset($result_url[$property])) {
                            unset($result_url[$property]);
                        }
                    }
                    unset($result_url);
                }
                $this->response['sources'] = $result_urls;
                $this->response['current_state'] = $updater->getState();
                $this->response['state'] = $updater->getFullState(waRequest::get('mode', 'apps'));
                //cleanup cache
                //waFiles::delete(wa()->getAppCachePath(null, false), true);
                $path_cache = waConfig::get('wa_path_cache');
                waFiles::delete($path_cache, true);
                waFiles::protect($path_cache);
                $root_path = waConfig::get('wa_path_root');
                foreach ($this->urls as $url) {
                    if (!isset($url['skipped']) || !$url['skipped']) {
                        $path_cache = $root_path.'/'.$url['target'].'/js/compiled';
                        waFiles::delete($path_cache, true);
                    }
                }


                $model->ping();
                $this->getConfig()->setCount(false);

                $response = $this->getResponse();
                $response->addHeader('Content-Type', 'application/json; charset=utf-8');
                $response->sendHeaders();
            } catch(Exception $ex) {
                $this->setError($ex->getMessage());
            }
        } else {
            throw new Exception('nothing to update');
        }
    }

    protected function add($target, $info, $item_id = null)
    {

        $this->urls[$target] = array(
            'source'=>$info['download_link'],
            'target'=>$target,
            'slug'=>$target,
            'md5'=>!empty($info['md5']) ? $info['md5'] : null,
        );

        if ($item_id) {


            $this->urls[$target] = array_merge($this->urls[$target],
            array(
                'slug'=>$item_id,
                'pass'=>$this->pass && ($this->getAppId()!=$item_id),
                'name'=>$info['name'],
                'img'=>$info['img'],
                'update'=>$info['current']?true:false,
                'subject'=>empty($info['subject']) ? 'system' : $info['subject'],
                'edition'=>empty($info['edition']) ? true : $info['edition'],
            ));

        }
        if(false){
            $this->urls[$target] = array(
            'slug'=>$extras_info['slug'],
            'pass'=>$pass && ($this->getAppId()!=$app_id),
            'img'=>$extras_info['img'],
            'update'=>$extras_info['current']?true:false,
            'subject'=>'app_'.$subject,
            );
        }
    }
}
//EOF