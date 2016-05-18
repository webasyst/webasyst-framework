<?php

class waUserModel extends waModel
{
    protected $table = "wa_contact";
    protected $id = 'id';

    public function getByLogin($login)
    {
        if (!$login) {
            return null;
        }
        return $this->getByField('login', $login);
    }

    public function getAllUsers()
    {
        $sql = "SELECT * FROM ".$this->table."
                WHERE is_user = 1
                ORDER BY name";
        return $this->getBySQL($sql);
    }

    public function getBySQL($sql)
    {
        $users = $this->query($sql)->fetchAll('id');
        if ($users) {
            $ids = array_keys($users);
            $sql = "SELECT contact_id, email
                FROM wa_contact_emails
                WHERE contact_id IN ('".implode("','", $ids)."')";
            $data = $this->query($sql);
            foreach ($data as $row) {
                if (!isset($users[$row['contact_id']]['email'])) {
                    $users[$row['contact_id']]['email'] = $row['email'];
                } else {
                    $users[$row['contact_id']]['email'] .= ', '.$row['email'];
                }
            }
        }
        return $users;
    }

    public function getLastUsers($timeout)
    {
        $sql = "SELECT * FROM ".$this->table."
                WHERE is_user = 1 AND
                      last_datetime >= NOW() - INTERVAL ".(int)$timeout." SECOND";
        return $this->getBySQL($sql);
    }
}

