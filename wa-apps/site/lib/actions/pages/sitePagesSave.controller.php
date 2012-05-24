<?php 

class sitePagesSaveController extends waPageSaveController
{
    
    protected $id;
    protected $domain;
    
    public function execute()
    {
        $this->id = (int)waRequest::get('id');
        $data = waRequest::post('info');

        if ($data['domain_id']) {
            $domain_id = $data['domain_id'];
            $domain_model = new siteDomainModel();
            $domain = $domain_model->getById($domain_id);
            $this->domain = $domain['name'];
        }

        $model = new sitePageModel();

        if (!isset($data['url'])) {
            $data['url'] = '';
        }
        $data['url'] = ltrim($data['url'], '/');

        $data['status'] = isset($data['status']) ? 1 : 0;

        if ($this->id) {
            $is_new = false;
            // remove cache
            $this->clearCache($data);
            // save to database
            if (!$model->update($this->id, $data)) {
                $this->errors = _w('Error saving web page');
                return;
            }
        } else {
            if ($data['url'] && substr($data['url'], -1) != '/' && strpos(substr($data['url'], -5), '.') === false) {
                $data['url'] .= '/';
            }
            $is_new = true;
            if ($this->id = $model->add($data)) {
                $data['id'] = $this->id;
            } else {
                $this->errors = _w('Error saving web page');
                return;
            }
        }

        // save params
        $this->saveParams($is_new);


        $this->saveExclude();

        $url = null;
        $routes = wa()->getRouting()->getRoutes($this->domain);
        foreach ($routes as $r_id => $r) {
            if (isset($r['app']) && $r['app'] == 'site' &&
                (!isset($r['_exclude']) || !in_array($this->id, $r['_exclude']))) {
                $url = waRouting::getUrlByRoute($r).$data['url'];
                break;
            }
        }



        // prepare response
        $this->response = array(
            'id' => $this->id,
            'name' => htmlspecialchars($data['name']),
            'add' => $is_new ? 1 : 0,
            'url' => $url,
            'status' => $data['status']
        );
    }


    protected function saveExclude()
    {
        $routes = wa()->getRouting()->getRoutes($this->domain);
        $save = false;
        $exclude = waRequest::post('exclude', array());
        foreach ($routes as $r_id => $r) {
            if (isset($r['app']) && $r['app'] == 'site') {
                if (in_array($r_id, $exclude)) {
                    if (!isset($r['_exclude'])) {
                        $r['_exclude'] = array();
                    }
                    if (!in_array($this->id, $r['_exclude'])) {
                        $r['_exclude'][] = $this->id;
                        $save = true;
                        $routes[$r_id] = $r;
                    }
                } elseif (isset($r['_exclude']) && $r['_exclude'] && in_array($this->id, $r['_exclude'])) {
                    unset($r['_exclude'][array_search($this->id, $r['_exclude'])]);
                    if (!$r['_exclude']) {
                        unset($r['_exclude']);
                    }
                    $save = true;
                    $routes[$r_id] = $r;
                }
            }
        }
        // save to config
        if ($save) {
            $path = $this->getConfig()->getPath('config', 'routing');
            $all_routes = file_exists($path) ? include($path) : array();
            $all_routes[$this->domain] = $routes;
            wa()->getRouting()->setRoutes($all_routes);
            waUtils::varExportToFile($all_routes, $path);
        }
    }


    protected function clearCache($data)
    {
        // delete database cache
        $cache = new waSerializeCache('pages'.$data['url'].'page');
        $cache->delete();

        // delete veiw cache for all templates
        if ($this->getConfig()->getOption('cache_time')) {
            $view = wa()->getView()->clearCache(null, $data['url']);
        }
    }
}