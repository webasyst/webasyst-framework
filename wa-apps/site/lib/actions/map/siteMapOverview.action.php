<?php
/**
 * Backend sitemap overview screen. Lists app settlements, pages, etc.
 * Used both as a separate screen as well as in a left slide-over sidebar in page editor.
 */
class siteMapOverviewAction extends waViewAction
{
    public function execute()
    {
        $this->setLayout(new siteBackendLayout());

        $sidebar_mode = (bool) waRequest::request('sidebar_mode');
        $page_id = waRequest::request('page_id', null, 'int');
        $domain_id = waRequest::request('domain_id', null, 'int');
        $htmleditor = waRequest::request('htmleditor', null, 'int');

        // update last_domain_id
        if (!$sidebar_mode) {
            siteHelper::saveLastDomainId();
        }

        $domain = $this->getNeedDomain($domain_id);

        if ($domain['is_alias']){ //redirect for aliases
            $this->redirect(wa()->getAppUrl('site').'settings/?domain_id='.$domain_id);
        }
        $blockpage_model = new siteBlockpageModel();
        $pages = $blockpage_model->getByDomainWithVerifyDraftModifications($domain_id);

        list($apps, $routes, $has_redirect) = $this->getSitemapRoutesApps($domain);
        $migrated_from_ui13 = wa()->getSetting('migrated_from_ui13', false, 'site');
        $this->loadHtmlPages($domain, $routes);

        list($sitemap_tree, $root_page) = $this->buildSitemapTree($routes, $pages);
        $sitemap_table_rows = $this->buildFlatSitemapTable($sitemap_tree);
        $this->workupFrontendLinks($sitemap_table_rows, $domain);
        $this->blockpagesHasUrlOverlapByFrontendLink($sitemap_table_rows);

        $show_alert_moving_to_settings = $migrated_from_ui13 && !wa()->getUser()->getSettings('site', 'hide_alert_moving_to_settings', false);

        $apps_to_add = [];
        foreach ($apps as $app) {
            if ($app['id'] === 'site') {
                continue;
            }
            $apps_to_add[$app['id']] = [
                'id' => $app['id'],
                'name' => siteHelper::getAlternativeAppNames($app['id'], $app['name'], true),
                'icon' => $app['icon'],
                'pages' => ifset($app, 'pages', false),
            ];
        }

        $this->view->assign([
            'sidebar_mode'     => $sidebar_mode,
            'table_rows'       => $sitemap_table_rows,
            'domain_id'        => $domain_id,
            'domain_idn'       => waIdna::dec($domain['name']),
            'domain'           => $domain,
            'page_id'          => $page_id,
            'has_redirect'     => $has_redirect,
            'show_alert_moving_to_settings' => $show_alert_moving_to_settings,
            'is_htmleditor'    => $htmleditor,
            'root_page'        => $root_page,
            'apps_to_add'      => $apps_to_add,
            'app_hashes'       => $this->getAppHashes($apps),
        ]);

        $this->workupAndAssignPersonalApps($domain);
    }

    protected function getSitemapRoutesApps($domain)
    {
        // В $apps попадают только приложения, разрешённые на этом экране
        // (часть спрятана, чтобы не пугать пользователя техническими подробностями).
        // Остальные поселения настраивается на экране Настройки.
        $apps = wa()->getApps();
        $allowed_apps = siteHelper::getSitemapAppIds();
        foreach ($apps as $app_id => $app) {
            if (empty($app['frontend']) || !in_array($app['id'], $allowed_apps)) {
                unset($apps[$app_id]);
            }
        }

        // В $routes попадают только поселения разрешённых приложений
        $has_redirect = false;
        $apps_routed = array();
        $routes = wa()->getRouting()->getRoutes($domain['name']);
        foreach ($routes as $route_id => &$route) {
            // do not show redirects
            if (!empty($route['redirect'])) {
                if (!isset($route['disabled']) || !$route['disabled']) {
                    $has_redirect = true;
                }
                unset($routes[$route_id]);
                continue;
            }
            // only show allowed apps
            if (!isset($route['app']) || !in_array($route['app'], $allowed_apps)) {
                unset($routes[$route_id]);
                continue;
            }
            // do not show tech settlements of Site app
            if ($route['app'] === 'site' && !empty($route['site_tech_route'])) {
                unset($routes[$route_id]);
                continue;
            }

            $route['route_id'] = $route_id;
            $route['url_formatted'] = '/'.ltrim(rtrim($route['url'], '*'), '/');

            $apps_routed[$route['app']] = true;
            $route['app'] = ifempty($apps[$route['app']], [
                'id' => $route['app'],
                'disabled' => true
            ]);
        }

        return [$apps, $routes, $has_redirect];
    }

    protected function getAppPages($app_id, $domain, $route_urls)
    {
        try {
            if ($app_id != 'site') {
                wa($app_id);
            }
            $class_name = $app_id.'PageModel';
            if (!class_exists($class_name) || !$route_urls) {
                return [];
            }
            $pages_model = new $class_name();

            if ($app_id == 'site') {
                return $pages_model->select('id,name,url,full_url,status,route,parent_id')
                    ->order('parent_id,sort')
                    ->where('domain_id=? AND route IN (?)', [$domain['id'], $route_urls])
                    ->fetchAll('id');
            } else {
                return $pages_model->select('id,name,url,full_url,status,route,parent_id')
                    ->order('parent_id,sort')
                    ->where('domain=? AND route IN (?)', [$domain['name'], $route_urls])
                    ->fetchAll('id');
            }
        } catch (waException $e) {
            return [];
        }
    }

    protected function getPagesTree($pages)
    {
        foreach ($pages as $page_id => $page) {
            if ($page['parent_id']) {
                if (!empty($pages[$page['parent_id']])) {
                    $pages[$page['parent_id']]['children'][] = &$pages[$page_id];
                } else {
                    $pages[$page_id]['parent_id'] = null;
                }
            }
        }

        foreach ($pages as $page_id => $page) {
            if (!empty($page['parent_id'])) {
                unset($pages[$page_id]);
            }
        }

        return $pages;
    }

    protected function loadHtmlPages($domain, &$routes)
    {
        $routes_by_app = [];
        foreach ($routes as $route) {
            if (isset($route['app']['id'])) {
                $routes_by_app[$route['app']['id']][$route['url']] = $route['url'];
            }
        }

        $pages_by_app = [];
        foreach($routes_by_app as $app_id => $app_route_urls) {
            $pages_by_app[$app_id] = $this->getAppPages($app_id, $domain, array_values($app_route_urls));
        }

        $is_added_main_page = false;
        foreach($routes as &$route) {
            $app_id = ifset($route, 'app', 'id', null);
            if (!$app_id) {
                $route['pages'] = [];
                continue;
            }
            $pages = array_filter($pages_by_app[$app_id], function($page) use ($route) {
                return $page['route'] == $route['url'];
            });
            foreach($pages as &$p) {
                $p['route_params'] = $route;
                $p['private'] = !empty($route['private']);
            }
            unset($p);

            if ($app_id == 'site') {
                // Главная страница в поселениях Сайта не показывается как часть дерева страниц,
                // а показывается в первой строчке как корень всего дерева.
                foreach($pages as $page_id => $page) {
                    if (!$page['full_url'] || $page['full_url'] == '/') {
                        $route['root_page'] = $page;
                        unset($pages[$page_id]);
                        foreach($pages as $page_id => &$p) {
                            if ($p['parent_id'] == $page['id']) {
                                $p['parent_id'] == $page['parent_id'];
                            }
                        }
                        unset($p);
                        break;
                    }
                }

                // Если главную страницу в поселении Сайта не нашли, то её нужно создать
                if (!$is_added_main_page && empty($route['root_page'])) {
                    $page_model = new sitePageModel();
                    $name = _w('Home page');
                    if (rtrim($route['url'], '/*') !== '') {
                        // Not Home page
                        if (strlen(ifset($route, '_name', ''))) {
                            $name = $route['_name'];
                        } else {
                            $app_settings_model = new waAppSettingsModel();
                            $name = $app_settings_model->get('webasyst', 'name', _ws('My company'));
                        }
                    }
                    $page_id = $page_model->add([
                        'domain_id' => $domain['id'],
                        'name' => $name,
                        'title' => $name,
                        'url' => '',
                        'full_url' => '',
                        'content' => '',
                        'route' => $route['url'],
                        'status' => 0,
                        'parent_id' => null,
                    ]);
                    $route['root_page'] = $page_model->getById($page_id);
                    $is_added_main_page = true;
                }

                if (!empty($route['root_page']) && strlen($route['root_page']['name'])) {
                    $route['_name'] = $route['root_page']['name'];
                }
            }
            $route['pages'] = $this->getPagesTree($pages);
        }
        unset($route);
    }

    protected function buildSitemapTree($routes, $pages)
    {
        $route_rows = [];
        $root_settlement = null;
        $route_rows_over_another = [];
        foreach ($routes as $route) {

            if (isset($route['app']['id'])) {
                $route = ['row_type' => 'route_app'] + $route;
            } else {
                continue; // should never happen
            }

            if ($root_settlement) {
                $route['disabled'] = true;
                $route['misconfigured_settlement'] = true;
            } else if ($route['url'] === '*' && !$root_settlement) {
                $root_settlement = $route;
                continue;
            } else {
                if ($route['app']['id'] === 'site' && false === strpos($route['url'], '<') && rtrim($route['url'], '*').'*' !== $route['url']) {
                    $route['show_over_another_section'] = true;
                    $route_rows_over_another[] = $route;
                    continue;
                }
            }

            $route_rows[] = $route;

            if ($route_rows_over_another) {
                $something_changed = false;
                $pattern = str_replace(array(' ', '.', '(', '!'), array('\s', '\.', '(?:', '\!'), ifset($route, 'url', ''));
                $pattern = preg_replace('/(^|[^\.])\*/ui', '$1.*?', $pattern);
                foreach ($route_rows_over_another as $i => $r) {
                    if (preg_match('!^'.$pattern.'$!ui', $r['url'], $match)) {
                        unset($route_rows_over_another[$i]);
                        $something_changed = true;
                        $route_rows[] = $r;
                    }
                }
                if (!empty($something_changed)) {
                    $route_rows_over_another = array_values($route_rows_over_another);
                }
            }
        }

        $page_rows = [];
        $pages = $this->getPagesTree($pages);
        $root_page_tree = null;
        foreach($pages as $page) {
            $page += ['row_type' => 'blockpage'];
            if (!$root_page_tree && $this->getRootPageFromTree($page)) {
                $root_page_tree = $page;
                continue;
            }
            $page_rows[] = $page;
        }

        $root_page = null;
        if ($root_settlement) {
            $root_settlement['is_main'] = true;
        }
        if ($root_page_tree) {
            $root_page_tree['is_main'] = !$root_settlement;
            // When main page is blockpage, order is: main blockpage, all blockpages, main route (preceded by routes over it), then all other routes
            $result = array_merge(
                $route_rows,                                // all other routes
                array_reverse($route_rows_over_another),    // routes over main route
                array_reverse($page_rows),                  // all blockpages
                [$root_page_tree],                          // main blockpage
                $root_settlement ? [$root_settlement] : [], // main route
            );
            $root_page = $root_page_tree;
        } else {
            // When main page is a settlement, order is: main route (followed by routes over it), then all blockpages, then all other routes
            foreach ($route_rows_over_another as &$r) {
                $r['show_under_another_section'] = true;
            }
            unset($r);
            $result = array_merge(
                $route_rows,                                // all other routes
                array_reverse($page_rows),                  // all blockpages
                array_reverse($route_rows_over_another),    // routes over main route
                $root_settlement ? [$root_settlement] : [], // main route
            );
            if ($root_settlement) {
                $root_page = $root_settlement;
            }
        }

        $result = array_reverse($result);
        return array($result, $root_page);
    }

    protected function buildFlatSitemapTable($sitemap_tree)
    {
        $result = [];
        $addPageTree = function($page, $level = 0) use (&$result, &$addPageTree) {
            $page['offset_level'] = $level;
            $result[] = $page;
            foreach(ifset($page, 'children', []) as $p) {
                $addPageTree(['row_type' => $page['row_type'], 'app_id' => $page['app_id']] + $p, $level + 1);
            }
        };

        foreach($sitemap_tree as $node) {
            if (!is_null($node)) {
                switch ($node['row_type']) {
                    case 'blockpage':
                        $addPageTree(['app_id' => 'site'] + $node, 0);
                        break;
                    case 'route_app':
                        $node['is_broken_route_url'] = siteHelper::isBrokenAppRouteUrl($node);
                        $result[] = $node;
                        foreach(ifset($node, 'pages', []) as $page) {
                            $addPageTree([
                                'row_type' => 'htmlpage',
                                'app_id' => $node['app']['id'],
                            ] + $page, 1);
                        }
                        break;
                }
            }
        }
        return $result;
    }

    protected function getRootPageFromTree($page)
    {
        if (!$page['full_url'] || $page['full_url'] == '/') {
            return $page;
        }
        foreach(ifset($page, 'children', []) as $p) {
            $result = $this->getRootPageFromTree($p);
            if ($result) {
                return $result;
            }
        }
        return false;
    }

    protected function getNeedDomain(int $domain_id)
    {
        $domains = siteHelper::getDomains(true);
        if (!$domain_id || empty($domains[$domain_id])) {
            throw new waException('Domain not found', 404);
        }
        return $domains[$domain_id] + ['id' => $domain_id];
    }

    protected function workupAndAssignPersonalApps($domain)
    {
        // get domain config
        $domain_config_path = $this->getConfig()->getConfigPath('domains/'.$domain['name'].'.php');
        if (file_exists($domain_config_path)) {
            $domain_config = include($domain_config_path);
        } else {
            $domain_config = array();
        }

        // leave only need
        $auth_apps = array();
        $apps = wa()->getApps();
        foreach ($apps as $app_id => $app) {
            if (!empty($app['frontend']) && !empty($app['my_account'])) {
                $auth_apps[$app_id] = $app;

                $link = '';
                $auth_apps[$app_id] += [
                    'items' => $this->getItems($app_id, $link),
                    'link_path' => null,
                    'link' => null,
                ];
                if ($auth_apps[$app_id]['items']) {
                    if ($link) {
                        $prepared_link_path = substr($link, strlen(wa()->getRootUrl()));
                        $auth_apps[$app_id]['link'] = 'http://'.$domain['name'].'/'.$prepared_link_path;
                        $auth_apps[$app_id]['link_path'] = '/'.$prepared_link_path;
                    }
                } else {
                    unset($auth_apps[$app_id]);
                }
            }
        }

        $apps_routed = array();
        $routes = wa()->getRouting()->getRoutes($domain['name']);
        foreach ($routes as $route_id => &$route) {
            if (isset($route['app']) && isset($apps[$route['app']])) {
                $apps_routed[$route['app']] = true;
            }
        }

        // filtred and sorted apps
        $sorted_auth_apps = array();
        $apps_disabled = array();
        if (!empty($domain_config['personal'])) {
            foreach ($domain_config['personal'] as $app_id => $enabled) {
                if (isset($auth_apps[$app_id]) && isset($apps_routed[$app_id])) {
                    $sorted_auth_apps[$app_id] = $auth_apps[$app_id];
                    if ($enabled) {
                        $sorted_auth_apps[$app_id]['state'] = 'enabled';
                    } else {
                        $sorted_auth_apps[$app_id]['state'] = 'disabled';
                        $apps_disabled[$app_id] = true;
                    }
                }
            }
        }

        foreach ($auth_apps as $app_id => $app) {
            if (!isset($apps_disabled[$app_id])) {
                $sorted_auth_apps[$app_id] = $app;
                if (isset($apps_routed[$app_id])) {
                    $sorted_auth_apps[$app_id]['state'] = 'enabled';
                } else {
                    $sorted_auth_apps[$app_id]['state'] = 'no_route';
                }
            }
        }

        $auth_config = wa()->getAuthConfig($domain['name']);
        if (isset($auth_config['app']) && !empty($apps_disabled[$auth_config['app']])) {
            $this->view->assign('profile_disabled', true);
        }

        $protocol = waRequest::isHttps() ? 'https://' : 'http://';
        $auth_route = ifempty($auth_config['route_url'], '*');
        $auth_link = $protocol.$domain['name'].'/'.rtrim($auth_route, '*').'my/';

        $this->view->assign(array(
            'auth_enabled'  => !empty($auth_config['auth']),
            'auth_app'      => ifset($auth_config['app']),
            'auth_apps'     => $sorted_auth_apps,
            'auth_link'     => $auth_link,
            'auth_route'    => $auth_route,
        ));
    }

    protected function getItems($app_id, &$link = null)
    {
        if (!wa()->appExists($app_id) || $app_id === 'site') {
            return array();
        }

        $old_app = wa()->getApp();

        if ($old_app != $app_id) {
            waSystem::getInstance($app_id, null, true);
        }
        $class_name = $app_id.'MyNavAction';
        $result = array();
        if (class_exists($class_name)) {
            /**
             * @var waViewAction $action
             */
            $action = new $class_name();
            wa()->getView()->assign('my_nav_selected', '');
            try {
                $html = $action->display();
                $link = '';
                if (preg_match_all('/<li.*?>(.*?)<\/li>/uis', $html, $match)) {
                    foreach ($match[1] as $m) {
                        if (!$link && preg_match('/href="(.*?)"/uis', $m, $link_m)) {
                            $link = $link_m[1];
                            if (substr($link, 0, 4) == 'http') {
                                // Это значит, что личный кабинет на текущем домене не поселен, но поселен на другом.
                                $link = '';
                            }
                        }
                        $m = preg_replace('#<(script|style).*?>.*?</\\1>#uis', '', $m);
                        $result[] = trim(strip_tags($m));
                    }
                }
            } catch (Exception $e) {
            }
        }

        if ($old_app != $app_id) {
            wa()->setActive($old_app);
        }
        return $result;
    }

    protected function workupFrontendLinks(&$rows, $domain)
    {
        if (waRequest::isHttps()) {
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }
        $domain_root_url = $protocol.$domain['name'].'/';

        foreach($rows as &$row) {
            if (isset($row['full_url'])) {
                if (isset($row['route'])) {
                    $full_url = rtrim($row['route'], '*').$row['full_url'];
                } else {
                    $full_url = rtrim($row['full_url'], '/');
                    if ($full_url) {
                        $full_url .= '/';
                    }
                }
            } elseif (isset($row['url_formatted'])) {
                $full_url = ltrim($row['url_formatted'], '/');
            } else {
                continue;
            }

            $row['frontend_link'] = $domain_root_url.$full_url;
        }
        unset($row);
    }

    protected function blockpagesHasUrlOverlapByFrontendLink(&$rows = [])
    {
        $blockpages = [];
        $urls_map = [];
        foreach ($rows as $i => &$row) {
            if (ifset($row, 'row_type', '') === 'blockpage') {
                $blockpages[] = &$row;
            } else {
                if (empty($row['show_over_another_section'])) {
                    $urls_map[$row['frontend_link']] = $i;
                }
            }
        }
        unset($row);

        if (!$urls_map) {
            return;
        }

        foreach ($blockpages as &$p) {
            $p['show_over_another_section'] = isset($urls_map[$p['frontend_link']]);
        }
        unset($p);
    }

    protected function getAppHashes($apps)
    {
        $app_hashes = [];
        foreach ($apps as $app) {
            if (!empty($app['pages'])) {
                $app_hashes[$app['id']] = siteHelper::getPreviewHash($app['id']);
            }
        }
        return $app_hashes;
    }

}
