<?php

class photosPhotoDeleteMethod extends waAPIMethod
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

        $photo_model = new photosPhotoModel();
        $photo_rights_model = new photosPhotoRightsModel();
        $allowed_photo_id = $photo_rights_model->filterAllowedPhotoIds($photo_id, true);

        if ($allowed_photo_id) {
            foreach ($allowed_photo_id as $id) {
                $photo_model->delete($id);
                /**
                 * Extend delete process
                 * Make extra workup
                 * @event photo_delete
                 */
                wa()->event('photo_delete', $id);
            }
            $this->response = true;
        } else {
            throw new waAPIException('access_denied', 403);
        }
    }
}