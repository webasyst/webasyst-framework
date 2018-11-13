<?php

class waAuthInvalidCredentialsException extends waException
{
    public function __construct($message = '', $code = 500, $previous = null)
    {
        $message = is_scalar($message) ? (string)$message : '';
        if (strlen($message) <= 0) {
            $message = _ws('Invalid login or password');
        }
        parent::__construct($message, $code, $previous);
    }
}
