<?php

class blogBlogGetBlogUrlController extends waJsonController
{
	public function execute()
	{
		$this->getResponse()->addHeader('Content-type', 'application/json');
		$blog_name = waRequest::post('blog_name', '', waRequest::TYPE_STRING_TRIM);

		$this->response = array(
			'slug' => blogHelper::transliterate($blog_name),
		);
	}
}