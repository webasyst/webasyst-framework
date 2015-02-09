<?php

/**
 * Removes unsubscribed status from contact email.
 */
class mailerUnsubscribedDeleteController extends waJsonController
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }

        $email = trim(waRequest::post('email'));
        $list_id = waRequest::post('list_id', null, 'int');

        if ($email && $list_id !== null) {
            $um = new mailerUnsubscriberModel();
            $um->deleteByField(array(
                'email' => $email,
                'list_id' => $list_id,
            ));
        }
    }
}

