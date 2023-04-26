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

//

abstract class installerExtrasRemoveAction extends waViewAction
{
    protected $extras_type = false;
    /**
     *
     * @var waInstallerApps
     */
    protected $installer;

    abstract protected function removeExtras($app_id, $extras_id);


    private function init()
    {
        $url = parse_url($r = waRequest::server('HTTP_REFERER'), PHP_URL_QUERY);
        if (preg_match('/(^|&)module=(themes|plugins|widgets)($|&)/', $url, $matches)) {
            $this->extras_type = $matches[2];
        } elseif (preg_match('/^installer(\w+)RemoveAction$/', get_class($this), $matches)) {
            $this->extras_type = strtolower($matches[1]);
        }

        if (installerHelper::isDeveloper()) {
            switch ($this->extras_type) {
                case 'themes':
                    $msg = _w("Design theme not deleted: .svn or .git folder located in the theme directory.");
                    $msg .= "\n"._w("A .git or .svn directory has been detected. To ignore the developer mode, add option 'installer_in_developer_mode' => true to wa-config/config.php file.");
                    break;
                case 'plugins':
                    $msg = _w("Plugin not deleted: .svn or .git folder located in the plugin directory.");
                    $msg .= "\n"._w("A .git or .svn directory has been detected. To ignore the developer mode, add option 'installer_in_developer_mode' => true to wa-config/config.php file.");
                    break;
                case 'widgets':
                    $msg = _w("Unable to delete application's widgets (developer version is on)");
                    $msg .= "\n"._w("A .git or .svn directory has been detected. To ignore the developer mode, add option 'installer_in_developer_mode' => true to wa-config/config.php file.");
                    break;
                default:
                    $msg = '???';
                    break;
            }

            $msg = installerMessage::getInstance()->raiseMessage($msg, installerMessage::R_FAIL);
            $this->redirect('?msg='.$msg);
        }
    }

    public function execute()
    {
        $this->init();

        $extras_ids = waRequest::post('extras_id');
        try {
            /*
             _w('Themes not found');
             _w('Plugins not found');
             */
            foreach ($extras_ids as & $info) {
                if (!is_array($info)) {
                    $info = array('vendor' => $info);
                }
                unset($info);
            }

            $options = array(
                'installed' => true,
                'status'    => true
            );


            switch ($this->extras_type) {
                case 'plugins':
                    $options['system'] = true;
                    break;
                case 'widgets':
                    $options['widgets'] = true;
                    break;
            }

            $this->installer = installerHelper::getInstaller();
            $app_list = $this->installer->getItems($options);

            $queue = array();

            $options['local'] = true;
            foreach ($extras_ids as $slug => $info) {
                $slug = preg_replace('@^wa-widgets/@', 'webasyst/widgets/', $slug);
                $slug_chunks = explode('/', $slug);

                if ($slug_chunks[0] == 'wa-plugins') {
                    $app_id = $slug_chunks[0].'/'.$slug_chunks[1];
                } else {
                    $app_id = reset($slug_chunks);
                }
                if (isset($app_list[$app_id])) {
                    $app = $app_list[$app_id];
                    if (isset($slug_chunks[1]) && isset($slug_chunks[2]) && isset($app[$slug_chunks[1]][$slug_chunks[2]])) {
                        $installed = $app[$slug_chunks[1]][$slug_chunks[2]];
                    } else {
                        $installed = $this->installer->getItemInfo($slug, $options);
                    }
                    if ($installed) {
                        if ($info['vendor'] == $installed['vendor']) {
                            if (!empty($installed['installed']['system'])) {
                                /*
                                 _w("Unable to delete theme “%s”.");
                                 _w("Unable to delete plugin “%s”.");
                                _w("Cannot delete system app’s widgets \"%s\"");
                                 */

                                $message = "Cannot delete system app’s {$this->extras_type} \"%s\"";
                                throw new waException(sprintf(_w($message), _wd($slug, isset($info['name']) ? $info['name'] : '???')));
                            } elseif (!empty($installed['inbuilt'])) {
                                /*
                                _w("Cannot delete built-in app’s widgets \"%s\"");
                                 */
                                $message = "Cannot delete built-in app’s {$this->extras_type} \"%s\"";
                                throw new waException(sprintf(_w($message), _wd($slug, isset($info['name']) ? $info['name'] : '???')));
                            } elseif ($this->extras_type == 'themes' && empty($installed['installed']['parent_theme_id'])) {
                                $this->validateThemes($app_list, $app_id, $installed['installed']['id'], $slug);
                            }
                            $queue[] = array(
                                'real_slug' => $slug,
                                'app_slug'  => $app_id,
                                'ext_id'    => $installed['id'],
                                'name'      => sprintf("%s (%s)", _wd($slug, $installed['installed']['name']), _wd($app_id, $app['name'])),
                            );
                            unset($extras_ids[$slug]);
                        } else {
                            throw new waException(sprintf('Invalid item vendor: expected %s but get %s', $installed['vendor'], $info['vendor']));
                        }

                    } else {
                        //TODO force delete item
                    }
                }
            }
            $deleted_extras = $deleted_extras_slug = array();
            $ip = waRequest::getIp();
            foreach ($queue as $q) {
                if ($this->removeExtras($q['app_slug'], $q['ext_id'])) {

                    $params = array(
                        'type' => $this->extras_type,
                        'id'   => sprintf('%s/%s', $q['app_slug'], $q['ext_id']),
                        'name' => $q['name'],
                        'ip'   => $ip,
                    );

                    $this->logAction('item_uninstall', $params);
                    $deleted_extras[] = $q['name'];
                    $deleted_extras_slug[] = $q['real_slug'];
                }
            }

            if ($deleted_extras_slug) {
                $this->updateFactProducts($deleted_extras_slug);
            }

            if (!$deleted_extras) {
                $message = sprintf('Application %s not found', $this->extras_type);
                throw new waException(_w($message));
            }
            /*
             _w('Plugin “%s“ has been deleted.', 'Plugins “%s“ have been deleted.');
             _w('Theme “%s“ has been deleted.', 'Themes “%s“ have been deleted.');
            _w('Application widget %s has been deleted', 'Applications widgets %s have been deleted');
             */
            $message_singular = sprintf('App %s %%s has been deleted.', preg_replace('/s$/', '', $this->extras_type));
            $message_plural = sprintf('App %s %%s have been deleted.', $this->extras_type);
            $message = sprintf(_w($message_singular, $message_plural, count($deleted_extras), false), implode(', ', $deleted_extras));
            $msg = installerMessage::getInstance()->raiseMessage($message);
            $this->redirect('?msg='.$msg);
        } catch (Exception $ex) {
            waLog::log($ex->getMessage(), 'installer/remove.log');
            $msg = installerMessage::getInstance()->raiseMessage($ex->getMessage(), installerMessage::R_FAIL);
            $this->redirect('?msg='.$msg);
        }

    }

    /**
     * @param $apps
     * @param $app_id
     * @param $theme_id
     * @param $slug
     * @return void
     * @throws waException
     */
    private function validateThemes($apps, $app_id, $theme_id, $slug)
    {
        $parent_theme_id = $app_id . ':' . $theme_id;
        $app_names = [];
        foreach ($apps as $app) {
            if (!empty($app['themes'])) {
                foreach ($app['themes'] as $theme) {
                    if (isset($theme['installed']['parent_theme_id'])
                        && $theme['installed']['parent_theme_id'] == $parent_theme_id
                    ) {
                        $app_names[] = sprintf_wp('“%s”', _wd($app['id'], $app['name']));
                    }
                }
            }
        }
        if ($app_names) {
            $message = _w('Deletion of design theme “%s” for the “%s” app is unavailable because it is selected as a parent theme for the child themes of apps %s. Before deleting this design theme, either delete all its child themes or change their parent design theme.');

            throw new waException(sprintf(
                $message,
                _wd($slug, isset($apps[$app_id]['themes'][$theme_id]['name']) ? $apps[$app_id]['themes'][$theme_id]['name'] : '???'),
                _wd($app_id, $apps[$app_id]['name']),
                implode(', ', $app_names),
            ));
        }
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
