<?php

class sitePageGetListMethod extends waAPIMethod
{
    public function execute()
    {
        $domain_id = $this->get('domain_id', true);
        $route = $this->get('route');
        if ($route) {
            $route = urldecode($route);
        }

        $content = $this->get('content');

        $page_model = new sitePageModel();
        $pages = $page_model->getByDomain($domain_id, $route, $content);

        if ($this->get('params') && $pages) {
            $params_model = new sitePageParamsModel();
            $rows = $params_model->getByField('page_id', array_keys($pages), true);
            foreach ($rows as $row) {
                $pages[$row['page_id']]['params'][$row['name']] = $row['value'];
            }
        }

        $tree = $this->get('tree');
        if ($tree == null || $tree) {
            foreach ($pages as $page_id => $page) {
                if ($page['parent_id'] && isset($pages[$page['parent_id']])) {
                    $pages[$page['parent_id']]['childs'][] = &$pages[$page_id];
                }
            }
            foreach ($pages as $page_id => $page) {
                if ($page['parent_id']) {
                    unset($pages[$page_id]);
                }
            }
        }

        $this->response['pages'] = array_values($pages);
    }
}