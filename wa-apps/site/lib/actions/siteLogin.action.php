<?php

class siteLoginAction extends waLoginAction
{

    public function execute()
    {
        $this->setLayout(new siteFrontendLayout());
        $this->setThemeTemplate('login.html');
        try {
            parent::execute();
        } catch (waException $e) {
            if ($e->getCode() == 404) {
                $this->view->assign('error_code', $e->getCode());
                $this->view->assign('error_message', $e->getMessage());
                $this->setThemeTemplate('error.html');
            } else {
                throw $e;
            }
        }
    }

    protected function afterAuth()
    {
        if (waRequest::get('return')) {
            $url = $this->getStorage()->get('auth_referer');
            if ($url) {
                $this->getStorage()->del('auth_referer');
                $this->redirect($url);
            }
        }
        $this->getStorage()->del('auth_referer');
        $url = waRequest::param('secure') ? $this->getConfig()->getCurrentUrl() : wa()->getRouteUrl('/frontend/my');
        $this->redirect($url);
    }

}