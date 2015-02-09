<?php

/** Change own password */
class webasystProfilePasswordController extends waJsonController
{
    public function execute()
    {
        $user = wa()->getUser();
        if (waRequest::post('password') === waRequest::post('confirm_password')) {
            $user['password'] = waRequest::post('password');
        } else {
            $this->errors[] = _w('Passwords do not match.');
        }

        if (!$this->errors) {
            $this->response = $user->save();
        }
    }
}
