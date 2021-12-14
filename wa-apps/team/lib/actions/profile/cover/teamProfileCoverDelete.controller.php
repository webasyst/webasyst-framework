<?php

class teamProfileCoverDeleteController extends waJsonController
{
    public function execute()
    {
        $photo_ids = $this->getPhotoIds();
        if (!$photo_ids) {
            return;
        }

        $contact = $this->getContact();

        $list = new waContactCoverList($contact->getId());
        $list->delete($photo_ids);
    }

    protected function getContact()
    {
        $id = waRequest::post('id', null, 'int');
        $can_edit = teamUser::canEdit($id);
        if (!$id || !$can_edit) {
            throw new waRightsException(_w('Access denied'));
        }
        return new waContact($id);
    }

    /**
     * @return int[]
     */
    protected function getPhotoIds()
    {
        return waRequest::post('photo_id', [], waRequest::TYPE_ARRAY_INT);
    }
}