<?php

class teamCalendarExternalTokenInvalidException extends waException
{
    protected $message = 'Token is invalid';

    protected $params;

    public function setParams($params)
    {
        $this->params = $params;
    }

    public function getParams()
    {
        return $this->params;
    }
}
