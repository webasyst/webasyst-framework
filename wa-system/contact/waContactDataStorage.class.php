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
class waContactDataStorage extends waContactStorage
{
    /**
     * @var waContactDataModel
     */
    protected $model;

    /**
     * Returns model
     *
     * @return waContactDataModel
     */
    public function getModel()
    {
        if (!$this->model) {
            $this->model = new waContactDataModel();
        }
        return $this->model;
    }

    public function load(waContact $contact, $fields = null)
    {
        foreach ($fields as $k => $field_id) {
            $f = waContactFields::get($field_id);
            if ($f && $f instanceof waContactCompositeField) {
                unset($fields[$k]);
                $fields = array_merge($fields, $f->getField());
            }
        }
        return $this->getModel()->getData($contact->getId(), $fields);
    }

    public function save(waContact $contact, $fields)
    {
        $contact_id = $contact->getId();
        $data = array();
        foreach ($fields as $field => $value) {
            $f = waContactFields::get($field);
            if (!$f || !$f->isMulti()) {
                if ($f instanceof waContactCompositeField) {
                    /**
                     * @var $f waContactCompositeField
                     */
                    $delete = array();
                    if (isset($value['data'])) {
                        $value = $value['data'];
                    } elseif (isset($value['value']) && is_array($value['value'])) {
                        $value = $value['value'];
                    }
                    foreach ($f->getField(false) as $subfield) {
                        if (isset($value[$subfield]) && $value[$subfield]) {
                            $data[$field.":".$subfield][0] = array(
                                'value' => $value[$subfield],
                                'ext' => ''
                            );
                        } else {
                            $delete[] = $field.":".$subfield;
                        }
                    }
                    if ($delete) {
                        $sql = "DELETE FROM ".$this->getModel()->getTableName()."
                                WHERE contact_id = ".(int)$contact_id." AND field IN ('".implode("', '", $this->getModel()->escape($delete))."')";
                        $this->getModel()->exec($sql);
                    }
                } else {
                    if ($value === null) {
                        $sql = "DELETE FROM ".$this->getModel()->getTableName()."
                                WHERE contact_id = ".(int)$contact_id." AND field = '".$this->getModel()->escape($field)."'";
                        $this->getModel()->exec($sql);
                    } else {
                        $data[$field][0] = array(
                            'value' => $value,
                            'ext' => ''
                        );
                    }
                }
            } elseif ($f->isMulti()) {
                $sort = 0;
                if (!is_array($value) || isset($value['value'])) {
                    $value = array($value);
                }
                $delete_flag = false;
                foreach ($value as $value_info) {
                    if ($value_info === null) {
                        $sql = "DELETE FROM ".$this->getModel()->getTableName()."
                                WHERE contact_id = i:id AND ".
                                ($f instanceof waContactCompositeField ? "field LIKE s:field" : "field = s:field")."
                                AND sort >= i:sort";
                        $this->getModel()->exec($sql, array('id' => $contact_id,
                        'field' => $field.($f instanceof waContactCompositeField ? ':%' : ''), 'sort' => $sort));
                        continue;
                    } elseif (!is_array($value_info) && !strlen($value_info)) {
                        $sql = "DELETE FROM ".$this->getModel()->getTableName()."
                                WHERE contact_id = i:id AND field = s:field AND sort = i:sort";
                        $this->getModel()->exec($sql, array('id' => $contact_id, 'field' => $field, 'sort' => $sort));
                        continue;
                    }
                    if (is_array($value_info) && (isset($value_info['data']) || $f instanceof waContactCompositeField)) {
                        $v = isset($value_info['data']) ? $value_info['data'] : (isset($value_info['value']) ? $value_info['value'] : array());
                        $ext = isset($value_info['ext']) ? $value_info['ext'] : '';
                        foreach ($v as $subfield => $subvalue) {
                            if (!strlen($subvalue)) {
                                    $sql = "DELETE FROM ".$this->getModel()->getTableName()."
                                            WHERE contact_id = i:id AND field = s:field AND sort = i:sort";
                                    $this->getModel()->exec($sql, array('id' => $contact_id, 'field' => $field.":".$subfield, 'sort' => $sort));
                            } else {
                                $data[$field.":".$subfield][$sort] = array(
                                    'value' => $subvalue,
                                    'ext' => $ext
                                );
                            }
                        }
                    } else {
                        if (is_array($value_info)) {
                            $v = $value_info['value'];
                            $ext = isset($value_info['ext']) ? $value_info['ext'] : '';
                        } else {
                            $v = $value_info;
                            $ext = '';
                        }
                        if (!strlen($v)) {
                            $sql = "DELETE FROM ".$this->getModel()->getTableName()."
                                    WHERE contact_id = i:id AND field = s:field AND sort = i:sort";
                            $this->getModel()->exec($sql, array('id' => $contact_id, 'field' => $field, 'sort' => $sort));
                            $delete_flag = true;
                            continue;
                        }
                        $data[$field][$sort] = array(
                            'value' => $v,
                            'ext' => $ext
                        );
                    }
                    $sort++;
                }
                if ($delete_flag) {
                    $sql = "DELETE FROM ".$this->getModel()->getTableName()."
                            WHERE contact_id = i:id AND field = s:field AND sort >= i:sort";
                    $this->getModel()->exec($sql, array('id' => $contact_id, 'field' => $field, 'sort' => $sort));
                }
            }
        }

        if ($data) {

            // float with ',' convert to string with '.'

            foreach ($data as $f => &$f_rows) {
                foreach ($f_rows as $s => &$row) {
                    if (isset($row['value']) && is_float($row['value'])) {
                        $row['value'] = str_replace(',', '.', ('' . $row['value']));
                    }
                }
                unset($row);
            }
            unset($f_rows);

            // find records to update
            $rows = $this->getModel()->getByField(array(
                'contact_id' => $contact->getId(),
                'field' => array_keys($data)
            ), true);
            foreach ($rows as $row) {
                if (isset($data[$row['field']][$row['sort']])) {
                    $this->getModel()->updateById($row['id'], $data[$row['field']][$row['sort']]);
                    unset($data[$row['field']][$row['sort']]);
                }
            }
            $insert = array();
            foreach ($data as $f => $f_rows) {
                foreach ($f_rows as $s => $row) {
                    $insert[] = $contact->getId().
                        ", '".$this->getModel()->escape($f)."', '".
                        $this->getModel()->escape($row['ext'])."', '".
                        $this->getModel()->escape($row['value'])."', ".(int)$s;
                }
            }
            // insert new records
            if ($insert) {
                $sql = "INSERT INTO ".$this->getModel()->getTableName()." (contact_id, field, ext, value, sort)
                        VALUES (".implode("), (", $insert).")";
                return $this->getModel()->exec($sql);
            }
        }
        return true;
    }

    public function deleteAll($fields, $type=null) {
        if (!$fields) {
            return;
        }
        if (!is_array($fields)) {
            $fields = array($fields);
        }

        $where = array();
        foreach($fields as $id) {
            $f = waContactFields::get($id);
            if ($f instanceof waContactCompositeField) {
                $where[] = "cd.field LIKE '".$this->getModel()->escape($id).":%'";
            } else {
                $where[] = "cd.field='".$this->getModel()->escape($id)."'";
            }
        }

        switch($type) {
            case 'person':
            case 'company':
                $join = "JOIN wa_contact AS c ON c.id=cd.contact_id";
                if ($type == 'company') {
                    $cwhere = "c.is_company>0 AND ";
                } else {
                    $cwhere = "c.is_company=0 AND ";
                }
                break;
            default:
                $join = '';
                $cwhere = '';
        }

        // Hope they know what they're doing :)
        $sql = "DELETE cd FROM ".$this->getModel()->getTableName()." AS cd $join
                WHERE $cwhere(".implode(' OR ', $where).")";
        $this->getModel()->exec($sql);
    }

    /**
     * @param waContactField|string $field
     * @return bool|int|mixed
     */
    public function duplNum($field)
    {
        if ($field instanceof waContactField) {
            $field = $field->getId();
        }
        $sql = "SELECT SUM(t.num) AS dupl
                FROM (
                    SELECT value, COUNT(*) AS num
                    FROM wa_contact_data
                    WHERE field=:field
                    GROUP BY value
                    HAVING num > 1
                ) AS t";
        $r = $this->getModel()->query($sql, array('field' => $field))->fetchField();
        return $r ? $r : 0;
    }

    /**
     * @param waContactField|string $field
     * @param $values
     * @param array $excludeIds
     * @return array
     */
    public function findDuplicatesFor($field, $values, $excludeIds=array())
    {
        if ($field instanceof waContactField) {
            $field = $field->getId();
        }
        if (!$values) {
            return array();
        }

        $sql = "SELECT value, contact_id
                FROM wa_contact_data
                WHERE field=:field
                    AND value IN (:values)".
                    ($excludeIds ? " AND contact_id NOT IN (:excludeIds) " : '').
                "GROUP BY value";

        $r = $this->getModel()->query($sql, array('field' => $field, 'values' => $values, 'excludeIds' => $excludeIds));
        return $r->fetchAll('value', true);
    }
}

// EOF