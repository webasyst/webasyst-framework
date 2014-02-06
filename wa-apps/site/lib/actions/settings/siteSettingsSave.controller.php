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
        $url = mb_strtolower(rtrim(waRequest::post('url'), '/'));
        if ($url != $domain) {
            $domain_model = new siteDomainModel();
            // domain already exists
            if ($domain_model->getByName($url)) {
                $this->errors = sprintf(_w("Website with a domain name %s is already registered in this Webasyst installation. Delete %s website (Site app > %s > Settings) to be able to use it's domain name for another website."), $url, $url, $url);
                return;
            }
            $domain_model->updateById(siteHelper::getDomainId(), array('name' => $url));
            $routes[$url] = $routes[$domain];
            unset($routes[$domain]);

            // move configs
            $old = $this->getConfig()->getConfigPath('domains/'.$domain.'.php');
            if (file_exists($old)) {
                waFiles::move($old, $this->getConfig()->getConfigPath('domains/'.$url.'.php'));
            }
            $old = wa()->getDataPath('data/'.$domain.'/', true, 'site', false);
            if (file_exists($old)) {
                waFiles::move($old, wa()->getDataPath('data/'.$url.'/', true));
                waFiles::delete($old);
            }
            $domain = $url;
            siteHelper::setDomain(siteHelper::getDomainId(), $domain);
        }
        
        
        // save wa_apps
        $domain_config_path = $this->getConfig()->getConfigPath('domains/'.$domain.'.php');
        if (file_exists($domain_config_path)) {
            $domain_config = include($domain_config_path);
        } else {
            $domain_config = array();
        }

        $title = waRequest::post('title');
        $style = waRequest::post('background');
        if (!$style || substr($style, 0, 1) == '.') {
            if ($s = $this->saveBackground()) {
                $style = '.'.$s;
            }
        }
        $domain_model = new siteDomainModel();
        $domain_model->updateById(siteHelper::getDomainId(), array(
            'title' => $title,
            'style' => $style
        ));

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
        
        waUtils::varExportToFile($routes, $path);
        
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

        // save other settings
        foreach (array('head_js', 'google_analytics') as $key) {
            if (!empty($domain_config[$key]) || waRequest::post($key)) {
                $domain_config[$key] = waRequest::post($key);
                $save_config = true;
            }
        }
        
        if ($save_config && !waUtils::varExportToFile($domain_config, $domain_config_path)) {
            $this->errors = sprintf(_w('Settings could not be saved due to the insufficient file write permissions for the "%s" folder.'), 'wa-config/apps/site/domains');
        }
        
        
        $this->saveFavicon();
        $this->saveRobots();

        $this->saveAuthSettings();
        $this->log('site_edit');
    }

    protected function saveAuthSettings()
    {
        $auth_enabled = waRequest::post('auth_enabled');
        $auth_app = waRequest::post('auth_app');
        $domain = siteHelper::getDomain();
        $config = wa()->getConfig()->getAuth();
        if (!isset($config[$domain])) {
            if (!$auth_enabled) {
                return;
            }
            $config[$domain] = array();
        }
        if ($auth_enabled && $auth_app) {
            $config[$domain]['auth'] = true;
            $config[$domain]['app'] = $auth_app;
            if (waRequest::post('auth_captcha')) {
                $config[$domain]['signup_captcha'] = true;
            } elseif (isset($config[$domain]['signup_captcha'])) {
                unset($config[$domain]['signup_captcha']);
            }
        } else {
            if (isset($config[$domain]['auth'])) {
                unset($config[$domain]['auth']);
            }
            if (isset($config[$domain]['app'])) {
                unset($config[$domain]['app']);
            }
            if (isset($config[$domain]['signup_captcha'])) {
                unset($config[$domain]['signup_captcha']);
            }
        }
        // save auth adapters
        if (waRequest::post('auth_adapters') && waRequest::post('adapter_ids')) {
            $config[$domain]['adapters'] = array();
            $adapters = waRequest::post('adapters', array());
            foreach (waRequest::post('adapter_ids') as $adapter_id) {
                $config[$domain]['adapters'][$adapter_id] = $adapters[$adapter_id];
            }
        } else {
            if (isset($config[$domain]['adapters'])) {
                unset($config[$domain]['adapters']);
            }
        }
        if (!$this->getConfig()->setAuth($config)) {
            $this->errors = sprintf(_w('File could not be saved due to the insufficient file write permissions for the "%s" folder.'), 'wa-config/');
        }
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
            if ($favicon->extension !== 'ico') {
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