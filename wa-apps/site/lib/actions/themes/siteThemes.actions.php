<?php
/**
 * Design themes tab for a single Site (domain) in UI 2.0
 */
class siteThemesActions extends waDesignActions
    {
        // protected $design_url = '?module=design&action=theme&domain_id=';
        protected $design_url = '#/themes/';
        protected $themes_url = '#/themes/';

        protected $options = array(
            'container' => true,
            'save_panel' => false,
            'js' => array(
                'ace' => false,
                'editor' => true,
                'storage' => false
            ),
            'is_ajax' => false
        );

        public function __construct()
        {
            if (!$this->getRights('design')) {
                throw new waRightsException("Access denied.");
            }
        }

        public function defaultAction() {
            $this->setLayout(new siteBackendThemesLayout());

            $this->setTemplate($this->getConfig()->getAppPath().'/templates/actions/themes/Themes.html');

            $this->display($this->getAppThemes() + [
                'routes'          => $this->getRoutes(),
                'template_path'   => $this->getConfig()->getRootPath().'/wa-system/design/templates/',
                'options'         => $this->options,
            ]);
        }

        public function themesInstalledAction()
        {
            $this->setTemplate($this->getConfig()->getAppPath().'/templates/actions/themes/Themes.installed.include.html');
            $this->display($this->getAppThemes());
        }

        protected function getAppThemes()
        {
            $app_id = $this->getAppId();
            $apps = wa()->getApps();
            $app = $apps[$app_id];

            $all_domains = wa()->getRouting()->getDomains();
            $domain_info = siteHelper::getDomainInfo();
            $domain_name = $domain_info['name'];
            $domain = $this->getNeedDomain($domain_info['id']);

            if (!empty($domain['is_alias'])){ //redirect for aliases
                $this->redirect(wa()->getAppUrl('site').'settings/?domain_id='.$domain_info['id']);
            }
            // themes used on current domain, theme_id => route_id
            $used_domain_themes = [];

            // themes used on any domain, theme_id => true
            $used_app_themes = [];

            $apps_with_theme = [];
            foreach ($apps as $_app_id => $_app) {
                if (!empty($_app['themes'])) {
                    $apps_with_theme[$_app_id] = 1;
                }
            }
            foreach($all_domains as $d) {
                foreach(wa()->getRouting()->getRoutes($d) as $route_id => $route) {
                    if (is_array($route) && isset($route['app'])) {
                        foreach(['theme', 'theme_mobile'] as $k) {
                            if (isset($apps_with_theme[$route['app']]) && ifset($route, $k, '') === '') {
                                $route[$k] = 'default';
                            }
                            if (isset($route[$k])) {
                                $used_app_themes[$route[$k]] = true;
                                if ($d == $domain_name) {
                                    $used_domain_themes[$route[$k]] = $route_id;
                                }
                            }
                        }
                    }
                }
            }

            $blockpages = (new siteBlockpageModel())
                ->select('theme')
                ->where('domain_id = ?', $domain_info['id'])
                ->fetchAll();
            foreach ($blockpages as $page) {
                $used_domain_themes[$page['theme']] = 0;
                $used_app_themes[$page['theme']] = true;
            }

            $app_themes = wa()->getThemes('site', true);
            usort($app_themes, function($a, $b) use ($used_domain_themes, $used_app_themes) {
                $a_used_domain = isset($used_domain_themes[$a->id]);
                $b_used_domain = isset($used_domain_themes[$b->id]);
                $a_used_app = isset($used_app_themes[$a->id]);
                $b_used_app = isset($used_app_themes[$b->id]);

                if ($a_used_domain && !$b_used_domain) {
                    return -1;
                } elseif (!$a_used_domain && $b_used_domain) {
                    return 1;
                } elseif ($a_used_app && !$b_used_app) {
                    return -1;
                } elseif (!$a_used_app && $b_used_app) {
                    return 1;
                } else {
                    return min(1, max(-1, $a->mtime - $b->mtime));
                }
            });

            return [
                'themes_url'      => $this->themes_url,
                'design_url'      => $this->design_url,
                'domains'         => $all_domains,
                'app_id'          => $app_id,
                'app'             => $app,
                'app_themes'      => $app_themes,
                'used_app_themes' => $used_app_themes,
                'domain_id'       => $domain_info['id'],
                'domain'          => $domain_name,
                'used_domain_themes'  => $used_domain_themes,
            ];
        }

        protected function getNeedDomain(int $domain_id)
        {
            $domains = siteHelper::getDomains(true);
            if (!$domain_id || empty($domains[$domain_id])) {
                throw new waException('Domain not found', 404);
            }
            return $domains[$domain_id] + ['id' => $domain_id];
        }
}
