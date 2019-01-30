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

    public function __construct()
    {
        if (!$this->getRights('design')) {
            throw new waRightsException("Access denied");
        }
    }

    protected function getRoutes($all = false)
    {
        if ($all) {
            return parent::getRoutes();
        }
        $routes = wa()->getRouting()->getByApp($this->getAppId());
        $result = array();
        $domain = siteHelper::getDomain();
        if (isset($routes[$domain])) {
            foreach (array_reverse($routes[$domain], true) as $route_id => $route) {
                $route['_id'] = $route_id;
                $route['_domain'] = $domain;
                $route['_url'] = waRouting::getUrlByRoute($route, $domain);
                $route['_url_title'] = $domain.'/'.waRouting::clearUrl($route['url']);
                $result[] = $route;
            }
        }
        return $result;
    }

}
