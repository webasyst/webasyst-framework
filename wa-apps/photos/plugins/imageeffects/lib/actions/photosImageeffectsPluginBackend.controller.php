<?php

class photosImageeffectsPluginBackendController extends waJsonController
{
    private $filters = array(
        'grayscale' => waImage::FILTER_GRAYSCALE,
        'sepia' => waImage::FILTER_SEPIA
    );

    public function execute()
    {
        $id  = waRequest::post('id', null, waRequest::TYPE_INT);
        $filter = waRequest::post('filter', 'grayscale', waRequest::TYPE_STRING_TRIM);
        if (!$id) {
            throw new waException(_w("Can't apply a filter to photo: unknown photo id"));
        }
        if (!isset($this->filters[$filter])) {
            throw new waException(_w("Can't apply a filter to photo: unknown filter"));
        }
        $filter = $this->filters[$filter];

        $photo_model = new photosPhotoModel();
        $photo_rights_model = new photosPhotoRightsModel();
        $photo = $photo_model->getById($id);

        $photo_rights_model = new photosPhotoRightsModel();
        if (!$photo_rights_model->checkRights($photo, true)) {
            throw new waException(_w("You don't have sufficient access rights"));
        }

        $photo_path = photosPhoto::getPhotoPath($photo);

        $image = new photosImage($photo_path);
        if ($image->filter($filter)->save()) {
            waFiles::delete(photosPhoto::getPhotoThumbDir($photo));
            $edit_datetime = date('Y-m-d H:i:s');
            $photo_model->updateById($id, array(
                'edit_datetime' => $edit_datetime
            ));
            $photo['edit_datetime'] = $edit_datetime;

            $original_photo_path = photosPhoto::getOriginalPhotoPath($photo);
            if (wa('photos')->getConfig()->getOption('save_original') && file_exists($original_photo_path)) {
                $photo['original_exists'] = true;
            } else {
                $photo['original_exists'] = false;
            }
            $this->response['photo'] = $photo;
        }
    }
}