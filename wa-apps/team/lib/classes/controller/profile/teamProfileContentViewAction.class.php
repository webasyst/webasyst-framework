<?php

/**
 * Base for teamProfileAction and teamProfileUI20Trait
 */
class teamProfileContentViewAction extends teamContentViewAction
{
    /**
     * @var waContact
     */
    protected $profile_contact;

    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->profile_contact = teamUser::getCurrentProfileContact();
    }

    protected function canEdit()
    {
        return teamUser::canEdit($this->profile_contact->getId());
    }

    public function canViewExternalCalendarsInfo()
    {
        return wa()->getUser()->isAdmin('team') || $this->profile_contact->getId() == wa()->getUser()->getId();
    }

    protected function isOwnProfile()
    {
        return $this->profile_contact->getId() == wa()->getUser()->getId();
    }

    /**
     * @return string
     * @throws waDbException
     * @throws waException
     */
    protected function getWebasystIDAuthUrl()
    {
        // if installation is not connected yet
        $m = new waWebasystIDClientManager();
        if (!$m->isConnected()) {
            return '';
        }

        // only own profile can bind with webasyst ID
        if (!$this->isOwnProfile()) {
            return '';
        }

        // profile is already bound with webasyst ID
        if ($this->profile_contact->getWebasystContactId() > 0) {
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

    protected static function getUserEvent(waContact $user)
    {
        $birh_day_event = self::getBirthDayEvent($user);
        if (!empty($birh_day_event)) {
            return $birh_day_event;
        }

        $cem = new waContactEventsModel();
        return $cem->getEventByContact($user['id'], 1);
    }

    protected static function getAllUserEvents(waContact $user)
    {
        $birh_day_event = self::getBirthDayEvent($user);
        if (!empty($birh_day_event)) {
            $birh_day_event = [$birh_day_event];
        }
        $cem = new waContactEventsModel();
        $events = $cem->getEventByContact($user['id']);
        return array_merge($birh_day_event, $events);
    }

    protected static function getBirthDayEvent(waContact $user)
    {
        if ($user->get('birth_day') == waDateTime::format('j', null, waDateTime::getDefaultTimezone()) && $user->get('birth_month') == waDateTime::format('n', null, waDateTime::getDefaultTimezone())) {
            if (wa('team')->whichUI('team')  !== '1.3') {
                return [
                    'id'          => 0,
                    'calendar_id' => 'birthday',
                    'summary'     => _w('Birthday'),
                    'bg_color'    => '#e43a89',
                    'font_color'  => '#ffffff',
                ];
            }
            return [
                'id'            => 0,
                'calendar_id'   => 'birthday',
                'summary'       => _w('Birthday'),
                'calendar_name' => _w('Birthday'),
                'bg_color'      => 'white',
                'font_color'    => 'black',
            ];
        }
        return [];
    }

    protected function pluginHook()
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
            'contact_id' => $this->profile_contact['id'],
            'contact'    => $this->profile_contact,
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

    protected function getListContext()
    {
        $list = $this->getRequest()->get('list', '', waRequest::TYPE_STRING_TRIM);
        $list = trim($list, '/');
        return $list;
    }

    /**
     * @return bool
     * @throws waDbException
     * @throws waException
     */
    protected function isConnectedToWebasystID()
    {
        $m = new waWebasystIDClientManager();
        return $m->isConnected();
    }

    protected function isWebasystIDForced()
    {
        $cm = new waWebasystIDClientManager();
        return $cm->isBackendAuthForced();
    }
}
