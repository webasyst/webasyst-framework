<?php

class photosConfig extends waAppConfig
{
    protected $sizes = array(
        'big' => '970',
        'middle' => '750',
        'thumb' => '200x0',
        'crop' => '96x96',
    );

    public function getSize($name)
    {
        return isset($this->sizes[$name]) ? $this->sizes[$name] : null;
    }

    public function getSizes($type = 'all')
    {
        $custom_sizes = $this->getOption('sizes');
        if ($type == 'custom') {
            return $custom_sizes;
        } elseif ($type == 'system') {
            return $this->sizes;
        }
        $sizes = array_merge(array_values($this->sizes), array_values($custom_sizes));
        return array_unique($sizes);
    }

    public function onInit()
    {
        if ($contact_id = wa()->getUser()->getId()) {
            $last_login_time = $this->getLastLoginTime();
            if (!$last_login_time || !strtotime($last_login_time)) {
                $last_login_time = $this->setLastLoginTime($contact_id);
                $this->countPhotosInAlbum();
            }
        }
    }

    protected function countPhotosInAlbum()
    {
        $album_count_model = new photosAlbumCountModel();
        foreach ($album_count_model->getAlbumsWithoutCalculatedCount() as $album_id) {
            $collection = new photosCollection('/album/'.$album_id);
            $collection->count();
        }
    }

    public function getPhotoPath($photo_id, $extension, $thumb = false)
    {
        $file_name = $this->getPhotoFolder($photo_id).'/'.$photo_id.'.'.$extension;
        return wa()->getDataPath($file_name, false, $this->application);
    }


    public function getPhotoThumbDir($photo_id)
    {
        $path = $this->getPhotoFolder($photo_id).'/'.$photo_id;
        return wa()->getDataPath($path, true);
    }

    private function getPhotoFolder($photo_id)
    {
        $str = str_pad($photo_id, 4, '0', STR_PAD_LEFT);
        return substr($str, -2).'/'.substr($str, -4, 2);
    }

    public function getPhotoUrl($photo_id, $extension, $size)
    {
        $path = $this->getPhotoFolder($photo_id).'/'.$photo_id.'/'.$photo_id.'.'.$size.'.'.$extension;
        if (self::systemOption('mod_rewrite')) {
            return wa()->getDataUrl($path, true);
        } else {
            $wa = wa();
            if (file_exists($wa->getDataPath($path, true))) {
                return $wa->getDataUrl($path, true);
            } else {
                return $wa->getDataUrl('thumb.php/'.$path, true);
            }
        }
    }

    public function getLastLoginTime()
    {
        return wa()->getStorage()->read('photos_last_login_time');
    }

    public function setLastLoginTime($contact_id)
    {
        $contact_id = $contact_id ? $contact_id : wa()->getUser()->getId();
        $datetime = date("Y-m-d H:i:s", time());

        $contact = new waContactSettingsModel();
        $contact->set($contact_id, 'photos', 'last_login_time', $datetime);
        wa()->getStorage()->write('photos_last_login_time', $datetime);
        return $datetime;
    }

    public function getRouting($route = array())
    {
        $url_type = isset($route['url_type']) ? $route['url_type'] : 0;
        $routes = parent::getRouting($route);
        if ($routes) {
            if ($url_type == 0) {
                $routes = $routes[0];
            } else {
                $routes = $routes[1];
            }
        }
        /**
         * Extend routing via plugin routes
         * @event routing
         * @param array $routes
         * @return array routes collected for every plugin
         */
        $result = wa()->event('routing', $routes);
        $all_plugins_routes = array();
        foreach ($result as $plugin_id => $routing_rules) {
            if ($routing_rules) {
                $plugin = str_replace('-plugin', '', $plugin_id);
                if ($url_type == 0) {
                    $routing_rules = $routing_rules[0];
                } else {
                    $routing_rules = $routing_rules[1];
                }
                foreach ($routing_rules as $url => &$route) {
                    if (!is_array($route)) {
                        list($route_ar['module'], $route_ar['action']) = explode('/', $route);
                        $route = $route_ar;
                    }
                    $route['plugin'] = $plugin;
                    $all_plugins_routes[$url] = $route;
                }
                unset($route);
            }
        }
        $routes = array_merge($all_plugins_routes, $routes);

        return $routes;
    }
}