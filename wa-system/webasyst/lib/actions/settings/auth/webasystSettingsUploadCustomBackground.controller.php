<?php

class webasystSettingsUploadCustomBackgroundController extends webasystSettingsJsonController
{
    public function execute()
    {
        $file = waRequest::file('image');

        if ($file->uploaded()) {
            $model = new waAppSettingsModel();
            $images_path = wa()->getDataPath(null, true, 'webasyst');
            $images = $this->getImages($images_path);

            $ext = 'png';
            if (preg_match('/\.(png|gif|jpg|jpeg|bmp|tif)$/i', $file->name, $matches)) {
                $ext = $matches[1];
            }
            $name = 'auth_form_background.'.$ext;
            $path = wa()->getDataPath($name, true, 'webasyst');
            try {
                $image = $file->waImage();
            } catch (waException $e) {
                $message = $e->getMessage();
                $tmp_name = $file->tmp_name;
                if (!preg_match('//u', $tmp_name)) {
                    $tmp_name = iconv('windows-1251', 'utf-8', $tmp_name);
                }
                if (strpos($message, $tmp_name) !== false) {
                    $message = preg_replace('/:\s*$/', '', str_replace($tmp_name, '', $message));

                }
                return $this->errors = $message;
            }
            foreach ($images as $i) {
                waFiles::delete($images_path.'/'.$i);
            }
            $file->copyTo($path);
            clearstatcache();
            $name .= '?'.time();
            $model->set('webasyst', 'auth_form_background', $name);
            $image_info = get_object_vars($image);
            $image_info['file_size'] = filesize($path);
            $image_info['file_size_formatted'] = $this->formatFileSize($image_info['file_size'], '%0.0f', _w('B,KB,MB'));
            $image_info['file_mtime'] = filemtime($path);
            $image_info['file_name'] = basename($path);
            $image_info['img_path'] = wa()->getDataUrl(null, true, 'webasyst').'/'.$image_info['file_name'].'?t='.$image_info['file_mtime'];
            $this->response = $image_info;
        }
    }

    protected function getImages($path)
    {
        $files = waFiles::listdir($path);
        foreach ($files as $id => $file) {
            if (!is_file($path.'/'.$file) || !preg_match('@\.(jpe?g|png|gif|bmp)$@', $file)) {
                unset($files[$id]);
            }
        }
        return array_values($files);
    }

    protected function formatFileSize($file_size, $format='%0.2f', $dimensions='Bytes,KBytes,MBytes,GBytes')
    {
        $dimensions = explode(',',$dimensions);
        $dimensions = array_map('trim',$dimensions);
        $dimension = array_shift($dimensions);
        while(($file_size>768)&&($dimension = array_shift($dimensions))){
            $file_size = $file_size/1024;
        }
        return sprintf($format,$file_size).' '.$dimension;
    }
}