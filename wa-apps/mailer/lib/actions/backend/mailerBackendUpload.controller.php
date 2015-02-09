<?php

/**
 * Controller to upload files via XHR.
 * For JS counterpart see: http://github.com/valums/file-uploader
 *
 * Save file to tmp dir and write array similar to $_FILES[...] to session.
 * Uploaded file may later be retrieved using
 *
 * $file = new mailerUploadedFile($session_key); // instanceof waRequestFile
 *
 * See mailerUploadedFile class for details.
 */
class mailerBackendUploadController extends waController
{
    public function execute()
    {
        $allowed = array(
            'template_preview' => array('jpg', 'jpeg', 'png', 'gif'),
            'template_import' => array('zip'),
        );

        $id = waRequest::request('file_id');
        if (!isset($allowed[$id])) {
            throw new waException('Unknown file id.', 403);
        }

        $result = mailerUploadedFile::uploadToTmp($id, wa()->getTempPath('tmp_upload'), $allowed[$id]);

        // Check if file is an image
        if (!$result['error'] && waRequest::request('image')) {
            $file = new mailerUploadedFile($id);
            try {
                $file->waImage();
            } catch (waException $e) {
                $result = array('error' => _w('File is not an image or is broken.'));
                $file->delete();
            }
        }

        if (!$result['error']) {
            $result = array('success' => true);
        }
        echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
    }
}

