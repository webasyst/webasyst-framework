<?php

class photosFrontendAlbum
{
    /**
     * @param string $album
     * @param array $options
     *      string $options['type'] [optional] - 'current', 'first'. Default is 'current'
     *          'current' - use current domain for build link
     *          'first' - iterate through all domains and build urls until not empty url is built
     * @return string|null
     * @throws waException
     */
    public static function getLink($album = '', $options = [])
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

        $options = is_array($options) ? $options : [];
        $type = ifset($options['type']);
        if (!in_array($type, ['current', 'first'])) {
            $type = 'current';
        }

        $current_domain = $routing->getDomain(null, true, false);

        $domains = [$current_domain];
        if ($type === 'first') {
            foreach ($routing->getDomains() as $domain) {
                if ($domain != $current_domain) {
                    $domains[] = $domain;
                }
            }
        }

        $link = null;
        foreach ($domains as $domain) {
            $link = $routing->getUrl('photos/frontend/album', ['url' => $album], true, $domain);
            if ($link) {
                break;
            }
        }

        return $link ? rtrim($link, '/').'/' : null;
    }

    public static function escapeFields($album)
    {
        // escape
        $album['name'] = photosPhoto::escape($album['name']);
        return $album;
    }
}
