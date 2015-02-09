<?php

/**
 * Wrapper around Swift's PHP mail() transport class.
 * Allows Mailer to work around some of its unfortunate behaviour.
 */
class mailerPhpMailTransport extends Swift_Transport_MailTransport
{
    protected $_eventDispatcher;
    protected $_invoker;

    /**
     * Create a new MailTransport, optionally specifying $extraParams.
     * @param string $extraParams
     */
    public function __construct($extraParams = '-f%s')
    {
        list($this->_invoker, $this->_eventDispatcher) = Swift_DependencyContainer::getInstance()->createDependenciesFor('transport.mail');
        $this->setExtraParams($extraParams);
    }

    /**
     * Send the given Message.
     *
     * Recipient/sender data will be retrieved from the Message API.
     * The return value is the number of recipients who were accepted for delivery.
     *
     * @param Swift_Mime_Message $message
     * @param string[] &$failedRecipients to collect failures by-reference
     * @return int
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $failedRecipients = (array) $failedRecipients;

        if ($evt = $this->_eventDispatcher->createSendEvent($this, $message)) {
            $this->_eventDispatcher->dispatchEvent($evt, 'beforeSendPerformed');
            if ($evt->bubbleCancelled()) {
                return 0;
            }
        }

        $count = (
            count((array) $message->getTo())
            + count((array) $message->getCc())
            + count((array) $message->getBcc())
            );

        $toHeader = $message->getHeaders()->get('To');
        $subjectHeader = $message->getHeaders()->get('Subject');

        if (!$toHeader) {
            throw new Swift_TransportException(
                'Cannot send message without a recipient'
                );
        }
        $to = $toHeader->getFieldBody();
        $subject = $subjectHeader ? $subjectHeader->getFieldBody() : '';

        //Remove headers that would otherwise be duplicated
        $message->getHeaders()->remove('To');
        $message->getHeaders()->remove('Subject');

        $messageStr = $message->toString();

        $message->getHeaders()->set($toHeader);
        $message->getHeaders()->set($subjectHeader);

        //Separate headers from body
        if (false !== $endHeaders = strpos($messageStr, "\r\n\r\n")) {
            $headers = substr($messageStr, 0, $endHeaders) . "\r\n"; //Keep last EOL
            $body = substr($messageStr, $endHeaders + 4);
        } else {
            $headers = $messageStr . "\r\n";
            $body = '';
        }

        unset($messageStr);

        // Make sure no recipient is longer than 255 chars
        // !!! TODO: move this logic elsewhere so that all transports are affected?
        if (strlen($to) > 255) {
            $addresses_encoded = $toHeader->getNameAddressStrings();
            $addresses = $toHeader->getNameAddresses();
            $addresses_list = array_keys($addresses);
            $changed = false;
            foreach($addresses_encoded as $i => $addr_encoded) {
                if (strlen($addr_encoded) > 255) {
                    $changed = true;
                    $addresses[$addresses_list[$i]] = ucfirst(preg_replace('~@.*~', '', $addresses_list[$i]));
                }
            }
            if ($changed) {
                $toHeader->setNameAddresses($addresses);
                $to = $toHeader->getFieldBody();
            }
        }

        // Okay, the line endings problem. Big pain.
        if ("\r\n" === PHP_EOL) {
            // On windows, mail() talks directly to SMTP. Line endings must be \r\n to comply with RFC 2822.
            // Additionally, when a dot is found at the start of the line, it is removed for some reason.
            // To compensate for this, we double such dots if found.
            $subject = str_replace("\r\n.", "\r\n..", $subject);
            $headers = str_replace("\r\n.", "\r\n..", $headers);
            $body = str_replace("\r\n.", "\r\n..", $body);
            $to = str_replace("\r\n.", "\r\n..", $body);
        } else {
            // On Unix/Linux, mail() talks to sendmail or similar CLI utilities.
            // PHP documentation advises to use \r\n anyway, but this causes the following problem.

            // Mail() itself terminates headers it adds (To:, Subject:, Return-path:) witn \n, not \r\n.
            // This even seems logical since it talks via unix CLI and should use unix-style terminators.

            // However, when 'To:' field is longer than 70 chars, Swift splits it into several lines using \r\n.

            // Now from the sendmail's point of view. Sendmail reads first line of the message, which is the first line
            // of the 'To:' header. This line ends with Swift's \r\n. Sendmail (or at least some of its versions) assumes \r\n
            // as the line terminator for the session.
            // But the 'To:' header itself ends with mail()'s \n which is not recognized as the end of the header.
            // As a result, the next header after 'To:' (usulally Subject:) glues to 'To:', making the header incorrect.

            // So, we use \n as line terminator on Unix/Linux instead of advised \r\n to avoid that problem.
            $subject = str_replace("\r\n", PHP_EOL, $subject);
            $headers = str_replace("\r\n", PHP_EOL, $headers);
            $body = str_replace("\r\n", PHP_EOL, $body);
            $to = str_replace("\r\n", PHP_EOL, $to);
        }

        if ($this->_invoker->mail($to, $subject, $body, $headers, sprintf($this->getExtraParams(), $this->_getReversePath($message)))) {
            if ($evt) {
                $evt->setResult(Swift_Events_SendEvent::RESULT_SUCCESS);
                $evt->setFailedRecipients($failedRecipients);
                $this->_eventDispatcher->dispatchEvent($evt, 'sendPerformed');
            }
        } else {
            $failedRecipients = array_merge(
                $failedRecipients,
                array_keys((array) $message->getTo()),
                array_keys((array) $message->getCc()),
                array_keys((array) $message->getBcc())
                );

            if ($evt) {
                $evt->setResult(Swift_Events_SendEvent::RESULT_FAILED);
                $evt->setFailedRecipients($failedRecipients);
                $this->_eventDispatcher->dispatchEvent($evt, 'sendPerformed');
            }

            $message->generateId();

            $count = 0;
        }

        return $count;
    }

    /**
     * Register a plugin.
     *
     * @param Swift_Events_EventListener $plugin
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        $this->_eventDispatcher->bindEventListener($plugin);
    }

    /** Determine the best-use reverse path for this message */
    protected function _getReversePath(Swift_Mime_Message $message)
    {
        $return = $message->getReturnPath();
        $sender = $message->getSender();
        $from = $message->getFrom();
        $path = null;
        if (!empty($return)) {
            $path = $return;
        } elseif (!empty($sender)) {
            $keys = array_keys($sender);
            $path = array_shift($keys);
        } elseif (!empty($from)) {
            $keys = array_keys($from);
            $path = array_shift($keys);
        }

        return $path;
    }

    /**
     * Create a new MailTransport instance.
     * @param  string              $extraParams To be passed to mail()
     * @return Swift_MailTransport
     */
    public static function newInstance($extraParams = '-f%s')
    {
        return new self($extraParams);
    }
}

