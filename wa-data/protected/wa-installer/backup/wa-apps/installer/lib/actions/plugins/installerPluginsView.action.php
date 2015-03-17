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

class installerPluginsViewAction extends installerItemsAction
{
    protected $module = 'plugins';


    protected function getAppOptions()
    {
        $options = parent::getAppOptions();
        if (preg_match('@^wa-plugins/@', waRequest::get('slug'))) {
            $options += array('system' => true);
        }
        return $options;
    }

    public function execute()
    {
        parent::execute();
        $return_url = waRequest::get('return_url', waRequest::server('HTTP_REFERER'));
        if ($return_hash = waRequest::get('return_hash')) {
            if ($return_hash = preg_replace('@^#@', '', $return_hash)) {
                $return_url .= '#'.$return_hash;
            }
        }
        $this->view->assign('top', !!preg_match('@^[^/]+$@', waRequest::get('slug')) && !waRequest::get('filter'));
        $this->view->assign('return_url', $return_url);
    }
}
//EOF