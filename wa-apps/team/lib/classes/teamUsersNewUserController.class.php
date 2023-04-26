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

    protected function getPhone()
    {
        return $this->getRequest()->post('phone', '', waRequest::TYPE_STRING_TRIM);
    }

    protected function getContactId()
    {
        return $this->getRequest()->post('contact_id', null, waRequest::TYPE_INT);
    }

    protected function validateError($email)
    {
        $v = new waEmailValidator();
        $error = null;
        if (!$email) {
            $error = _w('This is a required field.');
        } else {
            if (!$v->isValid($email)) {
                $error = _w('This does not look like a valid email address.');
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
            $error = !teamHelper::isBanned($contact_info) ? _w('Already in our team!') : _w('This contact was banned.');
        }
        return $error;
    }

    public static function getAdditionalContactDataFieldsList()
    {
        return [
            ['name' => _w('First name'), 'id' => 'firstname'],
            ['name' => _w('Middle name'), 'id' => 'middlename'],
            ['name' => _w('Last name'), 'id' => 'lastname'],
            ['name' => _w('Job title'), 'id' => 'jobtitle'],
            ['name' => _w('Company'), 'id' => 'company'],
            ['name' => _w('Email'), 'id' => 'email'],
            ['name' => _w('Phone'), 'id' => 'phone'],
        ];
    }

    protected function getAdditionalContactData()
    {
        $field_ids = array_column(self::getAdditionalContactDataFieldsList(), 'id');
        $additional_contact_data = $this->getRequest()->post('c', [], waRequest::TYPE_ARRAY_TRIM);
        $additional_contact_data = array_intersect_key($additional_contact_data, array_fill_keys($field_ids, ''));
        $additional_contact_data = array_filter($additional_contact_data);

        if (isset($additional_contact_data['email'])) {
            $v = new waEmailValidator();
            if (!$v->isValid($additional_contact_data['email'])) {
                unset($additional_contact_data['email']);
            }
        }

        if (isset($additional_contact_data['phone'])) {
            $v = new waPhoneNumberValidator();
            if (!$v->isValid($additional_contact_data['phone'])) {
                unset($additional_contact_data['phone']);
            }
        }

        return $additional_contact_data;
    }

    protected function getGroups()
    {
        return array_keys(waRequest::post('groups', array(), waRequest::TYPE_ARRAY_TRIM));
    }
}
