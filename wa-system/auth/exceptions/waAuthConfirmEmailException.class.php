<?php

class waAuthConfirmEmailException extends waAuthException
{
    public function __construct($message = '', $code = 500, $previous = null)
    {
        $message = is_scalar($message) ? (string)$message : '';
        if (strlen($message) <= 0) {
            $message = _ws('Confirm email exception');
        }

        $file_candidates = array(
            waConfig::get('wa_path_config').'/exception/confirm_email_message.php',
            dirname(__FILE__).'/confirm_email_message.php',
        );

        foreach($file_candidates as $f) {
            if (file_exists($f)) {
                ob_start();
                include($f);
                $message = ob_get_clean();
                break;
            }
        }

        parent::__construct($message, $code, $previous);
    }
}
