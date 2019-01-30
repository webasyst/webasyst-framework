<?php

class teamCalendarExternalParamsModel extends teamParamsModel
{
    protected $relation_id = 'calendar_external_id';
    protected $table = 'team_calendar_external_params';

    public function setToken($id, $token)
    {
        return $this->addOne($id, 'token', $token);
    }

    public function getToken($id)
    {
        $item = $this->getByField(array($this->relation_id => $id, 'name' => 'token'));
        if (!$item) {
            return null;
        }
        return $item['value'];
    }
}
