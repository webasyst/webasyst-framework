<?php
class teamScheduleEventEditAction extends teamScheduleEventViewAction
{
    public function execute()
    {
        parent::execute();
        $event = $this->getEvent();
        //teamHelper::checkCalendarRights($event['calendar_id'], $event['contact_id']);
    }
}
