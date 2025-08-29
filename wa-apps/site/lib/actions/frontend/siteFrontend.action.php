<?php

class siteFrontendAction extends waPageAction
{
    public function execute()
    {
        $page = $this->params;
        if ($page && is_array($page)) {
            $this->setLastModified($page);

            $params = waRequest::param();
            foreach ($params as $k => $v) {
                if (in_array($k, array('url', 'module', 'action'))) {
                    unset($params[$k]);
                }
            }
            $this->view->getHelper()->globals($params);
            $this->view->assign('frontend_page', wa('site')->event('frontend_page', $page, array('before_content', 'after_content')));
            $this->view->assign('page', $page);
            $this->view->assign('wa_theme_url', $this->getThemeUrl());
            $page['content'] = $this->renderPage($page);
            $this->view->assign('page', $page);

            // set response
            if (!$this->getResponse()->getTitle() && isset($page['title'])) {
                $this->getResponse()->setTitle($page['title']);
            }
            $this->getResponse()->setMeta(array(
                'keywords' => isset($page['keywords']) ? $page['keywords'] : '',
                'description' => isset($page['description']) ? $page['description'] : ''
            ));
            
            if (ifset($page['params'], 'og_active', false)) {
                foreach (ifset($page['params'], array()) as $property => $content) {
                    if ($content && $property !== 'og_active') {
                        substr($property, 0, 3) == 'og_' && wa()->getResponse()->setOGMeta('og:'.substr($property, 3), $content);
                    }
                }
            }

            $this->view->assign('breadcrumbs', $this->getBreadcrumbs($page));
            $this->setThemeTemplate('page.html');
        } else {

            $error_message = '';

            // show exception
            if ($this->params instanceof Exception) {
                $e = $this->params;
                $code = $e->getCode();
                $error_message = $e->getMessage();
            } else {
                $code = 404;
            }

            if ($code < 600 && $code >= 400) {
                $this->getResponse()->setStatus($code);
                if ($code == 404) {
                    if ($this->getConfig()->getCurrentUrl() == wa()->getAppUrl(null, true)
                        && (empty($page['id']) && empty($page['content']))
                    ) {
                        $this->getResponse()->setTitle(_w("Homepage"));
                    } else {
                        $this->getResponse()->setTitle('404. ' . _ws("Page not found"));
                        $error_message = _ws("Page not found");
                    }
                }
            } else {
                $this->getResponse()->setStatus(500);
            }

            $this->view->assign('error_message', $error_message);
            $this->view->assign('error_code', $code);
            $this->setThemeTemplate('error.html');
            $this->view->assign('page', array());
        }
    }

    public function getBreadcrumbs($page)
    {
        $page_model = new sitePageModel();
        $breadcrumbs = array();
        $root_url = wa()->getAppUrl(null, true);
        $root_page_id = ifset($page, 'id', null);
        if (isset($page['parent_id'])) {
            while ($page['parent_id']) {
                $page = $page_model->getById($page['parent_id']);
                $breadcrumbs[] = array(
                    'url' => $root_url . $page['full_url'],
                    'name' => $page['name'] ? $page['name'] : $page['title']
                );
                $root_page_id = $page['id'];
            }
        }
        $this->view->assign('root_page_id', $root_page_id);
        return array_reverse($breadcrumbs);
    }

    public function display($clear_assign = true)
    {
        return parent::display(false);
    }

}
