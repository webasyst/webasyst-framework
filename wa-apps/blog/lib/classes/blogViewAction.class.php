<?php
class blogViewAction extends waViewAction
{
	public function display($clear_assign = false)
	{
		$this->view->getHelper()->globals($this->getRequest()->param());
		return parent::display($clear_assign);
	}
}