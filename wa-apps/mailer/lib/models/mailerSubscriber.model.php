<?php

/**
 * Storage for individual subscribers.
 * By design, it is possible to subscribe to different lists.
 * In many places it is possible to give list_id=0, meaning 'all lists'.
 *
 * !!! Since there's currently no UI for managing lists and forms, `list_id` is barely used at all. 
 */
class mailerSubscriberModel extends waModel
{
    protected $table = 'mailer_subscriber';

    public function getByContact($contact_id)
    {
        $sql = "SELECT *
                FROM mailer_subscribe_list l
                JOIN {$this->table} s
                    ON l.id = s.list_id
                WHERE s.contact_id = i:contact_id
                ORDER BY s.datetime DESC";
        return $this->query($sql, array('contact_id' => $contact_id))->fetchAll();
    }

    /*
     * Add subscribe to table. First finds in wa_contact_emails 'id' with such $contact_id and $email
     * @param integer Contact Id
     * @param integer Subscription List Id
     * @param string Subscription Email
     */
    public function add($contact_id, $list_id, $email)
    {
        if (!is_array($list_id)) {
            $list_id = array($list_id);
        }
        $cnt = 0;
        foreach ($list_id as $id) {
            $id = $this->query("INSERT IGNORE
                              INTO
                                  mailer_subscriber (contact_id, contact_email_id, list_id, datetime)
                              SELECT
                                  ce.contact_id,
                                  ce.id,
                                  i:list_id,
                                  s:dtm
                              FROM wa_contact_emails ce
                              WHERE
                                  ce.contact_id = i:contact_id AND
                                  ce.email = s:email
                              ORDER BY ce.contact_id ASC LIMIT 1",
                    array(
                        'list_id'    => $id,
                        'dtm'        => date('Y-m-d H:i:s'),
                        'contact_id' => $contact_id,
                        'email'      => $email,
                    ));

            $cnt += $id->affectedRows();
        }
        return $cnt;
    }

    /*
     * Delete from table in reason of unsubscribing from list
     * @param integer $contact_id Subscriber Contact Id
     * @param integer $list_id Subscription List Id, if false - delete from entire table
     * @return boolean
     */
    public function deleteForUnsubscribe($contact_id, $from_list = false)
    {
        $params = array('contact_id' => $contact_id);
        if ($from_list) {
            $params['list_id'] = $from_list;
        }

        return $this->deleteByField($params);
    }

    public function countListView($search, $list_id = 0)
    {
        $where_sql = $where_list_id = '';
        $join_sql = '';
        $list_id = (int) $list_id;
        if ($search) {
            $where_sql = " AND CONCAT(c.name, ' ', ce.email) LIKE '%".$this->escape($search, 'like')."%'";
            $join_sql = " JOIN wa_contact AS c ON s.contact_id=c.id
                        JOIN wa_contact_emails AS ce ON s.contact_email_id=ce.id";
        }
        if ($list_id) {
            $where_list_id = " WHERE s.list_id = $list_id";
        }

        $sql = "SELECT COUNT(DISTINCT s.contact_id, s.contact_email_id)
                FROM {$this->table} AS s
                {$join_sql}
                {$where_list_id}
                {$where_sql}";
        return $this->query($sql)->fetchField();
    }

    public function getListView($search, $start, $limit, $order, $list_id = 0)
    {
        // Search condition
        $list_id = (int) $list_id;
        if ($search) {
            $where_sql = "WHERE CONCAT(c.name, ' ', ce.email) LIKE '%".$this->escape($search, 'like')."%'".($list_id > 0 ? " AND list_id = $list_id" : "");
            $contact_join = 'JOIN';
        } else {
            $where_sql = $list_id > 0 ? "WHERE list_id = $list_id" : '';
            $contact_join = 'LEFT JOIN';
        }

        // Limit
        $limit_sql = '';
        if ($limit) {
            $limit = (int) $limit;
            if ($start) {
                $limit_sql = "LIMIT {$start}, {$limit}";
            } else {
                $limit_sql = "LIMIT {$limit}";
            }
        }

        // Order
        $order_sql = '';
        if ($order) {
            $possible_order = array(
                'name' => 'c.name',
                'email' => 'ce.email',
                'datetime' => 's.datetime',
                '!name' => 'c.name DESC',
                '!email' => 'ce.email DESC',
                '!datetime' => 's.datetime DESC',
            );
            if (!$order || empty($possible_order[$order])) {
                $order = key($possible_order);
            }
            $order_sql = "ORDER BY ".$possible_order[$order];
        }

        $sql = "SELECT
                  s.list_id as 'list_id',
                  s.contact_email_id as 'contact_email_id',
                  MAX(s.datetime) as 'datetime',
                  c.name as 'name',
                  ce.contact_id 'contact_id',
                  ce.email as 'email'
                FROM {$this->table} AS s
                {$contact_join} wa_contact_emails AS ce
                  ON s.contact_email_id = ce.id
                {$contact_join} wa_contact AS c
                  ON c.id = ce.contact_id
                {$where_sql}
                GROUP BY s.contact_id, s.contact_email_id
                {$order_sql}
                {$limit_sql}";
        return $this->query($sql)->fetchAll();
    }

    /**
     * @desc Search or create contact by email (and name if given)
     * @param $subscriber array() Array with 'email', maybe 'name' for searchin contact
     * @return bool|int|mixed|null
     * @throws waException
     */
    protected function getContact($subscriber)
    {
        // Get contact_id by email
        $cem = new waContactEmailsModel();
        $contact_id = null;
        $contact_id = $cem->getContactIdByNameEmail($subscriber['name'], $subscriber['email'], false);

        if (!$contact_id) {
            $contact = new waContact();
            $contact['create_method'] = 'subscriber';
            $data['create_ip'] = waRequest::getIp();
            $data['create_user_agent'] = waRequest::getUserAgent();
            $contact['create_contact_id'] = 0;
            if ($contact->save($subscriber)) {
                throw new waException('Unable to create contact.', 500);
            }
            $contact_id = $contact->getId();
        }
        return $contact_id;
    }

    /**
     * @decs Adds subscriber from subscription form or by email
     * @param integer $form_id - ID of form, which is used form subscribe
     * @param array $subscriber - email, name and other fields maybe
     * @param array $subscriber_lists - subscriptions list
     * @return bool|int|mixed|null
     */
    public function addSubscriber($form_id, $subscriber, $subscriber_lists)
    {
        $contact_id = $this->getContact($subscriber);

        $mfl = new mailerFormSubscribeListsModel();
        $lists = $mfl->getListsIds($form_id);

        $mfp = new mailerFormParamsModel();
        $params = $mfp->get($form_id);

        // if form have no checked lists in form settings - subscribe to All Subscribers (id = 0)
        $lists = !empty($lists) ? $lists : array(0);
        // if form has no option to choose lists by user - subscribe to all checked lists in form settings
        $subscriber_lists = isset($params['show_subscription_list']) ? $subscriber_lists : $lists;
        // if no lists checked by user - add to All Subscribers (id = 0)
//        $subscriber_lists = isset($subscriber_lists) ? $subscriber_lists : array(0);

        // Remove contact from unsubscribers
        $mu = new mailerUnsubscriberModel();
        $mu->deleteByField('email', $subscriber['email']);

        foreach($subscriber_lists as $value){
            // check if we add to All Subscribers OR given list ids are in forms list
            if ($value == 0 || in_array($value, $lists)) {
                $this->add($contact_id, $value, $subscriber['email']);
            }
        }
        return $contact_id;
    }
}

