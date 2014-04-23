<?php

class siteDesignActions extends waDesignActions
{
    protected $design_url = '#/design/';
    protected $themes_url = '#/themes/';

    protected $options = array(
        'container' => false,
        'save_panel' => false,
        'js' => array(
            'ace' => false,
            'editor' => true,
            'storage' => false
        ),
        'is_ajax' => true
    );

    protected function getRoutes()
    {
        $routes = wa()->getRouting()->getByApp($this->getAppId());
        $result = array();
        $domain = siteHelper::getDomain();
        if (isset($routes[$domain])) {
            foreach ($routes[$domain] as $route_id => $route) {
                $route['_id'] = $route_id;
                $route['_domain'] = $domain;
                $route['_url'] = waRouting::getUrlByRoute($route, $domain);
                $route['_url_title'] = $domain.'/'.waRouting::clearUrl($route['url']);
                $result[] = $route;
            }
        }
        return $result;
    }

    protected function getThemesRoutes(&$themes, $routes)
    {
        $routes = parent::getRoutes();
        return parent::getThemesRoutes($themes, $routes);
    }

    protected function sortThemes($themes, $themes_routes)
    {
        $domain = 'http://'.siteHelper::getDomain();
        $n = strlen($domain);
        // sort themes
        $result = array();

        foreach ($themes_routes as $theme_id => $r) {
            $add = false;
            foreach ($r as $r_url => $r_title) {
                if (!strncasecmp($r_url, $domain, $n)) {
                    $add = true;
                    break;
                }
            }
            if ($add) {
                $result[$theme_id] = $themes[$theme_id];
            }
        }

        foreach ($themes_routes as $theme_id => $r) {
            if (!isset($result[$theme_id])) {
                $result[$theme_id] = $themes[$theme_id];
            }
        }

        foreach ($themes as $theme_id => $theme) {
            if (!isset($temp_themes[$theme_id])) {
                $result[$theme_id] = $theme;
            }
        }

        return $result;
    }

}
