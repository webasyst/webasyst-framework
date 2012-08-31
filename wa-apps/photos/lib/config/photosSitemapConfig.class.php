<?php

class photosSitemapConfig extends waSitemapConfig
{
    public function execute()
    {
        $routes = $this->getRoutes();
        $app_id = wa()->getApp();

        $album_model = new photosAlbumModel();
        $album_photos_model = new photosAlbumPhotosModel();

        foreach ($routes as $route) {
            $this->routing->setRoute($route);

            $albums = $album_model->getByField(array(
                'type' => photosAlbumModel::TYPE_STATIC,
                'status' => 1
            ), 'id');

            $favorites_lastmod_time = null;

            // albums and photos in albums
            if ($albums) {
                $current_album_id = null;
                $current_album_lastmod_time = null;
                foreach ((array)$album_photos_model->getPhotos(array_keys($albums)) as $photo) {
                    if ($photo['album_id'] != $current_album_id) {
                        if ($current_album_id) {
                            $this->addUrl(photosFrontendAlbum::getLink($albums[$current_album_id]), $current_album_lastmod_time);
                        }
                        $current_album_id = $photo['album_id'];
                    }
                    $photo_url = photosFrontendPhoto::getLink($photo, $albums[$current_album_id]);

                    $lastmod_time = max($photo['edit_datetime'], $photo['upload_datetime']);
                    $this->addUrl($photo_url, $lastmod_time);
                    $current_album_lastmod_time = max($current_album_lastmod_time, $lastmod_time);
                    if ($photo['rate'] > 0) {
                        $favorites_lastmod_time = max($favorites_lastmod_time, $lastmod_time);
                    }
                }
            }

            // just photos (that aren't inside any album)
            foreach ((array)$album_photos_model->getPhotos() as $photo) {
                $photo_url = photosFrontendPhoto::getLink($photo);
                $lastmod_time = max($photo['edit_datetime'], $photo['upload_datetime']);
                $this->addUrl($photo_url, $lastmod_time);
                if ($photo['rate'] > 0) {
                    $favorites_lastmod_time = max($favorites_lastmod_time, $lastmod_time);
                }
            }

            // favorite page
            $this->addUrl(photosCollection::getFrontendLink('favorites', false), $favorites_lastmod_time);

            // main page
            wa()->getRouteUrl($app_id."/frontend", array(), true);
        }
    }
}