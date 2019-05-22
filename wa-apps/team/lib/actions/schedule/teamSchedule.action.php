<?php

class teamScheduleAction extends teamContentViewAction
{
    public function execute()
    {
        $period_start = waRequest::request('start', date('Y-m-01'), waRequest::TYPE_STRING);
        $calendar_id = waRequest::request('calendar');
        $user_id = waRequest::request('user', null, waRequest::TYPE_INT);
        $period = waRequest::request('period', false, waRequest::TYPE_INT);
        $context = ($period) ? "profile" : "calendar";

        $this->view->assign(array(
            'calendars'            => teamCalendar::getCalendars(),
            'users'                => teamHelper::getUsers(),
            'selected_calendar_id' => $calendar_id,
            'selected_user_id'     => $user_id,
            'period_start'         => $period_start,
            'context'              => $context,
        ));
        $this->view->assign(teamCalendar::getHtml($user_id, $calendar_id, $period_start, $period));
    }
}
