<?php

class mailerFrontendMySubscriptionsAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign('just_confirmed', waRequest::get('just-confirmed', 0));

        $contact = wa()->getUser();

        $ms = new mailerSubscriberModel();
        $msl = new mailerSubscribeListModel();
        $mu = new mailerUnsubscriberModel();
        $ce = new waContactEmailsModel();

        $just_updated = $unsubscribed_date = $just_subscribed_again = $just_unsubscribed = $log_id = false;
        $contact_subscriptions_ids = $contact_subscriptions = $all_subscriptions = array();

        // get user default email
        $default_email = $contact->get('email', 'default');
        $user_emails = array();
        foreach($ce->getEmails($contact->getId()) as $email) {
            $user_emails[] = $email['value'];
        }

        $unsubscriber = $mu->getByField('email', $user_emails, true);
        // user is unsubscribed from all if only all his emails are in unsubscride table
        if ($unsubscriber && count($unsubscriber) == count($user_emails)) {
            $unsubscribed_date = $unsubscriber[0]['datetime'];
            $unsubscriber = true;
        }

        $all_subscriptions = $msl->getAllListsList();

        if (!$unsubscriber) {
            foreach ($ms->getByContact($contact['id']) as $subscripion) {
                $contact_subscriptions_ids[] = $subscripion['id'];
            }
        }

        $update_subscriptions = waRequest::post('update_subscriptions');
        $unsubscribe_from_all = waRequest::post('unsubscribe_from_all');
        $subscribe_again = waRequest::post('subscribe_again');
        $lists = waRequest::post('list');

        // unsubscribing by link in email or button
        if (!$unsubscriber && $unsubscribe_from_all) {
            // if contact is not in Unsubscribers already we will unsubscribe him
            $ms->deleteForUnsubscribe($contact->getId(), $contact_subscriptions);
            $unsubscribed_date = date('Y-m-d H:i:s');
            $mu->multipleInsert(array(
                'email' => $user_emails,
                'list_id' => 0,
                'message_id' => 0,
                'datetime' => $unsubscribed_date
            ), true);

            $unsubscriber = $just_unsubscribed = true;

            $this->logAction('unsubscribed_from_all_mailings');
        }

        if ($update_subscriptions) {
            if ($lists) {
                // if contact is in Unsubscribers and we have subscriptions to subscribe
                // first, delete from unsubscribers table
                $mu->deleteByField('email', $user_emails);

                // second, get id array for new and old subscriptions
                $lists_nothing_to_do = array_intersect($lists, $contact_subscriptions);
                $lists_to_unsubscribe = array_diff($contact_subscriptions, $lists_nothing_to_do);
                $lists_to_subscribe = array_diff($lists, $lists_nothing_to_do);

                // delete old subscriptions
                $ms->deleteForUnsubscribe($contact->getId(), $lists_to_unsubscribe);
                // add new subscriptions
                $ms->add($contact->getId(), $lists_to_subscribe, $default_email);

                $contact_subscriptions_ids = $lists_nothing_to_do + $lists_to_subscribe;

                $this->logAction('edited_subscription');
            }
            else {
                // if contact is in Unsubscribers and noting selected - delete all subscriptions
                $ms->deleteForUnsubscribe($contact->getId(), $contact_subscriptions_ids);
                $contact_subscriptions_ids = array();
            }

            $unsubscriber = $just_unsubscribed = false;
            $just_updated = true;
        }

        if ($subscribe_again) {
            // if contact is in Unsubscribers already we will subscibr him again
            $mu->deleteByField('email', $user_emails);

            $ms->add($contact->getId(), 0, $default_email); // Subscribe to all lists

            $unsubscriber = $just_unsubscribed = false;
            $just_subscribed_again = true;

            $this->logAction('subscribed_from_customer_portal');
        }

        foreach ($all_subscriptions as $subscription) {
            $contact_subscriptions[] = array(
                'subscription_info' => $subscription,
                'subscribed' => in_array($subscription['list_id'], $contact_subscriptions_ids)
            );
        }

        $this->view->assign('subscriptions', $contact_subscriptions);
        $this->view->assign('unsubscriber', $unsubscriber);
        $this->view->assign('unsubscribed_date', $unsubscribed_date);
        $this->view->assign('just_updated', $just_updated);
        $this->view->assign('just_unsubscribed', $just_unsubscribed);
        $this->view->assign('just_subscribed_again', $just_subscribed_again);

        $this->view->assign('my_nav_selected', 'subscriptions');
        $this->setThemeTemplate('my.subscriptions.html');
        $this->getResponse()->setTitle(_w('My subscriptions'));

        $this->setLayout(new mailerFrontendLayout());
    }
}