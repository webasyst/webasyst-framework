<?php

class waContactDataModel extends waModel
{
    protected $table = "wa_contact_data";

    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_UNCONFIRMED = 'unconfirmed';
    const STATUS_UNAVAILABLE = 'unavailable';
    const STATUS_UNKNOWN = NULL;


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
                        'ext'  => $row['ext'],
                        'status' => $row['status']
                    );
                } else {
                    $result[$row['contact_id']][$f][$row['sort']]['data'][$subfield] = $row['value'];
                }
            } else {
                $result[$row['contact_id']][$field][$row['sort']] = array(
                    'value' => $row['value'],
                    'ext'   => $row['ext'],
                    'status' => $row['status']
                );
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

    /**
     * Get first contact with password by this phone
     * @param string $phone
     * @param int|int[]|null $exclude_ids contact ids that excluded from searching
     * @return null|int
     */
    public function getContactWithPasswordByPhone($phone, $exclude_ids = array())
    {
        $phone = is_scalar($phone) ? $phone : '';
        if (strlen($phone) <= 0) {
            return null;
        }

        $phone = waContactPhoneField::cleanPhoneNumber($phone);
        if (strlen($phone) <= 0) {
            return null;
        }

        $validator = new waPhoneNumberValidator();
        if (!$validator->isValid($phone)) {
            return null;
        }

        $exclude_ids = waUtils::toIntArray($exclude_ids);
        $exclude_ids = waUtils::dropNotPositive($exclude_ids);

        $where = array(
            "d.field = 'phone'",
            "d.value = :phone",
            "d.sort = 0",
            "c.password != ''"
        );

        if ($exclude_ids) {
            $where[] = "c.id NOT IN (:ids)";
        }

        $where = join(" AND ", $where);

        $sql = "SELECT c.id 
                  FROM `{$this->table}` d 
                  JOIN wa_contact c ON d.contact_id = c.id
                WHERE {$where}
                LIMIT 1";

        return $this->query($sql, array('phone' => $phone, 'ids' => $exclude_ids))->fetchField();
    }

    public function getByContact($id)
    {
        $sql = "SELECT * FROM ".$this->table."
                WHERE contact_id = i:id
                ORDER BY field, sort";
        $data = $this->query($sql, array('id' => $id));
        $result = array();
        foreach ($data as $row) {
            if (strpos($row['field'], ':') !== false) {
                $field = explode(':', $row['field'], 2);
                $result[$field[0]][$row['ext']][$row['sort']][$field[1]] = array(
                    'id'    => $row['id'],
                    'value' => $row['value']
                );
            } else {
                if (!isset($result[$row['field']][$row['ext']][$row['sort']])) {
                    $result[$row['field']][$row['ext']][$row['sort']] = array(
                        'id'    => $row['id'],
                        'value' => $row['value']
                    );
                } else {
                    $result[$row['field']][$row['ext']][] = array(
                        'id'    => $row['id'],
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
            'field'      => $field,
            'value'      => $value,
            'sort'       => $sort
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
        return $this->query($sql, array('field' => $field, 'limit' => $limit))->fetchAll('value', true);
    }

    public function getContactIdByPhone($phone)
    {
        $phone = waContactPhoneField::cleanPhoneNumber($phone);
        if (strlen($phone) <= 0) {
            return false;
        }
        return $this->getContactIdByFieldValue('phone', $phone);
    }

    public function getPhones($contact_id)
    {
        $sql = "SELECT `values`, ext, status FROM ".$this->table." WHERE contact_id = i:id AND `field` = 'phone' ORDER BY sort";
        return $this->query($sql, array('id' => $contact_id))->fetchAll();
    }

    public function getPhone($contact_id, $sort = 0)
    {
        return $this->getByField(array('contact_id' => $contact_id, 'field' => 'phone', 'sort' => $sort));
    }

    /**
     * Update phone status for this contact
     *
     * @param int $contact_id
     * @param string $phone
     * @param string $status self::STATUS_*
     *
     * @return bool
     *   If phone for this contact doesn't exists return FALSE
     *   Otherwise return TRUE
     *
     * @throws waException
     */
    public function updateContactPhoneStatus($contact_id, $phone, $status)
    {
        $phone = waContactPhoneField::cleanPhoneNumber($phone);
        
        $row = $this->getByField(array(
            'contact_id' => $contact_id,
            'field'      => 'phone',
            'value'      => $phone
        ));
        if (!$row) {
            return false;
        }
        if ($row['status'] !== $status) {
            $this->updateById($row['id'], array(
                'status' => $status
            ));
        }
        return true;
    }

    protected function getContactIdByFieldValue($field_id, $value)
    {
        $sql = "SELECT contact_id FROM ".$this->table."
                WHERE
                    `field` = :field_id AND
                    `value` LIKE ('".$this->escape($value, 'like')."')
                ORDER BY contact_id, sort LIMIT 1";
        return $this->query($sql, array('field_id' => $field_id))->fetchField();
    }
}

