<?php

/**
 * Dialog showing info about single unsubscriber.
 */
class mailerUnsubscribedInfoAction extends waViewAction
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }

        $email = trim(waRequest::post('email'));
        $list_id = waRequest::post('list_id', null, 'int');

        // Unsibscriber data
        $um = new mailerUnsubscriberModel();
        $data = $um->getByField(array(
            'email' => $email,
            'list_id' => $list_id,
        ));
        if (!$data) {
            die('<h1>'._w('Unsubscriber does not exist.').'</h1>');
        }

        $data['datetime_formatted'] = mailerCampaignsArchiveAction::formatListDate($data['datetime']);

        // Linked contacts by email
        $data['linked_contacts'] = array();
        $sql = "SELECT c.id, c.name
                FROM wa_contact_emails AS e
                    JOIN wa_contact AS c
                        ON c.id=e.contact_id
                WHERE e.email=:email";
        foreach ($um->query($sql, array('email' => $email)) as $row) {
            $data['linked_contacts'][$row['id']] = $row['name'];
        }

        // Linked campaign
        if ($data['message_id']) {
            $mm = new mailerMessageModel();
            $campaign = $mm->getById($data['message_id']);
            if ($campaign) {
                $data['campaign_name'] = $campaign['subject'];
            } else {
                $data['message_id'] = null;
            }
        }

        $this->view->assign('data', $data);
    }
}

