<?php

class photosFrontendAlbum
{
    public static function getLink($album = '')
    {
        static $wa = null;
        $wa = $wa ? $wa : wa();
        if (is_array($album)) {
            $link = $wa->getRouteUrl('photos/frontend/album', array(
                'url' => $album['full_url']
            ), true);
            return $link ? rtrim($link, '/').'/' : null;
        } else {
            $album = (string) $album;
            $link = $wa->getRouteUrl('photos/frontend/album', array(
                'url' => $album
            ), true);
            return $link ? rtrim($link, '/').'/' : null;
        }
    }

    public static function escapeFields($album)
    {
        // escape
        $album['name'] = photosPhoto::escape($album['name']);
        return $album;
    }
}