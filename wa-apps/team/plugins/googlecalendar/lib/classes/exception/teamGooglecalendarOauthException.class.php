<?php

class teamGooglecalendarOauthException extends waException
{
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
