<?php

class teamScheduleSettingsAction extends waViewAction
{
    public function execute()
    {
        $current_user = teamUser::getCurrentProfileContact();

        /**
         * @event backend_schedule_settings
         * @params array[string]waContact $params['current_user'] Current user observed calendar (schedule)
         * @return array[string][string]string $return[%plugin_id%]['top'] Insert own html block in settings dialog
         * @return array[string][string]string $return[%plugin_id%]['li'] Insert html inside <li> in settings dialog
         * @return array[string][string]string $return[%plugin_id%]['bottom'] Insert own html in bottom in settings dialog
         */
        $params = array(
            'current_user' => $current_user,
        );
        $backend_schedule_settings = wa()->event('backend_schedule_settings', $params);

        $this->view->assign(array(
            'calendars' => $this->getCalendars($current_user->getId()),
            'is_own_profile' => $current_user->getId() == wa()->getUser()->getId(),
            'backend_schedule_settings' => $backend_schedule_settings
        ));
    }

    public function getCalendars($contact_id)
    {
        $cem = new teamCalendarExternalModel();

        $wa_user_id = wa()->getUser()->getId();

        $wa_calendars_ids = array();
        $calendars = $cem->getCalendars($contact_id);
        foreach ($calendars as $calendar) {
            $wa_calendars_ids[] = $calendar['calendar_id'];
        }

        $ccm = new waContactCalendarsModel();
        $wa_calendars = $ccm->getById($wa_calendars_ids);
        $empty_wa_calendar = $ccm->getEmptyRow();

        foreach ($calendars as &$calendar) {
            $plugin = teamCalendarExternalPlugin::factoryByCalendar($calendar);
            $calendar['plugin'] = array(
                'name' => $plugin->getName(),
                'account_info_html' => $plugin->getAccountInfoHtml(array('action' => 'schedule_settings')),
                'icon' => $plugin->getIconUrl()
            );
            $calendar['name'] = $plugin->getCalendarName();
            $calendar['is_connected'] = $plugin->isConnected();
            $calendar['is_mapped'] = $plugin->isMapped();
            $calendar['calendar'] = ifset($wa_calendars[$calendar['calendar_id']], $empty_wa_calendar);
            $calendar['is_own'] = $calendar['contact_id'] == $wa_user_id;
        }
        unset($calendar);

        return $calendars;
    }
}
