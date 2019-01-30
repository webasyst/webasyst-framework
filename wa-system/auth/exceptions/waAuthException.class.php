<?php

class waAuthException extends waException
{
    public function __construct($message = '', $code = 500, $previous = null)
    {
        $message = is_scalar($message) ? (string)$message : '';
        if (strlen($message) <= 0) {
            $message = _ws('Auth exception');
        }
        parent::__construct($message, $code, $previous);
    }
}
