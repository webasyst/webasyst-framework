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

class installerUpdateDownloadlogController extends waController
{
    public function execute()
    {
        $installer = new waInstaller();
        waFiles::readFile($installer->getLogPath(), 'update.txt');
    }
}
