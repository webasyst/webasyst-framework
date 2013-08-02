<?php

/**
 * Builder of forms containing contact fields.
 */
class waContactForm
{
    /** @var waContactField[] field_id => waContactField */
    public $fields;

    /** @var array */
    public $options;

    /** @var array field_id => list of error message strings */
    public $errors = array();

    /** @var array field_id => value to show in field, as accepted by waContactField->getHTML(). Existing POST data overwrite this. */
    public $values = array();

    /** @var boolean Used by validateFields() to validate at most once. */
    protected $fields_validated = false;

    /** Contact to validate this form against. */
    public $contact = null;

    /** Can be used to feed faked POST data into this form. */
    public $post = null;

    /**
     * Factory method to load form fields from config.
     *
     * Config must return an array: field_id => waContactField OR array of options to specify on existing field with given field_id.
     *
     * @param string|array $file path to config file, or array of config options.
     * @param array $options
     */
    public static function loadConfig($file, $options = array())
    {
        if (is_array($file)) {
            $fields_config = $file;
        } else {
            if (!is_readable($file)) {
                throw new waException('Config is not readable: '.$file);
            }
            $fields_config = include($file);
            if (!$fields_config || !is_array($fields_config)) {
                waLog::log('Incorrect config '.$file);
                $fields_config = array();
            }
        }

        $fields = array();
        $values = array(); // hidden field values known beforehand
        foreach ($fields_config as $full_field_id => $opts) {
            if ($opts instanceof waContactField) {
                $f = clone $opts;
            } else if (is_array($opts)) {
                // Allow to specify something like 'phone.home' as field_id in config file.
                $fid = explode('.', $full_field_id, 2);
                $fid = $fid[0];

                $f = waContactFields::get($fid);
                if (!$f) {
                    waLog::log('ContactField '.$fid.' not found.');
                    continue;
                } else {
                    // Prepare fields parameter for composite field
                    if ($f instanceof waContactCompositeField && !empty($opts['fields'])) {
                        if (!is_array($opts['fields'])) {
                            unset($opts['fields']);
                        } else {
                            $old_subfields = $f->getFields();
                            $subfields = array();
                            foreach($opts['fields'] as $sfid => $sfopts) {
                                if (empty($old_subfields[$sfid])) {
                                    waLog::log('Field '.$fid.':'.$sfid.' not found and is ignored in '.$file);
                                    continue;
                                }
                                $subfields[$sfid] = self::getClone($old_subfields[$sfid], $sfopts);
                                if ($subfields[$sfid] instanceof waContactHiddenField) {
                                    if (empty($values[$full_field_id]['data'])) {
                                        $values[$full_field_id] = array('data' => array());
                                    }
                                    $values[$full_field_id]['data'][$sfid] = $subfields[$sfid]->getParameter('value');
                                }
                            }

                            $opts['fields'] = $subfields;
                        }
                    }

                    $f = self::getClone($f, $opts);
                    if ($f instanceof waContactHiddenField) {
                        $values[$full_field_id] = $f->getParameter('value');
                    }
                }
            } else {
                waLog::log('Field '.$fid.' has incorrect format and is ignored in '.$file);
                continue;
            }

            $fields[$full_field_id] = $f;
        }

        $form = new self($fields, $options);
        $form->setValue($values);
        return $form;
    }

    protected static function getClone($f, $opts)
    {
        if (!is_array($opts)) {
            return clone $f;
        }

        if (!empty($opts['hidden'])) {
            return new waContactHiddenField($f->getId(), $f->getName(), $opts);
        }

        $f = clone $f;
        foreach ($opts as $k => $v) {
            $f->setParameter($k, $v);
        }
        return $f;
    }

    /**
     * Options:
     * - namespace
     *   - Prefix for all fields of this form.
     *   - Defaults to 'data'.
     *
     * @param array $fields list of waContactField
     * @param array $options
     */
    public function __construct($fields = array(), $options = array())
    {
        if (!is_array($fields)) {
            throw new waException('$fields must be an array');
        }
        $this->fields = array();
        foreach($fields as $full_field_id => $f) {
            if (!($f instanceof waContactField)) {
                throw new waException('Bad parameters for '.get_class($this));
            }

            // Allows to specify a list of fields instead of key => value pairs
            if (!$full_field_id || is_numeric($full_field_id)) {
                $full_field_id = $f->getId();
            }

            $this->fields[$full_field_id] = $f;
        }

        if (!is_array($options)) {
            throw new waException('$options must be an array');
        }
        $this->options = $options;
        $this->options['namespace'] = ifempty($this->options['namespace'], 'data');
    }

    /**
     * Set form value.
     * Note that when POST exists, data from POST overwrite setValue().
     * Accepts 1 or 2 parameters.
     * - 1 parameter: array field_id => value. Set several values.
     * - 1 parameter: waContact. Fetch data via waContact->load()
     * - 2 parameters: field_id, value. Set value for single field.
     */
    public function setValue($field_id, $value=null)
    {
        if (func_num_args() == 1) {
            if ($field_id instanceof waContact) {
                $c = $field_id;
                $arr = array();
                foreach ($this->fields as $fid => $f) {
                    $arr[$fid] = $c->get($fid);
                }
            } else if (is_array($field_id)) {
                $arr = $field_id;
            } else {
                return $this;
            }
        } else {
            $arr = array($field_id => $value);
        }

        foreach($arr as $fid => $v) {
            if (isset($this->fields[$fid])) {
                $this->values[$fid] = $v;
            }
        }

        return $this;
    }

    /**
     * @param string $field_id
     * @return mixed POST data for entire form or single form field; null when no POST submitted.
     */
    public function post($field_id = null)
    {
        if ($this->post === null) {
            $this->post = waRequest::post($this->opt('namespace'));
        }
        if (!$this->post || !is_array($this->post)) {
            return null;
        }
        if ($field_id) {
            return ifset($this->post[$field_id]);
        }
        return $this->post;
    }

    /**
     * Get list of errors for specified field, or append an error to the list.
     *
     * With no parameters returns an array of all errors: field_id => list of strings.
     *
     * With one parameter returns a list of errors for one field.
     *
     * With two parameters appends an error to the list of errors for specified field.
     * This sets internal state so that form HTML will contain given error message next to the field.
     * Forces isValid() to return false.
     *
     * @param string $field_id field_id or null to set message for entire form, not attached to any field.
     * @param string $error_text
     */
    public function errors($field_id='', $error_text=null)
    {
        if (func_num_args() === 0) {
            return $this->errors;
        }
        if ($field_id === null || empty($this->fields[$field_id])) {
            $field_id = '';
        }
        if (strlen($error_text) <= 0) {
            $this->validateFields();
            return ifset($this->errors[$field_id], array());
        }
        if (empty($this->errors[$field_id])) {
            $this->errors[$field_id] = array();
        }
        $this->errors[$field_id][] = $error_text;
        return $this;
    }

    /**
     * Validate this form and set internal state so that form HTML will contain error messages.
     * @return boolean true when no errors encountered; otherwise false.
     */
    public function isValid($contact=null)
    {
        $this->validateFields($contact);
        return !$this->errors;
    }

    /**
     * Get specified form field or all of them.
     * @param string $field_id
     * @return waContactField|waContactField[]
     */
    public function fields($field_id = null)
    {
        if ($field_id) {
            if (isset($this->fields[$field_id])) {
                return $this->fields[$field_id];
            }
            return null;
        }
        return $this->fields;
    }

    /**
     * HTML for the whole form or single form field.
     * @param string $field_id
     * @param boolean $with_errors whether to add class="error" and error text next to form fields
     */
    public function html($field_id = null, $with_errors = true)
    {
        $this->validateFields();

        // Single field?
        if ($field_id) {
            if (empty($this->fields[$field_id])) {
                return '';
            }

            $opts = $this->options;
            $opts['id'] = $field_id;

            if (empty($this->contact)) {
                $this->contact = new waContact();
            }
            if ($this->post()) {
                $opts['value'] = $this->fields[$field_id]->set($this->contact, $this->post($field_id), array());
            } else if (isset($this->values[$field_id])) {
                $opts['value'] = $this->fields[$field_id]->set($this->contact, $this->values[$field_id], array());
            }

            // HTML with no errors?
            if ($with_errors && !empty($this->errors[$field_id]) && !empty($this->errors[$field_id])) {
                $opts['validation_errors'] = $this->errors[$field_id];
            }

            return $this->fields[$field_id]->getHTML($opts);
        }

        // Whole form
        $class_field = $this->opt('css_class_field', wa()->getEnv() == 'frontend' ? 'wa-field' : 'field');
        $class_value = $this->opt('css_class_value', wa()->getEnv() == 'frontend' ? 'wa-value' : 'value');
        $class_name = $this->opt('css_class_name', wa()->getEnv() == 'frontend' ? 'wa-name' : 'name');
        $result = '';
        foreach($this->fields() as $fid => $f) {

            if ($f instanceof waContactHiddenField) {
                $result .= $this->html($fid, true);
                continue;
            }

            $result .= '<div class="'.$class_field.($f->isRequired() ? ' '.(wa()->getEnv() == 'frontend' ? 'wa-required' : 'required') : '').'"><div class="'.$class_name.'">'.
                $f->getName().'</div><div class="'.$class_value.'">';
            $result .= "\n".$this->html($fid, $with_errors);
            $result .= "\n</div></div>";
        }
        return $result;
    }

    /**
     * Value of a single option, or the whole options array.
     *
     * @param string $name
     * @param mixed $default value to return when no option with this $name specified
     * @return mixed
     */
    public function opt($name=null, $default=null)
    {
        if ($name === null) {
            return $this->options;
        }
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }
        return $default;
    }

    /** Make sure POST data is properly validated using waFontactField instances in $this->fields. */
    protected function validateFields($contact = null)
    {
        if (!$contact || !($contact instanceof waContact)) {
            $contact = null;
        }
        if ($this->fields_validated && (!$contact || $contact === $this->contact)) {
            return;
        }
        $this->contact = $contact ? $contact : new waContact();
        $this->fields_validated = true;
        if (!$this->post()) {
            return;
        }
        foreach($this->fields as $fid => $f) {
            $errors = $f->validate($f->set($this->contact, $this->post($fid), array()), $this->contact->getId());
            if (!$errors) {
                continue;
            }
            if (!is_array($errors)) {
                $errors = array($errors);
            }
            if (empty($this->errors[$fid])) {
                $this->errors[$fid] = array();
            }
            if (empty($this->errors[$fid])) {
                $this->errors[$fid] = $errors;
            } else {
                $this->errors[$fid] = array_merge($this->errors[$fid], $errors);
            }
        }
    }
}

