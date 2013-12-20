<?php

class waContactEmailsModel extends waModel
{
    protected $table = "wa_contact_emails";

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
                ORDER BY sort LIMIT 1";
        return $this->query($sql)->fetchField();
    }

    public function getContactIdsByEmails($emails)
    {
        if (!$emails || !is_array($emails)) {
            return array();
        }
        $sql = "SELECT email, contact_id FROM ".$this->table." WHERE email IN (:emails)";
        return $this->query($sql, array('emails' => $emails))->fetchAll('email', true);
    }

    public function getContactIdByNameEmail($name, $email)
    {
        $sql = "SELECT c.id FROM ".$this->table." e
                JOIN wa_contact c ON e.contact_id = c.id
                WHERE e.email = s:email AND e.sort = 0 AND c.name = s:name";
        return  $this->query($sql, array('email' => $email, 'name' => $name))->fetchField();
    }

    public function getContactWithPassword($email)
    {
        $sql = "SELECT c.id FROM ".$this->table." e JOIN wa_contact c ON e.contact_id = c.id
                WHERE e.email LIKE '".$this->escape($email, 'like')."' AND e.sort = 0 AND c.password != ''
                LIMIT 1";
        return $this->query($sql)->fetchField();
    }
}

