<?php

class photosPhotoAddToAlbumsController extends waJsonController
{
    /**
     * @var photosAlbumPhotosModel
     */
    private $album_photos_model;

    private $photo_ids;

    public function execute()
    {
        $this->photo_ids = waRequest::post('photo_id', array(), waRequest::TYPE_ARRAY_INT);
        $album_id = waRequest::post('album_id', array(), waRequest::TYPE_ARRAY_INT);
        $copy = waRequest::post('copy', 1, waRequest::TYPE_INT);

        $this->album_photos_model = new photosAlbumPhotosModel();

        $config = $this->getConfig();
        $last_login_datetime = $config->getLastLoginTime();

        if (!$copy) { // it means manage with one photo and make setting albums
            $photo_id = $this->photo_ids[0];
            $early_albums = array_keys($this->album_photos_model->getByField('photo_id', $photo_id, 'album_id'));
            // TODO: check rights for editing (take into account deleting!)
            $this->album_photos_model->set($photo_id, $album_id);

            $this->response['albums'] = $this->getAlbumsCounters($last_login_datetime);
            $old_albums = array();
            foreach ($early_albums as $a_id) {
                if (!isset($albums[$a_id])) {
                    $collection = new photosCollection('/album/'.$a_id);
                    $album = array(
                        'id' => $a_id,
                        'count' => $collection->count(),
                        'count_new' => /*isset($last_uploaded[$a_id]) ? $last_uploaded[$a_id] : 0*/0
                    );
                    $old_albums[] = $album;
                }
            }
            $this->response['old_albums'] = $old_albums;
        } else {
            // otherwise adding
            $album_rights_model = new photosAlbumRightsModel();
            $allowed_album_id = $album_rights_model->filterAllowedAlbumIds($album_id, true);
            $denied_album_id = array_values(array_diff($album_id, $allowed_album_id));

            if ($allowed_album_id) {
                $this->album_photos_model->add($this->photo_ids, $allowed_album_id);
                $this->response['albums'] = $this->getAlbumsCounters($last_login_datetime);
            }

            if ($denied_album_id) {
                $this->response['alert_msg'] = photosPhoto::sprintf_wplural(
                        "The operation was not performed to %d album (%%s)",
                        "The operation was not performed to %d albums (%%s)",
                        count($denied_album_id),
                        _w("out of %d selected", "out of %d selected", count($album_id))
                ) . ', ' . _w("because you don't have sufficient access rights") . '.';

            }
        }
    }

    private function getAlbumsCounters($last_login_datetime)
    {
        $albums = array();
        $photo_albums = $this->album_photos_model->getAlbums($this->photo_ids, array('id', 'name'));
        $last_uploaded = $this->album_photos_model->lastUploadedCounters($last_login_datetime);
        foreach ($photo_albums as &$p_albums) {
            foreach ($p_albums as $a_id => &$album) {
                if (!isset($albums[$a_id])) {
                    $collection = new photosCollection('/album/'.$a_id);
                    $album['count'] = $collection->count();
                    //$album['count_new'] = isset($last_uploaded[$a_id]) ? $last_uploaded[$a_id] : 0;
                    $album['count_new'] = 0;
                    $albums[$a_id] = $album;
                }
            }
        }
        unset($p_albums, $album);
        return array_values($albums);
    }
}