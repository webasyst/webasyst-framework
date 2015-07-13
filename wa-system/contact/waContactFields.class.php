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

/**
 * Static class to manage contact fields
 */
class waContactFields
{
    /** Fields for 'person' contact type
      * id => waContactField */
    protected static $personFields;

    /** Fields for 'company' contact type
      * id => waContactField */
    protected static $companyFields;

    /** Fields not used for person contact type (system and custom).
      * $companyDisabled and $personDisabled share the same object instances. Don't spoil them, clone if needed.
      * id => waContactField */
    protected static $personDisabled;

    /** Fields not used for company contact type (system and custom).
      * $companyDisabled and $personDisabled share the same object instances. Don't spoil them, clone if needed.
      * id => waContactField */
    protected static $companyDisabled;

    /** id => boolean: true for system fields, false for custom ones */
    protected static $fieldStatus;

    /** Field parameters to store in *_fields_order.php
      * id => default value to store in *fields.php */
    public static $customParameters = array(
        'allow_self_edit' => false,
        'required' => false,
        'unique' => false,
    );

    /** List of storages, cache for self::getStorage()
      * id => waContactStorage */
    protected static $storages;

    /**
     * Get storage singleton by key
     * @param string $key info|email|data|classname to initialize (defaults to info)
     * @return waContactStorage
     */
    public static function getStorage($key = 'info')
    {
        switch ($key) {
            case 'data':
                $class = 'waContactDataStorage';
                break;
            case 'email':
                $class = 'waContactEmailStorage';
                break;
            case 'info':
                $class = 'waContactInfoStorage';
                break;
            default:
                $class = $key;
        }
        if (!isset(self::$storages[$class])) {
            self::$storages[$class] = new $class();
        }
        return self::$storages[$class];
    }

    /**
     * Return object of the field or null if not found
     *
     * @param string $field_id
     * @param string $type (defaults to enabled) see ::getAll() parameters for details
     * @return waContactField|waContactCompositeField
     */
    public static function get($field_id, $type='enabled')
    {
        self::ensureStaticVars();
        if (strpos($field_id, ':')) {
            $field_parts = explode(':', $field_id);
            $field_id = $field_parts[0];
        }
        if (($type == 'all' || $type == 'enabled' || $type == 'person') && isset(self::$personFields[$field_id])) {
            return self::$personFields[$field_id];
        }
        if (($type == 'all' || $type == 'enabled' || $type == 'company') && isset(self::$companyFields[$field_id])) {
            return self::$companyFields[$field_id];
        }
        if (($type == 'all' || $type == 'person_disabled') && isset(self::$personDisabled[$field_id])) {
            return self::$personDisabled[$field_id];
        }
        if (($type == 'all' || $type == 'company_disabled') && isset(self::$companyDisabled[$field_id])) {
            return self::$companyDisabled[$field_id];
        }
        return null;
    }

    /**
     * Merge two arrays (possibly containing same keys) so that the resulting array
     * contains key => value from each of them in order of appearance in original array.
     * If it is not possible, then no guarantee about resulting key order is made.
     * @param $a
     * @param $b
     * @return array
     */
    protected static function merge_maintaining_order($a, $b)
    {
        $result = array();
        reset($a);
        reset($b);

        /* Invariant: $result contains items (in correct order) from
         * $a up to current position (not including current position),
         * and from $b up to current position (not including current position).
         * Current position in $b is after the last processed element from $a
         * that $b also contains. */
        while(FALSE !== current($a)) {
            $ak = key($a);
            $bk = key($b);

            // In case parallel order in two arrays is broken, we check if item already exists
            // in $result. This should not happen, but it sometimes does (e.g. when user
            // edited files manually)
            if (isset($result[$ak])) {
                next($a);
                continue;
            }
            if (isset($result[$bk])) {
                next($b);
                continue;
            }

            if(isset($b[$ak])) {
                // add to $result all items from $b
                // starting from $bk and ending at $ak (not including $ak)
                while($ak != $bk) {
                    $result[$bk] = isset($a[$bk]) ? $a[$bk] : current($b);
                    next($b);
                    $bk = key($b);
                }
                // Set current position to the next item in $b after $ak
                next($b);
            }

            // Add ro $result current item from $a
            $result[$ak] = current($a);
            next($a);
        }

        // Add to $result all items from $b starting at current position
        while(FALSE !== current($b)) {
            $result[key($b)] = current($b);
            next($b);
        }

        return $result;
    }

    /**
     * Return array of field objects by type:
     * person: all person fields (default);
     * contact: all company fields;
     * person_disabled: existing custom and system fields disabled for person contacts;
     * company_disabled: existing custom and system fields disabled for company contacts.
     * enabled: all fields currently active either for company or for person; if a field is active for both then only a person instance returned
     * all: all fields; first enabled for person, then enabled for company, then rest.
     *
     * @param string $contactType person|contact|person_disabled|company_disabled|enabled
     * @param boolean $all (defaults to false) whether to include hidden fields in the list
     * @throws waException
     * @return array fieldId => waContactField
     */
    public static function getAll($contactType='person', $all=false)
    {
        self::ensureStaticVars();
        switch ($contactType) {
            case 'person':
                $result = self::$personFields;
                break;
            case 'company':
                $result = self::$companyFields;
                break;
            case 'person_disabled':
                $result = self::$personDisabled;
                break;
            case 'company_disabled':
                $result = self::$companyDisabled;
                break;
            case 'enabled':
                $result = self::$personFields + self::$companyFields;
                break;
            case 'all':
                $result = self::merge_maintaining_order(self::$personFields, self::$companyFields) + self::$personDisabled;
                break;
            default:
                throw new waException('Unknown contact type: '.$contactType);
        }

        if (!$all) {
            foreach($result as $id => $f) {
                /**
                 * @var waContactField $f
                 */
                if ($f->getParameter('hidden')) {
                    unset($result[$id]);
                }
            }
        }
        return $result;
    }

    /**
     * @param string $contactType defaults to person; see ::getAll() parameters for details
     * @param bool $all
     * @return array
     */
    public static function getInfo($contactType='person', $all = false)
    {
        self::ensureStaticVars();
        $result = array();
        $fields = self::getAll($contactType, $all);
        foreach ($fields as $field_id => $field) {
            /**
             * @var waContactField $field
             */
            $result[$field_id] = $field->getInfo();
        }
        return $result;
    }

    /**
     * Add a new field to custom_fields.php if its id is unique (throws waException otherwise)
     * @throws waException
     * @param waContactField $field
     * @deprecated use ::updateField() instead
     */
    public static function createField($field)
    {
        if (!($field instanceof waContactField)) {
            throw new waException('Invalid contact field '.print_r($field, TRUE));
        }
        self::ensureStaticVars();

        $id = $field->getId();
        if (null !== self::isSystemField($id)) {
            throw new waException('Field already exists: '.$id);
        }

        self::updateField($field);
    }

    /**
     * Deletes the field completely.
     * @param $id waContactField|int field ID or field instance.
     * @throws waException
     */
    public static function deleteField($id)
    {
        self::ensureStaticVars();
        if (is_object($id) && $id instanceof waContactField) {
            $id = $id->getId();
        }
        if (null === self::isSystemField($id)) {
            throw new waException('Unknown field: '.$id);
        }

        // remove custom field, if it is not used anymore
        if (self::isSystemField($id)) {
            throw new waException('Unable to delete system field: '.$id);
        }

        // Remove field from order lists first
        if (isset(self::$companyFields[$id])) {
            self::disableField($id, 'company', true);
        }
        if (isset(self::$personFields[$id])) {
            self::disableField($id, 'person', true);
        }

        $file = wa()->getConfig()->getConfigPath('custom_fields.php', true, 'contacts');
        if (!is_readable($file)) {
            return;
        }
        $fields = include($file);
        if (!$fields || !is_array($fields)) {
            return;
        }
        foreach ($fields as $k => $f) {
            /**
             * @var waContactField $f
             */
            if (!($f instanceof waContactField)) {
                throw new waException("Invalid contact field ".print_r($f, TRUE));
            }
            if ($id === $f->getId()) {
                break;
            }
        }
        if ($id !== $f->getId()) {
            throw new waException('Unable to find field '.$id.' in '.$file);
        }
        unset($fields[$k]);

        $fields = array_values($fields);
        foreach ($fields as $field) {
            if ($field instanceof waContactField) {
                $field->prepareVarExport();
            }
        }

        waUtils::varExportToFile(array_values($fields), $file, true);
        unset(self::$fieldStatus[$id], self::$personDisabled[$id], self::$companyDisabled[$id]);
    }

    /**
     * Update existing field without affecting *_fields_order.php
     * or add it to custom fields if not exists yet.
     * @throws waException
     * @param waContactField $field
     */
    public static function updateField($field)
    {
        if (! ( $field instanceof waContactField)) {
            throw new waException('Invalid contact field '.print_r($field, TRUE));
        }
        self::ensureStaticVars();
        $id = $field->getId();
        $field = clone $field;
        self::resetCustomParameters($field);

        // Update custom_fields config.
        // We don't care if a field is system or not: we write to custom config anyway.
        // Custom config overwrites system fields.
        $file = wa()->getConfig()->getConfigPath('custom_fields.php', true, 'contacts');
        if (is_readable($file)) {
            $fields = include($file);
        }
        if (empty($fields) || !is_array($fields)) {
            $fields = array();
        }
        $changed = false;
        foreach ($fields as $k => $v) {
            /** @var waContactField $v */
            if ($v->getId() == $id) {
                $fields[$k] = $field;
                $changed = true;
                break;
            }
        }
        if (!$changed) {
            $fields[] = $field;
        }
        foreach ($fields as $field) {
            if ($field instanceof waContactField) {
                $field->prepareVarExport();
            }
        }
        waUtils::varExportToFile($fields, $file, true);

        // Update static vars
        self::$fieldStatus[$id] = false;
        if (isset(self::$personFields[$id])) {
            $cp = self::getCustomParameters(self::$personFields[$id]);
            self::$personFields[$id] = clone $field;
            self::$personFields[$id]->setParameters($cp);
        } else {
            self::$personDisabled[$id] = $field;
        }
        if (isset(self::$companyFields[$id])) {
            $cp = self::getCustomParameters(self::$companyFields[$id]);
            self::$companyFields[$id] = clone $field;
            self::$companyFields[$id]->setParameters($cp);
        } else {
            self::$companyDisabled[$id] = $field;
        }
    }

    /**
     * Add an existing field to person or company order list at a given position.
     * If field already exists for this contact type then update its data and position.
     * @throws waException
     * @param $field string|waContactField Field ID
     * @param $type string person|company
     * @param $position int how many items to skip at the begining of list before inserting $field (default: new field is inserted at the end of the list, existing field position does not change);
     */
    public static function enableField($field, $type, $position=null)
    {
        if (!($field instanceof waContactField)) {
            $field = self::get($field, 'all');
        }
        if (!($field instanceof waContactField)) {
            throw new waException('Invalid contact field '.print_r($field, TRUE));
        }
        $id = $field->getId();
        self::ensureStaticVars();
        if (null === self::isSystemField($id)) {
            throw new waException('Unknown field: '.$id);
        }

        $cp = self::getCustomParameters($field);

        switch($type) {
            case 'person':
                if (isset(self::$personFields[$id])) {
                    self::$personFields[$id]->setParameters($cp);
                } else {
                    self::$personFields[$id] = clone $field;
                }

                $fileWrite = wa()->getConfig()->getConfigPath('person_fields_order.php', true, 'contacts');
                if (is_readable($fileWrite)) {
                    $fileRead = $fileWrite;
                } else {
                    $fileRead = wa()->getConfig()->getPath('system', 'contact/data/person_fields_default');
                }
                break;
            case 'company':
                if (isset(self::$companyFields[$id])) {
                    self::$companyFields[$id]->setParameters($cp);
                } else {
                    self::$companyFields[$id] = clone $field;
                }
                $fileWrite = wa()->getConfig()->getConfigPath('company_fields_order.php', true, 'contacts');
                if (is_readable($fileWrite)) {
                    $fileRead = $fileWrite;
                } else {
                    $fileRead = wa()->getConfig()->getPath('system', 'contact/data/company_fields_default');
                }
                break;
            default:
                throw new waException('Unknown contact type: '.$type);
        }

        $old = include($fileRead);
        if (empty($old) || !is_array($old)) {
            $old = array();
        }
        if ($position !== null) {
            $position = (int) $position;
        }

        $contactOrder = array();
        $i = 0;
        foreach($old as $k => $v) {
            if (($position !== null && $position !== FALSE && $position <= $i)
                    || ($k == $id && $position === null)) {
                $position = FALSE;
                $contactOrder[$id] = $cp;
            }

            if ($k != $id) {
                $contactOrder[$k] = $v;
                $i++;
            }
        }
        if ($position !== FALSE) {
            $contactOrder[$id] = $cp;
        }
        waUtils::varExportToFile($contactOrder, $fileWrite, true);
    }

    /**
     * Remove given field from person or company order list.
     * @param $type string person|company
     * @param $id waContactField|int field ID or field instance.
     * @param boolean $delete delete values from db or not
     * @throws waException
     */
    public static function disableField($id, $type, $delete = false) {
        self::ensureStaticVars();
        if (is_object($id) && $id instanceof waContactField) {
            $id = $id->getId();
        }
        if (null === self::isSystemField($id)) {
            throw new waException('Unknown field: '.$id);
        }

        switch($type) {
            case 'person':
                if (!isset(self::$personFields[$id])) {
                    return;
                }
                $f = self::$personFields[$id];
                unset(self::$personFields[$id]);
                self::$personDisabled[$id] = $f;
                $file = wa()->getConfig()->getConfigPath('person_fields_order.php', true, 'contacts');
                break;
            case 'company':
                if (!isset(self::$companyFields[$id])) {
                    return;
                }
                $f = self::$companyFields[$id];
                unset(self::$companyFields[$id]);
                self::$companyDisabled[$id] = $f;
                $file = wa()->getConfig()->getConfigPath('company_fields_order.php', true, 'contacts');
                break;
            default:
                throw new waException('Unknown contact type: '.$type);
        }

        /**
         * @var waContactField $f
         */

        if ($delete) {
            // Remove data from DB
            $f->getStorage()->deleteAll($id, $type);
        }

        // Remove field from order file
        if (!is_readable($file)) {
            return;
        }
        $contactOrder = include($file);
        unset($contactOrder[$id]);
        if (empty($contactOrder) || !is_array($contactOrder)) {
            $contactOrder = array();
        }
        waUtils::varExportToFile($contactOrder, $file, true);
    }

    /**
     * @param string $id
     * @return mixed null, if field does not exist; false if it is custom; true if it is system.
     */
    public static function isSystemField($id)
    {
        self::ensureStaticVars();
        if (!isset(self::$fieldStatus[$id])) {
            return null;
        }
        return self::$fieldStatus[$id];
    }

    /**
     * Get field parameters to be saved in *_fields_order.php
     * @param waContactField $field
     * @return array parameter => value
     */
    protected static function getCustomParameters($field)
    {
        $result = array();
        foreach(self::$customParameters as $p => $def) {
            $result[$p] = $field->getParameter($p);
        }
        return $result;
    }

    /**
     * Reset field parameters that are normally saved in *_fields_order.php
     * to prepare $field object to be saved in *fields.php
     * @param waContactField $field
     */
    protected static function resetCustomParameters($field)
    {
        foreach(self::$customParameters as $p => $def) {
            $field->setParameter($p, $def);
        }
    }

    /**
     * Load self::$personFields, self::$companyFields, self::$fieldStatus if not loaded yet
     * @throws waException
     * @return
     */
    protected static function ensureStaticVars()
    {
        if (self::$personFields !== null) {
            return;
        }

        // Temporary storage for field objects; id => waContactField
        $fields = array();

        // Load system fields
        self::$fieldStatus = array();
        foreach(include(wa()->getConfig()->getPath('system', 'contact/data/fields')) as $f) {
            /**
             * @var waContactField $f
             */
            if (!($f instanceof waContactField)) {
                throw new waException("Invalid contact field ".print_r($f, TRUE));
            }
            $id = $f->getId();
            self::$fieldStatus[$id] = true;
            $fields[$id] = $f;
        }

        // Load custom fields
        $file = wa()->getConfig()->getConfigPath('custom_fields.php', true, 'contacts');
        if (is_readable($file)) {
            $cfg = include($file);
            if (empty($cfg) || !is_array($cfg)) {
                $cfg = array();
            }
            foreach($cfg as $f) {
                /**
                 * @var waContactField $f
                 */
                if (!($f instanceof waContactField)) {
                    throw new waException("Invalid contact field ".print_r($f, TRUE));
                }
                $id = $f->getId();
                self::$fieldStatus[$id] = false;
                $fields[$id] = $f;
            }
        }

        // Person field order
        $file = wa()->getConfig()->getConfigPath('person_fields_order.php', true, 'contacts');
        if(!is_readable($file)) {
            $file = wa()->getConfig()->getPath('system', 'contact/data/person_fields_default');
        }
        $contactOrder = include($file);

        // Company field order
        $file = wa()->getConfig()->getConfigPath('company_fields_order.php', true, 'contacts');
        if(!is_readable($file)) {
            $file = wa()->getConfig()->getPath('system', 'contact/data/company_fields_default');
        }
        $companyOrder = include($file);

        // Load fields into self::$companyFields in correct order, and the rest into self::$companyDisabled
        self::$companyFields = array();
        foreach($companyOrder as $id => $param) {
            if (!isset($fields[$id])) {
                throw new waException('Unknown field '.$id.' in company field order.');
            }
            self::$companyFields[$id] = clone $fields[$id];
            self::$companyFields[$id]->setParameters($param);
        }
        self::$companyDisabled = array_diff_key($fields, self::$companyFields);

        // same for self::$personFields and self::$personDisabled
        self::$personFields = array();
        foreach($contactOrder as $id => $param) {
            if (!isset($fields[$id])) {
                throw new waException('Unknown field '.$id.' in person field order.');
            }

            if (isset(self::$companyDisabled[$id])) {
                self::$personFields[$id] = clone $fields[$id];
            } else {
                // don't have to clone since this object is used nowhere else
                self::$personFields[$id] = $fields[$id];
                unset($fields[$id]); // being paranoid
            }
            self::$personFields[$id]->setParameters($param);
        }
        self::$personDisabled = array_diff_key($fields, self::$personFields);
    }
}

// EOF