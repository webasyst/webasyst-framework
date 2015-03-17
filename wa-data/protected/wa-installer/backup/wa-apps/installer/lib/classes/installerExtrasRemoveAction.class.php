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
        if (preg_match('/(^|&)module=(themes|plugins)($|&)/', $url, $matches)) {
            $this->extras_type = $matches[2];
        } elseif (preg_match('/^installer(\w+)RemoveAction$/', get_class($this), $matches)) {
            $this->extras_type = strtolower($matches[1]);
        }

        if (installerHelper::isDeveloper()) {
            switch ($this->extras_type) {
                case 'themes':
                    $msg = _w("Unable to delete application's themes (developer version is on)");
                    break;
                case 'plugins':
                    $msg = _w("Unable to delete application's plugins (developer version is on)");
                    break;
                default:
                    $msg = '???';
                    break;
            }

            $msg = installerMessage::getInstance()->raiseMessage($msg, installerMessage::R_FAIL);
            $this->redirect('?msg='.$msg.'#/'.$this->extras_type.'/');
        }
    }

    public function execute()
    {
        $this->init();

        $extras_ids = waRequest::post('extras_id');
        try {
            /*
             _w('Application themes not found');
             _w('Application plugins not found');
             */
            foreach ($extras_ids as & $info) {
                if (!is_array($info)) {
                    $info = array('vendor' => $info);
                }
                unset($info);
            }

            $options = array(
                'installed' => true,
                'local'     => true,
            );


            if ($this->extras_type == 'plugins') {
                $options['system'] = true;
            }

            $this->installer = installerHelper::getInstaller();
            $app_list = $this->installer->getItems($options);

            $queue = array();


            foreach ($extras_ids as $slug => $info) {
                $slug_chunks = explode('/', $slug);
                if ($slug_chunks[0] == 'wa-plugins') {
                    $app_id = $slug_chunks[0].'/'.$slug_chunks[1];
                } else {
                    $app_id = reset($slug_chunks);
                }
                if (isset($app_list[$app_id]) || ($slug_chunks == 'wa-plugins')) {
                    $app = $app_list[$app_id];
                    if (($installed = $this->installer->getItemInfo($slug, $options)) && ($info['vendor'] == $installed['vendor'])) {
                        if (!empty($installed['installed']['system'])) {
                            /*
                             _w("Can not delete system application's themes \"%s\"");
                             _w("Can not delete system application's plugins \"%s\"");
                             */

                            $message = "Can not delete system application's {$this->extras_type} \"%s\"";
                            throw new waException(sprintf(_w($message), _wd($slug, isset($info['name'])?$info['name']:'???')));
                        }
                        $queue[] = array(
                            'app_slug' => $app_id,
                            'ext_id'   => $installed['id'],
                            'name'     => sprintf("%s (%s)", _wd($slug, $installed['installed']['name']), _wd($app_id, $app['name'])),
                        );
                        unset($extras_ids[$slug]);
                    }
                }
            }

            $deleted_extras = array();
            foreach ($queue as $q) {
                if ($this->removeExtras($q['app_slug'], $q['ext_id'])) {
                    $deleted_extras[] = $q['name'];
                }
            }

            if (!$deleted_extras) {
                $message = sprintf('Application %s not found', $this->extras_type);
                throw new waException(_w($message));
            }
            /*
             _w('Application plugin %s has been deleted', 'Applications plugins %s have been deleted');
             _w('Application theme %s has been deleted', 'Applications themes %s have been deleted');
             */
            $message_singular = sprintf('Application %s %%s has been deleted', preg_replace('/s$/', '', $this->extras_type));
            $message_plural = sprintf('Applications %a %%s have been deleted', $this->extras_type);
            $message = sprintf(_w($message_singular, $message_plural, count($deleted_extras), false), implode(', ', $deleted_extras));
            $msg = installerMessage::getInstance()->raiseMessage($message);
            $this->redirect('?msg='.$msg.'#/'.$this->extras_type.'/');
        } catch (Exception $ex) {
            $msg = installerMessage::getInstance()->raiseMessage($ex->getMessage(), installerMessage::R_FAIL);
            $this->redirect('?msg='.$msg.'#/'.$this->extras_type.'/');
        }

    }
}
