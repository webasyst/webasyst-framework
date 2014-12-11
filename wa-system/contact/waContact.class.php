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

    /**
     * Returns contact's numeric id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the value of contact's name property.
     *
     * @return string
     */
    public function getName()
    {
        return $this->get('name');
    }

    /**
     * Returns contact's photo URL.
     *
     * @param int|string|null $width Image width. Arbitrary integer value, or string value 'original', which requires
     *     that method must return the URL of the original image originally uploaded from a user's computer. Defaults to 96.
     * @param int|string|null $height Image height (integer). If not specified, the integer value specified for the
     *     $width parameter is used.
     * @return string
     */
    public function getPhoto($width = null, $height = null)
    {
        return self::getPhotoUrl($this->id, $this->id ? $this->get('photo') : null, $width, $height, $this['is_company'] ? 'company' : 'person');
    }

    /**
     * Returns contact's photo URL @2x.
     *
     * @param int $size
     * @return string
     */
    public function getPhoto2x($size)
    {
        return self::getPhotoUrl($this->id, $this->id ? $this->get('photo') : null, $size, $size, $this['is_company'] ? 'company' : 'person', true);
    }

    /**
     * Returns the photo URL of the specified contact.
     *
     * @param int $id Contact id
     * @param int $ts Contact photo id stored in contact's 'photo' property. If not specified, the URL of the default
     *     userpic is returned.
     * @param int|string|null $width Image width. Arbitrary integer value, or string value 'original', which requires
     *     that method must return the URL of the original image originally uploaded from a user's computer. Defaults to 96.
     * @param int|string|null $height Image height (integer). If not specified, the integer value specified for the
     *     $width parameter is used.
     * @param string $type
     * @param bool $retina
     * @return string
     */
    public static function getPhotoUrl($id, $ts, $width = null, $height = null, $type = 'person', $retina = null)
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

        if ($retina === null) {
            $retina = (wa()->getEnv() == 'backend');
        }

        $dir = self::getPhotoDir($id, false);
        
        if ($ts) {
            if ($size != 'original' && $retina) {
                $size .= '@2x';
            }
            if (waSystemConfig::systemOption('mod_rewrite')) {
                return wa()->getDataUrl("photos/{$dir}{$ts}.{$size}.jpg", true, 'contacts');
            } else {
                if (file_exists(wa()->getDataPath("photos/{$dir}{$ts}.{$size}.jpg", true, 'contacts'))) {
                    return wa()->getDataUrl("photos/{$dir}{$ts}.{$size}.jpg", true, 'contacts');
                } else {
                    return wa()->getDataUrl("photos/thumb.php/{$dir}{$ts}.{$size}.jpg", true, 'contacts');
                }
            }
        } else {
            $size = (int)$width;
            if (!in_array($size, array(20, 32, 50, 96))) {
                $size = 96;
            }
            if ($retina) {
                $size .= '@2x';
            }
            if ($type == 'company') {
                return wa()->getRootUrl().'wa-content/img/company'.$size.'.jpg';
            } else {
                return wa()->getRootUrl().'wa-content/img/userpic'.$size.'.jpg';
            }
        }
    }
    
    public static function getPhotoDir($contact_id, $with_prefix = true)
    {
        $str = str_pad($contact_id, 4, '0', STR_PAD_LEFT);
        $str = substr($str, -2).'/'.substr($str, -4, 2);
        $path = "{$str}/{$contact_id}/";
        if ($with_prefix) {
            return "photos/{$path}";
        }
        return $path;
    }

    /**
     * Adds an image to contact.
     *
     * @param string $file Path to image file
     * @throws waException
     * @return string
     */
    public function setPhoto($file)
    {
        if (!file_exists($file)) {
            throw new waException('file not exists');
        }
        if (!$this->getId()) {
            throw new waException('Contact not saved!');
        }

        $rand = mt_rand();
        $path = wa()->getDataPath(self::getPhotoDir($this->getId()), true, 'contacts');
        // delete old image
        if (file_exists($path)) {
            waFiles::delete($path);
        }
        waFiles::create($path);
        $filename = $path."/".$rand.".original.jpg";
        waFiles::create($filename);
        waImage::factory($file)->save($filename, 90);
        waFiles::copy($filename, $path."/".$rand.".jpg");

        waContactFields::getStorage('waContactInfoStorage')->set($this, array('photo' => $rand));

        return $this->getPhoto();
    }

    /**
     * Deletes current contact.
     *
     * @return bool Whether contact was deleted successfully
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
     * Returns the value of a contact's property.
     *
     * @param string $field_id Contact property id:
     *     - field name from wa_contact table
     *     - 'email'
     *     - any custom field name from wa_contact_data table
     *
     *     Additional allowed formats of property id:
     *     - 'field_id:subfield_id' for composite fields; e.g., 'address:street'
     *     - 'field_id.ext' for subfields of multi-fields with extension; e.g., 'phone.work'
     *
     * @param string|null $format Data format:
     *     - 'default': simple text value of a contact property; for multi-fields, the first of available values is returned
     *     - 'value':   simple text value of a contact property; for multi-fields, an array of all available values is returned
     *     - 'html':    the full value of a contact property formatted by means of HTML code for displaying in a web page
     *     - 'js':      simple text value of a contact property; for multi-fields, an array of all available values is
     *                  returned, which contains the following elements for each value:
     *         a) 'value': the value of a contact property formatted by means of HTML code for displaying in a web page
     *         b) 'data':  simple text value of a multi-field property as a string or array
     *         In addition to these basic elements, each sub-array of the returned array may contain other elements
     *         specific to various contact properties.
     *
     *     - If no format is specified: simple text value of a contact property is returned; for multi-fields, an array
     *       of all available values is returned, which contains a value element with a property's simple text value as
     *       well as other elements specific to various contact properties.
     *
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
            // Composite field
            // @todo: make simple method for check composite fields
            if ($subfield && $field instanceof waContactCompositeField) {
                $result = $field->get($this);
                if (!$field->isMulti()) {
                    if (isset($result['data'][$subfield])) {
                        $result['value'] = $result['data'][$subfield];
                        $result['data'] = array($subfield => $result['data'][$subfield]);
                    } else {
                        $result['value'] = "";
                    }
                    $result = $field->format($result, $format);
                } else {
                    foreach ($result as &$row) {
                        if (isset($row['data'][$subfield])) {
                            $row['value'] = $row['data'][$subfield];
                            $row['data'] = array($subfield => $row['data'][$subfield]);
                        } else {
                            $row['value'] = "";
                        }
                        $row = $field->format($row, $format);
                    }
                    unset($row);
                }
            } else {
                $result = $field->get($this, $format);
            }

            // Multi field access by extension
            if ($field->isMulti() && $ext) {
                foreach ($result as $sort => $row) {
                    if (empty($row['ext']) || $row['ext'] !== $ext) {
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
                if ($field_id === 'firstname' &&
                        $result === null &&
                        !trim($this['middlename'] !== null ? $this['middlename'] : '') &&
                        !trim($this['lastname'] !== null ? $this['lastname'] : '') &&
                        !trim($this['company'] !== null ? $this['company'] : '')
                    )
                {
                    $eml = $this->get('email', 'value');
                    if ($eml) {
                        if (is_array($eml)) {
                            $eml = array_values($eml);
                            $eml = reset($eml);
                        } else {
                            $eml = '';
                        }
                        $pos = strpos($eml, '@');
                        if ($pos == false) {
                            return $eml;
                        } else {
                            return substr($eml, 0, $pos);
                        }
                    }
                }

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

                // special case for is_company field when is_company IS NULL (e.g.: new contact)
                // and from firstname, middlename, lastname and company, only company is not empty
                if ($field_id == 'is_company' &&
                        $result === null &&
                        !trim($this['firstname'] !== null ? $this['firstname'] : '') &&
                        !trim($this['middlename'] !== null ? $this['middlename'] : '') &&
                        !trim($this['lastname'] !== null ? $this['lastname'] : '') &&
                        trim($this['company'] !== null ? $this['company'] : '')
                    )
                {
                    $result = '1';
                }
                // special case for firstname field when middlename, lastname, company empty, but email is not
                // form firstname from email
                if ($field_id === 'firstname') {

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

    /**
     * Returns the first value of a contact's multi-field.
     *
     * @param string $field_id Contact property id
     * @return mixed
     */
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

    /**
     * Returns full information about contact, which is stored in cache.
     *
     * @param unknown_type $format Data format
     * @see self::get()
     * @param unknown_type $all Flag requiring to return the values of fields marked as hidden in file
     *     wa-system/contact/data/fields.php.
     */
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
                    if ($field->getStorage()) {
                        $load[$field->getStorage()->getType()] = true;
                    }
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

    /**
     * Validates specified values of contact properties.
     *
     * @param array $data Associative array of contact property values.
     * @return int|array Zero, if no errors were found in provided data, or array of error messages otherwise
     */
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
     *
     * @return boolean
     */
    public function exists()
    {
        if (!$this->id) {
            return false;
        } else {
            $model = new waContactModel();
            return !!$model->select('id')->where("id = i:0", array($this->id))->fetch();
        }
    }

    /**
     * Saves contact's data to database.
     *
     * @param array $data Associative array of contact property values.
     * @param bool $validate Flag requiring to validate property values. Defaults to false.
     * @return int|array Zero, if saved successfully, or array of error messages otherwise
     */
    public function save($data = array(), $validate = false)
    {
        $is_user = $this->get('is_user');

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
        $this->data['firstname'] = $this->get('firstname');
        $this->data['is_company'] = $this->get('is_company');
        if ($this->id && isset($this->data['is_user'])) {
            $log_model = new waLogModel();
            if ($this->data['is_user'] == '-1' && $is_user != '-1') {
                $log_model->add('access_disable', null, $this->id, wa()->getUser()->getId());
            } else if ($this->data['is_user'] != '-1' && $is_user == '-1') {
                $log_model->add('access_enable', null, $this->id, wa()->getUser()->getId());
            }
        }

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
                if ($f->isMulti()) {
                    foreach ($value as &$val) {
                        if (is_string($val)) {
                            $val = trim($val);
                        } else if (isset($val['value']) && is_string($val['value'])) {
                            $val['value'] = trim($val['value']);
                        } else if ($f instanceof waContactCompositeField && isset($val['data']) && is_array($val['data'])) {
                            foreach ($val['data'] as &$v) {
                                if (is_string($v)) {
                                    $v = trim($v);
                                }
                            }
                            unset($v);
                        }
                    }
                    unset($val);
                } else {
                    if (is_string($value)) {
                        $value = trim($value);
                    } else if (isset($value['value']) && is_string($value['value'])) {
                        $value['value'] = trim($value['value']);
                    } else if ($f instanceof waContactCompositeField && isset($value['data']) && is_array($value['data'])) {
                        foreach ($value['data'] as &$v) {
                            if (is_string($v)) {
                                $v = trim($v);
                            }
                        }
                        unset($v);
                    }
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
                if (!$errors && $f->getStorage()) {
                    $save[$f->getStorage()->getType()][$field] = $f->prepareSave($value, $this);
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

        $is_add = false;
        // Saving to all storages
        try {
            if (!$this->id) {
                $is_add = true;
                $storage = 'waContactInfoStorage';

                if (wa()->getEnv() == 'frontend') {
                    if ($ref = waRequest::cookie('referer')) {
                        $save['waContactDataStorage']['referer'] = $ref;
                        $save['waContactDataStorage']['referer_host'] = parse_url($ref, PHP_URL_HOST);
                    }
                    if ($utm = waRequest::cookie('utm')) {
                        $utm = json_decode($utm, true);
                        if ($utm && is_array($utm)) {
                            foreach ($utm as $k => $v) {
                                $save['waContactDataStorage']['utm_'.$k] = $v;
                            }
                        }
                    }
                }

                $this->id = waContactFields::getStorage($storage)->set($this, $save[$storage]);
                unset($save[$storage]);
            }
            foreach ($save as $storage => $storage_data) {
                waContactFields::getStorage($storage)->set($this, $storage_data);
            }
            $this->data = array();
            wa()->event(array('contacts', 'save'), $this);
            $this->removeCache();
            $this->clearDisabledFields();

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

    public function clearDisabledFields()
    {
        if (!$this->id) {
            return;
        }
        $disabled_fields = waContactFields::getAll($this['is_company'] ? 'company_disabled' : 'person_disabled', true);
        $data = array();
        foreach ($disabled_fields as $field_id => $field) {
            $data[$field->getStorage()->getType()][$field_id] = $field->prepareSave(null);
        }

        foreach ($data as $storage => $storage_data) {
            waContactFields::getStorage($storage)->set($this, $storage_data);
        }

    }

    /**
     * Returns contact's locale id.
     *
     * @return string
     */
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

    /**
     * Returns contact's time zone value.
     *
     * @return string
     */
    public function getTimezone()
    {
        $timezone = $this->get('timezone');
        if (!$timezone) {
            $timezone = self::$options['default']['timezone'];
        }
        return $timezone;
    }

    /**
     * Returns the value of a contact's property stored in cache, without accessing the database.
     *
     * @param string $field_id Contact property id. If not specified, information about all properties of a contact is returned.
     * @param mixed $old_value Flag requiring to return only contact property values retrieved from the database and to
     *         ignore dynamically added ones. If not specified, false is used by default: return both values stored in
     *         the database and those added dynamically.
     * @return mixed
     */
    public function getCache($field_id = null, $old_value = false)
    {
        if (!$field_id) {
            $result = $old_value ? array() : (array)$this->data;
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

    /**
     * Returns the properties of a cointact relating to specified app.
     *
     * @param string|null $app_id App id
     * @param string|null $name Id of the contact property associated with the specified app which must be returned. If
     *      not specified, an associative array of all properties of a contact is returned, which are associated with
     *      the specified app.
     * @param string|null $default The default value, which must be returned, if the specified contact property for the
     *     specified app is not available. If contact property id is not specified for $default parameter, then the
     *     value of $default parameter is ignored.
     * @return mixed
     */
    public function getSettings($app_id, $name = null, $default = null)
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
            return isset($this->settings[$app_id][$name]) ? $this->settings[$app_id][$name] : $default;
        } else {
            return $this->settings[$app_id];
        }
    }

    /**
     * Saves properties of specified contact to database.
     *
     * @param string $app_id Id of the app for which a value of a contact property must be set
     * @param string $name Contact property id
     * @param mixed $value Contact property value
     * @return bool Whether saved successfully
     */
    public function setSettings($app_id, $name, $value = null)
    {
        $setting_model = new waContactSettingsModel();
        if (is_array($value)) {
            $value = implode(",", $value);
        }
        return $setting_model->set($this->id, $app_id, $name, $value);
    }

    /**
     * Deletes contact's property relating to specified app.
     *
     * @param string $app_id App id
     * @param string $name Contact property id
     */
    public function delSettings($app_id, $name)
    {
        $setting_model = new waContactSettingsModel();
        $setting_model->delete($this->id, $app_id, $name);
    }


    /**
     * Returns information about a contact's access rights configuration.
     *
     * @param string $app_id Id of the app for which contact's access rights configuration must be returned.
     * @param string $name String id of the access rights element available for the specified app. If not specified,
     *     all values of access rights for the current contact are returned. If % character is appended to the access
     *     rights element id, then the access rights values for that element are returned as an array. The array
     *     structure is defined by the value of the $assoc parameter.
     * @param bool $assoc Flag defining the structure of the returned array:
     *     - true (default): multi-fields of access rights configuration elements are included in the returned array
     *       with access rights elements' ids as array keys and 1 as their values.
     *     - false: array keys are incremented starting from 0, array item values containing the ids of access
     *       rights configuration elements of access rights multi-fields enabled for a user.
     * @return int|bool|array
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
     * Returns information about whether a user has full (adminstrative) access rights to all installed apps or only one
     * specified app.
     *
     * @param string $app_id App id. If not specified, access rights for all installed apps are verified.
     * @return bool
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
     * Sets access rights for a user.
     * If a user has administrative access rights for the specified app, then an attempt to change his access rights
     * configuration using this method is ignored.
     *
     * @param string $app_id Id of the app for which contact's access rights must be set
     * @param string $name Access rights element id supported by specified app
     * @param int $value Access rights value
     * @return bool Whether access rights have been set successfully
     */
    public function setRight($app_id, $name, $value)
    {
        if (!$this->isAdmin($app_id)) {
            $right_model = new waContactRightsModel();
            return $right_model->save($this->id, $app_id, $name, $value);
        }
        return true;
    }

    /**
     * Returns user's status: "online", "offline"
     *
     * @return string
     */
    public function getStatus()
    {
        $timeout = self::$options['online_timeout']; // in sec
        if (($last = $this->get('last_datetime')) && $last != '0000-00-00 00:00:00') {
            if (time() - strtotime($last) < $timeout) {
                $m = new waLoginLogModel();
                $datetime_out = $m->select('datetime_out')->
                        where('contact_id = i:0', array($this->id))->
                        order('id DESC')->
                        limit(1)->fetchField();
                if ($datetime_out === null) {
                    return 'online';
                } else {
                    return 'offline';
                }
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

    /**
     * Adds an extra value to specified contact property.
     *
     * @param string $field Contact property id
     * @param mixed $value Property value
     */
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
     * Returns hash for specified password string.
     *
     * By default, hash is generated using PHP function md5(). If configuration file wa-config/SystemConfig.class.php
     * contains information about user-defined function wa_password_hash(), then that function is used for generating
     * password hash instead of md5().
     *
     * @param string $password Password string
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
     * Adds contact to a category.
     *
     * @param int|string $category_id Category's simple numeric or system string key (app_id)
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

    /**
     * Sets a value for specified contact property.
     *
     * @param string $field_id Contact property id
     * @param mixed $value Property value
     * @param bool $add Flag requiring to add specified value to existing values of a multi-field. If false,
     *     all existing values of the specified multi-field are deleted and replaced with specified value.
     */
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

    /**
     * Returns current data and time relative to contact's locale and time zone configuration.
     *
     * @return string
     */
    public function getTime()
    {
        return waDateTime::format("datetime", null, $this->get('timezone'), $this->getLocale());
    }
    
    public function getTopFields()
    {
        $top = array();
        static $fields = null;
        if ($fields === null) {
            $fields = array(
                waContactFields::getAll('person', true),
                waContactFields::getAll('company', true),
            );
        }
        
        $country_model = new waCountryModel();
        $iso3letters_map = $country_model->select('DISTINCT iso3letter')->fetchAll('iso3letter', true);
        
        foreach ($fields[intval($this['is_company'])] as $f) {
            $info = $f->getInfo();
            if ($f->getParameter('top') && ($value = $this->get($info['id'], 'top,html')) ) {
                
                if ($info['type'] == 'Address') {
                    $data = $this->get($info['id']);
                    $data_for_map = $this->get($info['id'], 'forMap');
                    $value = (array) $value;
                    foreach ($value as $k => &$v) {
                        if (isset($data[$k]['data']['country']) && isset($iso3letters_map[$data[$k]['data']['country']])) {
                            $v = '<img src="'.wa_url().'wa-content/img/country/'.strtolower($data[$k]['data']['country']).'.gif" /> ' . $v;
                        }
                        $map_url = '';
                        if (is_string($data_for_map[$k])) {
                            $map_url = $data_for_map[$k];
                        } else {
                            if (!empty($data_for_map[$k]['coords'])) {
                                $map_url = $data_for_map[$k]['coords'];
                            } else if (!empty($data_for_map[$k]['with_street'])) {
                                $map_url = $data_for_map[$k]['with_street'];
                            }
                        }
                        $v .= ' <a target="_blank" href="//maps.google.com/maps?q=' . urlencode($map_url) . '&z=15" class="small underline map-link">' . _w('map') . '</a>';
                        $v = '<div data-subfield-index='.$k.'>'.$v.'</div>';
                    }
                    unset($v);
                }
                
                $delimiter = ($info['type'] == 'Composite' || $info['type'] == 'Address') ? "<br>" : ", ";
                
                $top[] = array(
                    'id' => $info['id'],
                    'name' => $f->getName(),
                    'value' => is_array($value) ? implode($delimiter, $value) : $value,
                    'icon' => ($info['type'] == 'Email' || $info['type'] == 'Phone') ? strtolower($info['type']) : '',
                    'pic' => ''
                );
            }
        }
        return $top;
    }
}

// EOF