<?php
class teamScheduleIncAction extends teamScheduleAction
{
    public function execute()
    {
        parent::execute();

        $this->setTemplate('templates/actions/schedule/Schedule.inc.html');
    }
}
