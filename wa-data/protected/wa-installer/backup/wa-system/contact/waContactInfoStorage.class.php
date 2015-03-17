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
class waContactInfoStorage extends waContactStorage
{
    /**
     * @var waContactModel
     */
    protected $model;

    public function getModel() {
        if (!$this->model) {
            $this->model = new waContactModel();
        }
        return $this->model;
    }
    
    public function load(waContact $contact, $fields = null)
    {
        $this->getModel();
        $data = $this->model->getById($contact->getId());
        if (!$data) {
            throw new waException('Contact does not exist: '.$contact->getId(), 404);
        }
        return $data;
    }

    public function save(waContact $contact, $fields)
    {
        $this->getModel();
        if (isset($fields['birthday']) && isset($fields['birthday']['value'])) {
            $fields = array_merge($fields, $fields['birthday']['value']);
            unset($fields['birthday']);
        }
        if ($contact->getId()) {
            return $this->model->updateById($contact->getId(), $fields);
        } else {
            return $this->model->insert($fields);
        }
    }

    public function deleteAll($fields, $type=null) {
        if (!is_array($fields)) {
            $fields = array($fields);
        }
        if (!$fields) {
            return;
        }

        switch($type) {
            case 'person':
                $cwhere = " WHERE c.is_company=0 ";
                break;
            case 'company':
                $cwhere = " WHERE c.is_company>0 ";
                break;
            default:
                $cwhere = '';
        }

        $set = array();
        foreach($fields as $id) {
            switch($id) {
                case 'birthday':
                    $value = "'0000-00-00'";
                    break;
                case 'photo':
                    $value = 0;
                    break;
                default:
                    $value = "''";
                    break;
            }
            $set[] = "`$id`=$value";
        }

        $set = implode(',', $set);
        $this->getModel();
        $this->model->exec("UPDATE wa_contact AS c SET ".$set.$cwhere);
    }

    public function duplNum($field) {
        // Check if field exists, is active and is kept in this storage
        if ($field instanceof waContactField) {
            $field = $field->getId();
        }
        if (! ( $field = waContactFields::get($field))) {
            return 0;
        }
        if ($field->getParameter('storage') != 'info') {
            return 0;
        }
        $field = $field->getId();

        $sql = "SELECT SUM(t.num) AS dupl
                FROM (
                    SELECT `$field` AS f, COUNT(*) AS num
                    FROM wa_contact
                    WHERE LENGTH(`$field`) > 0
                    GROUP BY f
                    HAVING num > 1
                ) AS t";
        $this->getModel();
        $r = $this->model->query($sql)->fetchField();
        return $r ? $r : 0;
    }

    public function findDuplicatesFor($field, $values, $excludeIds=array()) {
        if (!$values) {
            return array();
        }
        // Check if field exists, is active and is kept in this storage
        if (!($field instanceof waContactField)) {
            $field = waContactFields::get($field);
            if (!$field) {
                return array();
            }
        }
        if ($field->getParameter('storage') != 'info') {
            return array();
        }
        $field = $field->getId();

        $sql = "SELECT `$field` AS f, id
                FROM wa_contact
                WHERE `$field` IN (:values)".
                    ($excludeIds ? " AND id NOT IN (:excludeIds) " : ' ').
                "GROUP BY f";
        $this->getModel();
        $r = $this->model->query($sql, array('values' => $values, 'excludeIds' => $excludeIds));
        return $r->fetchAll('f', true);
    }
}
