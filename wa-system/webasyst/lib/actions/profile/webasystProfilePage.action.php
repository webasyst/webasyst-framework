<?php
/**
 * Own profile editor for users who don't have access to Team app.
 */
class webasystProfilePageAction extends waViewAction
{
    public function execute()
    {
        $user = wa()->getUser();
        $user->load();

        /*
         * @event backend_personal_profile
         */
        $params = array(
            'user' => $user,
            'top' => $user->getTopFields(),
        );
        $backend_personal_profile = wa()->event(array('webasyst', 'backend_personal_profile'), $params);

        // Redirect to old Contacts app if user has access to it
        if (wa()->appExists('contacts') && wa()->getUser()->getRights('contacts', 'backend')) {
            wa('contacts', 1)->getResponse()->redirect(wa()->getUrl()."#/contact/{$user['id']}/");
        }

        // User with no access to the Team app - force legacy UI
        waRequest::setParam('force_ui_version', '1.3');

        $can_edit = $this->canEdit($user['id']);
        $this->getContactInfo($can_edit, $user);

        $this->view->assign(array(
            'backend_personal_profile' => $backend_personal_profile,
            'top' => $params['top'],
            'user' => $user,
            'webasyst_id_auth_url' => $this->getWebasystIDAuthUrl($user),
            'customer_center_auth_url' => $this->getCustomerCenterAuthUrl(),
            'webasyst_id_email' => $this->getWebasystIDEmail(),
            'is_connected_to_webasyst_id' => $this->isConnectedToWebasystID(),
            'is_bound_with_webasyst_contact'   => $this->isBoundWithWebasystContact(),
            'user_settings' => (new waContactSettingsModel())->get($user['id'], 'webasyst'),
            'can_edit' => $can_edit,
            'profile_template_path' => wa()->appExists('team') ?  'wa-apps/team/templates/actions/profile/Profile.html' : 'wa-system/webasyst/templates/actions-legacy/profile/ProfilePage.html',
        ));
    }

    /**
     * @param waContact $user
     * @return string
     * @throws waDbException
     * @throws waException
     */
    protected function getWebasystIDAuthUrl($user)
    {
        // if installation is not connected yet
        $m = new waWebasystIDClientManager();
        if (!$m->isConnected()) {
            return '';
        }

        // profile is already bound with webasyst ID
        if ($user->getWebasystContactId() > 0) {
            return '';
        }

        $auth = new waWebasystIDWAAuth();
        return $auth->getUrl();
    }

    /**
     * @return bool
     * @throws waException
     */
    protected function getCustomerCenterAuthUrl()
    {
        $access_token = $this->getWebasystAuthAccessToken();
        if (!$access_token) {
            return '';
        }
        return wa()->getConfig()->getBackendUrl(true) . '?module=profile&action=customer';
    }

    /**
     * Email of webasyst ID contact
     * @return mixed|string
     * @throws waDbException
     * @throws waException
     */
    protected function getWebasystIDEmail()
    {
        $access_token = $this->getWebasystAuthAccessToken();
        if (!$access_token) {
            return '';
        }
        $atm = new waWebasystIDAccessTokenManager();
        $info = $atm->extractTokenInfo($access_token);
        return $info['email'];
    }

    /**
     * Get access token if supports 'auth' scope
     * @return array|mixed
     * @throws waDbException
     * @throws waException
     */
    protected function getWebasystAuthAccessToken()
    {
        $token_params = $this->getUser()->getWebasystTokenParams();
        if ($token_params) {
            $access_token = $token_params['access_token'];
            $atm = new waWebasystIDAccessTokenManager();
            $supports = $atm->isScopeSupported('auth', $access_token);
            if ($supports) {
                return $access_token;
            }
        }
        return [];
    }

    protected function isConnectedToWebasystID()
    {
        return (new waWebasystIDClientManager())->isConnected();
    }

    protected function isBoundWithWebasystContact()
    {
        return (new waContact())->getWebasystContactId() > 0;
    }

    protected function canEdit($user_id)
    {
        return teamUser::canEdit($user_id);
    }

    /** Using $this->id get waContact and save it in $this->contact;
     * Load vars into $this->view specific to waContact. */
    protected function getContactInfo($can_edit, $user)
    {

        $contact = $user;
        $this->view->assign('own_profile', true);


        $this->view->assign('contact', $contact);

        // who created this contact and when
        $this->view->assign('contact_create_time', waDateTime::format('datetime', $contact['create_datetime'], $user->getTimezone()));
        if ($contact['create_contact_id']) {
            try {
                $author = new waContact($contact['create_contact_id']);
                if ($author['name']) {
                    $this->view->assign('author', $author);
                }
            } catch (Exception $e) {
                // Contact not found. Ignore silently.
            }
        }

        // Main contact editor data
        $fieldValues = $contact->load('js', true);
        if (!empty($fieldValues['company_contact_id'])) {
            $m = new waContactModel();
            if (!$m->getById($fieldValues['company_contact_id'])) {
                $fieldValues['company_contact_id'] = 0;
                $this->contact->save(array('company_contact_id' => 0));
            }
        }

        $contactFields = waContactFields::getInfo($contact['is_company'] ? 'company' : 'person', true);

        // Only show fields that are allowed in own profile
        if ($can_edit === 'limited_own_profile') {
            $allowed = array();
            foreach (waContactFields::getAll('person') as $f) {
                if ($f->getParameter('allow_self_edit')) {
                    $allowed[$f->getId()] = true;
                }
            }

            $fieldValues = array_intersect_key($fieldValues, $allowed);
            $contactFields = array_intersect_key($contactFields, $allowed);
        }

        // Normalize field values
        foreach ($contactFields as $field_info) {
            if (is_object($field_info) && $field_info instanceof waContactField) {
                $field_info = $field_info->getInfo();
            }
            if ($field_info['multi'] && isset($fieldValues[$field_info['id']])) {
                $fieldValues[$field_info['id']] = array_values($fieldValues[$field_info['id']]);
            }
            if ($field_info['id'] === 'timezone') {
                // This hack is here rather than correct definition in waContactTimezoneField
                // because of backwards compatibility with older version of Contacts app
                // that does not know nothing about special Timezone field type.
                $contactFields[$field_info['id']]['type'] = 'Timezone';
            }
        }

        $this->view->assign('contactFields', $contactFields);
        $this->view->assign('contactFieldsOrder', array_keys($contactFields));
        $this->view->assign('fieldValues', $fieldValues);

        // Contact categories
        $cm = new waContactCategoriesModel();
        $this->view->assign('contact_categories', array_values($cm->getContactCategories($user['id'])));

    }
}
