<?php

class waPageAction extends waViewAction
{
    protected $model;

    public function execute()
    {
        $page = array();

        if ($id = waRequest::param('page_id')) {
            $page = $this->getPageModel()->get($id);
            if ($page && in_array($page['id'], waRequest::param('_exclude', array()))) {
                $page = array();
            }

        }
        if (!$page) {
            $this->getResponse()->setStatus(404);
            $this->getResponse()->setTitle('404. '._ws("Page not found"));

            $this->view->assign('error_code', 404);
            $this->view->assign('error_message', _ws("Page not found"));

            $this->setThemeTemplate('error.html');
        } else {
            $this->getResponse()->setTitle($page['title']);
            $this->view->assign('page', $page);

            try {
                $this->view->assign('wa_theme_url', $this->getThemeUrl());
                $page['content'] = $this->view->fetch('string:'.$page['content']);
            } catch (SmartyCompilerException $e) {
                $message = preg_replace('/"[a-z0-9]{32,}"/'," of content Site page with id {$page['id']}",$e->getMessage());
                throw new SmartyCompilerException($message, $e->getCode());
            }
            $this->layout->assign('page_id', $page['id']);
            $this->view->assign('page', $page);
            $this->setThemeTemplate('page.html');
        }
    }

    public function display($clear_assign = true)
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
}