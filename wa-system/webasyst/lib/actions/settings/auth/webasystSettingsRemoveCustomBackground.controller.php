<?php

class webasystSettingsRemoveCustomBackgroundController extends webasystSettingsJsonController
{
    public function execute()
    {
        $model = new waAppSettingsModel();
        $images_path = wa()->getDataPath(null, true, 'webasyst');
        if ($images = waFiles::listdir($images_path)) {
            foreach ($images as $image) {
                if (is_file($images_path.'/'.$image) && preg_match('@\.(jpe?g|png|gif|bmp)$@', $image)) {
                    waFiles::delete($images_path."/".$image);
                }
            }
        }
        $model->del('webasyst', 'auth_form_background');
    }
}