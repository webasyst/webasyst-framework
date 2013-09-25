<?php

class photosPhotoRotateMethod extends waAPIMethod
{
    protected $method = 'POST';
    
    public function execute()
    {
        $id = $this->post('id', true);
        $photo_model = new photosPhotoModel();
        $photo = $photo_model->getById($id);
        
        $clockwise = waRequest::post('clockwise', null, 1);
        if (!is_numeric($clockwise)) {
            $clockwise = strtolower(trim($clockwise));
            $clockwise = $clockwise === 'false' ? 0 : 1;
        }
        
        if ($photo) {
            try {
                $photo_model = new photosPhotoModel();
                $photo_model->rotate($id, $clockwise);
            } catch (waException $e) {
                throw new waAPIException('server_error', $e->getMessage(), 500);
            }
            $this->response = true;
        } else {
            throw new waAPIException('invalid_request', 'Photo not found', 404);
        }
    }
}