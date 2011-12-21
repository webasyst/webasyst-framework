<?php

class siteThemesDownloadController extends waController
{
	public function execute()
	{
		//make cache
		$target_file = null;
		$slug = $this->getRequest()->get('slug');
		$theme = siteThemes::getInstance($slug);
		$target_file = $theme->compress(wa()->getDataPath("export/themes"));
		waFiles::readFile($target_file,basename($target_file),false);
		waFiles::delete($target_file);
	}
}