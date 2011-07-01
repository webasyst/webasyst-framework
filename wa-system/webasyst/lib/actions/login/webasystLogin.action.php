<?php

class webasystLoginAction extends waViewAction
{
    public function execute()
    {

        $app_settings_model = new waAppSettingsModel();
        $this->view->assign('name', $app_settings_model->get('webasyst', 'name', 'Webasyst'));
        $background = $app_settings_model->get('webasyst', 'auth_form_background');
        $stretch = $app_settings_model->get('webasyst', 'auth_form_background_stretch');
        if ($background) {
            $background = 'wa-data/public/webasyst/'.$background;
        }
        $this->view->assign('stretch', $stretch);
        $this->view->assign('background', $background);

        $this->view->assign('remember_enabled', $app_settings_model->get('webasyst', 'rememberme', 1));

        $auth = waSystem::getInstance()->getAuth();
        try {
        	if ($auth->auth()) {
            	$redirect = $this->getConfig()->getCurrentUrl();
                if (!$redirect || $redirect === $this->getConfig()->getBackendUrl(true)) {
                	$redirect = $this->getUser()->getLastPage();
                }
                if (!$redirect) {
                	$redirect = $this->getConfig()->getBackendUrl(true);
                }
                $this->redirect(array('url' => $redirect));        		
        	}
        } catch (waException $e) {
        	$this->view->assign('error', $e->getMessage());
        }
        
       	$this->view->assign('options', $auth->getOptions());	
        
        if (waRequest::getMethod() != 'post') {
        	$this->view->assign('remember', waRequest::cookie('remember', 1));
        }
        if (waRequest::isMobile()) {
            $this->template = 'LoginMobile';
        }
    }
}

// EOF