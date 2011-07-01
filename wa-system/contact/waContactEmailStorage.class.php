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
class waContactEmailStorage extends waContactStorage
{
    /**
     * @var waContactEmailsModel
     */
    protected $model;
    
    /**
     * Returns model 
     * 
     * @return waContactEmailsModel
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = new waContactEmailsModel();
        }
        return $this->model;        
    }
    
    public function load(waContact $contact, $fields = null)
    {
        return array('email' => $this->getModel()->getEmails($contact->getId()));
    }

    public function save(waContact $contact, $fields)
    {
        if (!isset($fields['email'])) {
        	return true;	
        }
    	
        $data = array();
        $delete_flag = false;
        foreach ($fields['email'] as $sort => $field) {
            if ($field === null) {
                $sql = "DELETE FROM ".$this->getModel()->getTableName()." 
                        WHERE contact_id = i:id AND sort >= i:sort";
                $this->getModel()->exec($sql, array('id' => $contact->getId(), 'sort' => $sort));
                continue;
            } 
            
            if (is_array($field)) {
                $value = $field['value'];
            } else {
                $value = $field;
            }
            $ext = (is_array($field) && isset($field['ext'])) ? $field['ext'] : '';
            if ($value) {
                $data[] = $contact->getId().", '".$this->getModel()->escape($value)."', '".$this->getModel()->escape($ext)."' , ".(int)$sort;
            } else {
                $sql = "DELETE FROM ".$this->getModel()->getTableName()." 
                        WHERE contact_id = i:id AND sort = i:sort";
                $this->getModel()->exec($sql, array('id' => $contact->getId(), 'sort' => $sort));
                $delete_flag = true;
                continue;
            }
        }
        if ($delete_flag) {
                $sql = "DELETE FROM ".$this->getModel()->getTableName()." 
                        WHERE contact_id = i:id AND sort >= i:sort";
                $this->getModel()->exec($sql, array('id' => $contact->getId(), 'sort' => $sort));            
        } 
        if ($data) {
            $sql = "INSERT INTO ".$this->getModel()->getTableName()." (contact_id, email, ext, sort) 
                    VALUES (".implode("), (", $data).") ON DUPLICATE KEY UPDATE email = VALUES(email), ext = VALUES(ext), sort = VALUES(sort)";
            return $this->getModel()->exec($sql);
        }
        return true;
    }

    public function deleteAll($fields, $type=null) {
        switch($type) {
            case 'person':
            case 'company':
                $join = " JOIN wa_contact AS c ON c.id=ce.contact_id ";
                if ($type == 'company') {
                    $cwhere = " WHERE c.is_company>0 ";
                } else {
                    $cwhere = " WHERE c.is_company=0 ";
                }
                break;
            default:
                $join = '';
                $cwhere = '';
        }

        // Hope they know what they're doing :)
        $this->getModel()->query("DELETE ce FROM ".$this->getModel()->getTableName()." AS ce".$join.$cwhere);
    }


    public function duplNum($field) {
        $sql = "SELECT SUM(t.num) AS dupl
                FROM (
                    SELECT email, COUNT(*) AS num
                    FROM wa_contact_emails
                    GROUP BY email
                    HAVING num > 1 
                ) AS t";
        $r = $this->getModel()->query($sql)->fetchField();
        return $r ? $r : 0;
    }
    
    public function findDuplicatesFor($field, $values, $excludeIds=array()) {
        if (!$values) {
            return array();
        }
        $sql = "SELECT email, contact_id
                FROM wa_contact_emails
                WHERE email IN (:values)".
                    ($excludeIds ? " AND contact_id NOT IN (:excludeIds) " : '').
                "GROUP BY email";
        $r = $this->getModel()->query($sql, array('values' => $values, 'excludeIds' => $excludeIds));
        return $r->fetchAll('email', true);
    }
}

// EOF