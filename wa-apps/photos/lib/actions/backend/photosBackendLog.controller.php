<?php

class photosBackendLogController extends waJsonController
{
    public function execute()
    {
        $action = waRequest::get('action_to_log', '', waRequest::TYPE_STRING_TRIM);
        $params = null;
        if ($action == 'photos_upload') {
            $count = waRequest::request('count', 0, 'int');
            $this->response = _w('Uploaded %d photo', 'Uploaded %d photos', $count);
            $params = implode(',', waRequest::get('ids'));
        }
        $this->logAction($action, $params);
    }
}