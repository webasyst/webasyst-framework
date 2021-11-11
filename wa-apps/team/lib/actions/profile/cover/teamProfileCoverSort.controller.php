<?php

class teamProfileCoverSortController extends waJsonController
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

    protected function newCoverList($contact_id)
    {
        return new waContactCoverList($contact_id, [
            'size_aliases' => wa('team')->getConfig()->getProfileCoverSizeAliases()
        ]);
    }

    protected function doExecute()
    {
        $contact = $this->getContact();
        $photo_ids = $this->getPhotoIds();

        $cover_list = $this->newCoverList($contact->getId());
        if ($photo_ids) {
            $cover_list->sort($photo_ids);
        }
        $this->response = [
            'thumbnails' => $cover_list->getThumbnails()
        ];
    }

    protected function getPhotoIds()
    {
        $photo_ids = waRequest::post('photo_id', [], waRequest::TYPE_ARRAY_INT);
        return waUtils::dropNotPositive($photo_ids);
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
}
