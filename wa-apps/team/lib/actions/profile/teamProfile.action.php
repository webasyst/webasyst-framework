<?php

/**
 * User profile page.
 * /team/u/<login>/<tab>/
 * /team/id/<id>/<tab>/
 */
class teamProfileAction extends teamContentViewAction
{
    public function execute()
    {
        $user = teamUser::getCurrentProfileContact();
        waRequest::setParam('id', $user['id']);
        waRequest::setParam('login', $user['login']);
        $user->load();

        $invite = null;
        if ($user['is_user'] == 0) {
            $watm = new waAppTokensModel();
            $invite = $watm->select('expire_datetime')->where("contact_id=".intval($user['id']." AND expire_datetime < '".date('Y-m-d H:i:s')."'"))->fetchAssoc();
        }

        $twasm = new teamWaAppSettingsModel();
        $user_name_format = $twasm->getUserNameDisplayFormat();
        if ($user_name_format !== 'login') {
            $user_name_formatted = $user->getName();
        } else {
            $user_name_formatted = waContactNameField::formatName($user, true);
        }

        $ugm = new waUserGroupsModel();
        $this->view->assign(array(
            'backend_profile'                  => $this->pluginHook($user),
            'user_event'                       => self::getUserEvent($user),
            'top'                              => $user->getTopFields(),
            'tab'                              => waRequest::param('tab', null, waRequest::TYPE_STRING_TRIM),
            'can_view_external_calendars_info' => $this->canViewExternalCalendarsInfo($user),
            'can_edit'                         => teamUser::canEdit($user->getId()),
            'user'                             => $user,
            'groups'                           => teamHelper::groupRights($ugm->getGroups($user->getId())),
            // teamHelper::getVisibleGroups($user),
            'user_name_formatted'              => $user_name_formatted,
            'invite'                           => $invite,
            'is_own_profile'                   => $this->isOwnProfile($user),
            'webasyst_id_auth_url'             => $this->getWebasystIDAuthUrl($user),
            'is_super_admin'                   => $this->getUser()->isAdmin('webasyst'),
            'customer_center_auth_url'         => $this->getCustomerCenterAuthUrl(),
            'webasyst_id_email'                => $this->getWebasystIDEmail()
        ));
        $this->view->assign(teamCalendar::getHtml($user['id'], null, null, true));
    }

    public function canViewExternalCalendarsInfo(waContact $user)
    {
        return wa()->getUser()->isAdmin('team') || $user->getId() == wa()->getUser()->getId();
    }

    protected static function getUserEvent($user)
    {
        if ($user->get('birth_day') == waDateTime::format('j') && $user->get('birth_month') == waDateTime::format('n')) {
            return array(
                'id'          => 0,
                'calendar_id' => 'birthday',
                'summary'     => _w('Birthday'),
                'bg_color'    => 'white',
                'font_color'  => 'black'
            );
        } else {
            $cem = new waContactEventsModel();
            return $cem->getEventByContact($user['id'], 1);
        }
    }

    protected function pluginHook($user)
    {
        /*
         * @event backend_profile
         * @return array[string]array $return[%plugin_id%] array of html output
         * @return array[string][string]string $return[%plugin_id%]['header_links_li'] html output
         * @return array[string][string]string $return[%plugin_id%]['before_header'] html output
         * @return array[string][string]string $return[%plugin_id%]['header'] html output
         * @return array[string][string]string $return[%plugin_id%]['after_header'] html output
         * @return array[string][string]string $return[%plugin_id%]['before_top'] html output
         * @return array[string][string]string $return[%plugin_id%]['after_top'] html output
         * @return array[string][string]string $return[%plugin_id%]['photo'] html output
         */
        $backend_profile_params = array(
            'contact_id' => $user['id'],
            'contact'    => $user,
        );
        return wa('team')->event('backend_profile', $backend_profile_params, array(
            'header_links_li',
            'before_header',
            'header',
            'after_header',
            'before_top',
            'after_top',
            'photo',
        ));
    }

    protected function isOwnProfile($user)
    {
        return $user instanceof waContact && $user->getId() == wa()->getUser()->getId();
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

        // only own profile can bind with webasyst ID
        if (!$this->isOwnProfile($user)) {
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
}
