<?php

class waAuthConfirmPhoneException extends waAuthException
{
    public function __construct($message = '', $code = 500, $previous = null)
    {
        $message = is_scalar($message) ? (string)$message : '';
        if (strlen($message) <= 0) {
            $message = _ws('Phone number confirmation error.');
        }
        parent::__construct($message, $code, $previous);
    }
}
