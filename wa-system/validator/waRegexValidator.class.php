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
class waRegexValidator extends waStringValidator
{
    protected $options = array(
        'required' => false,
        'pattern' => '//'
    );

    protected function init()
    {
        parent::init();
        $this->setMessage('not_match', _ws('Not match.'));
    }

    public function setPattern($pattern)
    {
        $this->setOption('pattern', $pattern);
    }

    public function getPattern()
    {
        return $this->getOption('pattern');
    }

    public function isValid($value)
    {
        parent::isValid($value);

        if (!$this->isEmpty($value)) {
            $pattern = $this->getPattern();
            if (!preg_match($pattern, $value)) {
                $this->setError($this->getMessage('not_match', array('value' => $value)));
            }
        }

        return $this->getErrors() ? false : true;
    }
}