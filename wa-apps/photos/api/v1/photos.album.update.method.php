<?php

class photosAlbumUpdateMethod extends waAPIMethod
{
    protected $method = 'POST';

    public function execute()
    {        
        $id = $this->get('id', true);
        
        $album_model = new photosAlbumModel();
        $album = $album_model->getById($id);

        if ($album) {

            $album_rights_model = new photosAlbumRightsModel();
            if (!$album_rights_model->checkRights($id, true)) {
                throw new waAPIException('access_denied', 403);
            }
            
            $data = waRequest::post();
            
            if (isset($data['parent_id']) && $album['parent_id'] != $data['parent_id']) {
                if (!$album_model->getById($data['parent_id'])) {
                    throw new waAPIException('invalid_param', 'Parent album not found', 404);
                }
                if (!$album_model->move($id, null, $data['parent_id'])) {
                    throw new waAPIException('server_error', 500);
                }
            }
            
            if (isset($data['type'])) {
                unset($data['type']);
            }
            
            if ($album_model->update($id, $data)) {
                
                // correct rights
                $album = $album_model->getById($id);
                $group_ids = array(0);
                if ($data['status'] == -1) {
                    $group_ids = array(-wa()->getUser()->getId());
                }
                $album_rights_model = new photosAlbumRightsModel();
                $album_rights_model->setRights($id, $group_ids);
                
                $method = new photosAlbumGetInfoMethod();
                $this->response = $method->getResponse(true);
            } else {
                throw new waAPIException('server_error', 500);
            }
        } else {
            throw new waAPIException('invalid_param', 'Album not found', 404);
        }
    }
}