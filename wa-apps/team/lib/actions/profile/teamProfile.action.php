<?php

/**
 * User profile page.
 * /team/u/<login>/<tab>/
 * /team/id/<id>/<tab>/
 */
class teamProfileAction extends teamProfileContentViewAction
{
    use teamProfileEditorInfoTrait;
    protected $contact;
    protected $id;

    public function __construct($params = null)
    {
        parent::__construct($params);

        $this->id = $this->profile_contact['id'];
        $this->contact = new waContact($this->id);

        if (!$this->contact || !$this->contact->exists()) {
            throw new waException('Contact not found', 404);
        }
    }

    public function execute()
    {
        waRequest::setParam('id', $this->profile_contact['id']);
        waRequest::setParam('login', $this->profile_contact['login']);
        $this->profile_contact->load();

        $invite = null;
        if ($this->profile_contact['is_user'] == 0) {
            $watm = new waAppTokensModel();
            $invite = $watm->select('expire_datetime')->where("contact_id=".intval($this->profile_contact['id']." AND expire_datetime < '".date('Y-m-d H:i:s')."'"))->fetchAssoc();
        }

        $twasm = new teamWaAppSettingsModel();
        $user_name_format = $twasm->getUserNameDisplayFormat();
        if ($user_name_format !== 'login') {
            $user_name_formatted = $this->profile_contact->getName();
        } else {
            $user_name_formatted = waContactNameField::formatName($this->profile_contact, true);
        }

        $ugm = new waUserGroupsModel();

        $can_edit = $this->canEdit();
        $this->getContactInfo($can_edit);

        $this->view->assign([
            'backend_profile'                  => $this->pluginHook(),
            'user_event'                       => self::getUserEvent($this->profile_contact),
            'top'                              => $this->profile_contact->getTopFields(),
            'tab'                              => waRequest::param('tab', null, waRequest::TYPE_STRING_TRIM),
            'can_view_external_calendars_info' => $this->canViewExternalCalendarsInfo(),
            'can_edit'                         => $can_edit,
            'user'                             => $this->profile_contact,
            'groups'                           => teamHelper::groupRights($ugm->getGroups($this->profile_contact->getId())),
            'user_name_formatted'              => $user_name_formatted,
            'invite'                           => $invite,
            'is_own_profile'                   => $this->isOwnProfile(),
            'is_super_admin'                   => $this->getUser()->isAdmin('webasyst'),
            'save_url'                         => $this->getSaveUrl($can_edit),
            'geocoding'                        => $this->getGeocodingOptions(),
            // webasyst ID related vars
            'is_connected_to_webasyst_id'      => $this->isConnectedToWebasystID(),
            'is_webasyst_id_forced'            => $this->isWebasystIDForced(),
            'webasyst_id_auth_url'             => $this->getWebasystIDAuthUrl(),
            'is_bound_with_webasyst_contact'   => $this->profile_contact->getWebasystContactId() > 0,
            'customer_center_auth_url'         => $this->getCustomerCenterAuthUrl(),
            'webasyst_id_email'                => $this->getWebasystIDEmail(),
        ]);

        $this->view->assign(teamCalendar::getHtml($this->profile_contact['id'], null, null, true));
        $this->view->assign($this->getCreationInfo());

        $this->view->assign($this->getUI20Data());

    }

    protected function getContacts()
    {
        $context = $this->getListContext();
        $hash = 'users';

        if (preg_match('!^group\/\d+$!i', $context)) {
            $hash = trim($context, '/');
        } elseif (substr($context, 0, 7) === 'search/') {
            $query = substr($context, 7);
            $res = teamAutocompleteController::usersAutocomplete($query);

            $ids = waUtils::getFieldValues(is_array($res) ? $res : [], 'id');
            $ids = waUtils::toIntArray($ids);
            $ids = waUtils::dropNotPositive($ids);

            if ($ids) {
                $hash = 'id/' . join(',', $ids);
            } else {
                $hash = '';
            }
        } elseif ($context === 'invited') {
            $invited = teamUsersInvitedAction::getInvited();
            if ($invited) {
                $hash = 'id/' . join(',', array_keys($invited));
            } else {
                $hash = '';
            }
        } elseif ($context == 'inactive') {
            $cm = new waContactModel();
            $ids = $cm->select('id')->where('is_user=-1 AND login IS NOT NULL')->fetchAll('id', true);
            if ($ids) {
                $ids = array_keys($ids);
                $hash = 'id/' . join(',', $ids);
            } else {
                $hash = '';
            }
        }
        if (!$hash) {
            return [];
        }

        return teamUser::getList($hash, array(
            'convert_to_utc' => 'update_datetime',
            'additional_fields' => array(
                'update_datetime' => 'c.create_datetime',
            ),
            'fields' => teamUser::getFields().',_online_status',
        ));
    }

    protected function getCreationInfo()
    {
        $current_user = wa()->getUser();
        $contact_create_time = waDateTime::format('datetime', $this->profile_contact['create_datetime'], $current_user->getTimezone());

        $data = [
            'contact_create_time' => $contact_create_time
        ];

        if ($this->profile_contact['create_contact_id']) {
            $author = new waContact($this->profile_contact['create_contact_id']);
            if ($author->exists() && $author['name']) {
                $data['author'] = $author;
            }
        }

        return $data;
    }

    protected function getUI20Data()
    {
        if (wa()->whichUI($this->getAppId()) != '2.0') {
            return [];
        }
        $profile_data = $this->getEditorProfileData();

        if (isset($profile_data['fieldValues']['socialnetwork'])) {
            $socialnetwork_icons = [
                'twitter' => '<i class="fab fa-twitter text-gray"></i>&nbsp;',
                'vkontakte' => '<i class="fab fa-vk text-gray"></i>&nbsp;',
                'facebook' => '<i class="fab fa-facebook-f text-gray"></i>&nbsp;',
                'linkedin' => '<i class="fab fa-linkedin-in text-gray"></i>&nbsp;'
            ];
            foreach ($profile_data['fieldValues']['socialnetwork'] as $id => $socialnetwork) {
                if(in_array($socialnetwork['ext'], array_keys($socialnetwork_icons))) {
                    $profile_data['fieldValues']['socialnetwork'][$id]['value'] = str_replace('<i class="icon16 '.$socialnetwork['ext'].'"></i>', $socialnetwork_icons[$socialnetwork['ext']], $socialnetwork['value']);
                    if($socialnetwork['ext'] === 'linkedin') {
                       $profile_data['fieldValues']['socialnetwork'][$id]['value'] = $socialnetwork_icons[$socialnetwork['ext']].'<span>'.$socialnetwork['value'].'</span>';
                    }
                }else{
                    $profile_data['fieldValues']['socialnetwork'][$id]['value'] = '<i class="fas fa-comments text-gray"></i>&nbsp;<span>'.$socialnetwork['value'].'</span>';
                }
            }
        }

        if (isset($profile_data['fieldValues']['im'])) {
            $im_icons = [
                'icq' => '<i class="fas fa-comments text-gray"></i>',
                'skype' => '<i class="fab fa-skype text-gray"></i>',
                'jabber' => '<i class="fas fa-comments text-gray"></i>',
                'yahoo' => '<i class="fab fa-yahoo text-gray"></i>',
                'aim' => '<i class="fas fa-comments text-gray"></i>',
                'msn' => '<i class="fas fa-comments text-gray"></i>',
                'telegram' => '<i class="fab fa-telegram-plane text-gray"></i>',
            ];
            foreach ($profile_data['fieldValues']['im'] as $id => $im) {
                if(in_array($im['ext'], array_keys($im_icons))) {
                    $profile_data['fieldValues']['im'][$id]['value'] = $im_icons[$im['ext']].'&nbsp;<span>'.$im['value'].'</span>';
                }else{
                    $profile_data['fieldValues']['im'][$id]['value'] = '<i class="fas fa-comments text-gray"></i>&nbsp;<span>'.$im['value'].'</span>';
                }
            }
        }

        return [
            'contacts' => $this->getContacts(),
            'context' => $this->getListContext(),
            'profile_editor' => [
                'options' => $this->getEditorOptions(),
                'data' => $profile_data
            ],
            'cover_thumbnails' => $this->getCoverThumbnails(),
            'calendar_widget' => teamCalendar::getHtml($this->profile_contact['id'], null, date('Y-m-d'), 7),
            'stats_widget_data' => teamProfileStatsAction::getChartData(waDateTime::date('Y-m-d', strtotime("-14 day")), waDateTime::date('Y-m-d'), 'days', $this->profile_contact['id'])
        ];
    }

    /** Using $this->id get waContact and save it in $this->contact;
     * Load vars into $this->view specific to waContact. */
    protected function getContactInfo($can_edit)
    {
        $user = wa()->getUser();
        if ($this->id == $user->getId()) {
            $this->contact = $user;
            $this->view->assign('own_profile', true);
        } else {
            $this->contact = new waContact($this->id);
            $this->view->assign('own_profile', false);
        }

        $this->view->assign('contact', $this->contact);

        // who created this contact and when
        $this->view->assign('contact_create_time', waDateTime::format('datetime', $this->contact['create_datetime'], $user->getTimezone()));
        if ($this->contact['create_contact_id']) {
            try {
                $author = new waContact($this->contact['create_contact_id']);
                if ($author['name']) {
                    $this->view->assign('author', $author);
                }
            } catch (Exception $e) {
                // Contact not found. Ignore silently.
            }
        }

        $this->view->assign('top', $this->contact->getTopFields());

        // Main contact editor data
        $fieldValues = $this->contact->load('js', true);
        if (!empty($fieldValues['company_contact_id'])) {
            $m = new waContactModel();
            if (!$m->getById($fieldValues['company_contact_id'])) {
                $fieldValues['company_contact_id'] = 0;
                $this->contact->save(array('company_contact_id' => 0));
            }
        }

        $contactFields = waContactFields::getInfo($this->contact['is_company'] ? 'company' : 'person', true);

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
        $this->view->assign('contact_categories', array_values($cm->getContactCategories($this->id)));

    }
}
