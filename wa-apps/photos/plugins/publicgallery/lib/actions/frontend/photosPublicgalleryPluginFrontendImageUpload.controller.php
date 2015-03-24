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

        $files = photosUploadPhotoController::getFilesFromPost();
        foreach ($files as $file) {
            if ($file->error_code != UPLOAD_ERR_OK) {
                $this->response['files'][] = array(
                    'name' => $file->name,
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