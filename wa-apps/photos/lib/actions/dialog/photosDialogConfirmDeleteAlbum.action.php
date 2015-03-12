<?php

class photosDialogConfirmDeleteAlbumAction extends photosDialogConfirmViewAction
{
    public function __construct($params=null) {
        parent::__construct($params);
        $this->type = 'delete-album';
    }

    public function execute()
    {
        $album_id = waRequest::get('id', null, waRequest::TYPE_INT);

        $album_model = new photosAlbumModel();
        $albums = $album_model->getAlbums();
        $album = ifset($albums[$album_id]);

        if (!$album_id || !$album) {
            throw new waException(_w('Unknown album'));
        }

        $collection = new photosCollection('/album/'.$album_id);

        $this->view->assign('album', $album);
        $this->view->assign('photos_count', $collection->count());
        $this->view->assign('offspring', $this->getOffspringIds($albums, $album_id));
    }

    protected function getOffspringIds($albums, $album_id)
    {
        $offspring = array($album_id => true);
        $not_offspring = array(0 => true);
        unset($albums[$album_id], $albums[0]);

        do {
            $initial_count = count($albums);
            foreach($albums as $id => $a) {
                if (!empty($not_offspring[$a['parent_id']])) {
                    $not_offspring[$id] = true;
                    unset($albums[$id]);
                } else if (!empty($offspring[$a['parent_id']])) {
                    $offspring[$id] = true;
                    unset($albums[$id]);
                }
            }
        } while(count($albums) != $initial_count);

        unset($offspring[$album_id]);
        return array_keys($offspring);
    }
}
