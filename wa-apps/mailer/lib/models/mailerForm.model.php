<?php

/**
 * Class mailerFormModel
 */
class mailerFormModel extends waModel
{
    protected $table = 'mailer_form';

    public function save($form_id, $form)
    {
        $form_id = $this->insert(array(
            'id' => $form_id,
            'name' => $form['name'],
            'create_datetime' => date("Y-m-d H:i:s"),
            'create_contact_id' => wa()->getUser()->getId(),
            'status' => 1
        ), 1);

        return $form_id;
    }


    public function getForms()
    {
        $sql = "SELECT f.id, f.name, f.list_id, l.name list_name FROM ".$this->table." f
        		LEFT JOIN mailer_subscribe_list l ON f.list_id = l.id";
        return $this->query($sql)->fetchAll();
    }
}