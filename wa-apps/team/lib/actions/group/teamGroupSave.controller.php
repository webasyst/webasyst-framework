<?php
class teamGroupSaveController extends waJsonController
{
    public function execute()
    {
        $post_data = waRequest::post('data', array(), waRequest::TYPE_ARRAY_TRIM);
        $group_id = ifset($post_data['id']);

        $gm = new teamWaGroupModel();

        if ($group_id) {

            if (!teamHelper::hasRights('manage_group.'.$group_id)) {
                throw new waRightsException();
            }
            $group = $gm->getById($group_id);
            if (!$group) {
                throw new waException('Group not found');
            }
            unset($post_data['cnt']);
            $gm->updateGroup($group_id, $post_data);

            $this->logAction('group_edit', $group_id);

        } else {

            if (!teamHelper::hasRights('add_groups')) {
                throw new waRightsException();
            }
            unset($post_data['id']);
            $post_data['cnt'] = 0;
            $group_id = $gm->addGroup($post_data);

            $this->logAction('group_add', $group_id);

            if (!teamHelper::hasRights('manage_group.'.$group_id)) {
                wa()->getUser()->setRight('team', 'manage_group.'.$group_id, 1);
            }
        }
        $this->response = array(
            'id' => $group_id,
            'url' => teamHelper::getUrl('id', $group_id),
        );
    }
}
