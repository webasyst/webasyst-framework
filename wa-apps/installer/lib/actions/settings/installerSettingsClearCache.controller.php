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

class installerSettingsClearCacheController extends waJsonController
{
    public function execute()
    {
        try {
            $errors = installerHelper::flushCache();

            $this->response['message'] = _w('Cache cleared');
            if ($errors) {
                $this->response['message'] .= "<br>"._w('But with errors:')."<br>".implode("<br>", $errors);
            }
        } catch (Exception $ex) {
            $this->setError($ex->getMessage());
        }
    }
}
