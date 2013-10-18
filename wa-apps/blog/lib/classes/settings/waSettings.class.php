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
 * @subpackage settings
 */
abstract class waSettings implements Countable, ArrayAccess, Iterator, Serializable
{
    const TYPE_INT		 = 'int';
    const TYPE_FLOAT	 = 'float';
    const TYPE_POSITIVE	 = 'positive';
    const TYPE_TEXT		 = 'text';
    const TYPE_BOOLEAN	 = 'boolean';
    const TYPE_CUSTOM	 = 'custom';
    /**
     * @var array Array of settings
     */
    protected $settings = null;
    /**
     * @var array Array of flags for changed settings
     */
    protected $settingsChanged = array();
    /**
     *
     * @var bool store setting at storage on destroy
     */
    protected $approvedSettings = false;
    /**
     * @var array Pointer to custom properties of settings
     */
    protected $settingsParams = null;
    /**
     * @var int
     */
    protected $settingsCounter = 0;
    /**
     * @var string Name of settings instance
     */
    protected $name;


    protected function __construct($name = __CLASS__)
    {
        $this->name = $name;
    }

    function __destruct()
    {
        if ($this->settingsChanged) {
            //$this->save();
        }
    }

    /**
     * @todo check data structure
     * @param mixed $settingsParams
     * @return void
     */
    public function initSettingsParams(&$settingsParams)
    {
        $this->settingsParams = &$settingsParams;
    }


    abstract protected function load();

    /**
     * @todo use Cast to check type
     * @param $name
     * @param $value
     * @return void
     */
    final protected function loadSetting($name,$value) {
        if (is_array($_value = @unserialize($value))) {
            $value = $_value;
        } elseif (!is_null($_value = @json_decode($value,true))) {
            $value = $_value;
        } elseif (isset($this->settingsParams[$name])&&isset($this->settingsParams[$name]['settings_type'])&&($this->settingsParams[$name]['settings_type']=='array')) {
            $value = array();
        }
        $this[$name] = $value;
    }

    /**
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    abstract protected function saveSetting($name,$value);

    /**
     * Store settings at storage
     * @param bool $force
     * @return void
     */
    public function save($force = false)
    {
        $settingsChanged = $force? $this->settingsChanged: array_fill_keys(array_keys($this->settings), true);
        $updated = false;
        if (is_array($settingsChanged)) {
            foreach ($settingsChanged as $name=>&$changed) {
                if ($changed) {
                    $updated = true;
                    if (!isset($this->settingsParams[$name])||!isset($this->settingsParams[$name]['settings_store'])||($this->settingsParams[$name]['settings_store']=='mysql')) {
                        $value = $this->settings[$name];
                        if (is_object($value)&&isset($this->settingsParams[$name]['settings_object'])) {
                            $class =get_class($value);
                            if (class_exists($class)&&in_array('waSettingWrapper',class_parents($class))) {
                                $value = $value->store();
                            } else {
                                waLog::log(sprintf('Invalid setting class %s for setting %s at %s',get_class($value),$name,$this->name));
                            }
                        }
                        if (is_array($value)) {
                            //$value = serialize($value);
                            $value = json_encode($value);
                        }
                        $this->saveSetting($name,$value);
                    }

                    $changed = false;
                }
            }
        }
        if ($updated) {
            $this->flush();
        }
    }

    /**
     * Remove specified or all settings values from storage
     * @param string $name
     * @return void
     */
    abstract protected function onDelete($name = null);

    /**
     * Remove all settings from storage
     *
     * @return void
     */
    public function delete()
    {
        $this->onDelete();
        $this->flush();
    }

    /**
     * Ignore changed settings - not store them at storage
     * @return void
     */
    public function revert()
    {
        $this->settingsChanged = array();
    }


    /**
     * Load default settings if it aviable
     * @return void
     */
    abstract public function reset();


    /**
     *
     * @param string $field
     * @param mixed $value
     * @throws waException
     * @return mixed
     */
    private function cast($field,$value)
    {
        if (isset($this->settingsParams[$field])&&isset($this->settingsParams[$field]['settings_save_function'])) {
            $type = $this->settingsParams[$field]['settings_save_function'];
            if (preg_match('/^([\w]+)(.*)$/',$type,$matches)) {
                $type	= $matches[1];
                $callback = trim($matches[2]);
                if (preg_match('/^[\w]+::[\w]+$/',$callback)) {
                    $callback = explode('::',$callback);
                }
                switch($type) {
                    case self::TYPE_CUSTOM:{
                        if (is_array($callback)) {
                            if (!isset($callback[0]) || !isset($callback[1]) || !isset($callback[0]) || !isset($callback[1]) ) {
                                throw new waException("Invalid callback param");
                            }
                            if (!class_exists($callback[0])) {
                                throw new waException("Class {$callback[0]} not found");
                            }
                            if (!in_array($callback[1],get_class_methods($callback[0]))) {
                                throw new waException("Method {$callback[0]}->{$callback[1]} not found");
                            }
                        } else {
                            if (!function_exists($callback)) {
                                throw new waException("Function {$callback} not found");
                            }
                        }
                        $value = call_user_func_array($callback,array($field,$value));
                        break;
                    }
                    case self::TYPE_INT:{
                        //TODO use System Utlity Cast types
                        $value = is_array($value)?array_map('intval',$value):intval($value);
                        break;
                    }
                    case self::TYPE_FLOAT:{
                        $value = is_array($value)?array_map('floatval',$value):floatval($value);
                        break;
                    }
                    case self::TYPE_BOOLEAN:{
                        $value = $value?true:false;
                        break;
                    }
                    case self::TYPE_TEXT:{
                        $value = sprintf('%s',$value);
                        break;
                    }
                    case self::TYPE_POSITIVE:{
                        $value = is_array($value)?array_map('floatval',$value):floatval($value);
                        if (is_array($value)) {
                            foreach ($value as &$value_item) {
                                $value_item = max(0,$value_item);
                            }
                            unset($value_item);
                        } else {
                            $value = max(0,$value);
                        }
                        break;
                    }
                }
            }
        }
        return $value;
    }

    /**
     * Get control html code
     *
     * @see waHtmlControl#getControl()
     * @throws waException
     * @param string $type Type of control (use standard or try to found registered control types)
     * @param string $name
     * @param array $params
     * @return string
     */
    public function getControl($type, $name, $params = array())
    {
        if ((isset($this->settingsParams) && isset($this->settingsParams[$name])) || isset($this->settings[$name])||true) {
            $params['value']=$this[$name];
            $params = array_merge($this->settingsParams[$name],$params);
            return waHtmlControl::getControl($type,$name,$params);
        } else {
            throw new waException("undefined setting {$name}");
        }

    }

    /**
     * Flush settings cache
     * @return void
     */
    abstract public function flush();

    /**
     * Returns true if the parameter exists (implements the ArrayAccess interface).
     *
     * @param  string  $name  The parameter name
     *
     * @return Boolean true if the parameter exists, false otherwise
     */
    public function offsetExists($name)
    {
        if ($this->settings === null) {
            $this->load();
        }
        return array_key_exists($name, $this->settings);
    }

    /**
     * Returns a parameter value (implements the ArrayAccess interface).
     *
     * @param  string  $name  The parameter name
     *
     * @throws waException
     * @throws InvalidArgumentException
     * @return mixed  The parameter value
     */
    public function offsetGet($name)
    {
        if ($this->settings === null) {
            $this->load();
        }
        if (!array_key_exists($name, $this->settings)) {
            //TODO: fix var name
            if (isset($this->settingsParams[$name])&&isset($this->settingsParams[$name]['value'])) {

                $this->settings[$name] = $this->settingsParams[$name]['value'];
            } elseif (isset($this->settingsParams[$name])&&isset($this->settingsParams[$name]['settings_value'])) {
                $this->settings[$name] = $this->settingsParams[$name]['settings_value'];
            } else {
                //XXX check it
                // delete settings from storage
                $this->offsetUnset($name);
            }

            if (isset($this->settingsParams[$name]['settings_object'])) {
                $class = $this->settingsParams[$name]['settings_object'];
                if (class_exists($class)) {
                    if (in_array('waSettingWrapper',class_parents($class))) {
                        $this->settings[$name] = new $class($this->settings[$name]);
                    } else {
                        throw new waException(sprintf('Invalid settings_object class %s for %s setting of %s (it must be extends from waSettingWrapper)'));
                    }
                } else {
                    throw new InvalidArgumentException(sprintf('Not found class %s for %s setting of %s', $class, $name,$this->name));
                }
            }
        }

        return array_key_exists($name, $this->settings)?$this->settings[$name]:null;
    }

    /**
     * Sets a parameter (implements the ArrayAccess interface).
     *
     * @param string  $name   The parameter name
     * @param mixed   $value  The parameter value
     */
    public function offsetSet($name, $value)
    {

        if ($this->settings === null) {
            $this->load();
        }
        $value = $this->cast($name,$value);

        if ($this[$name] !== $value) {
            $this->settingsChanged[$name] = true;

        }
        if (isset($this->settingsParams[$name]['settings_object'])) {
            if ($value instanceof waSettingWrapper) {
                $this->settings[$name]->update($value);
            } else {
                $class = $this->settingsParams[$name]['settings_object'];
                if (class_exists($class)) {
                    if (in_array('waSettingWrapper',class_parents($class))) {
                        $this->settings[$name] = new $class($value);
                    } else {
                        throw new waException(sprintf('Invalid settings_object class %s for %s setting of %s (it must be extends from waSettingWrapper)'));
                    }
                } else {
                    throw new InvalidArgumentException(sprintf('Not found class %s for %s setting of %s', $class, $name,$this->name));
                }
            }
        } else {
            $this->settings[$name] = $value;
        }
        return $this->settings[$name];
    }

    /**
     * Removes a parameter (implements the ArrayAccess interface).
     *
     * @param string $name    The parameter name
     */
    public function offsetUnset($name)
    {
        if ($this->settings === null) {
            $this->load();
        }
        unset($this->settings[$name]);
        $this->onDelete($name);
    }

    public function rewind()
    {
        if ($this->settings === null) {
            $this->load();
        }
        reset($this->settings);
    }

    public function current()
    {
        if ($this->settings === null) {
            $this->load();
        }
        return current($this->settings);
    }

    public function key()
    {
        if ($this->settings === null) {
            $this->load();
        }
        return key($this->settings);
    }

    public function next()
    {
        if ($this->settings === null) {
            $this->load();
        }
        return next($this->settings);
    }

    public function valid()
    {
        if ($this->settings === null) {
            $this->load();
        }
        return $this->current() !== false;
    }

    public function count()
    {
        if ($this->settings === null) {
            $this->load();
        }
        return count($this->settings);
    }

    public function serialize()
    {
        return serialize($this->settings);
    }


    public function unserialize($data)
    {
        $this->settings = unserialize($data);
    }
}
