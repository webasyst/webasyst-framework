<?php

/** Change own password */
class webasystProfilePasswordController extends waJsonController
{
    public function execute()
    {
        $user = wa()->getUser();
        $password = waRequest::post('password');
        $confirm_password = waRequest::post('confirm_password');

        if ($password === $confirm_password) {
            if (strlen((string)$password) > waAuth::PASSWORD_MAX_LENGTH) {
                $this->errors[] = _w('Specified password is too long.');
            } else {
                $user['password'] = $password;
            }
        } else {
            $this->errors[] = _w('Passwords do not match');
        }

        if (!$this->errors) {
            $this->response = $user->save();
        }
    }
}
