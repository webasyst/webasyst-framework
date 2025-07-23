<?php

class siteConfigureSaveController extends waJsonController
{
    public function execute()
    {
        $path = $this->getConfig()->getPath('config', 'routing');
        if (file_exists($path)) {
            $routes = include($path);
        } else {
            $routes = array();
        }
        $domain = siteHelper::getDomain();
        $is_alias = wa()->getRouting()->isAlias($domain);

        $url = siteHelper::validateDomainUrl(waRequest::post('url', '', 'string'));
        $url = trim(waIdna::enc($url));

        if (!$url) {
            $this->errors = sprintf(_w("Incorrect domain URL: %s"), waRequest::post('url', '', 'string'));
            return;
        }

        $event_params = array(
            'config' => array(),
        );
        $domain_model = new siteDomainModel();

        if ($url != $domain) {
            // domain already exists
            if ($domain_model->getByName($url)) {
                $this->errors = sprintf(_w("A site with domain name %s already exists in this Webasyst account."), $url);
                return;
            }
            $event_params['renamed_from_domain'] = $domain;
            $domain_model->updateById(siteHelper::getDomainId(), array('name' => $url));

            if (!$is_alias) {
                $routes[$url] = $routes[$domain];
                // move configs
                $old = $this->getConfig()->getConfigPath('domains/' . $domain . '.php');
                if (file_exists($old)) {
                    waFiles::move($old, $this->getConfig()->getConfigPath('domains/' . $url . '.php'));
                }
                $old = wa()->getDataPath('data/' . $domain . '/', true, 'site', false);
                if (file_exists($old)) {
                    waFiles::move($old, wa()->getDataPath('data/' . $url . '/', true));
                    clearstatcache();
                    try {
                        waFiles::delete($old, true);
                    } catch (waException $e) {
                    }
                }
            } else {
                $routes[$url] = $is_alias; // $is_alias - this not boolean value, this alias domain name ¯\_(ツ)_/¯
            }

            unset($routes[$domain]);
            $domain = $url;
            siteHelper::setDomain(siteHelper::getDomainId(), $domain);
        }

        $title = waRequest::post('title');

        $domain_model->updateById(siteHelper::getDomainId(), array(
            'title' => $title
        ));

        if (!$is_alias) {
            // save wa_apps
            $domain_config_path = $this->getConfig()->getConfigPath('domains/' . $domain . '.php');
            if (file_exists($domain_config_path)) {
                $orig_domain_config = $domain_config = include($domain_config_path);
            } else {
                $orig_domain_config = $domain_config = array();
            }
            $save_config = false;
            if ($title) {
                $domain_config['name'] = $title;
                $save_config = true;
            } else {
                if (isset($domain_config['name'])) {
                    unset($domain_config['name']);
                    $save_config = true;
                }
            }

            if (waRequest::post('wa_apps_type')) {
                $apps = waRequest::post('apps');
                if (!$domain_config) {
                    // create directory
                    waFiles::create($domain_config_path);
                }
                $domain_config['apps'] = array();
                foreach ($apps['url'] as $i => $u) {
                    $domain_config['apps'][] = array(
                        'url' => $u,
                        'name' => $apps['name'][$i]
                    );
                }
                $save_config = true;
            } else {
                if (isset($domain_config['apps'])) {
                    unset($domain_config['apps']);
                    $save_config = true;
                }
            }

            // CDN
            $cdn_list = waRequest::post('cdn', array(), waRequest::TYPE_ARRAY_TRIM);
            $cdns = array();
            foreach ($cdn_list as $_cdn) {
                if (!empty($_cdn)) {
                    $cdns[] = $_cdn;
                }
            }

            if (!empty($cdns[0])) {
                $domain_config['cdn'] = $cdns[0];
                $domain_config['cdn_list'] = $cdns;
                $save_config = true;
            } elseif (!empty($domain_config['cdn']) || !empty($domain_config['cdn_list'])) {
                unset($domain_config['cdn']);
                unset($domain_config['cdn_list']);
                $save_config = true;
            }

            // save other settings
            foreach (array('head_js', 'google_analytics') as $key) {
                if (!empty($domain_config[$key]) || waRequest::post($key)) {
                    $domain_config[$key] = waRequest::post($key);
                    $save_config = true;
                }
            }

            $ssl_all = waRequest::post('ssl_all', null, waRequest::TYPE_STRING);

            if (isset($ssl_all)) {
                $domain_config['ssl_all'] = true;
            } else {
                $domain_config['ssl_all'] = false;
            };

            //Invert notifications settings key. Made to not create a meta update. @todo in webasyst 2
            if (waRequest::post('url_notification')) {
                $domain_config['url_notification'] = false;
                $save_config = true;
            } else {
                $domain_config['url_notification'] = true;
                $save_config = true;
            }

            $domain_config['touchicon_title'] = waRequest::post('touchicon_title', '', waRequest::TYPE_STRING_TRIM);

            //Delete cache problem domains
            $cache_domain = new waVarExportCache('problem_domains', 3600, 'site/settings/');
            $cache_domain->delete();
            //Remove notification
            wa()->getStorage()->del('apps-count');

            $this->saveFavicon();
            $this->saveTouchicon();
            siteHelper::updateFaviconsConfig($domain_config, true);

            if ($save_config && !waUtils::varExportToFile($domain_config, $domain_config_path)) {
                $this->errors = sprintf(_w('Settings could not be saved due to insufficient file write permissions for folder “%s”.'), 'wa-config/apps/site/domains');
            } else {
                $domain_config = $orig_domain_config;
            }
            $event_params['config'] = $domain_config;
        }
        $this->saveRobots();

        waUtils::varExportToFile($routes, $path);

        $this->logAction('site_edit', $domain);

        $event_params = $domain_model->getById(siteHelper::getDomainId()) + array(
            'routes' => ifset($routes, $url, null),
        ) + $event_params;
        /**
         * @event domain_save
         * @return void
         */
        wa('site')->event('domain_save', $event_params);
    }

    protected function saveFavicon()
    {
        $favicon = waRequest::file('favicon');
        if ($favicon->uploaded()) {
            $allowed_extensions = ['ico', 'png'];
            $allowed_mime_types = ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png'];
            $ext = strtolower($favicon->extension);
            if (!in_array($ext, $allowed_extensions) && !in_array($favicon->type, $allowed_mime_types)) {
                $this->errors = sprintf_wp(
                    'Only files with name extensions %s are allowed.',
                    implode(', ', array_map(function($ext) { return '.'.$ext; }, $allowed_extensions))
                );
            } else {
                $path = wa()->getDataPath('data/'.siteHelper::getDomain().'/', true);
                if (!file_exists($path) || !is_writable($path)) {
                    $this->errors = sprintf(_w('File could not be saved due to insufficient file write permissions for folder “%s”.'), 'wa-data/public/site/data/'.siteHelper::getDomain());
                } else {
                    if ($ext === 'ico') {
                        if (!$favicon->moveTo($path, 'favicon.ico')) {
                            $this->errors = _w('Failed to upload file.');
                        } else {
                            $this->removeIcons([
                                'favicon-96.png',
                            ]);
                        }
                    } else {
                        // if PNG favicon
                        $image_width = $favicon->waImage()->width;
                        $image_height = $favicon->waImage()->height;
                        // create copies of icons with a different resolution

                        $max_png_size = 16;
                        $ico_sizes = [];
                        foreach ([16, 32, 48] as $size) {
                            if ($image_width >= $size && $image_height >= $size) {
                                $max_png_size = $size;
                                $ico_sizes[] = [$size, $size];
                            }
                        }

                        $max_png_size = $image_width >= 96 && $image_height >= 96 ? 96 : $max_png_size;

                        $favicon->waImage()->resize($max_png_size, $max_png_size)->save($path.'favicon-96.png');

                        $imageToIco = new siteImageToIco($favicon->waImage()->file, $ico_sizes);
                        if (!$imageToIco->save_ico($path.'favicon.ico')) {
                            $this->errors = _w('Failed to upload file.');
                        }
                    }
                }
            }
        } elseif ($favicon->error_code != UPLOAD_ERR_NO_FILE) {
            $this->errors = $favicon->error;
        } elseif (waRequest::post('remove_favicon')) {
            $this->removeIcons([
                'favicon.ico',
                'favicon-96.png',
            ]);
        }
    }

    protected function saveTouchicon()
    {
        $touchicon = waRequest::file('touchicon');
        if ($touchicon->uploaded()) {
            $ext = strtolower($touchicon->extension);
            if ($ext !== 'png' && $touchicon->type !== 'image/png') {
                $this->errors = _w('Files with extension *.png are allowed only.');
            } else {
                $path = wa()->getDataPath('data/'.siteHelper::getDomain().'/', true);
                if (!file_exists($path) || !is_writable($path)) {
                    $this->errors = sprintf(_w('File could not be saved due to insufficient file write permissions for folder “%s”.'), 'wa-data/public/site/data/'.siteHelper::getDomain());
                    return;
                } else {
                    $resized_image = $touchicon->waImage()->resize(180, 180);
                    if (!$resized_image->save($path.'apple-touch-icon.png')) {
                        $this->errors = _w('Failed to upload file.');
                    }
                }
            }
            // create webmanifest for Android
            $this->updateWebmanifest($touchicon->waImage());

        } elseif ($touchicon->error_code != UPLOAD_ERR_NO_FILE) {
            $this->errors = $touchicon->error;
        } elseif (waRequest::post('remove_touchicon')) {
            $this->removeIcons([
                'apple-touch-icon.png',
                'favicon-192.png',
                'site.webmanifest'
            ]);
        } else {
            $this->updateWebmanifest();
        }
    }

    protected function updateWebmanifest(?waImage $favicon = null)
    {
        $domain = siteHelper::getDomain();
        $path = wa()->getDataPath('data/'.$domain.'/', true);
        if (!file_exists($path) || !is_writable($path)) {
            $this->errors = sprintf(_w('File could not be saved due to insufficient file write permissions for folder “%s”.'), 'wa-data/public/site/data/'.$domain);
            return;
        }

        $manifest_file = $path.'site.webmanifest';
        $manifest_icons = [
            'favicon-192.png' => 192,
        ];
        if (file_exists($manifest_file)) {
            $file_data = @file_get_contents($manifest_file);
            if (!$file_data && !is_string($file_data)) {
                throw new waException('Webmanifest is not correct.');
            }
            $data = waUtils::jsonDecode($file_data, true);

            // remove old icons
            if ($favicon) {
                $this->removeIcons(array_keys($manifest_icons));
                $data['icons'] = [];
            }
        } else {
            $data = ['icons' => []];
        }

        $data = array_merge($data, [
            'theme_color' => '#ffffff',
            'background_color' => '#ffffff',
            'display' => 'standalone'
        ]);

        if ($touchicon_title = waRequest::post('touchicon_title', '', waRequest::TYPE_STRING_TRIM)) {
            $data['name'] = $touchicon_title;
            $data['short_name'] = $touchicon_title;
        }

        if ($favicon) {
            $data_url = wa()->getDataUrl('data/'.$domain.'/', true);
            foreach ($manifest_icons as $icon => $size) {
                $favicon_copy = clone $favicon;
                if ($favicon_copy->resize($size, $size)->save($path.$icon)) {
                    $icon_data = [
                        'src' => $data_url.$icon.'?v='.filemtime($path.$icon),
                        'sizes' => $size.'x'.$size,
                        'type' => 'image/png'
                    ];
                    $data['icons'][] = $icon_data;
                }
            }
        }

        if (!empty($data['icons'])) {
            waFiles::write($manifest_file, waUtils::jsonEncode($data));
        }
    }

    protected function saveRobots()
    {
        $path = wa()->getDataPath('data/'.siteHelper::getDomain().'/', true);
        if ($robots = waRequest::post('robots')) {
            if (!file_exists($path) || !is_writable($path)) {
                $this->errors = sprintf(_w('File could not be saved due to insufficient file write permissions for folder “%s”.'), 'wa-data/public/site/data/'.siteHelper::getDomain());
            } else {
                file_put_contents($path.'robots.txt', $robots);
            }
        } elseif (file_exists($path.'robots.txt')) {
            waFiles::delete($path.'robots.txt');
        }
    }

    private function removeIcons($icons)
    {
        $path = wa()->getDataPath(null, true).'/data/'.siteHelper::getDomain().'/';
        foreach($icons as $icon) {
            waFiles::delete($path.$icon, true);
        }
    }
}
