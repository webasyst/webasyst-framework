<?php

class waPageSaveController extends waPageJsonController
{
    protected $id;

    public function execute()
    {
        $this->id = (int)waRequest::get('id');
        $data = waRequest::post('info');
        if (!isset($data['url'])) {
            $data['url'] = '';
        }
        $data['url'] = ltrim($data['url'], '/');
        $data['status'] = isset($data['status']) ? 1 : 0;

        if ($this->id) {
            $is_new = false;
            // save to database
            if (!$this->getPageModel()->update($this->id, $data)) {
                $this->errors = _ws('Error saving web page');
                return;
            }
        } else {
            if ($data['url'] && substr($data['url'], -1) != '/' && strpos(substr($data['url'], -5), '.') === false) {
                $data['url'] .= '/';
            }
            $is_new = true;
            if ($this->id = $this->getPageModel()->add($data)) {
                $data['id'] = $this->id;
            } else {
                $this->errors = _w('Error saving web page');
                return;
            }
        }

        // save params
        $this->saveParams();

        $this->saveExclude();


        $url = null;
        $routes = wa()->getRouting()->getByApp($this->getAppId());
        foreach ($routes as $domain => $domain_routes) {
            foreach ($domain_routes as $r) {
                if (!isset($r['_exclude']) || !in_array($this->id, $r['_exclude'])) {
                    $url = waRouting::getUrlByRoute($r, $domain).$data['url'];
                    break;
                }
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
        $path = $this->getConfig()->getPath('config', 'routing');
        $all_routes = file_exists($path) ? include($path) : array();
        $save = false;
        $data = waRequest::post('exclude', array());
        $exclude = array();
        foreach ($data as $row) {
            $row = explode(':', $row);
            if (!isset($exclude[$row[0]])) {
                $exclude[$row[0]] = array();
            }
            $exclude[$row[0]][] = $row[1];
        }
        foreach ($all_routes as $domain => $domain_routes) {
            foreach ($domain_routes as $r_id => $r) {
                if (isset($r['app'])) {
                    if (isset($exclude[$domain]) && in_array($r_id, $exclude[$domain])) {
                        if (!isset($r['_exclude'])) {
                            $r['_exclude'] = array();
                        }
                        if (!in_array($this->id, $r['_exclude'])) {
                            $r['_exclude'][] = $this->id;
                            $save = true;
                            $all_routes[$domain][$r_id] = $r;
                        }
                    } elseif (isset($r['_exclude']) && $r['_exclude'] && in_array($this->id, $r['_exclude'])) {
                        unset($r['_exclude'][array_search($this->id, $r['_exclude'])]);
                        if (!$r['_exclude']) {
                            unset($r['_exclude']);
                        }
                        $save = true;
                        $all_routes[$domain][$r_id] = $r;
                    }
                }
            }
        }

        // save to config
        if ($save) {
            wa()->getRouting()->setRoutes($all_routes);
            waUtils::varExportToFile($all_routes, $path);
        }
    }

    protected function saveParams()
    {
        $params = waRequest::post('params');
        $other_params = waRequest::post('other_params');
        if ($other_params) {
            $other_params = explode("\n", $other_params);
            foreach ($other_params as $param) {
                $param = explode("=", trim($param), 2);
                if (count($param) == 2) {
                    $params[$param[0]] = $param[1];
                }
            }
        }

        $this->getPageModel()->setParams($this->id, $params);
    }

}