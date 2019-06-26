<?php

class waContactEmailsModel extends waModel
{
    protected $table = "wa_contact_emails";

    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_UNCONFIRMED = 'unconfirmed';
    const STATUS_UNAVAILABLE = 'unavailable';
    const STATUS_UNKNOWN = 'unknown';

    public function getData($ids, $fields = null)
    {
        $sql = "SELECT * FROM ".$this->table."
                WHERE contact_id".(is_array($ids) ? " IN (i:ids)" : " = i:ids")."
                ORDER BY sort";
        $result = array();
        $i = 0;
        foreach ($this->query($sql, array('ids' => $ids)) as $row) {
            if (!isset($result[$row['contact_id']]['email'])) {
                $result[$row['contact_id']]['email'] = array();
            }
            if ( isset($result[$row['contact_id']]['email'][$row['sort']]) ||
                 $row['sort'] > count($result[$row['contact_id']]['email'])
            ) {
                $i++;
                $row['sort'] = count($result[$row['contact_id']]['email']);
                $this->exec("UPDATE ".$this->table." SET sort = i:sort WHERE id = i:id",
                            array('id' => $row['id'], 'sort' => $row['sort']));
            }
            $result[$row['contact_id']]['email'][$row['sort']] = $row['email'];
        }

        if (is_array($ids)) {
            return $result;
        } else {
            return isset($result[$ids]) ? $result[$ids] : array();
        }
    }

    public function getEmails($contact_id)
    {
        $sql = "SELECT email AS value, ext, status FROM ".$this->table." WHERE contact_id = i:id ORDER BY sort";
        return $this->query($sql, array('id' => $contact_id))->fetchAll();
    }

    public function getEmail($contact_id, $sort = 0)
    {
        return $this->getByField(array('contact_id' => $contact_id, 'sort' => $sort));
    }

    /**
     * Update email status for this contact
     *
     * @param int $contact_id
     * @param string $email
     * @param string $status self::STATUS_* const
     *
     * @return bool
     *   If email for this contact doesn't exists return FALSE
     *   Otherwise return TRUE
     *
     * @throws waException
     */
    public function updateContactEmailStatus($contact_id, $email, $status)
    {
        $row = $this->getByField(array(
            'contact_id' => $contact_id,
            'email' => $email
        ));
        if (!$row) {
            return false;
        }
        if ($row['status'] !== $status) {
            $this->updateById($row['id'], array('status' => $status));
        }
        return true;
    }

    public function delete($email_id)
    {
        $row = $this->getById($email_id);
        if ($row) {
            $this->deleteById($email_id);
            $sql = "UPDATE ".$this->table." SET sort = sort - 1
                    WHERE contact_id = ".(int)$row['contact_id']." AND sort > ".(int)$row['sort'];
            $this->exec($sql);
        }
    }

    public function getContactIdByEmail($email)
    {
        $sql = "SELECT contact_id FROM ".$this->table."
                WHERE email LIKE ('".$this->escape($email, 'like')."')
                ORDER BY contact_id,sort LIMIT 1";
        return $this->query($sql)->fetchField();
    }

    /**
     * Get first contact with password by this email
     * @param string $email Must be valid email
     * @param int|int[]|null $exclude_ids contact ids that excluded from searching
     * @return null|int
     */
    public function getContactWithPassword($email, $exclude_ids = array())
    {
        $email = is_scalar($email) ? (string)$email : '';
        if (strlen($email) <= 0) {
            return null;
        }

        $validator = new waEmailValidator(array('required'=>true));
        if (!$validator->isValid($email)) {
            return null;
        }

        $exclude_ids = waUtils::toIntArray($exclude_ids);
        $exclude_ids = waUtils::dropNotPositive($exclude_ids);

        $where = array(
            "e.email LIKE s:email",
            "e.sort = 0",
            "c.password != ''"
        );

        if ($exclude_ids) {
            $where[] = "c.id NOT IN (:ids)";
        }

        $where = join(" AND ", $where);

        $sql = "SELECT c.id FROM ".$this->table." e JOIN wa_contact c ON e.contact_id = c.id
                WHERE {$where}
                LIMIT 1";
        return $this->query($sql, array('email' => $email, 'ids' => $exclude_ids))->fetchField();
    }

    public function getContactIdsByEmails($emails)
    {
        if (!$emails || !is_array($emails)) {
            return array();
        }
        $sql = "SELECT email, contact_id FROM ".$this->table." WHERE email IN (:emails)";
        return $this->query($sql, array('emails' => $emails))->fetchAll('email', true);
    }

    public function getContactIdByNameEmail($name, $email, $strong = true)
    {
        $sql = "SELECT c.id FROM ".$this->table." e
                JOIN wa_contact c ON e.contact_id = c.id
                WHERE e.email = s:email AND e.sort = 0 AND c.name = s:name";
        $contact_id = $this->query($sql, array('email' => $email, 'name' => $name))->fetchField();
        if (!$strong && !$contact_id) {
            $contact_id = $this->getContactIdByEmail($email);
        }
        return  $contact_id;
    }

    public function getMainContactMyEmail($email)
    {
        // find oldest contact or with password
        $sql = "SELECT c.id FROM ".$this->table." e JOIN wa_contact c ON e.contact_id = c.id
                WHERE e.email LIKE '".$this->escape($email, 'like')."' AND e.sort = 0
                ORDER BY c.password DESC, c.id
                LIMIT 1";
        return $this->query($sql)->fetchField();
    }
}

