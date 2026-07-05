<?php

class siteMainPage
{
    private $domain_id = null;
    private $domain = null;
    private $path = null;
    private $routes = [];
    private $has_changed_routes = false;

    /**
     * @param $domain_id
     * @param array $routes
     */
    public function __construct($domain_id, &$routes = null)
    {
        $this->domain_id = $domain_id;
        $this->domain = ifset(ref(siteHelper::getDomains(true)), $domain_id, 'name', null);

        if (is_array($routes)) {
            $this->routes = &$routes;
        } else {
            $this->path = wa()->getConfig()->getPath('config', 'routing');
            if (file_exists($this->path)) {
                $this->routes = include($this->path);
                if (!is_writable($this->path)) {
                    throw new JsonException(sprintf(_ws('Settings could not be saved due to insufficient write permissions for file %s.'), 'wa-config/routing.php'));
                }
            }
        }
    }

    /**
     * Update old main page
     * @return void
     */
    public function silenceMainPage()
    {
        $domain_routes = ifset($this->routes, $this->domain, []);
        $old_root_page = $this->getFirstRootPage($this->domain_id, $domain_routes);
        if ($old_root_page) {
            list($old_type, $old_id) = $old_root_page;

            switch ($old_type) {
                case 'blockpage':

                    $blockpage_model = new siteBlockpageModel();
                    $page = $blockpage_model->getById($old_id);

                    $blockpage_params_model = new siteBlockpageParamsModel();
                    $params = $blockpage_params_model->getById($old_id);
                    if (isset($params['url_before_main_page'])) {
                        $new_url = $params['url_before_main_page'];
                        $blockpage_params_model->deleteOne($old_id, 'url_before_main_page');
                    } else {
                        $new_url = $this->generateRouteId();
                    }

                    $blockpage_model->updateByDomainId($this->domain_id, $old_id, [
                        'url' => $new_url,
                        'full_url' => $new_url,
                        'sort' => 1,
                    ]);

                    // update tree starting from the page
                    $child_ids = $blockpage_model->getChildIds($old_id);
                    if ($child_ids) {
                        $blockpage_model->updateFullUrl($child_ids, $new_url, $page['url']);
                    }

                    // update draft
                    $blockpage_model->updateByField([
                        'final_page_id' => $old_id,
                    ], [
                        'url' => $new_url,
                        'full_url' => $new_url,
                        'sort' => 1,
                    ]);

                    break;
                case 'route_app':
                    $main_route = $this->routes[$this->domain][$old_id];
                    $new_url = $this->generateRouteId(ifset($main_route, 'app', ''));
                    $new_url .= '/*';

                    $old_url = ifset($main_route, 'old_url', '');
                    if (strlen($old_url)) {
                        unset($main_route['old_url']);
                        if(!$this->isUrlDuplicate($old_url)) {
                            $new_url = $old_url;
                        }
                    }

                    $main_route['url'] = $new_url;
                    $this->routes[$this->domain][$old_id] = $main_route;
                    $this->updatePagesRoute($main_route['app'], '*', $new_url);
                    $this->has_changed_routes = true;
                    break;
            }

            return [
                'type' => $old_type,
                'id' => $old_id,
                'url' => $new_url
            ];
        }

        return null;
    }

    /**
     * Set new main page
     *
     * @param string $app_id
     * @param string $type blockpage | route_app
     * @param string|integer $id $route_id or $page_id
     * @return void
     */
    public function setNewMainPage($app_id, $type, $id)
    {
        switch ($type) {
            case 'blockpage':
                $blockpage_model = new siteBlockpageModel();
                $page = $blockpage_model->getById($id);
                if (strlen((string)$page['url'])) {
                    $blockpage_params_model = new siteBlockpageParamsModel();
                    $blockpage_params_model->setOne($id, 'url_before_main_page', $page['url']);
                }

                // Move page to root if it hs a parent
                if (!empty($page['parent_id'])) {
                    $page['parent_id'] = null;
                    $blockpage_model->move($id, [
                        'domain_id' => $page['domain_id'],
                    ]);
                }

                // update page
                $blockpage_model->updateByDomainId($this->domain_id, $id, [
                    'url' => '',
                    'full_url' => '',
                    'sort' => 0,
                ]);

                if (strlen((string)$page['url'])) {
                    // update tree starting from the page
                    $child_ids = $blockpage_model->getChildIds($id);
                    if ($child_ids) {
                        $blockpage_model->updateFullUrl($child_ids, '', $page['url']);
                    }

                    // update draft
                    $blockpage_model->updateByField([
                        'final_page_id' => $id,
                    ], [
                        'url' => '',
                        'full_url' => '',
                        'parent_id' => null,
                    ]);
                }

                break;
            case 'route_app':
                if ($type === 'route_app') {
                    $this->updatePagesRoute($app_id, $this->routes[$this->domain][$id]['url'], '*');
                }

                $main_route = $this->routes[$this->domain][$id];
                $main_route['old_url'] = $main_route['url'];
                $main_route['url'] = '*';
                unset($this->routes[$this->domain][$id]);
                // place to end
                $this->routes[$this->domain][$id] = $main_route;
                $this->has_changed_routes = true;
        }
    }

    public function isAllowedAsMainPage($app_id, $type, $id)
    {
        switch ($type) {
            case 'blockpage':
                $blockpage_model = new siteBlockpageModel();
                $page = $blockpage_model->getById($id);
                return $page['status'] == 'final_published' && !$page['parent_id'];
            case 'route_app':
                return true;
        }

    }

    /**
     * Use for finally saving routes
     *
     * @return bool
     */
    public function saveRoutes()
    {
        if ($this->has_changed_routes) {
            return waUtils::varExportToFile($this->routes, $this->path);
        }
        return false;
    }

    private function updatePagesRoute($app_id, $old_route, $new_route)
    {
        $page_model = siteHelper::getPageModel($app_id);
        if ($page_model instanceof waPageModel) {
            $page_model->updateRoute($this->domain, $old_route, $new_route);
        }
    }

    /**
     * @return array|null
     */
    private function getFirstRootPage(int $domain_id, array $routes)
    {
        $blockpage_model = new siteBlockpageModel();
        $blockpage = $blockpage_model->getByUrl($domain_id, '');
        if ($blockpage) {
            return array('blockpage', $blockpage['id']);
        }

        $root_route_id = null;
        foreach ($routes as $route_id => $route) {
            if ($route['url'] === '*' && !isset($route['redirect'])) {
                $root_route_id = $route_id;
                break;
            }
        }
        if (null !== $root_route_id) {
            return array('route_app', $root_route_id);
        }

        return null;
    }

    private function generateRouteId(string $app=''): string
    {
        if ($app) {
            $mask = siteHelper::getAlternativeAppUrl($app);
        } else {
            $mask = 'old-main-page';
        }

        $max_index = -1;
        foreach ($this->routes[$this->domain] as $route) {
            if (isset($route['app'])) {
                $m = [];
                $url = rtrim($route['url'], '/*');
                if (preg_match('/^'.$mask.'(-\d*)?$/', $url, $m)) {
                    $idx = intval(ltrim($m[1] ?? 0, '-'));
                    $max_index = $idx > $max_index ? $idx : $max_index;
                }
            }
        }

        $blockpage_model = new siteBlockpageModel();
        $other_blockpages = $blockpage_model->getByUrlStartingWith($this->domain_id, $mask);
        foreach ($other_blockpages as $p) {
            if (preg_match('/^'.$mask.'(-\d*)?$/', $p['full_url'], $m)) {
                $idx = intval(ltrim($m[1] ?? 0, '-'));
                $max_index = $idx > $max_index ? $idx : $max_index;
            }
        }

        $id = $mask;
        if ($max_index > -1) {
            $id = $mask . '-' . ++$max_index;
        }

        return $id;
    }

    private function isUrlDuplicate(string $url): bool
    {
        foreach ($this->routes[$this->domain] as $route) {
            if (ifset($route, 'url', '') === $url) {
                return true;
            }
        }

        return false;
    }
}
