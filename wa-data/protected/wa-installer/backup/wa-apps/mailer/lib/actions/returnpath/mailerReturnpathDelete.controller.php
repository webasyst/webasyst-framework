<?php

class mailerReturnpathDeleteController extends waJsonController
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }

        $id = waRequest::post('id');
        if ($id && !mailerHelper::isReturnPathAlive($id)) {
            $sm = new mailerReturnPathModel();
            $sm->deleteById($id);
        }
    }
}

