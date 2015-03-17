<?php

class mailerSendersDeleteController extends waJsonController
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }

        $id = waRequest::post('id');
        if ($id) {
            $sm = new mailerSenderModel();
            $sm->deleteById($id);
        }
    }
}
