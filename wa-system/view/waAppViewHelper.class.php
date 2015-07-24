<?php

class waAppViewHelper
{
    /**
     * @var waSystem
     */
    protected $wa;
    protected $cdn = '';

    public function __construct($system)
    {
        $this->wa = $system;

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
        $app_id = $this->wa->getConfig()->getApplication();
        $theme = new waTheme($theme_id, $app_id);
        return $theme->path ? $theme->path.'/' : null;
    }

    /**
     * @param string $theme_id
     * @return string
     */
    public function themeUrl($theme_id)
    {
        $app_id = $this->wa->getConfig()->getApplication();
        $theme = new waTheme($theme_id, $app_id);
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
            if ($this->wa->getConfig()->getApplication() == wa()->getRouting()->getRoute('app')) {
                $route = wa()->getRouting()->getRoute('url');
                $url = $this->wa->getAppUrl(null, true);
            } else {
                $routes = wa()->getRouting()->getByApp($this->wa->getConfig()->getApplication(), $domain);
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
            if ($cache = $this->wa->getCache()) {
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
        $page['content'] = $this->wa->getView()->fetch('string:'.$page['content']);

        $page_params_model = $page_model->getParamsModel();
        $page += $page_params_model->getById($id);

        return $page;
    }


    /**
     * @return waPageModel
     */
    protected function getPageModel()
    {
        $class = $this->wa->getConfig()->getApplication().'PageModel';
        return new $class();
    }

    public function config($name)
    {
        return $this->wa->getConfig()->getOption($name);
    }

}