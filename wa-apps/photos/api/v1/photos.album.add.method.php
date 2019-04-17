<?php

class photosAlbumAddMethod extends waAPIMethod
{
    protected $method = 'POST';

    public function execute()
    {
        if (!wa()->getUser()->getRights('photos', 'upload')) {
            throw new waAPIException('access_denied', 403);
        }
        
        $data = waRequest::post();

        // check required param name
        $this->post('name', true);

        $album_model = new photosAlbumModel();
        
        $group_ids = array(0);
        if (!isset($data['status'])) {
            $data['status'] = 1;
        } else if ($data['status'] == -1) {
            $group_ids = array(-wa()->getUser()->getId());
        }
        
        if ($data['status'] <= 0) {
            $data['hash'] = md5(uniqid(time(), true));
        } else {
            $data['url'] = $album_model->suggestUniqueUrl(photosPhoto::suggestUrl($data['name']));
        }
        
        if (!isset($data['type'])) {
            $data['type'] = photosAlbumModel::TYPE_STATIC;
        }

        $parent_id = waRequest::post('parent_id', 0, 'int');
        $parent = $album_model->getById($parent_id);
        if ($parent_id) {
            if (!$parent) {
                throw new waAPIException('invalid_request', 'Parent album not found', 404);
            }
            if ($data['type'] == photosAlbumModel::TYPE_STATIC && $parent['type'] == photosAlbumModel::TYPE_DYNAMIC) {
                throw new waAPIException('invalid_request', 'Inserted album is static but parent album is dynamic', 404);
            }
            if ($data['status'] > 0 && $parent['status'] <= 0) {
                throw new waAPIException('invalid_request', 'Inserted album is public but parent album is private', 404);
            }
        }

        if ($id = $album_model->add($data, $parent_id)) {
            // return info of the new album
            $_GET['id'] = $id;
            
            if ($parent_id) {
                $child = $album_model->getFirstChild($parent_id);
                $album_model->move($id, $child ? $child['id'] : 0, $parent_id);
            }
            
            $album_rights_model = new photosAlbumRightsModel();
            $album_rights_model->setRights($id, $group_ids);
            
            $method = new photosAlbumGetInfoMethod();
            $this->response = $method->getResponse(true);
        } else {
            throw new waAPIException('server_error', 500);
        }
    }
}