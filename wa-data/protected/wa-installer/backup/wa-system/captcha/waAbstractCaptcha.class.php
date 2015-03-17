<?php

/*
* This file is part of Webasyst framework.
*
* Licensed under the terms of the GNU Lesser General Public License (LGPL).
* http://www.webasyst.com/framework/license/
*
* @link http://www.webasyst.com/
* @author Webasyst LLC
* @copyright 2011-2012 Webasyst LLC
* @package wa-system
* @subpackage captcha
*/
abstract class waAbstractCaptcha
{
    protected $options = array();
    protected $required = array();

    /**
     * @param array $options
     * @throws waException
     */
    public function __construct($options = array())
    {
        $this->options = $options + $this->options;
        foreach ($this->required as $k) {
            if (!isset($this->options[$k])) {
                throw new waException('Option '.$k.' is required');
            }
        }
    }

    /**
     * @abstract
     * @param string $code
     * @return bool
     */
    abstract public function isValid($code = null);

    /**
     * @abstract
     * @return string
     */
    abstract public function getHtml();

    /**
     * @abstract
     */
    abstract public function display();
} 