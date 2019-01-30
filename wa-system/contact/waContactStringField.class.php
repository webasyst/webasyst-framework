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

    /**
     * Get the current value of parameter $p.
     * Used by a field constructor to access field parameters.
     *
     * waContactStringField has one parameter: input_height, integer from 1 to 5.
     *
     * @param string $p parameter to read
     * @return array|null
     */
    public function getParameter($p)
    {
        if ($p == 'input_height') {
            if (!isset($this->options['input_height'])) {
                $this->options['input_height'] = 1;
            }
            return $this->options['input_height'];
        }
        return parent::getParameter($p);
    }

    /**
     * Set the value of parameter $p.
     * Used by a field constructor to change field parameters.
     *
     * waContactStringField has one parameter: input_height, integer from 1 to 5.
     *
     * @param string $p parameter to set
     * @param mixed $value value to set
     * @return void
     */
    public function setParameter($p, $value)
    {
        if ($p == 'input_height') {
            $value = (int) $value;
            if ($value < 1) {
                $value = 1;
            } else if ($value > 5) {
                $value = 5;
            }
            $this->options['input_height'] = $value;
        } else {
            parent::setParameter($p, $value);
        }
    }

    public function getHtmlOne($params = array(), $attrs = '')
    {
        if ($this->getParameter('input_height') <= 1) {
            return parent::getHtmlOne($params, $attrs);
        }

        $value = isset($params['value']) ? $params['value'] : '';
        $name = $this->getName(null, true);
        if (!empty($params['placeholder'])) {
            $attrs .= ' placeholder="'.$name.'"';
        }
        return '<textarea '.$attrs.' name="'.$this->getHTMLName($params).'" title="'.$name.'">'.htmlspecialchars($value).'</textarea>';
    }
}

// EOF