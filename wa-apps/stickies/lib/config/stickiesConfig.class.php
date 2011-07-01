<?php
class stickiesConfig extends waAppConfig
{
	protected $application_config = array();
	public function init()
	{
		parent::init();
		$this->application_config = include($this->getAppPath().'/lib/config/config.php');
	}
	
	public function getStickiesSizes()
	{
		return $this->application_config&&isset($this->application_config['sizes'])?$this->application_config['sizes']:array(150,200,300,400);
	}
	
	public function getStickiesColors()
	{
		return $this->application_config&&isset($this->application_config['colors'])?$this->application_config['colors']:array();
	}
	
	public function getSheetBackgrounds()
	{
		return $this->application_config&&isset($this->application_config['backgrounds'])?$this->application_config['backgrounds']:array();
	}
}
?>