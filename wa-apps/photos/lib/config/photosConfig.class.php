<?php

class photosConfig extends waAppConfig
{
    protected $sizes = array(
        'big' => '970',
        'middle' => '750',
        'thumb' => '200x0',
        'crop' => '96x96',
        'mobile' => '512'
    );

    public function getSize($name)
    {
        return isset($this->sizes[$name]) ? $this->sizes[$name] : null;
    }

    private $last_activity_time;

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
        $this->getLastLoginTime();
        $this->setCount();
    }

    public function onCount()
    {
        return null;
        $photo_model = new photosPhotoModel();
        $count = $photo_model->countAll($t = $this->getLastLoginTime(false));
        return $count ? $count : null;
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




    public function getLastLoginTime($app = true)
    {
        $expire_interval = $this->getOption('expire_interval');
        $expire_interval = ($expire_interval === null) ? 180 : max(0, intval($expire_interval));
        if (!$this->last_activity_time || !$app) {
            $storage = wa()->getStorage();
            $now = time();
            if ($activity = $storage->get('photos_last_activity_time')) {
                list($datetime, $timestamp) = $activity;
                if ($app && (($now - $timestamp) > $expire_interval) ){
                    $datetime = ($now - $expire_interval);
                    $storage->set('photos_last_activity_time', array($datetime,$now));
                    if ($contact_id = wa()->getUser()->getId()) {
                        $contact_settings = new waContactSettingsModel();
                        $contact_settings->set($contact_id, 'photos', 'last_login_time', date("Y-m-d H:i:s", $now));
                    }
                }
            } else {
                if ($contact_id = wa()->getUser()->getId()) {
                    $contact_settings = new waContactSettingsModel();
                    if ($last_login_time = $contact_settings->getOne($contact_id, 'photos', 'last_login_time')) {
                        if ($datetime = strtotime($last_login_time)) {
                            $datetime = max($datetime, $now - $expire_interval);
                        } else {
                            $datetime = $now - $expire_interval;
                        }
                    } else {
                        $datetime = $now - $expire_interval;
                    }
                    if ($app) {
                        $contact_settings->set($contact_id, 'photos', 'last_login_time', date("Y-m-d H:i:s", $now));
                    }
                } else {
                    $datetime = $now - $expire_interval;
                }
                if ($app) {
                    $storage->set('photos_last_activity_time', array($datetime,$now));
                }
            }
            if ($app) {
                $storage->set('photos_last_attend_time', $now);
                $this->last_activity_time = date("Y-m-d H:i:s", $datetime);
            } else {
                $datetime = max($datetime,$storage->get('photos_last_attend_time'));
                return date("Y-m-d H:i:s", $datetime);
            }
        }
        return $this->last_activity_time;
    }

    public function setLastLoginTime($datetime)
    {
        $contact_id = wa()->getUser()->getId();

        $contact_settings = new waContactSettingsModel();
        $contact_settings->set($contact_id, 'photos', 'last_login_time', $datetime);
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
        $result = wa()->event(array('photos', 'routing'), $routes);
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
    
    public function getSidebarWidth()
    {
        $settings_model = new waContactSettingsModel();
        $width = (int)$settings_model->getOne(
            wa()->getUser()->getId(),
            'shop',
            'sidebar_width'
        );
        if (!$width) {
            return 250;
        }
        return max(min($width, 400), 200);
    }
    
    public function setSidebarWidth($width)
    {
        $width = max(min((int)$width, 400), 200);
        $settings_model = new waContactSettingsModel();
        $settings_model->set(
            wa()->getUser()->getId(),
            'shop',
            'sidebar_width',
            $width
        );
    }
    
    public function getSaveQuality() {
        $quality = $this->getOption('save_quality');
        if(!$quality) {
            $quality = 90;
        }
        return $quality;
    }
    
}