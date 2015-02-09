<?php

/**
 * Removes contact from subscription.
 */
class mailerSubscribersDeleteController extends waJsonController
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }
        $contact_email_id = waRequest::post('contact_email_id');
        $list_id = waRequest::post('list_id', 0 ,'int');
        $um = new mailerSubscriberModel();

        if ($list_id) { // delete only from given subscription
            $um->deleteByField(array(
                'contact_email_id' => $contact_email_id,
                'list_id' => $list_id
            ));
            // if this was last subscription list - will add to All Subscribers
            if (!$um->getByField('contact_email_id', $contact_email_id, true)) {
                $em = new waContactEmailsModel();
                $email = $em->getById($contact_email_id);
                $um->insert(array(
                        'contact_id' => $email['contact_id'],
                        'contact_email_id' => $contact_email_id,
                        'list_id' => 0,
                        'datetime' => date("Y-m-d H:i:s")
                    ), 2);

            }
        }
        else { // delete from all subscriptions
            $um->deleteByField(array(
                'contact_email_id' => $contact_email_id
            ));
        }

    }
}

