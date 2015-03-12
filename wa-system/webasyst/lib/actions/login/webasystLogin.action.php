<?php

class webasystLoginAction extends waLoginAction
{

    public function execute()
    {
        $this->view->assign('title', $this->getTitle());
        $this->view->assign('title_style', $this->getTitleStyle());

        $this->view->setOptions(array('left_delimiter' => '{', 'right_delimiter' => '}'));
        if ($this->template === null) {
            if (waRequest::isMobile()) {
                $this->setLayout(null);
                $this->template = 'LoginMobile.html';
            } else {
                $this->template = 'Login.html';
            }
            $template_file = wa()->getDataPath('templates/'.$this->template, false, 'webasyst');
            if (file_exists($template_file)) {
                $this->template = 'file:'.$template_file;
            } else {
                $this->template = wa()->getAppPath('templates/actions/login/', 'webasyst') . $this->template;
            }
        }
        $this->view->assign('login', waRequest::post('login', $this->getStorage()->read('auth_login')));
        parent::execute();
        if ($this->layout) {
            $this->layout->assign('error', $this->view->getVars('error'));
        }

        $ref = waRequest::server('HTTP_REFERER');
        if(waRequest::get('back_to') && $ref) {
            $this->getStorage()->write('login_back_on_cancel', $ref);
        } else if (!$ref) {
            $this->getStorage()->remove('login_back_on_cancel');
        }
        $this->view->assign('back_on_cancel', wa()->getStorage()->read('login_back_on_cancel'));
    }

    protected function saveReferer()
    {

    }

    protected function checkAuthConfig()
    {

    }

    protected function afterAuth()
    {
        $this->getStorage()->remove('auth_login');
        $redirect = $this->getConfig()->getCurrentUrl();
        $backend_url = $this->getConfig()->getBackendUrl(true);
        if (!$redirect || $redirect === $backend_url) {
            $redirect = $this->getUser()->getLastPage();
        }
        if (!$redirect || substr($redirect, 0, strlen($backend_url) + 1) == $backend_url.'?') {
            $redirect = $backend_url;
        }
        
        wa()->getUser()->setSettings('webasyst', 'backend_url', $this->getConfig()->getHostUrl() . $backend_url);
        
        $this->redirect(array('url' => $redirect));
    }

    public function getTitle()
    {
        if ( ( $title = $this->getConfig()->getOption('login_form_title'))) {
            return waLocale::fromArray($title);
        }
        return wa()->getSetting('name', 'Webasyst', 'webasyst');
    }

    public function getTitleStyle()
    {
        return $this->getConfig()->getOption('login_form_title_style');
    }
}

// EOF