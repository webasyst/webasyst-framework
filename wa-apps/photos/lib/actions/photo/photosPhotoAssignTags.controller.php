<?php

class photosPhotoAssignTagsController extends waJsonController
{
    public function execute()
    {
        $photo_id = waRequest::post('photo_id', array(), waRequest::TYPE_ARRAY_INT);
        $one_photo = waRequest::post('one_photo', 0, waRequest::TYPE_INT);
        $tags = waRequest::post('tags', '', waRequest::TYPE_STRING_TRIM);
        $tags = $tags ? explode(',', $tags) : array();
        $delete_tags = waRequest::post('delete_tags', array(), waRequest::TYPE_ARRAY_INT);

        $tag_model = new photosTagModel();
        $photo_tag_model = new photosPhotoTagsModel();

        $photo_rights_model = new photosPhotoRightsModel();
        $allowed_photo_id = $photo_rights_model->filterAllowedPhotoIds($photo_id, true);
        $denied_photo_id = array_values(array_diff($photo_id, $allowed_photo_id));

        if ($allowed_photo_id) {
            if ($one_photo) {
                $allowed_photo_id = $allowed_photo_id[0];
                $photo_tag_model->set($allowed_photo_id, $tags);

                $photo_model = new photosPhotoModel();
                if ($parent_id = $photo_model->getStackParentId($allowed_photo_id)) {
                    $this->response['parent_id'] = $parent_id;
                }
            } else {
                if ($delete_tags) {
                    $photo_tag_model->delete($allowed_photo_id, $delete_tags);
                }
                $photo_tag_model->assign($allowed_photo_id, $tag_model->getIds($tags, true));
            }
            $allowed_photo_id = (array)$allowed_photo_id;
            $tags = $photo_tag_model->getTags($allowed_photo_id);
            if (!$tags && $allowed_photo_id) {
                $tags = array_fill_keys($allowed_photo_id, array());
            }
            $this->response['tags'] = $tags;
        }
        if ($denied_photo_id) {
            $this->response['alert_msg'] = photosPhoto::sprintf_wplural(
                    "The operation was not performed to %d photo (%%s)",
                    "The operation was not performed to %d photos (%%s)",
                    count($denied_photo_id),
                    _w("out of %d selected", "out of %d selected", count($photo_id))
            ) . ', ' . _w("because you don't have sufficient access rights") . '.';
        }
        $this->response['cloud'] = $tag_model->getCloud();
    }
}