<?php

class siteInstaller
{

    public function addDefaultVariables() {
        $model = new waModel();

        if (!$model->query('SELECT COUNT(*) FROM site_variable')->fetchField()) {
            $locale_data = [
                'ru_RU' => [
                    ['id' => 'company-name', 'content' => 'ООО «Моя компания»', 'description' => 'Название компании', 'sort' => 1],
                    ['id' => 'address', 'content' => 'Москва, ул. Пушкина, д. 1, оф. 1', 'description' => 'Адрес', 'sort' => 2],
                    ['id' => 'phone', 'content' => '+7 (123) 456-78-90', 'description' => 'Телефон', 'sort' => 3],
                    ['id' => 'email', 'content' => 'info@my-company.ru', 'description' => 'Email', 'sort' => 4],
                ],
                'en_US' => [
                    ['id' => 'company-name', 'content' => 'Your Company Name Ltd', 'description' => 'Company Name', 'sort' => 1],
                    ['id' => 'address', 'content' => '123 Kingsway, London, WC2B 6NH, United Kingdom', 'description' => 'Address', 'sort' => 2],
                    ['id' => 'phone', 'content' => '+44 01 2345 6789', 'description' => 'Phone', 'sort' => 3],
                    ['id' => 'email', 'content' => 'info@your-company.com', 'description' => 'Email', 'sort' => 4],
                ]
            ];

            $data_to_insert = waLocale::getLocale() === 'ru_RU' ? $locale_data['ru_RU'] : $locale_data['en_US'];

            foreach ($data_to_insert as $data) {
                $model->exec('INSERT INTO `site_variable`(`id`, `content`, `create_datetime`, `description`, `sort`) VALUES (?, ?, CURRENT_TIMESTAMP, ?, ?)',
                    $data['id'], $data['content'], $data['description'], $data['sort']);
            }
        }
    }

    protected $routing = null;

    protected function loadRouting()
    {
        if ($this->routing === null) {
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
            $path = wa()->getConfig()->getPath('config', 'routing');
            if (file_exists($path)) {
                $this->routing = include($path);
            } else {
                $this->routing = [];
            }
        }
        return $this->routing;
    }

    // Update routing: add settlements for all frontend apps that do not have settlements yet;
    // remove settlements for apps that are not installed.
    public function prepareRouting()
    {
        $routing = $this->loadRouting();
        $apps = include(wa()->getConfig()->getPath('config', 'apps'));

        $frontend_apps = [];
        foreach ($apps as $app_id => $enabled) {
            if (!$enabled) {
                continue;
            }
            $app_config = wa()->getAppPath('lib/config/app.php', $app_id);
            if (!file_exists($app_config)) {
                continue;
            }
            $app_info = include($app_config);
            if (empty($app_info['frontend'])) {
                continue;
            }

            $frontend_apps[$app_id] = $app_info;
        }

        $routing_changed = false;
        foreach ($routing as $domain => &$domain_routes) {
            if (!is_array($domain_routes)) {
                continue;
            }

            $need_apps = $frontend_apps;
            foreach ($domain_routes as $route_id => $route) {
                if (empty($route['app'])) {
                    continue;
                }
                if (empty($frontend_apps[$route['app']])) {
                    unset($domain_routes[$route_id]);
                    $routing_changed = true;
                    continue;
                } else if ($route['app'] === 'site' && empty($route['priority_settlement'])) {
                    $domain_routes[$route_id]['priority_settlement'] = true;
                    $routing_changed = true;
                }
                unset($need_apps[$route['app']]);
            }

            foreach ($need_apps as $app_id => $app_info) {
                $routing_params = ifset($app_info, 'routing_params', []);
                foreach ($routing_params as $k => $v) {
                    if (is_callable($v)) {
                        $routing_params[$k] = call_user_func($routing_param_value);
                    }
                }
                $routing_params = [
                    'url' => $app_id == 'site' ? 'new-page/*' : $app_id.'/*',
                    'app' => $app_id,
                    'theme' => 'default',
                    'theme_mobile' => 'default',
                ] + $routing_params;

                if (empty($app_info['themes']) || wa()->isSingleAppMode()) {
                    $routing_params['private'] = true;
                }

                array_unshift($domain_routes, $routing_params);
                $routing_changed = true;
            }
        }
        unset($domain_routes);

        if ($routing_changed) {
            $this->routing = $routing;
            $path = wa()->getConfig()->getPath('config', 'routing');
            waUtils::varExportToFile($this->routing, $path);
        }
    }

    // Create all sites that exist in routing but do not yet exist in site_domain
    public function addDomains()
    {
        $domain_model = new siteDomainModel();
        $domains = $domain_model->getAll('name');
        foreach (array_keys($this->loadRouting()) as $domain) {
            if (empty($domains[$domain])) {
                $domain_model->insert(array('name' => $domain));
            }
        }
    }

    // Create HTML root page status=published for all site settlements in routing.
    public function addPages()
    {
        $app_settings_model = new waAppSettingsModel();
        $default_route_name = $app_settings_model->get('webasyst', 'name', _ws('My company'));
        $domain_ids = array_flip(siteHelper::getDomains());

        $route_urls = [];

        foreach ($this->loadRouting() as $domain => $domain_routes) {
            if (!is_array($domain_routes) || empty($domain_ids[$domain])) {
                continue;
            }
            foreach ($domain_routes as $route) {
                if (ifset($route, 'app', null) !== 'site' || empty($route['url'])) {
                    continue;
                }
                $route_urls[] = $route['url'];
            }
        }
        if (!$route_urls) {
            return;
        }

        $page_model = new sitePageModel();
        $rows = $page_model->getByField([
            'parent_id' => null,
            'full_url' => '',
            'url' => '',
            'route' => $route_urls,
        ], true);
        $pages = [];
        foreach ($rows as $p) {
            $pages[$p['domain_id']][$p['route']] = true;
        }

        foreach ($this->loadRouting() as $domain => $domain_routes) {
            if (!is_array($domain_routes) || empty($domain_ids[$domain])) {
                continue;
            }
            $domain_id = $domain_ids[$domain];

            foreach ($domain_routes as $route) {
                if (ifset($route, 'app', null) !== 'site' || empty($route['url']) || !empty($pages[$domain_id][$route['url']])) {
                    continue;
                }
                if (rtrim($route['url'], '/*') === '') {
                    $name = _w('Home page');
                } else {
                    $name = ifempty($route, '_name', $default_route_name);
                }
                $page_id = $page_model->add([
                    'domain_id' => $domain_id,
                    'name' => $name,
                    'title' => $name,
                    'url' => '',
                    'full_url' => '',
                    'content' => '',
                    'route' => $route['url'],
                    'status' => 1,
                    'parent_id' => null,
                ]);

                $page_model->updateById($page_id, [
                    'content' => $this->getDemoContent($page_id, $domain_id)
                ]);
            }
        }
    }

    protected function getDemoContent($page_id, $domain_id)
    {
        $view = wa('site')->getView();

        $view->assign([
            'page_id'   => $page_id,
            'domain_id' => $domain_id,
        ]);

        $template = wa()->getAppPath('templates/actions/frontend/includes/demo_htmlpage.html', 'site');

        try {
            return $view->fetch($template);
        } catch (Exception $e) {
            waLog::dump(['Error rendering template demo_htmlpage.html', $e->getMessage(), $e->getTraceAsString()]);
            return '';
        }
    }

}
