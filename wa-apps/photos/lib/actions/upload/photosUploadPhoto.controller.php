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
        $this->status = waRequest::post('status', 0, 'int');
        $this->groups = waRequest::post('groups', array(), waRequest::TYPE_ARRAY_INT);
        if (!$this->groups) {
            $this->status = -1; // only author have access to this photo
            $this->groups = array(-$this->getUser()->getId());
        }
        // work with album
        $this->album_id = waRequest::post('album_id');
        $this->album_id = (int)$this->album_id;
        if ($this->album_id > 0 && !$album_rights_model->checkRights($this->album_id, true)) {
            $this->response['files'][] = array(
                'error' => _w("You don't have sufficient access rights")
            );
            return;
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
                $this->response['files'][] = $this->save($file);
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
                        $this->response['files'][] = $this->save($file);
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


    protected function save(waRequestFile $file)
    {
        // check image
        if (!($image = $file->waImage())) {
            throw new waException(_w('Incorrect image'));
        }

        $exif_data = photosExif::getInfo($file->tmp_name);
        $image_changed = false;
        if (!empty($exif_data['Orientation'])) {
            $image_changed = $this->correctOrientation($exif_data['Orientation'], $image);
        }

        /**
         * Extend upload proccess
         * Make extra workup
         * @event photo_upload
         */
        $event = wa()->event('photo_upload', $image);
        if ($event && !$image_changed) {
            foreach ($event as $plugin_id => $result) {
                if ($result) {
                    $image_changed = true;
                    break;
                }
            }
        }

        $data = array(
            'name' => preg_replace('/\.[^\.]+$/', '' ,basename($file->name)),
            'ext' => $file->extension,
            'size' => $file->size,
            'type' => $image->type,
            'width' => $image->width,
            'height' => $image->height,
            'contact_id' => $this->getUser()->getId(),
            'status' => $this->status,
            'upload_datetime'=> date('Y-m-d H:i:s'),
        );

        if ($this->status <= 0) {
            $data['hash'] = md5(uniqid(time(), true));
        }
        $photo_id = $data['id'] = $this->model->insert($data);
        if (!$photo_id) {
            throw new waException(_w('Database error'));
        }

        // update url
        $url = $this->generateUrl($data['name'], $photo_id);
        $this->model->updateById($photo_id, array(
            'url' => $url
        ));

        // check rigths to upload folder
        $photo_path = photosPhoto::getPhotoPath($data);
        if ((file_exists($photo_path) && !is_writable($photo_path)) ||
            (!file_exists($photo_path) && !waFiles::create($photo_path))) {
            $this->model->deleteById($photo_id);
            throw new waException(sprintf(_w("The insufficient file write permissions for the %s folder."), substr($photo_path, strlen($this->getConfig()->getRootPath()))));
        }

        if ($image_changed) {
            $image->save($photo_path);
            // save original
            if ($this->getConfig()->getOption('save_original')) {
                $original_file = photosPhoto::getOriginalPhotoPath($photo_path);
                $file->moveTo($original_file);
            }
        } else {
            $file->moveTo($photo_path);
        }
        unset($image);        // free variable

        // add to album
        if ($photo_id && $this->album_id) {
            $album_photos_model = new photosAlbumPhotosModel();

            // update note if album is empty and note is yet null
            $r = $album_photos_model->getByField('album_id', $this->album_id);
            if (!$r) {
                $album_model = new photosAlbumModel();
                $sql = "UPDATE " . $album_model->getTableName() . " SET note = IFNULL(note, s:note) WHERE id = i:album_id";
                $time = !empty($exif_data['DateTimeOriginal']) ? strtotime($exif_data['DateTimeOriginal']) : time();
                $album_model->query($sql, array(
                    'note' => mb_strtolower(_ws(date('F', $time))).' '._ws(date('Y', $time)),
                    'album_id' => $this->album_id
                ));
            }

            // add to album iteself
            $sort = (int)$album_photos_model->query("SELECT sort + 1 AS sort FROM " . $album_photos_model->getTableName() .
                " WHERE album_id = i:album_id ORDER BY sort DESC LIMIT 1", array('album_id' => $this->album_id)
            )->fetchField('sort');
            $album_photos_model->insert(array(
                'photo_id' => $photo_id,
                'album_id' => $this->album_id,
                'sort' => $sort
            ));
        }

        // save rights for groups
        if ($this->groups) {
            $rights_model = new photosPhotoRightsModel();
            $rights_model->multiInsert(array('photo_id' => $photo_id, 'group_id' => $this->groups));
        }

        // save exif data
        if (!empty($exif_data)) {
            $exif_model = new photosPhotoExifModel();
            $exif_model->save($photo_id, $exif_data);
        }

        $sizes = $this->getConfig()->getSizes();

        photosPhoto::generateThumbs($data, $sizes);

        return array(
            'name' => $file->name,
            'type' => $file->type,
            'size' => $file->size,
            'thumbnail_url' => photosPhoto::getPhotoUrl($data, photosPhoto::getThumbPhotoSize()),
            'url' => '#/photo/'.$photo_id.'/'
        );
    }

    protected function correctOrientation($orientation, waImage $image)
    {
        $angles = array(
            3 => '180', 4 => '180',
            5 => '90',  6 => '90',
            7 => '-90', 8 => '-90'
        );
        if (isset($angles[$orientation])) {
            $image->rotate($angles[$orientation]);
            return true;
        }
        return false;
    }

    private function urlExists($url, $photo_id)
    {
        $where = "url = s:url AND id != i:id";
        return !!$this->model->select('id')->where($where, array(
            'url' => $url,
            'id' => $photo_id
        ))->fetch();
    }

    private function generateUrl($name, $photo_id)
    {
        $counter = 1;
        $original_url = photosPhoto::suggestUrl($name);
        $url = $original_url;
        while ($this->urlExists($url, $photo_id)) {
            $url = "{$original_url}_{$counter}";
            $counter++;
        }
        return $url;
    }

    public function display()
    {
        $this->getResponse()->sendHeaders();
        echo json_encode($this->response);
    }
}