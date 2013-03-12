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
abstract class waContactFieldFormatter
{
    public abstract function format($data);

    protected $_type;
    protected $options;

    /**
     * Because of a specific way this class is saved and loaded via var_dump,
     * constructor parameters order and number cannot be changed in subclasses.
     * Subclasses also must always provide a call to parent's constructor.
     * @param array $options
     */
    public function __construct($options = null)
    {
        $this->_type = get_class($this);
        $this->options = $options;
    }

    public static function __set_state($state)
    {
         return new $state['_type']($state['options']);
    }
}

// EOF