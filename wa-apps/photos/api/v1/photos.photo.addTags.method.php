<?php

class photosPhotoAddTagsMethod extends waAPIMethod
{
    protected $method = 'POST';
    
    public function execute()
    {
        $photo_id = $this->post('id', true);
        if (!is_array($photo_id)) {
            if (strpos($photo_id, ',') !== false) {
                $photo_id = array_map('intval', explode(',', $photo_id));
            } else {
                $photo_id = array($photo_id);
            }
        }
        $tag = waRequest::post('tag', '');
        if (!$tag) {
            $tag = array();
        }
        if (!is_array($tag)) {
            if (strpos($tag, ',') !== false) {
                $tag = explode(',', $tag);
            } else {
                $tag = array($tag);
            }
        }
        $tag = array_map('trim', $tag);
        
        $tag_model = new photosTagModel();
        $photo_tag_model = new photosPhotoTagsModel();
        $photo_rights_model = new photosPhotoRightsModel();
        $allowed_photo_id = $photo_rights_model->filterAllowedPhotoIds($photo_id, true);
        if ($allowed_photo_id) {
            $photo_tag_model->assign($allowed_photo_id, $tag_model->getIds($tag, true));
            $this->response = true;
        } else {
            throw new waAPIException('access_denied', 403);
        }
    }

}