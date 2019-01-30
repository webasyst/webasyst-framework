<?php

class waContactCalendarsModel extends waModel
{
    protected $table = 'wa_contact_calendars';

    public function get()
    {
        return $this->select('*')->order('sort')->fetchAll('id');
    }
}
