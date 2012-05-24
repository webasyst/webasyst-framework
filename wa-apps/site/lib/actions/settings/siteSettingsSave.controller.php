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
        $url = waRequest::post('url');
        if ($url != $domain) {
            $domain_model = new siteDomainModel();
            $domain_model->updateById(siteHelper::getDomainId(), array('name' => $url));
            $routes[$url] = $routes[$domain];
            unset($routes[$domain]);
        }
        
        
        // save wa_apps
        $domain_config_path = $this->getConfig()->getConfigPath('domains/'.$domain.'.php');
        if (file_exists($domain_config_path)) {
            $domain_config = include($domain_config_path);
        } else {
            $domain_config = array();
        }        
        
        $title = waRequest::post('title');
        if ($title != siteHelper::getDomain('title')) {
            $domain_model = new siteDomainModel();
            $domain_model->updateById(siteHelper::getDomainId(), array('title' => $title));
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
        
        if ($save_config && !waUtils::varExportToFile($domain_config, $domain_config_path)) {
            $this->errors = sprintf(_w('Navigation menu could not be saved due to the insufficient file write permissions for the "%s" folder.'), 'wa-config/apps/site/domains');
        }
        
        
        $this->saveFavicon();
        $this->saveRobots();
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