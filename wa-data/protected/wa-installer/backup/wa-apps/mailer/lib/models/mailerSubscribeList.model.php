<?php

/**
 * Storage for subscribe lists.
 */
class mailerSubscribeListModel extends waModel
{
    protected $table = "mailer_subscribe_list";

    /**
     * Gets lists list with subscribers count for each list
     * @return array
     */
    public function getAllListsList()
    {
        return $this->query("SELECT
                                msl.id 'list_id',
                                msl.name 'list_name',
                                COUNT(DISTINCT ms.contact_id, ms.contact_email_id) 'subscribers'
                            FROM {$this->table} msl
                            LEFT OUTER JOIN mailer_subscriber ms
                                ON ms.list_id = msl.id
                            GROUP BY msl.id")->fetchAll();
    }

    public function getListById($id)
    {
        return $this->getById($id);
    }

    public function save($id, $data)
    {
        $list_id = $this->insert(array(
            'id' => $id <= 0 ? null : $id,
            'name' => $data['name'],
            'create_datetime' => date("Y-m-d H:i:s"),
            'create_contact_id' => wa()->getUser()->getId(),
            'description' => ''
        ), 1);

        return $list_id;
    }
}