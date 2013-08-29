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

        if (is_array($id)) {
            if (isset($id['id'])) {
                $this->id = $id['id'];
            }
            foreach ($id as $k => $v) {
                if ($k != 'id') {
                    $this->set($k, $v);
                }
            }
        } else {
            $this->id = (int)$id;
        }
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
                $l = $app_settings_model->get('webasyst', 'locale');
            } catch (waException $e) {
                $l = null;
            }
            self::$options['default']['locale'] = waRequest::getLocale($l);
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
        return self::getPhotoUrl($this->id, $this->get('photo'), $width, $height);
    }

    public static function getPhotoUrl($id, $ts, $width = null, $height = null)
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

        if ($ts) {
            if (waSystemConfig::systemOption('mod_rewrite')) {
                return wa()->getDataUrl('photo/'.$id.'/'.$ts.'.'.$size.'.jpg', true, 'contacts');
            } else {
                if (file_exists(wa()->getDataPath('photo/'.$id.'/'.$ts.'.'.$size.'.jpg', true, 'contacts'))) {
                    return wa()->getDataUrl('photo/'.$id.'/'.$ts.'.'.$size.'.jpg', true, 'contacts');
                } else {
                    return wa()->getDataUrl('photo/thumb.php/'.$id.'/'.$ts.'.'.$size.'.jpg', true, 'contacts');
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

    public function setPhoto($file)
    {
        if (!file_exists($file)) {
            throw new waException('file not exists');
        }
        if (!$this->getId()) {
            throw new waException('Contact not saved!');
        }

        $rand = mt_rand();
        $path = wa()->getDataPath("photo", true, 'contacts')."/".$this->getId();
        // delete old image
        if (file_exists($path)) {
            waFiles::delete($path);
        }
        waFiles::create($path);
        $filename = $path."/".$rand.".original.jpg";
        waImage::factory($file)->save($filename, 90);
        waFiles::copy($filename, $path."/".$rand.".jpg");

        waContactFields::getStorage('waContactInfoStorage')->set($this, array('photo' => $rand));

        return $this->getPhoto();
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

    /**
     * Get field value of the contact
     *
     * @param string $field_id field to retrieve; either col from wa_contact table, or 'email', or custom
     *                          field from wa_contact_data. 'field_id:subfield_id' for composite fields is allowed.
     *                          'field_id.ext' for fields with extension is allowed.
     * @param string $format   data format to use. Default is the same as $this[$field_id].
     *                          'value' for simple
     * @return mixed
     */
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


        // Try to use field object
        $field = waContactFields::get($field_id, 'enabled');
        if ($field) {
            $result = $field->get($this, $format);

            // Composite field
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
                    unset($row);
                }
            }

            // Multi field access by extension
            if ($field->isMulti() && $ext) {
                foreach ($result as $sort => $row) {
                    if ($row['ext'] !== $ext) {
                        unset($result[$sort]);
                    }
                }

                $result = array_values($result);
            }

            // 'default' format: a simple string
            if ($format === 'default' || (is_array($format) && in_array('default', $format))) {
                // for multi fields return value of the first copy
                if ($field->isMulti()) {
                    if (!$result) {
                        return '';
                    } elseif (is_array($result[0])) {
                        return isset($result[0]['value']) ? $result[0]['value'] : null;
                    } else {
                        return $result[0];
                    }
                }
                // for non-multi fields return field value
                else {
                    return is_array($result) ? $result['value'] : $result;
                }
            }
            // 'html' format: ???
            else if ($format == 'html') {
                if ($field->isMulti()) {
                    return implode(', ', $result);
                } else {
                    return $result;
                }
            }
            // no special formatting
            else {
                return $result;
            }
        }
        // no field with $field_id exists
        else {
            if ($this->issetCache($field_id)) {
                $result = $this->getCache($field_id);
            } else {

                // try get data from default storage
                $result = waContactFields::getStorage()->get($this, $field_id);
                if (!$result && isset(self::$options['default'][$field_id])) {
                    return self::$options['default'][$field_id];
                }
                // try get data from data storage
                elseif ($result === null) {
                    $result = waContactFields::getStorage('data')->get($this, $field_id);
                }
            }
            if ($result && is_array($result)) {
                $result = current($result);
                if (is_array($result) && isset($result['value'])) {
                    return $result['value'];
                }
            }
            return $result;
        }
    }

    public function getFirst($field_id)
    {
        $value = $this->get($field_id);
        if (strpos($field_id, '.') !== false) {
            $field_parts = explode('.', $field_id, 2);
            $field_id = $field_parts[0];
        }
        $field = waContactFields::get($field_id, 'enabled');
        if ($field && $field->isMulti()) {
            return isset($value[0]) ? $value[0] : ($field instanceof waContactCompositeField ? array() : '');
        }
        return $value;
    }

    /**
     * Returns code for the user
     * @return string
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
                /**
                 * @var waContactField $field
                 */
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
                foreach ($result as $field_id => $value) {
                    if (!isset($fields[$field_id]) && is_array($value)) {
                        if (isset($value[0]['value'])) {
                            $result[$field_id] = $value[0]['value'];
                        }
                    }
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
     * @param bool $validate
     * @return int|array
     */
    public function save($data = array(), $validate = false)
    {
        $add = array();
        foreach ($data as $key => $value) {
            if (strpos($key, '.')) {
                $key_parts = explode('.', $key);
                $f = waContactFields::get($key_parts[0]);
                if ($f) {
                    $key = $key_parts[0];
                    if ($key_parts[1] && $f->isExt()) {
                        // add next field
                        $add[$key] = true;
                        if (is_array($value)) {
                            if (!isset($value['value'])) {
                                $value = array('ext' => $key_parts[1], 'value' => $value);
                            }
                        } else {
                            $value = array('ext' => $key_parts[1], 'value' => $value);
                        }
                    }
                }
            } else {
                $f = waContactFields::get($key);
            }
            if ($f) {
                $this->data[$key] = $f->set($this, $value, array(), isset($add[$key]) ? true : false);
            } else {
                if ($key == 'password') {
                    $value = self::getPasswordHash($value);
                }
                $this->data[$key] = $value;
            }
        }
        $this->data['name'] = $this->get('name');
        $save = array();
        $errors = array();
        $contact_model = new waContactModel();
        foreach ($this->data as $field => $value) {
            if ($field == 'login') {
                $f = new waContactStringField('login', _ws('Login'), array('unique' => true, 'storage' => 'info'));
            } else {
                $f = waContactFields::get($field, $this['is_company'] ? 'company' : 'person');
            }
            if ($f) {
                if ($f->isMulti() && !is_array($value)) {
                    $value = array($value);
                }
                if ($validate !== 42) { // this deep dark magic is used when merging contacts
                    if ($validate) {
                        if ($e = $f->validate($value, $this->id)) {
                            $errors[$f->getId()] = $e;
                        }
                    } elseif ($f->isUnique()) { // validate unique
                        if ($e = $f->validateUnique($value, $this->id)) {
                            $errors[$f->getId()] = $e;
                        }
                    }
                }
                if (!$errors) {
                    $save[$f->getStorage()->getType()][$field] = $value;
                }
            } elseif ($contact_model->fieldExists($field)) {
                $save['waContactInfoStorage'][$field] = $value;
            } else {
                $save['waContactDataStorage'][$field] = $value;
            }
        }
        // Returns errors
        if ($errors) {
            return $errors;
        }

        // Saving to all storages
        try {

            $is_add = false;
            if (!$this->id) {
                $is_add = true;
                $storage = 'waContactInfoStorage';
                $this->id = waContactFields::getStorage($storage)->set($this, $save[$storage]);
                unset($save[$storage]);
            }
            foreach ($save as $storage => $storage_data) {
                waContactFields::getStorage($storage)->set($this, $storage_data);
            }
            $this->data = array();
            wa()->event(array('contacts', 'save'), $this);
        } catch (Exception $e) {
            // remove created contact
            if ($is_add && $this->id) {
                $this->delete();
                $this->id = null;
            }
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
        if (wa()->getEnv() == 'frontend' && waRequest::param('locale')) {
            return waRequest::param('locale');
        }
        // try get locale by header Accept-Language (only for current user)
        if (!$locale && $this instanceof waAuthUser) {
            $locale = waRequest::getLocale();
        }
        if (!$locale) {
            $locale = self::$options['default']['locale'];
        }
        return $locale;
    }

    public function getTimezone()
    {
        $timezone = $this->get('timezone');
        if (!$timezone) {
            $timezone = self::$options['default']['timezone'];
        }
        return $timezone;
    }

    /**
     * Returns data for this contact from cache without any database queries
     * Used by contactStorage.
     *
     * @param string $field_id field to retrieve data for; omit to get all data from cache
     * @param mixed $old_value true to consider only values from DB; false (default) to add values set to this contact but not saved yet
     * @return array|null
     */
    public function getCache($field_id = null, $old_value = false)
    {
        if (!$field_id) {
            $result = $old_value ? array() : $this->data;
            if ($this->id && isset(self::$cache[$this->id])) {
                $result += self::$cache[$this->id];
            }
            return $result;
        }

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
     * @param string $field_id
     * @param bool $old_value
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

    /**
     * Static variant of setCache()
     * Accepts one parameter: array(contact_id => array(field => data))
     * or two parameters: contact_id and array(field => data)
     *
     * @static
     * @param int $id
     * @param array $data
     */
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

    /**
     * @param array $data
     */
    public function setCache($data)
    {
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
     * @return int|bool
     */
    public function getRights($app_id, $name = null, $assoc = true)
    {
        if ($name !== null && substr($name, -1) === '%') {
            if (!$this->id) {
                return array();
            }
            $right_model = new waContactRightsModel();
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
            if (!$this->id) {
                return false;
            }
            $right_model = new waContactRightsModel();
            $r = $right_model->get($this->id, $app_id, $name);
            // check .all
            if (!$r && strpos($name, '.') !== false) {
                return $right_model->get($this->id, $app_id, substr($name, 0, strpos($name, '.')).'.all');
            }
            return $r;
        }
    }

    /**
     * Check the user is admin of the application
     *
     * @param string $app_id
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
        //@TODO
        if (!$this->id) {
            return isset($this->data[$offset]);
        } else {
            return true;
        }
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }


    public function add($field, $value)
    {
        $this->set($field, $value, true);
    }

    public function setPassword($password, $is_hash = false)
    {
        if ($is_hash) {
            $this->data['password'] = $password;
        } else {
            $this->data['password'] = self::getPasswordHash($password);
        }
    }

    /**
     * Returns hash of the password
     * You can override this function in file wa-config/SystemConfig.class.php
     * (add function wa_password_hash ($password) { return ...})
     *
     * @param string $password
     * @return string
     */
    public static function getPasswordHash($password)
    {
        if (function_exists('wa_password_hash')) {
            return wa_password_hash($password);
        } else {
            return md5($password);
        }
    }

    /**
     * Add contact to the category
     *
     * @param int|string $category_id - id or system key (app_id) of the category
     * @throws waException
     */
    public function addToCategory($category_id)
    {
        if (!$this->id) {
            throw new waException('Contact not saved!');
        }
        if (!is_numeric($category_id)) {
            $category_model = new waContactCategoryModel();
            $category = $category_model->getBySystemId($category_id);
            $category_id = $category['id'];
        }
        $contact_categories_model = new waContactCategoriesModel();
        if (!$contact_categories_model->inCategory($this->id, $category_id)) {
            $contact_categories_model->add($this->id, $category_id);
        }
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
            if ($field_id == 'password') {
                $value = self::getPasswordHash($value);
            }
            $this->data[$field_id] = $value;
        } else {
            $this->data[$field_id] = $f->set($this, $value, array('ext' => $ext, 'subfield' => $subfield), $add);
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