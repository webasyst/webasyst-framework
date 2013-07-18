<?php

require_once realpath(dirname(__FILE__).'/../').'/vendors/swift/swift_required.php';

class waMailMessage extends Swift_Message
{
    /**
     * @var waIdna
     */
    protected static $_idna;

    public function addAttachment($path, $name = null, $inline = false)
    {
        $attach = Swift_Attachment::fromPath($path);
        if ($name !== null) {
            $attach->setFilename($name);
        }
        if ($inline) {
            $attach->setDisposition('inline');
        }
        $this->attach($attach);
    }

    /**
     * @param array|string $addresses
     * @param string $name
     * @return waMailMessage
     */
    public function setTo($addresses, $name = null)
    {
        $this->_formatAddresses($addresses, $name);
        if (!is_array($addresses) && isset($name)) {
            $addresses = array($addresses => $name);
        }
        $result = array();
        foreach ((array)$addresses as $email => $name) {
            if (!is_string($email)) {
                $email = $name;
                $name = null;
            }
            if (!preg_match("/^[a-z0-9~@+:\[\]\.-]+$/ui", $email)) {
                $email = $this->encodeEmail($email);
            }
            if ($name === null) {
                $result[] = $email;
            } else {
                $result[$email] = $name;
            }
        }
        return parent::setTo($result);
    }


    public function encodeEmail($email)
    {
        if (!self::$_idna) {
            self::$_idna = new waIdna();
        }
        return self::$_idna->encode($email);
    }

    /**
     * @param array|string $addresses
     * @param string $name
     * @return waMailMessage
     */
    public function setFrom($addresses, $name = null)
    {
        $this->_formatAddresses($addresses, $name);
        return parent::setFrom($addresses, $name);
    }

    private function _formatAddresses(&$addresses, &$name)
    {
        if (!is_array($addresses) && $name === null && strpos($addresses, '<') !== false) {
            if ($data = $this->parseAddress($addresses)) {
                $addresses = $data['email'];
                $name = $data['name'];
            }
        }
    }

    protected function parseAddress($addresses)
    {
        $parser = new waMailAddressParser($addresses);
        $data = $parser->parse();
        if ($data) {
            return $data[0];
        }
        return false;
    }

    public function setBody($body, $contentType = 'text/html', $charset = null)
    {
        return parent::setBody($body, $contentType, $charset);
    }

    public function send()
    {
        if (!$this->getFrom()) {
            if ($from = waMail::getDefaultFrom()) {
                $this->setFrom($from);
            }
        }
        $mailer = new waMail(waMail::getTransportByEmail(key($this->getFrom())));
        return $mailer->send($this);
    }
}