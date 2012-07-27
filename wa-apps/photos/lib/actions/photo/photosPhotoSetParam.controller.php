<?php

class photosPhotoSetParamController extends waJsonController
{
    private $availableParams = array(
        'maximize_photo'
    );

    public function execute()
    {
        //TODO: delete this class
        $name = waRequest::post('name', '', waRequest::TYPE_STRING_TRIM);
        if (in_array($name, $this->availableParams) === false) {
            throw new Exception("Can't set param: unknown param");
        }

        $id  = waRequest::post('id', null, waRequest::TYPE_INT);
        if (!$id) {
            throw new Exception("Can't set param");
        }

        $photo_rights_model = new photosPhotoRightsModel();
        if (!$photo_rights_model->checkRights($id, true)) {
            throw new waException(_w("You don't have sufficient access rights"));
        }

        $value = waRequest::post('value', false, waRequest::TYPE_STRING_TRIM) ? 1 : 0;

        $app = $this->getApp();
        $user = waSystem::getInstance()->getUser();
        $user->setSettings($app, $name, $value);
    }
}