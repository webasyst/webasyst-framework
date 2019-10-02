<?php

class webasystFieldConstructor
{
    protected static $person_main_fields = array(
        'name',
        'title',
        'firstname',
        'middlename',
        'lastname',
        'jobtitle',
        'company',
        'email',
        'phone'
    );

    protected static $company_main_fields = array(
        'name',
        'company',
        'email',
        'phone'
    );

    protected $noneditable_fields = array(
        'sex',
        'email',
        'phone',
        'birthday',
        'im',
        'socialnetwork',
        'address',
        'url',
        'timezone',
        'locale',
        'about'
    );

    protected $disabled_fields = array(
        'person'  => array('categories'),
        'company' => array('categories')
    );

    protected $all_fields;
    protected $all_fields_plain;
    protected $options = array();

    private $pro_installed;
    private $crm_installed;

    public function __construct($options = array())
    {
        $this->options = $options;
        $this->ensureCustomFieldsExists();
        foreach (array('firstname', 'middlename', 'lastname', 'email', 'phone') as $f_id) {
            $field = $this->getField($f_id);
            if ($field) {
                $my_profile = $field->getParameter('my_profile');
                if ($my_profile != 2) {
                    $field->setParameter('my_profile', 2);
                    waContactFields::updateField($field);
                }
            }
        }
    }

    /**
     * @param waContactField|null $field
     * @param array|null $data
     * @return array of two items
     *          0 - null|waContactField
     *          1 - array of errors
     * @throws waException
     */
    public function updateField($field, $data = array())
    {
        $field = $field instanceof waContactField ? $field : null;

        if (!$data && !$field) {
            return array(null, array());
        }

        if ($data) {
            $res = $this->getUpdatedField($field, (array)$data);
            if (!$res[0]) {
                return $res;
            }
            $field = $res[0];
        }

        if ($this->isFieldEditable($field)) {
            waContactFields::updateField($field);
        }

        if (!$this->isFieldSystem($field)) {
            $info = $field->getInfo();
            $info['localized_names'] = $field->getParameter('localized_names');
            if ($field instanceof waContactSelectField) {
                $info['options'] = $field->getOptions();
            }
            $info['disabled'] = true;
        }

        return array($field, array());
    }

    public function saveFieldsOrder($field_ids = array())
    {
        $fields_order = $this->getAllFieldsOrder();
        if ($field_ids == $fields_order) {
            return;
        }

        $person_fields = array();
        $company_fields = array();

        foreach ($field_ids as $id) {
            $pf = waContactFields::get($id, 'person');
            $cf = waContactFields::get($id, 'company');
            $pStatus = $pf ? ($pf->getParameter('required') ? 'required' : 'enabled') : 'disabled';
            $cStatus = $cf ? ($cf->getParameter('required') ? 'required' : 'enabled') : 'disabled';
            if ($pStatus != 'disabled') {
                $person_fields[] = $id;
            }
            if ($cStatus != 'disabled') {
                $company_fields[] = $id;
            }
        }

        $this->saveAllFieldsOrder($field_ids);
        $this->savePersonFieldsOrder($person_fields);
        $this->saveCompanyFieldsOrder($company_fields);

        if ($this->contactsProInstalled()) {
            contactsProHelper::sortFieldsInSearchConfig($field_ids);
        }

        if ($this->crmInstalled()) {
            $this->sortFieldsInSearchConfig($field_ids);
        }
    }

    protected function sortFieldsInSearchConfig($fields)
    {
        if (!$this->crmInstalled()) {
            return;
        }
        $field_ids = array();
        foreach ($fields as $field) {
            if ($field instanceof waContactField) {
                $field_ids[] = $field->getId();
            } else {
                $field_ids[] = (string)$field;
            }
        }
        $field_ids = array_filter($field_ids);
        crmContactsSearchHelper::sortItems(array_unique(array_merge(self::$person_main_fields, self::$company_main_fields, $field_ids)), 'contact_info');
    }

    /**
     * @param null $field
     * @param array $data
     * @return array of two items
     *          0 - null|waContactField
     *          1 - array of errors
     * @throws waException
     */
    protected function getUpdatedField($field = null, $data = array())
    {
        $id = trim(ifset($data['id']));
        $names = (array)ifset($data['names']);
        $ftype = trim(ifset($data['ftype']));
        $unique = 0;

        if (!is_array($names) || !$names) {
            if ($field) {
                $names = array();
            } else {
                return array(null, array('Wrong names: must be a non-empty array.'));
            }
        }

        if ($names) {
            $locales = waSystem::getInstance()->getConfig()->getLocales('name');
            $n = array();
            foreach ($names as $l => $value) {
                if (!isset($locales[$l])) {
                    return array(null, array(sprintf(_ws('Unknown locale: %s'), $l)));
                }
                $value = (string)$value;
                if (strlen($value) > 0) {
                    $n[$l] = $value;
                }
            }
            $names = $n;
            if (empty($names)) {
                return array(null, array(array(wa()->getLocale() => _ws('Required field'))));
            }
        }

        $select_field_value = trim(ifset($data['select_field_value']));

        if ($field) {
            $id = $field->getId();
        } else {
            if (strlen($id) === 0) {
                return array(null, array(array("id_val" => _ws('Required field'))));
            }
            if (preg_match('/[^a-z_\-0-9]/i', $id)) {
                return array(
                    null,
                    array(
                        array('id_val' => _ws('Only English alphanumeric, hyphen and underline symbols are allowed'))
                    )
                );
            }
            // field id exists
            if (null !== $this->isFieldSystem($id)) {
                return array(
                    null,
                    array(
                        _ws('This ID is already in use')
                    )
                );
            }

            switch ($ftype) {
                case "String":
                    $field = new waContactStringField($id, $names);
                    break;
                case "Date":
                    $field = new waContactDateField($id, $names);
                    break;
                case "Number":
                    $field = new waContactNumberField($id, $names);
                    break;
                case "Phone":
                    $field = new waContactPhoneField($id, $names);
                    break;
                case "Url":
                    $field = new waContactUrlField($id, $names);
                    break;
                case "Text":
                    $field = new waContactTextField($id, $names);
                    break;
                case "Select":
                    $options = array_map('trim', array_filter(explode("\r\n", $select_field_value)));
                    $field = new waContactSelectField($id, $names, array(
                        'options' => $options
                    ));
                    break;
                case "Checkbox":
                    $field = new waContactCheckboxField($id, $names);
                    break;
                case "Radio":
                    $options = array_map('trim', array_filter(explode("\r\n", $select_field_value)));
                    $field = new waContactRadioSelectField($id, $names, array('options' => $options));
                    break;
                default:
                    return array(
                        null,
                        array(
                            _ws('Unknown field type:').' '.$ftype
                        )
                    );
            }
        }

        if ($names && !$this->isFieldSystem($id) && $this->isFieldEditable($id)) {
            $field->setParameter('localized_names', $names);
        }

        if ($select_field_value && $field->getParameter('storage') === 'data') {
            $opts = array_map('trim', array_filter(explode("\r\n", $select_field_value)));
            if (!empty($opts)) {
                $select_options = array();
                foreach ($opts as $val) {
                    $select_options[$val] = $val;
                }
                $field->setParameter('options', $select_options);
            }
        }

        if ($unique && !in_array($id, self::$person_main_fields) && !($field instanceof waContactCompositeField)) {
            // Check for duplicates in $field
            $dupl = $field->getStorage()->duplNum($field);

            if ($dupl) {
                $msg = sprintf(_ws('We have found %d duplicate for this field', 'We have found %d duplicates for this field'), $dupl);
                $msg = str_replace(array('[', ']'), array('<a href="'.wa_url().'webasyst/contacts/#/contacts/duplicates/'.$field->getId().'/">', '</a>'), $msg);
                return array(null, array($msg));
            }

            $field->setParameter('unique', !!$unique);
        } else {
            $field->setParameter('unique', false);
        }

        return array($field, array());
    }

    /**
     * @param string|waContactField $field
     * @return bool
     */
    public function isFieldEditable($field)
    {
        if ($field instanceof waContactField) {
            $field_id = $field->getId();
        } else {
            $field_id = (string)$field;
        }
        return !in_array($field_id, $this->noneditable_fields);
    }

    /**
     * Can delete this field or subfield
     * @param string $field_id
     * @param string $subfield_id
     * @return bool|mixed
     */
    public function canDeleteSubfield($field_id, $subfield_id)
    {
        if (!is_scalar($field_id) || !is_scalar($subfield_id)) {
            return false;
        }
        $parent_field_info = $this->getFieldInfo($field_id);
        $original_sub_field_ids = ifset($parent_field_info, 'original_sub_field_ids', array());
        $original_sub_field_ids = is_array($original_sub_field_ids) ? $original_sub_field_ids : array();
        return !in_array($subfield_id, $original_sub_field_ids, true);
    }

    /**
     * Is field system
     * @param string|waContactField $field
     * @return mixed null, if field does not exist; false if it is custom; true if it is system.
     * @throws waException
     */
    public function isFieldSystem($field)
    {
        if ($field instanceof waContactField) {
            $field_id = $field->getId();
        } else {
            $field_id = (string)$field;
        }
        return waContactFields::isSystemField($field_id);
    }

    public function getPersonMainFields()
    {
        return self::$person_main_fields;
    }

    public function getPersonAlwaysDisabledFields()
    {
        return $this->disabled_fields['person'];
    }

    /**
     * @param string|waContactField $field
     * @param mixed $types array('person', 'company'), array('person'), array('company'), 'all' (true), empty(false, null)
     * @throws waException
     */
    public function enableField($field, $types)
    {
        if (!($field instanceof waContactField)) {
            $field_id = (string)$field;
            $field = $this->getField($field_id);
        }

        if (!($field instanceof waContactField)) {
            return;
        }

        if (!$types) {
            $types = array();
        } else {
            if ($types === 'all' || $types === true) {
                $types = array('person', 'company');
            } else {
                $types = (array)$types;
            }
        }

        // email field is always enabled for person
        if ($field instanceof waContactEmailField) {
            $types[] = 'person';
        }

        $types = array_unique($types);

        $was_enabled_for_contact = (bool)waContactFields::get($field->getId(), 'enabled');

        $enable = 0;
        foreach (array('person', 'company') as $type) {
            if (in_array($type, $types)) {
                $enable += 1;
                waContactFields::enableField($field, $type);
            } else {
                waContactFields::disableField($field, $type);
            }
        }

        if ($this->contactsProInstalled()) {
            if ($enable > 0) {
                if (!contactsProHelper::isEnabledSearchingByField($field)) {
                    contactsProHelper::enableSearchingByField($field);
                }
            } else {
                if (contactsProHelper::isEnabledSearchingByField($field)) {
                    contactsProHelper::disableSearchingByField($field);
                }
            }
        }

        if ($this->crmInstalled()) {
            if ($enable > 0) {
                if (!crmContactsSearchHelper::isContactFieldEnabledForSearch($field)) {
                    crmContactsSearchHelper::enableContactFieldForSearch($field);
                }
            } else {
                if (crmContactsSearchHelper::isContactFieldEnabledForSearch($field)) {
                    crmContactsSearchHelper::disableContactFieldForSearch($field);
                }
            }
        }

        // IF was not enable for contact and now is - move field in his OLD place
        if (!$was_enabled_for_contact && $enable > 0) {
            $all_fields_order = $this->getAllFieldsOrder();
            $fields_order = array_diff($all_fields_order, self::$person_main_fields, self::$company_main_fields);
            $this->saveFieldsOrder($fields_order);
        }

    }

    /**
     * @param $field_id
     * @return waContactField|waContactCompositeField|null
     * @throws waException
     */
    public function getField($field_id)
    {
        return waContactFields::get($field_id, 'all');
    }

    /**
     * @param $field_id
     * @param bool
     * @return array
     */
    public function getFieldInfo($field_id)
    {
        $fields = $this->getAllFields();
        if (isset($fields['main'][$field_id])) {
            $info = $fields['main'][$field_id];
        } elseif (isset($fields['other'][$field_id])) {
            $info = $fields['other'][$field_id];
        } else {
            $info = null;
        }
        if ($info && $field_id === 'address') {
            $info['original_sub_field_ids'] = array(
                'street',
                'city',
                'region',
                'zip',
                'country'
            );
        }
        return $info;
    }

    public function getAllFields()
    {
        if ($this->all_fields !== null) {
            return $this->all_fields;
        }

        $current_locale = waLocale::getLocale();
        foreach (waLocale::getAll() as $locale_id) {
            if ($current_locale != $locale_id) {
                waLocale::load($locale_id, wa()->getAppPath('locale', 'webasyst'), 'webasyst', true);
            }
        }

        waLocale::load($current_locale, wa()->getAppPath('locale', 'webasyst'), 'webasyst', true);

        $contactFields = array();
        $fields = $this->getAllFieldsPlainList();
        if (isset($fields['company_contact_id'])) {
            unset($fields['company_contact_id']);
        }

        $field_types = $this->getFieldTypes();

        foreach ($fields as $field_id => $field) {
            /**
             * @var waContactField $field
             */
            try {
                $contactFields[$field_id] = $field->getInfo();
            } catch (waException $e) {
                waContactFields::deleteField($field_id);
                continue;
            }
            if (method_exists($field, 'getOptions')) {
                try {
                    $contactFields[$field_id]['options'] = $field->getOptions();
                } catch (waException $e) {
                    $contactFields[$field_id]['options'] = null;
                }
            } else {
                $contactFields[$field_id]['options'] = null;
            }

            // if this field is 'system' and we can't edit or delete it
            if (in_array($field_id, $this->noneditable_fields)) {
                $contactFields[$field_id]['editable'] = false;
                foreach (waLocale::getAll() as $locale_id) {
                    $contactFields[$field_id]['localized_names'][$locale_id] = $field->getName($locale_id);
                }
            } else {
                $contactFields[$field_id]['editable'] = true;
                $contactFields[$field_id]['localized_names'] = $field->getParameter('localized_names');
            }

            $contactFields[$field_id]['storage'] = $field->getParameter('storage');
            $contactFields[$field_id]['top'] = $field->getParameter('top');

            $contactFields[$field_id]['my_profile'] = $field->getParameter('my_profile');
            if (!$contactFields[$field_id]['my_profile']) {
                $contactFields[$field_id]['my_profile'] = '0';  // editable
            }

            if ($field instanceof waContactLocaleField) {
                $contactFields[$field_id]['type'] = 'Language';
            }
            if ($field instanceof waContactTimezoneField) {
                $contactFields[$field_id]['type'] = 'Timezone';
            }
            if ($field instanceof waContactRadioSelectField) {
                $contactFields[$field_id]['type'] = 'Radio';
            }

            if (isset($field_types[$contactFields[$field_id]['type']])) {
                $contactFields[$field_id]['type_name'] = $field_types[$contactFields[$field_id]['type']];
            }
        }

        // for holding order
        $main_fields = array();
        $other_fields = array();

        foreach ($contactFields as $id => $data) {
            if ($id == 'name') {
                continue;
            }

            $fcp = waContactFields::get($id, 'all')->getParameter('fconstructor');
            if ($fcp && !is_array($fcp)) {
                $fcp = array($fcp);
            }
            if (!$fcp) {
                $fcp = array();
            }
            $fcp = array_flip($fcp);
            if (isset($fcp['hidden'])) {
                continue;
            }

            $pf = waContactFields::get($id, 'person');
            $cf = waContactFields::get($id, 'company');

            if ($pf instanceof waContactCompositeField || $cf instanceof waContactCompositeField) {
                $unique = 'n/a';
            } else {
                $unique = $pf ? $pf->getParameter('unique') : ($cf ? $cf->getParameter('unique') : false);
            }

            if (in_array($id, self::$person_main_fields)) {
                $p_field = &$main_fields[$id];
            } else {
                $p_field = &$other_fields[$id];
            }

            $p_field = array(
                'name'            => $data['name'],
                'id'              => $id,
                'type'            => $data['type'],
                'type_name'       => ifset($data['type_name']),
                'multi'           => $data['multi'],
                'options'         => $data['options'],
                'editable'        => $data['editable'],
                'unique'          => $unique,
                'storage'         => $data['storage'],
                'pStatus'         => $pf ? ($pf->getParameter('required') ? 'required' : 'enabled') : 'disabled',
                'cStatus'         => $cf ? ($cf->getParameter('required') ? 'required' : 'enabled') : 'disabled',
                'localized_names' => $data['localized_names'],
                'my_profile'      => $data['my_profile'],
                'top'             => $data['top'],
                'icon'            => ''
            );

            $p_field['disabled'] = $p_field['pStatus'] == 'disabled' && $p_field['cStatus'] == 'disabled';

            if ($id == 'email') {
                $p_field['icon'] = '<i class="icon16 email"></i>';
            } elseif ($id == 'phone') {
                $p_field['icon'] = '<i class="icon16 phone"></i>';
            } elseif ($id == 'im') {
                $p_field['icon'] = '<i class="icon16 im"></i>';
            }

            unset($p_field);
        }

        wa()->getConfig()->setLocale($current_locale, true);

        return $this->all_fields = array(
            'main'  => $main_fields,
            'other' => $other_fields
        );
    }

    public function getAllFieldsPlainList()
    {
        if ($this->all_fields_plain !== null) {
            return $this->all_fields_plain;
        }

        $all_fields = waContactFields::getAll('all', true);
        $all_fields_order_file = wa()->getConfig()->getConfigPath('all_fields_order.php', true, 'contacts');

        if (!file_exists($all_fields_order_file)) {
            $order = array_keys($all_fields);
            waUtils::varExportToFile($order, $all_fields_order_file, true);
            $this->all_fields_plain = $all_fields;
        } else {
            $order = include($all_fields_order_file);
            $res = array();
            $del = false;
            foreach ($order as $f_id) {
                if (isset($all_fields[$f_id])) {
                    $res[$f_id] = $all_fields[$f_id];
                    unset($all_fields[$f_id]);
                } else {
                    $del = true;
                    unset($order[$f_id]);
                }
            }
            foreach ($all_fields as $f_id => $f) {
                $res[$f_id] = $all_fields[$f_id];
            }
            if ($del) {
                waUtils::varExportToFile($order, $all_fields_order_file, true);
            }
            $this->all_fields_plain = $res;
        }

        return $this->all_fields_plain;
    }

    protected function getAllFieldsOrder()
    {
        return array_keys($this->getAllFieldsPlainList());
    }

    protected function saveAllFieldsOrder($fields)
    {
        $this->saveFieldsOrderByType($fields, 'all');
    }

    protected function savePersonFieldsOrder($fields)
    {
        $this->saveFieldsOrderByType($fields, 'person');
    }

    protected function saveCompanyFieldsOrder($fields)
    {
        $this->saveFieldsOrderByType($fields, 'company');
    }

    protected function saveFieldsOrderByType($fields, $type = 'all')
    {
        $this->all_fields_plain = null;
        $this->all_fields = null;

        $item = reset($fields);
        if (!is_string($item)) {
            $fields = array_keys($fields);
        }

        if ($type == 'all') {
            $all_fields_order_file = wa()->getConfig()->getConfigPath('all_fields_order.php', true, 'contacts');
            waUtils::varExportToFile($fields, $all_fields_order_file, true);
            return;
        }

        if ($type == 'person' || $type == 'company') {
            $main_fields = $type == 'person' ? self::$person_main_fields : self::$company_main_fields;
            foreach ($main_fields as $id) {
                $k = in_array($id, $fields);
                if ($k !== false) {
                    unset($fields[$k]);
                }
            }

            $new_order = array_merge($main_fields, $fields);
            waContactFields::sortFields($new_order, $type);
        }
    }

    public function getFieldTypes()
    {
        return array(
            'String'   => _ws('one-line text'),
            'Text'     => _ws('multi-line text'),
            'Number'   => _ws('number'),
            'Radio'    => _ws('radio select'),
            'Select'   => _ws('drop-down list'),
            'Checkbox' => _ws('checkbox'),
            'Date'     => _ws('date'),
        );
    }

    public function getLocale()
    {
        $l = wa()->getLocale();
        $ls = wa()->getConfig()->getLocales('name_region');
        return array(
            'id'          => $l,
            'name_region' => $ls[$l]
        );
    }

    public function getOtherLocales()
    {
        $l = wa()->getLocale();
        $ls = wa()->getConfig()->getLocales('name_region');
        $ols = array();
        unset($ls[$l]);
        foreach ($ls as $id => $nr) {
            $ols[] = array(
                'id'          => $id,
                'name_region' => $nr
            );
        }
        return $ols;
    }

    public function deleteField($field)
    {
        if (is_string($field)) {
            $field_id = $field;
            $field = waContactFields::get($field, 'all');
        } else {
            $field_id = $field->getId();
            $field = waContactFields::get($field, 'all');
        }

        if ($field) {
            if ($this->contactsProInstalled()) {
                if (contactsProHelper::isEnabledSearchingByField($field_id)) {
                    contactsProHelper::disableSearchingByField($field_id);
                }
            }
            if ($this->crmInstalled()) {
                if (crmContactsSearchHelper::isContactFieldEnabledForSearch($field)) {
                    crmContactsSearchHelper::disableContactFieldForSearch($field);
                }
                crmDealFields::deleteField($field_id);
                crmForm::deleteFieldsFromForms($field_id);
            }
            waContactFields::deleteField($field_id);
        }

        if ($field_id) {
            $all_fields_order_file = wa()->getConfig()->getConfigPath('all_fields_order.php', true, 'contacts');
            $order = include($all_fields_order_file);
            $k = array_search($field_id, $order);
            if ($k !== false) {
                unset($order[$k]);
                waUtils::varExportToFile($order, $all_fields_order_file, true);
            }
        }
    }

    protected function ensureCustomFieldsExists()
    {
        $custom_fields_file = wa()->getConfig()->getConfigPath('custom_fields.php', true, 'contacts');
        if (file_exists($custom_fields_file)) {
            return;
        }

        $system_fields_file = wa()->getConfig()->getPath('system', 'contact/data/fields');
        waFiles::copy($system_fields_file, $custom_fields_file);

        // enable main fields for person
        $sort = 0;
        foreach (self::$person_main_fields as $f_id) {
            $field = waContactFields::get($f_id, 'all');
            if ($field) {
                waContactFields::updateField($field);
                waContactFields::enableField($field, 'person', $sort);
                $sort += 1;
            }
        }

        // enable main fields for company
        $sort = 0;
        foreach (self::$company_main_fields as $f_id) {
            $field = waContactFields::get($f_id, 'all');
            if ($field) {
                if ($f_id === 'company') {
                    $field->setParameter('required', true);
                } else {
                    if ($f_id === 'name') { // because company is its name
                        $field->setParameter('required', false);
                    }
                }
                waContactFields::updateField($field);
                waContactFields::enableField($field, 'company', $sort);
                $sort += 1;
            }
        }

        // disable fields
        foreach ($this->disabled_fields as $type => $fields) {
            foreach ($fields as $f_id) {
                $field = waContactFields::get($f_id, 'all');
                if ($field) {
                    waContactFields::enableField($field, $type);
                    waContactFields::disableField($field, $type);
                }
            }
        }
    }

    protected function contactsProInstalled()
    {
        if ($this->pro_installed !== null) {
            return $this->pro_installed;
        }
        if (!wa()->appExists('contacts')) {
            $this->pro_installed = false;
        } else {
            $plugins = wa('contacts')->getConfig()->getPlugins();
            $this->pro_installed = !empty($plugins['pro']);
        }
        return $this->pro_installed;
    }

    protected function crmInstalled()
    {
        if ($this->crm_installed !== null) {
            return $this->crm_installed;
        }

        $this->crm_installed = wa()->appExists('crm');
        if ($this->crm_installed) {
            wa('crm');
        }
        return $this->crm_installed;
    }
}
