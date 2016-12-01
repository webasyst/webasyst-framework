<?php

class teamScheduleEventViewAction extends waViewAction
{
    /**
     * @var teamWaContactEventsModel
     */
    private $em;

    private $event;

    public function execute()
    {
        $event = $this->getEvent();
        $contact_id = $event['contact_id'];

        $contact = new waContact($contact_id);
        $user = array(
            'id'           => $contact_id,
            'name'         => $contact->getName(),
            'photo_url_16' => $contact->getPhoto2x(16),
            'photo_url_32' => $contact->getPhoto2x(32),
        );

        $can_edit = teamUser::canEdit($contact_id) || $contact_id == wa()->getUser()->getId();
        try {
            teamHelper::checkCalendarRights($event['calendar_id'], $event['contact_id']);
        } catch (waException $e) {
            if (!$event['id']) {
                $event['calendar_id'] = null;
                $event['contact_id'] = wa()->getUser()->getId();
            }
            $can_edit = false;
        }

        $ccm = new waContactCalendarsModel();
        $all_calendars = $ccm->get();
        $available_calendars = array();
        if (empty($event['external_calendar_info'])) {
            foreach ($all_calendars as $cid => $c) {
                if (!$c['is_limited'] || teamHelper::hasRights('edit_events_in_calendar.'.$c['id'])) {
                    $available_calendars[$cid] = $c;
                }
            }
//            $users = teamHelper::getUsers();
            $available_users = teamUser::getList('users/all', array(
                'add_item_all' => false,
                'fields'       => 'minimal',
                'order'        => 'name',
                'can_edit'     => true,
            ));

        } else {
            if (!empty($event['calendar_id']) && !empty($all_calendars[$event['calendar_id']])) {
                $available_calendars[$event['calendar_id']] = $all_calendars[$event['calendar_id']];
            }
            $available_users[$contact_id] = $user;
        }

        $this->view->assign(array(
            'all_calendars'       => $all_calendars,
            'available_calendars' => $available_calendars,
            'event'               => $event,
            'user'                => $user,
            'available_users'     => $available_users,
            'can_edit'            => $can_edit,
        ));
    }

    protected function getEvent()
    {
        if ($this->event) {
            return $this->event;
        }

        $id = (int)$this->getRequest()->request('id');
        $post = (array)$this->getRequest()->request('data');
        if ($id <= 0) {
            $id = (int)ifset($post['id']);
        }

        if ($id > 0) {
            $this->event = $this->getExistedEvent($id);
            return $this->event;
        }

        if (empty($post['start']) || empty($post['end'])) {
            throw new waException('Date(s) not found');
        }

        $this->event = $this->getEmptyEvent(array(
            'contact_id'  => !empty($post['contact_id']) ? $post['contact_id'] : wa()->getUser()->getId(),
            'calendar_id' => ifset($post['calendar_id']),
            'start'       => $post['start'],
            'end'         => $post['end']
        ));
        return $this->event;

    }

    private function getExistedEvent($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            throw new waException('Event not found');
        }
        $event = $this->getEventModel()->getEvent($id);
        if (!$event) {
            throw new waException('Event not found');
        }
        $event['external_events'] = teamWaContactEventsModel::workupExternalEvents($event['external_events']);
        if (count($event['external_events']) > 0) {
            $event['external_calendar_info'] = $event['external_events'][0]['calendar'];
        }
        return $event;
    }

    private function getEmptyEvent($default = array())
    {
        return $this->getEventModel()->getEmptyRecord($default);
    }

    protected function getEventModel()
    {
        if (!$this->em) {
            $this->em = new teamWaContactEventsModel();
        }
        return $this->em;
    }
}
