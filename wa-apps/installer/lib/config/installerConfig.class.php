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

class installerConfig extends waAppConfig
{
    protected $application_config = array();

    public function init()
    {
        parent::init();
        require_once($this->getPath('installer', 'lib/init'));
    }

    public function onCount()
    {
        $args = func_get_args();
        $force = array_shift($args);

        $model = new waAppSettingsModel();
        $app_id = $this->getApplication();
        $count = null;

        //check cache expiration time
        if ($force || ((time() - $model->get($app_id, 'update_counter_timestamp', 0)) > 600) || is_null($count = $model->get($app_id, 'update_counter', null))) {
            $count = installerHelper::getUpdatesCounter('total');
            //check available versions for installed items
            //download if required changelog & requirements for updated items
            //count applicable updates (optional)
            $model->ping();
        } elseif (is_null($count)) {
            $count = $model->get($app_id, 'update_counter');
        }
        if ($count) {
            $count = array(
                'count' => $count,
                'url'   => $url = $this->getBackendUrl(true).$this->application.'/?module=update',
            );
        }
        return $count;
    }

    public function setCount($n = null)
    {
        wa()->getStorage()->open();
        $model = new waAppSettingsModel();
        $model->ping();
        $app_id = $this->getApplication();
        $model->set($app_id, 'update_counter', $n);
        $model->set($app_id, 'update_counter_timestamp', ($n === false) ? 0 : time());
        parent::setCount($n);
    }
}
//EOF
