<?php
/**
 * Collection of helpers to enable use of Photos app as a storage for blog post images.
 */
class blogPhotosBridge
{
    public static function isEnabled()
    {
        return self::isAvailable() && wa()->getSetting('image_storage', 'photo_app', 'blog') == 'photo_app';
    }

    public static function isAvailable()
    {
        $apps = wa()->getApps();
        return isset($apps['photos']) && version_compare($apps['photos']['version'], '1.1.5') >= 0;
    }

    public static function is2xEnabled()
    {
        return self::isAvailable() && wa('photos')->getConfig()->getOption('enable_2x');
    }

    public static function loadAlbums(&$posts)
    {
        $album_ids = array();
        foreach($posts as &$p) {
            $p['album'] = null;
            if ($p['album_id']) {
                $album_ids[$p['album_id']] = $p['album_id'];
            }
        }
        unset($p);
        if (!$album_ids || !self::isAvailable()) {
            return $posts;
        }

        wa('photos');

        // Albums
        $album_model = new photosAlbumModel();
        $albums = $album_model->getById($album_ids);
        $albums[0] = $album_model->getEmptyRow();

        // Album photos and additional fields
        foreach ($albums as &$a) {
            $a['params'] = array();
            $a['photos'] = array();
            $a['frontend_link'] = photosFrontendAlbum::getLink($a);
            if (wa()->getEnv() == 'backend') {
                $a['backend_link'] = wa()->getAppUrl('photos').'#/album/'.$a['id'].'/';
            }
            if ($a['id']) {
                $collection = new photosCollection('album/'.$a['id']);
                $collection->setCheckRights(false);
                $a['photos'] = $collection->getPhotos("*,thumb,thumb_crop,thumb_big,frontend_link,tags", 0, 100500);
                if ($a['photos']) {
                    $a['photos'] = photosCollection::extendPhotos($a['photos']);
                }
            }
        }
        unset($a);

        // Album params
        $album_params_model = new photosAlbumParamsModel();
        foreach($album_params_model->get(array_keys($albums)) as $album_id => $params) {
            $albums[$album_id] += $params;
            $albums[$album_id]['params'] = $params;
        }

        // Attach albums to posts
        foreach($posts as &$p) {
            if ($p['album_id']) {
                if (!empty($albums[$p['album_id']])) {
                    $p['album'] = $albums[$p['album_id']];
                } else {
                    $p['album'] = $albums[0];
                }
            }
        }
        unset($p);

        return $posts;
    }
}

