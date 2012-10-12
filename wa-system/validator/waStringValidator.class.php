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
class waStringValidator extends waValidator
{

    protected function init()
    {
        parent::init();
        $this->setMessage('max_length', sprintf(_ws('No more than %d character please', 'No more than %d characters please', $this->getOption('max_length')), $this->getOption('max_length')));
        $this->setMessage('min_length', sprintf(_ws('No less than %d character please', 'No less than %d characters please', $this->getOption('min_length')), $this->getOption('min_length')));
    }

    public function isValid($value)
    {
        parent::isValid($value);

        if (($length = $this->getOption('max_length', false)) && mb_strlen($value) > $length) {
            $this->setError($this->getMessage('max_length', array('value' => $value)));
        }

        if (($length = $this->getOption('min_length', false)) && mb_strlen($value) < $length) {
            $this->setError($this->getMessage('min_length', array('value' => $value)));
        }

        return $this->getErrors() ? false : true;
    }

}