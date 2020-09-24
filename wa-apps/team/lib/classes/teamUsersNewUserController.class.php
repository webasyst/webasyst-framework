<?php

class teamUsersNewUserController extends waJsonController
{
    protected $check_rights = true;

    public function __construct()
    {
        if ($this->check_rights && !teamHelper::hasRights('add_users')) {
            throw new waRightsException();
        }
    }

    protected function getEmail()
    {
        return $this->getRequest()->post('email', '', waRequest::TYPE_STRING_TRIM);
    }

    protected function validateError($email)
    {
        $v = new waEmailValidator();
        $error = null;
        if (!$email) {
            $error = _w('This is a required field.');
        } else {
            if (!$v->isValid($email)) {
                $error = _w('This does not look like a valid email');
            }
        }
        return $error;
    }

    /**
     * @param $email
     * @return array[0]array|null Found user or null if not
     * @return array[1]null|string Error
     */
    protected function findUserByEmail($email)
    {
        $cm = new waContactModel();
        $contact_info = $cm->getByEmail($email);
        return $contact_info;
    }

    protected function validateContact($contact_info)
    {
        $error = null;
        if ($contact_info && $contact_info['is_user']) {
            $error = !teamHelper::isBanned($contact_info) ? _w('Already in our team!') : _w('This contact was banned');
        }
        return $error;
    }

    protected function getGroups()
    {
        return array_keys(waRequest::post('groups', array(), waRequest::TYPE_ARRAY_TRIM));
    }
}
