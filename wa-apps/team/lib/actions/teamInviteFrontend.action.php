<?php

/**
 * New user signup form. Links to this controller are sent via "invite new user form".
 *
 * THIS IS FRONTEND. wa()->getUser() is not authorized, possibly guest.
 */
class teamInviteFrontendAction extends waViewAction
{
    protected $webasyst_id_auth_result;

    public function __construct(array $token_info, $webasyst_id_auth_result = null)
    {
        parent::__construct($token_info);
        $this->webasyst_id_auth_result = $webasyst_id_auth_result;
    }

    /**
     * @throws waAuthConfirmEmailException
     * @throws waAuthConfirmPhoneException
     * @throws waAuthException
     * @throws waAuthInvalidCredentialsException
     * @throws waDbException
     * @throws waException
     * @throws waRightsException
     */
    public function execute()
    {
        if (wa()->getEnv() != 'frontend' || empty($this->params['contact_id'])) {
            throw new waRightsException();
        }

        // errors to show in UI
        $errors = array();

        // ID of invited contact
        $contact_id = $this->params['contact_id'];

        if ($this->webasyst_id_auth_result) {
            // Has successful response from webasyst ID auth
            if (!empty($this->webasyst_id_auth_result['status'])) {
                if ($this->afterWebasystIDAuth()) {
                    $this->authAsBackendUser($contact_id);  // this method will redirect automatically
                } else {
                    $this->rollbackWebasystIDAuth();        // rollback webasyst ID auth if error happened
                    $errors['login'] = _w("Authorization with Webasyst ID has failed, try to log in with a login name and a password.");
                }
            }
        } else {
            // backend auth forced by webasyst ID, no show dialog - go to webasyst ID right away
            $cm = new waWebasystIDClientManager();
            if ($cm->isBackendAuthForced()) {
                $this->forceWebasystIDAuth();
            }
        }


        $data = waRequest::post('data', array(), 'array');
        $data += array(
            'login'            => '',
            'password'         => '',
            'password_confirm' => '',
        );

        if (waRequest::method() == 'post') {

            if (empty($data['login'])) {
                $errors['login'] = _w('A login name is required.');
            } else {
                $data['login'] = strtolower(trim($data['login']));
                if (!preg_match('~^[a-z0-9@_\.\-]+$~u', $data['login'])) {
                    $errors['login'] = _w('Invalid login name.');
                } else {
                    $user_model = new waUserModel();
                    $another_user = $user_model->select('id')->where("login = s:0 AND id != i:1", array($data['login'], $contact_id))->limit(1)->fetch();
                    if ($another_user) {
                        $errors['login'] = _w('This login is already set for another user');
                    }
                }
            }

            if (empty($data['password'])) {
                $errors['password'] = _w('This is a required field.');
            } elseif ($data['password'] != $data['password_confirm']) {
                $errors['password_confirm'] = _w('Passwords do not match.');
            } elseif (strlen($data['password']) > waAuth::PASSWORD_MAX_LENGTH) {
                $errors['password'] = _w('Specified password is too long.');
            }
            unset($data['password_confirm']);

            if (!$errors) {

                $token_data = json_decode($this->params['data'], true);
                teamHelper::convertToBackendUser($contact_id, $token_data, $data['login'], $data['password']);

                // If there's a waid_invite token, notify WAID server that invite has been accepted locally
                self::waidClientInviteAccept($contact_id);

                // this method will redirect automatically
                $this->authAsBackendUser($contact_id);
            }
        }

        list($background, $stretch) = webasystLoginLayout::getBackground();

        $token_link = waAppTokensModel::getLink($this->params['token']);

        $this->view->assign('stretch', $stretch);
        $this->view->assign('background', $background);
        $this->view->assign('title_style', wa()->getConfig()->getOption('login_form_title_style'));
        $this->view->assign('title', wa()->getSetting('name', 'Webasyst', 'webasyst'));
        $this->view->assign('webasyst_id_auth_result', $this->webasyst_id_auth_result);
        $this->view->assign('backend_url', $this->getConfig()->getBackendUrl(true));

        $this->view->assign(array(
            'errors'     => $errors,
            'data'       => $data,
            'background_url' => wa()->getUrl() . 'wa-content/img/backgrounds/bokeh_vivid.jpg',
            'webasyst_id_auth_url' => $this->getWebasystIDAuthUrl(),
            'token_link' => $token_link
        ));
        if(wa()->whichUI() === '1.3') {
            $this->setTemplate('templates/actions-legacy/invite/InviteFrontend.html');
        }else{
            $this->setTemplate('templates/actions/invite/InviteFrontend.html');
        }
    }

    protected static function waidClientInviteAccept($contact_id)
    {
        $app_tokens_model = new waAppTokensModel();
        $rows = $app_tokens_model->getByField([
            'app_id' => 'team',
            'contact_id' => $contact_id,
            'type' => 'waid_invite',
        ], true);
        $api = null;
        $contact_waid_model = null;
        foreach($rows as $waid_invite) {
            if (strtotime($waid_invite['expire_datetime']) < time()) {
                continue;
            }
            if (!$api) {
                $api = new waWebasystIDApi();
            }
            $webasyst_contact_id = $api->clientInviteAccept($waid_invite['token']);
            if ($webasyst_contact_id) {
                if (!$contact_waid_model) {
                    $contact_waid_model = new waContactWaidModel();
                }
                $contact_waid_model->set($contact_id, $webasyst_contact_id, []);
            }
        }
        if ($rows) {
            $app_tokens_model->deleteByField([
                'app_id' => 'team',
                'contact_id' => $contact_id,
                'type' => 'waid_invite',
            ]);
        }
    }

    protected function forceWebasystIDAuth()
    {
        $auth = new waWebasystIDWAAuth();
        $url = $auth->getInviteAuthUrl($this->params['token']);
        $this->redirect($url);
    }

    protected function getWebasystIDAuthUrl()
    {
        $auth = new waWebasystIDWAAuth();
        if ($auth->isClientConnected()) {
            return $auth->getInviteAuthUrl($this->params['token']);
        }
        return null;
    }

    /**
     * @param string $email
     * @return string|null
     * @throws waException
     */
    protected function generateLogin($email)
    {
        $cm = new waContactModel();
        return $cm->generateLoginByEmail($email);
    }

    protected function generatePassword()
    {
        return waContact::generatePassword();
    }

    /**
     * When successfully authorized by webasyst ID call this method
     * It will convert invited contact to backend user with generated login and password.
     * Notice, that not empty login and password is webasyst backend convention
     */
    protected function afterWebasystIDAuth()
    {
        $contact_id = $this->params['contact_id'];

        $cem = new waContactEmailsModel();
        $emails = $cem->getEmails($contact_id);
        $email = $emails ? reset($emails) : null;
        if (!$email) {
            return false;
        }

        $login = $this->generateLogin($email['value']);
        if (!$login) {
            return false;
        }

        $password = $this->generatePassword();

        // For security reasons login and is_user
        // have to be updated directly via model
        $contact_model = new waContactModel();
        $contact_model->updateById($contact_id, array(
            'login' => $login,
            'is_user' => 1,
        ));

        $token_data = json_decode($this->params['data'], true);
        teamHelper::convertToBackendUser($contact_id, $token_data, $login, $password);

        return true;
    }

    protected function rollbackWebasystIDAuth()
    {
        $contact = new waContact($this->params['contact_id']);
        $contact->unbindWaid();
    }

    /**
     * Delete invite token from DB
     */
    protected function invalidateInviteToken()
    {
        $app_tokens_model = new waAppTokensModel();
        $app_tokens_model->deleteById($this->params['token']);
    }

    /**
     * Authorize contact id into backend, invalidate invite token and redirect form this page
     * @param int $contact_id
     * @throws waAuthConfirmEmailException
     * @throws waAuthConfirmPhoneException
     * @throws waAuthException
     * @throws waAuthInvalidCredentialsException
     * @throws waException
     */
    protected function authAsBackendUser($contact_id)
    {
        // Auth as the user
        wa()->getAuth()->auth(array('id' => $contact_id));

        // Delete the token used to access this page
        $this->invalidateInviteToken();

        // Redirect to profile editor
        $this->redirect(wa()->getConfig()->getBackendUrl(true) . '?module=profile');
    }
}
