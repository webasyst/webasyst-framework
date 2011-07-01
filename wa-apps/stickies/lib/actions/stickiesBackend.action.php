<?php
/**
 *
 * @author WebAsyst Team
 * @package Stickies
 *
 */
class stickiesBackendAction extends waViewAction
{
	private $user = null;
	private $app_id = null;
	private $allow_add = false;
	function __construct()
	{
		$this->user = $this->getUser();
		$this->app_id = waSystem::getInstance()->getApp();
		if (!$this->user->isAdmin($this->app_id) && !$this->user->getRights($this->app_id)) {
			throw new waException(null, 403);
		}
		$this->allow_add = $this->user->getRights($this->app_id,'add_sheet');
		//TODO use for cache time SystemConfig::isDebug()
		$this->cache_time = 0;1800;
		$this->cache_id = $this->allow_add?'Y':'N';
		parent::__construct();
	}

	public function execute()
	{
		/**
		 * @var stickiesAppConfig
		 */
		$config = $this->getConfig();

		$stick_sizes = $config->getStickiesSizes();
		$this->view->assign('stick_sizes',$stick_sizes);
		$this->view->assign('stick_min_size',min($stick_sizes));
		$this->view->assign('stick_max_size',max($stick_sizes));

		$stick_colors = $config->getStickiesColors();
		$this->view->assign('stick_colors',array_keys($stick_colors));
		$this->view->assign('stick_colors_css',$stick_colors);

		$sheet_backgrounds = $config->getSheetBackgrounds();
		$this->view->assign('sheet_backgrounds',$sheet_backgrounds);

		$this->view->assign('rights_add_sheet',$this->allow_add);
	}
	
	public function getTemplate()
	{
		$template = parent::getTemplate();
		if (($id = $this->getRequest()->isMobile())||false) {
			$this->view->assign('mobile_id',$id);
			$template = str_replace('templates/actions/', 'templates/actions-mobile/', $template);
		}
		return $template;
	}
}

?>