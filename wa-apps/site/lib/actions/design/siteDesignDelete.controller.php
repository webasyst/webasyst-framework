<?php 


class siteDesignDeleteController extends waJsonController
{
	public function execute()
	{
		$app = waRequest::get('app', $this->getApp());
		$theme = waRequest::post('theme', 'default');
		$file = waRequest::post('file');
		
		$theme = new waTheme($theme, $app);
		$theme->removeFile($file);
	}
}