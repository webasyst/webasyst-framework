<?php
/**
 * Created by PhpStorm.
 * User: kirillmaramygin
 * Date: 27/03/14
 * Time: 15:00
 */

class mailerScheduleDeleteController extends waJsonController
{
    public function execute()
    {
        // scheduled campaign id
        $message_id = waRequest::post('id', 0, 'int');

        $mm = new mailerMessageModel();
        // if we have message id
        if ($message_id) {
            // getting campaign by id
            $campaign = $mm->getById($message_id);
            // if we don't have one or it already sent?
            if (!$campaign || $campaign['status'] > 0 && $campaign['status'] != mailerMessageModel::STATUS_PENDING ) {
                $this->response = $message_id;
                return;
            }

            // Access control
            if (mailerHelper::campaignAccess($campaign) < 2) {
                throw new waException('Access denied.', 403);
            }
        } else {
            // Access control
            if (mailerHelper::isAuthor() < 2) {
                throw new waException('Access denied.', 403);
            }
        }

        $params['send_datetime'] = NULL;
        $params['status'] = mailerMessageModel::STATUS_DRAFT;
        $mm->updateById($message_id, $params);
        // and move recipients from mailer_message_log back to mailer_draft_recipients
//        $mml = new mailerMessageLogModel();
//        $mml->moveToDraftRecipients($message_id);

        $this->response = $message_id;
    }
} 