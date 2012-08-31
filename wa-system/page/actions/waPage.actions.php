<?php

class waPageActions extends waActions
{
    protected $model;
    protected $sidebar = true;
    protected $url = '?module=pages&id=';
    protected $add_url = '?module=pages&id=';
    protected $ibutton = true;

    protected $options = array(
        'codemirror' => true,
        'container' => true,
        'show_url' => false
    );

    public function defaultAction()
    {
        $id = waRequest::get('id');
        $page_model = $this->getPageModel();

        $pages = $this->getPages();

        if ($id === null && $pages) {
            $page_ids = array_keys($pages);
            $id = $page_ids[0];
        }

        if (!$id || !($page = $page_model->getById($id))) {
            $id = null;
            $page = array();
        }

        $data = $this->getPageParams($id);

        $data['page'] = $page;
        $data['preview_hash'] = $this->getPreviewHash();

        $data['lang'] = substr(wa()->getLocale(), 0, 2);

        $data['sidebar'] = $this->sidebar;
        $data['page_url'] = $this->url;
        $data['page_add_url'] = $this->add_url;
        $data['ibutton'] = $this->ibutton;

        if ($this->sidebar) {
            $data['pages'] = $pages;
        }

        $routes = $this->getPageRoutes($id, $data);
        $data['routes'] = $routes;

        $data['upload_url'] = wa()->getDataUrl('img', true);
        $data['options'] = $this->options;

        $template = $this->getConfig()->getRootPath().'/wa-system/page/templates/PageEdit.html';
        $this->display($data, $template);
    }


    protected function getPageRoutes($id, &$data)
    {
        $routes = wa()->getRouting()->getByApp($this->getAppId());

        $page_route = false;

        foreach ($routes as $domain => $domain_routes) {
            foreach ($domain_routes as $r_id => $r) {
                if (strpos($r['url'], '<url') !== false) {
                    unset($routes[$domain][$r_id]);
                    continue;
                }
                $routes[$domain][$r_id] = array(
                    'url' => waRouting::getUrlByRoute($r, $domain),
                    'exclude' => isset($r['_exclude']) ? in_array($id, $r['_exclude']) : false
                );
                if (!$routes[$domain][$r_id]['exclude'] && !$page_route) {
                    $page_route = true;
                    $data['url'] = waRouting::getUrlByRoute($r, $domain);
                }
            }
        }

        return $routes;
    }

    protected function getPages()
    {
        return $this->getPageModel()->select('id,name,url,status')->order('sort')->fetchAll('id');
    }


    /**
     * @param int $id - page id
     * @return array
     */
    protected function getPageParams($id)
    {
        $params = $other_params = array();
        $vars = array(
            'keywords' => _ws('META Keywords'),
            'description' => _ws('META Description')
        );

        if ($id) {
            $params = $this->getPageModel()->getParams($id);
        }

        $main_params = array();
        foreach ($vars as $v => $t) {
            if (isset($params[$v])) {
                $main_params[$v] = $params[$v];
                unset($params[$v]);
            } else {
                $main_params[$v] = '';
            }
        }
        return array(
            'vars' => $vars,
            'params' => $main_params,
            'other_params' => $params,
        );
    }

    protected function getPreviewHash()
    {
        $hash = $this->appSettings('preview_hash');
        if ($hash) {
            $hash_parts = explode('.', $hash);
            if (time() - $hash_parts[1] > 14400) {
                $hash = '';
            }
        }
        if (!$hash) {
            $hash = uniqid().'.'.time();
            $app_settings_model = new waAppSettingsModel();
            $app_settings_model->set($this->getAppId(), 'preview_hash', $hash);
        }

        return md5($hash);
    }

    public function saveAction()
    {
        $id = (int)waRequest::get('id');
        $data = waRequest::post('info');
        if (!isset($data['url'])) {
            $data['url'] = '';
        }
        $data['url'] = ltrim($data['url'], '/');
        $data['status'] = isset($data['status']) ? 1 : 0;

        if ($id) {
            $is_new = false;
            // save to database
            if (!$this->getPageModel()->update($id, $data)) {
                $this->displayJson(array(), _ws('Error saving web page'));
                return;
            }
        } else {
            if ($data['url'] && substr($data['url'], -1) != '/' && strpos(substr($data['url'], -5), '.') === false) {
                $data['url'] .= '/';
            }
            $is_new = true;
            if ($id = $this->getPageModel()->add($data)) {
                $data['id'] = $id;
            } else {
                $this->displayJson(array(), _ws('Error saving web page'));
                return;
            }
        }

        // save params
        $this->saveParams($id);

        $this->saveExclude($id);


        $url = null;
        $routes = wa()->getRouting()->getByApp($this->getAppId());
        foreach ($routes as $domain => $domain_routes) {
            foreach ($domain_routes as $r) {
                if (!isset($r['_exclude']) || !in_array($id, $r['_exclude'])) {
                    $url = waRouting::getUrlByRoute($r, $domain).$data['url'];
                    break;
                }
            }
        }

        // prepare response
        $this->displayJson(array(
            'id' => $id,
            'name' => htmlspecialchars($data['name']),
            'add' => $is_new ? 1 : 0,
            'url' => $url,
            'status' => $data['status']
        ));
    }

    /**
     * @param int $id - page id
     */
    protected function saveExclude($id)
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
                        if (!in_array($id, $r['_exclude'])) {
                            $r['_exclude'][] = $id;
                            $save = true;
                            $all_routes[$domain][$r_id] = $r;
                        }
                    } elseif (isset($r['_exclude']) && $r['_exclude'] && in_array($id, $r['_exclude'])) {
                        unset($r['_exclude'][array_search($id, $r['_exclude'])]);
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

    /**
     * @param int $id - page id
     */
    protected function saveParams($id)
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

        $this->getPageModel()->setParams($id, $params);
    }


    public function deleteAction()
    {
        $id = waRequest::post('id');
        $page_model = $this->getPageModel();
        $page = $page_model->getById($id);
        if ($page) {
            $page_model->delete($id);
        }
        $this->displayJson(array());
    }


    public function sortAction()
    {
        $page_model = $this->getPageModel();
        $page_model->move(waRequest::post('id', 0, 'int'), waRequest::post('pos', 1, 'int'));
    }


    public function uploadimageAction()
    {
        $path = wa()->getDataPath('img', true);

        $response = array();

        if (!is_writable($path)) {
            $p = substr($path, strlen(wa()->getDataPath('', true)));
            $errors = sprintf(_w("File could not bet saved due to the insufficient file write permissions for the %s folder."), $p);
        } else {
            $errors = array();
            $f = waRequest::file('file');
            $name = $f->name;
            if ($this->processFile($f, $path, $name, $errors)) {
                $response = wa()->getDataUrl('img/'.$name, true);
            }
            $errors = implode(" \r\n", $errors);
        }

        $this->displayJson($response, $errors);
    }

    /**
     * @param waRequestFile $f
     * @param string $path
     * @param string $name
     * @param array $errors
     * @return bool
     */
    protected function processFile(waRequestFile $f, $path, &$name, &$errors = array())
    {
        if ($f->uploaded()) {
            if (!$this->isFileValid($f)) {
                return false;
            }
            if (!$this->saveFile($f, $path, $name)) {
                $errors[] = sprintf(_w('Failed to upload file %s.'), $f->name);
                return false;
            }
            return true;
        } else {
            $errors[] = sprintf(_w('Failed to upload file %s.'), $f->name).' ('.$f->error.')';
            return false;
        }
    }

    protected function isFileValid($f)
    {
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        if (!in_array(strtolower($f->extension), $allowed)) {
            $this->errors[] = sprintf(_ws("Files with extensions %s are allowed only."), '*.'.implode(', *.', $allowed));
            return false;
        }
        return true;
    }

    protected function saveFile(waRequestFile $f, $path, &$name)
    {
        if (file_exists($path.DIRECTORY_SEPARATOR.$f->name)) {
            $i = strrpos($f->name, '.');
            $name = substr($f->name, 0, $i);
            $ext = substr($f->name, $i + 1);
            $i = 1;
            while (file_exists($path.DIRECTORY_SEPARATOR.$name.'-'.$i.'.'.$ext)) {
                $i++;
            }
            $name = $name.'-'.$i.'.'.$ext;
            return $f->moveTo($path, $name);
        }
        return $f->moveTo($path, $f->name);
    }

    /**
     * @return waPageModel
     */
    protected function getPageModel()
    {
        if (!$this->model) {
            $this->model = $this->getAppId().'PageModel';
        }
        return new $this->model();
    }


}