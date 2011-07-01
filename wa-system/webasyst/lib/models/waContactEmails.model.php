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
        $sql = "SELECT email value, ext FROM ".$this->table." WHERE contact_id = i:id ORDER BY sort";
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
    	$sql = "SELECT contact_id FROM ".$this->table." WHERE email = s:email ORDER BY sort LIMIT 1";
    	return $this->query($sql, array('email' => $email))->fetchField();
    }
}

