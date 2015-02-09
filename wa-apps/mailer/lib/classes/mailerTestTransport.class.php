<?php

/**
 * Transport used for debugging.
 * Logs messages to wa-log/mailer/ instead of sending them.
 */
class mailerTestTransport implements Swift_Transport
{
    protected $plugins = array();

    // Swift_Transport interface functions that are not used in this transport.
    public function registerPlugin(Swift_Events_EventListener $plugin) {
        $this->plugins[] = $plugin;
    }

    public function isStarted() { return false; }
    public function start() {}
    public function stop() {}

    /**
     * Send the given Message.
     *
     * @param Swift_Mime_Message $message
     * @param string[] &$failedRecipients to collect failures by-reference
     * @return int
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $failedRecipients = (array) $failedRecipients;

        // Dir to save .eml file to
        $path = wa()->getConfig()->getPath('log').'/mailer';
        if (!waFiles::create($path)) {
            throw new waException('Unable to create log dir: '.$path);
        }

        // .eml file contents
        $messageStr = $message->toString();

        // Kinda plugin dispatch...
        $evt = new Swift_Events_SendEvent($this, $message);
        foreach($this->plugins as $p) {
            if (method_exists($p, 'beforeSendPerformed')) {
                $p->beforeSendPerformed($evt);
            }
        }

        // Save email into file with unique filename
        $filename = date('Ymd.His').'.'.sprintf('%u', crc32($messageStr)).'.eml';
        file_put_contents($path.'/'.$filename, $messageStr);

        // Kinda plugin dispatch...
        foreach($this->plugins as $p) {
            if (method_exists($p, 'sendPerformed')) {
                $p->sendPerformed($evt);
            }
        }

        // Return number of recipients accepted for delivery, i.e. all of them
        return count((array) $message->getTo()) + count((array) $message->getCc()) + count((array) $message->getBcc());
    }

    public static function newInstance()
    {
        return new self();
    }
}

