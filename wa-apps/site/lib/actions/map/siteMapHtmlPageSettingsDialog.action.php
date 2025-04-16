<?php
/**
 * Blockpage settings (name, url, SEO etc.)
 * Used as a separate controller as well as a part of main Editor screen,
 * @see siteEditorAction
 */
class siteMapHtmlPageSettingsDialogAction extends waViewAction
{
    public $page_id;

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

    public function __construct($params = null)
    {
        parent::__construct($params);
        if (isset($params['page_id'])) {
            $this->page_id = $params['page_id'];
        } else {
            $this->page_id = waRequest::request('page_id', null, 'int');
        }
    }

    public function execute()
    {
        $route_id = waRequest::request('route', '');
        $routes = wa()->getRouting()->getRoutes(siteHelper::getDomain());

        $page = [];
        if ($this->page_id) {
            $page = $this->getPage($this->page_id);
        }
        $this->editData($page, $this->page_id);

        $this->view->assign([
            'route' => $routes[$route_id] ?? [],
            'route_id' => $route_id,
            'locales'  => array('' => _w('Auto')) + waLocale::getAll('name'),
        ]);
    }

    protected function editData($page, $page_id)
    {
        $url = '';
        $domain = siteHelper::getDomain();
        $app_id = waRequest::request('app_id', 'site');

        if ($page) {
            $route = $page['route'];
            if ($page['parent_id']) {
                $parent = $this->getPage($page['parent_id']);
                $url = $parent['full_url'] ? rtrim($parent['full_url'], '/').'/' : '';
            }
        } else {
            if ($parent_id = waRequest::request('parent_id', null, 'int')) {
                $parent = $this->getPage($parent_id);
                if (!empty($parent['domain'])) {
                    $domain = $parent['domain'];
                }
                $route = $parent['route'];
                $this->options['data']['info[parent_id]'] = $parent_id;
                $url = $parent['full_url'] ? rtrim($parent['full_url'], '/').'/' : '';
            } else if ( ( $route_id = waRequest::request('route_id', '', 'string')) !== '') {
                $app_routes = wa()->getRouting()->getByApp($app_id, $domain);
                if ($app_route = ifset($app_routes[$route_id])) {
                    $route = $app_route['url'];
                }
            } else {
                $domain = waRequest::get('domain');
                $this->options['data']['info[domain]'] = $domain;
                $route = waRequest::get('route');
                $this->options['data']['info[route]'] = $route;
            }
        }

        if ($route) {
            $url = wa()->getRouting()->clearUrl($route) . $url;
            $this->options['data']['info[route]'] = $route;
        }
        if ($domain) {
            $url = 'http://'.$domain.'/'.$url;
            $this->options['data']['info[domain]'] = $domain;
        } else {
            $url = null;
        }

        $warnings = array();
        $routes = $this->getRoutes();
        if (!isset($routes[$domain.'/'.$route])) {
            if (empty($page)) {
                $warnings['no_site_storefront'] = true;
            } elseif (empty($routes)) {
                $warnings['deleted_site_storefront'] = true;
            } else {
                $warnings['several_site_storefront'] = true;
            }
        }

        $url_decoded = null;
        $idna = new waIdna();
        if ($url) {
            $url_decoded = $idna->decode($url);
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
            'domain_id'    => waRequest::request('domain_id'),
            'domain_decode' => $idna->decode($domain),
            'app_id'       => $app_id,
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

    /**
     * @param int $id - page id
     * @return array
     */
    protected function getPageParams($id)
    {
        $params = array();
        $vars = array(
            'keywords' => _ws('META Keywords'),
            'description' => _ws('META Description')
        );

        if ($id) {
            $params = siteHelper::getPageModel(waRequest::request('app_id', 'site'))->getParams($id);
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

    /**
     * @return array|null
     */
    protected function getPage($id)
    {
        $model = siteHelper::getPageModel(waRequest::request('app_id', 'site'));
        return $model->getById($id);
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
}
