<?php

class photosUploadPhotoController extends waJsonController
{
    /**
     * @var photosPhotoModel
     */
    protected $model;

    protected $album_id;
    protected $status;
    protected $groups;

    public function execute()
    {
        if (!$this->getRights('upload')) {
            throw new waRightsException(_w("You don't have sufficient access rights"));
        }
        $this->response['files'] = array();

        $this->model = new photosPhotoModel();

        $album_rights_model = new photosAlbumRightsModel();

        // rights for photos
        $status = waRequest::post('status', 0, 'int');
        $groups = waRequest::post('groups', array(), waRequest::TYPE_ARRAY_INT);
        if (!$groups) {
            $status = -1; // only author have access to this photo
            $groups = array(-$this->getUser()->getId());
        }

        // work with album
        $album_id = (int) waRequest::post('album_id');
        if ($album_id > 0 && !$album_rights_model->checkRights($album_id, true)) {
            $this->response['files'][] = array(
                'error' => _w("You don't have sufficient access rights")
            );
            return;
        }

        $this->getStorage()->close();

        foreach (self::getFilesFromPost() as $file) {
            if ($file->error_code != UPLOAD_ERR_OK) {
                $this->response['files'][] = array(
                    'name' => $file->name,
                    'error' => $file->error
                );
            } else {
                try {
                    $this->response['files'][] = $this->save($file, array(
                        'status' => $status,
                        'groups' => $groups,
                        'album_id' => $album_id
                    ));
                } catch (Exception $e) {
                    $this->response['files'][] = array(
                        'name' => $file->name,
                        'error' => $e->getMessage()
                    );
                }
            }
        }
    }

    public static function getFilesFromPost()
    {
        if (waRequest::server('HTTP_X_FILE_NAME')) {
            $name = waRequest::server('HTTP_X_FILE_NAME');
            $size = waRequest::server('HTTP_X_FILE_SIZE');

            $safe_name = trim(preg_replace('~[^a-z\.]~', '', waLocale::transliterate($name)), ". \n\t\r");
            $safe_name || ($safe_name = uniqid('p'));
            $file_path = wa()->getTempPath('photos/upload/').$safe_name;

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

            return array($file);
        } else {
            return waRequest::file('files');
        }
    }

    protected function save(waRequestFile $file, $data)
    {
        $id = $this->model->add($file, $data);
        if (!$id) {
            throw new waException(_w("Save error"));
        }

        $photo = $this->model->getById($id);

        $parent_id = (int) waRequest::post('parent_id');
        if ((int) waRequest::post('parent_id')) {
            $this->model->appendToStack($parent_id, array($id));
        }

        return array(
            'id' => $id,
            'name' => $file->name,
            'type' => $file->type,
            'size' => $file->size,
            'thumbnail_url' => photosPhoto::getPhotoUrl($photo, photosPhoto::getThumbPhotoSize()),
            'url' => '#/photo/'.$id.'/'
        );
    }

    public function display()
    {
        $this->getResponse()->sendHeaders();
        echo json_encode($this->response);
    }
}