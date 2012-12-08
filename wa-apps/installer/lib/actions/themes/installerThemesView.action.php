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

class installerThemesViewAction extends waViewAction
{
    public function execute()
    {
        $filter = array();

        $filter['enabled'] = true;
        $filter['extras'] = 'themes';

        $app = false;
        $messages = array();
        $applications = installerHelper::getApps($messages, $update_counter, $filter);

        $search = array();

        $search['slug'] = $app_id = waRequest::get('slug');
        $search['vendor'] = waRequest::get('vendor', 'webasyst');
        $themes = wa()->getThemes($search['slug']);

        if (array_filter($search, 'strlen') && ($app = installerHelper::search($applications, $search))) {
            foreach($app['extras']['themes'] as $id => $info) {
                if(!isset($themes[$id])) {
                    $themes[$id] = new waTheme($id,$search['slug'], true);
                    $themes[$id]['name'] = $info['name'];
                    $themes[$id]['description'] = $info['description'];
                }
                if(!empty($info['payware'])) {
                    $themes[$id]['payware'] = $info['payware'];
                }
                if(!empty($info['img'])) {
                    $themes[$id]['cover'] = $info['img'];
                }
            }

            $this->view->assign('themes', $themes);
            $this->view->assign('messages', $messages);
            $this->view->assign('app_id', $app_id);
            $this->view->assign('app', $app);
            $this->view->assign('slug', $search['slug']);
            $this->view->assign('vendor', $search['vendor']);
        }
    }
}
//EOF