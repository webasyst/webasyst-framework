<?php

class photosStackPhotoMoveController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::post('id', null, waRequest::TYPE_INT);
        $before_id = waRequest::post('before_id', 0, waRequest::TYPE_INT);

        if ($id) {
            $photo_rights_model = new photosPhotoRightsModel();
            if (!$photo_rights_model->checkRights($id, true)) {
                throw new waException(_w("You don't have sufficient access rights"));
            }
            $photo_model = new photosPhotoModel();
            $photo_model->moveStackSort($id, $before_id);
            $photo = $photo_model->getById($id);

            if ($stack = $photo_model->getStack($id, array('thumb' => true))) {
                $this->response['stack'] = $stack;
            }
        }
    }
}