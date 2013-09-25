<?php

class photosStackMakeController extends waJsonController
{
    public function execute()
    {
        $stack = array();
        $parent_id = waRequest::post('parent_id', null, waRequest::TYPE_INT);
        $photo_id = (array)waRequest::post('photo_id', array(), waRequest::TYPE_ARRAY_INT);
        $prev_denied_photo_id = waRequest::post('denied_photo_id', array(), waRequest::TYPE_ARRAY_INT);

        $photo_model = new photosPhotoModel();
        $photo_rights_model = new photosPhotoRightsModel();
        if (!$photo_rights_model->checkRights($parent_id, true)) {
            throw new waException(_w("You don't have sufficient access rights"));
        }
        $allowed_photo_id = $photo_rights_model->filterAllowedPhotoIds($photo_id, true);
        $denied_photo_ids = array_diff($photo_id, $allowed_photo_id);

        if ($allowed_photo_id) {
            $parent = $photo_model->getById($parent_id);
            $stack[$parent_id] = $allowed_photo_id;
            if ($parent['stack_count'] > 0) {
                $photo_model->appendToStack($parent_id, $allowed_photo_id);
            } else {
                $photo_model->makeStack($parent_id, $allowed_photo_id);
            }
        }

        $denied_parent_ids = array();
        if ($denied_photo_ids) {
            foreach ($photo_model->getByField('id', $denied_photo_ids, 'id') as $photo) {
                $denied_parent_ids[] = $photo['parent_id'] > 0 ? $photo['parent_id'] : $photo['id'];
            }
        }

        $denied_photo_id = array_values(array_unique(array_merge($prev_denied_photo_id, $denied_parent_ids)));
        $this->response['denied_photo_ids'] = $denied_photo_id;

        $all_photos_length = waRequest::post('photos_length', 0, waRequest::TYPE_INT);
        if (!$all_photos_length) {
            $all_photos_length = count($photo_id);
        }
        $all_photos_length += 1;  // plus parent photo
        $denied_photos_length = count($denied_photo_id);
        if ($denied_photos_length > 0 && $all_photos_length > 0) {
            $this->response['alert_msg'] = photosPhoto::sprintf_wplural(
                    "The operation was not performed to %d photo (%%s)",
                    "The operation was not performed to %d photos (%%s)",
            $denied_photos_length,
            _w("out of %d selected", "out of %d selected", $all_photos_length)
            ) . ', ' . _w("because you don't have sufficient access rights") . '.';
        }


        if ($stack) {
            /**
             * Extra actions after making stack
             * @event make_stack
             * @params array[int][int]int $stack[%parent_id%][]
             */
            wa()->event('make_stack', $stack);
            $this->log('photos_stack', 1);
        }
        $this->response['parent_id'] = $parent_id;
        $this->response['photo'] = $photo_model->getById($parent_id);
    }
}