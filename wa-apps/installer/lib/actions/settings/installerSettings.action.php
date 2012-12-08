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

class installerSettingsAction extends waViewAction
{
    public function execute()
    {
        $model = new waAppSettingsModel();
        $settings = array(
			'name'					=> 'Webasyst',
			'url'					=> wa()->getRootUrl(true),
        //'auth_type'				=> 'login',
			'auth_form_background'	=> null,
			'auth_form_background_stretch'=>1,
        //'license'=>'',
			'locale'				=> 'ru_RU',
			'email'					=> '',
			'rememberme'			=> 1,
        );

        $config_settings = array(
			'debug'					=> 'boolean',
        );

        $flush_settings = array('debug');

        $config_path = waSystem::getInstance()->getConfigPath().'/config.php';
        $config = file_exists($config_path)?include($config_path):array();
        if (!is_array($config)) {
            $config = array();
        }

        $changed = false;
        $flush = false;
        $message = array();
        try {
            $messages = installerMessage::getInstance()->handle(waRequest::get('msg'));
            foreach ($settings as $setting => &$value) {
                if (waRequest::post() && !in_array($setting, array('auth_form_background'))) {
                    $post_value = waRequest::post($setting, '', 'string_trim');
                    if (!is_null($post_value)) {
                        $model->set('webasyst', $setting, $post_value);
                        $changed = true;
                    } elseif (!is_null($value)) {
                        $model->set('webasyst', $setting, '');
                    }
                    $value = $model->get('webasyst', $setting, $value);
                } else {
                    $value = $model->get('webasyst', $setting, $value);
                }
                unset($value);
            }
            $config_changed = false;
            if (waRequest::post()) {
                $config_values = waRequest::post('config');
                if (!is_array($config_values)) {
                    $config_values = array();
                }
                foreach ($config_settings as $setting=>$type) {
                    $value = isset($config_values[$setting]) ? $config_values[$setting] : false;
                    switch ($type) {
                        case 'boolean': {
                            $value = $value?true:false;
                            break;
                        }
                    }
                    if (!isset($config[$setting]) || ($config[$setting] !== $value)) {
                        $config[$setting] = $value;
                        $config_changed = true;
                        if (in_array($setting, $flush_settings)) {
                            $flush = true;
                        }
                    }
                }
                if ($config_changed) {
                    waUtils::varExportToFile($config, $config_path);
                }
                if ($flush) {
                    $path_cache = waConfig::get('wa_path_cache');
                    waFiles::delete($path_cache, true);
                    waFiles::protect($path_cache);
                }

                $model->ping();


            }

            if ($changed || $config_changed) {
                $message[] ='[`Settings saved`]';
            }
            $name = preg_replace('/\?.*$/', '', $settings['auth_form_background']);
            $path = wa()->getDataPath($name, true, 'webasyst');
            $file = waRequest::file('auth_form_background');
            if ($file->uploaded()) {
                if ($name) {
                    waFiles::delete(wa()->getDataPath($name, true, 'webasyst'));
                    $model->set('webasyst', 'auth_form_background', false);
                    $settings['auth_form_background'] = false;
                }
                $ext = 'png';
                if (preg_match('/\.(png|gif|jpg|jpeg|bmp|tif)$/i', $file->name, $matches)) {
                    $ext = $matches[1];
                }
                $name = 'auth_form_background.'.$ext;
                $path = wa()->getDataPath($name, true, 'webasyst');
                try {
                    $image = $file->waImage();
                } catch (waException $ex) {
                    $message = $ex->getMessage();
                    $tmp_name = $file->tmp_name;
                    if (!preg_match('//u', $tmp_name)) {
                        $tmp_name = iconv('windows-1251', 'utf-8', $tmp_name);
                    }
                    if (strpos($message, $tmp_name) !== false) {
                        throw new waException(preg_replace('/:\s*$/', '', str_replace($tmp_name, '', $message)));
                    }
                    throw $ex;
                }
                $file->copyTo($path);
                //$image->save($path);
                $name .= '?'.time();
                $model->set('webasyst', 'auth_form_background', $name);
                $settings['auth_form_background'] = $name;
                $message[] = '[`Image uploaded`]';
                $image_info = get_object_vars($image);
                $image_info['file_size'] = filesize($path);
                $image_info['file_name'] = basename($path);
                $this->view->assign('image', $image_info);
            }

            if ($settings['auth_form_background'] && file_exists($path)) {
                $image = new waImage($path);
                $image_info = get_object_vars($image);
                $image_info['file_size'] = filesize($path);
                $image_info['file_name'] = basename($path);
                $this->view->assign('image', $image_info);
                unset($image);
            }

            if ($message) {
                $params = array();
                $params['module']='settings';
                $params['msg'] =installerMessage::getInstance()->raiseMessage(implode(', ', $message));
                $this->redirect($params);
            }

        } catch (waException $ex) {
            $msg = installerMessage::getInstance()->raiseMessage($ex->getMessage(), installerMessage::R_FAIL);
            $params = array(
				'module'=>'settings',
				'msg'=>$msg
            );
            if ($message) {
                //$params['success'] = base64_encode(implode(', ', $message));
            }
            $this->redirect($params);
        }

        $this->view->assign('update_counter', $model->get($this->getApp(), 'update_counter'));
        //$this->view->assign('install_counter', $model->get($this->getApp(), 'install_counter'));
        $apps = wa()->getApps();
        $version = $apps['installer']['version'];
        if (abs(time() - $apps['installer']['build']) > 2) {
            $version .= '.'.$apps['installer']['build'];
        }
        $this->view->assign('version', $version);


        $this->view->assign('settings', $settings);
        $this->view->assign('config', $config);
        $this->view->assign('action', 'settings');
        $this->view->assign('messages', $messages);
        $locales = waSystem::getInstance()->getConfig()->getLocales('name');
        $this->view->assign('locales', $locales);
        $this->view->assign('title', _w('Settings'));
    }
}
//EOF