<?php

class photosBackendLogController extends waJsonController
{
    public function execute()
    {
        $action = waRequest::get('action_to_log', '', waRequest::TYPE_STRING_TRIM);
        $this->log($action, 1);
    }
}