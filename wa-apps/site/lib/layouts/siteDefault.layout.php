<?php

class siteDefaultLayout extends waLayout
{
    protected $domain_id;

    public function execute()
    {
        $this->domain_id = siteHelper::getDomainId();

        $this->view->assign('apps', siteHelper::getApps());
        $this->view->assign('domain_id', $this->domain_id);
        $this->view->assign('domain_url', siteHelper::getDomain());
        $this->view->assign('domain_alias', wa()->getRouting()->isAlias(siteHelper::getDomain()));
        $this->view->assign('domain_protocol', $this->getProtocol());
        $this->view->assign('domains', siteHelper::getDomains(true));
        $this->view->assign('pages', $this->getPages());
        $this->view->assign('domain_root_url', siteHelper::getDomainUrl());
        $this->view->assign('routing_errors', wa()->getConfig()->getRoutingErrors());
        $this->view->assign('rights', array(
            'admin'  => $this->getUser()->isAdmin('site'),
            'files'  => $this->getRights('files'),
            'themes' => $this->getRights('themes'),
            'blocks' => $this->getRights('blocks'),
        ));
        $this->view->assign('mirror', $this->isMirror());

        /**
         * Extend backend sidebar
         * Add extra sidebar items (menu items, system output)
         * @event backend_sidebar
         * @example #event handler example
         * public function sidebarAction()
         * {
         *     $output = array();
         *
         *     #add external link into sidebar menu
         *     $output['menu_li']='<li>
         *         <a href="http://www.webasyst.com">
         *             http://www.webasyst.com
         *         </a>
         *     </li>';
         *
         *     #add system link into sidebar menu
         *     $output['system_li']='<li>
         *         <a href="http://www.webasyst.com">
         *             http://www.webasyst.com
         *         </a>
         *     </li>';
         *
         *     return $output;
         * }
         * @return array[string][string]string $return[%plugin_id%]['menu_li'] Single menu items
         * @return array[string][string]string $return[%plugin_id%]['system_li'] Extra menu items
         */
        $this->view->assign('backend_sidebar', wa()->event('backend_sidebar'));
    }

    protected function getPages()
    {
        $domain = siteHelper::getDomain();
        $routing = wa()->getRouting();
        $routes = $routing->getRoutes($domain);

        $page_model = new sitePageModel();
        $pages = $page_model->select('id,name,url,status')->where('domain_id = '.$this->domain_id)->order('sort')->fetchAll('id');

        $pages_urls = array();
        foreach ($pages as $page_id => $p) {
            $pages_urls[$page_id] = $p['url'];
            $pages[$page_id]['url'] = null;
        }

        foreach ($routes as $r_id => $r) {
            if (isset($r['app']) && $r['app'] == 'site' && (strpos($r['url'], '<url') === false)) {
                $u = $routing->getUrlByRoute($r);
                if (!isset($r['_exclude']) || !$r['_exclude']) {
                    foreach ($pages_urls as $p_id => $p_url) {
                        $pages[$p_id]['url'] = $u.$p_url;
                        unset($pages_urls[$p_id]);
                    }
                } else {
                    foreach ($pages_urls as $p_id => $p_url) {
                        if (!in_array($p_id, $r['_exclude'])) {
                            $pages[$p_id]['url'] = $u.$p_url;
                            unset($pages_urls[$p_id]);
                        }
                    }
                }
            }
        }

        return $pages;
    }

    /**
     * Get the protocol to create a link to files
     * @return string
     */
    protected function getProtocol()
    {
        $domain_config_path = $this->getConfig()->getConfigPath('domains/'.siteHelper::getDomain().'.php');
        if (file_exists($domain_config_path)) {
            $orig_domain_config = include($domain_config_path);
        }
        $domains = siteHelper::getDomains();
        $domain_id = waRequest::get('domain_id', null, waRequest::TYPE_INT);
        if (!empty($orig_domain_config['ssl_all'])) {
            $protocol = 'https://';
        } elseif ((!$domain_id || $domains[$domain_id] == wa()->getConfig()->getDomain()) && waRequest::isHttps()) { //first site in list or current site
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }
        return $protocol;
    }

    /**
     * @return bool
     */
    protected function isMirror()
    {
        $path = $this->getConfig()->getPath('config', 'routing');
        $routes = array();

        if (file_exists($path)) {
            $routes = include($path);
        }

        $domain_model = new siteDomainModel();
        $domain = $domain_model->getById($this->domain_id);

        if (!isset($routes[$domain['name']]) || is_array($routes[$domain['name']])) {
            return false;
        }

        return true;
    }

}