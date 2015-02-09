<?php

/**
 * Class mailerFrontendConfirmAction
 * @desc Confirms subscription
 */
class mailerFrontendConfirmAction extends waViewAction
{
    public function execute()
    {
        $hash = waRequest::param('hash');
        if (!$hash) {
            throw new waException("Page not found", 404);
        }

        $mst = new mailerSubscriberTempModel();
        $data = $mst->getByHash($hash);
        $contact_id = null;

        if (!$data) {
            $this->redirect(wa()->getRouteUrl('mailer/frontend/mySubscriptions/').'?just-confirmed=0');
        }
        $data = unserialize($data['data']);
        $mst->deleteByHash($hash);

        $ms = new mailerSubscriberModel();
        $contact_id = $ms->addSubscriber($data['form'], $data['subscriber'], $data['lists']);

        wa()->getAuth()->auth(array('id'=>$contact_id));

        $this->logAction('subscribed_via_form');

        $this->redirect(wa()->getRouteUrl('mailer/frontend/mySubscriptions/').'?just-confirmed=1');

//        $this->view->assign('contact_id', $contact_id);
//        $this->view->assign('contact_name', $new_contact->getName());
    }
}