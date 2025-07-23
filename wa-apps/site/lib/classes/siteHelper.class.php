<?php

class siteHelper
{
    protected static $domain_id = null;
    protected static $domains = array();
    protected static $locale = null;
    protected static $themes = array();

    public static function getRoutingErrorsInfo($domain_id = null)
    {
        if (empty($domain_id)) {
            $domain_id = self::getDomainId();
        }
        $not_install_text = '';
        $incorrect_text = '';
        $incorrect_ids = array();
        // Get from cache.
        $routing_error = wa('site')->getConfig()->getRoutingErrors();

        $apps = wa()->getApps();

        if (!empty($routing_error)) {
            $not_install = ifset($routing_error, 'apps', $domain_id, 'not_install', null);
            if ($not_install && wa()->whichUI() === '1.3') {
                $not_install_id = array();
                foreach ($not_install as $app_name) {
                    if (isset($apps[$app_name]['name'])) {
                        $not_install_id[] = $apps[$app_name]['name'];
                    }
                }

                $not_install_text = sprintf(_w('You have no rules set up for %s app.', 'You have no rules set up for %s apps.',
                    count($not_install), false), implode(_w('”, “'), $not_install_id));
            }

            $incorrect = ifset($routing_error, 'apps', $domain_id, 'incorrect', null);
            if ($incorrect) {

                $incorrect_apps = array();
                $incorrect_text_rules = array();
                $incorrect_redirect_rules = array();
                $incorrect_text_parts = array();

                foreach ($incorrect as $rule_id => $app_name) {
                    if (isset($apps[$app_name]['name'])) {
                        $incorrect_ids[$rule_id] = $apps[$app_name]['name'];
                        $incorrect_apps[$rule_id] = $apps[$app_name]['name'];
                    }

                    if ($app_name == ':text') {
                        $incorrect_ids[$rule_id] = _w('Custom text');
                        $incorrect_text_rules[$rule_id] = _w('Custom text');
                    }

                    if ($app_name == ':redirect') {
                        $incorrect_ids[$rule_id] = _w('Redirect');
                        $incorrect_redirect_rules[$rule_id] = _w('Redirect');
                    }
                }

                $domain = ifset(self::$domains, $domain_id, 'name', '');

                if ($incorrect_apps) {
                    $incorrect_text_parts[] = sprintf(_w('Some rules of %s app are incorrect.', 'Some rules of %s apps are incorrect.',
                        count($incorrect_apps), false), implode(_w('”, “'), $incorrect_apps));
                }
                if ($incorrect_text_rules) {
                    $incorrect_text_parts[] = _w('“Custom text” rule is incorrect.', '%d “Custom text” rules are incorrect.', count($incorrect_text_rules));
                }
                if ($incorrect_redirect_rules) {
                    $incorrect_text_parts[] = _w('“Redirect” rule is incorrect.', '%d “Redirect” rules are incorrect.', count($incorrect_redirect_rules));
                }

                $incorrect_text_parts[] = sprintf(_w('Move rule %s/* to the bottom of the rule list.'), waIdna::dec($domain));

                $incorrect_text = join(PHP_EOL, $incorrect_text_parts);

            }
        }

        return array(
            'not_install'   => $not_install_text,
            'incorrect'     => $incorrect_text,
            'incorrect_ids' => $incorrect_ids
        );
    }

    public static function getDomains($full = false)
    {
        if (!self::$domains) {
            $domain_model = new siteDomainModel();
            $q = $domain_model->select('*')->order('id');
            if (!wa()->getUser()->isAdmin('site')) {
                $domain_ids = wa()->getUser()->getRights('site', 'domain.%', false);
                if ($domain_ids) {
                    $q->where("id IN ('".implode("','", $domain_ids)."')");
                } else {
                    $q->where('0');
                }
            }
            self::$domains = $q->fetchAll('id');
            if (wa()->getUser()->isAdmin('site')) {
                $routes = wa('wa-system')->getConfig()->getRouting();
                // hide default routing (for all domains)
                if (isset($routes['default'])) {
                    unset($routes['default']);
                }
                $ds = array();
                foreach (self::$domains as $d) {
                    $ds[] = $d['name'];
                }
                foreach ($routes as $r_id => $r) {
                    if (!is_array($r)) {
                        unset($routes[$r_id]);
                    }
                }
                $new_domains = array_diff(array_keys($routes), $ds);
                if ($new_domains) {
                    foreach ($new_domains as $d) {
                        $domain_model->insert(array('name' => $d));
                    }
                    self::$domains = $domain_model->select('*')->fetchAll('id');
                }
                if (!self::$domains) {
                    $domain_model->insert(array('name' => wa()->getConfig()->getDomain()));
                    self::$domains = $domain_model->select('*')->fetchAll('id');
                }
            }
            // hide default routing (for all domains)
            if (isset(self::$domains['default'])) {
                unset(self::$domains['default']);
            }
        }
        $result = array();
        foreach (self::$domains as $id => $d) {
            $result[$id] = $d['title'] ? $d['title'] : $d['name'];
            if ($full) {
                $result[$id] = array(
                    'name'     => $d['name'],
                    'title'    => $result[$id],
                    'style'    => $d['style'],
                    'is_alias' => wa()->getRouting()->isAlias($d['name'])
                );
            }
        }
        return $result;
    }

    public static function getDomainId()
    {
        if (!self::$domain_id) {

            $domains = self::getDomains(true);
            if (wa()->getEnv() === 'frontend') {
                $domain = wa()->getRouting()->getDomain(null, true);
            } else {
                $domain_id = waRequest::get('domain_id');
                if (is_numeric($domain_id)) {
                    self::$domain_id = (int)$domain_id;
                } else {
                    $domain = $domain_id;
                    unset($domain_id);
                }
            }

            if (!self::$domain_id && !empty($domain)) {
                foreach ($domains as $d_id => $d) {
                    if ($d['name'] == $domain) {
                        self::$domain_id = $d_id;
                        break;
                    }
                }
            }

            if (wa()->getEnv() !== 'frontend') {
                if (!self::$domain_id) {
                    self::$domain_id = wa()->getUser()->getSettings('site', 'last_domain_id');
                    if (!isset($domains[self::$domain_id])) {
                        self::$domain_id = null;
                    }
                }
                if (!self::$domain_id) {
                    self::$domain_id = current(array_keys($domains));
                }
            }

            if (self::$domain_id && !isset($domains[self::$domain_id])) {
                throw new waException('Domain not found', 404);
            }
        }
        return self::$domain_id;
    }

    public static function setDomain($id, $domain)
    {
        self::getDomains();
        self::$domains[$id]['name'] = $domain;
    }

    public static function getDomain($key = 'name')
    {
        self::getDomains();
        return self::$domains[self::getDomainId()][$key];
    }

    public static function getDomainInfo()
    {
        self::getDomains();
        $domain_info = self::$domains[self::getDomainId()];
        $domain_info['id'] = self::getDomainId();
        return $domain_info;
    }

    public static function getApp($info = true)
    {
        $app_id = waRequest::get('app');
        if (!$app_id) {
            $app_id = wa()->getConfig()->getApplication();
        }
        if ($info) {
            return wa()->getAppInfo($app_id);
        }
        return $app_id;
    }

    public static function getApps($app_key = false)
    {
        $wa = wa();
        $routes = $wa->getRouting()->getRoutes(self::getDomain());
        $all_apps = $wa->getApps();

        $apps = array();
        foreach ($routes as $route_id => $route) {
            if (isset($route['app'])) {
                $app_id = $route['app'];
                if (!isset($all_apps[$app_id])) {
                    continue;
                }
                if (isset($route['parent']) && isset($route['page_id'])) {
                    continue;
                }
                if (!isset($apps[$app_id])) {
                    if ($app_key && (!isset($all_apps[$app_id][$app_key]) || !$all_apps[$app_id][$app_key])) {
                        continue;
                    }
                    $apps[$app_id] = $all_apps[$app_id];
                    $apps[$app_id]['routes'] = array();
                }
                $apps[$app_id]['routes'][$route_id] = $route;
            }
        }
        return $apps;

    }

    public static function getThemes($app_id, $name_only = true)
    {
        if (!isset(self::$themes[$app_id])) {
            self::$themes[$app_id] = wa()->getThemes($app_id);
        }
        if ($name_only) {
            $themes = self::$themes[$app_id];
            foreach ($themes as &$theme) {
                if (!isset($theme['name'])) {
                    throw new waException("Invalid theme");
                }
                $theme = $theme['name'];
            }
            return $themes;
        }
        return self::$themes[$app_id];
    }

    public static function copyTheme($source, $dest)
    {
        if (!file_exists($dest)) {
            waFiles::create($dest);
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
        $n = strlen($source);
        foreach ($iterator as $file) {
            $rel_path = str_replace('\\', '/', substr($file, $n));
            if (strpos($rel_path, '/.svn') !== false) {
                continue;
            }
            if ($file->isDir() && $file->getFileName() !== '.' && $file->getFileName() !== '..') {
                mkdir($dest.'/'.$rel_path);
            } elseif ($file->isFile()) {
                copy($file->getPathName(), $dest.'/'.$rel_path);
                if (basename($file->getPathName()) == 'theme.xml') {
                    @touch($dest.'/'.$rel_path);
                }
            }
        }
    }

    public static function getDomainUrl()
    {
        $u1 = rtrim(wa()->getRootUrl(false, false), '/');
        $u2 = rtrim(wa()->getRootUrl(false, true), '/');
        if ($u1 != $u2) {
            return substr($u2, strlen($u1));
        } else {
            return '';
        }
    }

    public static function sortThemes($themes, $route)
    {
        $result = array();
        $t = isset($route['theme']) ? $route['theme'] : 'default';
        if (isset($themes[$t])) {
            $result[$t] = $themes[$t];
            unset($themes[$t]);
        }
        $t = isset($route['theme_mobile']) ? $route['theme_mobile'] : '';
        if ($t && $t != $route['theme'] && isset($themes[$t])) {
            $result[$t] = $themes[$t];
            unset($themes[$t]);
        }
        foreach ($themes as $t => $theme) {
            $result[$t] = $theme;
        }
        return $result;
    }

    public static function validateDomainUrl($url)
    {
        $url = mb_strtolower(trim($url, '/'));
        $url = preg_replace('~[/\\\\]+~', '/', $url);
        if (preg_match('~[:<>"%\?]|/\.\./~', $url)) {
            return false;
        }
        return $url;
    }

    public static function getOneStringKey($dkim_pub_key)
    {
        $one_string_key = trim(preg_replace('/^\-{5}[^\-]+\-{5}(.+)\-{5}[^\-]+\-{5}$/s', '$1', trim($dkim_pub_key)));
        //$one_string_key = str_replace('-----BEGIN PUBLIC KEY-----', '', $dkim_pub_key);
        //$one_string_key = trim(str_replace('-----END PUBLIC KEY-----', '', $one_string_key));
        $one_string_key = preg_replace('/\s+/s', '', $one_string_key);
        return $one_string_key;
    }

    public static function getDkimSelector($email)
    {
        $e = explode('@', $email);
        return trim(preg_replace('/[^a-z0-9]/i', '', $e[0])).'wamail';
    }

    public static function getSitemapAppIds()
    {
        $result = [];
        foreach (wa()->getApps() as $app) {
            if (empty($app['frontend']) || empty($app['themes'])) {
                continue;
            }
            if (!empty($app['routing_params']['private'])) {
                continue;
            }
            if ($app['id'] == 'feedback') {
                continue;
            }
            $result[] = $app['id'];
        }
        return $result;
    }

    public static function getAlternativeAppNames(string $app_id, string $def_app_name, $is_fullname = false)
    {
        $names = [
            'shop' => _w('Shop'),
            'photos' => _w('Photo gallery'),
            'hub' => [_w('Knowledge base'), _w('Knowledge base and forum')],
            'blog' => _w('Blog'),
            'helpdesk' => _w('Helpdesk'),
            'site' => _w('New page'),
        ];

        if (isset($names[$app_id])) {
            $name = $names[$app_id];

            if (is_array($name)) {
                if ($is_fullname) {
                    $name = $name[1];
                } else {
                    $name = $name[0];
                }
            }
        } else {
            $name = $def_app_name;
        }

        return $name;
    }

    public static function getAlternativeAppUrl(string $app_id)
    {
        $urls = [
            'photos' => 'photo-gallery',
            'hub' => 'knowledge-base',
            'site' => 'new-page',
        ];
        return ifset($urls, $app_id, $app_id);
    }

    public static function isBrokenAppRouteUrl($route)
    {
        $app = ifset($route, 'app', '');
        if (is_array($app)) {
            $app = $app['id'];
        }
        if (empty($route['url'])) {
            return $app === 'site' ? false : true;
        }
        if ($route['url'] == '*') {
            return false;
        }
        $trimmed_url = rtrim($route['url'], '/*').'/';
        if ($app === 'site') {
            return $route['url'] != $trimmed_url && $route['url'] != $trimmed_url.'*';
        } else {
            return $route['url'] != $trimmed_url.'*';
        }
    }

    public static function getBlocks()
    {
        $model = new siteBlockModel();
        $blocks = $model->order('sort')->fetchAll('id');

        $apps = wa()->getApps();
        foreach ($apps as $app_id => $app) {
            $site_config = self::getAppSiteConfig($app_id);
            if (!empty($site_config['blocks'])) {
                foreach ($site_config['blocks'] as $block_id => $block) {
                    if (!is_array($block)) {
                        $block = array('content' => $block, 'description' => '');
                    }
                    $block_id = $app_id.'.'.$block_id;
                    if (!isset($blocks[$block_id])) {
                        $block['id'] = $block_id;
                        $block['app'] = $app;
                        $blocks[$block_id] = $block;
                    }
                }
            }
        }

        foreach ($blocks as $block_id => $block) {
            if (empty($block['app'])) {
                if (($pos = strpos($block_id, '.')) !== false) {
                    $app_id = substr($block_id, 0, $pos);
                    if (isset($apps[$app_id])) {
                        $blocks[$block_id]['app_icon'] = $apps[$app_id]['icon'];
                    }
                }
            }
        }

        return $blocks;
    }

    public static function getAppSiteConfig($app_id)
    {
        $site_config = array();
        $path = wa()->getConfig()->getAppsPath($app_id, 'lib/config/site.php');
        if (file_exists($path)) {
            wa($app_id);
            $site_config = include($path);
            if (!is_array($site_config)) {
                $site_config = array();
            }
        }
        return $site_config;
    }

    public static function getPageModel(?string $app_id = null): ?waPageModel
    {
        if (waConfig::get('is_template')) {
            return null;
        }
        if (!$app_id) {
            $app_id = waRequest::request('app_id');
        }
        if (!$app_id || !wa()->appExists($app_id)) {
            return null;
        }

        wa($app_id);
        $page_model_class = $app_id.'PageModel';
        if (!class_exists($page_model_class)) {
            return null;
        }

        $result = new $page_model_class();
        if (!$result instanceof waPageModel) {
            return null;
        }

        return $result;
    }

    public static function saveLastDomainId()
    {
        $domain_id = waRequest::request('domain_id', null, 'int');
        if ($domain_id && $domain_id != wa()->getUser()->getSettings('site', 'last_domain_id')) {
            wa()->getUser()->setSettings('site', 'last_domain_id', $domain_id);
        }
    }

    public static function blockpageHasUrlOverlap($full_url, $parent_id = null)
    {
        $domain = self::getDomain();
        $domain_id = self::getDomainId();
        $routes = wa()->getRouting()->getRoutes($domain);
        foreach ($routes as $r) {
            if (isset($r['url']) && $full_url === rtrim($r['url'], '/*')) {
                return true;
            }
        }
        if (empty($parent_id)) {
            return false;
        }

        $apps = wa()->getApps();
        $app_ids = [];
        foreach ($apps as $app) {
            if (empty($app['frontend']) || empty($app['pages'])) {
                continue;
            }
            $app_ids[] = $app['id'];
        }

        $ids = [];
        $page_route = '*';
        $full_url = rtrim($full_url, '/') . '/';

        $uri_parts = explode('/', $full_url);
        if (count($uri_parts) > 1) {
            $page_route = $uri_parts[0] . '/*';

            unset($uri_parts[0]);
            $full_url = implode('/', $uri_parts);
        }

        foreach ($app_ids as $app_id) {
            $app_model = self::getPageModel($app_id);
            if (!$app_model) {
                continue;
            }

            $domain_field = $app_model->getDomainField();

            $ids += $app_model->select('id')
                ->where(
                    $domain_field.' = ? AND route = ? AND full_url = ?',
                    [
                        $domain_field === 'domain' ? $domain : $domain_id, // $$domain_field
                        $page_route,
                        $full_url
                    ]
                )
                ->fetchAll();
        }

        return $ids;
    }

    public static function getPreviewHash($app_id = 'site')
    {
        return wa('site')->getConfig()->getPreviewHash($app_id);
    }

    public static function getIncrementUrl($routes = null)
    {
        $name_start = 'new-page';
        $slug = $name_start;
        $m = new waModel();
        $urls = $m->query(
            "SELECT url FROM site_blockpage WHERE domain_id=:domain_id AND url LIKE :name_start
            UNION ALL
            SELECT url FROM site_page WHERE domain_id=:domain_id AND parent_id IS NULL AND url LIKE :name_start",
            ['domain_id' => self::getDomainId(), 'name_start' => $name_start.'%']
        )->fetchAll(null, true);

        if ($routes === null) {
            $routes = wa()->getRouting()->getRoutes(self::getDomain());
        }
        foreach ($routes as $r) {
            if (!isset($r['app']) || $r['app'] !== 'site') {
                continue;
            }
            $urls[] = rtrim($r['url'], '/*');
        }

        $max_index = -1;
        foreach ($urls as $url) {
            $m = [];
            $url = rtrim($url, '/');
            if ($url && preg_match("/^{$name_start}(-\d+)?$/", $url, $m)) {
                $i = intval(ltrim($m[1] ?? 0, '-'));
                if ($i > $max_index) {
                    $max_index = $i;
                }
            }
        }
        if ($max_index > -1) {
            $slug = $name_start.'-'.++$max_index;
        }

        return $slug;
    }

    public static function updateFaviconsConfig(&$domain_config, $without_save = false)
    {
        $domain = waSystem::getInstance()->getRouting()->getDomain();
        $data_folder = 'data/'.$domain;
        $path = wa()->getDataPath('/'.$data_folder.'/', true, 'site');
        $url = 'wa-data/public/site/'.$data_folder;

        $domain_config['favicons'] = [];
        if (file_exists($path.'favicon.ico')) {
            $domain_config['favicons']['favicon.ico'] = 'favicon.ico?v='.filemtime($path.'favicon.ico');
        }
        if (file_exists($path.'favicon-96.png')) {
            $domain_config['favicons']['favicon-96.png'] = $url.'/favicon-96.png?v='.filemtime($path.'favicon-96.png');
        }
        if (file_exists($path.'apple-touch-icon.png')) {
            $domain_config['favicons']['apple-touch-icon.png'] = 'apple-touch-icon.png?v='.filemtime($path.'apple-touch-icon.png');
        }
        if (file_exists($path.'site.webmanifest')) {
            $domain_config['favicons']['site.webmanifest'] = 'site.webmanifest?v='.filemtime($path.'site.webmanifest');
        }

        if (!$without_save) {
            $domain_config_path = waSystem::getInstance()->getConfig()->getConfigPath('domains/'.$domain.'.php', true, 'site');
            waUtils::varExportToFile($domain_config, $domain_config_path);
        }
    }
}
