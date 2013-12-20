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
     *     'export' => bool,      // !!! never used?..
     *     'sort' => bool,        // ?..
     *     'pa_hidden' => bool,   // do not show in personal account
     *     'pa_readonly' => bool, // show as read-only in personal acccount
     *     'unique' => bool,      // only allows unique values
     *     'required' => bool,    // is required in visual contact editor
     *     'search' => bool,      // ?..
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
            'unique' => $this->isUnique(),
            'required' => $this->isRequired(),
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
            if ($locale == waSystem::getInstance()->getLocale() && wa()->getEnv() == 'backend') {
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

    public function isUnique()
    {
        return isset($this->options['unique']) && $this->options['unique'];
    }

    public function isRequired()
    {
        return isset($this->options['required']) && $this->options['required'];
    }


    public function isExt()
    {
        return $this->isMulti() && isset($this->options['ext']);
    }

    /**
     * @param bool $name
     * @return waContactStorage|string
     */
    public function getStorage($name = null)
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
            if (!is_array($data)) {
                $data = array($data);
            }
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
     * Prepare value to be stored in DB.
     * Data returned will be validated and later passed to $this->getStorage()->set().
     * For non-multi fields return string or array(value=>string, ext=>string).
     * For multi fields return list of arrays(value=>string, ext=>string); ext is optional, see $this->isExt()
     *
     * @param waContact $contact
     * @param mixed $value can be a string, an array(value=>..., ext=>...) or list of these.
     * @param array $params
     * @param bool $add
     * @return array
     */
    public function set(waContact $contact, $value, $params = array(), $add = false)
    {
        if ($this->isMulti()) {
            //
            // This scary chunk of code brings $value into common form for multi fields:
            // list of arrays (value => string, ext => string)
            // 'ext' is only present if enabled, see $this->isExt().
            // 'value' is passed through $this->setValue() for preparation.
            //
            $is_ext = $this->isExt();
            $ext = isset($params['ext']) ? $params['ext'] : '';
            if (!is_array($value)) {
                $value = array('value' => $value);
                if ($is_ext) {
                    $value['ext'] = $ext;
                }
                $value = array($this->setValue($value));
            } elseif (isset($value['value'])) {
                if ($is_ext && !isset($value['ext'])) {
                    $value['ext'] = $ext;
                }
                if (!$is_ext && isset($value['ext'])) {
                    unset($value['ext']);
                }
                $value['value'] = $this->setValue($value['value']);
                $value = array($value);
            } else {
                foreach ($value as &$v) {
                    if (!is_array($v)) {
                        $v = array('value' => $this->setValue($v));
                        if ($is_ext) {
                            $v['ext'] = $ext;
                        }
                    } else {
                        if (!$is_ext && isset($v['ext'])) {
                            unset($v['ext']);
                        }
                        if ($is_ext && !isset($v['ext'])) {
                            $v['ext'] = $ext;
                        }
                        $v['value'] = $this->setValue(ifset($v['value'], ''));
                    }
                }
                unset($v);
            }
            if ($add) {
                $data = $contact->get($this->id);
                foreach ($value as $v) {
                    $data[] = $v;
                }
                return $data;
            } else {
                if ($is_ext && $ext) {
                    $data = $contact->get($this->id);
                    foreach ($data as $sort => $row) {
                        if ($row['ext'] == $ext) {
                            unset($data[$sort]);
                        }
                    }
                    foreach ($value as $v) {
                        $data[] = $v;
                    }
                    return $data;
                } else {
                    return $value;
                }
            }
        } else {
            return $this->setValue($value);
        }
    }

    /**
     * Helper for $this->set() to format value of a field before inserting into DB
     * (actually even before validation, so beware).
     *
     * $value is a single value passed to waContact['field_id'] via assignment.
     * No extension, just the value, e.g.: 'a@b.com', not array('value' => 'a@b.com', 'ext' => 'work').
     * Note that for composite fields this behaves a little differently, see waContactCompositeField.
     *
     * @param mixed $value
     * @return mixed possibly changed $value
     */
    protected function setValue($value)
    {
        return $value;
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
     * @param $data
     * @param int $contactId
     * @return array|string|null Validation errors (array for multi fields, string for simple fields) or null if everything is ok.
     */
    public function validateUnique($data, $contactId=null)
    {
        if (!$this->getParameter('unique')) {
            return null;
        }

        if (!$this->isMulti()) {
            $data = array($data);
        }

        // array of plain string values
        $values = array();
        if (is_array($data)) {
            foreach($data as $sort => $value) {
                $value = $this->format($value, 'value');
                if ($value || $value === 0) { // do not check empty values to be unique
                    $values[$sort] = $value;
                }
            }
        } else if ($data !== null) {
            return array(_w('Data must be an array.'));
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
        $rows = $this->getStorage()->findDuplicatesFor($this, array_keys($flipped), $contactId ? array($contactId) : array());
        foreach($rows as $value => $cid) {
            if (isset($flipped[$value])) {
                $dupl[$flipped[$value]] = $cid;
            } else {
                // Must be a duplicate in case-insensitive search
                foreach($flipped as $v => $i) {
                    if (mb_strtolower($v) == mb_strtolower($value)) {
                        $dupl[$i] = $cid;
                        break;
                    }
                }
                if (!$dupl) {
                    // Sanity check for debugging purposes
                    throw new waException("Unable to find duplicate value $value among flipped: ".print_r($flipped, true));
                }
            }
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
            if (!$rights_model && class_exists('ContactsRightsModel')) {
                $rights_model = new ContactsRightsModel();
                $userId = waSystem::getInstance()->getUser()->getId();
            }
            if ($rights_model && $rights_model->getRight($userId, $cid)) {
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
     * @param int $contact_id
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
                /**
                 * @var waValidator $validator
                 */
                if ($validator instanceof waValidator) {
                    if ($this->isMulti()) {
                        $allEmpty = true;
                        if (is_array($data)) {
                            foreach ($data as $sort => $value) {
                                $value = $this->format($value, 'value');
                                if (!$value && $value !== '0') {
                                    continue;
                                }

                                $allEmpty = false;
                                if (!$validator->isValid($value)) {
                                    $errors[$sort] = implode("<br />", $validator->getErrors());
                                }
                            }
                        } else if ($data !== null) {
                            return array(_w('Data must be an array.'));
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
                        } else if ($this->getParameter('required') && empty($value)) {
                            $errors = _ws('This field is required');
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
                if (is_array($data) && isset($data['value'])) {
                    $k = array_keys($data);
                    $data = $data['value'];
                    sort($k);
                    if ($k == array('ext', 'value')) {
                        $data = htmlspecialchars($data);
                    }
                } else if (!is_array($data)) {
                    $data = htmlspecialchars($data);
                } else {
                    $data = '';
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
                    if (!is_array($data) || isset($data['value'])) {
                        $data = htmlspecialchars(is_array($data) ? $data['value'] : $data);
                    }
                }
                continue;
            }
        }
        return $data;
    }

    /**
     * @param string $format
     * @return waContactFieldFormatter
     */
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

    /**
     * @return string
     */
    public function getType()
    {
        if (isset($this->options['type'])) {
            return $this->options['type'];
        }
        return str_replace(array('waContact', 'Field'), array('', ''), get_class($this));
    }

    /**
     * Get the current value of option $p.
     * Used by a Field Constructor editor to access field parameters.
     *
     * waContactField has one parameter: localized_names = array(locale => name)
     *
     * @param $p string parameter to read
     * @return array|null
     */
    public function getParameter($p)
    {
        if ($p == 'localized_names') {
            return $this->name;
        }

        if (!isset($this->options[$p])) {
            return null;
        }
        return $this->options[$p];
    }

    /**
     * Set the value of option $p.
     * Used by a Field Constructor editor to change field parameters.
     *
     * localized_names = array(locale => name)
     * required = boolean
     * unique = boolean
     *
     * @param $p string parameter to set
     * @param $value mixed value to set
     */
    public function setParameter($p, $value)
    {
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

    /**
     * Set array of parameters
     * @param array $param parameter => value
     * @throws waException
     */
    public function setParameters($param)
    {
        if (!is_array($param)) {
            throw new waException('$param must be an array: '.print_r($param, TRUE));
        }
        foreach($param as $p => $val) {
            $this->setParameter($p, $val);
        }
    }

    protected function getHTMLName($params)
    {
        $prefix = $suffix = '';
        if (isset($params['namespace'])) {
            $prefix .= $params['namespace'].'[';
            $suffix .= ']';
        }
        if (isset($params['parent'])) {
            if ($prefix) {
                $prefix .= $params['parent'].'][';
            } else {
                $prefix .= $params['parent'].'[';
                $suffix .= ']';
            }
        }

        if (isset($params['multi_index'])) {
            if (isset($params['parent'])) {
                // For composite multi-fields multi_index goes before field id:
                // namespace[parent_name][i][field_id]
                $prefix .= $params['multi_index'].'][';
            } else {
                // For non-composite multi-fields multi_index goes after field id:
                // namespace[field_id][i]
                $suffix = ']['.$params['multi_index'].$suffix;
            }
        }
        $name = isset($params['id']) ? $params['id'] : $this->getId();

        return $prefix.$name.$suffix;
    }

    public function getHtmlOne($params = array(), $attrs = '')
    {
        $value = isset($params['value']) ? $params['value'] : '';
        $ext = null;

        if (is_array($value)) {
            $ext = $this->getParameter('force_single') ? null : ifset($value['ext'], '');
            $value = ifset($value['value'], '');
        }

        $name_input = $name = $this->getHTMLName($params);
        if ($this->isMulti() && $ext) {
            $name_input .= '[value]';
        }

        $result = '<input '.$attrs.' type="text" name="'.htmlspecialchars($name_input).'" value="'.htmlspecialchars($value).'">';
        if ($ext) {
            // !!! add a proper <select>?
            $result .= '<input type="hidden" name="'.htmlspecialchars($name.'[ext]').'" value="'.htmlspecialchars($ext).'">';
        }

        return $result;
    }

    public function getHtmlOneWithErrors($errors, $params = array(), $attrs = '')
    {
        // Validation errors?
        $errors_html = '';
        if (!empty($errors)) {
            if (!is_array($errors)) {
                $errors = array((string) $errors);
            }
            foreach($errors as $error_msg) {
                if (is_array($error_msg)) {
                    $error_msg = implode("<br>\n", $error_msg);
                }
                $errors_html .= "\n".'<em class="errormsg">'.htmlspecialchars($error_msg).'</em>';
            }

            $attrs = preg_replace('~class="~', 'class="error ', $attrs);
            if (false === strpos($attrs, 'class="error')) {
                $attrs .= ' class="error"';
            }
        }

        return $this->getHtmlOne($params, $attrs).$errors_html;
    }

    public function getHTML($params = array(), $attrs = '')
    {
        if ($this->isMulti()) {
            if (!empty($params['value']) && is_array($params['value']) && !empty($params['value'][0])) {
                // Multi-field with at least one value
                $params_one = $params;
                unset($params_one['validation_errors']);
                $i = 0;
                $result = array();
                while (isset($params['value'][$i])) {
                    if (!empty($params['value'][1])) {
                        $params_one['multi_index'] = $i;
                    }
                    $params_one['value'] = $params['value'][$i];

                    // Validation errors?
                    $errors = null;
                    if (!empty($params['validation_errors']) && is_array($params['validation_errors']) && !empty($params['validation_errors'][$i])) {
                        $errors = $params['validation_errors'][$i];
                    }

                    $result[] = $this->getHtmlOneWithErrors($errors, $params_one, $attrs);
                    $i++;

                    // Show single field when forced to show one value even for multi fields
                    if ($this->getParameter('force_single')) {
                        return $result[0];
                    }
                }
                return '<p>'.implode('</p><p>', $result).'</p>';
            } else {
                // Multi-field with no values
                return '<p>'.$this->getHtmlOneWithErrors(ifempty($params['validation_errors']), $params, $attrs).'</p>';
            }
        }

        // Non-multi field
        return $this->getHtmlOneWithErrors(ifempty($params['validation_errors']), $params, $attrs);
    }

    public static function __set_state($state)
    {
         return new $state['_type']($state['id'], $state['name'], $state['options']);
    }
}

