<?php
/**
 * Various actions available to admin from access page and contact profile access tab.
 */
class teamAccessSaveActions extends waJsonActions
{
    protected $id;
    /** @var waContact */
    protected $contact;

    protected function preExecute()
    {
        if (!wa()->getUser()->isAdmin()) {
            throw new waRightsException(_w('Access denied'));
        }
        if (waRequest::getMethod() != 'post') {
            throw new waRightsException('only POST method is allowed');
        }

        $this->id = waRequest::request('id', 0, 'int');
        if (!$this->id) {
            throw new waException('no id specified', 400);
        }
        $this->response = true;
        if ($this->action == 'rights' && $this->id < 0) {
            $this->contact = null;
        } else {
            $this->contact = new waContact($this->id);
            $this->contact->getName(); // load data and 404 if not found
        }
    }

    /** Main access enabled/disabled toggle in profile access tab. */
    protected function banAction()
    {
        $this->contact['is_user'] = -1;
        $this->saveContact();

        $reason = $this->getRequest()->post('text', '', waRequest::TYPE_STRING_TRIM);

        $log_model = new waLogModel();
        $log_item = $log_model->select('*')->where(
            "subject_contact_id = i:id AND action = 'access_disable'",
            array('id' => $this->contact['id'])
        )->order('id DESC')->limit(1)->fetch();

        if ($log_item && strlen($reason) > 0) {
            $log_item_params = [];
            if ($log_item['params']) {
                $log_item_params = json_decode($log_item['params'], true);
                if (!is_array($log_item_params)) {
                    $log_item_params = [];
                }
            }
            $log_item_params['reason'] = $reason;
            $log_model->updateById($log_item['id'], [
                'params' => json_encode($log_item_params)
            ]);
        }

        $this->response = array(
            'access_disable_msg' => teamProfileAccessAction::getAccessDisableMsg($this->contact),
        );
    }

    /** Main access enabled/disabled toggle in profile access tab. */
    protected function unbanAction()
    {
        $this->contact['is_user'] = $this->contact['login'] ? 1 : 0;
        $this->saveContact();
    }

    /** Turn a contact into a user. */
    protected function makeuserAction()
    {
        $this->contact['is_user'] = 1;
        $this->saveContact();
    }

    protected function grantAction()
    {
        $login = $this->validatedLogin();
        $password = $this->validatedPassword();
        if (!$this->errors) {
            $this->contact['is_user'] = 1;
            $this->contact['login'] = $login;
            $this->contact['password'] = $password;
            $this->saveContact();

            // set rights right away
            if ($this->getRequest()->post('set_rights')) {
                $this->rightsAction();
            }
        }
    }

    /** Saves "Backend: no access" state in profile tab */
    protected function revokeAction()
    {
        waUser::revokeUser($this->id, false);
    }

    /** Form in profile tab that changes user ligin */
    protected function loginAction()
    {
        $login = $this->validatedLogin();
        if (!$this->errors) {
            $this->contact['login'] = $login;
            $this->saveContact();
        }
    }

    /** Form in profile tab that changes password */
    protected function passwordAction()
    {
        $password = $this->validatedPassword();
        if (!$this->errors) {
            $this->contact['password'] = $password;
            $this->saveContact();
        }
    }

    /** Managing user API tokens */
    protected function apiAction()
    {
        $api_token_model = new waApiTokensModel();

        $action = waRequest::post('action', null, waRequest::TYPE_STRING_TRIM);
        $token_id = waRequest::post('token_id', null, waRequest::TYPE_STRING_TRIM);

        $available_actions = array('remove');

        if (!in_array($action, $available_actions)) {
            return $this->errors[] = _w('Unknown action');
        }

        if ($action === 'remove' && !$token_id) {
            return $this->errors[] = _w('The token was not transferred.');
        } else {
            return $api_token_model->deleteByField(array('contact_id' => $this->contact->getId(), 'token' => $token_id));
        }
    }

    /** Helper to validate login from POST */
    protected function validatedLogin()
    {
        $login = trim(urldecode(waRequest::post('login', '', 'string_trim')));
        if (strlen($login) <= 0) {
            $this->errors[] = _w('Login is required.');
            return null;
        }

        $user_model = new waUserModel();
        $another_user = $user_model->select('id,name')->where("login = s:0 AND id != i:1", array($login, $this->id))->limit(1)->fetch();
        if ($another_user) {
            $another_user['login'] = $login;
            $this->errors[] = sprintf_wp(
                'This login name is already being used by user %s.',
                sprintf(
                    '<a href="%s">%s</a>',
                    teamUser::link($another_user),
                    htmlspecialchars($another_user['name'])
                )
            );
            return null;
        }

        return $login;
    }

    /** Helper to validate password from POST */
    protected function validatedPassword()
    {
        $password = waRequest::post('password', '', 'string');
        $password_confirmation = waRequest::post('confirm_password', '', 'string');
        if (strlen($password) <= 0) {
            $this->errors[] = _w('Password must not be empty.');
            return null;
        }
        if ($password !== $password_confirmation) {
            $this->errors[] = _w('Passwords do not match.');
            return null;
        }
        if (strlen($password) > waAuth::PASSWORD_MAX_LENGTH) {
            $this->errors[] = _w('Specified password is too long.');
            return null;
        }
        return $password;
    }

    /** Helper to validate contact save */
    protected function saveContact()
    {
        $r = $this->contact->save();
        if ($r !== 0) {
            $this->errors = $r;
        }
    }

    /** Modify groups of a single user. Used in profile tab. */
    protected function savegroupsAction()
    {
        $groups = waRequest::post('groups', array(), 'array_int');

        $ugm = new waUserGroupsModel();
        if (waRequest::request('set')) {
            $ugm->delete($this->id);
        }
        if ($groups) {
            $ugm->add(array_map(wa_lambda('$gid', 'return array('.$this->id.', $gid);'), $groups));
        }

        $gm = new waGroupModel();
        $counters = $gm->select('id,cnt')->where("type='group'")->fetchAll('id', true);

        $this->response = array(
            'counters' => $counters,
        );
    }

    /** Save per-app backend access rights.
     *  Used in profile tab, as well as in a separate Access page. */
    protected function rightsAction()
    {
        // load parameters from POST
        $app_id = waRequest::post('app_id', '', 'string');
        $name = waRequest::post('name', '', 'string');
        $value = waRequest::post('value', 0, 'int');
        if (!$name && !$value) {
            $values = waRequest::post('app', null, 'array');
            if (!$values) {
                throw new waException('Bad values for access rights.');
            }
        } else {
            $values = array($name => $value);
        }

        $right_model = new waContactRightsModel();
        $is_admin = $right_model->get($this->id, 'webasyst', 'backend', false);

        if ($is_admin && $app_id != 'webasyst') {
            throw new waException('Cannot change application rights for global admin.');
        }

        $has_backend_access_old = $this->hasBackendAccess($this->id);

        // If contact used to have limited access and we're changing global admin privileges,
        // then need to notify all applications to remove their custom access records.
        if (!$is_admin && $app_id == 'webasyst' && $name == 'backend') {
            self::removeCustomAccessRights($this->id);
        }

        // Initialize RightConfig of an app
        $right_config = null;
        $class_name = wa($app_id)->getConfig()->getPrefix()."RightConfig";
        if (class_exists($class_name)) {
            $right_config = new $class_name();
            if (!empty($values['backend']) && $values['backend'] == 1) {
                // Default access rights when user receives access to app
                $values += (array) $right_config->setDefaultRights($this->id);
            }
        }

        // Update $app_id access records
        foreach ($values as $name => $value) {
            if ($right_config && $right_config->setRights($this->id, $name, $value)) {
                // If we've got response from custom rights config, then no need to update main rights table
                continue;
            }

            $right_model->save($this->id, $app_id, $name, $value);
        }

        // Make sure contact is a user
        $has_backend_access_new = $this->hasBackendAccess($this->id);
        if ($has_backend_access_new && $this->contact && $this->contact['is_user'] == 0) {
            $this->contact->save(array(
                'is_user' => 1,
            ));
        }

        // Log backend access change
        if ($has_backend_access_new !== $has_backend_access_old) {
            if ($has_backend_access_new) {
                $this->logAction("grant_backend_access", null, $this->id);
            } else {
                $this->logAction("revoke_backend_access", null, $this->id);
            }
        }
    }

    protected static function removeCustomAccessRights($contact_id)
    {
        foreach (wa()->getApps() as $aid => $app) {
            if (empty($app['rights'])) {
                continue;
            }
            try {
                $app_config = SystemConfig::getAppConfig($aid);
                $class_name = $app_config->getPrefix()."RightConfig";
                $file_path = $app_config->getAppPath('lib/config/'.$class_name.".class.php");
                if (!file_exists($file_path)) {
                    continue;
                }

                waSystem::getInstance($aid, $app_config);
                $right_config = new $class_name();
                $right_config->clearRights($contact_id);
            } catch (Exception $e) {
                // silently ignore applications errors
            }
        }
    }

    public static function hasBackendAccess($contact_id)
    {
        $rm = new waContactRightsModel();
        $access = $rm->getApps(-$contact_id, 'backend', true, false);
        return !empty($access['webasyst']) || array_intersect_key($access, wa()->getApps());
    }
}
