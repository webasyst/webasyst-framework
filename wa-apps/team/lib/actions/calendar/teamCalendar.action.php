<?php

class teamCalendarAction extends waViewAction
{
    public static $right_model = null;

    public function execute()
    {
        if (!wa()->getUser()->isAdmin($this->getAppId())) {
            throw new waRightsException();
        }
        $calendar_id = waRequest::request('id', null, waRequest::TYPE_INT);
        if ($calendar_id) {
            $ccm = new waContactCalendarsModel();
            $cem = new waContactEventsModel();
            $calendar = $ccm->getById($calendar_id);
            if (!$calendar_id || !$calendar) {
                throw new waException('Calendar not found');
            }
            $event_count = $cem->select('COUNT(*) cnt')->where("calendar_id=$calendar_id")->fetchField('cnt');
        } else {
            $calendar = array(
                'id'             => null,
                'name'           => null,
                'bg_color'       => null,
                'font_color'     => null,
                'sort'           => null,
                'is_limited'     => null,
                'default_status' => null,
            );
            $event_count = 0;
        }

        $groups = teamHelper::getVisibleGroups();
        $locations = teamHelper::getVisibleLocations();
        foreach ($groups as &$g) {
            $this->getGroupRights($g, $calendar);
        }
        foreach ($locations as &$g) {
            $this->getGroupRights($g, $calendar);
        }
        unset($g);

        $this->view->assign(array(
            'calendar'    => $calendar,
            'event_count' => $event_count,
            'groups'      => $groups,
            'locations'   => $locations,
        ));
    }

    protected function getGroupRights(&$group, $calendar)
    {
        $right_model = self::$right_model ? self::$right_model : new waContactRightsModel();
        $group['rights'] = $right_model->get(-$group['id'], 'team', 'backend');
        if ($group['rights'] == 0) {
            $group['rights'] = -1;
        } elseif ($group['rights'] == 1) {
            $group['rights'] = $right_model->get(-$group['id'], 'team', 'edit_events_in_calendar.all');
            if ($group['rights'] == 1) {
                $group['rights'] = 2;
            } elseif ($group['rights'] == 0 && $calendar['id']) {
                $group['rights'] = $right_model->get(
                    -$group['id'],
                    'team',
                    'edit_events_in_calendar.'.$calendar['id']
                );
            }
        }
    }
}
