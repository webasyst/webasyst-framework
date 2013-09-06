<?php

/** Create a new user from existing contact. */
class contactsContactsUserController extends waJsonController
{
    public function execute()
    {
        $id = (int)waRequest::get('id', waRequest::TYPE_INT);
        $action = waRequest::get('a');

        if (waRequest::getMethod() != 'post') {
            throw new waException('Send something via POST to confirm operation.');
        }

        $admin = 2 <= $this->getUser()->getRights(wa()->getApp(), 'backend');
        if (!$admin && ($action != 'passwd' || $this->getUser()->getId() != $id)) {
            throw new waRightsException('Access denied.');
        }

        switch($action) {
            case 'delete':
                $this->deleteUser($id);
                return;
            case 'passwd':
                $this->userPassword($id);
                return;
            case 'create':
            default:
                $this->createUser($id);
                return;
        }
    }

    protected function userPassword($id) {
        $user = new waUser($id);
        if (waRequest::post('password') === waRequest::post('confirm_password')) {
            $user['password'] = waRequest::post('password');
        } else {
            $this->errors[] = _w('Passwords do not match.');
        }

        if (!$this->errors) {
            $this->response = $user->save();
        }
    }

    protected function deleteUser($id) {
        waUser::revokeUser($id);
        $this->response = TRUE;
    }

    protected function createUser($id) {
        $user = new waUser($id);

        if ($user['is_user']) {
            throw new waException('Already a user.');
        }

        if (waRequest::post('invite')) {
            $user['password'] = uniqid(time(), true); // !!! this is bad and easy to brute force
        } else {
            $login = trim(waRequest::post('login'));
            if (strlen($login) <= 0) {
                $this->errors[] = _w('Login is required.');
            } else if ( ( $u = waUser::getByLogin($login))) {
                $nameLink = '<a href="'.wa_url().'webasyst/contacts/#/contact/'.$u->getId().'">'.$u->get('name').'</a>';
                $this->errors[] = str_replace('%NAME_LINK%', $nameLink, _w('This login is already set for user %NAME_LINK%'));
            }
            $user['login'] = $login;

            // Do not set password if old one exists AND form left empty
            if (!$user['password'] || waRequest::post('password')) {
                if (waRequest::post('password') === waRequest::post('confirm_password')) {
                    $user['password'] = waRequest::post('password');
                } else {
                    $this->errors[] = _w('Passwords do not match.');
                }
            }
        }

        if (!$this->errors) {
            $user['is_user'] = 1;
            $this->response = $user->save();
            $this->log('create_user_account', 1);
        }
    }
}

// EOF