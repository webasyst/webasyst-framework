<?php
/**
 * Drag-and-drop for pages (block and HTML) and settlements
 * for the Map Overview page.
 *
 * POST parameters for HTML pages and block pages:
 * - id: page id to move
 * - before_id: page `id` will be moved before page `before_id`; defaults to last place inside parent.
 * - One of:
 *   - parent_id: attach page `id` to page `parent_id`.
 *   - domain_id (int) + route (string): attach page `id` directly to domain route;
 *     `route` is the url of route to move page into.
 * - For HTML pages:
 *   - app_id: which app page belongs to (defaults to site); can not move page from app to app.
 * - For Block pages:
 *   - app_id=site_blockpage
 *
 * POST parameters for domain routes:
 * - app_id=site_route
 * - domain_id (int)
 * - route_id: index of route to move
 * - before_route_id: `route_id` will be moved before `before_route_id`
 */
class siteMapMoveController extends waJsonController
{
    protected $model;

    public function execute()
    {
        $app_id = waRequest::request('app_id', null, 'string');
        if ($app_id == 'site_blockpage') {
            $this->executeMoveBlockPage();
        } else if ($app_id == 'site_route') {
            $this->executeMoveDomainRoute();
        } else {
            $this->executeMoveHtmlPage();
        }
    }

    protected function executeMoveHtmlPage()
    {
        $app_id = waRequest::request('app_id', 'site', 'string');
        wa($app_id);
        $class_name = $app_id.'PageModel';
        $this->executeMovePage(new $class_name());
    }

    protected function executeMovePage($page_model)
    {
        $page_id = waRequest::post('id', 0, 'int');
        $before_id = waRequest::post('before_id', 0, 'int');

        $parent_id = null;
        if ($before_id) {
            $before_page = $page_model->getById($before_id);
            $parent_id = ifset($before_page, 'parent_id', null);
        }

        if ($parent_id === null) {
            $parent_id = waRequest::post('parent_id');
            if (!$parent_id) {
                $domain_id = waRequest::request('domain_id', null, 'int');
                $route = waRequest::post('route', '', 'string');

                if ($page_model->fieldExists('domain_id')) {
                    $parent_id = array(
                        'domain_id' => $domain_id,
                        'route' => $route,
                    );
                } else {
                    $domain_model = new siteDomainModel();
                    $domain = $domain_model->getById($domain_id);
                    if (!$domain) {
                        throw new waException('Domain not found', 404);
                    }
                    $parent_id = array(
                        'domain' => $domain['name'],
                        'route' => $route,
                    );
                }
            }
        }

        $result = $page_model->move($page_id, $parent_id, $before_id);
        if ($result) {
            $this->response = $result;
        } else {
            $this->errors = _w('Database error');
        }
    }

    protected function executeMoveBlockPage()
    {
        $this->executeMovePage(new siteBlockpageModel());
    }

    protected function executeMoveDomainRoute()
    {
        $route_id = waRequest::post('route_id', '', 'string');
        $domain_id = waRequest::request('domain_id', null, 'int');
        $before_route_id = waRequest::post('before_route_id', null, 'string');

        $domain_model = new siteDomainModel();
        $domain = $domain_model->getById($domain_id);
        if (!$domain) {
            throw new waException('Domain not found', 404);
        }
        $domain = $domain['name'];

        $path = $this->getConfig()->getPath('config', 'routing');
        $all_routes = file_exists($path) ? include($path) : array();

        if (isset($all_routes[$domain][$route_id])) {

            $route_to_move = $all_routes[$domain][$route_id];
            $pattern = str_replace(array(' ', '.', '(', '!'), array('\s', '\.', '(?:', '\!'), ifset($route_to_move, 'url', ''));
            $pattern = preg_replace('/(^|[^\.])\*/ui', '$1.*?', $pattern);

            // Routes attached to the $route_id we move should move with it if new position shadows them.
            // List all the attached routes.
            $route_rows_over_another = [];
            foreach($all_routes[$domain] as $id => $route) {
                if ($id == $route_id) {
                    break;
                }
                if (isset($route['url']) && ifset($route, 'app', '') === 'site' && false === strpos($route['url'], '<') && rtrim($route['url'], '*').'*' !== $route['url']) {
                    if (preg_match('!^'.$pattern.'$!ui', $route['url'], $match)) {
                        $route_rows_over_another[$id] = $route;
                    }
                }
            }
            $route_rows_over_another_copy = $route_rows_over_another;

            $new_domain_routes = [];
            foreach($all_routes[$domain] as $id => $r) {
                $id = (string) $id;
                if ($id === $route_id) {
                    continue;
                }
                $new_domain_routes[$id] = $r;
                if ($route_to_move !== null) {
                    unset($route_rows_over_another[$id]);
                    if ($id === $before_route_id) {
                        $new_domain_routes += $route_rows_over_another;
                        $new_domain_routes[$route_id] = $route_to_move;
                        $route_to_move = null;
                    }
                }
            }
            if ($route_to_move !== null) {
                // last one on site map page = first one in routing file
                $new_domain_routes = $route_rows_over_another_copy + [$route_id => $route_to_move] + $new_domain_routes;
            }

            if (array_keys($all_routes[$domain]) != array_keys($new_domain_routes)) {
                $initial_shadowed_routes = $this->getShadowedRoutes($all_routes, $route_id);
                $new_shadowed_routes = $this->getShadowedRoutes($new_domain_routes, $route_id);
                $added_shadowed_routes = array_diff_key($new_shadowed_routes, $initial_shadowed_routes);
                if ($added_shadowed_routes) {
                    $this->errors[] = [
                        'error' => 'position_not_allowed',
                        'description' => _w('Such arrangement is not allowed. Probably, the section will stop working because the address of the section being dragged partially matches another address.'),
                        'shadowed_routes' => array_values($new_shadowed_routes),
                        'added_shadowed_routes' => array_values($added_shadowed_routes),
                    ];
                    return;
                } else {
                    $all_routes[$domain] = $new_domain_routes;
                    waUtils::varExportToFile($all_routes, $path);
                    (new waVarExportCache('problem_domains', 3600, 'site/settings/'))->delete();
                }
            }
        }

        $this->response['routing_errors'] = siteHelper::getRoutingErrorsInfo();
    }

    protected function getShadowedRoutes($all_routes, $route_id)
    {
        if (empty($all_routes[$route_id])) {
            return [];
        }
        $result = [];
        $before_route = true;
        foreach ($all_routes as $r_id => $route) {
            if ($r_id == $route_id) {
                $before_route = false;
            } else if ($before_route) {
                if (!isset($result[$route_id]) && $this->isShadowedBy($all_routes[$route_id], $route)) {
                    $result[$route_id] = $route_id;
                }
            } else {
                if ($this->isShadowedBy($route, $all_routes[$route_id])) {
                    $result[$r_id] = $route_id;
                }
            }
        }
        return $result;
    }

    protected function isShadowedBy($route, $by_route)
    {
        $route_url = ifset($route, 'url', '');
        $by_route_url = ifset($by_route, 'url', '');
        if (!$by_route_url || substr($by_route_url, -1) !== '*') {
            return $route_url === $by_route_url;
        }

        $route_url = rtrim($route_url, '/*');
        $by_route_url = rtrim($by_route_url, '/*');
        return substr($route_url, 0, strlen($by_route_url)) === $by_route_url;
    }
}
