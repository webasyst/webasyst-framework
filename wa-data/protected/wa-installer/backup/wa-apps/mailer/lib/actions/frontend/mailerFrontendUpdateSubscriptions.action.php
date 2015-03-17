<?php

class mailerFrontendUpdateSubscriptionsAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign('just_updated', 1);

        $contact = wa()->getUser();

        $ms = new mailerSubscriberModel();
        $msl = new mailerSubscribeListModel();
        $mu = new mailerUnsubscriberModel();

        // get user default email
        $default_email = $contact->get('email', 'default');
        $ce = new waContactEmailsModel();
        $user_emails = $ce->getEmails(wa()->getUser()->getId());

        $my_subscription_ids = array();
        foreach($ms->getByContact($contact['id']) as $subscripion) {
            $my_subscription_ids[] = $subscripion['id'];
        }

        $this->view->assign('my_nav_selected', 'subscriptions');
        $this->view->assign('all_subscriptions', $msl->getAllListsList());
        $this->view->assign('my_subscriptions', $my_subscription_ids);

        $this->setThemeTemplate('my.subscriptions.html');
        $this->getResponse()->setTitle(_w('My subscriptions'));

        $this->setLayout(new mailerFrontendLayout());
    }
}

