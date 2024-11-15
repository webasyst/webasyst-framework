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
            $parent_id = ifset($before_page, 'parent_id', '');
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

                // Attach page under domain's main page if it exists
                $main_page = $page_model->getByField($parent_id + [
                    'full_url' => '',
                ]);
                if ($main_page) {
                    $parent_id = $main_page['id'];
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
            $new_domain_routes = [];
            $route_to_move = $all_routes[$domain][$route_id];
            foreach($all_routes[$domain] as $id => $r) {
                $id = (string) $id;
                if ($id === $route_id) {
                    continue;
                }
                $new_domain_routes[$id] = $r;
                if ($route_to_move !== null && $id === $before_route_id) {
                    $new_domain_routes[$route_id] = $route_to_move;
                    $route_to_move = null;
                }
            }
            if ($route_to_move !== null) {
                // last one on site map page = first one in routing file
                $new_domain_routes = [$route_id => $route_to_move] + $new_domain_routes;
            }

            if (array_keys($all_routes[$domain]) != array_keys($new_domain_routes)) {
                $all_routes[$domain] = $new_domain_routes;
                waUtils::varExportToFile($all_routes, $path);
                (new waVarExportCache('problem_domains', 3600, 'site/settings/'))->delete();
            }
        }

        $this->response['routing_errors'] = siteHelper::getRoutingErrorsInfo();
    }
}
