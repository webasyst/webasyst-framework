<?php

class teamProfileCoverSaveController extends waJsonController
{
    public function execute()
    {
        try {
            $this->doExecute();
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    protected function handleException(Exception $e)
    {
        $message = $e->getMessage();
        $code = $e->getCode();
        if (!in_array($code, [403, 404])) {
            $code = 500;
        }

        switch ($code) {
            case 403:
                $error_code = 'access_denied';
                break;
            case 404:
                $error_code = 'not_found';
                break;
            default:
                $error_code = 'fail';
                break;
        }

        $this->getResponse()->setStatus($code);
        $this->errors[$error_code] = $message;
    }

    protected function doExecute()
    {
        $contact = $this->getContact();

        list($uploaded, $rejected) = $this->uploadFiles($this->getFiles());

        $cover_list = $this->newCoverList($contact->getId());
        foreach ($uploaded as $id => $file) {
            $error = $this->validateFile($file);
            if ($error) {
                $rejected[$id] = $error;
                unset($uploaded[$id]);
            }
        }

        $added = [];
        foreach ($uploaded as $id => $file) {
            /**
             * @var waRequestFile $file
             */
            $photo_id = $cover_list->add($file);
            if ($photo_id > 0) {
                $added[$id] = [
                    'photo_id' => $photo_id,
                    'name' => $file->name,
                ];
            } else {
                $rejected[$id] = [
                    'name' => $file->name,
                    'error' => 'not_added',
                    'description' => _w('Not added')
                ];
            }
        }

        $added_photo_ids = waUtils::getFieldValues($added, 'photo_id');

        $this->response = [
            'added' => $added,
            'rejected' => $rejected,
            'thumbnails' => $cover_list->getThumbnails($added_photo_ids)
        ];
    }

    protected function newCoverList($contact_id)
    {
        return new waContactCoverList($contact_id, [
            'size_aliases' => wa('team')->getConfig()->getProfileCoverSizeAliases()
        ]);
    }

    protected function validateFile(waRequestFile $file)
    {
        $mime_type = mime_content_type($file->tmp_name);
        if (!in_array($mime_type, ['image/gif', 'image/jpg', 'image/jpeg', 'image/png'], true)) {
            return [
                'name' => $file->name,
                'error' => 'not_allowed_mime_type',
                'description' => _w('Not allowed MIME type. Supported types are gif, jpg (jpeg), png.'),
            ];

        }
        return [];
    }

    protected function getContact()
    {
        $id = waRequest::post('id', null, 'int');
        $can_edit = teamUser::canEdit($id);
        if (!$id || !$can_edit) {
            throw new waRightsException(_w('Access denied'));
        }
        return new waContact($id);
    }

    protected function uploadFiles(waRequestFileIterator $files)
    {
        $uploaded = [];
        $failed = [];

        $idx = 0;
        foreach ($files as $file) {
            if ($file->error_code != UPLOAD_ERR_OK) {
                $failed[$idx] = [
                    'name' => $file->name,
                    'error' => $file->error_code,
                    'description' => $file->error,
                ];
            } else {
                $uploaded[$idx] = $file;
            }
            $idx++;
        }

        return [$uploaded, $failed];
    }

    protected function getFiles()
    {
        return waRequest::file($this->getFilesParamName());
    }

    protected function getFilesParamName()
    {
        return 'files';
    }
}
