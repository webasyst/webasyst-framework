<?php
/**
 * Duplicate a block page or HTML page
 */
class siteMapDuplicateController extends waJsonController
{
    public function execute()
    {
        $entity_type = waRequest::request('type', null, 'string');

        if (!waLicensing::check('site')->hasPremiumLicense()) {
            throw new waException(_w('The premium license is required to copy pages.'), 403);
        }

        try {
            switch ($entity_type) {
                case 'blockpage':
                    $this->executeBlockpage();
                    break;
                case 'htmlpage':
                    $this->executeHtmlpage();
                    break;
                case 'route':
                    $this->executeRoute();
                    break;
                default:
                    $this->errors[] = [
                        'error' => 'type_required',
                        'description' => 'type parameter is required',
                    ];
                    return;
            }
        } catch (Throwable $e) {
            $this->errors[] = self::formatError($e);
        }
    }

    public function executeBlockpage()
    {
        $page_id = waRequest::post('id', null, 'int');

        $blockpage_model = new siteBlockpageModel();
        if ($page_id) {
            $page = $blockpage_model->getById($page_id);
        }
        if (empty($page)) {
            $this->errors[] = [
                'error' => 'page_not_found',
                'description' => 'Page with this id does not exist',
            ];
            return;
        }

        $url_prefix = '-copy';
        $page['name'] = sprintf_wp('%s copy', $page['name']);
        $page['url'] = rtrim($page['url'], '/').$url_prefix;
        $page['full_url'] = rtrim($page['full_url'], '/').$url_prefix;
        $page['update_datetime'] = $page['create_datetime'] = date('Y-m-d H:i:s');
        $page['final_page_id'] = null;
        $page['status'] = 'final_unpublished';
        unset($page['id']);

        $new_page_id = $blockpage_model->insert($page);
        $blockpage_model->copyContents($page_id, $new_page_id);
        $blockpage_model->move($new_page_id, $page['parent_id'] ?? [
            'domain_id' => $page['domain_id'],
        ], $page_id);

        $this->response = [
            'id' => $new_page_id,
        ];
    }

    public function executeHtmlpage()
    {
        $app_id = waRequest::post('app', null, 'string');
        $page_id = waRequest::post('id', null, 'int');
        if (!$app_id) {
            $this->errors[] = [
                'error' => 'app_id_required',
                'description' => 'app_id parameter is required',
            ];
            return;
        }

        if ($app_id != 'site') {
            wa($app_id);
        }
        $class_name = $app_id.'PageModel';
        if (!class_exists($class_name)) {
            $this->errors[] = [
                'error' => 'no_app_page_model',
                'description' => 'Application does not support HTML pages',
            ];
            return;
        }
        $pages_model = new $class_name();
        if ($page_id) {
            $page = $pages_model->getById($page_id);
        }
        if (empty($page)) {
            $this->errors[] = [
                'error' => 'page_not_found',
                'description' => 'Page with this id does not exist',
            ];
            return;
        }

        $url_prefix = '-copy/';
        $page['name'] = sprintf_wp('%s copy', $page['name']);
        $page['url'] = rtrim($page['url'], '/').$url_prefix;
        $page['full_url'] = rtrim($page['full_url'], '/').$url_prefix;
        $page['update_datetime'] = $page['create_datetime'] = date('Y-m-d H:i:s');
        $page['create_contact_id'] = wa()->getUser()->getId();
        $page['status'] = 0;
        unset($page['id']);

        $new_page_id = $pages_model->insert($page);

        $move_parent = $page['parent_id'];
        if (!$move_parent) {
            if ($app_id == 'site') {
                $move_parent = [
                    'domain_id' => $page['domain_id'],
                    'route' => $page['route'],
                ];
            } else if (isset($page['domain']) && isset($page['route'])) {
                $move_parent = [
                    'domain' => $page['domain'],
                    'route' => $page['route'],
                ];
            }
        }
        if ($move_parent) {
            $pages_model->move($new_page_id, $move_parent, $page_id);
        }

        $params = $pages_model->getParams($page_id);
        $pages_model->setParams($new_page_id, $params);

        $this->response = [
            'id' => $new_page_id,
        ];
    }

    public function executeRoute()
    {
        $domain_id = waRequest::post('domain_id', null, 'string');
        $route_id = waRequest::post('route_id', null, 'string');

        $domains = siteHelper::getDomains(true);
        if (!isset($domains[$domain_id])) {
            throw new waException('Domain not found');
        }
        $domain = $domains[$domain_id] + ['id' => $domain_id];

        // Routes
        $routing_path = $this->getConfig()->getPath('config', 'routing');
        if (file_exists($routing_path)) {
            $routes = include($routing_path);
        }
        if (!isset($routes[$domain['name']][$route_id])) {
            throw new waException('Route not found');
        }

        // Duplicate route, changing its _name and url
        $orig_route = $routes[$domain['name']][$route_id];
        $new_route = self::modifyNewRoute($orig_route, $routes[$domain['name']]);

        // Determine id of new route
        $routes[$domain['name']][] = $new_route;
        $new_route_id = end(ref(array_keys($routes[$domain['name']])));
        unset($routes[$domain['name']][$new_route_id]);

        // Insert new route just before its original
        $new_domain_routes = [];
        foreach ($routes[$domain['name']] as $r_id => $r) {
            if ($r_id == $route_id) {
                $new_domain_routes[$new_route_id] = $new_route;
            }
            $new_domain_routes[$r_id] = $r;
        }

        $routes[$domain['name']] = $new_domain_routes;
        waUtils::varExportToFile($routes, $routing_path);

        // Duplicate HTML pages attached to route
        if (isset($orig_route['url']) && !empty($new_route['app']) && wa()->appExists($new_route['app'])) {
            $page_model = siteHelper::getPageModel($new_route['app']);
            if ($page_model) {
                self::duplicateAllRoutePages($domain, $orig_route['url'], $new_route['url'], $page_model);
            }
        }

        $this->response = [
            'id' => $new_route_id,
        ];
    }

    protected static function formatError(Throwable $e)
    {
        $result = [
            'error' => 'server_error',
            'description' => $e->getMessage().' ('.$e->getCode().')',
        ];
        if (waSystemConfig::isDebug()) {
            $result['stack'] = $e instanceof waException ? $e->getFullTraceAsString() : $e->getTraceAsString();
        }
        return $result;
    }

    protected function modifyNewRoute($orig_route, $domain_routes)
    {
        $new_route = $orig_route;
        $orig_app = ifset($orig_route, 'app', '');
        $orig_url = ifset($orig_route, 'url', $orig_app.'/*');
        $new_route['url'] = $this->getUniqueRouteUrl($orig_url, $orig_app, $domain_routes);

        $orig_name = ifset($orig_route, '_name', null);
        if (!$orig_name && $orig_app && wa()->appExists($orig_app)) {
            $apps = wa()->getApps();
            $orig_name = ifset($apps, $orig_app, 'name', null);
        }
        if ($orig_name) {
            $new_route['_name'] = sprintf_wp('%s copy', $orig_name);
        }
        return $new_route;
    }

    protected function getUniqueRouteUrl($orig_url, $orig_app, $domain_routes)
    {
        $existing_route_urls = [];
        foreach ($domain_routes as $r) {
            if (isset($r['url'])) {
                $existing_route_urls[$r['url']] = $r['url'];
            }
        }

        $i = 1;
        while (empty($result)) {
            $suffix = $i > 1 ? '-copy-'.$i.'/' : '-copy/';
            if (trim($orig_url, '/*')) {
                $result = rtrim($orig_url, '/*').$suffix;
            } else {
                $result = ifempty($orig_app, 'main').$suffix;
            }
            if ($orig_url != rtrim($orig_url, '*')) {
                $result .= '*';
            }
            if (isset($existing_route_urls[$result])) {
                unset($result);
                $i++;
            }
        }
        return $result;
    }

    // Create copy of each page found in given $page_model attached to $domain and $source_route, into $dest_route
    // Works with HTML pages of any app.
    protected static function duplicateAllRoutePages($domain, $source_route, $dest_route, $page_model)
    {
        if (!$page_model->fieldExists('route') || !$page_model->fieldExists('name') || !$page_model->fieldExists('full_url')) {
            return; // unsupported
        }
        if ($page_model->fieldExists('domain_id')) {
            $query = $page_model->where('domain_id=? AND route=?', $domain['id'], $source_route);
        } else {
            $query = $page_model->where('domain=? AND route=?', $domain['name'], $source_route);
        }

        try {
            // fetch page tree to copy
            $pages = $query->select('id,name,full_url,parent_id')->fetchAll('id');
            if (!$pages) {
                return;
            }
        } catch (waException $e) {
            return; // no parent_id field? give up
        }

        // Prepare SQL templates used in the loop below
        $params_sql = null;

        $fields = $page_model->getEmptyRow();
        unset($fields['id']);
        $all_fields = join(',', array_keys($fields));

        $page_sql = sprintf("
            INSERT INTO %s (%s)
            SELECT %s
            FROM %s
            WHERE id=?",
            $page_model->getTableName(),
            $all_fields,
            $all_fields,
            $page_model->getTableName()
        );
        if (method_exists($page_model, 'getParamsModel')) {
            $page_params_model = $page_model->getParamsModel();
            if ($page_params_model) {
                $params_sql = sprintf(
                    "INSERT INTO %s (`page_id`, `name`, `value`)
                     SELECT ?, `name`, `value`
                     FROM %s
                     WHERE page_id=?",
                    $page_params_model->getTableName(),
                    $page_params_model->getTableName()
                );
            }
        }

        $update = [
            'route' => $dest_route,
        ];

        // Loop over $unprocessed_ids many times, process one level of descendants each time
        $old_id_to_new_id = [];
        $something_changed = true;
        $unprocessed_ids = array_keys($pages);
        while ($something_changed) {
            $something_changed = false;
            foreach ($unprocessed_ids as $i => $id) {
                $p = $pages[$id];
                if (empty($p['parent_id']) || isset($old_id_to_new_id[$p['parent_id']])) {
                    $new_page_id = null;
                    try {
                        $new_page_id = $page_model->query($page_sql, [$id])->lastInsertId();
                        $update2 = $update + [
                            'parent_id' => ifset($old_id_to_new_id, $p['parent_id'], null),
                        ];
                        if (!trim($p['full_url'], '/*')) {
                            $update2['name'] = sprintf_wp('%s copy', $p['name']);
                        }
                        $page_model->updateById($new_page_id, $update2);

                        if ($params_sql) {
                            $page_params_model->exec($params_sql, [$new_page_id, $id]);
                        }
                    } catch (waException $e) {
                    }

                    unset($unprocessed_ids[$i]);
                    if ($new_page_id) {
                        $old_id_to_new_id[$id] = $new_page_id;
                        $something_changed = true;
                    }
                }
            }
        }
    }
}
