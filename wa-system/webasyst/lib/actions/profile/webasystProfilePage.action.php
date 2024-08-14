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
        //waRequest::setParam('force_ui_version', '1.3');

        $can_edit = $this->canEdit($user['id']);
        $this->getContactInfo($can_edit, $user);

        if (wa()->appExists('team')) {
            $profile_template_path = wa()->getAppPath('templates/actions/profile/Profile.html', 'team');
            $twasm = new teamWaAppSettingsModel();
            $user_name_format = $twasm->getUserNameDisplayFormat();
        } else {
            $profile_template_path = wa()->getAppPath('templates/actions' . ((wa()->whichUI() === '1.3') ? '-legacy' : '') . '/profile/ProfilePage.html', 'webasyst');
            $user_name_format = 'login';
        }

        if ($user_name_format !== 'login') {
            $user_name_formatted = $user->getName();
        } else {
            $user_name_formatted = waContactNameField::formatName($user, true);
        }

        $this->view->assign($this->getUI20Data());

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
            'profile_template_path' => $profile_template_path,
            'user_name_formatted' => $user_name_formatted,
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
        try {
            if (wa()->appExists('team')) {
                return teamUser::canEdit($user_id);
            }
        } catch (waException $e) {
        }
        return $user_id == wa()->getUser()->getId();
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

    protected function getUI20Data()
    {
        if (wa()->whichUI($this->getAppId()) != '2.0') {
            return [];
        }

        $profile_data = $this->view->getVars();
        unset($profile_data['own_profile'],$profile_data['contact'],$profile_data['contact_create_time'],$profile_data['author']);

        $profile_contact = wa()->getUser();

        if (isset($profile_data['fieldValues']['socialnetwork'])) {
            $socialnetwork_icons = [
                'instagram' => '<span class="t-profile-im-icon"><i class="fab fa-instagram" style="color: #FF2565;"></i></span>',
                'twitter' => '<span class="t-profile-im-icon"><i class="fab fa-twitter" style="color: #29A6F3;"></i></span>',
                'vkontakte' => '<span class="t-profile-im-icon"><i class="fab fa-vk" style="color: #2787F5;"></i></span>',
                'facebook' => '<span class="t-profile-im-icon"><i class="fab fa-facebook-f" style="color: #1877F2;"></i></span>',
                'linkedin' => '<span class="t-profile-im-icon"><i class="fab fa-linkedin-in" style="color: #0078B6;"></i></span>'
            ];
            foreach ($profile_data['fieldValues']['socialnetwork'] as $id => $socialnetwork) {
                if(in_array($socialnetwork['ext'], array_keys($socialnetwork_icons))) {
                    $profile_data['fieldValues']['socialnetwork'][$id]['value'] = str_replace('<i class="icon16 '.$socialnetwork['ext'].'"></i>', $socialnetwork_icons[$socialnetwork['ext']], $socialnetwork['value']);
                    if($socialnetwork['ext'] === 'linkedin') {
                        $profile_data['fieldValues']['socialnetwork'][$id]['value'] = $socialnetwork_icons[$socialnetwork['ext']].$socialnetwork['value'];
                    }
                }else{
                    $profile_data['fieldValues']['socialnetwork'][$id]['value'] = '<span class="t-profile-im-icon"><i class="fas fa-users" style="color: #5757D6;"></i></span>'.$socialnetwork['value'];
                }
            }
        }

        if (isset($profile_data['fieldValues']['im'])) {
            $im_icons = [
                'whatsapp' => '<i class="fab fa-whatsapp" style="color: #29C54D;"></i>',
                'telegram' => '<i class="fab fa-telegram-plane" style="color: #279FDA;"></i>',
                'skype' => '<i class="fab fa-skype" style="color: #28A8EA;"></i>',
                'facebook' => '<i class="fab fa-facebook-messenger" style="color: #0084FF;"></i>',
                'viber' => '<i class="fab fa-viber" style="color: #7360F4;"></i>',
                'discord' => '<i class="fab fa-discord" style="color: #404EED;"></i>',
                'slack' => '<i class="fab fa-slack" style="color: #A436AB;"></i>',
                'jabber' => '<i class="fas fa-comments" style="color: #d64c1e;"></i>',
                'yahoo' => '<i class="fab fa-yahoo" style="color: #581cc7;"></i>',
                'aim' => '<i class="fas fa-comments text-black"></i>',
                'msn' => '<i class="fas fa-comments" style="color: #333;"></i>',
            ];
            foreach ($profile_data['fieldValues']['im'] as $id => $im) {
                if(in_array($im['ext'], array_keys($im_icons))) {
                    $profile_data['fieldValues']['im'][$id]['value'] = $im_icons[$im['ext']].'&nbsp;<span>'.$im['value'].'</span>';
                    $profile_data['fieldValues']['im'][$id]['icon'] = $im_icons[$im['ext']];
                }else{
                    $profile_data['fieldValues']['im'][$id]['value'] = '<i class="fas fa-comments text-gray"></i>&nbsp;<span>'.$im['value'].'</span>';
                    $profile_data['fieldValues']['im'][$id]['icon'] = '<i class="fas fa-comments text-purple"></i>';
                }
            }
        }

        return [
            'user_settings' => (new waContactSettingsModel())->get($profile_contact['id'], 'webasyst'),
            'profile_editor' => [
                'options' => $this->getEditorOptions(),
                'data' => $profile_data
            ]
        ];
    }

    protected function getEditorOptions()
    {
        if (wa()->appExists('team')) {
            $tasm = new teamWaAppSettingsModel();
            $map_options = $tasm->getGeocodingOptions();
        } else {
            $map_options = array(
                'type' => '',
                'key' => '',
            );
        }
        $wa_app_url = wa()->getConfig()->getBackendUrl(true);

        return [
            'saveUrl' => $wa_app_url.'?module=profile&action=save',
            'contact_id' => $this->getUserId(),
            'current_user_id' => $this->getUserId(),
            'justCreated' => false,
            'geocoding' => $map_options,
            'wa_app_url' => $wa_app_url,
            'contactType' => $this->getUser()['is_company'] ? 'company' : 'person'
        ];
    }
}
