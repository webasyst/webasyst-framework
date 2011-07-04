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
class waContact implements ArrayAccess
{
    protected $id;
    // static runtime array cache for all contacts
    // @todo: set protected this property
    public static $cache = array();
    // for unsaved data
    protected $data;

    protected static $options = array(
        'default' => array()
    );

    protected $settings = null;

    public function __construct($id = null, $options = array())
    {
        foreach ($options as $name => $value) {
            self::$options[$name] = $value;
        }

        $this->init();

        $this->id = (int)$id;
    }

    public static function getOption($name, $default = null)
    {
        return isset(self::$options[$name]) ? self::$options[$name] : $default;
    }

    public function init()
    {
        if (!isset(self::$options['default']['locale'])) {
            try {
                $app_settings_model = new waAppSettingsModel();
                self::$options['default']['locale'] = $app_settings_model->get('webasyst', 'locale');
            } catch (waDbException $e) {}
            if (!isset(self::$options['default']['locale']) || !self::$options['default']['locale']) {
                self::$options['default']['locale'] = 'ru_RU';
            }
        }
        if (!isset(self::$options['default']['timezone'])) {
            self::$options['default']['timezone'] = @date_default_timezone_get();
            if (!self::$options['default']['timezone']) {
                self::$options['default']['timezone'] = 'UTC';
            }
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->get('name');
    }

    public function getPhoto($width = null, $height = null)
    {
        if ($width === 'original') {
            $size = 'original';
        } else if ($width && !$height) {
            $size = $width.'x'.$width;
        } else if (!$width) {
            $width = 96;
            $size = '96x96';
        } else {
            $size = $width.'x'.$height;
        }

        $ts = $this->get('photo');

        if ($ts) {
        	if (waSystemConfig::systemOption('mod_rewrite')) {
            	return wa()->getDataUrl('photo/'.$this->id.'/'.$ts.'.'.$size.'.jpg', true, 'contacts');
        	} else {
        		if (file_exists(wa()->getDataPath('photo/'.$this->id.'/'.$ts.'.'.$size.'.jpg', true, 'contacts'))) {
        			return wa()->getDataUrl('photo/'.$this->id.'/'.$ts.'.'.$size.'.jpg', true, 'contacts');
        		} else {
        			return wa()->getDataUrl('photo/thumb.php/'.$this->id.'/'.$ts.'.'.$size.'.jpg', true, 'contacts');
        		}
        	}
        } else {
            $size = (int)$width;
            if (!in_array($size, array(20, 32, 50, 96))) {
                $size = 96;
            }
            return wa()->getRootUrl().'wa-content/img/userpic'.$size.'.jpg';
        }
    }

    /**
     * Delete contact
     *
     * @return bool result
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }
        $contact_model = new waContactModel();
        return $contact_model->delete($this->id);
    }

    public function get($field_id, $format = null)
    {
        if (strpos($field_id, '.') !== false) {
            $field_parts = explode('.', $field_id, 2);
            $field_id = $field_parts[0];
            $ext = $field_parts[1];
        } else {
            $ext = null;
        }
        if (strpos($field_id, ':') !== false) {
            $field_parts = explode(':', $field_id, 2);
            $field_id = $field_parts[0];
            $subfield = $field_parts[1];
        } else {
            $subfield = null;
        }

        if ($format && strpos($format, '|') !== false) {
            $format = explode('|', $format);
        }


        // Get field object
        $field = waContactFields::get($field_id, 'enabled');
        if ($field) {
            $result = $field->get($this, $format);
            // @todo: make simple method for check composite fields
            if ($subfield && $field instanceof waContactCompositeField) {
                if (!$field->isMulti()) {
                    $result['value'] = isset($result['data'][$subfield]) ? $result['data'][$subfield] : null;
                    unset($result['data']);
                } else {
                    foreach ($result as &$row) {
                        $row['value'] = isset($row['data'][$subfield]) ? $row['data'][$subfield] : null;
                        unset($row['data']);
                    }
                }
            }
            if ($field->isMulti() && $ext) {
                foreach ($result as $sort => $row) {
                    if ($row['ext'] !== $ext) {
                        unset($result[$sort]);
                    }
                }
                $result = array_values($result);
            }
            if ($format === 'default' || (is_array($format) && in_array('default', $format))) {
                if ($field->isMulti()) {
                    if (!$result) {
                        return '';
                    } elseif (is_array($result[0])) {
                        return isset($result[0]['value']) ? $result[0]['value'] : null;
                    } else {
                        return $result[0];
                    }
                } else {
                    return is_array($result) ? $result['value'] : $result;
                }
            } elseif ($format == 'html') {
                if ($field->isMulti()) {
                    return implode(', ', $result);
                } else {
                    $result;
                }
            } else {
                return $result;
            }
        } else {
            // try get data from default storage
            $result = waContactFields::getStorage()->get($this, $field_id);
            if (!$result && isset(self::$options['default'][$field_id])) {
                return self::$options['default'][$field_id];
            } else {
                return $result;
            }
        }

    }

    /**
     * Returns code for the user
     */
    public function getCode()
    {
        return substr($this['password'], 0, 6).$this->id.substr($this['password'], -6);
    }

    public function load($format = false, $all = false)
    {
        if (!$this->id) {
            return $this->data;
        } else {
            $cache = isset(self::$cache[$this->id]) ? self::$cache[$this->id] : array();
            $fields = waContactFields::getAll($this['is_company'] ? 'company' : 'person', $all);
            // Get field to load from Storages
            $load = array();
            foreach ($fields as $field) {
                if (!isset($cache[$field->getId()])) {
                    $load[$field->getStorage()->getType()] = true;
                }
            }

            // Load data from storages
            foreach ($load as $storage => $bool) {
                waContactFields::getStorage($storage)->get($this);
            }

            // format accordingly
            if ($format) {
                $result = array();
                foreach ($fields as $field) {
                    $result[$field->getId()] = $field->get($this, $format);
                }
            } else {
                $result = self::$cache[$this->id];
                foreach ($fields as $field) {
                    $result[$field->getId()] = $field->get($this);
                }
                // remove some fields
                unset($result['password']);
            }
            return $result;
        }
    }

    public function validate($data=array())
    {
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }
        $this->data['name'] = $this->get('name');
        $errors = array();
        foreach ($this->data as $field => $value) {
            if ($f = waContactFields::get($field, $this['is_company'] ? 'company' : 'person')) {
                if ($f->isMulti() && !is_array($value)) {
                    $value = array($value);
                }

                if ($e = $f->validate($value, $this->id)) {
                    $errors[$f->getId()] = $e;
                }
            }
        }
        // Returns errors
        if ($errors) {
            return $errors;
        }

        return 0;

    }

    /**
     * Save unsaved data
     * If saving was succesfully returns 0 and array of errors themselves
     *
     * @param array $data
     * @return int|array
     */
    public function save($data = array(), $validate = false)
    {
        foreach ($data as $key => $value) {
        	if ($key == 'name') {
        		$this->data[$key] = waContactFields::get($key)->set($this, $value);	
        	} else {
            	$this->data[$key] = $value;
        	}
        }
        $this->data['name'] = $this->get('name');
        $save = array();
        $errors = array();
        $contact_model = new waContactModel();
        foreach ($this->data as $field => $value) {
            if ($f = waContactFields::get($field, $this['is_company'] ? 'company' : 'person')) {
                if ($f->isMulti() && !is_array($value)) {
                    $value = array($value);
                }
                if ($validate && ( $e = $f->validate($value, $this->id))) {
                        $errors[$f->getId()] = $e;
                } else {
                    $save[$f->getStorage()->getType()][$field] = $value;
                }
            } elseif ($contact_model->fieldExists($field)) {
                $save['waContactInfoStorage'][$field] = $value;
            } else {
                $save['waContactDataStorage'][$field] = $value;
            }
        }
        // Returns errors
        if ($validate && $errors) {
            return $errors;
        }

        // Saving to all storages
        try {
            if (!$this->id) {
                $storage = 'waContactInfoStorage';
                $this->id = waContactFields::getStorage($storage)->set($this, $save[$storage]);
                unset($save[$storage]);
            }
            foreach ($save as $storage => $storage_data) {
                waContactFields::getStorage($storage)->set($this, $storage_data);
            }
            $this->data = array();
        } catch (Exception $e) {
            $errors['name'][] = $e->getMessage();
        }
        return $errors ? $errors : 0;
    }

    public function getLocale()
    {
        if (!$this->id) {
            $locale = isset($this->data['locale']) ? $this->data['locale'] : null;
            if (!$locale) {
                $locale = waRequest::get('lang');
            }
        } else {
            if (isset(self::$cache[$this->id]['locale'])) {
                $locale = self::$cache[$this->id]['locale'];
            } else {
                $contact_model = new waContactModel();
                $contact_info = $contact_model->getById($this->id);
                $this->setCache($contact_info);
                $locale = isset($contact_info['locale']) ? $contact_info['locale'] : '';
            }
        }
        if (!$locale) {
            $locale = self::$options['default']['locale'];
        }
        return $locale;
    }

    public function getTimezone()
    {
        return $this->get('timezone');
    }

    /**
     * Returns data from cache (used by contactStorage)
     *
     * @param string $field_id
     * @param mixed
     */
    public function getCache($field_id, $old_value = false)
    {
        if (strpos($field_id, ':') !== false) {
            $field_parts = explode(':', $field_id);
            $field_id = $field_parts[0];
        }
        if (!$old_value && isset($this->data[$field_id])) {
            return $this->data[$field_id];
        } elseif ($this->id) {
            if (isset(self::$cache[$this->id][$field_id])) {
                return self::$cache[$this->id][$field_id];
            }
        }
        return null;
    }

    /**
     *
     *
     * @param string $field_id
     * @return bool
     */
    public function issetCache($field_id, $old_value = false)
    {
        if (strpos($field_id, ':') !== false) {
            $field_parts = explode(':', $field_id);
            $field_id = $field_parts[0];
        }
        $f = $this->id && isset(self::$cache[$this->id][$field_id]);
        if ($old_value) {
            return $f;
        } else {
            return isset($this->data[$field_id]) || $f;
        }
    }

    /** Static variant of setCache()
      * Accepts one parameter: array(contact_id => array(field => data))
      * or two parameters: contact_id and array(field => data) */
    public static function setCacheFor($id, $data=null) {
        if (!$data && is_array($id)) {
            $data = $id;
        } else {
            $data = array($id => $data);
        }
        foreach ($data as $id => $fields) {
            self::$cache[$id] = isset(self::$cache[$id]) ? array_merge(self::$cache[$id], $fields) : $fields;
        }
    }

    public function setCache($data) {
        if (isset(self::$cache[$this->getId()])) {
            self::$cache[$this->getId()] = array_merge(self::$cache[$this->getId()], $data);
        } else {
            self::$cache[$this->getId()] = $data;
        }
    }

    public function removeCache($keys = null)
    {
    	if ($keys === null) {
    		if (isset(self::$cache[$this->getId()])) {
    			unset(self::$cache[$this->getId()]);
    		}
    	} else {
	        if (!is_array($keys)) {
	            $keys = array($keys);
	        }
	        foreach ($keys as $key) {
	            if (isset(self::$cache[$this->getId()][$key])) {
	                unset(self::$cache[$this->getId()][$key]);
	            }
	        }
    	}
    }

    public function getSettings($app_id, $name = null)
    {
        // For general settings
        if (!$app_id) {
            $app_id = '';
        }
        // Get settings for app from database
        if (!isset($this->settings[$app_id])) {
            $setting_model = new waContactSettingsModel();
            $this->settings[$app_id] = $setting_model->get($this->id, $app_id);
        }

        if ($name) {
            return isset($this->settings[$app_id][$name]) ? $this->settings[$app_id][$name] : null;
        } else {
            return $this->settings[$app_id];
        }
    }

    public function getExtraSettings($app_id, $name = null)
    {
        $settings = $this->getSettings($app_id, $name);
        if ($name !== null) {
            $settings = explode("|", $settings);
            $result = array();
            foreach ($settings as $value) {
                $value = explode(":", $value, 2);
                $result[$value[0]] = $value[1];
            }
            return $result;
        } else {
            foreach ($settings as $key => $setting) {
                $setting = explode("|", $setting);
                $result = array();
                foreach ($setting as $value) {
                    $value = explode(":", $value, 2);
                    $result[$value[0]] = $value[1];
                }
                $settings[$key] = $result;
            }
            return $settings;
        }
    }

    public function setSettings($app_id, $name, $value = null)
    {
        $setting_model = new waContactSettingsModel();
        if (is_array($value)) {
            $value = implode(",", $value);
        }
        return $setting_model->set($this->id, $app_id, $name, $value);
    }

    public function delSettings($app_id, $name)
    {
        $setting_model = new waContactSettingsModel();
        $setting_model->delete($this->id, $app_id, $name);
    }


    /**
     * Returns rights of the contact
     *
     * @param string $app_id - application id (contacs, orders, ...)
     * @param string $name - key of the right, if it is null method return all rights of the contact for application
     * @param bool $assoc - only if $name is null when true returns associative array of the rights
     */
    public function getRights($app_id, $name = null, $assoc = true)
    {
        $right_model = new waContactRightsModel();
        if ($name !== null && substr($name, -1) === '%') {
            $data = $right_model->get($this->id, $app_id);
            $result = array();
            $prefix = substr($name, 0, -1);
            $n = strlen($prefix);
            foreach ($data as $key => $value) {
                if (substr($key, 0, $n) === $prefix) {
                    if ($assoc) {
                        $result[substr($key, $n)] = $value;
                    } else {
                        $result[] = substr($key, $n);
                    }
                }
            }
            return $result;
        } else {
            return $right_model->get($this->id, $app_id, $name);
        }
    }

    /**
     * Check the user is admin of the application
     *
     * @param string $app_id
     *
     * @return bool - true if contact is admin
     */
    public function isAdmin($app_id = 'webasyst')
    {
        $r = $this->getRights($app_id, 'backend');
        if ($app_id == 'webasyst') {
            return (bool)$r;
        } else {
            return $r >= 2;
        }
    }

    /**
     * Set right
     *
     * @param string $app_id
     * @param string $name
     * @param int $value
     *
     * @return bool - result
     */
    public function setRight($app_id, $name, $value)
    {
        if (!$this->isAdmin($app_id)) {
            $right_model = new waContactRightsModel();
            return $right_model->insert(array(
                'app_id' => $app_id,
                'group_id' => -$this->id,
                'name' => $name,
                'value' => $value
            ));
        }
        return true;
    }


    public function getStatus()
    {
        if (!$this->get('login')) {
            return 'not-complete';
        }
        $timeout = self::$options['online_timeout']; // in sec
        if (($last = $this->get('last_datetime')) && $last != '0000-00-00 00:00:00') {
            if (time() - strtotime($last) < $timeout) {
                return 'online';
            }
        }
        return 'offline';
    }

    public function offsetExists($offset)
    {
        //@todo:
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }


    public function add($field, $value)
    {
        $this->set($field, $value, true);
    }

    public function set($field_id, $value, $add = false)
    {
        if (strpos($field_id, '.') !== false) {
            $field_parts = explode('.', $field_id, 2);
            $field_id = $field_parts[0];
            $ext = $field_parts[1];
        } else {
            $ext = null;
        }
        if (strpos($field_id, ':') !== false) {
            $field_parts = explode(':', $field_id, 2);
            $field_id = $field_parts[0];
            $subfield = $field_parts[1];
        } else {
            $subfield = null;
        }

        $f = waContactFields::get($field_id, $this['is_company'] ? 'company' : 'person');
        if (!$f) {
            $this->data[$field_id] = $value;
        } else {
            if ($f instanceof waContactCompositeField) {
                if ($f->isMulti()) {
                    if ($subfield) {
                        if ($add) {
                            if (!isset($this->data[$field_id])) {
                                $this->data[$field_id] = $this->get($field_id);
                            }
                            if (($n = count($this->data[$field_id])) > 0) {
                                $data = $this->data[$field_id][$n - 1];
                                $data_ext = isset($data['ext']) ? $data['ext'] : null;
                                if (isset($data['fill']) && !isset($data['data'][$subfield]) && $ext == $data_ext) {
                                    $this->data[$field_id][$n - 1]['data'][$subfield] = $value;
                                    return;
                                }
                            }
                            $this->data[$field_id][] = array(
                                        'data' => array(
                                            $subfield => $value
                                        ),
                                        'fill' => true,
                                        'ext' => $ext
                            );
                        } else {
                            $this->data[$field_id] = array(
                                array(
                                    'data' => array(
                                        $subfield => $value
                                    ),
                                    'ext' => $ext
                                )
                            );
                        }
                        return;
                    }
                }
                $value = $f->set($value);
            }
            if ($f->isMulti()) {
                if ($f->isExt()) {
                    if (is_array($value)) {
                    	if (isset($value['value'])) {
                        	$value['ext'] = $ext;
                    	} else {
                    		foreach ($value as &$v) {
                    			$v = array(
                    				'value' => $v,
                    				'ext' => $ext
                    			);
                    		}
                    	}
                    } else {
                        $value = array(
                            'value' => $value,
                            'ext' => $ext
                        );
                    }
                }
                if (!$add) {
                    $this->data[$field_id] = array();
                } elseif (!isset($this->data[$field_id])) {
                    $this->data[$field_id] = $this->get($field_id);
                }
                if (is_array($value) && !isset($value['value'])) {
                	if (!$add) {
                    	$this->data[$field_id] = $value;
                	} else {
                		$this->data[$field_id] = array_merge($this->data[$field_id], $value);
                	}
                } else {
                    $this->data[$field_id][] = $value;
                }
            } else {
            	if (method_exists($f, 'set')) {
            		$this->data[$field_id] = $f->set($this, $value);
            	} else {
                	$this->data[$field_id] = $value;
            	}
            }
        }
    }

    public function offsetSet($offset, $value)
    {
    	$this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->data[$offset] = null;
    }


    public function getTime()
    {
        return waDateTime::format("datetime", null, $this->get('timezone'), $this->getLocale());
    }
}

// EOF