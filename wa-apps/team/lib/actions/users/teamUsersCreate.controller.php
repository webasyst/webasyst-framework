<?php

/**
 * Accepts an Email to send new user invitation to or create user right away
 */
class teamUsersCreateController extends teamUsersNewUserController
{
    public function execute()
    {
        $email = $this->getEmail();
        $error = $this->validateError($email);
        if ($error) {
            $this->errors[] = $error;
            return;
        }

        $groups = $this->getGroups();

        $contact_info = $this->findUserByEmail($email);
        $error = $this->validateContact($contact_info);
        if ($error) {
            $this->errors[] = $error;
            return;
        }

        $credentials = $this->getCredentials();
        $error = $this->validateCredentials($credentials, $contact_info['id']);
        if ($error) {
            $this->errors[] = $error;
            return;
        }

        $data = $credentials;
        unset($data['password_confirm']);
        $data['email'] = array($email);
        $data['id'] = $contact_info ? $contact_info['id'] : null;

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
        if (empty($data['login'])) {
            return array(_w('A login name is required.'), 'login');
        }

        $data['login'] = strtolower(trim($data['login']));
        if (!preg_match('~^[a-z0-9@_\.\-]+$~u', $data['login'])) {
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

}
