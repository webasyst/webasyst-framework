<?php

class waContactDataModel extends waModel
{
    protected $table = "wa_contact_data";


    /**
     * @param array|int $ids
     * @param array $fields
     * @return array
     */
    public function getData($ids, $fields = null)
    {
        $sql = "SELECT * FROM ".$this->table."
                WHERE contact_id";
            if (is_array($ids)) {
                $sql .= " IN ('".implode("', '", $this->escape($ids))."')";
            } else {
                $sql .= " = ".(int)$ids;
            }
        if ($fields) {
            $sql .= " AND field IN ('".implode("', '", $this->escape($fields))."')";
        }
        $sql .= " ORDER BY sort";

        $result = array();
        foreach ($this->query($sql) as $row) {
            $field = $row['field'];

            if (strpos($row['field'], ':') !== false) {
                $field_parts = explode(':', $row['field'], 2);
                $f = $field_parts[0];
                $subfield = $field_parts[1];
            } else {
                $f = $field;
                $subfield = '';
            }
            if ($subfield) {
                if (!isset($result[$row['contact_id']][$f][$row['sort']])) {
                    $result[$row['contact_id']][$f][$row['sort']] = array(
                        'data' => array(
                            $subfield => $row['value']
                        ),
                        'ext' => $row['ext']
                    );
                } else {
                    $result[$row['contact_id']][$f][$row['sort']]['data'][$subfield] = $row['value'];
                }
            } else {
                $result[$row['contact_id']][$field][$row['sort']] = array('value' => $row['value'], 'ext' => $row['ext']);
            }
        }
        if (is_array($ids)) {
            return $result;
        } else {
            if (isset($result[$ids])) {
                return $result[$ids];
            } else {
                return array();
            }
        }
    }

    public function getByContact($id)
    {
        $sql = "SELECT * FROM ".$this->table."
                WHERE contact_id = i:id
                ORDER BY field, sort";
        $data =  $this->query($sql, array('id' => $id));
        $result = array();
        foreach ($data as $row) {
            if (strpos($row['field'], ':') !== false) {
                $field = explode(':', $row['field'], 2);
                $result[$field[0]][$row['ext']][$row['sort']][$field[1]] = array(
                    'id' => $row['id'],
                    'value' => $row['value']
                );
            } else {
                if (!isset($result[$row['field']][$row['ext']][$row['sort']])) {
                    $result[$row['field']][$row['ext']][$row['sort']] = array(
                        'id' => $row['id'],
                        'value' => $row['value']
                    );
                } else {
                    $result[$row['field']][$row['ext']][] = array(
                        'id' => $row['id'],
                        'value' => $row['value']
                    );
                }
            }
        }
        return $result;
    }


    /**
     * Delete field by id and contact id
     *
     * @param int $id
     * @param int $contact_id
     * @return boolean
     */
    public function deleteField($id, $contact_id)
    {
        $row = $this->getById($id);
        if ($row) {
            // Delete row from table wa_contact_data
            $sql = "DELETE FROM ".$this->table." WHERE id = i:id AND contact_id = i:contact_id";
            if ($this->exec($sql, array('id' => $id, 'contact_id' => $contact_id))) {
                // Calc new sort for other fields of the same type that the deleted field
                $sql = "UPDATE ".$this->table." SET sort = sort - 1 WHERE contact_id = i:contact_id AND field = s:field AND sort > i:sort";
                $this->exec($sql, array('field' => $row['field'], 'contact_id' => $contact_id, 'sort' => $row['sort']));
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    public function addField($contact_id, $field, $value, $ext = null)
    {
        $sort = $this->getSort($contact_id, $field);
        $data = array(
            'contact_id' => $contact_id,
            'field' => $field,
            'value' => $value,
            'sort' => $sort
        );
        if ($ext) {
            $data['ext'] = $ext;
        }
        return $this->insert($data);
    }

    public function getSort($contact_id, $field)
    {
        $sql = "SELECT MAX(sort) FROM ".$this->table." WHERE contact_id = i:contact_id AND field = s:field";
        return (int)$this->query($sql, array('contact_id' => $contact_id, 'field' => $field))->fetchField();
    }

    public function getTopValues($field, $limit = 10)
    {
        $sql = "SELECT value, count(DISTINCT contact_id) n FROM ".$this->table."
                WHERE field = s:field
                GROUP BY value
                ORDER BY n DESC
                LIMIT i:limit";
        return $this->query($sql, array('field' => $field, 'limit'=> $limit))->fetchAll('value', true);
    }
}

