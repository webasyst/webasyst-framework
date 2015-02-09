<?php

/**
 * Campaign sending logic.
 */
class mailerMessage extends mailerSimpleMessage
{
    protected $id;
    protected $data;
    protected $params;
    protected $vars;
    /**
    * @var mailerMessageModel
    */
    protected $message_model;
    /**
    * @var mailerMessageLogModel
    */
    protected $log_model;
    protected $contact_fields;

    public function __construct($data)
    {
        // init messageModel
        $this->message_model = new mailerMessageModel();

        // init message
        if (is_array($data)) {
            $this->id = $data['id'];
            $this->data = $data;
        } else {
            $this->id = $data;
            $this->data = $this->message_model->getById($this->id);
        }

        if (!$this->data || !$this->id) {
            throw new waException('Message not found', 404);
        }

        $params_model = new mailerMessageParamsModel();
        $this->params = $params_model->getByMessage($this->id);

        // init messageLogModel
        $this->log_model = new mailerMessageLogModel();
        $this->contact_fields = array();
    }

    protected function prepareBody()
    {
        if (!$this->data) {
            throw new waException('No data loaded for mailermessage ('.$this->id.').');
        }

        // get contact fields used in template
        if (preg_match_all('~\$([a-z0-9_]+)~is', $this->data['body'], $match)) {
            $this->contact_fields = array();
            $fields = waContactFields::getAll();
            $fields['id'] = true;
            foreach($match[1] as $fld) {
                if (empty($fields[$fld])) {
                    continue;
                }
                $this->contact_fields[] = $fld;
            }
        }
        // replace urls for google analytics
        if (!empty($this->params['google_analytics'])) {
            // Converter of plain text links into <a> breaks inline CSS backgrounds, so it is turned off for now.
            //$this->data['body'] = preg_replace("#([^\"'=]|^)((https?)://[^'\"<>\n\r ]+)(?!<\/a>)(['\"<>\n\r ])#i", '\\1<a href="\\2">\\2</a>\\4', $this->data['body']);
            $this->data['body'] = preg_replace_callback('/(<a[^>]+href=([\'"]))(.*?)(\2[^>]*>)/si', array($this, 'generateUtm'), $this->data['body']);
        }

        // Add log_id parameter to the first image URL in body
        // getDataUrl() does not work in CLI mode (with cron), so the URL here is hardcored
        $url = '/wa-data/public/mailer/files/';
        if (strpos($this->data['body'], $url) !== false) {
            $this->data['body'] = preg_replace('!(<img[^<]*src="[^"]*'.$url.'[^"]+\.)(jpg|jpeg|png|gif)"!is', '$1{$log_id}.$2"', $this->data['body'], 1);
        }

        // curly bracket + symbol after = smarty error (in <style>)
        // fix this by adding space after curly bracket if we have ":" inside curly brackets
        $this->data['body'] = preg_replace('/{([^\s][^}]*):([^}]*)}/i', '{ $1:$2}', $this->data['body']);

        // prepare HTML
        if (stripos($this->data['body'], '<body') === false) {
            $this->data['body'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <title>'.$this->data['subject'].'</title>
    </head>
    <body>
'.$this->data['body'].'
</body></html>';
        } else {
            $this->data['body'] = preg_replace('~<title>[^<]*</title>~', '<title>'.htmlspecialchars($this->data['subject']).'</title>', $this->data['body']);
        }
    }

    protected function generateUtm($matches)
    {
        @list($url, $hash) = explode('#', $matches[3], 2);

        // Do not add UTM to mailto: links
        if (substr($url, 0, 7) == 'mailto:') {
            return $matches[1].$matches[3].$matches[4];
        }

        if (strpos($url, '?') === false) {
            $url .= '?';
        } else {
            $url .= '&';
        }
        $utm = array();
        $keys = array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content');
        foreach ($keys as $ga_key) {
            $key = 'google_analytics_'.$ga_key;
            if (isset($this->params[$key]) && $this->params[$key]) {
                $utm[] = $ga_key.'='.urlencode($this->params[$key]);
            }
        }
        $url .= implode('&', $utm);
        if ($hash) {
            $url .= '#'.$hash;
        }
        return $matches[1].$url.$matches[4];
    }

    /**
     * @param int $status
     */
    public function status($status = null)
    {
        if ($status === null) {
            return $this->data['status'];
        } else {
            $status = (int) $status;
            $data = array('status' => $status);

            // Update datetime too if needed
            if ($this->data['status'] != $status) {
                $update_return_path = false;
                if ($status == mailerMessageModel::STATUS_SENT) {
                    $data['finished_datetime'] = date('Y-m-d H:i:s');
                    $update_return_path = true;
                } else if ($status == mailerMessageModel::STATUS_SENDING && !$this->data['send_datetime']) {
                    $data['send_datetime'] = date('Y-m-d H:i:s');
                    $update_return_path = true;
                }

                if ($update_return_path && $this->data['return_path']) {
                    $rpm = new mailerReturnPathModel();
                    $rpm->updateByEmail($this->data['return_path'], array(
                        'last_campaign_date' => date('Y-m-d H:i:s'),
                    ));
                }

                $this->data = $data + $this->data;
                $this->message_model->updateById($this->id, $data);
            }
        }
    }

    public function isSending()
    {
        $filename = $this->getLockFile();
        if (!file_exists($filename)) {
            return false;
        }
        $x = @fopen($filename, "r+");
        if ($x && flock($x, LOCK_EX|LOCK_NB)) {
            $result = false;
            flock($x, LOCK_UN);
            fclose($x);
            unlink($filename);
        } else {
            $result = true;
            if ($x) {
                flock($x, LOCK_UN);
                fclose($x);
            }
        }
        return $result;
    }

    public function send()
    {
        $filename = $this->getLockFile();
        // create lock file
        touch($filename);
        // set rights for all
        chmod($filename, 0666);
        // open lock file
        $x = @fopen($filename, "r+");
        // check and get exclusive lock
        if ($x && flock($x, LOCK_EX|LOCK_NB)) {

            /**@/**
             * @event campaign.before_sending
             *
             * A sending session started for all campaigns
             *
             * For all campaigns there could be one sending session. It can be triggered by CRON,
             * or by a backend user opening campaign report page (for each campaign).
             *
             * @return void
             */
            wa()->event('campaign.before_sending');

            // send message
            $this->sendMessage();
            // unlock file
            flock($x, LOCK_UN);
            fclose($x);
            // remove lock file
            unlink($filename);
            return true;
        } else {
            // other script is already running at this moment
            return false;
        }
    }

    protected function getLockFile()
    {
        return wa()->getDataPath('lock/'.$this->id.'.lock', false, 'mailer');
    }

    public function getTestMessage()
    {
        $this->prepareBody();
        $message = $this->getMessage();
        $user = wa()->getUser();
        $row_id = '000';
        $message->setTo($user->get('email', 'default'), $user->getName());
        if (trim($this->data['return_path'])) {
            $message->setReturnPath(str_replace('@', '+'.$row_id.'@', $this->data['return_path']));
        } elseif ($this->data['return_path'] === ' ') {
            $this->setHeader($message, 'X-Log-ID', $row_id);
        }

        $unsubscribe_link = $this->getUnsubscribeLink();
        $this->setHeader($message, 'List-Unsubscribe', '<'.$unsubscribe_link.'>');

        // use smarty view
        $view = wa()->getView();
        $view->assign('log_id', $row_id);
        $view->assign('unsubscribe_link', $unsubscribe_link);
        if ($this->contact_fields) {
            $this->assignContactData($view, $user->load('value'));
        }
        $body = $view->display('eval:'.$this->data['body']);

        $message->setBody($body, 'text/html', 'utf-8');
        $message->addPart(mailerHtml2text::convert($body), 'text/plain');
        $message->generateId();

        return $message;
    }

    public function sendTestMessage($addresses, $subject=null)
    {
        if (!$addresses || !is_array($addresses)) {
            return array();
        }

        $this->prepareBody();
        $mailer = Swift_Mailer::newInstance($this->getTransport());
        $view = wa()->getView();

        if ($this->contact_fields) {
            $contact_ids = array();
            $cem = new waContactEmailsModel();
            $emails_to_id = $cem->getContactIdsByEmails(array_keys($addresses));
            $cc = new waContactsCollection(array_values($emails_to_id));
            $contacts = $cc->getContacts(implode(',', $this->contact_fields), 0, count($emails_to_id));
            $contacts[0] = array_fill_keys($this->contact_fields, '');
        }

        $result = array();

        foreach($addresses as $email => $name) {
            $row_id = 0;
            $row = array(
                'id' => $row_id,
                'name' => $name,
                'email' => $email,
                'contact_id' => ifset($emails_to_id[$email], 0),
            );
            $error = '';
            $recipient_status = null;

            try {
                $message = $this->getMessage();

                $this->attachSigner($message);

                // set to
                if ($row['name']) {
                    $message->setTo($row['email'], $row['name']);
                } else {
                    $message->setTo($row['email']);
                }
                // set Returh-Path
                if (trim($this->data['return_path'])) {
                    $message->setReturnPath(str_replace('@', '+'.$row_id.'@', $this->data['return_path']));
                } elseif ($this->data['return_path'] === ' ') {
                    $this->setHeader($message, 'X-Log-ID', $row_id);
                }
                // set Reply-to
                if (trim($this->data['reply_to'])) {
                    $message->setReplyTo(trim($this->data['reply_to']));
                }
                // set List-Unsubscribe
                $unsubscribe_link = $this->getUnsubscribeLink($row);
                $this->setHeader($message, 'List-Unsubscribe', '<'.$unsubscribe_link.'>');

                // Optional custom subject for test message
                if ($subject) {
                    $message->setSubject($subject);
                }

                // set body
                $view->clearAllAssign();
                $view->assign('log_id', $row_id);
                $view->assign('unsubscribe_link', $unsubscribe_link);
                if ($this->contact_fields) {
                    $this->assignContactData($view, ifset($contacts[$row['contact_id']], array()));
                }
                $body = $view->fetch('string:'.$this->data['body']);
                $message->setBody($body, 'text/html', 'utf-8');
                $message->addPart(mailerHtml2text::convert($body), 'text/plain');
                $message->generateId();

                // send message
                $recipient_status = $mailer->send($message) ? mailerMessageLogModel::STATUS_SENT : mailerMessageLogModel::STATUS_SENDING_ERROR;
            } catch (Exception $e) {
                if ($this->data['return_path'] &&
                    $e instanceof Swift_TransportException &&
                    strpos($e->getMessage(), '553') !== false)
                {
                    $message->setReturnPath($this->data['from_email']);
                    $this->data['return_path'] = ' ';
                    try {
                        $recipient_status = $mailer->send($message) ? mailerMessageLogModel::STATUS_SENT : mailerMessageLogModel::STATUS_SENDING_ERROR;
                    } catch (Exception $ex) {
                        $recipient_status = mailerMessageLogModel::STATUS_SENDING_ERROR;
                        $error = $ex->getMessage();
                    }
                } else {
                    $recipient_status = mailerMessageLogModel::STATUS_SENDING_ERROR;
                    $error = $e->getMessage();
                }
            }
            if ($recipient_status == mailerMessageLogModel::STATUS_SENT) {
                $result[$email] = '';
            } else {
                $result[$email] = ifempty($error, _w('Bounced by sender mail server'));//.' '.print_r(array($recipient_status, $row), 1);
            }
        }

        return $result;
    }

    public function testReturnPathSmtpSender()
    {
        $transport = $this->getTransport();
        if ($transport instanceof Swift_SmtpTransport) {
            if (!$transport->isStarted()) {
                $transport->start();
            }

            try {
                $transport->executeCommand(sprintf("MAIL FROM: <%s>\r\n", $this->data['return_path']), array(250));
            } catch (Exception $e) {
                return false;
            }
        }
        return true;
    }

    protected function sendMessage()
    {
        if ($this->data['status'] != mailerMessageModel::STATUS_SENDING) {
            return;
        }

        // Check if sending speed is set correctly
        $speed_limit = ifset($this->params['speed_limit'], ''); // emails per hour
        if ($this->data['status'] == mailerMessageModel::STATUS_DRAFT) {
            $speeds = wa('mailer')->getConfig()->getAvailableSpeeds();
            if (empty($speeds[$speed_limit]) || !empty($speeds[$speed_limit]['disabled'])) {
                throw new waException('Speed limit is set incorrectly. "'.$speed_limit.'" '.print_r($speeds, true));
            }
        }

        // prepare message
        $this->prepareBody();

        // send message
        $transport = $this->getTransport();

        $mailer = Swift_Mailer::newInstance($transport);

        // Use AntiFlood to re-connect after several emails
        $mailer->registerPlugin(new Swift_Plugins_AntiFloodPlugin(50));
        // Rate limit number of emails per minute
        if ($speed_limit) {
            $mailer->registerPlugin(new Swift_Plugins_ThrottlerPlugin(
                $speed_limit / 60, Swift_Plugins_ThrottlerPlugin::MESSAGES_PER_MINUTE
            ));
        } else {
            $mailer->registerPlugin(new Swift_Plugins_ThrottlerPlugin(
                150, Swift_Plugins_ThrottlerPlugin::MESSAGES_PER_MINUTE
            ));
        }

        // create smarty view
        $view = wa()->getView();

        $limit = 100;
        $i = 0;
        $sent_since_last_event = 0;
        while ($rows = $this->log_model->getByField(array('message_id' => $this->id, 'status' => mailerMessageLogModel::STATUS_AWAITING), 'id', $limit)) {
            if ($this->contact_fields) {
                $contact_ids = array();
                foreach ($rows as $row) {
                    $contact_ids[] = $row['contact_id'];
                }
                $cc = new waContactsCollection($contact_ids);
                $contacts = $cc->getContacts(implode(',', $this->contact_fields), 0, $limit);
            }
            foreach ($rows as $row_id => $row) {
                $recipient_status = mailerMessageLogModel::STATUS_SENDING_ERROR;
                try {
                    // Every once in a while notify plugins about how many messages we send,
                    // and check if campaign sending were paused.
                    if ($i % 20 == 0) {
                        if ($sent_since_last_event > 0) {
                            $this->eventSending($sent_since_last_event);
                            $sent_since_last_event = 0;
                        }
                        $status = $this->log_model->query("SELECT status FROM mailer_message WHERE id=:id", array('id' => $this->id))->fetchField();
                        if ($status != mailerMessageModel::STATUS_SENDING) {
                            // Sending paused.
                            return;
                        }
                    }

                    $message = $this->getMessage();

                    $this->attachSigner($message);

                    // set to
                    if ($row['name']) {
                        $message->setTo($row['email'], $row['name']);
                    } else {
                        $message->setTo($row['email']);
                    }
                    // set Returh-Path
                    $return_path_used = '';
                    $this->setHeader($message, 'X-Log-ID', $row_id);
                    if (trim($this->data['return_path'])) {
                        if (empty($this->params['no_plus_in_rp'])) {
                            // append log-id to return-path via the + sign
                            $return_path_used = str_replace('@', '+'.$row_id.'@', $this->data['return_path']);
                        } else {
                            // plus sign in return-path is not supported
                            $return_path_used = $this->data['return_path'];
                        }
                    }
                    if ($return_path_used) {
                        $message->setReturnPath($return_path_used);
                    }

                    // set Reply-to
                    if (trim($this->data['reply_to'])) {
                        $message->setReplyTo(trim($this->data['reply_to']));
                    }
                    // set List-Unsubscribe
                    $unsubscribe_link = $this->getUnsubscribeLink($row);
                    $this->setHeader($message, 'List-Unsubscribe', '<'.$unsubscribe_link.'>');

                    // set body
                    $view->clearAllAssign();
                    $view->assign('log_id', $row_id);
                    $view->assign('unsubscribe_link', $unsubscribe_link);
                    if ($this->contact_fields) {
                        $view = $this->assignContactData($view, $contacts[$row['contact_id']]);
                    }
                    $subject = $view->fetch('string:'.$this->data['subject']);
                    $message->setSubject($subject);

                    $body = $view->fetch('string:'.$this->data['body']);
                    $message->setBody($body, 'text/html', 'utf-8');
                    $message->addPart(mailerHtml2text::convert($body), 'text/plain');
                    $message->generateId();

                    $i++;

                    // send message
                    $recipient_status = $mailer->send($message) ? mailerMessageLogModel::STATUS_SENT : mailerMessageLogModel::STATUS_SENDING_ERROR;
                    $this->log_model->setStatus($row_id, $recipient_status);
                } catch (Exception $e) {
                    if ($i == 1 && $this->data['return_path'] &&
                        $e instanceof Swift_TransportException && (strpos($e->getMessage(), '553') !== false || strpos($e->getMessage(), '550') !== false))
                    {

                        //
                        // We've got a transport error trying to send the first message.
                        // Probably something's wrong with Sender settings.
                        // Try to fix things on the fly. Idea is to try several times,
                        // each time lowering our expectations about the sender.
                        //

                        $error_message = $e->getMessage();
                        $recipient_status = mailerMessageLogModel::STATUS_SENDING_ERROR;

                        do {
                            waLog::log('mailer: Return-path "'.$return_path_used.'" forbidden by sending server (sender_id='.$this->data['sender_id'].'): '.$error_message);

                            if (empty($this->params['no_plus_in_rp'])) {
                                // First suggestion: maybe sender does not like the '+' signs in return-paths
                                $this->params['no_plus_in_rp'] = 1;
                                $return_path_used = $this->data['return_path'];
                                $params_model = new mailerMessageParamsModel();
                                $params_model->insert(array(
                                    'message_id' => $this->id,
                                    'name' => 'no_plus_in_rp',
                                    'value' => 1,
                                ));
                            } else if ($this->data['return_path']) {
                                // Second suggestion: maybe sender does not like this return-path at all. Use the From address.
                                $return_path_used = $this->data['from_email'];
                                $this->data['return_path'] = '';
                                $this->message_model->updateById($this->id, array(
                                    'return_path' => ''
                                ));
                            } else {
                                // Too bad. No idea what's wrong. I give up!
                                break;
                            }

                            $message->setReturnPath($return_path_used);

                            try {
                                $error_message = '';
                                $recipient_status = $mailer->send($message) ? mailerMessageLogModel::STATUS_SENT : mailerMessageLogModel::STATUS_SENDING_ERROR;
                            } catch (Exception $ex) {
                                $recipient_status = mailerMessageLogModel::STATUS_SENDING_ERROR;
                                $error_message = $ex->getMessage();
                            }
                        } while ($recipient_status < mailerMessageLogModel::STATUS_SENT);

                        $this->log_model->setStatus($row_id, $recipient_status, $error_message, '', false); // non-fatal
                    } else {
                        $error_class = null;
                        $error = $e->getMessage();
                        $error_fatal = false;
                        if ($error && preg_match('~RFC 2822~u', $error)) {
                            $error_class = 'Address incorrect or does not exist';
                            $error_fatal = true;
                        }
                        $recipient_status = mailerMessageLogModel::STATUS_SENDING_ERROR;
                        $this->log_model->setStatus($row_id, $recipient_status, $error, $error_class, $error_fatal);

                        if (preg_match('~Expected response code [0-9]+ but got code ""~u', $error)) {
                            // SMTP server response timed out.
                            // The best thing we can do is to continue sending later.
                            if ($sent_since_last_event > 0) {
                                $this->eventSending($sent_since_last_event);
                            }
                            return;
                        } else if (preg_match('~Expected response code [0-9]+ but got code "421"~u', $error)) {
                            // Too many messages in one SMTP session. Reconnect and continue.
                            $transport->stop();
                            sleep(1);
                            $transport->start();
                        }
                    }
                }
                if ($recipient_status >= mailerMessageLogModel::STATUS_SENT) {
                    $sent_since_last_event++;
                }
            }
        }

        if ($sent_since_last_event > 0) {
            $this->eventSending($sent_since_last_event);
            $sent_since_last_event = 0;
        }

        // message has been sent
        $this->status(mailerMessageModel::STATUS_SENT);
    }

    protected function eventSending($sent_since_last_event)
    {
        /**@/**
         * @event campaign.sending
         *
         * Notify plugins about sending in progress
         *
         * @param array[string]int $params['sent_count'] number of emails successfully sent since last event
         * @param array[string]array $params['campaign'] row from mailer_message
         * @param array[string]array $params['params'] campaign params from mailer_message_params, key => value
         * @return void
         */
        $evt_params = array(
            'sent_count' => $sent_since_last_event,
            'campaign' => $this->data,
            'params' => $this->params,
        );
        wa()->event('campaign.sending', $evt_params);
    }

    protected function assignContactData($view, $contact_data)
    {
        foreach ($contact_data as $field => $value) {
            if (!$value) {
                $view->assign($field, '');
                continue;
            }
            $f = waContactFields::get($field);
            if ($f) {
                if ($f->isMulti()) {
                    $value = $value[0];
                }
                $value = $f->format($value, 'value');
            } else {
                while(is_array($value)) {
                    if (isset($value[0])) {
                        $value = $value[0];
                    } else {
                        $value = implode(', ', $value);
                    }
                }
            }
            $view->assign($field, $value);
        }
        return $view;
    }

    protected function setHeader(Swift_Message $message, $header, $value)
    {
        if ($message->getHeaders()->has($header)) {
            $message->getHeaders()->get($header)->setValue($value);
        } else {
            $message->getHeaders()->addTextHeader($header, $value);
        }
    }

    protected function getUnsubscribeLink($row=null)
    {
        $params = array(
            'hash' => self::getUnsubscribeHash($row),
        );
        if ($row && empty($row['id']) && !empty($row['email'])) {
            $params['email'] = $row['email'];
        }
        return wa()->getRouteUrl('mailer/frontend/unsubscribe', $params, true);
    }

    public static function getUnsubscribeHash($row=null)
    {
        if (!$row) {
            // fake hash for antispam email testing
            $hash = md5(uniqid(true).wa()->getUser()->get('email', 'default'));
            return substr($hash, 0, 16).'000'.substr($hash, -16);
        }
        $hash = md5($row['email'].'-'.$row['contact_id']);
        return substr($hash, 0, 16).$row['id'].substr($hash, -16);
    }

    /**
     * @return Swift_Message
     */
    protected function getMessage()
    {
        $m = parent::getMessage();
        // set header Precedence: bulk
        $m->getHeaders()->addTextHeader('Precedence', 'bulk');
        return $m;
    }

    /**
     * @param Swift_Message $message
     */
    protected function attachSigner(&$message)
    {
        // DKIM Signer
        $params_model = new mailerSenderParamsModel();
        $sender_params = $params_model->getBySender($this->data['sender_id']);
        if (!empty($sender_params['dkim'])) {
            $email = $this->data['from_email'];
            $e = explode('@', $email);
            $domain_name = array_pop($e);
            $signer = new Swift_Signers_DKIMSigner(
                $sender_params['dkim_pvt_key'], $domain_name, mailerHelper::getDkimSelector($email)
            );
            $signer->ignoreHeader('Return-Path');
            $signer->ignoreHeader('Bcc');
            $message->attachSigner($signer);
        }
    }
}

