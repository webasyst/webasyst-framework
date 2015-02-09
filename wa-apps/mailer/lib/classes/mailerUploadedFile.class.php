<?php

/**
 * Server-side helper to upload files via XHR.
 * For JS counterpart see: http://github.com/valums/file-uploader
 *
 * For use case see:
 * - mailerBackendUploadController,
 * - mailerBackendUploadedController.
 */
class mailerUploadedFile extends waRequestFile
{
    protected $file_id;
    protected $remove_too = array();

    /**
     * @todo allow multiple uploads with the same file id:
     * extend waRequestFileIterator instead of waRequestFile, add $multiple parameter and rewrite constructor.
     *
     * @param string $file_id session storage key to load file info from
     */
    public function __construct($file_id)
    {
        $this->file_id = $file_id;

        // populate $this->data to allow parent functions to do the job
        $data = wa()->getStorage()->read($file_id);
        if (!empty($data['remove_too']) && is_array($data['remove_too'])) {
            $this->remove_too = $data['remove_too'];
        }

        try {
            parent::__construct($data, true);
        } catch(Exception $e) {
            // Bad data in storage. Probably missing uploaded file.
            if (!empty($data['tmp_name'])) {
                @unlink($data['tmp_name']);
            }
            foreach($this->remove_too as $fname) {
                @unlink($fname);
            }
            wa()->getStorage()->remove($this->file_id);
        }
    }

    public function delete()
    {
        if (!$this->uploaded()) {
            return;
        }

        @unlink($this->tmp_name);
        foreach($this->remove_too as $fname) {
            @unlink($fname);
        }
        wa()->getStorage()->remove($this->file_id);
    }

    /**
     * Helper to save file uploaded via JS file uploader.
     * @param string $file_id session storage key to save file info to
     * @param string $dir
     * @param array $allowedExtensions list of valid extensions, ex. array("jpeg", "xml", "bmp"); empty to allow anything
     * @param int $sizeLimit max file size in bytes (defaults to 10 Mb)
     */
    public static function uploadToTmp($file_id, $dir, $allowedExtensions = array(), $sizeLimit = 10485760)
    {
        $dir = preg_replace('~[\\\\/]*$~', '/', $dir);

        $uploader = new qqFileUploader($allowedExtensions, $sizeLimit);

        // Check server settings
        $postSize = $uploader->toBytes(ini_get('post_max_size'));
        $uploadSize = $uploader->toBytes(ini_get('upload_max_filesize'));
        if ($postSize < $sizeLimit || $uploadSize < $sizeLimit) {
            $size = max(1, ceil($sizeLimit / 1024.0 / 1024)) . 'M';
            waLog::log('Note: post_max_size and/or upload_max_filesize settings are too low. Increase to '.$size);
        }

        $result = $uploader->handleUpload($dir);
        if (empty($result['error'])) {
            // remove old file if exists
            if ( ( wa()->getStorage()->read($file_id))) {
                $file = new self($file_id);
                $file->delete();
            }

            // Save new one to session
            wa()->getStorage()->write($file_id, $result);
        }
        return $result;
    }
}

/**
 * Helper to upload file via XHR or iframe depending on where browser can send it.
 */
class qqFileUploader
{
    private $allowedExtensions;
    private $sizeLimit;
    private $file;

    function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760) {
        $allowedExtensions = array_map("strtolower", $allowedExtensions);

        $this->allowedExtensions = $allowedExtensions;
        $this->sizeLimit = $sizeLimit;

        if (waRequest::get('qqfile')) {
            $this->file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = false;
        }
    }

    public function toBytes($str) {
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    }

    /**
     * Returns array('error'=>'error message') or array similar to $_FILES[...]
     */
    function handleUpload($uploadDirectory){
        if (!is_writable($uploadDirectory)){
            return array('error' => _w("Server error. Upload directory isn't writable."));
        }

        if (!$this->file){
            return array('error' => _w('No files were uploaded.'));
        }

        if ( ( $error = $this->file->getError())) {
            return array('error' => $error);
        }

        $pathinfo = pathinfo($this->file->getName());
        $ext = $pathinfo['extension'];

        // Check against allowed extensions
        if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => sprintf_wp('Valid file types: %s.', $these));
        }

        // generate unique file name
        do {
            $full_path = $uploadDirectory . md5(uniqid().$pathinfo['filename']) . '.'.$ext;
        } while (file_exists($full_path));

        // Move uploaded file
        if (!$this->file->save($full_path)) {
            if ( ( $error = $this->file->getError())) {
                return array('error' => $error);
            }
            return array('error' => _w('Could not save uploaded file.'));
        }

        // Check file size
        $size = filesize($full_path);
        if ($size > $this->sizeLimit) {
            unlink($full_path);
            return array('error' => _w('File is too large'));
        }

        return array(
            'name' => $this->file->getName(),
            'type' => $this->file->getType(),
            'size' => $size,
            'tmp_name' => $full_path,
            'error' => UPLOAD_ERR_OK,
        );
    }
}

/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr
{
    protected $error = null;

    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);

        $content_length = null;
        if (isset($_SERVER["CONTENT_LENGTH"])) {
            $content_length = (int) $_SERVER["CONTENT_LENGTH"];
        }

        if ($content_length && $realSize != $content_length) {
            $this->error = _w('The upload was cancelled, or server error encountered');
            return false;
        }

        $target = fopen($path, "w");
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);

        return true;
    }
    function getName() {
        return $_GET['qqfile'];
    }
    function getError() {
        return $this->error ? $this->error : '';
    }
    function getType() {
        return $_SERVER['CONTENT_TYPE'];
    }
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
        if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
            return false;
        }
        return true;
    }
    function getName() {
        return $_FILES['qqfile']['name'];
    }
    function getType() {
        return $_FILES['qqfile']['type'];
    }
    function getError() {
        return waRequestFile::getError($_FILES['qqfile']['error']);
    }
}

