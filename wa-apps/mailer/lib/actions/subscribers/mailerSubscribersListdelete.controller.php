<?php
/**
 * Deletes Form
 */

class mailerSubscribersListdeleteController extends waJsonController
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }

        $list_id = waRequest::post('id',0,'int');

        $msl = new mailerSubscribeListModel();
        $msl->deleteById($list_id);

        $ms = new mailerSubscriberModel();
        $ms->updateByField('list_id', $list_id, array('list_id' => 0, 'datetime' => date('Y-m-d H:i:s')), "IGNORE");
        $ms->deleteByField('list_id', $list_id);

        $mfsl = new mailerFormSubscribeListsModel();
        $mfsl->updateByListId($list_id, null);

        $this->response = $list_id;
    }
}