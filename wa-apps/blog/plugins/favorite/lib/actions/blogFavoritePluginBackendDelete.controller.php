<?php

class blogFavoritePluginBackendDeleteController extends waJsonController
{
	
    public function execute() 
    {
		$post_id = waRequest::get('post_id', false, 'int');
		if ($post_id) {
			$fovirite_model = new blogFavoritePluginModel();
			$fovirite_model->deleteByField(array(
				'contact_id' => wa()->getUser()->getId(),
				'post_id' => $post_id,
			));
			$this->response = $post_id;
		}
    }
}

