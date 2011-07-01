<?php
abstract class stickiesApiMethod extends waApiMethod
{

	/**
	 *
	 * @var stickiesSheetModel
	 */
	private $sheet_model;

	/**
	 *
	 * @var stickiesStickyModel
	 */
	private $stickies_model;

	/**
	 *
	 * @return stickiesSheetModel
	 */
	protected function getSheetModel()
	{
		if(!$this->sheet_model){
			$this->sheet_model = new stickiesSheetModel();
		}
		return $this->sheet_model;
	}

	/**
	 *
	 * @return stickiesStickyModel
	 */
	protected function getStickiesModel()
	{
		if(!$this->stickies_model){
			$this->stickies_model = new stickiesStickyModel();
		}
		return $this->stickies_model;
	}

	/**
	 * 
	 * @var array
	 */
	protected $params_definition = array();
	/**
	 * 
	 * @param $params array
	 * @return array
	 */
	protected function castParams($params = array())
	{
		waRequest::setParam($params);
		foreach($this->params_definition as $param => $definition){
			if(isset($definition['required'])&&$definition['required']){
				if(!isset($params[$param])){
					throw new waApiException(100,sprintf('Param %s are required',$param));
				}
			}
			$type = (isset($definition['type']) && $definition['type']) ? $definition['type'] : null;
			$default = isset($definition['default']) ? $definition['default'] : false;
			waRequest::setParam($param,waRequest::param($param, $default, $type));
		}
		
		return waRequest::param();
	}
}
?>