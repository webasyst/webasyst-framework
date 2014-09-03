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
                $r = $this->userPassword($id);
                if ($r) {
                    $this->response = $r;
                }
                return;
            case 'create_login':
                $r = $this->createLogin($id);
                if ($r) {
                    $this->response = $r;
                }
                return;
            case 'create_credentials':
                $r = $this->createCredentials($id);
                if ($r) {
                    $this->response = $r;
                }
                return;
        }
    }

    protected function userPassword($id, $just_check = false) {
        $user = new waUser($id);
        if (waRequest::post('password') === waRequest::post('confirm_password')) {
            $user['password'] = waRequest::post('password');
        } else {
            $this->errors[] = _w('Passwords do not match.');
        }

        if (!$this->errors) {
            if ($just_check) {
                return true;
            }
            $r = $user->save();
            if ($r === 0) {
                return true;
            } else {
                $this->errors = $r;
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    protected function deleteUser($id) {
        waUser::revokeUser($id);
        $this->response = true;
    }
    
    protected function createCredentials($id)
    {
        $r = $this->createLogin($id, true);
        if (!$r) {
            return false;
        }
        if (waRequest::post('password') && waRequest::post('confirm_password')) {
            if (!$this->userPassword($id, true)) {
                return false;
            }
        }
        $r = $this->createLogin($id);
        if (waRequest::post('password') && waRequest::post('confirm_password')) {
            $this->userPassword($id);
        }
        return $r;
    }

//    protected function createUser($id) {
//        $user = new waUser($id);
//
//        if ($user['is_user']) {
//            throw new waException('Already a user.');
//        }
//
//        if (waRequest::post('invite')) {
//            $user['password'] = uniqid(time(), true); // !!! this is bad and easy to brute force
//        } else {
//            $login = trim(waRequest::post('login'));
//            if (strlen($login) <= 0) {
//                $this->errors[] = _w('Login is required.');
//            } else if ( ( $u = waUser::getByLogin($login))) {
//                $nameLink = '<a href="'.wa_url().'webasyst/contacts/#/contact/'.$u->getId().'">'.$u->get('name').'</a>';
//                $this->errors[] = str_replace('%NAME_LINK%', $nameLink, _w('This login is already set for user %NAME_LINK%'));
//            }
//            $user['login'] = $login;
//
//            // Do not set password if old one exists AND form left empty
//            if (!$user['password'] || waRequest::post('password')) {
//                if (waRequest::post('password') === waRequest::post('confirm_password')) {
//                    $user['password'] = waRequest::post('password');
//                } else {
//                    $this->errors[] = _w('Passwords do not match.');
//                }
//            }
//        }
//
//        if (!$this->errors) {
//            $user['is_user'] = 1;
//            $this->response = $user->save();
//            $this->logAction('create_user_account', null, $user->getId());
//        }
//    }

    protected function loginExists($login, $id)
    {
        $user_model = new waUserModel();
        return $user_model->select('id,name')->where("login = s:0 AND id != i:1", array($login, $id))->limit(1)->fetch();
    }


    protected function createLogin($id, $just_check = false)
    {
        $user = new waUser($id);
        
        $login = trim(waRequest::post('login'));
        if (strlen($login) <= 0) {
            $this->errors[] = _w('Login is required.');
        } else if (($u = $this->loginExists($login, $id))) {
            $nameLink = '<a href="'.wa_url().'webasyst/contacts/#/contact/'.$u['id'].'">'.$u['name'].'</a>';
            $this->errors[] = str_replace('%NAME_LINK%', $nameLink, _w('This login is already set for user %NAME_LINK%'));
        }
        if (!$this->errors) {
            if ($just_check) {
                return true;
            }
            $user['login'] = $login;
            $r = $user->save();
            if ($r === 0) {
                return array(
                    'login' => $login
                );
            } else {
                $this->errors = $r;
                return false;
            }
        } else {
            return false;
        }
    }
}

// EOF