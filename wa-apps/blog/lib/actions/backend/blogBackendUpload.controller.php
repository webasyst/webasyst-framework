<?php
/**
 * Image upload for blog posts.
 */
class blogBackendUploadController extends waController
{
    public function execute()
    {
        if (blogPhotosBridge::isEnabled()) {
            $this->uploadToPhotosApp();
        } else {
            $this->uploadToFilder();
        }
    }

    public function uploadToFilder()
    {
        // When Photos app is not used to store images,
        // use the default system uploader to do all the work
        $actions = blogPagesActions();
        $actions->run('uploadimage');
    }

    public function uploadToPhotosApp()
    {
        $errors = array();
        $uploaded = array();
        $this->getStorage()->close();

        foreach ($this->getIterableFiles() as $file) {
            if ($file->error_code != UPLOAD_ERR_OK) {
                $errors[] = $file->error;
            } else {
                try {
                    $uploaded[] = $this->save($file, array(
                        'status' => -1,
                    ));
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        if (!$uploaded) {
            $errors[] = _w('No files to upload!');
        }

        $this->sendResponse($uploaded, $errors);

    }

    protected function getIterableFiles()
    {
        // Read file from global $_FILES
        // Don't bother with html5-style uploads for now...
        return waRequest::file('file');
    }

    protected function save(waRequestFile $file, $data = array())
    {
        wa('photos');
        $photo_model = new photosPhotoModel();

        $data['groups'] = array();
        $data['app_id'] = 'blog';
        $data['hash'] = '';
        $id = $photo_model->add($file, $data);
        if (!$id) {
            throw new waException(_w("Save error"));
        }

        $photo = $photo_model->getById($id);

        return array(
            'id'    => $id,
            'photo' => $photo,
            'name'  => $file->name,
            'type'  => $file->type,
            'size'  => $file->size,
            'url'   => photosPhoto::getPhotoUrl($photo, null, !!waRequest::get('absolute')),
            'thumbnail_url' => photosPhoto::getPhotoUrl($photo, photosPhoto::getThumbPhotoSize(), !!waRequest::get('absolute')),
        );
    }

    protected function sendResponse($uploaded, $errors)
    {
        $this->getResponse()->sendHeaders();

        // Currently this controller is only used for Redactor file uploads
        // to upload one file at a time. Redactor expects a specific response,
        // so we don't even bother for now with all other files. They are never there.
        //if (waRequest::get('filelink')) {
            if ($errors) {
                echo json_encode(array('error' => $errors));
            } else {
                echo json_encode(array('filelink' => $uploaded[0]['url']));
            }
        //} else ...
    }

}
