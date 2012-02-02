<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage mail
 */
class waMail
{
    public function __construct()
    {

    }

    /**
     * Compose new message and returns objects of waMailMessage class
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param string $from
     * @return waMailMessage
     */
    public function compose($to, $subject, $body, $from = null)
    {
        return new waMailMessage($to, $subject, $body, $from);
    }

    public function send($to, $subject = null, $body = null, $from = null)
    {
        if ($to instanceof waMailMessage) {
            $message = $to;
        } else {
            $message = $this->compose($to, $subject, $body, $from);
        }
        return @mail($message->getTo(true), $message->getSubject(true), $message->getBody(true), $message->getHeaders(true));
    }
}