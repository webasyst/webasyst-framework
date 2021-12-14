<?php
class teamScheduleIncAction extends teamScheduleAction
{
    public function execute()
    {
        parent::execute();
        if(wa()->whichUI() === '1.3') {
            $this->setTemplate('templates/actions-legacy/schedule/Schedule.inc.html');
        }else{
            $this->setTemplate('templates/actions/schedule/Schedule.inc.html');
        }
    }
}
