<?php

/**
 * Frontend action to serve mailview links from mail.
 */
class mailerFrontendMailviewAction extends waViewAction
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
        $mm = new mailerMessageModel();

        $log = $mlm->getById($log_id);
        if (!$log || $hash != mailerMessage::getUnsubscribeHash($log)) {
            throw new waException('Invalid hash');
        }
        $message = $mm->getById($log['message_id']);
        if (!$message) {
            throw new waException('Empty message');
        }
        $msg = new mailerMessage($message);

        $msg->loadMailview($this->view, $log);
    }
}

