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

abstract class installerExtrasRemoveAction extends waViewAction
{
    protected $extras_type = false;
    /**
     *
     * @var waInstallerApps
     */
    protected $installer;
    abstract protected function removeExtras($app_id, $extras_id, $info);
    function execute()
    {
        if (!$this->extras_type && preg_match('/^installer(\w+)RemoveAction$/', get_class($this), $matches)) {
            $this->extras_type = strtolower($matches[1]);
        }

        $module = $this->extras_type;
        $url = parse_url(waRequest::server('HTTP_REFERER'), PHP_URL_QUERY);
        if (preg_match("/(^|&)module=(update|apps|{$this->extras_type})($|&)/", $url, $matches)) {
            $module = $matches[2];
        }

        $extras_ids = waRequest::get('extras_id');
        try {
            /*
             _w('Application themes not found');
             _w('Application plugins not found');
             */
            //$message =  sprintf('Application %s not found', $this->extras_type);
            //throw new waException(_w($message));
            if (installerHelper::isDeveloper()) {

                /*
                 _w("Unable to delete application's themes (developer version is on)");
                 _w("Unable to delete application's plugins (developer version is on)");
                 */

                $message =  "Unable to delete application's {$this->extras_type} (developer version is on)";
                throw new waException(_w($message));
            }
            $vendors = array();
            foreach ($extras_ids as $extras_id=>&$info) {
                if (!is_array($info)) {
                    $info = array('vendor'=>$info);
                }
                $vendors[] = $info['vendor'];
                unset($info);
            }
            $vendors = array_unique($vendors);
            $locale = wa()->getLocale();

            $this->installer = new waInstallerApps(null, $locale);
            $app_list = $this->installer->getApplicationsList(true);
            $deleted_extras = array();
            foreach ($app_list as $app) {
                if (isset($app['extras']) && $app['extras'] && isset($app['extras'][$this->extras_type]) && $app['extras'][$this->extras_type]) {
                    foreach ($app['extras'][$this->extras_type] as $extras_id=>$info) {
                        $slug = $info['slug'];
                        if (isset($extras_ids[$slug]) && ($extras_ids[$slug]['vendor'] == $info['current']['vendor'])) {
                            if (isset($info['system']) && $info['system']) {
                                /*
                                 _w("Can not delete system application's themes \"%s\"");
                                 _w("Can not delete system application's plugins \"%s\"");
                                 */

                                $message =  "Can not delete system application's {$this->extras_type} \"%s\"";
                                throw new waException(sprintf(_w($message), $info['name']));
                            }
                            if ($this->removeExtras($app['slug'], $extras_id, $info)) {
                                $deleted_extras[] = "{$info['name']} ({$app['name']})";
                            }
                        }
                    }
                }
            }
            if (!$deleted_extras) {
                $message =  sprintf('Application %s not found', $this->extras_type);
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
            $this->redirect(array('module'=>$module, 'msg'=>$msg));
        } catch(Exception $ex) {
            $msg = installerMessage::getInstance()->raiseMessage($ex->getMessage(), installerMessage::R_FAIL);
            $this->redirect(array('module'=>$module, 'msg'=>$msg));
        }

    }
}
