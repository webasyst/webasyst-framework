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
        $this->options = ifempty($options, array()) + $this->options;
        foreach ($this->required as $k) {
            if (!isset($this->options[$k])) {
                throw new waException('Option '.$k.' is required');
            }
        }
    }

    /**
     * @abstract
     * @param string $code
     * @param string $error
     * @return bool
     */
    abstract public function isValid($code = null, &$error = '');

    /**
     * @abstract
     * @return string
     */
    abstract public function getHtml();

    /**
     * @abstract
     */
    abstract public function display();

    /**
     * @param null|string $key
     * @param mixed $default
     * @return array|mixed|null
     */
    public function getOption($key = null, $default = null)
    {
        return ($key) ? ifset($this->options, $key, $default) : $this->options;
    }
} 
