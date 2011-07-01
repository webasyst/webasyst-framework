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
abstract class waContactField
{
    protected $id;
    /**
     * Available options
     *
     * array(
     *     'export' => bool,
     *     'sort' => bool,
     *     'search' => bool,
     *     'validator' => waValidator,
     *     'storage' => waContactStorage,
     *     'multi' => bool,
     *     // for multi fields
     *     'ext' => array(
     *             'ext1' => 'ext1 Name',
     *             ...
     *     ),
     *     // subfields for composite fields
     *     'fields' => array(
     *             new waContactField($sub_id, $sub_name, $sub_options),
     *             ...
     *     ),
     *     // any options for specific field type
     *     ...
     * )
     */
    protected $options;

    /** array(locale => name) */
    protected $name = array();

    /** used by __set_state() */
    protected $_type;

    /**
     * Constructor
     *
     * Because of a specific way this class is saved and loaded via var_dump,
     * constructor parameters order and number cannot be changed in subclasses.
     * Subclasses also must always provide a call to parent's constructor.
     *
     * @param string $id
     * @param mixed $name either a string or an array(locale => name)
     * @param array $options
     */
    public function __construct($id, $name, $options = array())
    {
        $this->id = $id;
        $this->setParameter('localized_names', $name);
        if (!isset($options['storage'])) {
            $options['storage'] = 'data';
        }
        $this->options = $options;
        $this->_type = get_class($this);
        $this->init();
    }


    protected function init()
    {

    }

    /**
     * Returns id of the field
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }


    public function getInfo()
    {
        $info = array(
            'id' => $this->id,
            'name' => $this->getName(),
            'multi' => $this->isMulti(),
            'type' => $this->getType(),
            'unique' => $this->getParameter('unique'),
            'required' => $this->getParameter('required'),
        );
        if ($this->isMulti() && isset($this->options['ext'])) {
            $info['ext'] = $this->options['ext'];
            foreach ($info['ext'] as &$ext) {
                $ext = _ws($ext);
            }
        }
        return $info;
    }

    /**
     * Returns name of the field
     *
     * @param string $locale - locale
     * @return string
     */
    public function getName($locale = null)
    {
        if (!$locale) {
            $locale = waSystem::getInstance()->getLocale();
        }
        
        if (isset($this->name[$locale])) {
            return $this->name[$locale];
        } else if (isset($this->name['en_US'])) {
        	if ($locale = waSystem::getInstance()->getLocale()) {
        		return _ws($this->name['en_US']);	
        	} else {
            	return waLocale::translate('webasyst', $locale, $this->name['en_US']);
        	}
        } else {
            return reset($this->name); // reset() returns the first value
        }
    }

    public function isMulti()
    {
        return isset($this->options['multi']) && $this->options['multi'];
    }
    
    public function isExt()
    {
    	return $this->isMulti() && isset($this->options['ext']);
    }

    /**
     *
     * @return waContactStorage
     */
    public function getStorage($name = false)
    {
        if ($name) {
            return $this->options['storage'];
        }
        return waContactFields::getStorage($this->options['storage']);
    }

    public function get(waContact $contact, $format = null)
    {
        $data = $this->getStorage()->get($contact, $this->getId());
        if (!$data && $this->isMulti()) {
            return array();
        }
        if ($this->isMulti()) {
            foreach ($data as &$row) {
                $row = $this->format($row, $format);
            }
            return $data;
        } else {
            if (is_array($data) && isset($data[0])) {
                $data = $data[0];
            }
            if (is_array($data) && isset($data['value']) && !isset($data['data'])) {
                $data = $data['value'];
            }
            return $this->format($data, $format);
        }
    }

    /**
     * Returns validator of the field
     *
     * @return waValidator|null
     */
    public function getValidator()
    {
        if ($this->options['validator']) {
            return $this->options['validator'];
        }
        return null;
    }

    /**
     * Check field value to be unique, if field is set up as unique.
     *
     * @return array|string|null Validation errors (array for multi fields, string for simple fields) or null if everything is ok.
     */
    public function validateUnique($data, $contactId=null) {
        if (!$this->getParameter('unique')) {
            return null;
        }

        if (!$this->isMulti()) {
            $data = array($data);
        }

        // array of plain string values
        $values = array();
        foreach($data as $sort => $value) {
            $value = $this->format($value, 'value');
            if ($value || $value === 0) { // do not check empty values to be unique
                $values[$sort] = $value;
            }
        }

        // array of duplicates $sort => contact_id
        $dupl = array();

        // Check if there are duplicates among $values
        $flipped = array_flip($values);
        if (count($values) != count($flipped)) {
            // keys that disappeared after array_flip are duplicates, find them
            foreach(array_diff(array_keys($values), array_values($flipped)) as $key) {
                $dupl[$key] = $contactId;

                // there's another key that is not missing, but still is a duplicate since it's a copy of a missing key
                $dupl[$flipped[$values[$key]]] = $contactId;
            }
        }

        // Check if there are duplicates in database
        foreach($this->getStorage()->findDuplicatesFor($this, array_keys($flipped), $contactId ? array($contactId) : array()) as $value => $cid) {
            $dupl[$flipped[$value]] = $cid;
        }

        if (!$dupl) {
            return null;
        }

        // Create array of errors
        $errors = array();
        $errStrSelf = _ws('Duplicates are not allowed for this field.');
        $errStr = _ws('This field must be unique. The value entered is already set for %NAME_LINK%.');
        $errStrNoRights = _ws('This field must be unique. The value entered is already set for another contact.');
        $rights_model = null;
        $userId = null;
        foreach($dupl as $sort => $cid) {
            if ($cid === $contactId) {
                $errors[$sort] = $errStrSelf;
                continue;
            }

            // Check if current user can view $cid profile.
            if (!$rights_model) {
                $rights_model = new ContactsRightsModel();
                $userId = waSystem::getInstance()->getUser()->getId();
            }
            if ($rights_model->getRight($userId, $cid)) {
                // at least read access
                $contact = new waContact($cid);
                $nameLink = '<a href="'.wa_url().'webasyst/contacts/#/contact/'.$cid.'">'.$contact->get('name').'</a>';
                $errors[$sort] = str_replace('%NAME_LINK%', $nameLink, $errStr);
            } else {
                // no access
                $errors[$sort] = $errStrNoRights;
            }
        }
        return $errors;
    }

    /**
     * Validate field value and returns errors or null if value is valud
     * @param mixed $data
     * @param int|null $contactId
     * @return array|string|null
     */
    public function validate($data, $contact_id=null)
    {
        if (!isset($this->options['validators'])) {
            $this->options['validators'] = array();
        }

        if ($this->getParameter('required') && !$this->options['validators']) {
            $this->options['validators'][] = new waStringValidator($this->options);
        }

        if (!is_array($this->options['validators'])) {
            $validators = array($this->options['validators']);
        } else {
            $validators = $this->options['validators'];
        }

        $errors = null;
        if ($this->options['validators']) {
            foreach ($validators as $validator) {
                if ($validator instanceof waValidator) {
                    if ($this->isMulti()) {
                        $allEmpty = true;
                        foreach ($data as $sort => $value) {
                            $value = $this->format($this->setValue($value), 'value');
                            if (!$value && $value !== '0') {
                                continue;
                            }

                            $allEmpty = false;
                            if (!$validator->isValid($value)) {
                                $errors[$sort] = implode("<br />", $validator->getErrors());
                            }
                        }

                        if ($this->getParameter('required') && $allEmpty) {
                            if (!isset($errors[0])) {
                                $errors[0] = '';
                            }
                            $errors[0] = _ws('This field is required') . ($errors[0] ? '<br>'.$errors[0] : '');
                        }
                    } else {
                        $value = $this->format($data, 'value');
                        if (!$validator->isValid($value)) {
                            $errors = implode("<br />", $validator->getErrors());
                        }
                    }
                }
            }
        }

        // Check for duplicates if this field is unique
        if (!$errors) {
            $errors = $this->validateUnique($data, $contact_id);
        }

        return $errors;
    }

    public function format($data, $format = null)
    {
        if (!$format) {
            return $data;
        }
        if (!is_array($format)) {
            $format = array($format);
        }
        foreach ($format as $f) {

            if (strpos($f, ',')) {
                // when formats are delimeted by comma, use the first one that exists
                $found = false;
                foreach(explode(',', $f) as $f) {
                    if ($f == 'value' || $f == 'html' || $this->getFormatter($f)) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    // ignore it...
                    continue;
                }
            }

            if ($formatter = $this->getFormatter($f)) {
                $data = $formatter->format($data);
                continue;
            }

            if ($f == 'value') {
                if (is_array($data)) {
                    $data = $data['value'];
                }
                continue;
            }

            if ($f == 'html') {
                if ($this->isMulti()) {
                    if (is_array($data)) {
                        $result = htmlspecialchars($data['value']);
                        if (isset($data['ext']) && $data['ext']) {
                            $ext = $data['ext'];
                            if (isset($this->options['ext'][$ext])) {
                                $ext = _ws($this->options['ext'][$ext]);
                            }
                            $result .= ' <em class="hint">'.htmlspecialchars($ext).'</em>';
                        }
                        $data = $result;
                    }
                } else {
                    $data = htmlspecialchars(is_array($data) ? $data['value'] : $data);
                }
                continue;
            }
        }
        return $data;
    }

    protected function setValue($value) {
        return $value;
    }

    protected function getFormatter($format)
    {
        if (isset($this->options['formats'][$format])) {
            return $this->options['formats'][$format];
        }
        return null;
    }

    public function getField()
    {
        return $this->getId();
    }

    public function getType()
    {
        if (isset($this->options['type'])) {
            return $this->options['type'];
        }
        return str_replace(array('waContact', 'Field'), array('', ''), get_class($this));
    }

    /** Get the current value of option $p.
      * Used by a Field Constructor editor to access field parameters.
      *
      * waContactField has one parameter: localized_names = array(locale => name)
      *
      * @param $p string parameter to read */
    public function getParameter($p) {
        if ($p == 'localized_names') {
            return $this->name;
        }

        if (!isset($this->options[$p])) {
            return null;
        }
        return $this->options[$p];
    }

    /** Set the value of option $p.
      * Used by a Field Constructor editor to change field parameters.
      *
      * localized_names = array(locale => name)
      * required = boolean
      * unique = boolean
      *
      * @param $p string parameter to set
      * @param $value mixed value to set */
    public function setParameter($p, $value) {
        if ($p == 'localized_names') {
            if (is_array($value)) {
                if (!$value) {
                    $value['en_US'] = '';
                }
                $this->name = $value;
            } else {
                $this->name = array('en_US' => $value);
            }
            return;
        }

        $this->options[$p] = $value;
    }

    /** Set array of parameters
      * @param array $param parameter => value */
    public function setParameters($param) {
        if (!is_array($param)) {
            throw new waException('$param must be an array: '.print_r($param, TRUE));
        }
        foreach($param as $p => $val) {
            $this->setParameter($p, $val);
        }
    }

    public static function __set_state($state) {
         return new $state['_type']($state['id'], $state['name'], $state['options']);
    }
}

// EOF