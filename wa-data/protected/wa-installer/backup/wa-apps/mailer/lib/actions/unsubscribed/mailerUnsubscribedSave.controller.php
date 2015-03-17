<?php

/**
 * Removes unsubscribed status from contact email.
 */
class mailerUnsubscribedSaveController extends waJsonController
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }

        $email = trim(waRequest::request('email'));
        $list_id = waRequest::request('list_id', 0, 'int');

        if ($email && $list_id !== null) {
            $um = new mailerUnsubscriberModel();
            if ($list_id === 0) {
                $um->deleteByField('email', $email);
            }
            $um->insert(array(
                'email' => $email,
                'list_id' => $list_id,
                'message_id' => null,
                'datetime' => date('Y-m-d H:i:s'),
            ), 2); // INSERT IGNORE
        }
    }
}

