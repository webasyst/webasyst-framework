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
}
