<?php

class blogFrontendlogoutController extends waController
{
	public function execute()
	{
        wa()->getAuth()->clearAuth();
		wa()->getStorage()->remove('auth_user_data');
        throw new waException(_ws('Page not found'), 404);
	}
}
