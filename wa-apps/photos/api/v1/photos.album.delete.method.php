<?php

class photosAlbumDeleteMethod extends waAPIMethod
{
    protected $method = 'POST';

    public function execute()
    {
        $id = $this->post('id', true);
        $album_model = new photosAlbumModel();
        $album = $album_model->getById((int)$id);

        if ($album) {
            
            $album_rights_model = new photosAlbumRightsModel();
            if (!$album_rights_model->checkRights($id, true)) {
                throw new waAPIException('access_denied', 403);
            }
            
            if ($album_model->delete($id)) {
                $this->response = true;
            } else {
                throw new waAPIException('server_error', 500);
            }
        } else {
            throw new waAPIException('invalid_request', 'Album not found', 404);
        }
    }
}