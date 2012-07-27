<?php

class photosPhotoRotateController extends waJsonController
{

    private $derection_angles = array(
        'left' => '-90',
        'right' => '90'
    );

    public function execute()
    {
        $id  = waRequest::post('id', null, waRequest::TYPE_INT);
        if (!$id) {
            throw new waException("Can't rotate photo");
        }

        $direction = waRequest::post('direction', 'left', waRequest::TYPE_STRING_TRIM);
        if (isset($this->derection_angles[$direction])) {
            $photo_model = new photosPhotoModel();
            $photo_rights_model = new photosPhotoRightsModel();
            $photo = $photo_model->getById($id);

            if (!$photo_rights_model->checkRights($photo['id'], true)) {
                throw new waException(_w("You don't have sufficient access rights"));
            }

            $photo_path = photosPhoto::getPhotoPath($photo);

            try {
                $image = new photosImage($photo_path);
                $result = $image->rotate($this->derection_angles[$direction])->save($photo_path);
                if ($result) {
                    $thumb_dir = photosPhoto::getPhotoThumbDir($photo);
                    $temp_dir = sys_get_temp_dir() . '/' . substr(md5($thumb_dir), 0, 10) . '/';
                    if (file_exists($temp_dir)) {
                        waFiles::delete($temp_dir);
                    }
                    waFiles::move($thumb_dir, $temp_dir);
                    $obligatory_sizes = $this->getConfig()->getSizes();
                    photosPhoto::generateThumbs($photo, $obligatory_sizes);

                    $edit_datetime = date('Y-m-d H:i:s');
                    $data = array(
                        'edit_datetime' => $edit_datetime,
                        'width' => $photo['height'],
                        'height' => $photo['width']
                    );
                    $photo_model->updateById($id, $data);
                    $photo = array_merge($photo, $data);

                    $photo['thumb_big'] = photosPhoto::getThumbInfo($photo, photosPhoto::getBigPhotoSize());
                    $photo['thumb_middle'] = photosPhoto::getThumbInfo($photo, photosPhoto::getMiddlePhotoSize());

                    $original_photo_path = photosPhoto::getOriginalPhotoPath($photo);
                    if (wa('photos')->getConfig()->getOption('save_original') && file_exists($original_photo_path)) {
                        $photo['original_exists'] = true;
                    } else {
                        $photo['original_exists'] = false;
                    }
                    $this->response['photo'] = $photo;
                }
            } catch(Exception $e) {
                throw $e;
            }
        }
    }
}