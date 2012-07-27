<?php

class photosCommentsPluginBackendChangeStatusController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::get('id', null, waRequest::TYPE_INT);
        if (!$id) {
            throw new waException("Can't change status of comment: unknown id");
        }

        $status = waRequest::post('status', '', waRequest::TYPE_STRING_TRIM);
        if ($status == photosCommentModel::STATUS_DELETED || $status == photosCommentModel::STATUS_PUBLISHED) {
            $comment_model = new photosCommentModel();
            $comment_model->updateById($id, array(
                'status' => $status
            ));
        } else {
            $this->errors['unknown_status'] = _w('Unknown status');
        }
    }
}