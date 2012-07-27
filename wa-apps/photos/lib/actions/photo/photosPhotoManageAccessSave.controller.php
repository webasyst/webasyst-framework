<?php

class photosPhotoManageAccessSaveController extends waJsonController
{
    /**
     * @var photosPhotoModel
     */
    private $photo_model;

    public function execute()
    {
        $photo_id = waRequest::post('photo_id', array(), waRequest::TYPE_ARRAY_INT);
        $status = waRequest::post('status', 0, waRequest::TYPE_INT);
        $groups = waRequest::post('groups', array(), waRequest::TYPE_ARRAY_INT);
        if (!$groups) {
            $status = -1; // only author have access to this photo
            $groups = array(-$this->getUser()->getId());
        }
        // necessary when manage access rights for one photo. When in one photo extra info is needed in response
        $is_one_photo = waRequest::post('one_photo', 0, waRequest::TYPE_INT);

        // necessary only when manage access rights for several photos
        $prev_allowed_photo_id = waRequest::post('allowed_photo_id', array(), waRequest::TYPE_ARRAY_INT);
        $prev_denied_photo_id = waRequest::post('denied_photo_id', array(), waRequest::TYPE_ARRAY_INT);

        $photo_model = new photosPhotoModel();
        $photo_rights_model = new photosPhotoRightsModel();
        $allowed_photo_id = $photo_rights_model->filterAllowedPhotoIds($photo_id, true);
        $denied_photo_id = array_diff($photo_id, $allowed_photo_id);

        $photo_model->updateAccess($allowed_photo_id, $status, $groups);

        // leave only id of parents
        $denied_parent_id = array();
        if ($denied_photo_id) {
            foreach ($photo_model->getByField('id', $denied_photo_id, 'id') as $photo) {
                $denied_parent_id[] = $photo['parent_id'] > 0 ? $photo['parent_id'] : $photo['id'];
            }
        }
        $denied_photo_id = array_values(array_unique(array_merge($prev_denied_photo_id, $denied_parent_id)));
        $this->response['denied_photo_id'] = $denied_photo_id;

        // leave only id of parents
        $allowed_parent_id = array();
        if ($allowed_photo_id) {
            foreach ($photo_model->getByField('id', $allowed_photo_id, 'id') as $photo) {
                $allowed_parent_id[] = $photo['parent_id'] > 0 ? $photo['parent_id'] : $photo['id'];
            }
        }
        $allowed_photo_id = array_values(array_unique(array_merge($prev_allowed_photo_id, $allowed_parent_id)));
        $this->response['allowed_photo_id'] = $allowed_photo_id;

        $all_photos_length = waRequest::post('photos_length', 0, waRequest::TYPE_INT);
        if (!$all_photos_length) {
            $all_photos_length = count($photo_id);
        }
        $denied_photos_length = count($denied_photo_id);
        if ($denied_photos_length > 0 && $all_photos_length > 0) {
            $this->response['alert_msg'] = photosPhoto::sprintf_wplural(
                    "The operation was not performed to %d photo (%%s)",
                    "The operation was not performed to %d photos (%%s)",
                    $denied_photos_length,
                    _w("out of %d selected", "out of %d selected", $all_photos_length)
            ) . ', ' . _w("because you don't have sufficient access rights") . '.';
        }

        // if one photo send extra info for update cache and widget
        if ($is_one_photo) {
            $photo_id = current($photo_id);
            // check rights
            if (in_array($photo_id, $allowed_photo_id)) {
                $photo = $photo_model->getById($photo_id);
                // for make inline editable widget of frontend url
                $frontend_link_template = photosFrontendPhoto::getLink(array(
                    'url' => '%url%'
                ));
                $photo['edit_rights'] = $photo_rights_model->checkRights($photo['id'], true);
                $photo['private_url'] = photosPhotoModel::getPrivateUrl($photo);
                $this->response['photo'] = $photo;
                $this->response['frontend_link_template'] = $frontend_link_template;
            }
        }
    }
}