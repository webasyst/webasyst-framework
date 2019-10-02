<?php

class siteSettingsSaveController extends waJsonController
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
                $this->errors = sprintf(_w("Website with a domain name %s is already registered in this Webasyst installation. Delete %s website (Site app > %s > Settings) to be able to use it's domain name for another website."), $url, $url, $url);
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
        $style = waRequest::post('background');
        if (!$style || substr($style, 0, 1) == '.') {
            if ($s = $this->saveBackground()) {
                $style = '.'.$s;
            }
        }
        $domain_model->updateById(siteHelper::getDomainId(), array(
            'title' => $title,
            'style' => $style
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

            //Delete cache problem domains
            $cache_domain = new waVarExportCache('problem_domains', 3600, 'site/settings/');
            $cache_domain->delete();
            //Remove notification
            wa()->getStorage()->del('apps-count');

            if ($save_config && !waUtils::varExportToFile($domain_config, $domain_config_path)) {
                $this->errors = sprintf(_w('Settings could not be saved due to the insufficient file write permissions for the "%s" folder.'), 'wa-config/apps/site/domains');
            } else {
                $domain_config = $orig_domain_config;
            }

            $event_params['config'] = $domain_config;
            $this->saveFavicon();
            $this->saveTouchicon();
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

    protected function saveBackground()
    {
        $background = waRequest::file('background_file');
        if ($background && $background->uploaded()) {
            $allowed = array('jpg', 'jpeg', 'png', 'gif');
            if (!in_array($background->extension, $allowed)) {
                $this->errors = sprintf(_ws("Files with extensions %s are allowed only."), '*.'.implode(', *.', $allowed));
            } else {
                $path = wa()->getDataPath('background/', true);
                $ext = $background->extension;
                if (!file_exists($path) || !is_writable($path)) {
                    $this->errors = sprintf(_w('File could not be saved due to the insufficient file write permissions for the "%s" folder.'), 'wa-data/public/site/data/'.siteHelper::getDomain());
                } elseif (!$background->moveTo($path, siteHelper::getDomainId().'.'.$ext)) {
                    $this->errors = _w('Failed to upload file.');
                } else {
                    return $ext;
                }
            }
        }
        return false;
    }

    protected function saveFavicon()
    {
        $favicon = waRequest::file('favicon');
        if ($favicon->uploaded()) {
            $ext = strtolower($favicon->extension);
            $mime_favicon = $favicon->type === 'image/x-icon' || $favicon->type === 'image/vnd.microsoft.icon';
            if ($ext !== 'ico' && !$mime_favicon) {
                $this->errors = _w('Files with extension *.ico are allowed only.');
            } else {
                $path = wa()->getDataPath('data/'.siteHelper::getDomain().'/', true);
                if (!file_exists($path) || !is_writable($path)) {
                    $this->errors = sprintf(_w('File could not be saved due to the insufficient file write permissions for the "%s" folder.'), 'wa-data/public/site/data/'.siteHelper::getDomain());
                } elseif (!$favicon->moveTo($path, 'favicon.ico')) {
                    $this->errors = _w('Failed to upload file.');
                }
            }
        } elseif ($favicon->error_code != UPLOAD_ERR_NO_FILE) {
            $this->errors = $favicon->error;
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
                    $this->errors = sprintf(_w('File could not be saved due to the insufficient file write permissions for the "%s" folder.'), 'wa-data/public/site/data/'.siteHelper::getDomain());
                } elseif (!$touchicon->moveTo($path, 'apple-touch-icon.png')) {
                    $this->errors = _w('Failed to upload file.');
                }
            }
        } elseif ($touchicon->error_code != UPLOAD_ERR_NO_FILE) {
            $this->errors = $touchicon->error;
        }
    }

    protected function saveRobots()
    {
        $path = wa()->getDataPath('data/'.siteHelper::getDomain().'/', true);
        if ($robots = waRequest::post('robots')) {
            if (!file_exists($path) || !is_writable($path)) {
                $this->errors = sprintf(_w('File could not be saved due to the insufficient file write permissions for the "%s" folder.'), 'wa-data/public/site/data/'.siteHelper::getDomain());
            } else {
                file_put_contents($path.'robots.txt', $robots);
            }
        } elseif (file_exists($path.'robots.txt')) {
            waFiles::delete($path.'robots.txt');
        }
    }
}
