<?php
/**
 * Design from a list of themes
 */
class siteThemeActions extends waDesignActions
{
    protected $design_url = '#/themes/';
    protected $options = array(
        'container' => false,
        'save_panel' => true,
        'js' => array(
            'ace' => true,
            'editor' => true,
            'storage' => false
        ),
        'is_ajax' => true
    );

    public function __construct()
    {
        if (!$this->getRights('design')) {
            throw new waRightsException("Access denied.");
        }
    }

    public function defaultAction()
    {
        $app_id = $this->getAppId();
        $app = wa()->getAppInfo($app_id);

        $themes = wa()->getThemes($app_id, true);
        $routes = $this->getRoutes();

        $themes_routes = $this->getThemesRoutes($themes, $routes);

        $t_id = waRequest::get('theme');
        $route = array();
        if ($t_id) {
            foreach ($themes_routes as $r) {
                if (is_array($r) && $r['theme'] == $t_id) {
                    $route = $r;
                    break;
                }
            }
        } elseif ($themes_routes && is_array($themes_routes[0])) {
            $route = $themes_routes[0];
            $t_id = ifset($themes_routes[0]['theme'], 'default');
        } else {
            $t_id = 'default';
            if (empty($themes[$t_id])) {
                reset($themes);
                $t_id = key($themes);
            }
        }

        if (empty($themes[$t_id])) {
            throw new waException('Design theme not found.', 404);
        }
        $theme = $themes[$t_id];

        $routing_url = wa()->getAppUrl('site').'#/routing/';
        $current_url = $this->design_url.'theme='.$theme['id'];
        if ($route) {
            $current_url .= '&domain='.urlencode($route['_domain']).'&route='.$route['_id'];
        }

        $this->setTemplate('wa-apps/site/templates/actions/themes/Design.html');

        $this->display([
            'current_url'             => $current_url,
            'design_url'              => $this->design_url,
            'themes_url'              => $this->themes_url,
            'theme'                   => $theme,
            'route'                   => $route,
            'themes'                  => $themes,
            'themes_routes'           => $themes_routes, // TODO: cut
            'app_id'                  => $app_id,
            'app'                     => $app,
            'routing_url'             => $routing_url,
            'options'                 => $this->options,
            'edit_data'               => $this->getThemesEditData(['theme' => $t_id]),
            'module_name'             => 'theme',
        ]);
    }

    public function themeAction()
    {
        // Show 'Start using this theme' button unless theme is used on currently active domain
        $domain_id = waRequest::request('domain_id', null, waRequest::TYPE_INT);
        if ($domain_id) {
            $domain = ifset(ref(siteHelper::getDomains(true)), $domain_id, null);
        }
        if (!empty($domain)) {
            $routes = wa()->getRouting()->getRoutes($domain['name']);
            $theme_id = waRequest::request('theme');
            $routes = array_filter($routes, function($r) use ($theme_id) {
                return $theme_id === ifset($r, 'theme', '');
            });

            $show_theme_start_using = empty($routes);
            if ($show_theme_start_using) {
                $fallback_has_theme_usage = (bool)(new siteBlockpageModel())->select('id')->where(
                    'domain_id = ? AND theme = ?', [$domain_id, $theme_id]
                )->fetchAll();
                $this->getView()->assign('fallback_has_theme_usage', $fallback_has_theme_usage);
            }

            $this->getView()->assign([
                'show_theme_start_using' => $show_theme_start_using,
            ]);
        }

        parent::themeAction();
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

    protected function getThemesEditData(array $get = []) {
        try {
            return parent::getThemesEditData($get);
        } catch (waException $e) {
            // this is a trial theme, not editable
            $app_id = $this->getAppId();
            $app = wa()->getAppInfo($app_id);
            $theme_id = ifset($get, 'theme', '');
            $theme = new waTheme($theme_id, $app_id);
            $data = [
                'options'              => [],
                'app_id'               => $app_id,
                'design_url'           => $this->design_url,
                'app'                  => $app,
                'file'                 => null,
                'theme_id'             => $theme_id,
                'theme'                => null,
                'theme_usages'         => [],
                'theme_usages_decoded' => [],
                'route_url'            => null,
                'route_url_decoded'    => null,
                'theme_files'          => [],
            ];

            if ($theme->parent_theme_id) {
                $data['parent_theme'] = $theme->parent_theme;
            }
            return $data;
        }
    }

    public function editFilesAction()
    {
        $get = waRequest::get();

        $data = $this->getThemesEditData($get);
        $this->setTemplate('wa-apps/site/templates/actions/themes/DesignEditFiles.html', true);
        $this->display($data);
    }
}
