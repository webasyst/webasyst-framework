<?php

class waAuthRunOutOfTriesException extends waAuthException
{
    public function __construct($message = '', $code = 500, $previous = null)
    {
        $message = is_scalar($message) ? (string)$message : '';
        if (strlen($message) <= 0) {
            $message = _ws('You have run out of available attempts.');
        }
        parent::__construct($message, $code, $previous);
    }
}
