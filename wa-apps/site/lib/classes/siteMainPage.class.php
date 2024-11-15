<?php

class siteMainPage
{
    public static $old_main_page_url = 'old-main-page';

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
        $this->domain = siteHelper::getDomains()[$domain_id];

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
        $domain_routes = $this->routes[$this->domain];
        $old_root_page = $this->getFirstRootPage($this->domain_id, $domain_routes);
        if ($old_root_page) {
            list($old_type, $old_id) = $old_root_page;
            $old_url = $this->generateRouteId($domain_routes);
            switch ($old_type) {
                case 'blockpage':
                    // TODO: need update children
                    $blockpage_model = new siteBlockpageModel();
                    $blockpage_model->updateByDomainId($this->domain_id, $old_id, [
                        'url' => $old_url,
                        'full_url' => $old_url,
                        'sort' => 1
                    ]);
                    break;
                case 'route_app':
                    $old_url .= '/*';
                    $this->routes[$this->domain][$old_id]['url'] = $old_url;
                    $this->updatePagesRoute($this->routes[$this->domain][$old_id]['app'], '*', $old_url);
                    $this->has_changed_routes = true;
            }
            return [
                'type' => $old_type,
                'id' => $old_id,
                'url' => $old_url
            ];
        }

        return null;
    }

    /**
     * Set new main page
     *
     * @param string $app_id
     * @param string $type blockpage | route_app | route_text
     * @param string|integer $id $route_id or $page_id
     * @return void
     */
    public function setNewMainPage($app_id, $type, $id)
    {
        switch ($type) {
            case 'blockpage':
                $blockpage_model = new siteBlockpageModel();
                $blockpage_model->updateByDomainId($this->domain_id, $id, [
                    'url' => '',
                    'full_url' => '',
                    'sort' => 0
                ]);
                break;
            case 'route_app':
            case 'route_text':
                if ($type === 'route_app') {
                    $this->updatePagesRoute($app_id, $this->routes[$this->domain][$id]['url'], '*');
                }

                $main_route = $this->routes[$this->domain][$id];
                $main_route['url'] = '*';
                unset($this->routes[$this->domain][$id]);
                // insert to end
                $this->routes[$this->domain][$id] = $main_route;
                $this->has_changed_routes = true;
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
            // TODO: только с темой поселение является главной страницей?
            // update: 08.10.2024 видимо роут мб без темы
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

    private function generateRouteId(array $routes): string
    {
        $mask = self::$old_main_page_url;
        $max_index = -1;
        foreach ($routes as $route) {
            if (isset($route['app'])) {
                $m = [];
                $url = rtrim($route['url'], '/*');
                if (preg_match('/^'.$mask.'(-\d*)?$/', $url, $m)) {
                    $i = intval(ltrim($m[1] ?? 0, '-'));
                    $max_index = $i > $max_index ? $i : $max_index;
                }
            }
        }

        $route = $mask;
        if ($max_index > -1) {
            $route = $mask . '-' . ++$max_index;
        }

        return $route;
    }
}
