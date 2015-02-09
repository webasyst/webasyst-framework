<?php

class sitePagesActions extends waPageActions
{

    protected $url = '#/pages/';
    protected $add_url = '#/pages/add';

    protected $options = array(
        'container' => false,
        'show_url' => true,
        'save_panel' => false,
        'js' => array(
            'ace' => false,
            'editor' => false,
            'storage' => false
        ),
        'is_ajax' => true,
        'data' => array()
    );

    protected function prepareData(&$data)
    {
        $data['domain'] = siteHelper::getDomain();
        return $data;
    }

    public function editAction()
    {
        $domain = waRequest::get('domain');
        if ($domain) {
            $domain_model = new siteDomainModel();
            $d = $domain_model->getByName($domain);
            $this->options['data']['info[domain_id]'] = $d['id'];
        }
        if (!waRequest::get('id') && !waRequest::get('parent_id')) {
            if (!$this->getPageModel()->countByField(array(
                'domain_id' => siteHelper::getDomainId(),
                'route' => waRequest::get('route'),
            ))) {
                $this->options['disable_auto_url'] = true;
            }
        }
        parent::editAction();
    }

    protected function beforeSave(&$data, $parent = array())
    {
        if ($parent) {
            $data['domain_id'] = $parent['domain_id'];
        }
    }

    protected function getPage($id)
    {
        $p = $this->getPageModel()->getById($id);
        if ($p) {
            if ($p['domain_id']) {
                $domain_model = new siteDomainModel();
                $domain = $domain_model->getById($p['domain_id']);
                $p['domain'] = $domain['name'];
            } else {
                $p['domain'] = null;
            }
        }
        return $p;
    }

    public function moveAction()
    {
        $page_model = $this->getPageModel();
        $parent_id = waRequest::post('parent_id');
        if (!$parent_id) {
            $domain_model = new siteDomainModel();
            $domain = $domain_model->getByName(waRequest::post('domain'));
            $parent_id = array(
                'domain_id' => $domain ? $domain['id'] : 0,
                'route' => waRequest::post('route')
            );
        }
        $result = $page_model->move(waRequest::post('id', 0, 'int'), $parent_id, waRequest::post('before_id', 0, 'int'));
        $this->displayJson($result, $result ? null: _w('Database error'));
    }

    protected function getPages()
    {
        $domain_id = siteHelper::getDomainId();
        $pages = $this->getPageModel()->
            select('id,name,url,full_url,status,route,parent_id')->
            where('domain_id = '.$domain_id)->
            order('parent_id,sort')->fetchAll('id');

        $domain = siteHelper::getDomain();
        foreach ($pages as &$p) {
            $p['domain'] = $domain;
        }
        unset($p);
        return $pages;
    }

    protected function getRoutes()
    {
        $routes = wa()->getRouting()->getByApp($this->getAppId());
        $result = array();
        $d = siteHelper::getDomain();
        if (isset($routes[$d])) {
            foreach ($routes[$d] as $route) {
                $result[$d.'/'.$route['url']] = array(
                    'route' => $route['url'],
                    'domain' => $d
                );
            }
        }
        return $result;
    }
}