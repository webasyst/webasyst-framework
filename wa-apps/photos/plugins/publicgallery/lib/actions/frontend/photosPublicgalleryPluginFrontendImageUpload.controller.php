<?php

class photosPublicgalleryPluginFrontendImageUploadController extends waJsonController
{
    /**
     *
     * @var photosPhotoModel 
     */
    protected $model;
    
    public function execute()
    {
        $this->response['files'] = array();
        
        $this->model = new photosPhotoModel();
        
        $data = array(
            'contact_id' => wa()->getUser()->getId(),
            'status' => 1,
            'groups' => array(0),
            'source' => 'publicgallery'
        );
        
        
        $plugin = wa()->getPlugin('publicgallery');
        if ($plugin->getSettings('need_moderation')) {
            $data['moderation'] = 0;
        } else {
            $data['moderation'] = 1;
        }
        if ($data['moderation'] <= 0) {
            $data['status'] = 0;
        }
        
        $this->getStorage()->close();
        
        if (waRequest::server('HTTP_X_FILE_NAME')) {
            $name = waRequest::server('HTTP_X_FILE_NAME');
            $size = waRequest::server('HTTP_X_FILE_SIZE');
            $file_path = wa()->getTempPath('photos/upload/').$name;
            $append_file = is_file($file_path) && $size > filesize($file_path);
            clearstatcache();
            file_put_contents(
                $file_path,
                fopen('php://input', 'r'),
                $append_file ? FILE_APPEND : 0
            );
            $file = new waRequestFile(array(
                'name' => $name,
                'type' => waRequest::server('HTTP_X_FILE_TYPE'),
                'size' => $size,
                'tmp_name' => $file_path,
                'error' => 0
            ));
            try {
                $this->response['files'][] = $this->save($file, $data);
            } catch (Exception $e) {
                $this->response['files'][] = array(
                    'error' => $e->getMessage()
                );
            }
        } else {
            $files = waRequest::file('files');
            foreach ($files as $file) {
                if ($file->error_code != UPLOAD_ERR_OK) {
                    $this->response['files'][] = array(
                        'error' => $file->error
                    );
                } else {
                    try {
                        $this->response['files'][] = $this->save($file, $data);
                    } catch (Exception $e) {
                        $this->response['files'][] = array(
                            'name' => $file->name,
                            'error' => $e->getMessage()
                        );
                    }
                }
            }
        }
    }
    
    public function save(waRequestFile $file, $data) 
    {  
        // check image
        if (!($image = $file->waImage())) {
            throw new waException(_w('Incorrect image'));
        }

        $plugin = wa()->getPlugin('publicgallery');
        
        $min_size = $plugin->getSettings('min_size');
        if ($min_size && ($image->height < $min_size || $image->width < $min_size)) {
            throw new waException(sprintf(_w("Image is too small. Minimum image size is %d px"), $min_size));
        }
        
        $max_size = $plugin->getSettings('max_size');
        if ($max_size && ($image->height > $max_size || $image->width > $max_size)) {
            throw new waException(sprintf(_w("Image is too big. Maximum image size is %d px"), $max_size));
        }
        
        
        $id = $this->model->add($file, $data);
        if (!$id) {
            throw new waException(_w("Save error"));
        }
        $tag = $plugin->getSettings('assign_tag');
        if ($tag) {
            $photos_tag_model = new photosPhotoTagsModel();
            $photos_tag_model->set($id, $tag);
        }
        return array(
            'name' => $file->name,
            'type' => $file->type,
            'size' => $file->size
        );    
    }
    
    public function display()
    {
        $this->getResponse()->sendHeaders();
        echo json_encode($this->response);
    }
    
}