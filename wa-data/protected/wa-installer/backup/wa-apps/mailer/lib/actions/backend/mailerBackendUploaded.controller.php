<?php

/**
 * Controller to download files previously uploaded via mailerBackendUploadController.
 */
class mailerBackendUploadedController extends waController
{
    public function execute()
    {
        $id = waRequest::request('id');
        if (! ( $file = wa()->getStorage()->read($id))) {
            throw new waException('File not found.', 404);
        }
        if (empty($file['tmp_name'])
            || 0 !== strpos(realpath($file['tmp_name']), realpath(wa('mailer')->getTempPath('tmp_upload')))
            || !is_readable($file['tmp_name'])
        ) {
            throw new waException('File not found.', 404);
        }

        // Need to resize an image?
        $resize_x = waRequest::request('resize_x', null, 'int');
        $resize_y = waRequest::request('resize_y', null, 'int');
        $crop_x = waRequest::request('crop_x', null, 'int');
        $crop_y = waRequest::request('crop_y', null, 'int');
        if ($resize_x || $resize_y || $crop_x || $crop_y) {
            // is there such image in cache?
            $cache_filename = wa('mailer')->getTempPath('tmp_upload').'/cache/'.md5($file['tmp_name'].$resize_x.$resize_y.$crop_x.$crop_y).'.jpg';

            // Is there a file that is too old?
            if (@file_exists($cache_filename) && filemtime($cache_filename) < time() - 3600*24) {
                @unlink($cache_filename);
            }

            // Resize if there's no image in cache
            if (!is_readable($cache_filename)) {
                $dir = dirname($cache_filename);
                waFiles::create($dir);
                if (file_exists($cache_filename) || !is_writable($dir)) {
                    throw new waException('Unable to resize image: cache is not writable: '.dirname($cache_filename), 500);
                }
                $f = new mailerUploadedFile($id);

                $image = $f->waImage();
                if (($resize_x && $image->width > $resize_x) || ($resize_y && $image->height > $resize_y)) {
                    $master = waRequest::request('master', null);
                    if (!in_array($master, array(null, waImage::NONE, waImage::AUTO, waImage::INVERSE, waImage::WIDTH, waImage::HEIGHT))) {
                        $master = null;
                    }
                    $image->resize($resize_x, $resize_y, $master);
                }
                if (($crop_x && $image->width > $crop_x) || ($crop_y && $image->height > $crop_y)) {
                    if (!$crop_x) {
                        $crop_x = $image->width;
                    }
                    if (!$crop_y) {
                        $crop_x = $image->height;
                    }
                    $offset_x = waRequest::request('offset_x', waImage::CENTER, 'int');
                    $offset_y = waRequest::request('offset_y', waImage::CENTER, 'int');
                    $image->crop($crop_x, $crop_y, $offset_x, $offset_y);
                }
                $image->save($cache_filename);

                // Remember to remove cache image with main file
                if (empty($file['remove_too']) || !is_array($file['remove_too'])) {
                    $file['remove_too'] = array($cache_filename);
                } else {
                    $file['remove_too'][] = $cache_filename;
                }
                wa()->getStorage()->write($id, $file);
            }

            $file['tmp_name'] = $cache_filename;
        }

        waFiles::readFile($file['tmp_name'], waRequest::request('save') ? $file['name'] : null);
    }
}

