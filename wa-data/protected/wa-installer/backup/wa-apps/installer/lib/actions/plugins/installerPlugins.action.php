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

class installerPluginsAction extends installerItemsAction
{
    protected $module = 'plugins';


    protected function getExtrasOptions()
    {
        $options = parent::getExtrasOptions();
        $options['local'] = true;
        return $options;
    }

    protected function getAppOptions()
    {
        return parent::getAppOptions() + array('system' => true);
    }

    protected function extendApplications(&$applications)
    {
        parent::extendApplications($applications);
        $system_plugins = array();
        foreach (array_keys($applications) as $id) {
            if (strpos($id, 'wa-plugins/') === 0) {
                $system_plugins[str_replace('wa-plugins/', '', $id)] = $id;
            }
        }
        if (!empty($system_plugins)) {
            $applicable = array();
            foreach ($applications as $id => &$info) {
                if (!isset($info['system_plugins'])) {
                    $info['system_plugins'] = array();
                }

                foreach ($system_plugins as $type => $slug) {
                    if (!empty($info['installed'][$type.'_plugins'])) {
                        $applicable[$slug] = true;
                        $info['system_plugins'][$slug] = $slug;
                    }
                }

                unset($info);
            }
            foreach ($system_plugins as $slug) {
                if (empty($applicable[$slug])) {
                    unset($applications[$slug]);
                }
            }
        }
    }
}
