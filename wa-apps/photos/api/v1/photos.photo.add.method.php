<?php

class photosPhotoAddMethod extends waAPIMethod
{
    protected $method = 'POST';
    
    public function execute()
    {
        $data = waRequest::post();
        
        if (!wa()->getUser()->getRights('photos', 'upload')) {
            throw new waAPIException('access_denied', 403);
        }
        
        $group_ids = array(0);
        if (!isset($data['status'])) {
            $data['status'] = 1;
        } else if ($data['status'] == -1) {
            $group_ids = array(-wa()->getUser()->getId());
        }
        $data['groups'] = $group_ids;
        $data['source'] = photosPhotoModel::SOURCE_API;
        
        // work with album
        if (isset($data['album_id'])) {
            $album_id = $data['album_id'];
            $album_model = new photosAlbumModel();
            $album = $album_model->getById($album_id);
            if (!$album) {
                throw new waAPIException('invalid_param', 'Album not found', 404);
            }
            $album_rights_model = new photosAlbumRightsModel();
            if (!$album_rights_model->checkRights($album_id, true)) {
                throw new waAPIException('access_denied', 'Not rights to album', 403);
            }
        }
        
        $file = waRequest::file('file');
        if (!$file->uploaded()) {
            throw new waAPIException('server_error', $file->error, 500);
        }
        
        $id = null;
        $photo_model = new photosPhotoModel();
        try {
            $id = $photo_model->add($file, $data);
        } catch(Exception $e) {
            throw new waAPIException('server_error', $e->getMessage(), 500);
        }
        
        if (!$id) {
            throw new waAPIException('server_error', 500);
        }
     
        $_GET['id'] = $id;
        $method = new photosPhotoGetInfoMethod();
        $this->response = $method->getResponse(true);
    }

}