<?php

class photosPhotoRotateController extends waJsonController
{

    private $derection_angles = array(
        'left' => '-90',
        'right' => '90',
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

            if (!$photo_rights_model->checkRights($photo, true)) {
                throw new waException(_w("You don't have sufficient access rights"));
            }

            $photo_path = photosPhoto::getPhotoPath($photo);

            $paths = array();
            try {
                $image = new photosImage($photo_path);
                $result_photo_path = preg_replace('/(\.[^\.]+)$/','.result$1',$photo_path);
                $backup_photo_path = preg_replace('/(\.[^\.]+)$/','.backup$1',$photo_path);
                $paths[] = $result_photo_path;
                $result = $image->rotate($this->derection_angles[$direction])->save($result_photo_path);
                if ($result) {
                    $count = 0;
                    while(!file_exists($result_photo_path) && ++$count<5) {
                        sleep(1);
                    }
                    if(!file_exists($result_photo_path)) {
                        throw new waException("Error while rotate. I/O error");
                    }
                    $paths[] = $backup_photo_path;
                    if(waFiles::move($photo_path,$backup_photo_path)) {
                        if(!waFiles::move($result_photo_path,$photo_path)) {
                            if(!waFiles::move($backup_photo_path,$photo_path)) {
                                throw new waException("Error while rotate. Original file corupted but backuped" );
                            }
                            throw new waException("Error while rotate. Operation canceled");
                        } else {
                            $edit_datetime = date('Y-m-d H:i:s');
                            $data = array(
                                'edit_datetime' => $edit_datetime,
                                'width' => $photo['height'],
                                'height' => $photo['width']
                            );
                            $photo_model->updateById($id, $data);
                            $photo = array_merge($photo, $data);

                            $thumb_dir = photosPhoto::getPhotoThumbDir($photo);
                            $back_thumb_dir = preg_replace('@(/$|$)@','.back$1', $thumb_dir, 1);
                            $paths[] = $back_thumb_dir;
                            waFiles::delete($back_thumb_dir);
                            if (!(waFiles::move($thumb_dir, $back_thumb_dir) || waFiles::delete($back_thumb_dir)) && !waFiles::delete($thumb_dir)){
                                throw new waException("Error while rebuild thumbnails");
                            }
                        }

                        $photo['thumb'] = photosPhoto::getThumbInfo($photo, photosPhoto::getThumbPhotoSize());
                        $photo['thumb_big'] = photosPhoto::getThumbInfo($photo, photosPhoto::getBigPhotoSize());
                        $photo['thumb_middle'] = photosPhoto::getThumbInfo($photo, photosPhoto::getMiddlePhotoSize());

                        $original_photo_path = photosPhoto::getOriginalPhotoPath($photo);
                        if (wa('photos')->getConfig()->getOption('save_original') && file_exists($original_photo_path)) {
                            $photo['original_exists'] = true;
                        } else {
                            $photo['original_exists'] = false;
                        }
                        $this->response['photo'] = $photo;
                        $this->log('photo_edit', 1);

                        $obligatory_sizes = $this->getConfig()->getSizes();
                        try {
                            photosPhoto::generateThumbs($photo, $obligatory_sizes);
                        } catch(Exception $e) {
                            waLog::log($e->getMessage());

                        }
                    } else {
                        throw new waException("Error while rotate. Operation canceled");
                    }
                }
                foreach($paths as $path) {
                    waFiles::delete($path);
                }
            } catch(Exception $e) {
                foreach($paths as $path) {
                    waFiles::delete($path);
                }
                throw $e;
            }
        }
    }
}