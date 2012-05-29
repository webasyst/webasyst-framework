<?php

class blogEmailsubscriptionModel extends waModel
{
    protected $table = "blog_emailsubscription";

    public function getSubscribers($blog_id)
    {
        $sql = "SELECT c.id, c.name, c.photo FROM ".$this->table." s JOIN wa_contact c ON s.contact_id = c.id
                WHERE s.blog_id = i:blog_id";
        return $this->query($sql, array('blog_id' => $blog_id))->fetchAll();
    }
}