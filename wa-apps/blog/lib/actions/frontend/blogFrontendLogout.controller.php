<?php

class blogFrontendlogoutController extends waController
{
	public function execute()
	{
        wa()->getAuth()->clearAuth();
		wa()->getStorage()->remove('auth_user_data');
		$this->redirect(waRequest::server('HTTP_REFERER'));
	}
}
