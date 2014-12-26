<?php
/**
 * List of all root-level albums, default backend page
 */
class photosBackendAlbumsAction extends waViewAction
{
    public function execute()
    {
        // Load albums
        $album_model = new photosAlbumModel();
        $albums = $album_model->getAlbums();

        // We only care about root-level albums
        foreach($albums as $aid => $a) {
            if ($a['parent_id']) {
                unset($albums[$aid]);
                continue;
            }
        }

        // Load cover photos
        $album_model->keyPhotos($albums);

        $this->view->assign(array(
            'sidebar_width' => wa('photos')->getConfig()->getSidebarWidth(),
            'albums' => $albums,
        ));
    }

}

