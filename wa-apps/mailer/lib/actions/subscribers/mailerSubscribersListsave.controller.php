<?php
/*
 * Controller to add or edit subscription form
 */
class mailerSubscribersListsaveController extends waJsonController
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }

        $msl = new mailerSubscribeListModel();
        $list_id = waRequest::post('id', 0, 'int');
        $list = waRequest::post('list');
        if (empty($list['name'])) {
            throw new waException('Bad parameter.', 404);
        }
        $list_id = $msl->save($list_id, $list);

        $mfsl = new mailerFormSubscribeListsModel();
        if (isset($list['forms']) && is_array($list['forms'])) {
            $mfsl->updateByListId($list_id, $list['forms']);
        }

        $this->response = array(
            'id' => $list_id,
            'name' => $list['name']
        );
    }
}