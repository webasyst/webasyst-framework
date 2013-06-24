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
class waValidator
{

    protected $messages;

    protected $options = array(
        'required' => false,
    );

    protected $errors = array();

        protected $_type;

    /**
     * Because of a specific way this class is saved and loaded via var_dump,
     * constructor parameters order and number cannot be changed in subclasses.
     * Subclasses also must always provide a call to parent's constructor.
     * @param array $options
     * @param array $messages
     */
    public function __construct($options = array(), $messages = array())
    {
        $this->messages = array(
            'required' => _ws('Required'),
            'invalid' => _ws('Invalid'),
        );
        foreach ($messages as $k => $v) {
            $this->messages[$k] = $v;
        }
        foreach ($options as $k => $v) {
            $this->options[$k] = $v;
        }
        $this->_type = get_class($this);
        $this->init();
    }

    protected function init()
    {

    }

    /**
     * @param string|array $name
     * @param string $value
     */
    public function setOption($name, $value = null)
    {
        if (is_array($name) && $value === null) {
            foreach ($name as $k => $v) {
                $this->options[$k] = $v;
            }
        } else {
            $this->options[$name] = $value;
        }
    }

    protected function getOption($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    public function isValid($value)
    {
        $this->clearErrors();
        if ($this->getOption('required', false) && $this->isEmpty($value)) {
            $this->setError($this->getMessage('required', array('value' => $value)));
        }

        return $this->getErrors() ? false : true;
    }

    public function getErrors()
    {
        return $this->errors;
    }


    protected function clearErrors()
    {
        $this->errors = array();
    }

    public function setMessage($name, $message)
    {
        $this->messages[$name] = $message;
    }

    public function getMessage($name, $variables = array())
    {
        $message = isset($this->messages[$name]) ? $this->messages[$name] : _ws('Invalid');
        foreach ($variables as $k => $v) {
            $message = str_replace('%'.$k.'%', $v, $message);
        }
        return $message;
    }

    protected function setError($error, $code = null)
    {
        if ($code !== null) {
            $this->errors[$code] = $error;
        } else {
            $this->errors[] = $error;
        }
    }

    protected function isEmpty($value)
    {
        return $value === '' || $value === null || $value === array();
    }

    public static function __set_state($state) {
         return new $state['_type']($state['options'], $state['messages']);
    }
}

// EOF