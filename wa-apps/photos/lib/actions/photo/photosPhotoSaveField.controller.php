<?php

class photosPhotoSaveFieldController extends waJsonController
{
    /**
     * @var photosPhotoModel
     */
    private $photo_model;

    private $availableFields = array(
        'name', 'description', 'rate', 'url'
    );

    public function execute()
    {
        $name = waRequest::post('name', '', waRequest::TYPE_STRING_TRIM);
        if (in_array($name, $this->availableFields) === false) {
            throw new waException("Can't update photo: unknown field");
        }

        $photo_id = waRequest::post('id', null, waRequest::TYPE_ARRAY_INT);
        $value = waRequest::post('value', '', waRequest::TYPE_STRING_TRIM);
        if ($photo_id) {
            $photo_rights_model = new photosPhotoRightsModel();
            if (count($photo_id) == 1) { // editing only one photo
                if (!$photo_rights_model->checkRights(current($photo_id), true)) {
                    throw new waException(_w("You don't have sufficient access rights"));
                }

                // validations for one photo
                if ($name == 'url') {
                    if (!$this->validateUrl($value, current($photo_id))) {  // $photo_id is array of ids, so make current()
                        $this->errors['url'] = _w('URL is in use');
                        return;
                    }
                }

                $allowed_photo_id = $photo_id;
                $denied_photo_id = array();
            } else {
                $allowed_photo_id = $photo_rights_model->filterAllowedPhotoIds($photo_id, true);
                $denied_photo_id = array_diff($photo_id, $allowed_photo_id);
            }

            if ($allowed_photo_id) {
                if ($name == 'rate') {
                    $value = (int)$value;
                    if ($value < 0 || $value > 5) {
                        $value = 0;
                    }
                }
                $data[$name] = $value;

                $this->photo_model = new photosPhotoModel();
                if ($name == 'description' || $name == 'rate') {
                    $this->photo_model->update($allowed_photo_id, $data);
                    if (count($photo_id) == 1 && $allowed_photo_id) {    // means that we edit field in one-photo page
                        $photo_id = current($photo_id);
                        if ($parent_id = $this->photo_model->getStackParentId($photo_id)) {
                            $this->response['parent_id'] = $parent_id;
                        }
                    }
                    // change count of rated
                    if ($name == 'rate') {
                        $this->response['count'] = $this->photo_model->countRated();
                        $this->log('photos_rate', 1);
                    }
                } else {
                    // update only parent photo(s)
                    $this->photo_model->updateById($allowed_photo_id, $data);
                }
                if ($name == 'name') {
                    $this->response['value'] = $value;
                }
            }

            if (count($denied_photo_id) > 0 && count($photo_id) > 0) {
                $this->response['alert_msg'] = photosPhoto::sprintf_wplural(
                        "The operation was not performed to %d photo (%%s)",
                        "The operation was not performed to %d photos (%%s)",
                        count($denied_photo_id),
                        _w("out of %d selected", "out of %d selected", count($photo_id))
                ) . ', ' . _w("because you don't have sufficient access rights") . '.';
            }
            $allowed_photo_id_map = array();
            foreach ($allowed_photo_id as $id) {
                $allowed_photo_id_map[$id] = true;
            }
            $this->response['allowed_photo_id'] = $allowed_photo_id_map;
        }
    }

    private function validateUrl($url, $photo_id)
    {
        $this->photo_model = new photosPhotoModel();
        $where = "url = s:url AND id != i:id";
        return !$this->photo_model->select('id')->where($where, array(
            'url' => $url,
            'id' => $photo_id
        ))->fetch();
    }

}