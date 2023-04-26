<?php

/**
 * Accepts an Email to send new user invitation to or create user right away
 */
class teamUsersCreateController extends teamUsersNewUserController
{
    public function execute()
    {
        $contact_id = $this->getRequest()->post('contact_id', '', waRequest::TYPE_INT);

        if (empty($contact_id)) {
            $email = $this->getEmail();
            $error = $this->validateError($email);
            if ($error) {
                $this->errors[] = $error;
                return;
            }
            $contact_info = $this->findUserByEmail($email);
        } else {
            $email = '';
            $contact_model = new waContactModel();
            $contact_info = $contact_model->getById($contact_id);
        }
        $error = $this->validateContact($contact_info);
        if ($error) {
            $this->errors[] = $error;
            return;
        }

        $groups = $this->getGroups();
        $credentials = $this->getCredentials();
        $error = $this->validateCredentials($credentials, $contact_info ? $contact_info['id'] : 0);
        if ($error) {
            $this->errors[] = $error;
            return;
        }

        $event_data = compact('email', 'groups', 'contact_info', 'credentials');
        $this->runCreateUserHook($event_data);
        if ($this->errors) {
            return;
        }

        $data = $credentials;
        unset($data['password_confirm']);
        $data['id'] = $contact_info ? $contact_info['id'] : null;
        if (!empty($email)) {
            $data['email'] = array($email);
        }
        if (!$contact_id) {
            $data += $this->getAdditionalContactData();
        }

        $contact_id = $this->createUser($data, $groups);

        $this->response = array(
            'contact_url' => wa()->getUrl() . 'id/' . $contact_id . '/'
        );
    }

    public function getCredentials()
    {
        $post = $this->getRequest()->post();

        $data = array();
        foreach (array('login', 'password', 'password_confirm') as $field) {
            $data[$field] = trim(ifset($post[$field], ''));
        }

        return $data;
    }

    public function validateCredentials($data, $contact_id)
    {
        $data['login'] = trim($data['login']);
        if (empty($data['login'])) {
            return array(_w('A login name is required.'), 'login');
        }

        if (!preg_match('~^[a-z0-9@_\.\-]+$~u', strtolower($data['login']))) {
            return array(_w('Invalid login name.'), 'login');
        }

        $user_model = new waUserModel();
        $another_user = $user_model->select('id')->where("login = s:0 AND id != i:1", array($data['login'], $contact_id))->limit(1)->fetch();
        if ($another_user) {
            return array(_w('This login is already set for another user'), 'login');
        }

        if (empty($data['password'])) {
            return array(_w('This is a required field.'), 'password');
        } elseif ($data['password'] != $data['password_confirm']) {
            return array(_w('Passwords do not match.'), 'password_confirm');
        } elseif (strlen($data['password']) > waAuth::PASSWORD_MAX_LENGTH) {
            return array(_w('Specified password is too long.'), 'password');
        }

        return null;

    }

    public function createUser($data, $groups)
    {
        // Save login and password
        $contact = new waContact(ifset($data['id']));
        unset($data['id']);

        $data['is_user'] = 1;
        $data['locale'] = wa()->getUser()->getLocale();
        $contact->save($data);

        $contact_id = $contact->getId();

        $ugm = new waUserGroupsModel();
        foreach ($groups as $gid) {
            try {
                $ug = teamGroup::checkUserGroup($gid, $contact_id);
                $ug = $ugm->getByField($ug);
                if (!$ug) {
                    $ugm->add($contact_id, $gid);
                }
            } catch (waException $e) {
            }
        }

        $rm = new waContactRightsModel();
        $rm->save($contact_id, 'team', 'backend', 1);

        return $contact_id;
    }

    /**
     * @param array $event_data
     * @return void
     * @throws waException
     */
    protected function runCreateUserHook($event_data)
    {
        $create_user_event = wa()->event('create_user', $event_data);
        foreach ($create_user_event as $message) {
            if ($message) {
                $this->errors[] = [$message, 'general'];
            }
        }
    }

}
