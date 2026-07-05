<?php

class siteHtmlPagesActions extends waPageActions
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

    protected function beforeSave(&$data, $parent = array())
    {
        if (!empty($parent['domain_id'])) {
            $data['domain_id'] = $parent['domain_id'];
        }
    }

    public function saveContentAction()
    {
        $id = (int)waRequest::get('id');
        if (empty($id)) {
            throw new waException('Page id not found');
        }
        $data = waRequest::post('info', array());
        if (!isset($data['content'])) {
            throw new waException('Content field not found');
        }

        $page_model = $this->getPageModel();
        $old = $page_model->getById($id);
        if (empty($old)) {
            throw new waException('Page not found', 404);
        }

        // save to database
        if (!$page_model->update($id, ['content' => $data['content']])) {
            $this->displayJson(array(), _ws('Error saving web page'));
            return;
        }
        $this->logAction('page_edit', $id);

        /**
         * New page created or existing page modified.
         *
         * @event page_save
         * @param array $params
         * @param array[array] $params['page'] page data after save
         * @param array[array] $params['old'] page data before save (null if page is new)
         * @return void
         */
        $event_params = array(
            'page' => $page_model->getById($id),
            'old' => $old,
        );
        wa()->event('page_save', $event_params);

        // prepare response
        $this->displayJson(array(
            'id' => $id
        ));
    }

    protected function getPage($id)
    {
        $p = $this->getPageModel()->getById($id);
        if ($p) {
            if (!empty($p['domain_id'])) {
                $domain_model = new siteDomainModel();
                $domain = $domain_model->getById($p['domain_id']);
                $p['domain'] = $domain['name'];
            }
            if (empty($p['domain'])) {
                $p['domain'] = null;
            }
        }

        return $p;
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
        $routes = wa()->getRouting()->getByApp(waRequest::request('app_id', $this->getAppId()));
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

    protected function getPageModel()
    {
        return siteHelper::getPageModel(waRequest::request('app_id', $this->getAppId()));
    }

    public function pageDeleteAction()
    {
        $domain = siteHelper::getDomain();
        $id = waRequest::request('id');
        if (!waRequest::request('confirm_multiple_delete') && ($page_model = siteHelper::getPageModel())) {
            if (waRequest::request('app_id') === 'site' && ($domain_id = waRequest::request('domain_id'))) {
                $domain_field = ['domain_id' => $domain_id];
            } elseif ($domain) {
                $domain_field = ['domain' => $domain];
            }

            if ($page_model->countByField($domain_field + ['parent_id' => $id]) > 0) {
                $this->displayJson(['multiple_delete' => true]);
                return;
            }
        }

        $this->deleteAction();
    }
}
