<?php

/**
 * Frontend action to serve unsubscribe links from mail.
 */
class mailerFrontendUnsubscribeAction extends waViewAction
{
    public function execute()
    {
        $hash = waRequest::get('hash');
        if (!$hash) {
            $hash = waRequest::param('hash');
        }

        $log_id = substr($hash, 16, -16);
        // getting id in message_log table
        $mlm = new mailerMessageLogModel();
        $log = $mlm->getById($log_id);
        $email = waRequest::get('email');
        if (!$email) {
            $email = waRequest::param('email');
        }

        if ((!$log_id || !$log) && $email) { // if no hash specified - unsubscribe by email if passed (user must signin before)
            $this->unsubscribeByEmail($email);
        }
        elseif ($log)  { // if we have hash and record in DB then autoauth user
            wa()->getAuth()->auth(array('id'=>$log['contact_id']));

            if (!$log || $hash !== mailerMessage::getUnsubscribeHash($log)) {
                throw new waException('Page not found', 404);
            }

            $message_model = new mailerMessageModel();
            $message = $message_model->getById($log['message_id']);

            $list_id = waRequest::get('id');
            if ($list_id === null) {
                $list_id = $message['list_id'];
            }

            // Add email to mailer_unsubscriber
            $unsubscribe_model = new mailerUnsubscriberModel();
            if (!$list_id) {
                // list_id == 0 means: all lists
                $unsubscribe_model->deleteByField('email', $log['email']);
            }
            $unsubscribe_model->insert(array(
                    'email' => $log['email'],
                    'list_id' => $list_id,
                    'datetime' => date('Y-m-d H:i:s'),
                    'message_id' => $log['message_id'],
                ), 2);

            // Remove email from mailer_subscriber
            $subscribe_model = new mailerSubscriberModel();
            if ($list_id) {
                $subscribe_model->deleteForUnsubscribe($log['contact_id'], $list_id);
            } else {
                $subscribe_model->deleteForUnsubscribe($log['contact_id']);
            }

            // Update campaign statistics
            $mlm->updateById($log_id, array(
                    'status' => mailerMessageLogModel::STATUS_UNSUBSCRIBED,
                ));

            // Add to wa_log
            //$this->log('unsubscribe', 1, $log['contact_id'], 'list:'.$list_id.";message:".$message['id']);
            $this->logAction('unsubscribed_from_all_mailings', null, null, $log['contact_id']);

        } else {

        }
        $this->redirect(wa()->getRouteUrl('mailer/frontend/mySubscriptions/'));

        return;
    }

    protected function unsubscribeByEmail($email) {
        // Add email to mailer_unsubscriber
        $unsubscribe_model = new mailerUnsubscriberModel();
        $ce = new waContactEmailsModel();
        $ms = new mailerSubscriberModel();
        $unsubscribe_model->deleteByField('email', $email);
        $unsubscribe_model->insert(array(
            'email' => $email,
            'list_id' => 0,
            'datetime' => date('Y-m-d H:i:s'),
            'message_id' => 0, // most probably a test send
        ), 2);

        // Remove email from mailer_subscriber
        $email_id = $ce->getByField(array(
                'email' => $email
            ));
        if ($email_id) {
            $ms->deleteByField('contact_email_id', $email_id['id']);
        }
        // Add to wa_log
        //$this->log('unsubscribe', 1, 0);
        $this->logAction('unsubscribed_from_all_mailings');
    }
}

