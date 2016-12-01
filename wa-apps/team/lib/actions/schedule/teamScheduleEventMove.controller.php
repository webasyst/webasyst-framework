<?php

/**
 * Drag-and-drop of events in calendar table view moves their start date.
 */
class teamScheduleEventMoveController extends waJsonController
{
    /**
     * @var teamWaContactEventsModel
     */
    private $em;

    public function execute()
    {
        $new_start_date = $this->getNewStartDate();

        $event = $this->getEvent();
        teamHelper::checkCalendarRights($event['calendar_id'], $event['contact_id']);

        $start_date = explode(' ', $event['start']);
        $start_date = $start_date[0];

        $days_diff = round((strtotime($new_start_date) - strtotime($start_date)) / 3600 / 24);

        $this->getEventModel()->moveEventDates($event['id'], $days_diff);
    }

    protected function getEvent()
    {
        $id = $this->getId();
        $event = $this->getEventModel()->getById($id);
        if (!$event) {
            $this->notFound();
        }
        return $event;
    }

    protected function getEventModel()
    {
        if (!$this->em) {
            $this->em = new teamWaContactEventsModel();
        }
        return $this->em;
    }

    private function getId()
    {
        $id = (int) $this->getRequest()->post('id');
        if ($id <= 0) {
            $this->notFound();
        }
        return $id;
    }

    private function getNewStartDate()
    {
        $start = (string) $this->getRequest()->post('start');
        if (!strtotime($start)) {
            throw new waException('Bad start date');
        }
        return $start;
    }

    private function notFound()
    {
        throw new waException('Event not found', 404);
    }
}
