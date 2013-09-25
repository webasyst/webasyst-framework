<?php

class photosPhotoDeleteController extends waJsonController
{
    public function execute()
    {
        $photo_id = waRequest::post('photo_id', null, waRequest::TYPE_ARRAY_INT);
        $prev_denied_photo_id = waRequest::post('denied_photo_id', array(), waRequest::TYPE_ARRAY_INT);

        $photo_model = new photosPhotoModel();
        $photo_rights_model = new photosPhotoRightsModel();
        $allowed_photo_id = $photo_rights_model->filterAllowedPhotoIds($photo_id, true);
        $denied_photo_id = array_diff($photo_id, $allowed_photo_id);

        if ($allowed_photo_id) {
            // before deleting define if is it children photo in stack (one photo page)
            if (count($allowed_photo_id) == 1 && count($photo_id) == 1) {
                $photo = $photo_model->getById($allowed_photo_id);
                if ($photo) {
                    $photo = reset($photo);
                    if ($photo['parent_id'] > 0) {
                        $this->response['parent_id'] = $photo['parent_id'];
                    }
                }
            }
            
            foreach ($allowed_photo_id as $id) {
                $photo_model->delete($id);
                /**
                 * Extend delete process
                 * Make extra workup
                 * @event photo_delete
                 */
                wa()->event('photo_delete', $id);
            }
            $this->log('photos_delete', 1);
        }

        $denied_parent_id = array();
        if ($denied_photo_id) {
            foreach ($photo_model->getByField('id', $denied_photo_id, 'id') as $photo) {
                $denied_parent_id[] = $photo['parent_id'] > 0 ? $photo['parent_id'] : $photo['id'];
            }
        }

        $denied_photo_id = array_values(array_unique(array_merge($prev_denied_photo_id, $denied_parent_id)));
        $this->response['denied_photo_id'] = $denied_photo_id;

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
        if ($denied_photos_length == $all_photos_length) {
            $this->response['denied_all'] = true;
        } else {
            $this->response['denied_all'] = false;
        }
    }
}