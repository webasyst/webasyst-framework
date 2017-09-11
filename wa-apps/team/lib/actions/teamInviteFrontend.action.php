<?php

/**
 * New user signup form. Links to this controller are sent via "invite new user form".
 *
 * THIS IS FRONTEND. wa()->getUser() is not authorized, possibly guest.
 */
class teamInviteFrontendAction extends waViewAction
{
    public function execute()
    {
        if (wa()->getEnv() != 'frontend' || empty($this->params['contact_id'])) {
            throw new waRightsException();
        }
        $contact_id = $this->params['contact_id'];

        $errors = array();
        $data = waRequest::post('data', array(), 'array');
        $data += array(
            'login'            => '',
            'password'         => '',
            'password_confirm' => '',
        );

        if (waRequest::method() == 'post') {
            if (empty($data['login'])) {
                $errors['login'] = _ws('Login is required');
            } else {
                $data['login'] = strtolower(trim($data['login']));
                if (!preg_match('~^[a-z0-9@_\.\-]+$~u', $data['login'])) {
                    $errors['login'] = _ws('Invalid login');
                } else {
                    $user_model = new waUserModel();
                    $another_user = $user_model->select('id')->where("login = s:0 AND id != i:1", array($data['login'], $contact_id))->limit(1)->fetch();
                    if ($another_user) {
                        $errors['login'] = _w('This login is already set for another user');
                    }
                }
            }

            if (empty($data['password'])) {
                $errors['password'] = _ws('This field is required.');
            } else {
                if ($data['password'] != $data['password_confirm']) {
                    $errors['password_confirm'] = _ws('Passwords do not match');
                }
            }
            unset($data['password_confirm']);

            if (!$errors) {

                $token_data = json_decode($this->params['data'], true);

                // For security reasons login and is_user
                // have to be updated directly via model
                $contact_model = new waContactModel();
                $contact_model->updateById($contact_id, array(
                    'login' => $data['login'],
                    'is_user' => 1,
                ));

                // Save password
                $contact = new waContact($contact_id);
                $contact['password'] = $data['password'];
                $contact->save();

                // Save rights
                if (!empty($token_data['full_access'])) {
                    $contact->setRight('webasyst', 'backend', 2);
                } else {
                    $contact->setRight('team', 'backend', 1);
                }

                // Assign to groups
                if (!empty($token_data['groups'])) {
                    $ugm = new waUserGroupsModel();
                    foreach ($token_data['groups'] as $gid) {
                        try {
                            $ug = teamGroup::checkUserGroup($gid, $contact_id);
                            $ug = $ugm->getByField($ug);
                            if (!$ug) {
                                $ugm->add($contact_id, $gid);
                            }
                        } catch (waException $e) {
                        }
                    }
                }

                // Auth as the user
                wa()->getAuth()->auth(array('id' => $contact_id));

                // Delete the token used to access this page
                $app_tokens_model = new waAppTokensModel();
                $app_tokens_model->deleteById($this->params['token']);

                // Redirect to profile editor
                $this->redirect(wa()->getConfig()->getBackendUrl(true) . '?module=profile');
            }
        }

        list($background, $stretch) = webasystLoginLayout::getBackground();

        $this->view->assign('stretch', $stretch);
        $this->view->assign('background', $background);
        $this->view->assign('title_style', wa()->getConfig()->getOption('login_form_title_style'));
        $this->view->assign('title', wa()->getSetting('name', 'Webasyst', 'webasyst'));

        $this->view->assign(array(
            'errors'     => $errors,
            'data'       => $data,
            'background_url' => wa()->getUrl() . 'wa-content/img/backgrounds/bokeh_vivid.jpg',
        ));
        $this->setTemplate('templates/actions/InviteFrontend.html');
    }
}
