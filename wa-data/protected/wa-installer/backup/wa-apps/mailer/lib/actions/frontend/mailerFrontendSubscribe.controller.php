<?php

class mailerFrontendSubscribeController extends waJsonController
{
    public function execute()
    {
        $subscriber = waRequest::post('subscriber');
        if (!$subscriber) { // else subscribe by passed email and name
            $subscriber['email'] = waRequest::request('email');
            $subscriber['name'] = waRequest::request('name', '');
        }

        $lists = waRequest::post('lists');

        if ( waRequest::post('captcha') && !wa()->getCaptcha()->isValid()) {
            $this->errors[_w('Incorrect data')][] ='captcha' ;
        }

        $form_id = waRequest::post('form_id');
        $form_model = new mailerFormModel();

        if ($form_id && $form = $form_model->getById($form_id)) {
            $mfp = new mailerFormParamsModel();
            $form['params'] = $mfp->get($form_id);

            if (!$lists && isset($form['params']['show_subscription_list'])) {
                $this->errors[_w('Incorrect data')][] = 'lists[]';
            }
        }

//        $locale = waRequest::post('locale');
        $charset = waRequest::post('charset');
//        if (!$locale || !waLocale::getInfo($locale)) {
//            $locale = wa()->getLocale();
//        }

        // Convert name and email to UTF-8
        if ($charset && $charset != 'utf8') {
            if ( ( $t = @iconv($charset, 'utf8//IGNORE', $subscriber['name']))) {
                $subscriber['name'] = $t;
            }
            if ( ( $t = @iconv($charset, 'utf8//IGNORE', $subscriber['email']))) {
                $subscriber['email'] = $t;
            }
        }

        // Validate email
        $subscriber['email'] = trim($subscriber['email']);
        $ev = new waEmailValidator();

        if (!$subscriber['email']) {
            $this->errors[_w('Required fields')][] ='subscriber[email]' ;
        }
        else {
            if (!$ev->isValid($subscriber['email'])) {
                $this->errors[_w('Invalid email format')][] ='subscriber[email]' ;
            }
        }

        if (!$this->errors) {
            $ms = new mailerSubscriberModel();
            if (empty($form)) { // subscribe to All subscribers
                if ($contact_id = $ms->addSubscriber(null, $subscriber, array(0))) {
                    $this->logAction('subscribed_via_form', null, null, $contact_id);
                    $this->response = $contact_id;
                } else {
                    $this->errors = "error while susbscribing";
                }
                return;
            } else {
                if (isset($form['params']['confirm_mail'])) {
                    $this->confirmationTrigger($form, $subscriber, $lists);
                } else {
                    $contact_id = $ms->addSubscriber($form['id'], $subscriber, $lists);
                    $this->logAction('subscribed_via_form', null, null, $contact_id);
                }

                if ($form['params']['after_submit'] == 'redirect') {
                    $this->response = array($form['params']['after_submit'] => $form['params']['redirect_after_submit']);
                } else {
                    // doesn't contains HTML - keep new lines
                    if($form['params']['html_after_submit'] == strip_tags($form['params']['html_after_submit'])) {
                        $form['params']['html_after_submit'] = nl2br($form['params']['html_after_submit']);
                    }
                    $this->response = array($form['params']['after_submit'] => $form['params']['html_after_submit']);
                }
            }
        }
    }

    protected function confirmationTrigger($form, $subscriber, $lists)
    {
        // generate link with hash
        $confirmation_hash = hash('md5', time().'QuKLH:sh8o0gbksdblg`;ogZ$/.`+'.mt_rand().mt_rand().mt_rand());
        $confirm_url = wa()->getRouteUrl('mailer/frontend/confirm/hash', array('hash'=>$confirmation_hash), true);

        // Validate email
        $ev = new waEmailValidator();
        // default sender
        if (!empty($form['params']['confirm_mail_from']) && $ev->isValid($form['params']['confirm_mail_from'])) {
            $from_name = $from_email = trim($form['params']['confirm_mail_from']);
        }
        else {
            $default = waMail::getDefaultFrom();
            $from_name = reset($default);
            $from_email = key($default);
        }

        $message = new mailerSimpleMessage(array(
            'subject'       => trim($form['params']['confirm_mail_subject']),
            'body'          => nl2br(trim($form['params']['confirm_mail_body'])),
            'from_name'     => $from_name,
            'from_email'    => $from_email,
            'sender_id'     => 0
        ));

        $name = isset($subscriber['name']) ? trim($subscriber['name']) : "";
        $email = $subscriber['email'];

        $message->send($email, $name, array(
            '{SUBSCRIBER_NAME}'             => $name,
            '{SUBSCRIPTION_CONFIRM_URL}'    => $confirm_url
        ));

        $mst = new mailerSubscriberTempModel();
        $mst->save($confirmation_hash, array( 'form' => $form['id'], 'subscriber' => $subscriber, 'lists' => $lists ));
    }
}

