<?php

class blogFavoritePluginBackendAddController extends waJsonController
{
	
    public function execute() 
    {
		$post_id = waRequest::get('post_id', false, 'int');
		if ($post_id) {
			$fovirite_model = new blogFavoritePluginModel();
			$fovirite_model->insert(array(
				'contact_id' => wa()->getUser()->getId(),
				'post_id' => $post_id,
				'datetime' => date('Y-m-d H:i:s'),
			));
			$this->response = $post_id;
		}
    }
}

