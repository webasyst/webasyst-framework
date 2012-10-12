<?php

class photosStackUnmakeController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::get('id', null, waRequest::TYPE_INT);
        if ($id) {
            $photo_rights_model = new photosPhotoRightsModel();
            if (!$photo_rights_model->checkRights($id, true)) {
                throw new waException(_w("You don't have sufficient access rights"));
            }
            $photo_model = new photosPhotoModel();
            $photo_model->unstack($id);
            $this->log('photos_unstack', 1);
        }
    }
}