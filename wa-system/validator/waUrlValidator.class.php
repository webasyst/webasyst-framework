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
 * @subpackage validator
 */
class waUrlValidator extends waRegexValidator
{
    const REGEX_URL = '`^(https?|ftp|gopher|telnet|file|notes|ms-help):((//)|(\\\\\\\\))+([^[:punct:]]|[:#@%/;$()~_?\+-=\.&\\\\])*$`iu';

    protected function init()
    {
        $this->setMessage('not_match', 'Invalid URL');
        $this->setPattern(self::REGEX_URL);
    }

    public function isValid($value)
    {
        parent::isValid($value);

        // more restrictions for common protocols
        if (!$this->isEmpty($value) && !$this->getErrors() && ('http' == substr($value, 0, 4) || 'ftp' == substr($value, 0, 3))) {
            if (!preg_match('`^(https?|ftp):((//)|(\\\\\\\\))+((?:([^[:punct:]]|-)+\\.)+[^[:punct:]]{2,7})((/|\\|#)([^[:punct:]]|[:#@%/;$()~_?\+-=\.&\\\\])*)?$`ui', $value)) {
                $this->setError($this->getMessage('not_match', array('value' => $value)));
            }
        }
        return !$this->getErrors();
    }
}

// EOF
