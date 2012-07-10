<?php

class waPageEditAction extends waViewAction
{
    protected $id;
    protected $model;
    protected $sidebar = true;
    protected $url = '?module=pages&id=';
    protected $add_url = '?module=pages&id=';
    protected $ibutton = true;
    public function execute()
    {

        $this->id = waRequest::get('id');
        $page_model = $this->getPageModel();

        $pages = $this->getPages();

        if ($this->id === null && $pages) {
            $page_ids = array_keys($pages);
            $this->id = $page_ids[0];
        }

        if (!$this->id || !($page = $page_model->getById($this->id))) {
            $this->id = null;
            $page = array();
        }

        $this->getPageParams();

        $this->view->assign('page', $page);
        $this->view->assign('preview_hash', $this->getPreviewHash());

        $this->view->assign('lang', substr(wa()->getLocale(), 0, 2));

        $this->view->assign('sidebar', $this->sidebar);
        $this->view->assign('page_url', $this->url);
        $this->view->assign('page_add_url', $this->add_url);
        $this->view->assign('ibutton', $this->ibutton);

        if ($this->sidebar) {
            $this->view->assign('pages', $pages);
        }

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
                    'exclude' => isset($r['_exclude']) ? in_array($this->id, $r['_exclude']) : false
                );
                if (!$routes[$domain][$r_id]['exclude'] && !$page_route) {
                    $page_route = true;
                    $this->view->assign('url', waRouting::getUrlByRoute($r, $domain));
                }
            }
        }

        $this->view->assign('routes', $routes);
        $this->view->assign('upload_url', wa()->getDataUrl('img', true));

        $this->template = $this->getConfig()->getRootPath().'/wa-system/page/templates/PageEdit.html';
    }

    protected function getPages()
    {
        return $this->getPageModel()->select('id,name,url,status')->order('sort')->fetchAll('id');
    }


    protected function getPageParams()
    {
        $params = $other_params = array();
        $vars = array(
            'keywords' => _ws('META Keywords'),
            'description' => _ws('META Description')
        );

        if ($this->id) {
            $page_model = $this->getPageModel();
            $params = $this->getPageModel()->getParams($this->id);
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
        $this->view->assign('vars', $vars);
        $this->view->assign('params', $main_params);
        $this->view->assign('other_params', $params);
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