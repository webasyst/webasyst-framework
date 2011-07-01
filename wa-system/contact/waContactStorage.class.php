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
abstract class waContactStorage 
{
    
    public function getType()
    {
        return get_class($this);
    }
    
    public function get(waContact $contact, $fields = array(), $old_value = false)
    {    	
        if (!is_array($fields)) {
            $all_fields = array($fields);
        } else {
            $all_fields = $fields;
        }
        
        $result = array();
        $load_fields = array();
        foreach ($all_fields as $i => $field_id) {
            if ($contact->issetCache($field_id, $old_value)) {
                $result[$field_id] = $contact->getCache($field_id, $old_value);
            } else {
                if (strpos($field_id, ':') === false) {
                    $result[$field_id] = null;
                }
                $load_fields[] = $field_id;
            }
        }
                
        if ((!$fields || $load_fields) && $contact->getId()) {
            if ($load_result = $this->load($contact, $load_fields)) {
                $result = $load_result + $result;
            }
            $contact->setCache($result);
        }
                
        if (!is_array($fields)) {
            return $result[$fields];    
        } else {
            return $result;    
        }
    }
    
    abstract protected function load(waContact $contact, $fields = null);
    
    public function set(waContact $contact, $fields)
    {
        // Load data from database
        $this->get($contact, array_keys($fields), true);
        
        foreach ($fields as $field_id => $value) {
            if (($old_value = $contact->getCache($field_id, true)) === $value) {
                unset($fields[$field_id]);
            } 
            if (is_array($old_value) && is_array($value) && count($old_value) > count($value)) {
                $fields[$field_id][] = null;
            }    
        }
        if ($result = $this->save($contact, $fields)) {
            $contact->removeCache(array_keys($fields));
        }
        return $result;
    }
    
    abstract protected function save(waContact $contact, $fields);
    
    /** Delete all data for specified fields.
      * @param array $fields list of fields to remove
      * @param string $type company|person|both (defaults to both) */
    abstract public function deleteAll($fields, $type=null);

    /** Number of duplicates in db for given field.
      * @param waContactField|string $field
      * @return int */
    abstract public function duplNum($field);
    
    /** For each [key => value] pair in $values search db for value in $field
      * among all contacts except $excludeIds. (Note that $values could contain dublicates
      * itself, and they won't be reported.)
      * @param waContactField|string $field 
      * @return array key => contact_id for each key from $values for which a record in db found */
    abstract public function findDuplicatesFor($field, $values, $excludeIds=array());
}

// EOF