<?php

class photosSitemapConfig extends waSitemapConfig
{
    public function execute()
    {
        $routes = $this->getRoutes();
        $app_id = wa()->getApp();

        $album_model = new photosAlbumModel();
        $album_photos_model = new photosAlbumPhotosModel();
        $page_model = new photosPageModel();

        $real_domain = $this->routing->getDomain(null, true, false);

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
            $this->addUrl(photosCollection::getFrontendLink('favorites', false), $favorites_lastmod_time ? $favorites_lastmod_time : time());

            // pages

            $main_url = wa()->getRouteUrl($app_id."/frontend", array(), true, $real_domain);
            $domain = $this->routing->getDomain(null, true);
            $sql = "SELECT full_url, url, create_datetime, update_datetime FROM ".$page_model->getTableName().'
                    WHERE status = 1 AND domain = s:domain AND route = s:route';
            $pages = $page_model->query($sql, array('domain' => $domain, 'route' => $route['url']))->fetchAll();
            foreach ($pages as $p) {
                $this->addUrl($main_url.$p['full_url'], $p['update_datetime'] ? $p['update_datetime'] : $p['create_datetime'], self::CHANGE_MONTHLY, 0.6);
            }


            // main page
            $this->addUrl($main_url, time(), self::CHANGE_DAILY, 1.0);
        }
    }
}