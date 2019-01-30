<?php

class siteFrontendController extends waViewController
{
    public function execute()
    {
        if (!waRequest::isXMLHttpRequest()) {
            $this->setLayout(new siteFrontendLayout());
        }

        try {
            $this->executeAction(new siteFrontendAction($this->getPage()));
        } catch (Exception $e) {
            if (waSystemConfig::isDebug()) {
                echo $e;
            } else {
                waSystem::setActive('site');
                $this->executeAction(new siteFrontendAction($e));
            }
        }
    }

    protected function getPage()
    {
        // Attempt to get page data via plugin hook.
        $page = array();
        $domain = wa()->getRouting()->getDomain();
        $route = wa()->getRouting()->getRoute();
        $url = waRequest::param('url', '', 'string');
        wa('site')->event('frontend_page_before', ref(array(
            'domain' => $domain,
            'route' => $route,
            'url' => $url,
            'page' => &$page,
        )));
        if (!is_array($page) || !$page) {
            $page = array();
        } else {
            $page += wao(new sitePageModel())->getEmptyRow();
            $page += array(
                'title' => $page['name'],
                'params' => array(),
            );
            $page += (array)$page['params'];
        }

        // No idea what this param is used for. Legacy code.
        if (waRequest::param('error')) {
            return $page;
        }

        // Load page by route from DB
        if (empty($page)) {
            // Drafts are only visible if certain GET parameter is present
            $is_draft_visible = false;
            $preview_hash = waRequest::get('preview', null, 'string');
            if ($preview_hash) {
                $app_settings_model = new waAppSettingsModel();
                $preview_secret = $app_settings_model->get('site', 'preview_hash');
                $is_draft_visible = $preview_secret && $preview_hash == md5($preview_secret);
            }

            // Route request to a page
            $domain_id = $this->getDomainId($domain);
            $page = $this->getPageByUrl($domain_id, ifset($route, 'url', ''), $url, $is_draft_visible);

            // Redirect to canonical URL if page with `/` at the end exists.
            if (empty($page) && $url && substr($url, -1) !== '/') {
                $page = $this->getPageByUrl($domain_id, ifset($route, 'url', ''), $url.'/', $is_draft_visible, false);
                if ($page) {
                    $url = wa()->getConfig()->getRequestUrl(false);
                    if (strpos($url, '?') === false) {
                        $url .= '/';
                    } else {
                        $url = join('/?', explode('?', $url, 2));
                    }
                    wa()->getResponse()->redirect($url);
                }
            }
        }

        // Plugin hook to modify page before show
        wa('site')->event('frontend_page', ref(array(
            'domain' => $domain,
            'route' => $route,
            'url' => $url,
            'page' => &$page,
        )));

        return $page;
    }

    protected function getPageByUrl($domain_id, $route, $url, $is_draft_visible, $load_params = true)
    {
        $page_model = new sitePageModel();
        $page = $page_model->getByUrl($domain_id, $route, $url);

        if (!$page) {
            return array();
        }
        if (!$page['status'] && !$is_draft_visible) {
            return array();
        }
        if (!$load_params) {
            return $page;
        }

        $params_model = new sitePageParamsModel();
        $params = $params_model->getById($page['id']);
        $page['params'] = $params;
        $page += $params;

        if (empty($page['title'])) {
            $page['title'] = $page['name'];
        }

        foreach ($page as $k => $v) {
            if ($k != 'content' && is_string($v)) {
                $page[$k] = htmlspecialchars($v);
            }
        }

        return $page;
    }

    protected function getDomainId($domain)
    {
        $domain_model = new siteDomainModel();
        if ( ( $d = $domain_model->getByName($domain))) {
            return $d['id'];
        }

        if (substr($domain, 0, 4) == 'www.') {
            $domain = substr($domain, 4);
        } else {
            $domain = 'www.'.$domain;
        }
        if ( ( $d = $domain_model->getByName($domain))) {
            return $d['id'];
        }

        return null;
    }
}