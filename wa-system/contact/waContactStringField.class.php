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
 * @subpackage contact
 */
class waContactStringField extends waContactField
{
    public function init()
    {
        if (!isset($this->options['validators'])) {
            $this->options['validators'] = new waStringValidator($this->options);
        }    
    }

    public function getInfo()
    {
        $info = parent::getInfo();
        $info['input_height'] = $this->getParameter('input_height');
        return $info;
    }
    
    /** Get the current value of parameter $p.
      * Used by a field constructor to access field parameters.
      *
      * waContactStringField has one parameter: input_height, integer from 1 to 5.
      * 
      * @param $p string parameter to read */
    public function getParameter($p) {
        if ($p == 'input_height') {
            if (!isset($this->options['input_height'])) {
                $this->options['input_height'] = 1;
            }
            return $this->options['input_height'];
        }
        return parent::getParameter($p);
    }

    /** Set the value of parameter $p.
      * Used by a field constructor to change field parameters.
      *
      * waContactStringField has one parameter: input_height, integer from 1 to 5.
      * 
      * @param $p string parameter to set
      * @param $value mixed value to set */
    public function setParameter($p, $value) {
        if ($p == 'input_height') {
            $value = (int) $value;
            if ($value < 1) {
                $value = 1;
            } else if ($value > 5) {
                $value = 5;
            }
            $this->options['input_height'] = $value;
            return;
        }
        
        parent::setParameter($p, $value);
    }
}

// EOF