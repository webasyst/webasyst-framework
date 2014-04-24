<?php

class photosAlbumSaveFieldController extends waJsonController
{
    private $availableFields = array(
        'name', 'note'
    );

    public function execute()
    {
        $name = waRequest::post('name', '', waRequest::TYPE_STRING_TRIM);
        if (in_array($name, $this->availableFields) === false) {
            throw new waException(_w("Can't update album: unknown field"));
        }
        $album_rights_model = new photosAlbumRightsModel();
        $id = waRequest::post('id', null, waRequest::TYPE_ARRAY_INT);
        if (is_array($id)) {
            $id = current($id);
        }
        if ($id) {
            $album_model = new photosAlbumModel();
            $album = $album_model->getById($id);
            if (!$album) {
                throw new waException(_w('Unknown album'));
            }
            if (!$album_rights_model->checkRights($album, true)) {
                throw new waException(_w("You don't have sufficient access rights"));
            }
            $value = waRequest::post('value', '', waRequest::TYPE_STRING_TRIM);

            $album_model->updateById($id, array(
                $name => $value
            ));
            $album['not_escaped_name'] = $value;
            $album['name'] = photosPhoto::escape($value);
            $this->response['album'] = $album;
        }
    }
}