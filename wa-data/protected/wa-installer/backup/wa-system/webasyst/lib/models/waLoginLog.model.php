<?php

class waLoginLogModel extends waModel
{
    protected $table = 'wa_login_log';
    protected $id = 'id';

    public function getLast($contact_id)
    {
        $sql = "SELECT * FROM ".$this->table."
                WHERE contact_id = i:contact_id
                ORDER BY id DESC
                LIMIT 1";
        return $this->query($sql, array('contact_id' => $contact_id))->fetch();
    }

    public function getCurrent($contact_id)
    {
        $sql = "SELECT * FROM ".$this->table."
                WHERE contact_id = i:contact_id AND datetime_out IS NULL
                LIMIT 1";
        return $this->query($sql, array('contact_id' => $contact_id))->fetch();
    }

    public function getLastIn($contact_ids)
    {
        $sql = "SELECT contact_id, datetime_in FROM ".$this->table."
                WHERE datetime_out IS NULL AND
                      contact_id IN ('".implode("','", $contact_ids)."')";
        return $this->query($sql)->fetchAll('contact_id', true);
    }



}