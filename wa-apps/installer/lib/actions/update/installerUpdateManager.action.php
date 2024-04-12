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

    private $is_install = false;
    private $is_trial = false;
    private $urls;

    private function init()
    {
        $this->is_install = (bool)waRequest::request('install', false);
        $this->is_trial = (bool)waRequest::request('trial', false);
        $url = parse_url($r = waRequest::server('HTTP_REFERER', ''), PHP_URL_QUERY);
        if (is_string($url) && preg_match('/(^|&)module=(update|apps|plugins|widgets)($|&)/', $url, $matches)) {
            $this->module = $matches[2];
        }

        if (installerHelper::isDeveloper()) {
            if ($this->is_install) {
                $msg = _w('Unable to install the product (developer mode is on).');
            } else {
                $msg = _w('Unable to update the product (developer mode is on).');
            }
            $msg .= "\n"._w("A .git or .svn directory has been detected. To ignore the developer mode, add option 'installer_in_developer_mode' => true to wa-config/config.php file.");
            return $this->signalFailMessage($msg);
        }
    }

    public function execute()
    {
        $this->init();
        $trial_dir = waTheme::getTrialUrl();

        try {
            $updater = new waInstaller(waInstaller::LOG_TRACE);
            $state = $updater->getState();
            if (!isset($state['stage_status'])
                || $state['stage_status'] == waInstaller::STATE_COMPLETE
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

                $count_installer_dependencies = 0;
                foreach ($items as $app_id => $info) {
                    if (!empty($info['download_url'])
                        && !empty($info['applicable'])
                        && in_array($info['action'], $execute_actions)
                    ) {
                        $info['subject'] = 'app';
                        if ($app_id == 'installer') {
                            foreach ($info['download_url'] as $target => $url) {
                                $_info = $info;
                                $_info['download_url'] = $url;
                                $_info['name'] = _w('Webasyst framework').' ('.$target.')';
                                $this->add($target, $_info);
                                $queue_apps[$target] = $_info;
                                $count_installer_dependencies++;
                                unset($_info);
                            }
                        } else {
                            $target = 'wa-apps/'.$app_id;
                            $this->add($target, $info, $app_id);
                            $queue_apps[$target] = $info;
                        }
                    }

                    foreach (array('themes', 'plugins', 'widgets') as $type) {
                        if (!empty($info[$type]) && is_array($info[$type])) {
                            foreach ($info[$type] as $extra_id => $extras_info) {
                                if (!empty($extras_info['download_url'])
                                    && !empty($extras_info['applicable'])
                                    && in_array($extras_info['action'], $execute_actions)
                                ) {
                                    $extras_info['subject'] = 'app_'.$type;
                                    if (($type == 'themes') && is_array($extras_info['download_url'])) {
                                        waFiles::delete(waTheme::getTrialPath(), true);
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
                                                    if ($this->is_trial) {
                                                        $target = $trial_dir.$__info['slug'];
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
                                        } elseif (($app_id == 'webasyst') && ($type == 'widgets')) {
                                            $target = 'wa-widgets/'.$extra_id;
                                        } elseif (($app_id == 'wa-widgets')) {
                                            $target = 'wa-widgets/'.$extra_id;
                                        } elseif ($type == 'themes' && $this->is_trial) {
                                            waFiles::delete(waTheme::getTrialPath(), true);
                                            $target = $trial_dir.$app_id.'/'.$type.'/'.$extra_id;
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
                    $updater->flush();
                    throw new waException(_w('Please select items for update'));
                }

                $this->ensureLayout();

                $this->view->assign([
                    'action' => 'update',
                    'queue_apps' => $queue_apps,
                    'count_installer_dependencies' => $count_installer_dependencies,
                    'install' => !empty($this->is_install) ? 'install' : '',
                    'trial' => !empty($this->is_trial) ? 'trial' : '',
                    'title' => _w('Updates'),
                    'thread_id' => $state['thread_id'],
                    'update_execute_params' => [
                        'thread_id' => $state['thread_id'],
                        'mode' => 'raw',
                        'install' => !empty($this->is_install) ? 1 : 0,
                        'trial' => !empty($this->is_trial) ? 1 : 0,

                        /*'app_id' => array_map(function($target, $item) {
                            return [
                                'subject' => ifset($item, 'subject', 'app'),
                                'slug' => $target,
                                'vendor' => $item['vendor'],
                                'edition' => ifset($item, 'edition', '',
                                'id' => ifset($item, 'id', ''),
                            ];
                        }, array_keys($queue_apps), array_values($queue_apps)),*/
                    ],
                    'update_state_params' => [
                        'mode' => 'raw',
                    ],
                ]);

                $return_url = waRequest::post('return_url', null, waRequest::TYPE_STRING_TRIM);
                if (empty($return_url) && waRequest::post('additional_updates', 0, waRequest::TYPE_INT)) {
                    $this->view->assign('additional_updates', true);
                    $return_url = '?module=update&auto_submit=1';
                }
                $this->view->assign('return_url', $return_url);
                $path = wa()->getCachePath(sprintf('update.%s.php', $state['thread_id']), 'installer');
                waUtils::varExportToFile($this->urls, $path);

            } else {
                $msg = _w('Update is already in progress. Please wait while the current update session is completed before starting a new session.');
                return $this->signalFailMessage($msg);
            }
        } catch (Exception $ex) {
            return $this->signalFailMessage($ex->getMessage());
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
            'source'    => $info['download_url'],
            'target'    => $target,
            'slug'      => $target,
            'real_slug' => $info['slug'],
            'md5'       => !empty($info['md5']) ? $info['md5'] : null,
        );

        if ($this->is_trial) {
            $this->urls[$target]['source'] .= '&trial=1';
        }

        if ($item_id) {
            $this->urls[$target] = array_merge($this->urls[$target], array(
                'slug'      => $item_id,
                'real_slug' => $info['slug'],
                'pass'      => false && ($this->getAppId() != $item_id),
                'name'      => $info['name'],
                'icon'      => $info['icon'],
                'update'    => !empty($info['installed']),
                'subject'   => empty($info['subject']) ? 'system' : $info['subject'],
                'edition'   => empty($info['edition']) ? true : $info['edition'],
            ));
        }
    }

    // Overriden in installerUpdateManagerDialogAction
    protected function signalFailMessage($msg)
    {
        $this->redirect(array(
            'module' => $this->module,
            'msg'    => installerMessage::getInstance()->raiseMessage($msg, installerMessage::R_FAIL),
        ));
    }

    protected function ensureLayout()
    {
        if (!waRequest::get('_')) {
            $this->setLayout(new installerBackendStoreLayout());
            $this->getLayout()->assign('no_ajax', true);
        }
    }
}
