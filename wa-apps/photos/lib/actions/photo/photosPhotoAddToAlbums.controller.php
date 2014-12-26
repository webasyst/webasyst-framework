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
        $photo_rights_model = new photosPhotoRightsModel();

        if (!$copy) { // it means manage with one photo
            $photo_id = $this->photo_ids[0];
            if (!$photo_rights_model->checkRights($photo_id, true)) {
                throw new waException("You don't have sufficient access rights");
            }
            $early_albums = array_keys($this->album_photos_model->getByField('photo_id', $photo_id, 'album_id'));
            // TODO: check rights for editing (take into account deleting!)
            $this->album_photos_model->set($photo_id, $album_id);
            $this->log('photos_move', 1);

            $albums = $this->getAlbumsCounters();
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
            $this->response['albums'] = array_values($albums);
            $this->response['old_albums'] = $old_albums;
        } else {
            // otherwise copy photos to albums
            $allowed_photo_id = $photo_rights_model->filterAllowedPhotoIds($this->photo_ids, true);
            $denied_photo_id = array_values(array_diff($this->photo_ids, $allowed_photo_id));

            $album_rights_model = new photosAlbumRightsModel();
            $allowed_album_id = $album_rights_model->filterAllowedAlbumIds($album_id, true);
            $denied_album_id = array_values(array_diff($album_id, $allowed_album_id));

            if ($allowed_album_id && $allowed_photo_id) {
                $this->album_photos_model->add($allowed_photo_id, $allowed_album_id);
                $this->log('photos_move', 1);
            }

            $albums = $this->getAlbumsCounters();
            $this->response['albums'] = array_values($albums);

            if ($denied_photo_id) {
                $this->response['alert_msg'] = photosPhoto::sprintf_wplural(
                        "The operation was not performed to %d photo (%%s)",
                        "The operation was not performed to %d photos (%%s)",
                count($denied_photo_id),
                _w("out of %d selected", "out of %d selected", count($this->photo_ids))
                ) . ', ' . _w("because you don't have sufficient access rights") . '.';
            }
        }

        // Set cover photos for albums if first photo just been added to it
        $photo_model = new photosPhotoModel();
        $album_model = new photosAlbumModel();
        $allowed_photo_id = isset($allowed_photo_id) ? $allowed_photo_id : array($photo_id);
        $no_cover_album_ids = array();
        foreach(ifset($allowed_album_id, $album_id) as $album_id) {
            if (!empty($albums[$album_id]) && empty($albums[$album_id]['key_photo_id'])) {
                $no_cover_album_ids[] = $album_id;
            }
        }
        $photos = array();
        while ($allowed_photo_id && $no_cover_album_ids) {
            // Get random photo from added and make sure it exists
            shuffle($allowed_photo_id);
            $photo_id = array_pop($allowed_photo_id);
            if (!empty($photos[$photo_id])) {
                $photo = $photos[$photo_id];
            } else {
                $photos[$photo_id] = $photo = $photo_model->getById($photo_id);
            }
            if (!$photo) {
                continue;
            }

            // Photo exists, so add it back
            $allowed_photo_id[] = $photo_id;

            // Set cover for one album
            $album_id = array_pop($no_cover_album_ids);
            $album_model->updateById($album_id, array(
                'key_photo_id' => $photo_id,
            ));
            photosPhoto::generateThumbs($photo, array('192x192'));
        }
    }

    private function getAlbumsCounters()
    {
        $config = $this->getConfig();
        $last_activity_datetime = $config->getLastLoginTime();
        $albums = array();
        $photo_albums = $this->album_photos_model->getAlbums($this->photo_ids, array('id', 'name', 'key_photo_id'));
        foreach ($photo_albums as &$p_albums) {
            foreach ($p_albums as $a_id => &$album) {
                if (!isset($albums[$a_id])) {
                    $collection = new photosCollection('/album/'.$a_id);
                    $album['count'] = $collection->count();
                    //$album['count_new'] = XXX;
                    $album['count_new'] = 0;
                    $albums[$a_id] = $album;
                }
            }
            unset($album);
        }
        unset($p_albums);
        return $albums;
    }
}