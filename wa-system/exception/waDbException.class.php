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
 * @package wa-system
 * @subpackage database
 */
class waDbException extends waException
{
    public $real_message;

    public function __construct($message = '', $code = 500, $previous = null)
    {
        $this->real_message = $new_message = $message;
        if (!waSystemConfig::isDebug() && !defined('WA_TEST_ENVIRONMENT')) {
            $new_message = "Database error. See log for details.";
        }
        parent::__construct($new_message, $code, $previous);
        if (!waConfig::get('disable_exception_log')) {
            waLog::log($message."\n".$this->getFullTraceAsString(), 'db.log');
        }
    }
}
