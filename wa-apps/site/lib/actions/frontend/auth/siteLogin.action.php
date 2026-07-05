<?php

class siteLoginAction extends waLoginAction
{

    public function execute()
    {
        $this->setLayout(new siteFrontendLayout());
        $this->setThemeTemplate('login.html');
        try {
            parent::execute();
            $this->saveReferer();
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

    protected function redirectAfterAuth()
    {
        if (waRequest::get('return') || $this->isJsonMode()) {
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
