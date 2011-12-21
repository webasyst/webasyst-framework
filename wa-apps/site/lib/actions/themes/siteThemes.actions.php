<?php

class siteThemesActions extends waViewActions
{
	protected $response = array();
	protected $errors = array();

	private $theme_slug;
	private $theme_id;
	private $domain;
	private $theme_params;
	private $apps = array();

	protected function preExecute()
	{
		$rq = $this->getRequest();
		if(!$this->domain = $rq->post('domain')){
			$this->domain = array();
			$domains = siteHelper::getDomains(true);
			foreach ($domains as $domain) {
				$this->domain[] = $domain['name'];
			}

		}
		$this->apps = wa()->getApps();
		foreach($this->apps as $id => $info) {

			if (
			!isset($info['themes']) || !$info['themes'] || //no themes support
			!isset($info['frontend']) || !$info['frontend'] //here no frontend
			) {
				unset($this->apps[$id]);
			}
		}
		if($this->theme_slug = $rq->post('slug')){
			$this->theme_id = $rq->post('id');

			$this->theme_params = array(
				'name'=>$rq->post('name'),
			);
			$this->setTemplate('Row');
		} else {
		}
	}

	public function postExecute()
	{
		if(!$this->errors) {
			$this->view->assign('domain',$this->domain);
			$this->view->assign('domain_id',siteHelper::getDomainId());
		}
	}

	protected function defaultAction()
	{
		$this->setTemplate(ucfirst($this->action));

		$apps = array();
		$this->view->assign('error',false);
		$messages = array();
		//$messages = installerMessage::getInstance()->handle(waRequest::get('msg'));
		$themes = array();
		try {
			$storage = wa()->getStorage();

			foreach($this->apps as $id => $info) {
				$app_id = $info['id'];
				$app_themes = wa()->getThemes($app_id);
				$app_themes = siteThemes::load($app_themes, $this->domain, $app_id);
				foreach($app_themes as $theme_id=>$theme) {
					$themes["{$app_id}/themes/{$theme_id}"] = $theme;
				}
			}
			if ($themes) {
				$themes = siteThemes::sort($themes);
			}

			$this->view->assign('themes',$themes);
		} catch(Exception $ex) {
			$messages[] = array('text'=>$ex->getMessage(),'result'=>'fail');
		}

		$this->view->assign('messages',$messages);
		$this->view->assign('apps',$this->apps);
	}

	protected function renameAction()
	{
		try {
			$theme_info = siteThemes::getInstance($this->theme_slug)->move($this->theme_id,$this->theme_params)->getInfo($this->domain);
			$this->theme_slug = urlencode($theme_info['slug']);
			$this->view->assign('theme',$theme_info);
			$this->view->assign('apps',$this->apps);
		} catch (waException $ex) {
			$this->setError($ex->getMessage());
		}
	}

	protected function purgeAction()
	{
		siteThemes::getInstance($this->theme_slug)->purge();
	}

	protected function brushAction()
	{
		try {
			$this->view->assign('theme',siteThemes::getInstance($this->theme_slug)->brush()->getInfo($this->domain));
			$this->view->assign('apps',$this->apps);
		} catch (waException $ex) {
			$this->setError($ex->getMessage());
		}
	}

	protected function uploadAction()
	{
		if($file = $this->getRequest()->file('theme_files')) {
			/**
			 *
			 * @var waRequestFile
			 */
			if ($file->uploaded()) {
				try {
					$theme_info = siteThemes::extract($file->tmp_name)->getInfo($this->domain);
					$this->response['debug'] = $theme_info;
					$this->theme_slug = urlencode("{$theme_info['app']}/themes/{$theme_info['id']}");
					$this->response['slug'] = $this->theme_slug;
				} catch (Exception $ex) {
					waFiles::delete($file->tmp_name);
					$this->setError($ex->getMessage());
				}
			} else {
				$this->setError($file->error);
			}
		}
	}

	protected function infoAction()
	{
		try {
			$this->view->assign('theme',siteThemes::getInstance($this->theme_slug)->getInfo($this->domain));
			$this->view->assign('apps',$this->apps);
		} catch (waException $ex) {
			$this->setError($ex->getMessage());
		}
	}

	protected function copyAction()
	{
		try {
			$this->view->assign('theme',siteThemes::getInstance($this->theme_slug)->duplicate()->getInfo($this->domain));
			$this->view->assign('apps',$this->apps);
		} catch (waException $ex) {
			$this->setError($ex->getMessage());
		}
	}
	public function display($params = null)
	{
		if($this->action != 'default') {
			$response = $this->getResponse();
			$response->addHeader('Content-Type', 'text/javascript; charset=utf-8');
			$response->sendHeaders();
			if (!$this->errors) {

				if(!$this->response) {
					$this->response['content'] = $this->view->fetch($this->getTemplate() );
				}
				if($this->theme_slug && !isset($this->response['slug'])) {
					$this->response['slug'] = $this->theme_slug;
				}
				$data = array('status' => 'ok', 'data' => $this->response);
				echo json_encode($data);
			} else {
				waSystem::getInstance()->getResponse()->sendHeaders();
				echo json_encode(array('status' => 'fail', 'errors' => $this->errors));
			}

		} else {
			parent::display();
		}
	}

	public function setError($message, $data = array())
	{
		$this->errors[] = array($message, $data);
	}
}