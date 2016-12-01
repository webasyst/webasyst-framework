<?php

class teamCalendarExternalAuthorizeFailedException extends waException
{
    protected $params;

    public function getParams()
    {
        return $this->params;
    }

    public function setParams($params)
    {
        $this->params = $params;
    }
}
