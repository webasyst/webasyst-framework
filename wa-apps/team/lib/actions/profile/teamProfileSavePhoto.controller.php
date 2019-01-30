<?php
/** Contact photo editor: accepts a file and a crop area. */
class teamProfileSavePhotoController extends webasystProfileSavePhotoController
{
    protected function getId()
    {
        $id = waRequest::post('id', null, 'int');
        if ($id && !teamUser::canEdit($id)) {
            return null;
        }
        return $id;
    }
}
