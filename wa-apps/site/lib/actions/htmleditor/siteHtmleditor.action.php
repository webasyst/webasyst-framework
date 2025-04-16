<?php
/**
 * 1.0 HTML editor for block pages.
 */
class siteHtmleditorAction extends waViewAction
{

    protected $url = '';


    protected $options = array(
        'container' => false,
        'show_url' => true,
        'save_panel' => true,
        'js' => array(
            'ace' => false,
            'editor' => false,
            'storage' => false
        ),
        'is_ajax' => true,
        'data' => array()
    );


    public function execute()
    {
        $page_id = waRequest::param('page_id', null, 'int');

        if ($page_id) {
            $page_model = new sitePageModel();
            $page = $page_model->getById($page_id);
        }
        else {
            $page_id = null;
            $page = array();
        }
        if (empty($page)) {
            throw new waException('Not found', 404);
        }

        $this->setLayout(new siteBackendLayout([
            'custom_header_type' => 'html_editor',
            'hide_wa_app_icons' => true,
        ]));

        $this->editData($page, $page_id);

        $this->view->assign([
            //'page' => $page,
            //'lang' => substr(wa()->getLocale(), 0, 2),
            //'preview_hash' => waRequest::get('preview', null, 'string'),
            //'options' => $this->options,
        ]);
    }

    protected function editData($page, $page_id)
    {
        $url = '';

        if ($page) {
            $domain = siteHelper::getDomain();
            $route = $page['route'];
            if ($page['parent_id']) {
                $page_model = new sitePageModel();
                $parent = $page_model->getById($page['parent_id']);
                $url = $parent['full_url'] ? rtrim($parent['full_url'], '/').'/' : '';
            }
        } else {
            if ($parent_id = waRequest::param('parent_id')) {
                $parent = (new sitePageModel())->getById($parent_id);
                $domain = $parent['domain'];
                $route = $parent['route'];
                $this->options['data']['info[parent_id]'] = $parent_id;
                $url = $parent['full_url'] ? rtrim($parent['full_url'], '/').'/' : '';
            } else {
                $domain = waRequest::get('domain');
                $this->options['data']['info[domain]'] = $domain;
                $route = waRequest::get('route');
                $this->options['data']['info[route]'] = $route;
            }
        }

        $routes = wa()->getRouting()->getRoutes(siteHelper::getDomain());
        if ($domain) {
            $path = '';
            if ($route !== '*') {
                $path = rtrim($route, '/*').'/';
            }
            $url = 'http://'.$domain.'/'.$path;
            $page['domain'] = $domain;
        } else {
            $url = null;
        }

        $warnings = array();
        if (!isset($routes[$domain.'/'.$route])) {
            if (empty($page)) {
                $warnings['no_site_storefront'] = true;
            } elseif (empty($routes)) {
                $warnings['deleted_site_storefront'] = true;
            } else {
                $warnings['several_site_storefront'] = true;
            }
        }

        if ($url) {
            $idna = new waIdna();
            $url_decoded = $idna->decode($url);
        } else {
            $url_decoded = null;
        }

        $route_id = null;
        if ($page) {
            foreach ($routes as $_route_id => $_route) {
                if (ifset($_route['app']) === 'site' && $_route['url'] === $page['route']) {
                    $route_id = $_route_id;
                    break;
                }
            }
        }

        $data = array(
            'url'          => $url,
            'url_decoded'  => $url_decoded,
            'warnings'     => $warnings,
            'page'         => $page,
            'page_url'     => $this->url,
            'options'      => $this->options,
            'preview_hash' => siteHelper::getPreviewHash(),
            'lang'         => substr(wa()->getLocale(), 0, 2),
            'upload_url'   => wa()->getDataUrl('img', true),
            'route_id'     => strval($route_id),
        ) + $this->getPageParams($page_id);

        $data['page_edit'] = wa()->event('page_edit', $data);

        /**
         * Backend settings page
         * UI hook allow extends backend settings page
         * @event backend_page_edit
         * @param array $page
         * @return array[string][string]string $return[%plugin_id%]['action_button_li'] html output
         * @return array[string][string]string $return[%plugin_id%]['settings_section'] html output
         * @return array[string][string]string $return[%plugin_id%]['section'] html output
         */
        $data['backend_page_edit'] = wa()->event('backend_page_edit', $page, array(
            'action_button_li',
            'section',
            'settings_section'
        ));

        $this->view->assign($data);

    }

    protected function getRoutes()
    {
        $routes = wa()->getRouting()->getByApp($this->getAppId());
        $result = array();
        foreach ($routes as $d => $domain_routes) {
            foreach ($domain_routes as $route) {
                $result[$d.'/'.$route['url']] = array(
                    'route' => $route['url'],
                    'domain' => $d
                );
            }
        }
        return $result;
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
            $page_params_model = new sitePageParamsModel();
            $params = $page_params_model->getById($id);
        }

        $og_params = array();
        foreach ($params as $k => $v) {
            if (substr($k, 0, 3) == 'og_') {
                $og_params[substr($k, 3)] = $v;
                unset($params[$k]);
            }
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
            'og_params' => $og_params
        );
    }

}
