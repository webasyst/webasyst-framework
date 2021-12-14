<?php

class photosFrontendPhoto
{
    /**
     * @param $photo
     * @param null $album
     * @param bool $absolute
     * @param array $options
     *      string $options['type'] [optional] - 'current', 'first'. Default is 'current'
     *          'current' - use current domain for build link
     *          'first' - iterate through all domains and build urls until not empty url is built
     * @return string|null
     * @throws waException
     */
    public static function getLink($photo, $album = null, $absolute = true, $options = [])
    {
        if (isset($photo['status']) && $photo['status'] <= 0 && empty($photo['hash'])) {
            return null;
        }

        static $wa = null;
        $wa = $wa ? $wa : wa();

        $options = is_array($options) ? $options : [];
        $type = ifset($options['type']);
        if (!in_array($type, ['current', 'first'])) {
            $type = 'current';
        }

        $link = null;
        $current_domain = $wa->getRouting()->getDomain(null, true, false);

        $domains = [$current_domain];
        if ($type === 'first') {
            foreach ($wa->getRouting()->getDomains() as $domain) {
                if ($domain != $current_domain) {
                    $domains[] = $domain;
                }
            }
        }

        if (is_null($album)) {
            foreach ($domains as $domain) {
                $link = $wa->getRouteUrl('photos/frontend/photo', array(
                    'url' => $photo['url'].(isset($photo['status']) && ($photo['status'] <= 0 && !empty($photo['hash'])) ? ':'.$photo['hash'] : '')
                ), $absolute, $domain);
                if ($link) {
                    break;
                }
            }
        } else if (is_array($album)) {
            foreach ($domains as $domain) {
                $link = $wa->getRouteUrl('photos/frontend/photo', array(
                    'url' => $album['full_url'] . '/' . $photo['url']
                ), $absolute, $domain);
                if ($link) {
                    break;
                }
            }
        } else {
            $hash = $album;
            if (substr($hash, 0, 1) == '#') {
                $hash = substr($hash, 1);
            }
            $hash = trim($hash, '/');
            $hash = explode('/', $hash);

            $params = array(
                'url' => $photo['url']
            );
            if (count($hash) >= 2) {
                $params[$hash[0]] = $hash[1];
            } else if (count($hash) == 1) {
                $params[$hash[0]] = $hash[0];
            }

            foreach ($domains as $domain) {
                $link = $wa->getRouteUrl('photos/frontend/photo', $params, $absolute, $domain);
                if ($link) {
                    break;
                }
            }
        }

        return $link ? rtrim($link, '/').'/' : null;
    }
}
