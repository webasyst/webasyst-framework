<?php

class photosPhotoRestoreController extends waJsonController
{
    public function execute()
    {
        $id  = waRequest::post('id', null, waRequest::TYPE_INT);
        if (!$id) {
            throw new waException("Can't restore photo");
        }

        $photo_model = new photosPhotoModel();
        $photo_rights_model = new photosPhotoRightsModel();
        $photo = $photo_model->getById($id);

        if (!$photo_rights_model->checkRights($photo, true)) {
            throw new waException("You don't have sufficient access rights");
        }

        $original_photo_path = photosPhoto::getOriginalPhotoPath($photo);
        if (!wa('photos')->getConfig()->getOption('save_original') || !file_exists($original_photo_path)) {
            throw new waException("Can't restore photo. Original photo doesn't exist");
        }

        $paths = array();
        try {
            $photo_path = photosPhoto::getPhotoPath($photo);
            $backup_photo_path = preg_replace('/(\.[^\.]+)$/','.backup$1',$photo_path);
            if (waFiles::move($photo_path, $backup_photo_path)) {
                if (!waFiles::move($original_photo_path, $photo_path)) {
                    if (!waFiles::move($backup_photo_path, $photo_path)) {
                        throw new waException("Error while restore. Current file corupted but backuped" );
                    }
                    $paths[] = $backup_photo_path;
                    throw new waException("Error while restore. Operation canceled");
                } else {
                    $image = new photosImage($photo_path);
                    $edit_datetime = date('Y-m-d H:i:s');
                    $data = array(
                        'edit_datetime' => $edit_datetime,
                        'width' => $image->width,
                        'height' => $image->height
                    );
                    $photo_model->updateById($id, $data);
                    $photo = array_merge($photo, $data);

                    $thumb_dir = photosPhoto::getPhotoThumbDir($photo);
                    $back_thumb_dir = preg_replace('@(/$|$)@','.back$1', $thumb_dir, 1);
                    $paths[] = $back_thumb_dir;
                    waFiles::delete($back_thumb_dir); // old backups
                    if (!waFiles::move($thumb_dir, $back_thumb_dir) && !waFiles::delete($thumb_dir)){
                        throw new waException("Error while rebuild thumbnails");
                    }

                    $photo['original_exists'] = false;
                    $photo['thumb'] = photosPhoto::getThumbInfo($photo, photosPhoto::getThumbPhotoSize());
                    $photo['thumb_big'] = photosPhoto::getThumbInfo($photo, photosPhoto::getBigPhotoSize());
                    $photo['thumb_middle'] = photosPhoto::getThumbInfo($photo, photosPhoto::getMiddlePhotoSize());

                    $sizes = $this->getConfig()->getSizes();
                    try {
                        photosPhoto::generateThumbs($photo, $sizes);
                    } catch(Exception $e) {
                        waLog::log($e->getMessage());
                    }

                    $this->response['photo'] = $photo;
                    $this->log('photo_reverttooriginal', 1);
                }
            } else {
                throw new waException("Error while restore. Operation canceled");
            }
            foreach($paths as $path) {
                waFiles::delete($path);
            }
        } catch (Exception $e) {
            foreach($paths as $path) {
                waFiles::delete($path);
            }
            throw $e;
        }
    }
}