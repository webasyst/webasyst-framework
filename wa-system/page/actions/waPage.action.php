<?php

class waPageAction extends waViewAction
{
    protected $model;

    public function execute()
    {
        $page = array();

        if ($id = waRequest::param('page_id')) {
            $page = $this->getPageModel()->get($id);
            foreach ($page as $k => $v) {
                if ($k != 'content' && $k != 'title') {
                    $page[$k] = htmlspecialchars($v);
                }
            }
        }
        if (!$page) {
            $this->getResponse()->setStatus(404);
            $this->getResponse()->setTitle('404. '._ws("Page not found"));

            $this->view->assign('error_code', 404);
            $this->view->assign('error_message', _ws("Page not found"));

            $this->setThemeTemplate('error.html');
        } else {
            $this->setLastModified($page);

            $breadcrumbs = array();
            $parents = array();
            $p = $page;
            $root_url = wa()->getAppUrl(null, true);
            $root_page_id = $p['id'];
            while ($p['parent_id']) {
                $p = $this->getPageModel()->select('id, parent_id, name, title, url, full_url')->where("id = ?", $p['parent_id'])->fetch();
                $parents[] = $p;
                $breadcrumbs[] = array(
                    'name' => $p['name'],
                    'url' => $root_url.$p['full_url']
                );
                $root_page_id = $p['id'];
            }

            $this->view->assign('root_page_id', $root_page_id);
            if ($this->layout) {
                $this->layout->assign('root_page_id', $root_page_id);
            }

            $this->view->assign('page_parents', array_reverse($parents));
            if ($this->layout && $breadcrumbs) {
                $this->layout->assign('breadcrumbs', array_reverse($breadcrumbs));
            }

            $this->getResponse()->setTitle($page['title']);
            $this->getResponse()->setMeta(array(
                'keywords' => isset($page['keywords']) ? $page['keywords'] : '',
                'description' => isset($page['description']) ? $page['description'] : ''
            ));

            // Open Graph
            $og = false;
            foreach (array('title', 'image', 'video', 'description', 'type') as $k) {
                if (!empty($page['og_'.$k])) {
                    $og = true;
                    $this->getResponse()->setOGMeta('og:'.$k, $page['og_'.$k]);
                }
            }
            if (!$og) {
                $this->getResponse()->setOGMeta('og:title', $page['title']);
                if (!empty($page['description'])) {
                    $this->getResponse()->setOGMeta('og:description', $page['description']);
                }
            }

            $this->view->assign('page', $page);
            $this->view->assign('wa_theme_url', $this->getThemeUrl());
            $page['content'] = $this->renderPage($page);

            if ($this->layout) {
                $this->layout->assign('page_id', $page['id']);
            }
            $this->view->assign('page', $page);
            $this->setThemeTemplate('page.html');

            $this->addCanonicalUrl($page['full_url']);
        }
    }

    /**
     * @since 1.14.7
     */
    protected function setLastModified($page)
    {
        if (empty($page['update_datetime'])) {
            return;
        }

        $has_dynamic_content = false;
        if (empty($page['last_modified_ignore_dynamic_content'])) {
            $has_dynamic_content = preg_match('/\{\S/', $page['content']);
        }
        if ($has_dynamic_content) {
            $this->getResponse()->setLastModified(date("Y-m-d H:i:s"));
        } else {
            $this->getResponse()->setLastModified($page['update_datetime']);
        }
    }

    public function renderPage($page)
    {
        try {
            $result = $this->view->fetch('string:'.$page['content']);
            if ($result && false !== strpos($result, 'text-')) {
                // @deprecated
                $result = '<style>.text-center{text-align:center;}.text-right{text-align:right}.text-justify{text-align:justify;}</style>'.$result;
            }
            return $result;
        } catch (SmartyCompilerException $e) {
            $message = preg_replace('/"[a-z0-9]{32,}"/'," of content Site page with id {$page['id']}", $e->getMessage());
            throw new SmartyCompilerException($message, $e->getCode());
        }
    }

    public function display($clear_assign = false)
    {
        if (waSystemConfig::isDebug()) {
            return parent::display($clear_assign);
        }
        try {
            return parent::display($clear_assign);
        } catch (SmartyCompilerException $e) {
            $message = preg_replace('/(on\sline\s[0-9]+).*$/i', '$1', $e->getMessage());
            $message = str_replace($this->getConfig()->getRootPath(), '', $message);
            throw new SmartyCompilerException($message, $e->getCode());
        }
    }

    /**
     * @return waPageModel
     */
    protected function getPageModel()
    {
        if (!$this->model) {
            $this->model = $this->getAppId().'PageModel';
        }
        return new $this->model();
    }

    public function addCanonicalUrl($full_url)
    {
        $storefront_url = wa()->getRouteUrl('/frontend/', true);
        $canonical_url = $storefront_url . $full_url;
        $this->getResponse()->setCanonical($canonical_url);
    }
}