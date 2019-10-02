<?php

class webasystSettingsFieldSaveController extends webasystSettingsJsonController
{
    public function execute()
    {
        $id = $this->getId();

        $constructor = new webasystFieldConstructor();

        $field = null;
        if (strlen($id) > 0) {
            $field = $constructor->getField($id);
            if (!$field) {
                throw new waException(_w('Page not found'), 404);
            }
        }

        $options = waRequest::post('options');
        if (is_array($options)) {
            $this->setOptions($options);
        }

        $field_data = $this->getFieldData();
        $res = $constructor->updateField($field, $field_data);
        if (!$res[0]) {
            if ($res[1]) {
                $this->errors = $res[1];
                return;
            }
            $this->errors = array(_ws("Can't construct field"));
        }

        /**
         * @var waContactField $field
         */
        $field = $res[0];

        $enable = $this->getRequest()->post('enable', -1);
        if ($enable == 'true') {
            $field_types = array('person');
        }  elseif ($enable == 'false') {
            $field_types = array();
        } else {
            $field_types = (array)$this->getRequest()->post('type');
        }

        $constructor->enableField($field, $field_types);

        $data = $constructor->getFieldInfo($field_data['id']);

        $view = wa()->getView();
        $view->assign(array(
            'field' => $data,
        ));

        $this->response = array(
            'done' => true
        );
    }

    protected function getId()
    {
        return trim((string) $this->getRequest()->request('id'));
    }

    protected function getFieldData()
    {
        return array(
            'id'                 => trim($this->getRequest()->post('id_val')),
            'names'              => (array)$this->getRequest()->post('name'),
            'ftype'              => trim($this->getRequest()->post('ftype')),
            'select_field_value' => trim($this->getRequest()->post('select_field_value'))
        );
    }

    /**
     * @throws waException
     * @var waContactField $subfield
     */
    public function setOptions($options)
    {
        $all_fields = waContactFields::getAll('all');

        $field_constructor = new webasystFieldConstructor();

        $cfvm = new waContactFieldValuesModel();

        foreach ($options as $field_id => $opts)
        {
            $field = waContactFields::get($field_id);

            $old_fields = array();
            /**
             * @var waContactField $subfield
             */
            foreach($field->getParameter('fields') as $subfield) {
                $old_fields[$subfield->getId()] = $subfield;
            }

            $new_fields = array();
            foreach ($opts['fields'] as $subfield_id => $params)
            {
                if ($subfield_id == '%FID%') {
                    continue;
                }

                // for radio/select kind of field
                if (isset($params['options'])) {

                    $old_options = array();
                    if (!empty($old_fields[$subfield_id])) {
                        $subfield = $old_fields[$subfield_id];
                        $old_options = $subfield->getParameter('options');
                        $old_options = is_array($old_options) ? $old_options : array();
                    }

                    // Collect options (radio/select inputs) by this rules:
                    // + as key so as value must not be empty
                    // + key must be preserved in case of options resorted or new key added or another key deleted
                    // + use value for new key
                    $params_options = array();
                    foreach ($params['options'] as $key => $value) {
                        $key = trim($key);
                        $value = trim($value);
                        if (strlen($key) > 0 && strlen($value) > 0) {
                            if (!isset($old_options[$key])) {   // is new key
                                $key = $value;
                            }
                            $params_options[$key] = $value;
                        }
                    }
                    $params['options'] = $params_options;
                }


                if (!empty($old_fields[$subfield_id])) {

                    $subfield = $old_fields[$subfield_id];

                    // update
                    foreach ($params as $param_key => $param_value) {
                        $subfield->setParameter($param_key, $param_value);
                        $new_fields[$subfield_id] = $subfield;
                    }

                    // delete
                    if (!empty($params['_deleted']) && $params['_deleted'] == 1 && $field_constructor->canDeleteSubfield($field_id, $subfield_id)) {
                        unset($old_fields[$subfield_id]); // from old array
                        unset($new_fields[$subfield_id]); // from new array
                    }

                } else {
                    // create
                    $new_subfield = $this->createFromOpts($params, $all_fields);
                    foreach ($params as $param_key => $param_value) {
                        $new_subfield->setParameter($param_key, $param_value);
                    }
                    $new_subfield_id = $new_subfield->getId();
                    $new_fields[$new_subfield_id] = $new_subfield;

                    // For conditional fields, update ID in database: replace temporary id with new one
                    if ($new_subfield instanceof waContactConditionalField) {
                        $cfvm->changeField($subfield_id, $new_subfield->getId());
                    }
                }
            }

            // Add hidden address fields like `lat` and `lng`
            $new_fields += $old_fields;

            // Ensure correct format of 'hide' parameter of branch field
            foreach ($new_fields as &$subfield) {
                $this->normalizeHideParameter($subfield);
            }
            unset($subfield);

            $field->setParameter('fields', $new_fields);
            waContactFields::updateField($field);

            // Delete garbage from wa_contact_field_values
            $cfvm->exec("DELETE FROM wa_contact_field_values WHERE field RLIKE '__[0-9]+$'");
        }
    }


    /**
     * Ensure correct format of 'hide' parameter of branch field
     *
     * @param waContactBranchField $field
     */
    protected function normalizeHideParameter($field)
    {
        if (!($field instanceof waContactBranchField)) {
            return;
        }

        $hide_map = $field->getParameter('hide');
        $hide_map = is_array($hide_map) ? $hide_map : array();
        foreach ($hide_map as $key => $value) {
            $value = is_array($value) ? $value : array();
            if (empty($value)) {
                unset($hide_map[$key]);
            }
        }

        if ($hide_map === $field->getParameter('hide')) {
            return;
        }

        $field->setParameter('hide', $hide_map);
    }

    /**
     * Create new waContactField of appropriate type from given array of options.
     *
     * @param array $opts
     * @param array $occupied_keys
     * @return null|waContactField
     * @throws waException
     */
    public static function createFromOpts($opts, $occupied_keys = array())
    {
        if (!is_array($opts) || empty($opts['_type']) || waConfig::get('is_template')) {
            return null;
        }

        // Generate field_id from name
        $fld_id = self::transliterate((string)ifset($opts['localized_names'], ''));
        if (!$fld_id) {
            $fld_id = 'f';
        }
        if (strlen($fld_id) > 15) {
            $fld_id = substr($fld_id, 0, 15);
        }
        while (isset($occupied_keys[$fld_id])) {
            if (strlen($fld_id) >= 15) {
                $fld_id = substr($fld_id, 0, 10);
            }
            $fld_id .= mt_rand(0, 9);
        }

        // Create field object of appropriate type
        $options = array();
        $_type = strtolower($opts['_type']);
        switch ($_type) {
            case 'textarea':
                $class = 'waContactStringField';
                $options['storage'] = 'waContactDataStorage';
                $options['input_height'] = 5;
                break;
            case 'radio':
                $class = 'waContactRadioSelectField';
                break;
            default:
                $class = 'waContact'.ucfirst($_type).'Field';
        }
        if (!$_type || !class_exists($class)) {
            return null;
        }
        return new $class($fld_id, '', $options);
    }

    /**
     * Suggests a URL part generated from specified string.
     *
     * @param string $str Specified string
     * @param boolean $strict Whether a default value must be generated if provided string results in an empty URL
     * @return string
     * @throws waException
     */
    public static function transliterate($str, $strict = true)
    {
        $str = preg_replace('/\s+/u', '-', $str);
        if ($str) {
            foreach (waLocale::getAll() as $lang) {
                $str = waLocale::transliterate($str, $lang);
            }
        }
        $str = preg_replace('/[^a-zA-Z0-9_-]+/', '', $str);
        if ($strict && !strlen($str)) {
            $str = date('Ymd');
        }
        return strtolower($str);
    }
}
