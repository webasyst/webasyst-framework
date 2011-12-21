<?php

class webasystLoginAction extends waLoginAction
{
   
    protected function afterAuth()
    {
    	$redirect = $this->getConfig()->getCurrentUrl();
        if (!$redirect || $redirect === $this->getConfig()->getBackendUrl(true)) {
        	$redirect = $this->getUser()->getLastPage();
        }
        if (!$redirect || $redirect == $this->getConfig()->getBackendUrl(true).'?module=login') {
        	$redirect = $this->getConfig()->getBackendUrl(true);
        }
        $this->redirect(array('url' => $redirect));        		
    }
}

// EOF