<?php

class waAppViewHelper
{
    /**
     * @deprecated use $this->wa() instead
     * @var waSystem
     */
    protected $wa;
    protected $app_id = null;
    protected $cdn = '';

    public function __construct($system)
    {
        $this->wa = $system;
        if ($system && $system->getConfig() && method_exists($system->getConfig(), 'getApplication')) {
            $this->app_id = $system->getConfig()->getApplication();
        }

        if (wa()->getEnv() == 'frontend') {
            $domain = wa()->getRouting()->getDomain(null, true);
            $domain_config_path = wa()->getConfig()->getConfigPath('domains/' . $domain . '.php', true, 'site');
            if (file_exists($domain_config_path)) {
                $domain_config = include($domain_config_path);
                if (!empty($domain_config['cdn'])) {
                    $this->cdn = rtrim($domain_config['cdn'], '/');
                }
            }
        }
    }

    /**
     * @param string $theme_id
     * @return string
     */
    public function themePath($theme_id)
    {
        $theme = new waTheme($theme_id, $this->app_id);
        return $theme->path ? $theme->path.'/' : null;
    }

    /**
     * @param string $theme_id
     * @return string
     */
    public function themeUrl($theme_id)
    {
        $theme = new waTheme($theme_id, $this->app_id);
        return $theme->path ? $theme->getUrl() : null;
    }

    public function pages($parent_id = 0, $with_params = true)
    {
        if (is_bool($parent_id)) {
            $with_params = $parent_id;
            $parent_id = 0;
        }
        try {
            $page_model = $this->getPageModel();
            $domain = wa()->getRouting()->getDomain(null, true);
            if ($this->app_id == wa()->getRouting()->getRoute('app')) {
                $route = wa()->getRouting()->getRoute('url');
                $url = wa($this->app_id)->getAppUrl(null, true);
            } else {
                $routes = wa()->getRouting()->getByApp($this->app_id, $domain);
                if ($routes) {
                    $route = end($routes);
                    $route = $route['url'];
                    $url = wa()->getRootUrl(false, true).waRouting::clearUrl($route);
                } else {
                    return array();
                }
            }
            $pages = null;
            $cache_key = $domain.'/'.waRouting::clearUrl($route);
            if ($cache = wa($this->app_id)->getCache()) {
                $pages = $cache->get($cache_key, 'pages');
            }
            if ($pages === null) {
                $pages = $page_model->getPublishedPages($domain, $route);
                if ($with_params && $pages) {
                    $page_params_model = $page_model->getParamsModel();
                    $data = $page_params_model->getByField('page_id', array_keys($pages), true);
                    foreach ($data as $row) {
                        if (isset($pages[$row['page_id']])) {
                            $pages[$row['page_id']][$row['name']] = $row['value'];
                        }
                    }
                }

                foreach ($pages as &$page) {
                    $page['url'] = $url . $page['full_url'];
                    if (!isset($page['title']) || !$page['title']) {
                        $page['title'] = $page['name'];
                    }
                    foreach ($page as $k => $v) {
                        if ($k != 'content') {
                            $page[$k] = htmlspecialchars($v);
                        }
                    }
                }
                unset($page);
                foreach ($pages as $page_id => $page) {
                    if ($page['parent_id'] && isset($pages[$page['parent_id']])) {
                        $pages[$page['parent_id']]['childs'][] = &$pages[$page_id];
                    }
                }
                if ($cache) {
                    $cache->set($cache_key, $pages, 3600, 'pages');
                }
            }
            if ($parent_id) {
                return isset($pages[$parent_id]['childs']) ? $pages[$parent_id]['childs'] : array();
            }
            foreach ($pages as $page_id => $page) {
                if ($page['parent_id']) {
                    unset($pages[$page_id]);
                }
            }
            return $pages;
        } catch (Exception $e) {
            return array();
        }
    }

    public function page($id)
    {
        $page_model = $this->getPageModel();
        $page = $page_model->getById($id);
        $page['content'] = wa($this->app_id)->getView()->fetch('string:'.$page['content']);

        $page_params_model = $page_model->getParamsModel();
        $page += $page_params_model->getById($id);

        return $page;
    }


    /**
     * @return waPageModel
     */
    protected function getPageModel()
    {
        $class = $this->app_id.'PageModel';
        return new $class();
    }

    public function config($name)
    {
        return wa($this->app_id)->getConfig()->getOption($name);
    }

    protected function wa()
    {
        return wa($this->app_id);
    }
}