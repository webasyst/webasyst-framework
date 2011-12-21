<?php 

class siteSnippetsAction extends waViewAction
{
	public function execute()
	{
		$model = new siteSnippetModel();
		$blocks = $model->order('sort')->fetchAll('id');
		$this->view->assign('blocks', $blocks);
		$block = waRequest::get('id', current(array_keys($blocks)));
		if ($block && isset($blocks[$block])) {
			$block = $blocks[$block];
		} else {
			$block = null;
		}
		$this->view->assign('block', $block);
		$this->view->assign('editor', true);
		
		$this->view->assign('domain_id', siteHelper::getDomainId());		
	}
}