<?php

class photosAlbumGetTreeMethod extends waAPIMethod
{
    protected $method = 'GET';

    public function execute()
    {
        $parent_id = waRequest::get('parent_id');
        $depth = waRequest::get('depth', null, 'int');
        
        $album_model = new photosAlbumModel();
        $albums = $album_model->getAlbums();

        foreach ($albums as $album_id => $album) {
            if ($album['parent_id'] && isset($albums[$album['parent_id']])) {
                $albums[$album['parent_id']]['albums'][] = &$albums[$album_id];
            }
        }
        if (!$parent_id) {
            foreach ($albums as $album_id => $album) {
                if ($album['parent_id']) {
                    unset($albums[$album_id]);
                }
            }
            $albums = array_values($albums);
        } else {
            $albums = array($albums[$parent_id]);
        }
        
        if ($depth !== null) {
            $albums = array('albums' => $albums);
            $this->cutOffSubtree($albums, $depth + 1);
            $albums = $albums['albums'];
        }
        
        $this->response = $albums;
        $this->response['_element'] = 'album';
    }
    
    public function cutOffSubtree(&$tree, $depth = 0)
    {
        if ($depth === 0) {
            if (isset($tree['albums'])) {
                unset($tree['albums']);
            }
        } else {
            if (isset($tree['albums'])) {
                foreach ($tree['albums'] as &$subtree) {
                    $this->cutOffSubtree($subtree, $depth - 1);
                }
                unset($subtree);
            }
        }
    }
}