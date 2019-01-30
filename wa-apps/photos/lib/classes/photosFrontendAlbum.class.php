<?php

class photosFrontendAlbum
{
    public static function getLink($album = '')
    {
        static $routing = null;
        if($routing === null) {
             $routing = wa()->getRouting();
        }

        if (is_array($album)) {
            $album = $album['full_url'];
        } else {
            $album = (string) $album;
        }

        $link = $routing->getUrl('photos/frontend/album', array(
            'url' => $album,
        ), true, $routing->getDomain(null, true, false));

        return $link ? rtrim($link, '/').'/' : null;
    }

    public static function escapeFields($album)
    {
        // escape
        $album['name'] = photosPhoto::escape($album['name']);
        return $album;
    }
}